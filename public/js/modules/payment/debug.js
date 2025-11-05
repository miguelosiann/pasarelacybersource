// Debug CyberSource - Payment Gateway Step by Step

/**
 * Save form data to session
 */
function saveFormData() {
    const form = document.getElementById('debugPaymentForm');
    const formData = new FormData(form);
    const btn = document.querySelector('button[onclick="saveFormData()"]');
    
    // ✅ CRÍTICO: Agregar el sessionId de ThreatMetrix generado al cargar la página
    if (window.threatMetrixSessionId) {
        formData.append('threatmetrix_session_id', window.threatMetrixSessionId);
        console.log('✅ Adding ThreatMetrix SessionId to form data:', window.threatMetrixSessionId);
    } else {
        console.warn('⚠️ window.threatMetrixSessionId not found');
    }
    
    // Deshabilitar botón y mostrar loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Guardando...';

    fetch('/payment/debug/save-form', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Datos Guardados en Sesión!\n\nAhora puedes ejecutar el PASO 1.');
            
            // Habilitar botón PASO 1
            document.getElementById('btnStep1').disabled = false;
            
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check me-2"></i>Datos Guardados';
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-success');
        } else {
            alert('❌ Error al guardar los datos:\n' + JSON.stringify(data.errors));
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save me-2"></i>1️⃣ Guardar Datos en Sesión';
        }
    })
    .catch(error => {
        alert('❌ Error: ' + error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save me-2"></i>1️⃣ Guardar Datos en Sesión';
    });
}

/**
 * Execute Step 1 - Create Instrument Identifier
 */
function executeStep1() {
    const btn = document.getElementById('btnStep1');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';

    fetch('/payment/debug/step1', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step1Result', data, 'btnStep2');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 1';
    });
}

/**
 * Execute Step 2 - Create Payment Instrument
 */
function executeStep2() {
    const btn = document.getElementById('btnStep2');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';

    fetch('/payment/debug/step2', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step2Result', data, 'btnStep3');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 2';
    });
}

/**
 * Execute Step 3 - Setup 3DS
 */
