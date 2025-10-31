<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasarela CyberSource - Inicio</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>

<body>
    <div class="container">
        <div class="logo">💳</div>
        <h1>Pasarela CyberSource</h1>
        <p class="subtitle">Sistema Profesional de Pagos con 3D Secure 2.2.0</p>
        <div class="badges-container">
            <span class="badge">✅ Producción Ready</span>
            <span class="badge">🔒 3DS 2.2.0</span>
            <span class="badge">🚀 Laravel 11</span>
        </div>

        <div class="cards-container">
            <!-- Card de Checkout -->
            <a href="{{ route('payment.checkout') }}" class="card">
                <div class="card-icon">🛒</div>
                <h2 class="card-title">Checkout</h2>
                <p class="card-description">Procesamiento de pagos en producción con autenticación 3D Secure</p>

                <ul class="card-features">
                    <li>Tokenización de tarjetas</li>
                    <li>Validación automática</li>
                    <li>3D Secure integrado</li>
                    <li>Visa, Mastercard, Amex</li>
                    <li>Soporte UCAF Mastercard</li>
                </ul>

                <button class="card-button">Procesar Pago →</button>
            </a>

            <!-- Card de Debug -->
            <a href="{{ route('payment.debug.index') }}" class="card">
                <div class="card-icon">🐛</div>
                <h2 class="card-title">Debug Mode</h2>
                <p class="card-description">Herramienta de desarrollo para testing paso a paso</p>

                <ul class="card-features">
                    <li>7 pasos individuales</li>
                    <li>Request/Response completo</li>
                    <li>Testing de flujos</li>
                    <li>Validación detallada</li>
                    <li>Logs con emojis</li>
                </ul>

                <button class="card-button">Modo Debug →</button>
            </a>

            <!-- Card de Historial -->
            <a href="{{ route('payment.history') }}" class="card">
                <div class="card-icon">📊</div>
                <h2 class="card-title">Historial</h2>
                <p class="card-description">Visualiza todas las transacciones procesadas</p>

                <ul class="card-features">
                    <li>Lista de pagos completa</li>
                    <li>Detalles de 3DS</li>
                    <li>Estados de transacción</li>
                    <li>Filtros y búsqueda</li>
                    <li>Exportar datos</li>
                </ul>

                <button class="card-button">Ver Historial →</button>
            </a>
        </div>

        <!-- Características Técnicas -->
        <div class="info-section">
            <h3>⚡ Características Técnicas</h3>
            <div class="technical-grid">
                <div class="technical-grid-item">
                    <strong>🔐 Seguridad:</strong>
                    <p>3D Secure 2.2.0, HMAC, Tokenización TMS</p>
                </div>
                <div class="technical-grid-item">
                    <strong>💳 Tarjetas:</strong>
                    <p>Visa, Mastercard, American Express</p>
                </div>
                <div class="technical-grid-item">
                    <strong>🌍 Monedas:</strong>
                    <p>USD, CRC (configurable)</p>
                </div>
                <div class="technical-grid-item">
                    <strong>📱 Flujos:</strong>
                    <p>Frictionless, Challenge, Stand-in</p>
                </div>
            </div>
        </div>

        <!-- Sección de rutas -->
        <div class="info-section">
            <h3>🔗 Enlaces Rápidos</h3>
            <p><strong>Historial de Pagos:</strong> <a href="{{ route('payment.history') }}">Ver todas las transacciones →</a></p>
            <p><strong>Documentación CyberSource:</strong> <a href="https://developer.cybersource.com/" target="_blank">developer.cybersource.com →</a></p>
            <p><strong>Logs del Sistema:</strong> <code>storage/logs/laravel.log</code></p>
        </div>

        <div class="footer">
            <p>
                <strong>💳 Pasarela de Pagos CyberSource</strong><br>
                Desarrollado por <strong>Miguel Segura Alvarado</strong><br>
                Laravel 11 • PHP 8.1 • 3D Secure 2.2.0<br>
                <a href="https://developer.cybersource.com/" target="_blank">Documentación Oficial →</a>
            </p>
        </div>
    </div>
</body>

</html>
