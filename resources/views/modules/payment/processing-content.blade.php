<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cog fa-spin me-2"></i>
                        Procesamiento de Pago 3D Secure
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Debug Mode:</strong> Visualización detallada de cada paso del proceso
                    </div>
                </div>
            </div>

            @if($result && isset($result['flow_results']))
                <!-- PASO 1: Instrument Identifier -->
                @if(isset($result['flow_results']['step1']))
                    <div class="card mb-3 border-primary">
                        <div class="card-header bg-primary bg-opacity-10">
                            <h5 class="mb-0 text-primary">
                                <i class="fas fa-fingerprint me-2"></i>
                                PASO 1: Crear Instrument Identifier
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>URL:</strong></div>
                                <div class="col-md-9"><code>{{ $result['flow_results']['step1']['url'] ?? 'N/A' }}</code></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>HTTP Code:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge {{ ($result['flow_results']['step1']['http_code'] ?? 0) >= 200 && ($result['flow_results']['step1']['http_code'] ?? 0) < 300 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $result['flow_results']['step1']['http_code'] ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Request:</strong></div>
                                <div class="col-md-9">
                                    <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode(json_decode($result['flow_results']['step1']['request_payload'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3"><strong>Response:</strong></div>
                                <div class="col-md-9">
                                    <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode(json_decode($result['flow_results']['step1']['response'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- PASO 2: Payment Instrument -->
                @if(isset($result['flow_results']['step2']))
                    <div class="card mb-3 border-success">
                        <div class="card-header bg-success bg-opacity-10">
                            <h5 class="mb-0 text-success">
                                <i class="fas fa-credit-card me-2"></i>
                                PASO 2: Crear Payment Instrument
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>URL:</strong></div>
                                <div class="col-md-9"><code>{{ $result['flow_results']['step2']['url'] ?? 'N/A' }}</code></div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>HTTP Code:</strong></div>
                                <div class="col-md-9">
                                    <span class="badge {{ ($result['flow_results']['step2']['http_code'] ?? 0) >= 200 && ($result['flow_results']['step2']['http_code'] ?? 0) < 300 ? 'bg-success' : 'bg-danger' }}">
                                        {{ $result['flow_results']['step2']['http_code'] ?? 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3"><strong>Request:</strong></div>
                                <div class="col-md-9">
                                    <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode(json_decode($result['flow_results']['step2']['request_payload'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3"><strong>Response:</strong></div>
                                <div class="col-md-9">
                                    <pre class="bg-light p-2 rounded" style="max-height: 200px; overflow-y: auto;"><code>{{ json_encode(json_decode($result['flow_results']['step2']['response'] ?? '{}'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- More steps can be added here as needed -->

            @else
                <div class="card">
                    <div class="card-body text-center py-5">
                        <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Procesando...</span>
                        </div>
                        <h4>Procesando su pago...</h4>
                        <p class="text-muted">Por favor espere mientras procesamos su transacción de forma segura.</p>
                    </div>
                </div>
            @endif

            <!-- Back Button -->
            <div class="text-center mt-4">
                <a href="{{ route('payment.checkout') }}" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Formulario
                </a>
            </div>
        </div>
    </div>
</div>

