<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Payment\CyberSourceService;
use Illuminate\Support\Facades\Log;

class ChallengeController extends Controller
{
    protected $cyberSourceService;

    public function __construct(CyberSourceService $cyberSourceService)
    {
        $this->cyberSourceService = $cyberSourceService;
    }

    /**
     * Handle the return from 3DS challenge
     */
    public function callback(Request $request)
    {
        // Log received data for debugging
        Log::info('🔔 3DS Challenge callback received', [
            'all_inputs' => $request->all(),
            'post' => $request->post(),
            'query' => $request->query(),
            'input_cres' => $request->input('cres'),
            'input_threeDSSessionData' => $request->input('threeDSSessionData'),
            'input_MD' => $request->input('MD'),
            'session_has_payment_data' => session()->has('payment_data'),
            'session_has_challenge_data' => session()->has('challenge_data')
        ]);

        // Extract challenge data from request
        // CardinalCommerce puede enviar el JWT como 'cres' o como postMessage
        $challengeData = [
            'TransactionId' => $request->input('TransactionId', ''),
            'PARes' => $request->input('PARes', ''),
            'MD' => $request->input('MD', ''),
            'cres' => $request->input('cres', ''), // Challenge Response con JWT
            'threeDSSessionData' => $request->input('threeDSSessionData', ''),
            'status' => $request->input('status', ''),
            'error' => $request->input('error', ''),
            'CardinalJWT' => $request->input('CardinalJWT', '') ?? $request->input('cres', '')
        ];

        // Process CardinalJWT if present
        $jwtData = null;
        if (!empty($challengeData['CardinalJWT'])) {
            try {
                $jwtParts = explode('.', $challengeData['CardinalJWT']);
                if (count($jwtParts) === 3) {
                    // Decode JWT payload (add padding if needed)
                    $payload = $jwtParts[1];
                    $payload .= str_repeat('=', (4 - strlen($payload) % 4) % 4);
                    $decodedPayload = base64_decode(strtr($payload, '-_', '+/'));
                    $jwtData = json_decode($decodedPayload, true);
                    
                    Log::info('JWT Decoded', ['jwtData' => $jwtData]);
                    
                    // Extract challenge data from JWT
                    if (isset($jwtData['Payload']['Payment']['ProcessorTransactionId'])) {
                        $challengeData['TransactionId'] = $jwtData['Payload']['Payment']['ProcessorTransactionId'];
                    }
                    if (isset($jwtData['Payload']['ActionCode'])) {
                        $challengeData['status'] = $jwtData['Payload']['ActionCode'];
                    }
                }
            } catch (\Exception $e) {
                Log::error('Error decoding JWT', ['error' => $e->getMessage()]);
            }
        }

        // Determine if challenge was successful
        $challengeSuccess = (
            !empty($challengeData['CardinalJWT']) && 
            (isset($jwtData['Payload']['ActionCode']) && $jwtData['Payload']['ActionCode'] === 'SUCCESS')
        ) || !empty($challengeData['TransactionId']);

        // Return HTML response to be loaded in iframe
        return view('pages.payment.challenge-return', [
            'challengeData' => $challengeData,
            'jwtData' => $jwtData,
            'challengeSuccess' => $challengeSuccess
        ]);
    }

    /**
     * Process authorization after successful challenge
     */
    public function processAuthorizationAfterChallenge(Request $request)
    {
        // Get stored payment data from session
        $paymentData = session('payment_data');
        $paymentInstrumentId = session('payment_instrument_id');
        $challengeDataStored = session('challenge_data');

        if (!$paymentData || !$paymentInstrumentId || !$challengeDataStored) {
            Log::error('Missing payment, instrument or challenge data in session', [
                'has_payment_data' => !empty($paymentData),
                'has_payment_instrument_id' => !empty($paymentInstrumentId),
                'has_challenge_data' => !empty($challengeDataStored)
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Datos de pago no encontrados. Por favor inicie el proceso nuevamente.'
            ], 400);
        }

        // Get challenge result from request
        $challengeResult = $request->input('challenge_result');

        if (!$challengeResult || !isset($challengeResult['success']) || !$challengeResult['success']) {
            Log::error('Challenge was not successful', ['challenge_result' => $challengeResult]);
            
            // Clear session data
            session()->forget(['payment_data', 'payment_instrument_id', 'challenge_data']);
            
            return response()->json([
                'success' => false,
                'error' => 'La autenticación 3D Secure falló. Por favor intente nuevamente.'
            ], 400);
        }

        // Get authenticationTransactionId from session (stored before challenge)
        $authenticationTransactionId = session('authentication_transaction_id');
        
        // Fallback: try to get from challenge result if not in session
        if (!$authenticationTransactionId) {
            $authenticationTransactionId = $challengeResult['authenticationTransactionId'] ?? 
                                          $challengeResult['transactionId'] ?? 
                                          null;
        }

        if (!$authenticationTransactionId) {
            Log::error('Missing authenticationTransactionId from session and challenge', [
                'session_id' => session('authentication_transaction_id'),
                'challenge_result' => $challengeResult
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'No se pudo obtener el ID de autenticación del challenge.'
            ], 400);
        }
        
        Log::info('🔑 Using authenticationTransactionId', [
            'id' => $authenticationTransactionId,
            'source' => session('authentication_transaction_id') ? 'session' : 'challenge_result'
        ]);

        try {
            Log::info('🔄 Processing authorization after successful challenge', [
                'authenticationTransactionId' => $authenticationTransactionId,
                'paymentInstrumentId' => $paymentInstrumentId,
                'amount' => $paymentData['amount'],
                'currency' => $paymentData['currency'],
                'has_device_fingerprint_session_id' => !empty($paymentData['device_fingerprint_session_id']),
                'device_fingerprint_session_id' => $paymentData['device_fingerprint_session_id'] ?? 'NOT IN SESSION'
            ]);

            // STEP 1: Process Challenge Authentication (Validate + Authorization)
            // Este método maneja todo el flujo: Validate -> Authorization -> Save Payment
            $challengeResult = $this->cyberSourceService->processChallengeAuthentication(
                $paymentInstrumentId,
                $authenticationTransactionId,
                $paymentData
            );
            
            Log::info('📊 Challenge processing result received', [
                'success' => $challengeResult['success'] ?? false,
                'has_payment' => !empty($challengeResult['payment']),
                'transaction_id' => $challengeResult['transaction_id'] ?? 'N/A'
            ]);

            if (!$challengeResult['success']) {
                Log::error('❌ Challenge processing failed', [
                    'error' => $challengeResult['error'] ?? 'Unknown error',
                    'declined' => $challengeResult['declined'] ?? false,
                    'response' => $challengeResult['response'] ?? null
                ]);

                return response()->json([
                    'success' => false,
                    'error' => $challengeResult['error'] ?? 'Error al procesar el challenge',
                    'declined' => $challengeResult['declined'] ?? false,
                    'error_reason' => $challengeResult['error_reason'] ?? null,
                    'risk_score' => $challengeResult['risk_score'] ?? null
                ], 400);
            }

            // Clear session data
            session()->forget(['payment_data', 'payment_instrument_id', 'challenge_data', 'authentication_transaction_id']);

            Log::info('✅ Payment completed successfully after challenge', [
                'payment_id' => $challengeResult['payment']->id ?? 'N/A',
                'transaction_id' => $challengeResult['transaction_id'] ?? 'N/A'
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $challengeResult['payment']->id,
                'redirect_url' => route('payment.success', ['payment' => $challengeResult['payment']->id])
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error processing authorization after challenge', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error procesando la autorización final.'
            ], 500);
        }
    }
}
