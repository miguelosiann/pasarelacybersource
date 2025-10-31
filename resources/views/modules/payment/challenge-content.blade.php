<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Challenge Header -->
            <div class="card mb-4">
                <div class="card-body text-center py-4">
                    <i class="fas fa-shield-alt text-primary fa-3x mb-3"></i>
                    <h2 class="mb-2">Autenticación 3D Secure</h2>
                    <p class="text-muted mb-0">
                        Por favor complete la autenticación con su banco
                    </p>
                </div>
            </div>

            <!-- Challenge Content -->
            <div class="card">
                <div class="card-body">
                    <div id="challenge-container" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="mt-3">Cargando autenticación...</p>
                    </div>

                    <!-- Challenge iframe will be loaded here -->
                    <div id="challenge-iframe-container" class="d-none">
                        <iframe 
                            id="challenge-iframe" 
                            name="challengeFrame"
                            style="width: 100%; height: 600px; border: 1px solid #dee2e6; border-radius: 8px;"
                            sandbox="allow-forms allow-scripts allow-same-origin allow-top-navigation allow-modals allow-popups"
                        ></iframe>
                    </div>

                    <!-- Loading Message -->
                    <div id="processing-message" class="alert alert-info mt-4 d-none" role="alert">
                        <div class="d-flex align-items-center">
                            <div class="spinner-border spinner-border-sm me-3" role="status">
                                <span class="visually-hidden">Procesando...</span>
                            </div>
                            <div>
                                <strong>Procesando autenticación...</strong>
                                <p class="mb-0">Por favor no cierre esta ventana.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Error Message -->
                    <div id="error-message" class="alert alert-danger mt-4 d-none" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Error en la autenticación</strong>
                        <p class="mb-0" id="error-text"></p>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="window.location.href='{{ route('payment.checkout') }}'">
                            Volver a intentar
                        </button>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="alert alert-warning mt-4" role="alert">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Instrucciones:</strong>
                <ul class="mb-0 mt-2">
                    <li>Complete la autenticación solicitada por su banco</li>
                    <li>Puede requerir un código SMS o autenticación móvil</li>
                    <li>No cierre esta ventana durante el proceso</li>
                    <li>El proceso puede tardar unos momentos</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Hidden form for challenge submission -->
<form id="challenge-form" 
      action="{{ $challengeData['step_up_url'] ?? '' }}" 
      method="POST" 
      target="challengeFrame"
      style="display: none;">
    @if(isset($challengeData['access_token']))
        <input type="hidden" name="JWT" value="{{ $challengeData['access_token'] }}">
    @endif
    @if(isset($challengeData['reference_id']))
        <input type="hidden" name="MD" value="{{ $challengeData['reference_id'] }}">
    @endif
</form>

<script>
// Challenge data from server
const challengeData = @json($challengeData ?? []);

// Configuration
const config = {
    challengeReturnUrl: "{{ route('payment.challenge.callback') }}",
    successUrl: "{{ route('payment.success', ['payment' => 'PAYMENT_ID']) }}",
    failedUrl: "{{ route('payment.failed') }}",
    authorizeUrl: "{{ route('payment.challenge.authorize') }}",
    csrfToken: "{{ csrf_token() }}"
};

console.log('Challenge Data:', challengeData);
console.log('Config:', config);

// Initialize challenge
document.addEventListener('DOMContentLoaded', function() {
    initializeChallenge();
});

function initializeChallenge() {
    console.log('🚀 Initializing challenge...');
    console.log('📋 Challenge Data:', challengeData);
    
    const challengeContainer = document.getElementById('challenge-container');
    const iframeContainer = document.getElementById('challenge-iframe-container');
    const challengeForm = document.getElementById('challenge-form');

    if (!challengeData) {
        console.error('❌ challengeData is null or undefined');
        showError('Datos de autenticación no disponibles');
        return;
    }

    if (!challengeData.step_up_url) {
        console.error('❌ step_up_url is missing:', challengeData);
        showError('URL de autenticación no disponible. Datos recibidos: ' + JSON.stringify(challengeData));
        return;
    }

    console.log('✅ Challenge data validated');
    console.log('📤 Step Up URL:', challengeData.step_up_url);
    console.log('🔑 Access Token:', challengeData.access_token ? 'Present' : 'Missing');

    // Hide loading, show iframe
    setTimeout(() => {
        console.log('⏰ Timeout reached, loading iframe...');
        challengeContainer.classList.add('d-none');
        iframeContainer.classList.remove('d-none');
        
        // Submit form to iframe
        console.log('📤 Submitting challenge form to iframe');
        console.log('Form action:', challengeForm.action);
        console.log('Form target:', challengeForm.target);
        
        challengeForm.submit();
        
        console.log('✅ Challenge form submitted to iframe');
    }, 1000);

    // Listen for messages from iframe
    window.addEventListener('message', handleChallengeResponse, false);
    console.log('👂 Message listener attached');
}

