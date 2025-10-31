<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge Return</title>
    <link rel="stylesheet" href="{{ asset('css/variables.css') }}">
    <link rel="stylesheet" href="{{ asset('css/payment-gateway.css') }}">
</head>
<body class="challenge-return-body">
    <div class="challenge-return-container">
        @if($challengeSuccess)
            <h2 class="challenge-return-success">✅ Challenge Completado</h2>
            <p>Autenticación exitosa. Procesando autorización...</p>
            <div class="challenge-return-loading">⏳ Por favor espera...</div>
        @else
            <h2 class="challenge-return-error">❌ Challenge Fallido</h2>
            <p>Error en la autenticación.</p>
            @if(!empty($challengeData['error']))
                <p><strong>Error:</strong> {{ $challengeData['error'] }}</p>
            @endif
        @endif
    </div>

    <script>
        // ENVIAR RESULTADO DEL CHALLENGE AL PARENT
        // Según documentación CyberSource: El returnUrl recibe POST con TransactionId y MD
        (function() {
            console.log('🔊 Challenge-return: Sending challenge result to parent');
            
            // Datos recibidos del POST de CardinalCommerce
            const challengeData = @json($challengeData ?? []);
            const challengeSuccess = {{ $challengeSuccess ? 'true' : 'false' }};
            
            console.log('📋 Challenge callback data received from POST:', challengeData);
            console.log('✅ Challenge success:', challengeSuccess);
            
            // TransactionId viene del POST de CardinalCommerce
            // Este ES el authenticationTransactionId que necesitamos para PASO 5.5
            const transactionId = '{{ $challengeData['TransactionId'] ?? '' }}';
            const md = '{{ $challengeData['MD'] ?? '' }}';
            
            if (!transactionId) {
                console.error('❌ No TransactionId received from CardinalCommerce POST');
            } else {
                console.log('🔑 Authentication Transaction ID received:', transactionId);
            }
            
            // Preparar resultado para enviar al parent (debug.js)
            const challengeResult = {
                success: challengeSuccess,
                transactionId: transactionId,
                authenticationTransactionId: transactionId, // Alias para claridad
                md: md,
                fromChallenge: true,
                timestamp: new Date().toISOString()
            };
            
            console.log('📤 Sending challenge result to parent:', challengeResult);
            
            // Enviar al parent window (debug.js)
            if (window.parent && window.parent !== window) {
                window.parent.postMessage(challengeResult, '*');
                console.log('✅ Posted to parent window');
            }
            
            // Enviar al top window por si hay múltiples niveles de iframes
            if (window.top && window.top !== window) {
                window.top.postMessage(challengeResult, '*');
                console.log('✅ Posted to top window');
            }
            
            // Auto-cerrar el challenge después de enviar (opcional)
            // setTimeout(() => {
            //     console.log('ℹ️ Challenge iframe puede cerrarse ahora');
            // }, 1000);
        })();
    </script>
</body>
</html>

