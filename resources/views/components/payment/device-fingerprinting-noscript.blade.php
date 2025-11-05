@php
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
@endphp

@if($isEnabled)
{{-- Este noscript se ejecuta solo si JavaScript está deshabilitado --}}
<noscript>
    <iframe style="width: 100px; height: 100px; border: 0; position: absolute; top: -5000px;" 
            src="https://{{ $profilingDomain }}/fp/tags?org_id={{ $orgId }}&session_id={{ $fullSessionId }}">
    </iframe>
</noscript>
@endif