// Flag para evitar procesamiento duplicado
let processingAuthorization = false;

function handleChallengeResponse(event) {
    console.log('Received message from iframe:', event.data);

    // Validate message origin (in production, check event.origin)
    if (!event.data || typeof event.data !== 'object') {
        console.warn('Invalid message format');
        return;
    }

    // CRITICAL: Avoid duplicate processing
    if (processingAuthorization) {
        console.warn('⚠️ Authorization already in progress, ignoring duplicate message');
        return;
    }

    const challengeResult = event.data;

    // Intentar actualizar UI si los elementos existen
    const processingMessage = document.getElementById('processing-message');
    const iframeContainer = document.getElementById('challenge-iframe-container');
    
    if (processingMessage && iframeContainer) {
        // Show processing message
        processingMessage.classList.remove('d-none');
        iframeContainer.classList.add('d-none');
    } else {
        console.warn('⚠️ DOM elements not found, but continuing with authorization anyway');
    }

    // Handle challenge success - PROCESAR AUNQUE NO EXISTAN LOS ELEMENTOS
    if (challengeResult.success) {
        processingAuthorization = true; // ✅ Set flag to prevent duplicates
        console.log('✅ Challenge successful, processing authorization...');
        processAuthorizationAfterChallenge(challengeResult);
    } else {
        console.error('❌ Challenge failed:', challengeResult.error);
        // Solo intentar mostrar error si los elementos existen
        if (processingMessage && iframeContainer) {
            showError(challengeResult.error || 'La autenticación 3D Secure falló');
        }
    }
}

function processAuthorizationAfterChallenge(challengeResult) {
    // Send challenge result to server for final authorization
    fetch(config.authorizeUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': config.csrfToken
        },
        body: JSON.stringify({
            challenge_result: challengeResult
        })
    })
    .then(response => {
        // Si es 400 y processingAuthorization ya estaba en true, probablemente es llamada duplicada
        if (response.status === 400 && processingAuthorization) {
            console.warn('⚠️ Received 400 error, likely duplicate call - ignoring');
            return null;
        }
        return response.json();
    })
    .then(data => {
        // Si data es null (ignorado por ser duplicado), no hacer nada
        if (data === null) {
            return;
        }
        
        console.log('✅ Authorization response:', data);
        
        if (data.success) {
            // Redirect to success page
            console.log('🎉 Redirecting to success page...');
            window.location.href = data.redirect_url || config.successUrl;
        } else {
            // Reset flag on error
            processingAuthorization = false;
            showError(data.error || 'Error procesando la autorización final');
        }
    })
    .catch(error => {
        console.error('❌ Authorization error:', error);
        processingAuthorization = false; // Reset flag on error
        showError('Error de conexión. Por favor intente nuevamente.');
    });
}

function showError(message) {
    // Verificar que los elementos existan antes de usarlos
    const challengeContainer = document.getElementById('challenge-container');
    const iframeContainer = document.getElementById('challenge-iframe-container');
    const processingMessage = document.getElementById('processing-message');
    const errorDiv = document.getElementById('error-message');
    const errorText = document.getElementById('error-text');
    
    // Si los elementos no existen, la página cambió, no hacer nada
    if (!challengeContainer || !iframeContainer || !processingMessage || !errorDiv || !errorText) {
        console.warn('⚠️ Cannot show error, DOM elements not found');
        return;
    }
    
    challengeContainer.classList.add('d-none');
    iframeContainer.classList.add('d-none');
    processingMessage.classList.add('d-none');
    
    errorText.textContent = message;
    errorDiv.classList.remove('d-none');
}
</script>

