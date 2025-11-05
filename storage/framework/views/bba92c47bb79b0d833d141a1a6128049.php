<?php
    /**
     * Device Fingerprinting Component (ThreatMetrix)
     * 
     * Este componente carga el script de ThreatMetrix para recolección de device fingerprint.
     * El sessionId generado aquí se usará en toda la transacción para correlacionar
     * los datos del dispositivo con Decision Manager.
     * 
     * IMPORTANTE: Este tag debe cargarse en el <head> para asegurar que ThreatMetrix 
     * pueda recolectar información del dispositivo antes de la autorización.
     */
    
    // Configuración desde .env
    $orgId = config('cybersource.device_fingerprinting.org_id');
    $profilingDomain = config('cybersource.device_fingerprinting.profiling_domain', 'h.online-metrix.net');
    $merchantId = config('cybersource.merchant_id');
    $isEnabled = config('cybersource.device_fingerprinting.enabled', true);
    
    // Generar o recuperar sessionId de la sesión Laravel
    if (!session()->has('device_fingerprint_session_id')) {
        $sessionId = time() . '_' . bin2hex(random_bytes(8));
        session(['device_fingerprint_session_id' => $sessionId]);
        Log::info('✅ ThreatMetrix SessionId saved to session', ['session_id' => $sessionId]);
    } else {
        $sessionId = session('device_fingerprint_session_id');
    }
    
    // Concatenar merchantId + sessionId para el parámetro session_id del script
    $fullSessionId = $merchantId . $sessionId;
?>

<?php if($isEnabled): ?>
<script type="text/javascript">
    // Guardar el sessionId en window para que esté disponible globalmente
    window.threatMetrixSessionId = '<?php echo e($sessionId); ?>';
    window.threatMetrixMerchantId = '<?php echo e($merchantId); ?>';
    window.threatMetrixFullSessionId = '<?php echo e($fullSessionId); ?>';
    
    console.log('🔐 Device Fingerprinting Tag Loaded', {
        org_id: '<?php echo e($orgId); ?>',
        merchant_id: '<?php echo e($merchantId); ?>',
        session_id: '<?php echo e($sessionId); ?>',
        full_session_id: '<?php echo e($fullSessionId); ?>',
        profiling_domain: '<?php echo e($profilingDomain); ?>'
    });
</script>


<script type="text/javascript" 
        src="https://<?php echo e($profilingDomain); ?>/fp/tags.js?org_id=<?php echo e($orgId); ?>&session_id=<?php echo e($fullSessionId); ?>"
        async>
</script>


<noscript>
    <iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;" 
            src="https://<?php echo e($profilingDomain); ?>/fp/tags?org_id=<?php echo e($orgId); ?>&session_id=<?php echo e($fullSessionId); ?>">
    </iframe>
</noscript>

<?php if(config('app.env') === 'local'): ?>
<script>
    // Debug: Mostrar el SessionId en consola (solo en desarrollo)
    setTimeout(function() {
        alert('🔐 ThreatMetrix SessionId (DEBUG):\n\nSessionId: <?php echo e($sessionId); ?>\nFull: <?php echo e($fullSessionId); ?>\n\nEste ID se enviará a CyberSource en la autorización.');
    }, 1000);
</script>
<?php endif; ?>
<?php endif; ?>

<?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/components/payment/device-fingerprinting.blade.php ENDPATH**/ ?>