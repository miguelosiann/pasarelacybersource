<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Payment;
use App\Models\PaymentInstrument;
use App\Models\PaymentTransaction;
use Exception;

class CyberSourceService
{
    protected $merchantId;
    protected $apiKey;
    protected $apiSecret;
    protected $baseUrl;
    protected $hmacGenerator;
    protected $httpClient;
    
    public function __construct(HMACGenerator $hmacGenerator)
    {
        $this->merchantId = config('cybersource.merchant_id');
        $this->apiKey = config('cybersource.api_key');
        $this->apiSecret = config('cybersource.api_secret');
        $this->baseUrl = config('cybersource.base_url');
        $this->hmacGenerator = $hmacGenerator;
    }
    
    /**
     * Process a complete payment with 3D Secure (with detailed flow tracking)
     *
     * @param array $data Payment data
     * @return array
     */
    public function processPayment(array $data): array
    {
        $flowResults = [];
        
        try {
            // STEP 1: Create Instrument Identifier
            Log::info('CyberSource: Creating Instrument Identifier');
            $instrumentResult = $this->createInstrumentIdentifier($data['card_number']);
            $flowResults['step1'] = [
                'name' => 'Instrument Identifier',
                'url' => "{$this->baseUrl}/tms/v1/instrumentidentifiers",
                'http_code' => $instrumentResult['response']['http_code'] ?? 0,
                'request_payload' => $instrumentResult['request_payload'] ?? '',
                'response' => $instrumentResult['response']['body'] ?? '',
                'success' => $instrumentResult['success']
            ];
            
            if (!$instrumentResult['success']) {
                return $this->errorResponse('Failed to create Instrument Identifier', array_merge($instrumentResult, ['flow_results' => $flowResults]));
            }
            
            $instrumentId = $instrumentResult['instrument_id'];
            
            // STEP 2: Create Payment Instrument
            Log::info('CyberSource: Creating Payment Instrument', ['instrument_id' => $instrumentId]);
            $paymentInstrumentResult = $this->createPaymentInstrument($instrumentId, $data);
            $flowResults['step2'] = [
                'name' => 'Payment Instrument',
                'url' => "{$this->baseUrl}/tms/v1/paymentinstruments",
                'http_code' => $paymentInstrumentResult['response']['http_code'] ?? 0,
                'request_payload' => $paymentInstrumentResult['request_payload'] ?? '',
                'response' => $paymentInstrumentResult['response']['body'] ?? '',
                'success' => $paymentInstrumentResult['success']
            ];
            
            if (!$paymentInstrumentResult['success']) {
                return $this->errorResponse('Failed to create Payment Instrument', array_merge($paymentInstrumentResult, ['flow_results' => $flowResults]));
            }
            
            $paymentInstrumentId = $paymentInstrumentResult['payment_instrument_id'];
            
            // STEP 3: Perform 3D Secure authentication
            Log::info('CyberSource: Starting 3D Secure flow', ['payment_instrument_id' => $paymentInstrumentId]);
            $threeDSResult = $this->performThreeDSecure($paymentInstrumentId, $data);
            
            // STEP 4: Authorize payment
            if ($threeDSResult['success']) {
                Log::info('CyberSource: Authorizing payment');
                $authResult = $this->authorizePayment($paymentInstrumentId, $data, $threeDSResult);
                
                // Save payment to database
                $payment = $this->savePayment($data, $authResult, $threeDSResult);
                
                return [
                    'success' => $authResult['success'],
                    'payment' => $payment,
                    'data' => $authResult,
                    'flow_results' => $flowResults
                ];
            } else if (isset($threeDSResult['challenge_required'])) {
                // Return challenge data
                return [
                    'success' => false,
                    'challenge_required' => true,
                    'data' => $threeDSResult,
                    'flow_results' => $flowResults
                ];
            } else {
                return $this->errorResponse('3D Secure authentication failed', array_merge($threeDSResult, ['flow_results' => $flowResults]));
            }
            
        } catch (Exception $e) {
            Log::error('CyberSource Payment Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Payment processing error', ['error' => $e->getMessage(), 'flow_results' => $flowResults]);
        }
    }
    
    /**
     * Create Instrument Identifier
     *
     * @param string $cardNumber Card number
     * @return array
     */
    public function createInstrumentIdentifier(string $cardNumber): array
    {
        $url = "{$this->baseUrl}/tms/v1/instrumentidentifiers";
        $cleanCard = str_replace(' ', '', $cardNumber);
        
        $payload = json_encode([
            'card' => ['number' => $cleanCard]
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $data = json_decode($response['body'], true);
            Log::info('Instrument Identifier created', ['id' => $data['id'] ?? null]);
            
            return [
                'success' => true,
                'instrument_id' => $data['id'] ?? null,
                'response' => $response,
                'request_payload' => $payload
            ];
        }
        
        Log::error('Failed to create Instrument Identifier', ['response' => $response]);
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response,
            'request_payload' => $payload
        ];
    }
    
    /**
     * Create Payment Instrument
     *
     * @param string $instrumentId Instrument identifier ID
     * @param array $data Payment data
     * @return array
     */
    public function createPaymentInstrument(string $instrumentId, array $data): array
    {
        $url = "{$this->baseUrl}/tms/v1/paymentinstruments";
        
        // Build billTo array - exclude empty optional fields
        $billTo = array_filter([
            'firstName' => $data['first_name'],
            'lastName' => $data['last_name'],
            'company' => !empty($data['company']) ? $data['company'] : null,
            'address1' => $data['address1'],
            'locality' => $data['city'],
            'administrativeArea' => $data['state'],
            'postalCode' => $data['postal_code'],
            'country' => $data['country'],
            'email' => $data['email'],
            'phoneNumber' => !empty($data['phone']) ? $data['phone'] : null,
        ], function($value) {
            return $value !== null && $value !== '';
        });
        
        $payload = json_encode([
            'card' => [
                'expirationMonth' => $data['expiration_month'],
                'expirationYear' => $data['expiration_year'],
                'type' => $this->mapCardType($data['card_type'] ?? $data['card_number'])
            ],
            'billTo' => $billTo,
            'instrumentIdentifier' => ['id' => $instrumentId]
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $responseData = json_decode($response['body'], true);
            Log::info('Payment Instrument created', ['id' => $responseData['id'] ?? null]);
            
            return [
                'success' => true,
                'payment_instrument_id' => $responseData['id'] ?? null,
                'response' => $response,
                'request_payload' => $payload
            ];
        }
        
        Log::error('Failed to create Payment Instrument', ['response' => $response]);
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response,
            'request_payload' => $payload
        ];
    }
    
    /**
     * Perform 3D Secure authentication
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param array $data Payment data
     * @return array
     */
    protected function performThreeDSecure(string $paymentInstrumentId, array $data): array
    {
        // STEP 3: Setup 3DS
        $setupResult = $this->setup3DSSecure($paymentInstrumentId, $data);
        
        if (!$setupResult['success']) {
            return ['success' => false, 'error' => '3DS Setup failed'];
        }
        
        // STEP 4: Check Enrollment (pass paymentInstrumentId separately)
        $enrollmentResult = $this->checkEnrollment($paymentInstrumentId, $setupResult['data'], $data);
        
        if (!$enrollmentResult['success']) {
            return ['success' => false, 'error' => 'Enrollment check failed'];
        }
        
        // STEP 5: Process enrollment result
        return $this->processEnrollment($enrollmentResult['data'], $data);
    }
    
    /**
     * Setup 3D Secure (authentication-setups endpoint)
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param array $data Payment data
     * @return array
     */
    public function setup3DSSecure(string $paymentInstrumentId, array $data): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentication-setups";
        
