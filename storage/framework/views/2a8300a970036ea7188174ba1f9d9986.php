<?php $__env->startSection('title', 'Verificando Dispositivo - Pasarela de Pagos'); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-shield-alt me-2"></i>
                            Verificación de Seguridad
                        </h5>
                    </div>
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-spinner fa-spin fa-3x text-info"></i>
                        </div>
                        <h4 class="mb-3">Verificando su dispositivo...</h4>
                        <p class="text-muted">
                            Estamos verificando la seguridad de su transacción.<br>
                            Este proceso toma solo unos segundos.
                        </p>
                        
                        <div class="progress mt-4" style="height: 25px;">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                 role="progressbar" 
                                 style="width: 0%" 
                                 id="verificationProgress">
                                0%
                            </div>
                        </div>
                        
                        <p class="text-muted mt-3 small">
                            <i class="fas fa-lock me-1"></i>
                            Conexión segura - Sus datos están protegidos
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden iframe for device data collection -->
    <iframe id="cardinal_collection_iframe" 
            name="collectionIframe" 
            height="1" 
            width="1" 
            style="display: none;">
    </iframe>

    <!-- Hidden form -->
    <form id="cardinal_collection_form" 
          method="POST" 
          target="collectionIframe" 
          action="<?php echo e($deviceDataCollectionUrl); ?>">
        <input type="hidden" name="JWT" value="<?php echo e($accessToken); ?>">
        <?php if($deviceFingerprintSessionId && $merchantId): ?>
        <!-- SessionId para Cardinal = merchantId + sessionId -->
        <input type="hidden" name="SessionId" value="<?php echo e($merchantId); ?><?php echo e($deviceFingerprintSessionId); ?>">
        <?php endif; ?>
    </form>

    <!-- Form to continue to enrollment -->
    <form id="continue_form" method="POST" action="<?php echo e(route('payment.continue-after-collection')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="payment_instrument_id" value="<?php echo e($paymentInstrumentId); ?>">
        <input type="hidden" name="setup_data" value="<?php echo e(json_encode($setupData)); ?>">
    </form>

    <script>
        // Version: <?php echo e(now()->timestamp); ?> - Force browser cache refresh
        // ✅ CRÍTICO: Este es el ThreatMetrix SessionId (generado al cargar checkout)
        const threatMetrixSessionId = <?php echo json_encode($deviceFingerprintSessionId ?? null, 15, 512) ?>;
        const merchantId = <?php echo json_encode($merchantId ?? '', 15, 512) ?>;
        
        console.log('📱 Device Fingerprint Configuration:');
        console.log('  - ThreatMetrix Session ID (backend):', threatMetrixSessionId);
        console.log('  - Merchant ID:', merchantId);
        console.log('  - Full SessionId for Cardinal:', merchantId + threatMetrixSessionId);
        console.log('  - ⚠️ IMPORTANTE: Usaremos ThreatMetrix SessionId, NO el de Cardinal');

        // Auto-submit device collection form
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📤 Starting device data collection...');
            
            // ✅ Listen for device collection response BEFORE submitting
            window.addEventListener("message", function(event) {
                // Validate origin based on environment (test vs production)
                const validOrigins = [
                    'https://centinelapistag.cardinalcommerce.com',  // Test
                    'https://centinelapi.cardinalcommerce.com'       // Production
                ];
                
                if (validOrigins.includes(event.origin)) {
                    console.log('✅ Device collection response received:', event.data);
                    console.log('🔍 Debugging - Type of event.data:', typeof event.data);
                    
                    // ✅ CORRECCIÓN: Parsear el JSON si viene como string
                    let parsedData = event.data;
                    if (typeof event.data === 'string') {
                        try {
                            parsedData = JSON.parse(event.data);
                            console.log('✅ JSON parsed successfully:', parsedData);
                        } catch (e) {
                            console.error('❌ Failed to parse JSON:', e);
                            parsedData = event.data;
                        }
                    }
                    
                    console.log('🔍 Debugging - Parsed data type:', typeof parsedData);
                    console.log('🔍 Debugging - Parsed data keys:', Object.keys(parsedData || {}));
                    
                    // Try multiple ways to access SessionId from parsed data (Cardinal)
                    const cardinalSessionId = parsedData?.SessionId || parsedData?.sessionId || parsedData?.['Session Id'];
                    console.log('🔍 Cardinal SessionId captured:', cardinalSessionId);
                    
                    // ✅ CRÍTICO: NO sobrescribir el ThreatMetrix SessionId con el de Cardinal
                    // Cardinal tiene su propio sessionId para 3DS, pero Decision Manager necesita el de ThreatMetrix
                    
                    if (cardinalSessionId) {
                        console.log('📱 Cardinal SessionId received (para 3DS):', cardinalSessionId);
                        console.log('✅ ThreatMetrix SessionId preserved (para Decision Manager):', threatMetrixSessionId);
                        
                        // Add ThreatMetrix SessionId to the continue form (NO el de Cardinal)
                        const continueForm = document.getElementById('continue_form');
                        console.log('📋 Continue form found:', continueForm !== null);
                        
                        if (continueForm) {
                            // ✅ USAR ThreatMetrix SessionId, NO Cardinal
                            const existingInput = document.querySelector('input[name="device_fingerprint_session_id"]');
                            if (!existingInput) {
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'device_fingerprint_session_id';
                                hiddenInput.value = threatMetrixSessionId;  // ⭐ ThreatMetrix SessionId
                                continueForm.appendChild(hiddenInput);
                                console.log('✅ Hidden field added with ThreatMetrix SessionId:', threatMetrixSessionId);
                            } else {
                                console.log('ℹ️ Hidden field already exists, preserving ThreatMetrix SessionId');
                                existingInput.value = threatMetrixSessionId;  // ⭐ ThreatMetrix SessionId
                            }
                            
                            // Verify the field was added
                            const verifyInput = document.querySelector('input[name="device_fingerprint_session_id"]');
                            console.log('🔍 Verification - ThreatMetrix SessionId in form:', verifyInput ? verifyInput.value : 'NOT FOUND');
                        } else {
                            console.error('❌ Continue form not found!');
                        }
                    } else {
                        console.warn('⚠️ Cardinal SessionId not found in parsed data:', parsedData);
                        console.log('🔍 Original event.data (raw):', event.data);
                        console.log('🔍 Parsed data:', parsedData);
                    }
                }
            }, false);
            
            // Submit the form to CardinalCommerce
            setTimeout(function() {
                document.getElementById('cardinal_collection_form').submit();
                console.log('✅ Device collection form submitted');
            }, 500);

            // Progress bar animation
            let progress = 0;
            const progressBar = document.getElementById('verificationProgress');
            const progressInterval = setInterval(function() {
                progress += 10;
                progressBar.style.width = progress + '%';
                progressBar.textContent = progress + '%';
                
                if (progress >= 100) {
                    clearInterval(progressInterval);
                }
            }, 1000);

            // After 10 seconds, continue with enrollment
            setTimeout(function() {
                console.log('✅ Device collection completed, continuing with payment...');
                console.log('📱 ThreatMetrix Session ID (Decision Manager):', threatMetrixSessionId);
                
                // Final verification before submit
                const form = document.getElementById('continue_form');
                const sessionIdField = form.querySelector('input[name="device_fingerprint_session_id"]');
                
                // ✅ ÚLTIMA VERIFICACIÓN: Asegurar que el campo tiene el ThreatMetrix SessionId
                if (sessionIdField) {
                    if (sessionIdField.value !== threatMetrixSessionId) {
                        console.warn('⚠️ CORRECTING: Field had wrong value, setting ThreatMetrix SessionId');
                        sessionIdField.value = threatMetrixSessionId;
                    }
                    console.log('✅ FINAL CHECK - ThreatMetrix SessionId in form:', sessionIdField.value);
                } else {
                    console.error('❌ FINAL CHECK - Session ID field NOT in form! Adding now...');
                    // Agregar el campo si no existe
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    hiddenInput.name = 'device_fingerprint_session_id';
                    hiddenInput.value = threatMetrixSessionId;
                    form.appendChild(hiddenInput);
                }
                
                // Log all form data
                const formData = new FormData(form);
                console.log('📝 Form data being submitted:');
                for (let [key, value] of formData.entries()) {
                    console.log(`  ${key}: ${value.substring(0, 50)}${value.length > 50 ? '...' : ''}`);
                }
                
                document.getElementById('continue_form').submit();
            }, 10000);
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/device-collection.blade.php ENDPATH**/ ?>