function executeStep3() {
    const btn = document.getElementById('btnStep3');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';

    fetch('/payment/debug/step3', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step3Result', data, 'btnStep3_5'); // Cambio: ahora habilita PASO 3.5
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        
        // Guardar datos para el device collection
        if (data.success && data.response) {
            window.deviceCollectionData = {
                url: data.response.consumerAuthenticationInformation.deviceDataCollectionUrl,
                accessToken: data.response.consumerAuthenticationInformation.accessToken,
                referenceId: data.response.consumerAuthenticationInformation.referenceId,
                deviceFingerprintSessionId: data.response.device_fingerprint_session_id  // ⭐ NUEVO
            };
            
            // ⭐ Mostrar en consola el sessionId generado
            if (data.response.device_fingerprint_session_id) {
                console.log('📱 Device Fingerprint Session ID generated:', data.response.device_fingerprint_session_id);
                console.log('🔍 Este Session ID se enviará en la autorización (deviceFingerprintId)');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 3';
    });
}

/**
 * Execute Step 3.5 - Device Data Collection (Hidden Iframe)
 */
function executeStep3_5() {
    const btn = document.getElementById('btnStep3_5');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Cargando iframe...';

    if (!window.deviceCollectionData) {
        alert('❌ Error: Ejecuta el PASO 3 primero para obtener la URL del device collection.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 3.5 (Device Collection)';
        return;
    }

    const { url, accessToken, referenceId, deviceFingerprintSessionId } = window.deviceCollectionData;
    
    // ⭐ Log del sessionId que vamos a usar
    console.log('📱 Using Device Fingerprint Session ID:', deviceFingerprintSessionId || 'NOT GENERATED');
    
    // ✅ Variable para almacenar el Session ID capturado
    window.capturedDeviceFingerprintSessionId = null;

    // Crear contenedor para mostrar info
    const container = document.getElementById('deviceCollectionContainer');
    
    // Mostrar información del proceso
    container.innerHTML = `
        <div class="card border-info">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="mb-0 text-info">
                    <i class="fas fa-spinner fa-spin me-2"></i>
                    Recolectando datos del dispositivo...
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>URL:</strong> <code>${url}</code></p>
                <p class="mb-2"><strong>Reference ID:</strong> <code>${referenceId}</code></p>
                <p class="mb-2"><strong>Device Fingerprint Session ID:</strong> <code>${deviceFingerprintSessionId || 'NOT GENERATED'}</code></p>
                <p class="mb-2"><strong>Access Token:</strong> <code>${accessToken.substring(0, 50)}...</code></p>
                
                <div class="progress mt-3" style="height: 25px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" 
                         role="progressbar" 
                         style="width: 0%" 
                         id="collectionProgress">
                        Esperando 10 segundos...
                    </div>
                </div>
                
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fas fa-clock me-2"></i>
                    <strong>CardinalCommerce está recolectando datos...</strong><br>
                    Este proceso toma aproximadamente 10 segundos.
                </div>
            </div>
        </div>
        
        <!-- Iframe oculto de CardinalCommerce -->
        <iframe id="cardinal_collection_iframe" 
                name="collectionIframe" 
                height="1" 
                width="1" 
                style="display: none;">
        </iframe>
        
        <!-- Formulario oculto -->
        <form id="cardinal_collection_form" 
              method="POST" 
              target="collectionIframe" 
              action="${url}">
            <input type="hidden" name="JWT" value="${accessToken}">
            ${deviceFingerprintSessionId ? `<input type="hidden" name="SessionId" value="${deviceFingerprintSessionId}">` : ''}
        </form>
    `;

    // Auto-submit del formulario
    setTimeout(() => {
        document.getElementById('cardinal_collection_form').submit();
        console.log('📤 Device Data Collection: Formulario enviado al iframe');
    }, 500);

    // Simular progreso
    let progress = 0;
    const progressBar = document.getElementById('collectionProgress');
    const progressInterval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';
        progressBar.textContent = 'Recolectando... ' + progress + '%';
    }, 1000);

    // Esperar 10 segundos para que CardinalCommerce complete la recolección
    setTimeout(() => {
        clearInterval(progressInterval);
        progressBar.style.width = '100%';
        progressBar.textContent = '✅ Completado';
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-success');

        // Mostrar resultado
        const resultHtml = `
            <div class="card border-success mt-3">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Device Data Collection Completado
                    </h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        ✅ CardinalCommerce ha recolectado los datos del dispositivo.<br>
                        Los datos incluyen: navegador, resolución, timezone, plugins, etc.
                    </p>
                </div>
            </div>
        `;
        
        document.getElementById('step3_5Result').innerHTML = resultHtml;
        document.getElementById('step3_5Result').classList.add('show');

        // Actualizar botón
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        btn.classList.remove('btn-secondary');
        btn.classList.add('btn-success');

        // Habilitar PASO 4
        document.getElementById('btnStep4').disabled = false;

        // Scroll al siguiente paso
        setTimeout(() => {
            document.getElementById('btnStep4').scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }, 500);

        alert('✅ Device Data Collection Completado!\n\nCardinalCommerce ha recolectado los datos del dispositivo.\n\nAhora puedes ejecutar el PASO 4.');
        
    }, 10000); // 10 segundos
}

/**
 * Execute Step 4 - Check Enrollment
 * ✅ CRÍTICO: Usa el ThreatMetrix SessionId (del tag HTML) para Decision Manager
 */
