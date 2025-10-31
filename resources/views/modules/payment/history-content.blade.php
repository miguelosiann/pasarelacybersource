<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-history me-2"></i>
                            Historial de Pagos
                        </h5>
                        <a href="{{ route('payment.checkout') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-plus me-2"></i>
                            Nuevo Pago
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if($payments->isEmpty())
                        <div class="text-center py-5">
                            <i class="fas fa-receipt text-muted icon-lg"></i>
                            <p class="text-muted mt-3">No hay pagos registrados</p>
                            <a href="{{ route('payment.checkout') }}" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>
                                Realizar Primer Pago
                            </a>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Fecha</th>
                                        <th>Monto</th>
                                        <th>Estado</th>
                                        <th>Tarjeta</th>
                                        <th>Tipo 3DS</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($payments as $payment)
                                        <tr>
                                            <td>
                                                <code>#{{ $payment->id }}</code>
                                            </td>
                                            <td>
                                                {{ $payment->created_at->format('d/m/Y H:i') }}
                                                <br>
                                                <small class="text-muted">{{ $payment->created_at->diffForHumans() }}</small>
                                            </td>
                                            <td>
                                                <strong>
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
                                                </strong>
                                            </td>
                                            <td>
                                                @if($payment->status === 'completed')
                                                    <span class="badge bg-success">Completado</span>
                                                @elseif($payment->status === 'failed')
                                                    <span class="badge bg-danger">Fallido</span>
                                                @elseif($payment->status === 'processing')
                                                    <span class="badge bg-warning">Procesando</span>
                                                @else
                                                    <span class="badge bg-secondary">{{ ucfirst($payment->status) }}</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->card_last_four)
                                                    <i class="fas fa-credit-card me-1"></i>
                                                    **** {{ $payment->card_last_four }}
                                                    <br>
                                                    <small class="text-muted">{{ ucfirst($payment->card_type ?? '') }}</small>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($payment->flow_type === 'frictionless')
                                                    <i class="fas fa-shield-check text-success" title="Sin Fricción"></i>
                                                    <small class="text-muted d-block">Frictionless</small>
                                                @elseif($payment->flow_type === 'challenge')
                                                    <i class="fas fa-shield-alt text-primary" title="Challenge"></i>
                                                    <small class="text-muted d-block">Challenge</small>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td>
                                                <a href="{{ route('payment.show', $payment->id) }}" 
                                                   class="btn btn-sm btn-outline-primary"
                                                   title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $payments->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

