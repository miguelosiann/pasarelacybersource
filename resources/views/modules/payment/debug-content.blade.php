<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="card mb-4">
                <div class="card-header bg-dark text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-bug me-2"></i>
                        🐛 DEBUG MODE - CyberSource Paso por Paso
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Modo Debug:</strong> Ejecuta cada paso del flujo de pago manualmente para ver exactamente qué se envía y qué se recibe.
                    </div>
                </div>
            </div>

            <!-- Formulario de Datos -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">📝 Datos del Formulario</h5>
                </div>
                <div class="card-body">
                    <form id="debugPaymentForm">
                        @csrf
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Monto</label>
                                <input type="number" name="amount" class="form-control" step="0.01" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Moneda</label>
                                <select name="currency" class="form-select" required>
                                    <option value="" disabled selected>Seleccione moneda</option>
                                    <option value="USD">USD</option>
                                    <option value="CRC">CRC</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Número de Tarjeta</label>
                                <input type="text" name="card_number" class="form-control" placeholder="Ingresa el número de tarjeta" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Mes de Expiración</label>
                                <input type="text" name="expiry_month" class="form-control" placeholder="12" maxlength="2" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Año de Expiración</label>
                                <input type="text" name="expiry_year" class="form-control" placeholder="2030" maxlength="4" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Tipo de Tarjeta</label>
                                <select name="card_type" class="form-select" required>
                                    <option value="" disabled selected>Seleccione tipo</option>
                                    <option value="visa">VISA</option>
                                    <option value="mastercard">Mastercard</option>
                                    <option value="american express">American Express</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono (opcional)</label>
                                <input type="text" name="phone" class="form-control" placeholder="+506 1234-5678">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Empresa (opcional)</label>
                                <input type="text" name="company" class="form-control" placeholder="Mi Empresa">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">Dirección</label>
                                <input type="text" name="address1" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Ciudad</label>
                                <input type="text" name="city" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Estado/Provincia (2 letras)</label>
                                <input type="text" name="state" class="form-control" maxlength="2" style="text-transform: uppercase;" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Código Postal</label>
                                <input type="text" name="postal_code" class="form-control" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label class="form-label">País (2 letras)</label>
                                <select name="country" class="form-select" required>
                                    <option value="" disabled selected>Seleccione país</option>
                                    <option value="CR">Costa Rica (CR)</option>
                                    <option value="US">Estados Unidos (US)</option>
                                    <option value="MX">México (MX)</option>
                                </select>
                            </div>
                        </div>

                        <div class="text-center">
                            <button type="button" class="btn btn-primary btn-lg" onclick="saveFormData()">
                                <i class="fas fa-save me-2"></i>
                                1️⃣ Guardar Datos en Sesión
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- PASO 1: Instrument Identifier -->
            <div class="card mb-4">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0 text-success">
                        <i class="fas fa-fingerprint me-2"></i>
                        PASO 1: Crear Instrument Identifier
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">En este paso solo se envía el número de tarjeta para crear un identificador único.</p>
                    <button type="button" class="btn btn-success" onclick="executeStep1()" id="btnStep1" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 1
                    </button>
                    <div id="step1Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 2: Payment Instrument -->
            <div class="card mb-4">
                <div class="card-header bg-info bg-opacity-10">
                    <h5 class="mb-0 text-info">
                        <i class="fas fa-credit-card me-2"></i>
                        PASO 2: Crear Payment Instrument
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Se envía la información completa de la tarjeta (expiración, billing) usando el Instrument ID del paso 1.</p>
                    <button type="button" class="btn btn-info" onclick="executeStep2()" id="btnStep2" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 2
                    </button>
                    <div id="step2Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 3: Setup 3DS -->
            <div class="card mb-4">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0 text-warning">
                        <i class="fas fa-shield-alt me-2"></i>
                        PASO 3: Setup 3D Secure
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Se configura 3D Secure usando el Payment Instrument ID del paso 2.</p>
                    <button type="button" class="btn btn-warning" onclick="executeStep3()" id="btnStep3" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 3
                    </button>
                    <div id="step3Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 3.5: Device Data Collection -->
            <div class="card mb-4">
                <div class="card-header bg-secondary bg-opacity-10">
                    <h5 class="mb-0 text-secondary">
                        <i class="fas fa-desktop me-2"></i>
                        PASO 3.5: Device Data Collection (Iframe Oculto)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Se carga un iframe oculto de CardinalCommerce para recolectar datos del dispositivo del usuario (fingerprinting).</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Este paso recolecta datos del navegador (resolución, timezone, plugins, etc.) que CyberSource usa para detectar fraude.
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="executeStep3_5()" id="btnStep3_5" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 3.5 (Device Collection)
                    </button>
                    
                    <!-- Contenedor para el iframe oculto -->
                    <div id="deviceCollectionContainer" class="mt-3"></div>
                    
                    <div id="step3_5Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 4: Check Enrollment -->
            <div class="card mb-4">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="mb-0 text-danger">
                        <i class="fas fa-search me-2"></i>
                        PASO 4: Check Enrollment
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Se verifica si la tarjeta está inscrita en 3D Secure.</p>
                    <button type="button" class="btn btn-danger" onclick="executeStep4()" id="btnStep4" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 4
                    </button>
                    <div id="step4Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 4.5: Challenge (Y,C) -->
            <div class="card mb-4">
                <div class="card-header bg-warning bg-opacity-10">
                    <h5 class="mb-0 text-warning">
                        <i class="fas fa-shield-alt me-2"></i>
                        PASO 4.5: Challenge 3D Secure (Y,C)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Si el enrollment requiere challenge (Y,C), se muestra el iframe del banco para autenticación.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Escenario Y,C:</strong> La tarjeta requiere autenticación adicional del banco (SMS, app móvil, etc.)
                    </div>
                    <button type="button" class="btn btn-warning" onclick="executeStep4_5()" id="btnStep4_5" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 4.5 (Mostrar Challenge)
                    </button>
                    
                    <!-- Challenge iframe container -->
                    <div id="challengeContainer" class="mt-3 d-none">
                        <div class="card border-warning">
                            <div class="card-header bg-warning bg-opacity-10">
                                <h6 class="mb-0 text-warning">
                                    <i class="fas fa-shield-alt me-2"></i>
                                    Iframe del Banco (Challenge)
                                </h6>
                            </div>
                            <div class="card-body">
                                <iframe 
                                    id="challenge-iframe-debug" 
                                    name="challengeFrameDebug"
                                    style="width: 100%; height: 500px; border: 2px solid #ffc107; border-radius: 8px;"
                                    sandbox="allow-forms allow-scripts allow-same-origin allow-top-navigation allow-modals allow-popups"
                                ></iframe>
                            </div>
                        </div>
                        
                        <!-- Hidden form -->
                        <form id="challenge-form-debug" 
                              method="POST" 
                              target="challengeFrameDebug"
                              style="display: none;">
                            <input type="hidden" name="JWT" id="challenge-jwt-debug">
                        </form>
                    </div>
                    
                    <div id="step4_5Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 5: Authorization (Frictionless Y,Y) -->
            <div class="card mb-4">
                <div class="card-header bg-primary bg-opacity-10">
                    <h5 class="mb-0 text-primary">
                        <i class="fas fa-check-circle me-2"></i>
                        PASO 5: Authorization (Frictionless Y,Y)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Si el enrollment fue exitoso (Y,Y), se procede a la autorización final con captura del pago.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Nota:</strong> Este paso solo aplica cuando <code>veresEnrolled=Y</code> y <code>paresStatus=Y</code> (sin fricción).
                    </div>
                    <button type="button" class="btn btn-primary" onclick="executeStep5()" id="btnStep5" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 5 (Authorization)
                    </button>
                    <div id="step5Result" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 5.5A: Validation Service (Y,C) -->
            <div class="card mb-4">
                <div class="card-header bg-info bg-opacity-10">
                    <h5 class="mb-0 text-info">
                        <i class="fas fa-check-circle me-2"></i>
                        PASO 5.5A: Validation Service (Challenge Y,C)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Valida la autenticación del challenge antes de la autorización final.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Según documentación CyberSource:</strong><br>
                        "Validation is required <strong>only for step-up authentication</strong>. Frictionless authentication does not require this validation step."
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-book me-2"></i>
                        <strong>Endpoint:</strong> <code>POST /risk/v1/authentication-results</code><br>
                        <strong>Request:</strong> authenticationTransactionId + customerId<br>
                        <strong>Response:</strong> Datos 3DS validados (CAVV, ECI, XID, paresStatus, etc.)
                    </div>
                    <button type="button" class="btn btn-info" onclick="executeStep5_5a()" id="btnStep5_5a" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 5.5A (Validation Service)
                    </button>
                    <div id="step5_5aResult" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- PASO 5.5B: Authorization Después de Validation (Y,C) -->
            <div class="card mb-4">
                <div class="card-header bg-success bg-opacity-10">
                    <h5 class="mb-0 text-success">
                        <i class="fas fa-lock me-2"></i>
                        PASO 5.5B: Authorization After Validation (Challenge Y,C)
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Autorización final usando los datos validados del PASO 5.5A.</p>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Importante:</strong> Este paso usa los datos 3DS validados del PASO 5.5A:<br>
                        • CAVV, ECI, XID del validation response<br>
                        • <strong>actionList:</strong> <code>CONSUMER_AUTHENTICATION</code> (ya no VALIDATE)<br>
                        • Completará la autorización con liability shift
                    </div>
                    <button type="button" class="btn btn-success" onclick="executeStep5_5b()" id="btnStep5_5b" disabled>
                        <i class="fas fa-play me-2"></i>
                        Ejecutar PASO 5.5B (Authorization)
                    </button>
                    <div id="step5_5bResult" class="step-result mt-3"></div>
                </div>
            </div>

            <!-- Botón volver -->
            <div class="text-center mt-4">
                <a href="{{ route('payment.checkout') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Checkout Normal
                </a>
            </div>
        </div>
    </div>
</div>

