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
                        
                        <div class="progress mt-4 payment-progress">
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
            class="device-collection-iframe">
    </iframe>

    <!-- Hidden form -->
    <form id="cardinal_collection_form" 
          method="POST" 
          target="collectionIframe" 
          action="<?php echo e($deviceDataCollectionUrl); ?>">
        <input type="hidden" name="JWT" value="<?php echo e($accessToken); ?>">
    </form>

    <!-- Form to continue to enrollment -->
    <form id="continue_form" method="POST" action="<?php echo e(route('payment.continue-after-collection')); ?>">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="payment_instrument_id" value="<?php echo e($paymentInstrumentId); ?>">
        <input type="hidden" name="setup_data" value="<?php echo e(json_encode($setupData)); ?>">
    </form>

    <script>
        // Auto-submit device collection form
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📤 Starting device data collection...');
            
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
                document.getElementById('continue_form').submit();
            }, 10000);
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('template.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/device-collection.blade.php ENDPATH**/ ?>