<?php
    /**
     * Device Fingerprinting NoScript Component (ThreatMetrix)
     * 
     * Este componente se incluye en el <body> y proporciona un fallback
     * para navegadores que no tienen JavaScript habilitado.
     * 
     * Se incluye automáticamente en las páginas de checkout y debug.
     */
    
    $orgId = config('cybersource.device_fingerprinting.org_id');
    $profilingDomain = config('cybersource.device_fingerprinting.profiling_domain', 'h.online-metrix.net');
    $merchantId = config('cybersource.merchant_id');
    $sessionId = session('device_fingerprint_session_id', time() . '_' . bin2hex(random_bytes(8)));
    $fullSessionId = $merchantId . $sessionId;
    $isEnabled = config('cybersource.device_fingerprinting.enabled', true);
?>

<?php if($isEnabled): ?>

<noscript>
    <iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;" 
            src="https://<?php echo e($profilingDomain); ?>/fp/tags?org_id=<?php echo e($orgId); ?>&session_id=<?php echo e($fullSessionId); ?>">
    </iframe>
</noscript>
<?php endif; ?>

<?php /**PATH C:\xampp\htdocs\pasarelacybersource\resources\views/components/payment/device-fingerprinting-noscript.blade.php ENDPATH**/ ?>