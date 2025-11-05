<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\CyberSourceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{
    protected $cyberSourceService;

    public function __construct(CyberSourceService $cyberSourceService)
    {
        $this->cyberSourceService = $cyberSourceService;
    }

    /**
     * Show the checkout form
     */
    public function showForm()
    {
        return view('pages.payment.checkout');
    }

    /**
     * Process the payment
     */
    public function processPayment(Request $request)
    {
        // Validate payment data (CVV NOT required for 3DS 2.2.0)
        $allowedCurrencies = config('cybersource.allowed_currencies', ['USD', 'CRC']);
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'currency' => 'required|in:' . implode(',', $allowedCurrencies),
            'card_number' => 'required|string|min:13|max:19',
            'expiration_month' => 'required|string|size:2',
            'expiration_year' => 'required|string|size:4',
            'card_type' => 'required|in:visa,mastercard,american express',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        
        // ✅ CRÍTICO: Usar el ThreatMetrix SessionId generado al cargar la página de checkout
        $threatMetrixSessionId = session('device_fingerprint_session_id');
        if ($threatMetrixSessionId) {
            $data['device_fingerprint_session_id'] = $threatMetrixSessionId;
            Log::info('✅ Using ThreatMetrix SessionId from session', [
                'session_id' => $threatMetrixSessionId,
                'source' => 'checkout page load'
            ]);
        } else {
            Log::warning('⚠️ ThreatMetrix SessionId not found in session - will generate new one in setup3DSSecure');
        }
        
        // Log payment attempt
        Log::info('Payment processing started', [
            'user_id' => auth()->id(),
            'amount' => $data['amount'],
            'currency' => $data['currency'],
            'has_threatmetrix_session_id' => !empty($data['device_fingerprint_session_id'])
        ]);

        try {
            // STEP 1 & 2: Create instrument and payment instrument
            $setupResult = $this->cyberSourceService->setupPaymentInstrument($data);
            
            if (!$setupResult['success']) {
                return redirect()->route('payment.failed')
                    ->with('error', $setupResult['error'] ?? 'Error al configurar el pago');
            }
            
            // STEP 3: Setup 3DS and get device collection URL
            $threeDSSetupResult = $this->cyberSourceService->setup3DSSecure(
                $setupResult['payment_instrument_id'],
                $data
            );
            
            if (!$threeDSSetupResult['success']) {
                return redirect()->route('payment.failed')
                    ->with('error', 'Error al configurar 3D Secure');
            }
            
            // Store data in session for continuation
            session([
                'payment_data' => $data,
                'payment_instrument_id' => $setupResult['payment_instrument_id'],
                'threeds_setup_data' => $threeDSSetupResult['data'],
                'device_fingerprint_session_id' => $threeDSSetupResult['data']['device_fingerprint_session_id'] ?? null  // ⭐ NUEVO
            ]);
            
            // STEP 3.5: Show device data collection page
            return view('pages.payment.device-collection', [
                'deviceDataCollectionUrl' => $threeDSSetupResult['data']['consumerAuthenticationInformation']['deviceDataCollectionUrl'] ?? '',
                'accessToken' => $threeDSSetupResult['data']['consumerAuthenticationInformation']['accessToken'] ?? '',
                'paymentInstrumentId' => $setupResult['payment_instrument_id'],
                'setupData' => $threeDSSetupResult['data'],
                'deviceFingerprintSessionId' => $threeDSSetupResult['data']['device_fingerprint_session_id'] ?? null,  // ⭐ NUEVO
                'merchantId' => config('cybersource.merchant_id')  // ⭐ Para crear el session_id completo
            ]);

        } catch (\Exception $e) {
            Log::error('Payment processing exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect()->route('payment.failed')
                ->with('error', 'Ocurrió un error inesperado. Por favor intente nuevamente.');
        }
    }

    /**
     * Show payment processing page
     */
    public function processing()
    {
        return view('pages.payment.processing');
    }

    /**
     * Continue payment after device data collection
     */
    public function continueAfterCollection(Request $request)
    {
        $data = session('payment_data');
        $paymentInstrumentId = session('payment_instrument_id');
        $setupData = session('threeds_setup_data');
        
        // ✅ CRÍTICO: Usar ThreatMetrix SessionId (generado al cargar la página) para Decision Manager
        // El sessionId de CardinalCommerce es diferente y NO existe en ThreatMetrix
        $threatMetrixSessionId = session('device_fingerprint_session_id');
        $cardinalSessionId = $request->input('device_fingerprint_session_id');
        
        if ($threatMetrixSessionId) {
            Log::info('✅ Using ThreatMetrix SessionId for Decision Manager', [
                'threatmetrix_session_id' => $threatMetrixSessionId,
                'cardinal_session_id' => $cardinalSessionId,
                'source' => 'session (generated on page load)'
            ]);
            // Usar ThreatMetrix SessionId (tiene datos de profiling)
            $data['device_fingerprint_session_id'] = $threatMetrixSessionId;
            session(['payment_data' => $data]);
        } elseif ($cardinalSessionId) {
            Log::warning('⚠️ ThreatMetrix SessionId not found, using Cardinal SessionId as fallback', [
                'cardinal_session_id' => $cardinalSessionId
            ]);
            $data['device_fingerprint_session_id'] = $cardinalSessionId;
            session(['payment_data' => $data]);
        } else {
            Log::warning('⚠️ No Device Fingerprint Session ID available - will use referenceId as fallback');
        }
        
        if (!$data || !$paymentInstrumentId || !$setupData) {
            return redirect()->route('payment.failed')
                ->with('error', 'Sesión expirada. Por favor intente nuevamente.');
        }
        
        try {
            // STEP 4: Check Enrollment (ahora incluye device_fingerprint_session_id si fue capturado)
            $enrollmentResult = $this->cyberSourceService->checkEnrollment(
                $paymentInstrumentId,
                $setupData,
                $data
            );
            
            if (!$enrollmentResult['success']) {
                return redirect()->route('payment.failed')
                    ->with('error', 'Error en la verificación de enrollment');
            }
            
            // STEP 5: Process enrollment result
            $processResult = $this->cyberSourceService->processEnrollment(
                $enrollmentResult['data'],
                $data
            );
            
            // Check if challenge is required
            if (isset($processResult['challenge_required']) && $processResult['challenge_required']) {
                // Extract authenticationTransactionId for later use
                $authenticationTransactionId = $processResult['authentication_transaction_id'] ?? 
                                             $processResult['data']['consumerAuthenticationInformation']['authenticationTransactionId'] ?? 
                                             null;
                
                Log::info('🔔 Challenge required - preparing challenge page', [
                    'has_step_up_url' => !empty($processResult['step_up_url']),
                    'has_access_token' => !empty($processResult['access_token']),
                    'authenticationTransactionId' => $authenticationTransactionId,
                    'source' => $processResult['authentication_transaction_id'] ? 'processResult' : 'enrollment_data'
                ]);
                
                session([
                    'challenge_data' => $processResult,
                    'payment_instrument_id' => $paymentInstrumentId,
                    'payment_data' => $data,  // Guardar payment data para después del challenge
                    'authentication_transaction_id' => $authenticationTransactionId
                ]);
                
                Log::info('💾 Session data saved for challenge', [
                    'authentication_transaction_id' => $authenticationTransactionId,
                    'payment_instrument_id' => $paymentInstrumentId,
                    'session_has_auth_id' => session()->has('authentication_transaction_id')
                ]);
                
                return view('pages.payment.challenge', [
                    'challengeData' => [
                        'step_up_url' => $processResult['step_up_url'] ?? '',
                        'access_token' => $processResult['access_token'] ?? '',
                        'reference_id' => $processResult['reference_id'] ?? '',
                        'authentication_transaction_id' => $authenticationTransactionId,
                        'data' => $processResult['data'] ?? []
                    ]
                ]);
            }
            
            // If frictionless (Y,Y), authorize payment
            if ($processResult['success'] && $processResult['flow_type'] === 'frictionless') {
                // STEP 6: Authorize payment
                $authResult = $this->cyberSourceService->authorizePayment(
                    $paymentInstrumentId,
                    $data,
                    $processResult
                );
                
                if ($authResult['success']) {
                    // Save payment
                    $payment = $this->cyberSourceService->savePaymentRecord(
                        $data,
                        $authResult,
                        $processResult
                    );
                    
                    return redirect()->route('payment.success', ['payment' => $payment->id])
                        ->with('success', 'Pago procesado exitosamente');
                }
            }
            
            // If we get here, something went wrong
            return redirect()->route('payment.failed')
                ->with('error', 'No se pudo completar el pago');
            
        } catch (\Exception $e) {
            Log::error('Error after device collection', [
                'message' => $e->getMessage()
            ]);
            
            return redirect()->route('payment.failed')
                ->with('error', 'Error inesperado al procesar el pago');
        }
    }

    /**
     * Handle challenge callback (after user completes challenge)
     */
    public function handleChallengeCallback(Request $request)
    {
        $paymentInstrumentId = session('payment_instrument_id');
        $authenticationTransactionId = session('authentication_transaction_id');
        $data = session('payment_data');
        
        if (!$paymentInstrumentId || !$authenticationTransactionId || !$data) {
            return redirect()->route('payment.failed')
                ->with('error', 'Sesión expirada. Por favor intente nuevamente.');
        }
        
        try {
            Log::info('🔄 Processing Challenge Callback', [
                'payment_instrument_id' => $paymentInstrumentId,
                'authentication_transaction_id' => $authenticationTransactionId
            ]);
            
            // STEP 1: Validate Challenge Authentication
            $validateResult = $this->cyberSourceService->validateChallengeAuthentication(
                $paymentInstrumentId,
                $authenticationTransactionId
            );
            
            if (!$validateResult['success']) {
                Log::error('❌ Challenge validation failed', [
                    'error' => $validateResult['error'],
                    'response' => $validateResult['response']
                ]);
                
                return redirect()->route('payment.failed')
                    ->with('error', 'Error al validar la autenticación del challenge');
            }
            
            // Mirror DEBUG order: proceed to 5.5B only if validation status is AUTHENTICATION_SUCCESSFUL
            $validationData = $validateResult['validation_data'] ?? [];
            if (($validationData['status'] ?? '') !== 'AUTHENTICATION_SUCCESSFUL') {
                Log::error('❌ Validation did not return AUTHENTICATION_SUCCESSFUL', [
                    'status' => $validationData['status'] ?? 'UNKNOWN'
                ]);
                return redirect()->route('payment.failed')
                    ->with('error', 'La validación 3DS no fue exitosa.');
            }

            // STEP 2: Authorization After Validation (5.5B) using original authenticationTransactionId
            $challengeResult = $this->cyberSourceService->authorizeAfterChallengeValidation(
                $paymentInstrumentId,
                $validationData,
                $authenticationTransactionId,
                $data
            );
            
            if ($challengeResult['success']) {
                // Clear session data
                session()->forget([
                    'payment_data',
                    'payment_instrument_id',
                    'challenge_data',
                    'authentication_transaction_id'
                ]);
                
                return redirect()->route('payment.success', ['payment' => $challengeResult['payment']->id])
                    ->with('success', 'Pago procesado exitosamente después del challenge');
            } else {
                return redirect()->route('payment.failed')
                    ->with('error', $challengeResult['error'] ?? 'Error al procesar el pago después del challenge');
            }
            
        } catch (\Exception $e) {
            Log::error('Challenge callback exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('payment.failed')
                ->with('error', 'Error inesperado al procesar el challenge');
        }
    }

    /**
     * DEBUG: Save form data to session
     */
    public function saveFormData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'card_number' => 'required|string|min:13|max:19',
            'card_type' => 'required|string',
            'expiry_month' => 'required|string|size:2',
            'expiry_year' => 'required|string|size:4',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'company' => 'nullable|string|max:255',
            'address1' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:100',
            'postal_code' => 'required|string|max:20',
            'country' => 'required|string|size:2',
            'threatmetrix_session_id' => 'nullable|string|max:88',  // ✅ ThreatMetrix SessionId
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $validatedData = $validator->validated();
        
        // ✅ CRÍTICO: Guardar ThreatMetrix SessionId si fue enviado
        if (!empty($validatedData['threatmetrix_session_id'])) {
            session(['threatmetrix_session_id' => $validatedData['threatmetrix_session_id']]);
            Log::info('✅ ThreatMetrix SessionId saved to session', [
                'session_id' => $validatedData['threatmetrix_session_id']
            ]);
        }
        
        // Guardar en sesión
        session(['payment_debug_data' => $validatedData]);
        
        return response()->json([
            'success' => true, 
            'message' => 'Datos guardados en sesión',
            'data' => $validatedData,
            'threatmetrix_session_id' => $validatedData['threatmetrix_session_id'] ?? null
        ]);
    }

    /**
     * DEBUG: Execute Step 1 - Create Instrument Identifier
     */
    public function debugStep1()
    {
        $data = session('payment_debug_data');
        
        if (!$data) {
            return response()->json(['error' => 'No hay datos en sesión. Guarda el formulario primero.'], 400);
        }
        
        // Ejecutar solo PASO 1
        $result = $this->cyberSourceService->debugCreateInstrumentIdentifier($data);
        
        // Guardar resultado en sesión para siguiente paso
        if ($result['success'] && isset($result['response']['id'])) {
            session(['payment_debug_instrument_id' => $result['response']['id']]);
        }
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 2 - Create Payment Instrument
     */
    public function debugStep2()
    {
        $data = session('payment_debug_data');
        $instrumentId = session('payment_debug_instrument_id');
        
        if (!$data) {
            return response()->json(['error' => 'No hay datos en sesión. Guarda el formulario primero.'], 400);
        }
        
        if (!$instrumentId) {
            return response()->json(['error' => 'Ejecuta el PASO 1 primero.'], 400);
        }
        
        // Ejecutar solo PASO 2
        $result = $this->cyberSourceService->debugCreatePaymentInstrument($instrumentId, $data);
        
        // Guardar resultado en sesión para siguiente paso
        if ($result['success'] && isset($result['response']['id'])) {
            session(['payment_debug_payment_instrument_id' => $result['response']['id']]);
        }
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 3 - Setup 3DS
     */
    public function debugStep3()
    {
        $data = session('payment_debug_data');
        $paymentInstrumentId = session('payment_debug_payment_instrument_id');
        
        if (!$data) {
            return response()->json(['error' => 'No hay datos en sesión.'], 400);
        }
        
        if (!$paymentInstrumentId) {
            return response()->json(['error' => 'Ejecuta el PASO 2 primero.'], 400);
        }
        
        // Ejecutar solo PASO 3
        $result = $this->cyberSourceService->debugSetup3DS($paymentInstrumentId, $data);
        
        // Guardar resultado en sesión (incluyendo device_fingerprint_session_id)
        if ($result['success'] && isset($result['response'])) {
            session(['payment_debug_3ds_setup' => $result['response']]);
            
            // ⭐ Guardar sessionId si viene en la respuesta
            if (!empty($result['response']['device_fingerprint_session_id'])) {
                $data['device_fingerprint_session_id'] = $result['response']['device_fingerprint_session_id'];
                session(['payment_debug_data' => $data]);
                
                Log::info('📱 DEBUG PASO 3: Device Fingerprint Session ID generated and stored', [
                    'session_id' => $result['response']['device_fingerprint_session_id']
                ]);
            }
        }
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 4 - Check Enrollment
     */
    public function debugStep4(Request $request)
    {
        $data = session('payment_debug_data');
        $paymentInstrumentId = session('payment_debug_payment_instrument_id');
        $setupData = session('payment_debug_3ds_setup');
        
        if (!$data || !$paymentInstrumentId || !$setupData) {
            return response()->json(['error' => 'Ejecuta los pasos anteriores primero.'], 400);
        }
        
        // ✅ Capturar device fingerprint session ID si viene del frontend
        $deviceFingerprintSessionId = $request->input('device_fingerprint_session_id');
        
        if ($deviceFingerprintSessionId) {
            Log::info('📱 [DEBUG] Device Fingerprint Session ID received from debug UI', [
                'session_id' => $deviceFingerprintSessionId
            ]);
            $data['device_fingerprint_session_id'] = $deviceFingerprintSessionId;
            session(['payment_debug_data' => $data]); // Actualizar sesión
        } else {
            Log::info('[DEBUG] 📱 Device Fingerprint ID configuration', [
                'device_fingerprint_id' => $data['device_fingerprint_session_id'] ?? 'NOT IN DATA',
                'source' => 'from_setup3ds_or_missing',
                'has_session_id' => !empty($data['device_fingerprint_session_id'])
            ]);
        }
        
        // Ejecutar solo PASO 4
        $result = $this->cyberSourceService->debugCheckEnrollment($paymentInstrumentId, $setupData, $data);
        
        // Guardar enrollment data para PASO 5
        if ($result['success'] && isset($result['response'])) {
            session(['payment_debug_enrollment_data' => $result['response']]);
        }
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 5 - Authorization (Frictionless Y,Y)
     */
    public function debugStep5()
    {
        $data = session('payment_debug_data');
        $paymentInstrumentId = session('payment_debug_payment_instrument_id');
        $enrollmentData = session('payment_debug_enrollment_data');
        
        if (!$data || !$paymentInstrumentId || !$enrollmentData) {
            return response()->json(['error' => 'Ejecuta los pasos anteriores primero.'], 400);
        }
        
        // Ejecutar solo PASO 5 - Authorization
        $result = $this->cyberSourceService->debugAuthorization($paymentInstrumentId, $enrollmentData, $data);
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 5.5A - Validation Service (Solo para Challenge Y,C)
     */
    public function debugStep5_5a()
    {
        $data = session('payment_debug_data');
        $paymentInstrumentId = session('payment_debug_payment_instrument_id');
        
        if (!$data || !$paymentInstrumentId) {
            return response()->json(['error' => 'Ejecuta los pasos anteriores primero.'], 400);
        }
        
        // Obtener el authentication transaction ID del challenge
        $authenticationTransactionId = request()->input('authentication_transaction_id');
        
        if (!$authenticationTransactionId) {
            return response()->json(['error' => 'Falta el authentication transaction ID del challenge.'], 400);
        }
        
        // 🔑 GUARDAR el authenticationTransactionId ORIGINAL del challenge
        // Este ID debe usarse tanto en el Validation como en el Authorization
        session(['payment_debug_auth_transaction_id' => $authenticationTransactionId]);
        
        // Ejecutar PASO 5.5A - Validation Service
        $result = $this->cyberSourceService->debugValidationService(
            $paymentInstrumentId,
            $authenticationTransactionId
        );
        
        // Guardar resultado en sesión para PASO 5.5B
        if ($result['success'] && isset($result['response'])) {
            session(['payment_debug_validation_data' => $result['response']]);
        }
        
        return response()->json($result);
    }

    /**
     * DEBUG: Execute Step 5.5B - Authorization After Validation (Solo para Challenge Y,C)
     */
    public function debugStep5_5b()
    {
        $data = session('payment_debug_data');
        $paymentInstrumentId = session('payment_debug_payment_instrument_id');
        $validationData = session('payment_debug_validation_data');
        $authTransactionId = session('payment_debug_auth_transaction_id');
        
        if (!$data || !$paymentInstrumentId) {
            return response()->json(['error' => 'Ejecuta los pasos anteriores primero.'], 400);
        }
        
        if (!$validationData) {
            return response()->json(['error' => 'Ejecuta el PASO 5.5A (Validation) primero.'], 400);
        }
        
        if (!$authTransactionId) {
            return response()->json(['error' => 'No se encontró el authenticationTransactionId original del challenge.'], 400);
        }
        
        // Verificar que el validation fue exitoso
        if (($validationData['status'] ?? '') !== 'AUTHENTICATION_SUCCESSFUL') {
            return response()->json([
                'error' => 'El validation no fue exitoso. Status: ' . ($validationData['status'] ?? 'UNKNOWN')
            ], 400);
        }
        
        // Ejecutar PASO 5.5B - Authorization con datos validados
        // CRÍTICO: Pasar el authenticationTransactionId ORIGINAL del challenge
        $result = $this->cyberSourceService->debugAuthorizationAfterValidation(
            $paymentInstrumentId,
            $validationData,
            $authTransactionId,  // ✅ El ID original del challenge
            $data
        );
        
        return response()->json($result);
    }

    /**
     * DEBUG: Show debug page
     */
    public function showDebug()
    {
        return view('pages.payment.debug');
    }
}
