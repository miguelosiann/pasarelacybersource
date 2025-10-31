<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Message -->
            <div class="card border-success">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="text-success mb-3">¡Pago Procesado Exitosamente!</h2>
                    <p class="lead text-muted mb-4">
                        Su transacción ha sido completada con éxito.
                    </p>
                </div>
            </div>

            <!-- Payment Details -->
            <div class="card mt-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-receipt me-2"></i>
                        Detalles del Pago
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>ID de Transacción:</strong>
                            <p class="mb-0">{{ $payment->transaction_id ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Fecha:</strong>
                            <p class="mb-0">{{ $payment->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Monto:</strong>
                            <p class="mb-0 text-success fs-4">
                                @if($payment->currency === 'USD')
                                    ${{ number_format($payment->amount, 2) }} USD
                                @elseif($payment->currency === 'CRC')
                                    ₡{{ number_format($payment->amount, 2) }} CRC
                                @elseif($payment->currency === 'EUR')
                                    €{{ number_format($payment->amount, 2) }} EUR
                                @elseif($payment->currency === 'MXN')
                                    ${{ number_format($payment->amount, 2) }} MXN
                                @else
                                    {{ $payment->currency }} {{ number_format($payment->amount, 2) }}
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <strong>Estado:</strong>
                            <p class="mb-0">
                                <span class="badge bg-success">{{ ucfirst($payment->status) }}</span>
                            </p>
                        </div>
                    </div>

                    @if($payment->card_last_four)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Tarjeta:</strong>
                                <p class="mb-0">
                                    <i class="fas fa-credit-card me-2"></i>
                                    **** **** **** {{ $payment->card_last_four }}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <strong>Tipo:</strong>
                                <p class="mb-0">{{ ucfirst($payment->card_type ?? 'N/A') }}</p>
                            </div>
                        </div>
                    @endif

                    @if($payment->authorization_code)
                        <div class="row mb-3">
                            <div class="col-12">
                                <strong>Código de Autorización:</strong>
                                <p class="mb-0">{{ $payment->authorization_code }}</p>
                            </div>
                        </div>
                    @endif

                    @if($payment->flow_type)
                        <div class="row mb-3">
                            <div class="col-12">
                                <strong>Tipo de Autenticación:</strong>
                                <p class="mb-0">
                                    @if($payment->flow_type === 'frictionless')
                                        <i class="fas fa-shield-check text-success me-2"></i>
                                        3D Secure - Sin Fricción
                                    @elseif($payment->flow_type === 'challenge')
                                        <i class="fas fa-shield-alt text-primary me-2"></i>
                                        3D Secure - Challenge
                                    @else
                                        {{ ucfirst($payment->flow_type) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif

                    @if($payment->liability_shift)
                        <div class="alert alert-info mt-3" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Protección Mejorada:</strong> Esta transacción cuenta con protección contra fraude 3D Secure.
                        </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="card mt-4">
                <div class="card-body">
                    <h5 class="mb-3">¿Qué desea hacer ahora?</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <a href="/" class="btn btn-primary w-100">
                                <i class="fas fa-home me-2"></i>
                                Ir al Dashboard
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('payment.history') }}" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-history me-2"></i>
                                Ver Historial
                            </a>
                        </div>
                        <div class="col-md-4">
                            <button onclick="window.print()" class="btn btn-outline-primary w-100">
                                <i class="fas fa-print me-2"></i>
                                Imprimir Recibo
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Support Info -->
            <div class="alert alert-light mt-4" role="alert">
                <strong>Nota:</strong> Se ha enviado un comprobante de pago a su correo electrónico. 
                Si tiene alguna pregunta, puede contactarnos a través de 
                <a href="mailto:soporte@osiann.com">soporte técnico</a>.
            </div>
        </div>
    </div>
</div>

