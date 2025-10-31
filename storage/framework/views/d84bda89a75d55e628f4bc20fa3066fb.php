<!DOCTYPE html>
<html lang="es">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pasarela CyberSource - Inicio</title>
            <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            width: 100%;
            padding: 60px 40px;
            text-align: center;
            animation: fadeIn 0.6s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .logo {
            font-size: 80px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-20px);
            }
            60% {
                transform: translateY(-10px);
            }
        }

        h1 {
            color: #333;
            font-size: 42px;
            margin-bottom: 15px;
            font-weight: 700;
        }

        .subtitle {
            color: #666;
            font-size: 18px;
            margin-bottom: 40px;
        }

        .badge {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 40px;
        }

        .cards-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 15px;
            padding: 40px 30px;
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            display: block;
            border: 3px solid transparent;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
            border-color: #667eea;
        }

        .card-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }

        .card-title {
            color: #333;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 15px;
        }

        .card-description {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .card-features {
            list-style: none;
            padding: 0;
            margin: 20px 0;
            text-align: left;
        }

        .card-features li {
            color: #555;
            font-size: 14px;
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
        }

        .card-features li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }

        .card-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            margin-top: 10px;
        }

        .card-button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }

        .info-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
            margin-top: 40px;
            text-align: left;
        }

        .info-section h3 {
            color: #333;
            font-size: 20px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .info-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 10px;
        }

        .code-block {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            margin: 15px 0;
            overflow-x: auto;
        }

        .footer {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            color: #999;
            font-size: 14px;
        }

        .footer a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .container {
                padding: 40px 20px;
            }

            h1 {
                font-size: 32px;
            }

            .cards-container {
                grid-template-columns: 1fr;
            }
        }
            </style>
    </head>
<body>
    <div class="container">
        <div class="logo">💳</div>
        <h1>Pasarela CyberSource</h1>
        <p class="subtitle">Sistema completo de pagos con 3D Secure 2.2.0</p>
        <span class="badge">✅ Sistema Funcional</span>

        <div class="cards-container">
            <!-- Card de Checkout -->
            <a href="<?php echo e(route('payment.checkout')); ?>" class="card">
                <div class="card-icon">🛒</div>
                <h2 class="card-title">Checkout</h2>
                <p class="card-description">Formulario completo de pago con validación y procesamiento automático</p>
                
                <ul class="card-features">
                    <li>Formulario de pago completo</li>
                    <li>Validación en tiempo real</li>
                    <li>3D Secure automático</li>
                    <li>Frictionless + Challenge</li>
                    <li>Guardado en base de datos</li>
                </ul>

                <button class="card-button">Ir a Checkout →</button>
            </a>

            <!-- Card de Debug -->
            <a href="<?php echo e(route('payment.debug.index')); ?>" class="card">
                <div class="card-icon">🐛</div>
                <h2 class="card-title">Debug Mode</h2>
                <p class="card-description">Ejecuta el proceso paso a paso para testing y desarrollo</p>
                
                <ul class="card-features">
                    <li>Ejecución paso a paso</li>
                    <li>Ver request/response</li>
                    <li>7 pasos individuales</li>
                    <li>Testing completo</li>
                    <li>Logs detallados</li>
                    </ul>

                <button class="card-button">Ir a Debug →</button>
            </a>
                </div>

        <!-- Sección de información -->
        <div class="info-section">
            <h3>🎯 Tarjetas de Prueba</h3>
            
            <p><strong>Visa Frictionless (Sin Challenge):</strong></p>
            <div class="code-block">4111 1111 1111 1111  |  12/2030  |  CVV: 123</div>

            <p><strong>Visa Challenge (Con autenticación):</strong></p>
            <div class="code-block">4000 0000 0000 1091  |  12/2030  |  CVV: 123</div>

            <p style="margin-top: 20px;">
                <strong>💡 Tip:</strong> Usa el modo Debug para ver cada paso del proceso de pago en detalle.
            </p>
                </div>

        <!-- Sección de información adicional -->
        <div class="info-section" style="margin-top: 20px;">
            <h3>📊 Rutas Disponibles</h3>
            <p><strong>Historial de Pagos:</strong> <a href="<?php echo e(route('payment.history')); ?>">Ver historial →</a></p>
            <p><strong>Documentación:</strong> <a href="/README.md" target="_blank">README.md</a> | <a href="/QUICK_START.md" target="_blank">QUICK_START.md</a></p>
        </div>

        <div class="footer">
            <p>
                <strong>Sistema de Pagos CyberSource</strong><br>
                Replicado desde ociann-legal con Laravel 11<br>
                <a href="https://developer.cybersource.com/" target="_blank">Documentación CyberSource</a>
            </p>
        </div>
    </div>
    </body>
</html>
<?php /**PATH C:\xampp\htdocs\pasarelalaravel\resources\views/welcome.blade.php ENDPATH**/ ?>