        $payload = json_encode([
            'paymentInformation' => [
                'customer' => ['customerId' => $paymentInstrumentId]
            ]
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $responseData = json_decode($response['body'], true);
            
            return [
                'success' => true,
                'data' => $responseData
            ];
        }
        
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response
        ];
    }
    
    /**
     * Check enrollment status (authentications endpoint)
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param array $setupData Setup response
     * @param array $data Payment data
     * @return array
     */
    public function checkEnrollment(string $paymentInstrumentId, array $setupData, array $data): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentications";
        
        // Extract device information
        $deviceInfo = [
            'httpAcceptContent' => $_SERVER['HTTP_ACCEPT'] ?? '*/*',
            'httpBrowserColorDepth' => '24',
            'httpBrowserJavaEnabled' => 'true',
            'httpBrowserJavaScriptEnabled' => 'true',
            'httpBrowserLanguage' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US',
            'httpBrowserScreenHeight' => '1080',
            'httpBrowserScreenWidth' => '1920',
            'httpBrowserTimeDifference' => '0',
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'userAgentBrowserValue' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => time() . rand(1000, 9999)
            ],
            'consumerAuthenticationInformation' => [
                'referenceId' => $setupData['consumerAuthenticationInformation']['referenceId'] ?? '',
                'returnUrl' => config('cybersource.challenge_return_url'),
                'deviceChannel' => 'browser'
            ],
            'deviceInformation' => $deviceInfo,
            'orderInformation' => [
                'amountDetails' => [
                    'currency' => $data['currency'] ?? 'USD',
                    'totalAmount' => $data['amount']
                ]
            ],
            'paymentInformation' => [
                'customer' => ['customerId' => $paymentInstrumentId]
            ]
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $responseData = json_decode($response['body'], true);
            
            return [
                'success' => true,
                'data' => $responseData
            ];
        }
        
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response
        ];
    }
    
    /**
     * Process enrollment result
     *
     * @param array $enrollmentData Enrollment data
     * @param array $data Payment data
     * @return array
     */
    public function processEnrollment(array $enrollmentData, array $data): array
    {
        $authInfo = $enrollmentData['consumerAuthenticationInformation'] ?? [];
        $veresEnrolled = $authInfo['veresEnrolled'] ?? '';
        $paresStatus = $authInfo['paresStatus'] ?? '';
        
        // Detectar si es Mastercard para loggear UCAF
        $isMastercard = $this->isMastercard($data['card_number'] ?? null);
        
        // Log completo de los datos de enrollment recibidos
        Log::info('📋 Enrollment Data Received', [
            'veresEnrolled' => $veresEnrolled,
            'paresStatus' => $paresStatus,
            'authenticationTransactionId' => $authInfo['authenticationTransactionId'] ?? 'NULL',
            'cavv' => $authInfo['cavv'] ?? 'NULL',
            'eciRaw' => $authInfo['eciRaw'] ?? 'NULL',
            'xid' => $authInfo['xid'] ?? 'NULL',
            'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? 'NULL',
            'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? 'NULL',
            'specificationVersion' => $authInfo['specificationVersion'] ?? 'NULL',
            // ✅ Mastercard UCAF: Capturar datos UCAF del enrollment cuando es Mastercard
            'isMastercard' => $isMastercard,
            'has_ucafAuthenticationData' => !empty($authInfo['ucafAuthenticationData']),
            'has_ucafCollectionIndicator' => !empty($authInfo['ucafCollectionIndicator']),
            'ucafAuthenticationData' => $authInfo['ucafAuthenticationData'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
            'ucafCollectionIndicator' => $authInfo['ucafCollectionIndicator'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
            'full_consumerAuthInfo' => $authInfo
        ]);
        
        // Frictionless flow (Y,Y) - CORRECTO: Setup -> Enrollment -> Authorization -> Capture
        if ($veresEnrolled === 'Y' && $paresStatus === 'Y') {
            Log::info('✅ Frictionless Flow - Authentication Successful', [
                'has_required_fields' => !empty($authInfo['authenticationTransactionId']) && !empty($authInfo['cavv']) && !empty($authInfo['eciRaw'])
            ]);
            
            return [
                'success' => true,
                'flow_type' => 'frictionless',
                'enrollment_data' => $enrollmentData
            ];
        }
        
        // Challenge required (Y,C) - CORRECTO: Setup -> Enrollment -> Validate -> Authorization -> Capture
        if ($veresEnrolled === 'Y' && $paresStatus === 'C') {
            Log::info('🔄 Challenge Flow - Step-up Authentication Required', [
                'step_up_url' => $authInfo['stepUpUrl'] ?? 'MISSING',
                'access_token' => $authInfo['accessToken'] ?? 'MISSING',
                'reference_id' => $authInfo['referenceId'] ?? 'MISSING'
            ]);
            
            return [
                'success' => false,
                'challenge_required' => true,
                'flow_type' => 'challenge',
                'step_up_url' => $authInfo['stepUpUrl'] ?? '',
                'access_token' => $authInfo['accessToken'] ?? '',
                'reference_id' => $authInfo['referenceId'] ?? '',
                'authentication_transaction_id' => $authInfo['authenticationTransactionId'] ?? '',
                'enrollment_data' => $enrollmentData
            ];
        }
        
        // Not enrolled (N,N)
        if ($veresEnrolled === 'N' && $paresStatus === 'N') {
            return [
                'success' => true,
                'flow_type' => 'not_enrolled',
                'enrollment_data' => $enrollmentData
            ];
        }
        
        // Attempt not authenticated (Y,U)
        if ($veresEnrolled === 'Y' && $paresStatus === 'U') {
            return [
                'success' => true,
                'flow_type' => 'attempt',
                'enrollment_data' => $enrollmentData
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Unknown enrollment scenario: ' . $veresEnrolled . ',' . $paresStatus,
            'enrollment_data' => $enrollmentData
        ];
    }
    
    /**
     * Process Challenge Authentication (PASO COMPLETO para flujo Y,C)
     * Este método debe ejecutarse DESPUÉS de que el usuario complete el challenge
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param string $authenticationTransactionId Authentication transaction ID from enrollment
     * @param array $data Payment data
     * @return array
     */
    public function processChallengeAuthentication(string $paymentInstrumentId, string $authenticationTransactionId, array $data): array
    {
        $flowResults = [];
        
        try {
            // STEP 1: Validate Challenge Authentication
            Log::info('CyberSource: Validating Challenge Authentication', [
                'payment_instrument_id' => $paymentInstrumentId,
                'authentication_transaction_id' => $authenticationTransactionId
            ]);
            
            $validateResult = $this->validateChallengeAuthentication($paymentInstrumentId, $authenticationTransactionId);
            $flowResults['validate'] = [
                'name' => 'Validate Challenge',
                'url' => "{$this->baseUrl}/risk/v1/authentication-results",
                'http_code' => $validateResult['response']['http_code'] ?? 0,
                'request_payload' => json_encode([
                    'paymentInformation' => ['customer' => ['customerId' => $paymentInstrumentId]],
                    'consumerAuthenticationInformation' => ['authenticationTransactionId' => $authenticationTransactionId]
                ]),
                'response' => $validateResult['response']['body'] ?? '',
                'success' => $validateResult['success']
            ];
            
            if (!$validateResult['success']) {
                return $this->errorResponse('Challenge validation failed', array_merge($validateResult, ['flow_results' => $flowResults]));
            }
            
            // STEP 2: Prepare 3DS data for authorization
            $threeDSData = [
                'success' => true,
                'flow_type' => 'challenge',
                'enrollment_data' => $validateResult['validation_data']
            ];
            
            // CRÍTICO: Asegurar que el authenticationTransactionId esté presente
            if (!empty($authenticationTransactionId) && isset($threeDSData['enrollment_data']['consumerAuthenticationInformation'])) {
                $threeDSData['enrollment_data']['consumerAuthenticationInformation']['authenticationTransactionId'] = $authenticationTransactionId;
            }
            
            // STEP 3: Authorize payment with validated 3DS data
            // CRÍTICO: Usar authorizeAfterChallengeValidation (PASO 5.5B) para flujo challenge Y,C
            Log::info('CyberSource: Authorizing payment after challenge validation');
            $authResult = $this->authorizeAfterChallengeValidation(
                $paymentInstrumentId,
                $validateResult['validation_data'],
                $authenticationTransactionId,
                $data
            );
            $flowResults['authorization'] = [
                'name' => 'Authorization (After Challenge)',
                'url' => "{$this->baseUrl}/pts/v2/payments",
                'http_code' => $authResult['http_code'] ?? $authResult['response']['http_code'] ?? 0,
                'request_payload' => $authResult['request_payload'] ?? '',
                'response' => $authResult['raw_response']['body'] ?? $authResult['response']['body'] ?? '',
                'success' => $authResult['success']
            ];
            
            if (!$authResult['success']) {
                Log::error('❌ Authorization failed in processChallengeAuthentication', [
                    'http_code' => $authResult['http_code'] ?? 'UNKNOWN',
                    'error' => $authResult['error'] ?? 'UNKNOWN',
                    'response_data' => $authResult['data'] ?? null
                ]);
                return $this->errorResponse(
                    'Authorization failed after challenge: ' . ($authResult['error'] ?? 'HTTP ' . ($authResult['http_code'] ?? 'UNKNOWN')),
                    array_merge($authResult, ['flow_results' => $flowResults])
                );
            }
            
            // ✅ STEP 4: Payment ya fue guardado por authorizeAfterChallengeValidation
            // No necesitamos guardarlo nuevamente aquí
            $payment = $authResult['payment'] ?? null;
            
            if (!$payment) {
                Log::error('⚠️ PASO 5.5B: Payment not returned from authorizeAfterChallengeValidation', [
                    'auth_result_keys' => array_keys($authResult),
                    'auth_result_success' => $authResult['success'] ?? false
                ]);
                // Fallback: intentar guardar si no fue guardado
                try {
                    $payment = $this->savePayment($data, $authResult, $threeDSData);
                    Log::info('✅ Payment saved as fallback');
                } catch (\Exception $e) {
                    Log::error('❌ Failed to save payment as fallback', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
                }
            }
            
            return [
                'success' => true,
                'payment' => $payment,
                'data' => $authResult,
                'flow_results' => $flowResults
            ];
            
        } catch (Exception $e) {
            Log::error('CyberSource Challenge Processing Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return $this->errorResponse('Challenge processing error', ['error' => $e->getMessage(), 'flow_results' => $flowResults]);
        }
    }

    /**
     * Authorize payment
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param array $data Payment data
     * @param array $threeDSResult 3DS result
     * @return array
     */
    public function authorizePayment(string $paymentInstrumentId, array $data, array $threeDSResult): array
    {
        $url = "{$this->baseUrl}/pts/v2/payments";
        // Determine commerceIndicator for brand mapping to correct ECI on processor
        $commerceIndicator = $this->determineCommerceIndicator($data['card_number'] ?? null);

        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => uniqid('TC_', true),
            ],
            'processingInformation' => [
                'capture' => config('cybersource.capture_on_authorization', true),
                ...(isset($commerceIndicator) ? ['commerceIndicator' => $commerceIndicator] : []),
                'actionList' => ['CONSUMER_AUTHENTICATION'],  // CRÍTICO: Validar 3DS
                'authorizationOptions' => [
                    'initiator' => ['type' => 'merchant']
                ]
            ],
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'USD'
                ]
                // billTo NO necesario: La información ya está almacenada en el Payment Instrument token (customerId)
            ]
        ]);
        
        // Add 3DS data if available
        if (!empty($threeDSResult['enrollment_data'])) {
            $authInfo = $threeDSResult['enrollment_data']['consumerAuthenticationInformation'] ?? [];
            $isMastercard = $this->isMastercard($data['card_number'] ?? null);
            
            // Log de debugging para ver qué datos tenemos disponibles
            Log::info('🔍 3DS Data Available for Authorization', [
                'flow_type' => $threeDSResult['flow_type'] ?? 'unknown',
                'has_authenticationTransactionId' => !empty($authInfo['authenticationTransactionId']),
                'has_cavv' => !empty($authInfo['cavv']),
                'has_eciRaw' => !empty($authInfo['eciRaw']),
                'authenticationTransactionId' => $authInfo['authenticationTransactionId'] ?? 'MISSING',
                'cavv' => $authInfo['cavv'] ?? 'MISSING',
                'eciRaw' => $authInfo['eciRaw'] ?? 'MISSING',
                'xid' => $authInfo['xid'] ?? 'MISSING',
                'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? 'MISSING',
                'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? 'MISSING',
                // ✅ Mastercard UCAF: Verificar que los datos UCAF estén disponibles del enrollment
                'isMastercard' => $isMastercard,
                'has_ucafAuthenticationData' => !empty($authInfo['ucafAuthenticationData']),
                'has_ucafCollectionIndicator' => !empty($authInfo['ucafCollectionIndicator']),
                'ucafAuthenticationData' => $authInfo['ucafAuthenticationData'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
                'ucafCollectionIndicator' => $authInfo['ucafCollectionIndicator'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A')
            ]);
            
            // Validación flexible: requerimos authenticationTransactionId + eciRaw y (CAVV o UCAF)
            $hasAuthId = !empty($authInfo['authenticationTransactionId']);
            $hasEci = !empty($authInfo['eciRaw']);
            $hasCavv = !empty($authInfo['cavv']);
            $hasUcaf = !empty($authInfo['ucafAuthenticationData']) && !empty($authInfo['ucafCollectionIndicator']);
            if (!$hasAuthId || !$hasEci || (!$hasCavv && !$hasUcaf)) {
                Log::error('❌ Missing required 3DS fields for authorization', [
                    'needs' => 'authenticationTransactionId + eciRaw + (cavv OR ucafAuthenticationData+ucafCollectionIndicator)',
                    'available_fields' => array_keys($authInfo),
                ]);
                return [
                    'success' => false,
                    'error' => 'Missing required 3DS authentication data',
                    'response' => [
                        'http_code' => 0,
                        'body' => json_encode(['message' => 'Missing 3DS data'])
                    ]
                ];
            }
            
            $payloadDecoded = json_decode($payload, true);
            $isMastercard = $this->isMastercard($data['card_number'] ?? null);
            
            // Construir consumerAuthenticationInformation según la marca de tarjeta
            // Visa/Amex: incluir CAVV
            // Mastercard: incluir UCAF (ucafAuthenticationData + ucafCollectionIndicator)
            $consumerAuth = array_filter([
                'authenticationTransactionId' => $authInfo['authenticationTransactionId'] ?? null,
                'eciRaw' => $authInfo['eciRaw'] ?? null,
                'xid' => $authInfo['xid'] ?? null,
                'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? null,
                'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? null,
                'specificationVersion' => $authInfo['specificationVersion'] ?? null,
                // ✅ Visa/Amex: CAVV (solo si NO es Mastercard)
                'cavv' => (!$isMastercard) ? ($authInfo['cavv'] ?? null) : null,
                // ✅ Mastercard: UCAF (solo si ES Mastercard y los datos están disponibles)
                'ucafAuthenticationData' => ($isMastercard && !empty($authInfo['ucafAuthenticationData'])) ? $authInfo['ucafAuthenticationData'] : null,
                'ucafCollectionIndicator' => ($isMastercard && !empty($authInfo['ucafCollectionIndicator'])) ? $authInfo['ucafCollectionIndicator'] : null,
            ], function($value) {
                return $value !== null && $value !== '' && $value !== false;
            });
            
            // Asegurar que specificationVersion siempre esté presente
            if (!isset($consumerAuth['specificationVersion'])) {
                $consumerAuth['specificationVersion'] = '2.2.0';
            }
            
            $payloadDecoded['consumerAuthenticationInformation'] = $consumerAuth;
            
            Log::info('📤 3DS Data being sent to authorization', [
                'flow_type' => $threeDSResult['flow_type'] ?? 'unknown',
                'fields_included' => array_keys($consumerAuth),
                'consumerAuth' => $consumerAuth
            ]);
            
            $payload = json_encode($payloadDecoded);
        }
        
        // Debug: Log the complete payload being sent
        Log::info('🚀 Authorization Request', [
            'url' => $url,
            'payment_instrument_id' => $paymentInstrumentId,
            'payload' => json_decode($payload, true),
            'commerceIndicator' => $commerceIndicator ?? 'NONE'
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $responseData = json_decode($response['body'], true);
            
            return [
                'success' => true,
                'transaction_id' => $responseData['id'] ?? null,
                'authorization_code' => $responseData['status'] ?? null,
                'processor_response' => $responseData['processorInformation']['responseCode'] ?? null,
                'data' => $responseData
            ];
        }
        
        Log::error('❌ Authorization failed', [
            'response' => $response,
            'payload_sent' => json_decode($payload, true)
        ]);
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response
        ];
    }
    
    /**
     * Validate challenge authentication (Validation Service)
     * Called after successful challenge (Y,C) - PASO 5.5A Production
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param string $authenticationTransactionId Authentication transaction ID from challenge
     * @return array
     */
    public function validateChallengeAuthentication(string $paymentInstrumentId, string $authenticationTransactionId): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentication-results";
        
        $payload = json_encode([
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'consumerAuthenticationInformation' => [
                'authenticationTransactionId' => $authenticationTransactionId
            ]
        ]);
        
        Log::info('🔍 PASO 5.5A: Validation Service Request', [
            'url' => $url,
            'paymentInstrumentId' => $paymentInstrumentId,
            'authenticationTransactionId' => $authenticationTransactionId
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        if ($response['http_code'] >= 200 && $response['http_code'] < 300) {
            $responseData = json_decode($response['body'], true);
            
            $authInfoValidate = $responseData['consumerAuthenticationInformation'] ?? [];
            Log::info('✅ VALIDATE: Challenge Authentication Successful', [
                'status' => $responseData['status'] ?? '',
                'paresStatus' => $authInfoValidate['paresStatus'] ?? '',
                'eciRaw' => $authInfoValidate['eciRaw'] ?? '',
                'cavv' => $authInfoValidate['cavv'] ?? '',
                // ✅ Mastercard UCAF: Verificar si el Validation devuelve UCAF
                'has_ucafAuthenticationData' => !empty($authInfoValidate['ucafAuthenticationData']),
                'has_ucafCollectionIndicator' => !empty($authInfoValidate['ucafCollectionIndicator']),
                'ucafAuthenticationData' => $authInfoValidate['ucafAuthenticationData'] ?? 'NOT_IN_VALIDATION',
                'ucafCollectionIndicator' => $authInfoValidate['ucafCollectionIndicator'] ?? 'NOT_IN_VALIDATION'
            ]);
            
            return [
                'success' => true,
                'validation_data' => $responseData,
                'response' => $response
            ];
        }
        
        Log::error('❌ VALIDATE: Challenge Authentication Failed', [
            'http_code' => $response['http_code'],
            'response' => $response['body']
        ]);
        
        return [
            'success' => false,
            'error' => 'HTTP ' . $response['http_code'],
            'response' => $response
        ];
    }
    
    /**
     * Authorize payment after challenge validation
     * Called after successful validation (Y,C) - PASO 5.5B Production
     *
     * @param string $paymentInstrumentId Payment instrument ID
     * @param array $validationResponse Response from validation service
     * @param string $originalAuthTransactionId Original authentication transaction ID from challenge
     * @param array $data Payment data
     * @return array
     */
    public function authorizeAfterChallengeValidation(string $paymentInstrumentId, array $validationResponse, string $originalAuthTransactionId, array $data): array
    {
        $url = "{$this->baseUrl}/pts/v2/payments";
        
        // Extract validated 3DS data
        $authInfo = $validationResponse['consumerAuthenticationInformation'] ?? [];
        // Determine commerceIndicator based on card brand (Visa/Mastercard) to avoid ECI 7
        $commerceIndicator = $this->determineCommerceIndicator($data['card_number'] ?? null);
        $isMastercard = $this->isMastercard($data['card_number'] ?? null);
        
        // ✅ CRÍTICO para Mastercard: Si el Validation no devuelve UCAF, obtenerlos del enrollment original
        if ($isMastercard && (empty($authInfo['ucafAuthenticationData']) || empty($authInfo['ucafCollectionIndicator']))) {
            // Intentar obtener UCAF del enrollment original guardado en sesión
            $challengeData = session('challenge_data');
            $enrollmentData = $challengeData['enrollment_data'] ?? null;
            
            if ($enrollmentData) {
                $enrollmentAuthInfo = $enrollmentData['consumerAuthenticationInformation'] ?? [];
                if (!empty($enrollmentAuthInfo['ucafAuthenticationData'])) {
                    $authInfo['ucafAuthenticationData'] = $enrollmentAuthInfo['ucafAuthenticationData'];
                    Log::info('✅ PASO 5.5B: UCAF obtenido del enrollment original', [
                        'ucafAuthenticationData_from' => 'enrollment_original',
                        'has_ucafCollectionIndicator' => !empty($enrollmentAuthInfo['ucafCollectionIndicator'])
                    ]);
                }
                if (!empty($enrollmentAuthInfo['ucafCollectionIndicator'])) {
                    $authInfo['ucafCollectionIndicator'] = $enrollmentAuthInfo['ucafCollectionIndicator'];
                    Log::info('✅ PASO 5.5B: UCAF Collection Indicator obtenido del enrollment original');
                }
            }
        }
        
        Log::info('🔍 PASO 5.5B: Using validated 3DS data', [
            'original_auth_transaction_id' => $originalAuthTransactionId,
            'validation_transaction_id' => $validationResponse['id'] ?? 'N/A',
            'has_cavv' => !empty($authInfo['cavv']),
            'has_eciRaw' => !empty($authInfo['eciRaw']),
            'eciRaw' => $authInfo['eciRaw'] ?? 'MISSING',
            'paresStatus' => $authInfo['paresStatus'] ?? 'MISSING',
            'commerceIndicator' => $commerceIndicator ?? 'NONE',
            // ✅ Mastercard UCAF: Verificar que los datos UCAF estén disponibles (del validation o enrollment)
            'isMastercard' => $isMastercard,
            'has_ucafAuthenticationData' => !empty($authInfo['ucafAuthenticationData']),
            'has_ucafCollectionIndicator' => !empty($authInfo['ucafCollectionIndicator']),
            'ucafAuthenticationData' => $authInfo['ucafAuthenticationData'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
            'ucafCollectionIndicator' => $authInfo['ucafCollectionIndicator'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A')
        ]);
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => uniqid('TC_CHALLENGE_', true)
            ],
            'processingInformation' => [
                'capture' => config('cybersource.capture_on_authorization', true),
                // Set commerceIndicator explicitly for authenticated 3DS
                ...(isset($commerceIndicator) ? ['commerceIndicator' => $commerceIndicator] : []),
                // ❌ NO incluir actionList en PASO 5.5B: La autorización NO debe tener actionList porque ya se usó en Setup (PASO 3)
                'authorizationOptions' => [
                    'initiator' => ['type' => 'merchant']
                ]
            ],
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'USD'
                ]
                // billTo NO necesario: La información ya está en el Payment Instrument token
            ],
            'consumerAuthenticationInformation' => array_filter([
                // ✅ CRÍTICO: Usar el authenticationTransactionId ORIGINAL del challenge (del PASO 4)
                'authenticationTransactionId' => $originalAuthTransactionId,
                'eciRaw' => $authInfo['eciRaw'] ?? null,
                'xid' => $authInfo['xid'] ?? null,
                'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? null,
                'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? null,
                'specificationVersion' => $authInfo['specificationVersion'] ?? '2.2.0',
                // ✅ Visa/Amex: CAVV (solo si NO es Mastercard)
                'cavv' => (!$isMastercard) ? ($authInfo['cavv'] ?? null) : null,
                // ✅ Mastercard: UCAF (solo si ES Mastercard y los datos están disponibles)
                'ucafAuthenticationData' => ($isMastercard && !empty($authInfo['ucafAuthenticationData'])) ? $authInfo['ucafAuthenticationData'] : null,
                'ucafCollectionIndicator' => ($isMastercard && !empty($authInfo['ucafCollectionIndicator'])) ? $authInfo['ucafCollectionIndicator'] : null,
            ], function($value) {
                return $value !== null && $value !== '' && $value !== false;
            })
        ]);
        
        Log::info('🚀 PASO 5.5B: Authorization Request', [
            'url' => $url,
            'payload' => json_decode($payload, true)
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        $success = $response['http_code'] >= 200 && $response['http_code'] < 300;
        $responseData = json_decode($response['body'], true);
        
        if ($success) {
            Log::info('✅ PASO 5.5B: Authorization Success', [
                'transaction_id' => $responseData['id'] ?? 'N/A',
                'status' => $responseData['status'] ?? 'N/A',
                'eciRaw' => $responseData['consumerAuthenticationInformation']['eciRaw'] ?? 'N/A'
            ]);
        } else {
            Log::error('❌ PASO 5.5B: Authorization Failed', [
                'http_code' => $response['http_code'],
                'response' => $responseData
            ]);
        }
        
        // Construir authResult y threeDSResult para guardar Payment
        $authResult = [
            'success' => $success,
            'transaction_id' => $responseData['id'] ?? null,
            'authorization_code' => $responseData['status'] ?? null,
            'processor_response' => $responseData['processorInformation']['responseCode'] ?? null,
            'data' => $responseData
        ];
        
        $threeDSData = [
            'success' => $success,
            'flow_type' => 'challenge',
            'enrollment_data' => $validationResponse  // Usar validation response como enrollment data
        ];
        
        // Guardar Payment cuando es exitoso
        $payment = null;
        if ($success) {
            try {
                $payment = $this->savePayment($data, $authResult, $threeDSData);
                Log::info('💾 PASO 5.5B: Payment saved to database', ['payment_id' => $payment->id]);
            } catch (\Exception $e) {
                Log::error('❌ PASO 5.5B: Failed to save payment', ['error' => $e->getMessage()]);
            }
        }
        
        return [
            'success' => $success,
            'transaction_id' => $responseData['id'] ?? null,
            'authorization_code' => $responseData['status'] ?? null,
            'processor_response' => $responseData['processorInformation']['responseCode'] ?? null,
            'data' => $responseData,
            'http_code' => $response['http_code'],
            'raw_response' => $response,
            'payment' => $payment  // ✅ Retornar Payment para CheckoutController
        ];
    }
    
    /**
     * Make HTTP request to CyberSource API
     *
     * @param string $method HTTP method
     * @param string $url Full URL
     * @param string $payload Request body
     * @return array
     */
    protected function makeRequest(string $method, string $url, string $payload): array
    {
        $date = gmdate('D, d M Y H:i:s T');
        $path = parse_url($url, PHP_URL_PATH);
        $query = parse_url($url, PHP_URL_QUERY);
        $requestTarget = strtolower($method) . ' ' . $path . ($query ? '?' . $query : '');
        
        $digest = $this->hmacGenerator->generateDigest($payload);
        $signature = $this->hmacGenerator->generateSignature(
            $this->merchantId,
            $this->apiSecret,
            $date,
            $requestTarget,
            $digest
        );
        
        $signatureHeader = sprintf(
            'keyid="%s", algorithm="HmacSHA256", headers="host v-c-date request-target digest v-c-merchant-id", signature="%s"',
            $this->apiKey,
            $signature
        );
        
        $hostHeader = parse_url($this->baseUrl, PHP_URL_HOST) ?: 'apitest.cybersource.com';
        $headers = [
            'Content-Type: application/json',
            'Accept: */*',
            'digest: ' . $digest,
            'signature: ' . $signatureHeader,
            'v-c-date: ' . $date,
            'v-c-merchant-id: ' . $this->merchantId,
            'host: ' . $hostHeader
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => config('cybersource.request_timeout', 30),
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        
        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        // Log request/response
        if (config('cybersource.log_requests')) {
            Log::info('CyberSource API Request', [
                'method' => $method,
                'url' => $url,
                'http_code' => $httpCode
            ]);
        }
        
        if (config('cybersource.log_responses')) {
            Log::info('CyberSource API Response', [
                'http_code' => $httpCode,
                'body' => $body
            ]);
        }
        
        return [
            'body' => $body,
            'http_code' => $httpCode,
            'error' => $error,
        ];
    }
    
    /**
     * Map card type to CyberSource string (NOT numeric code)
     * CyberSource expects: 'visa', 'mastercard', 'american express'
     */
    protected function mapCardType(string $input): string
    {
        $normalized = strtolower(trim($input));
        // Normalize common names/aliases to the exact strings required by CyberSource
        $byName = [
            'visa' => 'visa',
            'mastercard' => 'mastercard',
            'mc' => 'mastercard',
            'amex' => 'american express',
            'american express' => 'american express',
        ];
        if (isset($byName[$normalized])) {
            return $byName[$normalized];
        }
        // Detect by PAN (BIN ranges)
        $digits = preg_replace('/\s+/', '', $input);
        if ($digits !== '') {
            if (strpos($digits, '34') === 0 || strpos($digits, '37') === 0) {
                return 'american express';
            }
            if (strpos($digits, '4') === 0) {
                return 'visa';
            }
            if (strpos($digits, '5') === 0 || strpos($digits, '2') === 0) {
                return 'mastercard';
            }
        }
        return 'visa';
    }
    
    /**
     * Determina el commerceIndicator según la marca de tarjeta para mapeo correcto de ECI
     * Visa -> 'vbv'
     * Mastercard -> 'spa'
     * Retorna null si no se puede determinar
     */
    protected function determineCommerceIndicator(?string $cardNumber): ?string
    {
        if (empty($cardNumber)) {
            return null;
        }
        $digits = preg_replace('/\s+/', '', $cardNumber);
        if ($digits === '') {
            return null;
        }
        // Visa empieza con 4
        if (strpos($digits, '4') === 0) {
            return 'vbv';
        }
        // Mastercard comúnmente empieza con 5 (BIN 51-55) y 2-series (2221-2720)
        if (strpos($digits, '5') === 0) {
            return 'spa';
        }
        if (strpos($digits, '2') === 0) {
            return 'spa';
        }
        return null;
    }
    
    /**
     * Detecta si la tarjeta es Mastercard
     * Mastercard requiere UCAF en transacciones tokenizadas (ucafAuthenticationData + ucafCollectionIndicator)
     * 
     * @param string|null $cardNumber Número de tarjeta
     * @return bool
     */
    protected function isMastercard(?string $cardNumber): bool
    {
        if (empty($cardNumber)) {
            return false;
        }
        $digits = preg_replace('/\s+/', '', $cardNumber);
        if ($digits === '') {
            return false;
        }
        // Mastercard empieza con 5 (BIN 51-55) o 2-series (2221-2720)
        return strpos($digits, '5') === 0 || strpos($digits, '2') === 0;
    }
    
    /**
     * Save payment to database
     *
     * @param array $data Payment data
     * @param array $authResult Authorization result
     * @param array $threeDSResult 3DS result
     * @return Payment
     */
    protected function savePayment(array $data, array $authResult, array $threeDSResult): Payment
    {
        $enrollmentData = $threeDSResult['enrollment_data'] ?? [];
        $authInfo = $enrollmentData['consumerAuthenticationInformation'] ?? [];
        
        return Payment::create([
            'user_id' => auth()->id(),
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'USD',
            'status' => $authResult['success'] ? 'completed' : 'failed',
            'transaction_id' => $authResult['transaction_id'] ?? null,
            'authorization_code' => $authResult['data']['processorInformation']['approvalCode'] ?? null,
            'flow_type' => $threeDSResult['flow_type'] ?? null,
            'liability_shift' => ($threeDSResult['flow_type'] ?? '') === 'frictionless',
            'card_last_four' => substr(str_replace(' ', '', $data['card_number']), -4),
            'card_type' => $this->mapCardType($data['card_number']),
            'threeds_version' => '2.2.0',
            'threeds_eci' => $authInfo['eciRaw'] ?? null,
            'threeds_cavv' => $authInfo['cavv'] ?? null,
            'threeds_xid' => $authInfo['xid'] ?? null,
            'threeds_authentication_status' => $enrollmentData['status'] ?? null,
            'metadata' => json_encode([
                'auth' => $authResult,
                '3ds' => $threeDSResult,
                'enrollment' => $enrollmentData
            ]),
            'error_message' => $authResult['error'] ?? null,
            'processed_at' => now(),
        ]);
    }
    
    /**
     * Error response helper
     *
     * @param string $message Error message
     * @param array $data Additional data
     * @return array
     */
    protected function errorResponse(string $message, array $data): array
    {
        return [
            'success' => false,
            'error' => $message,
            'data' => $data
        ];
    }

    /**
     * Setup payment instrument (STEPS 1 & 2 combined for checkout)
     *
     * @param array $data Payment data
     * @return array
     */
    public function setupPaymentInstrument(array $data): array
    {
        // STEP 1: Create Instrument Identifier
        $instrumentResult = $this->createInstrumentIdentifier($data['card_number']);
        
        if (!$instrumentResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create Instrument Identifier'
            ];
        }
        
        // STEP 2: Create Payment Instrument
        $paymentInstrumentResult = $this->createPaymentInstrument(
            $instrumentResult['instrument_id'],
            $data
        );
        
        if (!$paymentInstrumentResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to create Payment Instrument'
            ];
        }
        
        return [
            'success' => true,
            'instrument_id' => $instrumentResult['instrument_id'],
            'payment_instrument_id' => $paymentInstrumentResult['payment_instrument_id']
        ];
    }

    /**
     * Save payment record to database (public wrapper for checkout)
     *
     * @param array $data Payment data
     * @param array $authResult Authorization result
     * @param array $threeDSResult 3DS result
     * @return Payment
     */
    public function savePaymentRecord(array $data, array $authResult, array $threeDSResult): Payment
    {
        return $this->savePayment($data, $authResult, $threeDSResult);
    }

    // ============================================
    // DEBUG METHODS - Execute steps individually
    // ============================================

    /**
     * DEBUG: Create Instrument Identifier only (PASO 1)
     */
    public function debugCreateInstrumentIdentifier(array $data): array
    {
        $url = "{$this->baseUrl}/tms/v1/instrumentidentifiers";
        
        $payload = json_encode([
            'card' => [
                'number' => str_replace(' ', '', $data['card_number'])
            ]
        ]);
        
        Log::info('DEBUG PASO 1: Creating Instrument Identifier', ['url' => $url]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        return [
            'step' => 'PASO 1: Create Instrument Identifier',
            'description' => 'Solo se envía el número de tarjeta para crear un identificador único',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => json_decode($response['body'], true),
            'success' => $response['http_code'] >= 200 && $response['http_code'] < 300
        ];
    }

    /**
     * DEBUG: Create Payment Instrument only (PASO 2)
     */
    public function debugCreatePaymentInstrument(string $instrumentId, array $data): array
    {
        $url = "{$this->baseUrl}/tms/v1/paymentinstruments";
        
        $payload = json_encode([
            'card' => [
                'expirationMonth' => $data['expiry_month'],
                'expirationYear' => $data['expiry_year'],
                'type' => $this->mapCardType($data['card_type'] ?? $data['card_number'])
            ],
            'billTo' => array_filter([
                'firstName' => $data['first_name'],
                'lastName' => $data['last_name'],
                'company' => !empty($data['company']) ? $data['company'] : null,
                'address1' => $data['address1'],
                'locality' => $data['city'],
                'administrativeArea' => $data['state'],
                'postalCode' => $data['postal_code'],
                'country' => $data['country'],
                'email' => $data['email'],
                'phoneNumber' => !empty($data['phone']) ? $data['phone'] : null
            ], function($value) {
                return $value !== null && $value !== '';
            }),
            'instrumentIdentifier' => [
                'id' => $instrumentId
            ]
        ]);
        
        Log::info('DEBUG PASO 2: Creating Payment Instrument', ['url' => $url, 'instrument_id' => $instrumentId]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        return [
            'step' => 'PASO 2: Create Payment Instrument',
            'description' => 'Se envía toda la información de la tarjeta (expiración, billing info) junto con el Instrument ID',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => json_decode($response['body'], true),
            'success' => $response['http_code'] >= 200 && $response['http_code'] < 300
        ];
    }

    /**
     * DEBUG: Setup 3D Secure only (PASO 3)
     */
    public function debugSetup3DS(string $paymentInstrumentId, array $data): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentication-setups";
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => time() . rand(1000, 9999)
            ],
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ]
        ]);
        
        Log::info('DEBUG PASO 3: Setting up 3D Secure', ['url' => $url]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        return [
            'step' => 'PASO 3: Setup 3D Secure',
            'description' => 'Se configura 3D Secure con el Payment Instrument ID',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => json_decode($response['body'], true),
            'success' => $response['http_code'] >= 200 && $response['http_code'] < 300
        ];
    }

    /**
     * DEBUG: Check Enrollment only (PASO 4)
     */
    public function debugCheckEnrollment(string $paymentInstrumentId, array $setupData, array $data): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentications";
        
        $deviceInfo = [
            'httpAcceptContent' => $_SERVER['HTTP_ACCEPT'] ?? '*/*',
            'httpBrowserColorDepth' => '24',
            'httpBrowserJavaEnabled' => 'true',
            'httpBrowserJavaScriptEnabled' => 'true',
            'httpBrowserLanguage' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en-US',
            'httpBrowserScreenHeight' => '1080',
            'httpBrowserScreenWidth' => '1920',
            'httpBrowserTimeDifference' => '0',
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'userAgentBrowserValue' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => time() . rand(1000, 9999)
            ],
            'consumerAuthenticationInformation' => [
                'referenceId' => $setupData['consumerAuthenticationInformation']['referenceId'] ?? '',
                'returnUrl' => config('cybersource.challenge_return_url'),
                'deviceChannel' => 'browser'
            ],
            'deviceInformation' => $deviceInfo,
            'orderInformation' => [
                'amountDetails' => [
                    'currency' => $data['currency'] ?? 'USD',
                    'totalAmount' => $data['amount']
                ]
            ],
            'paymentInformation' => [
                'customer' => ['customerId' => $paymentInstrumentId]
            ]
        ]);
        
        Log::info('DEBUG PASO 4: Checking Enrollment', ['url' => $url]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        return [
            'step' => 'PASO 4: Check Enrollment',
            'description' => 'Se verifica si la tarjeta está inscrita en 3D Secure',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => json_decode($response['body'], true),
            'success' => $response['http_code'] >= 200 && $response['http_code'] < 300
        ];
    }

    /**
     * DEBUG: Authorization after Challenge (PASO 5.5B - Challenge Y,C)
     * Usa los datos validados del PASO 5.5A (Validation Service)
     */
    public function debugAuthorizationAfterValidation(string $paymentInstrumentId, array $validationResponse, string $originalAuthTransactionId, array $data): array
    {
        $url = "{$this->baseUrl}/pts/v2/payments";
        
        // Extraer datos validados del PASO 5.5A
        $authInfo = $validationResponse['consumerAuthenticationInformation'] ?? [];
        // Determinar commerceIndicator según marca (Visa/Mastercard) para evitar ECI 7 en el procesador
        $commerceIndicator = $this->determineCommerceIndicator($data['card_number'] ?? null);
        $isMastercard = $this->isMastercard($data['card_number'] ?? null);
        
        Log::info('🔍 DEBUG PASO 5.5B: Using validated 3DS data', [
            'original_auth_transaction_id' => $originalAuthTransactionId,
            'validation_transaction_id' => $validationResponse['id'] ?? 'N/A',
            'has_cavv' => !empty($authInfo['cavv']),
            'has_eciRaw' => !empty($authInfo['eciRaw']),
            'eciRaw' => $authInfo['eciRaw'] ?? 'MISSING',
            'paresStatus' => $authInfo['paresStatus'] ?? 'MISSING',
            'commerceIndicator' => $commerceIndicator ?? 'NONE',
            // ✅ Mastercard UCAF: Verificar que los datos UCAF estén disponibles del validation
            'isMastercard' => $isMastercard,
            'has_ucafAuthenticationData' => !empty($authInfo['ucafAuthenticationData']),
            'has_ucafCollectionIndicator' => !empty($authInfo['ucafCollectionIndicator']),
            'ucafAuthenticationData' => $authInfo['ucafAuthenticationData'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
            'ucafCollectionIndicator' => $authInfo['ucafCollectionIndicator'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A')
        ]);
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => 'challenge_auth_' . time()
            ],
            'processingInformation' => [
                'capture' => config('cybersource.capture_on_auth', true),
                // Establecer commerceIndicator explícito para 3DS autenticado
                // Visa: vbv (ECI 05/06); Mastercard: spa (ECI 02/01)
                // Si no se puede determinar, omitir la clave para no interferir
                ...(isset($commerceIndicator) ? ['commerceIndicator' => $commerceIndicator] : []),
                // ❌ NO incluir actionList en PASO 5.5B: La autorización NO debe tener actionList porque ya se usó en Setup (PASO 3)
                'authorizationOptions' => [
                    'initiator' => ['type' => 'merchant']
                ]
            ],
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'totalAmount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'USD'
                ]
                // billTo NO necesario: La información ya está almacenada en el Payment Instrument token (customerId)
            ],
            'consumerAuthenticationInformation' => array_filter([
                // ✅ CRÍTICO: Usar el authenticationTransactionId ORIGINAL del challenge (del PASO 4)
                // NO usar el ID de la transacción de validation
                'authenticationTransactionId' => $originalAuthTransactionId,
                'eciRaw' => $authInfo['eciRaw'] ?? null,
                'xid' => $authInfo['xid'] ?? null,
                'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? null,
                'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? null,
                'specificationVersion' => $authInfo['specificationVersion'] ?? '2.2.0',
                // ✅ Visa/Amex: CAVV (solo si NO es Mastercard)
                'cavv' => (!$isMastercard) ? ($authInfo['cavv'] ?? null) : null,
                // ✅ Mastercard: UCAF (solo si ES Mastercard y los datos están disponibles)
                'ucafAuthenticationData' => ($isMastercard && !empty($authInfo['ucafAuthenticationData'])) ? $authInfo['ucafAuthenticationData'] : null,
                'ucafCollectionIndicator' => ($isMastercard && !empty($authInfo['ucafCollectionIndicator'])) ? $authInfo['ucafCollectionIndicator'] : null,
            ], function($value) {
                return $value !== null && $value !== '' && $value !== false;
            })
        ]);
        
        Log::info('🚀 DEBUG PASO 5.5B: Authorization Request (After Validation)', [
            'url' => $url,
            'payload' => json_decode($payload, true)
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        $success = $response['http_code'] >= 200 && $response['http_code'] < 300;
        $responseData = json_decode($response['body'], true);
        
        // Si fue exitoso, guardar en la base de datos
        $payment = null;
        if ($success && $responseData) {
            try {
                $payment = \App\Models\Payment::create([
                    'user_id' => auth()->id(),
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'USD',
                    'status' => ($responseData['status'] ?? '') === 'AUTHORIZED' ? 'completed' : 'failed',
                    'transaction_id' => $responseData['id'] ?? null,
                    'authorization_code' => $responseData['processorInformation']['approvalCode'] ?? null,
                    'flow_type' => 'challenge',
                    'liability_shift' => true,
                    'card_last_four' => substr(str_replace(' ', '', $data['card_number']), -4),
                    'card_type' => $this->mapCardType($data['card_number']),
                    'threeds_version' => '2.2.0',
                    'threeds_eci' => $authInfo['eciRaw'] ?? null,
                    'threeds_cavv' => $authInfo['cavv'] ?? null,
                    'threeds_xid' => $authInfo['xid'] ?? null,
                    'threeds_authentication_status' => $validationResponse['status'] ?? 'VALIDATED',
                    'enrollment_data' => $validationResponse,
                    'metadata' => json_encode([
                        'validation' => $validationResponse,
                        'authorization' => $responseData,
                        'payment_instrument_id' => $paymentInstrumentId
                    ]),
                    'processed_at' => now(),
                ]);
                
                Log::info('💾 Payment saved to database (Challenge - After Validation)', ['payment_id' => $payment->id]);
            } catch (\Exception $e) {
                Log::error('Failed to save payment to database', ['error' => $e->getMessage()]);
            }
        }
        
        return [
            'step' => 'PASO 5.5B: Authorization After Validation (Challenge Y,C)',
            'description' => 'Autorización usando los datos validados del PASO 5.5A. Llamada separada según documentación CyberSource.',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => $responseData,
            'success' => $success,
            'payment_id' => $payment ? $payment->id : null,
            'saved_to_db' => $payment !== null
        ];
    }

    /**
     * DEBUG: Authorization only (PASO 5 - Frictionless Y,Y)
     */
    public function debugAuthorization(string $paymentInstrumentId, array $enrollmentData, array $data): array
    {
        $url = "{$this->baseUrl}/pts/v2/payments";
        $authInfo = $enrollmentData['consumerAuthenticationInformation'] ?? [];
        $isMastercard = $this->isMastercard($data['card_number'] ?? null);
        
        $payload = json_encode([
            'clientReferenceInformation' => [
                'code' => time() . rand(1000, 9999)
            ],
            'processingInformation' => [
                'capture' => config('cybersource.capture_on_auth', true),
                'actionList' => ['CONSUMER_AUTHENTICATION'],  // CRÍTICO: Validar 3DS
                'authorizationOptions' => [
                    'initiator' => ['type' => 'merchant']
                ]
            ],
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'orderInformation' => [
                'amountDetails' => [
                    'currency' => $data['currency'] ?? 'USD',
                    'totalAmount' => $data['amount']
                ]
                // billTo NO necesario: La información ya está almacenada en el Payment Instrument token (customerId)
            ],
            'consumerAuthenticationInformation' => array_filter([
                'authenticationTransactionId' => $authInfo['authenticationTransactionId'] ?? null,
                'eciRaw' => $authInfo['eciRaw'] ?? null,
                'xid' => $authInfo['xid'] ?? null,
                'directoryServerTransactionId' => $authInfo['directoryServerTransactionId'] ?? null,
                'threeDSServerTransactionId' => $authInfo['threeDSServerTransactionId'] ?? null,
                'specificationVersion' => $authInfo['specificationVersion'] ?? '2.2.0',
                // ✅ Visa/Amex: CAVV (solo si NO es Mastercard)
                'cavv' => (!$isMastercard) ? ($authInfo['cavv'] ?? null) : null,
                // ✅ Mastercard: UCAF (solo si ES Mastercard y los datos están disponibles)
                'ucafAuthenticationData' => ($isMastercard && !empty($authInfo['ucafAuthenticationData'])) ? $authInfo['ucafAuthenticationData'] : null,
                'ucafCollectionIndicator' => ($isMastercard && !empty($authInfo['ucafCollectionIndicator'])) ? $authInfo['ucafCollectionIndicator'] : null,
            ], function($value) {
                return $value !== null && $value !== '' && $value !== false;
            })
        ]);
        
        // Log de información 3DS disponible
        Log::info('🔍 DEBUG PASO 5: 3DS Data Available for Authorization', [
            'has_authenticationTransactionId' => !empty($authInfo['authenticationTransactionId']),
            'has_cavv' => !empty($authInfo['cavv']),
            'has_eciRaw' => !empty($authInfo['eciRaw']),
            'eciRaw' => $authInfo['eciRaw'] ?? 'MISSING',
            'authenticationTransactionId' => $authInfo['authenticationTransactionId'] ?? 'MISSING',
            // ✅ Mastercard UCAF: Verificar que los datos UCAF estén disponibles del enrollment
            'isMastercard' => $isMastercard,
            'has_ucafAuthenticationData' => !empty($authInfo['ucafAuthenticationData']),
            'has_ucafCollectionIndicator' => !empty($authInfo['ucafCollectionIndicator']),
            'ucafAuthenticationData' => $authInfo['ucafAuthenticationData'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A'),
            'ucafCollectionIndicator' => $authInfo['ucafCollectionIndicator'] ?? (($isMastercard) ? 'MISSING (Mastercard requires UCAF)' : 'N/A')
        ]);
        
        Log::info('🚀 DEBUG PASO 5: Authorization Request', [
            'url' => $url,
            'payment_instrument_id' => $paymentInstrumentId,
            'payload' => json_decode($payload, true)
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        $success = $response['http_code'] >= 200 && $response['http_code'] < 300;
        $responseData = json_decode($response['body'], true);
        
        // Si fue exitoso, guardar en la base de datos
        $payment = null;
        if ($success && $responseData) {
            try {
                $payment = \App\Models\Payment::create([
                    'user_id' => auth()->id(),
                    'amount' => $data['amount'],
                    'currency' => $data['currency'] ?? 'USD',
                    'status' => ($responseData['status'] ?? '') === 'AUTHORIZED' ? 'completed' : 'failed',
                    'transaction_id' => $responseData['id'] ?? null,
                    'authorization_code' => $responseData['processorInformation']['approvalCode'] ?? null,
                    'flow_type' => 'frictionless',
                    'liability_shift' => true,
                    'card_last_four' => substr(str_replace(' ', '', $data['card_number']), -4),
                    'card_type' => $this->mapCardType($data['card_number']),
                    'threeds_version' => '2.2.0',
                    'threeds_eci' => $enrollmentData['consumerAuthenticationInformation']['eciRaw'] ?? null,
                    'threeds_cavv' => $enrollmentData['consumerAuthenticationInformation']['cavv'] ?? null,
                    'threeds_xid' => $enrollmentData['consumerAuthenticationInformation']['xid'] ?? null,
                    'threeds_authentication_status' => $enrollmentData['status'] ?? null,
                    'enrollment_data' => $enrollmentData,
                    'metadata' => json_encode([
                        'enrollment' => $enrollmentData,
                        'authorization' => $responseData,
                        'payment_instrument_id' => $paymentInstrumentId
                    ]),
                    'processed_at' => now(),
                ]);
                
                Log::info('💾 Payment saved to database', ['payment_id' => $payment->id]);
            } catch (\Exception $e) {
                Log::error('Failed to save payment to database', ['error' => $e->getMessage()]);
            }
        }
        
        return [
            'step' => 'PASO 5: Authorization (Frictionless Y,Y)',
            'description' => 'Autorización final con los datos de autenticación 3DS. Se captura el pago si está configurado.',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => $responseData,
            'success' => $success,
            'payment_id' => $payment ? $payment->id : null,
            'saved_to_db' => $payment !== null
        ];
    }

    /**
     * DEBUG: Validation Service (PASO 5.5A - Solo para Challenge Y,C)
     * Según documentación CyberSource: Validation es SOLO para step-up authentication
     */
    public function debugValidationService(string $paymentInstrumentId, string $authenticationTransactionId): array
    {
        $url = "{$this->baseUrl}/risk/v1/authentication-results";
        
        $payload = json_encode([
            'paymentInformation' => [
                'customer' => [
                    'customerId' => $paymentInstrumentId
                ]
            ],
            'consumerAuthenticationInformation' => [
                'authenticationTransactionId' => $authenticationTransactionId
            ]
        ]);
        
        Log::info('🔍 DEBUG PASO 5.5A: Validation Service Request', [
            'url' => $url,
            'payment_instrument_id' => $paymentInstrumentId,
            'authentication_transaction_id' => $authenticationTransactionId
        ]);
        
        $response = $this->makeRequest('POST', $url, $payload);
        
        $success = $response['http_code'] >= 200 && $response['http_code'] < 300;
        $responseData = json_decode($response['body'], true);
        
        // Log del resultado
        if ($success && $responseData) {
            $authInfoValidate = $responseData['consumerAuthenticationInformation'] ?? [];
            Log::info('✅ DEBUG PASO 5.5A: Validation Successful', [
                'status' => $responseData['status'] ?? '',
                'paresStatus' => $authInfoValidate['paresStatus'] ?? '',
                'eciRaw' => $authInfoValidate['eciRaw'] ?? '',
                // ✅ Mastercard UCAF: Verificar si el Validation devuelve UCAF
                'has_ucafAuthenticationData' => !empty($authInfoValidate['ucafAuthenticationData']),
                'has_ucafCollectionIndicator' => !empty($authInfoValidate['ucafCollectionIndicator']),
                'ucafAuthenticationData' => $authInfoValidate['ucafAuthenticationData'] ?? 'NOT_IN_VALIDATION',
                'ucafCollectionIndicator' => $authInfoValidate['ucafCollectionIndicator'] ?? 'NOT_IN_VALIDATION'
            ]);
        } else {
            Log::error('❌ DEBUG PASO 5.5A: Validation Failed', [
                'http_code' => $response['http_code'],
                'response' => $response['body']
            ]);
        }
        
        return [
            'step' => 'PASO 5.5A: Validation Service (Challenge Y,C)',
            'description' => 'Validación de la autenticación del challenge. Solo para step-up authentication.',
            'url' => $url,
            'http_code' => $response['http_code'],
            'request' => json_decode($payload, true),
            'response' => $responseData,
            'success' => $success
        ];
    }
}
