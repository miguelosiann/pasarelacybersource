<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Error Message -->
            <div class="card border-danger">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-times-circle text-danger icon-xl"></i>
                    </div>
                    <h2 class="text-danger mb-3">Pago No Procesado</h2>
                    <p class="lead text-muted mb-4">
                        Lo sentimos, no pudimos procesar su pago.
                    </p>
                </div>
            </div>

            <!-- Error Details -->
            <div class="card mt-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Detalles del Error
                    </h5>
                </div>
                <div class="card-body">
                    @if($error)
                        <div class="alert alert-danger" role="alert">
                            <strong>Error:</strong> {{ $error }}
                        </div>
                    @else
                        <p class="text-muted">
                            No se pudo completar la transacción. Por favor verifique su información e intente nuevamente.
                        </p>
                    @endif

                    <h6 class="mt-4 mb-3">Posibles causas:</h6>
                    <ul class="text-muted">
                        <li>Fondos insuficientes en la tarjeta</li>
                        <li>Información de la tarjeta incorrecta</li>
                        <li>La tarjeta ha expirado</li>
                        <li>La transacción fue rechazada por el banco emisor</li>
                        <li>Problemas de conectividad durante el proceso</li>
                        <li>Autenticación 3D Secure no completada</li>
                    </ul>
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="mb-3">¿Qué desea hacer?</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="{{ route('payment.checkout') }}" class="btn btn-primary w-100">
                                <i class="fas fa-redo me-2"></i>
                                Intentar Nuevamente
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="/" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-home me-2"></i>
                                Volver al Inicio
                            </a>
                        </div>
                    </div>

                    <div class="row g-3 mt-2">
                        <div class="col-md-6">
                            <a href="mailto:soporte@tupasarela.com" class="btn btn-outline-info w-100">
                                <i class="fas fa-life-ring me-2"></i>
                                Contactar Soporte
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-history me-2"></i>
                                Ver Historial
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Help Card -->
            <div class="card mt-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        ¿Necesita Ayuda?
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-3">Si continúa experimentando problemas, puede:</p>
                    <ul class="mb-0">
                        <li>Verificar con su banco que la tarjeta esté activa para compras en línea</li>
                        <li>Intentar con una tarjeta diferente</li>
                        <li>Contactar a su banco para verificar si la transacción fue bloqueada</li>
                        <li>Crear un ticket de soporte para asistencia personalizada</li>
                    </ul>
                </div>
            </div>

            <!-- Security Notice -->
            <div class="alert alert-light mt-4" role="alert">
                <i class="fas fa-shield-alt me-2"></i>
                <strong>Seguridad:</strong> Ningún cargo fue realizado a su tarjeta. 
                Su información está protegida y encriptada durante todo el proceso.
            </div>
        </div>
    </div>
</div>

