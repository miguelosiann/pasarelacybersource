<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challenge Return</title>
    <link rel="stylesheet" href="<?php echo e(asset('css/variables.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/payment-gateway.css')); ?>">
</head>
<body class="challenge-return-body">
    <div class="challenge-return-container">
        <?php if($challengeSuccess): ?>
            <h2 class="challenge-return-success">✅ Challenge Completado</h2>
            <p>Autenticación exitosa. Procesando autorización...</p>
            <div class="challenge-return-loading">⏳ Por favor espera...</div>
        <?php else: ?>
            <h2 class="challenge-return-error">❌ Challenge Fallido</h2>
            <p>Error en la autenticación.</p>
            <?php if(!empty($challengeData['error'])): ?>
                <p><strong>Error:</strong> <?php echo e($challengeData['error']); ?></p>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        // ENVIAR RESULTADO DEL CHALLENGE AL PARENT
        // Según documentación CyberSource: El returnUrl recibe POST con TransactionId y MD
        (function() {
            console.log('🔊 Challenge-return: Sending challenge result to parent');
            
            // Datos recibidos del POST de CardinalCommerce
            const challengeData = <?php echo json_encode($challengeData ?? [], 15, 512) ?>;
            const challengeSuccess = <?php echo e($challengeSuccess ? 'true' : 'false'); ?>;
            
            console.log('📋 Challenge callback data received from POST:', challengeData);
            console.log('✅ Challenge success:', challengeSuccess);
            
            // TransactionId viene del POST de CardinalCommerce
            // Este ES el authenticationTransactionId que necesitamos para PASO 5.5
            const transactionId = '<?php echo e($challengeData['TransactionId'] ?? ''); ?>';
            const md = '<?php echo e($challengeData['MD'] ?? ''); ?>';
            
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

<?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/pages/payment/challenge-return.blade.php ENDPATH**/ ?>