function executeStep4() {
    const btn = document.getElementById('btnStep4');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';
    
    // ✅ CRÍTICO: Usar el ThreatMetrix SessionId generado al cargar la página
    // NO usar el sessionId de CardinalCommerce (capturedDeviceFingerprintSessionId)
    const requestBody = {};
    
    // Prioridad: ThreatMetrix SessionId > Cardinal SessionId
    if (window.threatMetrixSessionId) {
        requestBody.device_fingerprint_session_id = window.threatMetrixSessionId;
        console.log('✅ [DEBUG] Sending ThreatMetrix SessionId (from HTML tag):', window.threatMetrixSessionId);
    } else if (window.capturedDeviceFingerprintSessionId) {
        requestBody.device_fingerprint_session_id = window.capturedDeviceFingerprintSessionId;
        console.warn('⚠️ [DEBUG] Using Cardinal SessionId as fallback:', window.capturedDeviceFingerprintSessionId);
    } else {
        console.warn('⚠️ [DEBUG] No Device Fingerprint Session ID available, backend will use referenceId as fallback');
    }

    fetch('/payment/debug/step4', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(requestBody)
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step4Result', data, null);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        
        // Verificar el escenario de enrollment
        if (data.success && data.response) {
            const veresEnrolled = data.response.consumerAuthenticationInformation?.veresEnrolled;
            const paresStatus = data.response.consumerAuthenticationInformation?.paresStatus;
            
            if (veresEnrolled === 'Y' && paresStatus === 'Y') {
                // Caso frictionless - habilitar PASO 5
                document.getElementById('btnStep5').disabled = false;
                alert('✅ Enrollment Exitoso!\n\nveresEnrolled: Y\nparesStatus: Y\n\nPuedes ejecutar el PASO 5 (Authorization)');
            } else if (veresEnrolled === 'Y' && paresStatus === 'C') {
                // Caso challenge - habilitar PASO 4.5
                document.getElementById('btnStep4_5').disabled = false;
                
                // Guardar datos del challenge
                window.challengeData = {
                    stepUpUrl: data.response.consumerAuthenticationInformation?.stepUpUrl,
                    accessToken: data.response.consumerAuthenticationInformation?.accessToken,
                    referenceId: data.response.consumerAuthenticationInformation?.authenticationTransactionId
                };
                
                alert('⚠️ Challenge Requerido!\n\nveresEnrolled: Y\nparesStatus: C\n\nLa tarjeta requiere autenticación adicional.\nPuedes ejecutar el PASO 4.5 (Challenge)');
            } else {
                alert('ℹ️ Enrollment: ' + veresEnrolled + ',' + paresStatus + '\n\nEste escenario puede requerir manejo especial.');
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 4';
    });
}

/**
 * Execute Step 4.5 - Show Challenge Iframe (Y,C)
 */
function executeStep4_5() {
    const btn = document.getElementById('btnStep4_5');
    
    if (!window.challengeData) {
        alert('❌ Error: No hay datos de challenge. Ejecuta el PASO 4 primero con una tarjeta que requiera challenge.');
        return;
    }
    
    const { stepUpUrl, accessToken, referenceId } = window.challengeData;
    
    // Mostrar información del challenge
    const infoHtml = `
        <div class="card border-info mb-3">
            <div class="card-header bg-info bg-opacity-10">
                <h6 class="mb-0 text-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Datos del Challenge
                </h6>
            </div>
            <div class="card-body">
                <p class="mb-2"><strong>Step Up URL:</strong> <code>${stepUpUrl}</code></p>
                <p class="mb-2"><strong>Access Token (JWT):</strong> <code>${accessToken ? accessToken.substring(0, 50) + '...' : 'N/A'}</code></p>
                <p class="mb-0"><strong>Reference ID:</strong> <code>${referenceId}</code></p>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <strong>Instrucciones:</strong>
            <ul class="mb-0 mt-2">
                <li>El iframe del banco se cargará automáticamente abajo</li>
                <li>Completa la autenticación que el banco te solicite</li>
                <li>Puede ser un código SMS, autenticación móvil, etc.</li>
                <li>Después de completar, volverás automáticamente</li>
            </ul>
        </div>
    `;
    
    document.getElementById('step4_5Result').innerHTML = infoHtml;
    document.getElementById('step4_5Result').classList.add('show');
    
    // Mostrar el contenedor del iframe
    const container = document.getElementById('challengeContainer');
    container.classList.remove('d-none');
    
    // Configurar el formulario
    const form = document.getElementById('challenge-form-debug');
    const jwtInput = document.getElementById('challenge-jwt-debug');
    
    form.action = stepUpUrl;
    jwtInput.value = accessToken;
    
    // Actualizar botón
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Challenge en progreso...';
    
    // Auto-submit después de 1 segundo
    setTimeout(() => {
        form.submit();
        console.log('📤 Challenge form submitted to iframe');
        
        alert('🏦 Challenge Iniciado!\n\nEl iframe del banco se está cargando.\nCompleta la autenticación que te solicite.\n\n⚠️ NOTA: En el ambiente de prueba de CyberSource, el challenge puede ser simulado.');
    }, 1000);
    
    // Scroll al iframe
    setTimeout(() => {
        container.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 1500);
}

/**
 * Execute Step 5 - Authorization (Frictionless Y,Y)
 */
function executeStep5() {
    const btn = document.getElementById('btnStep5');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Ejecutando...';

    fetch('/payment/debug/step5', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step5Result', data, null);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        
        // Si fue exitoso, mostrar mensaje
        if (data.success && data.response) {
            const status = data.response.status;
            const id = data.response.id;
            const paymentId = data.payment_id;
            const savedToDB = data.saved_to_db;
            
            let message = '🎉 PAGO COMPLETADO!\n\n';
            message += 'Transaction ID: ' + id + '\n';
            message += 'Status: ' + status + '\n';
            
            if (savedToDB && paymentId) {
                message += '\n💾 GUARDADO EN BASE DE DATOS\n';
                message += 'Payment ID: ' + paymentId + '\n';
            }
            
            message += '\nEl flujo 3DS 2.2.0 se completó exitosamente!';
            
            alert(message);
        } else {
            alert('❌ Authorization Fallida\n\nRevisa los detalles de la respuesta.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 5 (Authorization)';
    });
}

/**
 * Execute Step 5.5A - Validation Service (Solo para Challenge Y,C)
 */
function executeStep5_5a() {
    const btn = document.getElementById('btnStep5_5a');
    
    if (!window.authenticationTransactionId) {
        alert('❌ Error: No se encontró el Authentication Transaction ID.\n\nAsegúrate de haber completado el PASO 4.5 (Challenge) primero.');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Validando...';

    fetch('/payment/debug/step5_5a', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            authentication_transaction_id: window.authenticationTransactionId
        })
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step5_5aResult', data, 'btnStep5_5b');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        
        // Guardar datos del validation para PASO 5.5B
        if (data.success && data.response) {
            window.validationData = data.response;
            
            const status = data.response.status;
            const paresStatus = data.response.consumerAuthenticationInformation?.paresStatus;
            const eci = data.response.consumerAuthenticationInformation?.eciRaw;
            
            let message = '✅ VALIDATION EXITOSA!\n\n';
            message += 'Status: ' + status + '\n';
            message += 'PARes Status: ' + paresStatus + '\n';
            message += 'ECI: ' + eci + '\n';
            message += '\n📋 Datos 3DS validados correctamente.\n';
            message += 'Ahora puedes ejecutar el PASO 5.5B (Authorization).';
            
            alert(message);
        } else {
            alert('❌ Validation Fallida\n\nRevisa los detalles de la respuesta.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 5.5A (Validation)';
    });
}

/**
 * Execute Step 5.5B - Authorization After Validation (Solo para Challenge Y,C)
 */
function executeStep5_5b() {
    const btn = document.getElementById('btnStep5_5b');
    
    if (!window.validationData) {
        alert('❌ Error: No hay datos de validation.\n\nAsegúrate de haber completado el PASO 5.5A (Validation) primero.');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Autorizando...';

    fetch('/payment/debug/step5_5b', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        displayStepResult('step5_5bResult', data, null);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Completado';
        
        // Si fue exitoso, mostrar mensaje
        if (data.success && data.response) {
            const status = data.response.status;
            const id = data.response.id;
            const eci = data.response.consumerAuthenticationInformation?.eciRaw;
            const paymentId = data.payment_id;
            const savedToDB = data.saved_to_db;
            
            let message = '🎉 PAGO COMPLETADO (Challenge con Validation)!\n\n';
            message += 'Transaction ID: ' + id + '\n';
            message += 'Status: ' + status + '\n';
            message += 'ECI: ' + eci + '\n';
            
            if (savedToDB && paymentId) {
                message += '\n💾 GUARDADO EN BASE DE DATOS\n';
                message += 'Payment ID: ' + paymentId + '\n';
            }
            
            message += '\n✅ Flujo completo 3DS 2.2.0 con Challenge:\n';
            message += '1. Check Enrollment (Y,C)\n';
            message += '2. Step-Up Challenge\n';
            message += '3. Validation Service\n';
            message += '4. Authorization\n';
            message += '\n🏆 Completado exitosamente!';
            
            alert(message);
        } else {
            alert('❌ Authorization Fallida\n\nRevisa los detalles de la respuesta.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-play me-2"></i>Ejecutar PASO 5.5B (Authorization)';
    });
}

/**
 * Display step result in a nice format
 */
function displayStepResult(containerId, data, nextButtonId) {
    const container = document.getElementById(containerId);
    
    if (!data) {
        container.innerHTML = '<div class="alert alert-danger">No se recibieron datos</div>';
        return;
    }

    const isSuccess = data.success === true;
    const statusClass = isSuccess ? 'success' : 'danger';
    const statusIcon = isSuccess ? 'check-circle' : 'times-circle';

    let html = `
        <div class="card border-${statusClass}">
            <div class="card-header bg-${statusClass} text-white">
                <h6 class="mb-0">
                    <i class="fas fa-${statusIcon} me-2"></i>
                    ${data.step || 'Resultado'} - HTTP ${data.http_code || 'N/A'}
                </h6>
            </div>
            <div class="card-body">
    `;

    if (data.description) {
        html += `<p class="text-muted"><strong>Descripción:</strong> ${data.description}</p>`;
    }

    if (data.url) {
        html += `
            <div class="mb-3">
                <h6>🌐 URL del Endpoint:</h6>
                <code class="d-block bg-light p-2 rounded">${data.url}</code>
            </div>
        `;
    }

    if (data.request) {
        html += `
            <div class="mb-3">
                <h6>📤 REQUEST enviado:</h6>
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(data.request, null, 2)}</code></pre>
            </div>
        `;
    }

    if (data.response) {
        html += `
            <div class="mb-3">
                <h6>📥 RESPONSE recibido:</h6>
                <pre class="bg-light p-3 rounded" style="max-height: 300px; overflow-y: auto;"><code>${JSON.stringify(data.response, null, 2)}</code></pre>
            </div>
        `;
    }

    if (data.error) {
        html += `
            <div class="alert alert-danger">
                <strong>❌ Error:</strong> ${data.error}
            </div>
        `;
    }
    
    // ⚠️ IMPORTANTE: Mostrar si fue rechazado por Decision Manager
    if (data.declined === true) {
        html += `
            <div class="alert alert-warning mt-3">
                <h6 class="mb-2">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ⚠️ Transacción Rechazada por Decision Manager
                </h6>
                <p class="mb-2"><strong>Motivo:</strong> ${data.error_reason || 'UNKNOWN'}</p>
                <p class="mb-2"><strong>Mensaje:</strong> ${data.error_message || 'UNKNOWN'}</p>
                ${data.risk_score ? `<p class="mb-2"><strong>Risk Score:</strong> <span class="badge bg-danger">${data.risk_score}</span></p>` : ''}
                <p class="mb-0 text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    El sistema Decision Manager de CyberSource analizó la transacción y la rechazó según las reglas configuradas.
                </p>
            </div>
        `;
    }
    
    // Si el pago fue guardado en la BD, mostrar info
    if (data.saved_to_db && data.payment_id) {
        const paymentStatus = data.declined ? 'failed' : 'completed';
        const paymentBadge = data.declined ? 'bg-danger' : 'bg-success';
        html += `
            <div class="alert ${data.declined ? 'alert-warning' : 'alert-success'} mt-3">
                <h6 class="mb-2">
                    <i class="fas fa-database me-2"></i>
                    💾 Pago Guardado en Base de Datos
                </h6>
                <p class="mb-0">
                    <strong>Payment ID:</strong> <code>${data.payment_id}</code><br>
                    <strong>Status:</strong> <span class="badge ${paymentBadge}">${paymentStatus}</span><br>
                    <small>El pago ha sido registrado en la tabla <code>payments</code> ${data.declined ? 'como rechazado' : 'exitosamente'}</small>
                </p>
            </div>
        `;
    }

    html += `
            </div>
        </div>
    `;

    container.innerHTML = html;
    container.classList.add('show');

    // Scroll al resultado
    container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Habilitar siguiente botón si existe y fue exitoso
    if (nextButtonId && isSuccess) {
        const nextBtn = document.getElementById(nextButtonId);
        if (nextBtn) {
            nextBtn.disabled = false;
            // Scroll al siguiente botón
            setTimeout(() => {
                nextBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 500);
        }
    }
}

// Listen for challenge callback messages
// ⚠️ IMPORTANTE: Debe estar FUERA del DOMContentLoaded para capturar mensajes tempranos
window.addEventListener('message', function(event) {
    // Log completo para debug
    console.log('📨 [DEBUG] Received postMessage:', {
        data: event.data,
        origin: event.origin,
        source: event.source === window ? 'SAME_WINDOW' : 'IFRAME',
        isObject: typeof event.data === 'object',
        hasTransactionId: event.data && event.data.transactionId,
        fromChallenge: event.data && event.data.fromChallenge
    });
    
    // Según documentación CyberSource:
    // El returnUrl recibe POST con TransactionId y MD (NO CardinalJWT en el POST)
    // El TransactionId ES el authenticationTransactionId que necesitamos
    
    // ✅ PRIORIDAD 1: Callback desde challenge-return.blade.php con TransactionId
    if (event.data && 
        typeof event.data === 'object' && 
        !Array.isArray(event.data) && 
        event.data.fromChallenge === true && 
        event.data.transactionId) {
        
        console.log('✅ PRIORITY 1: Received challenge result with TransactionId:', event.data);
        handleChallengeCallback(event.data);
        return;
    }
    
    // 📋 PRIORIDAD 2: Otros mensajes con transactionId válido
    if (event.data && 
        typeof event.data === 'object' && 
        !Array.isArray(event.data) && 
        event.data.transactionId && 
        event.data.success !== undefined) {
        
        console.log('📋 PRIORITY 2: Received callback with transactionId:', event.data);
        handleChallengeCallback(event.data);
        return;
    }
    
    // ℹ️ Otros mensajes (ignorar silenciosamente)
    console.log('ℹ️ Message ignored (not a challenge result)');
}, true); // Capture phase

// Auto-uppercase for state field
document.addEventListener('DOMContentLoaded', function() {
    const stateField = document.querySelector('input[name="state"]');
    if (stateField) {
        stateField.addEventListener('input', function(e) {
            this.value = this.value.toUpperCase().replace(/[^A-Z]/g, '');
        });
    }
});

/**
 * Handle challenge callback from iframe
 * Según documentación CyberSource: recibimos TransactionId del POST, no JWT
 */
function handleChallengeCallback(challengeResult) {
    console.log('✅ Challenge callback received:', challengeResult);
    
    // Según documentación CyberSource:
    // El returnUrl recibe POST con TransactionId (que ES el authenticationTransactionId)
    const authenticationTransactionId = challengeResult.transactionId || 
                                       challengeResult.authenticationTransactionId;
    
    if (!authenticationTransactionId) {
        console.error('❌ No TransactionId received from challenge callback');
        alert('❌ Error: No se recibió el TransactionId del challenge.\n\nNo se puede continuar con el PASO 5.5.');
        return;
    }
    
    console.log('🔑 Authentication Transaction ID:', authenticationTransactionId);
    console.log('📋 Challenge success:', challengeResult.success);
    console.log('📋 Merchant Data (MD):', challengeResult.md);
    
    // Guardar datos globalmente para PASO 5.5
    window.challengeCompleted = true;
    window.authenticationTransactionId = authenticationTransactionId;
    window.challengeSuccess = challengeResult.success;
    
    const btn = document.getElementById('btnStep4_5');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check me-2"></i>Challenge Completado';
        btn.classList.remove('btn-warning');
        btn.classList.add('btn-success');
    }
    
    // Habilitar botón PASO 5.5A (Validation Service)
    const btnStep5_5a = document.getElementById('btnStep5_5a');
    if (btnStep5_5a) {
        btnStep5_5a.disabled = false;
        console.log('✅ Botón PASO 5.5A (Validation) habilitado');
    }
    
    // Mostrar resultado del challenge
    const resultHtml = `
        <div class="card border-success mt-3">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0">
                    <i class="fas fa-check-circle me-2"></i>
                    ✅ Challenge Completado Exitosamente
                </h6>
            </div>
            <div class="card-body">
                <h6 class="mb-3">📥 Datos Recibidos del ReturnURL:</h6>
                
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Según documentación CyberSource:</strong><br>
                    El returnUrl recibe un POST con <code>TransactionId</code> y <code>MD</code>.<br>
                    El <code>TransactionId</code> ES el <code>authenticationTransactionId</code> que necesitamos.
                </div>
                
                <div class="mb-2">
                    <strong>🔑 Authentication Transaction ID (para PASO 5.5):</strong>
                    <code class="d-block bg-warning bg-opacity-25 p-2 rounded mt-1 fw-bold">${authenticationTransactionId || 'N/A'}</code>
                </div>
                
                <div class="mb-2">
                    <strong>Success Status:</strong>
                    <code class="d-block bg-light p-2 rounded mt-1">${challengeResult.success ? '✅ YES' : '❌ NO'}</code>
                </div>
                
                <div class="mb-2">
                    <strong>Merchant Data (MD):</strong>
                    <code class="d-block bg-light p-2 rounded mt-1">${challengeResult.md || 'null'}</code>
                </div>
                
                <div class="mb-2">
                    <strong>Timestamp:</strong>
                    <code class="d-block bg-light p-2 rounded mt-1">${challengeResult.timestamp || new Date().toISOString()}</code>
                </div>
                
                ${challengeResult.error ? `
                <div class="alert alert-warning mt-2">
                    <strong>❌ Error:</strong> ${challengeResult.error}
                </div>
                ` : ''}
                
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-arrow-right me-2"></i>
                    <strong>✅ Siguiente paso:</strong> Haz clic en el botón <strong>PASO 5.5A (Validation Service)</strong> (habilitado abajo) para validar la autenticación antes de la autorización final.
                </div>
            </div>
        </div>
    `;
    
    const resultContainer = document.getElementById('step4_5Result');
    if (resultContainer) {
        resultContainer.innerHTML += resultHtml;
    }
    
    // Alert al usuario
    alert('✅ Challenge Completado!\n\n🔑 Authentication Transaction ID:\n' + (authenticationTransactionId || 'N/A') + '\n\n📋 PRÓXIMOS PASOS (Challenge Y,C):\n1. PASO 5.5A: Validation Service (habilitado)\n2. PASO 5.5B: Authorization\n\n✅ Haz clic en PASO 5.5A para continuar.');
    
    // Scroll al resultado
    setTimeout(() => {
        resultContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 500);
}

