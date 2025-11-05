<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CyberSource Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for CyberSource payment gateway integration.
    | Set these values in your .env file.
    |
    */

    'merchant_id' => env('CYBERSOURCE_MERCHANT_ID'),
    'api_key' => env('CYBERSOURCE_API_KEY'),
    'api_secret' => env('CYBERSOURCE_API_SECRET'),
    'base_url' => env('CYBERSOURCE_BASE_URL', 'https://apitest.cybersource.com'),
    
    /*
    |--------------------------------------------------------------------------
    | URLs
    |--------------------------------------------------------------------------
    |
    | Return URLs for payment callbacks and challenges.
    |
    */

    'challenge_return_url' => env('CYBERSOURCE_CHALLENGE_RETURN_URL', env('APP_URL') . '/payment/challenge/callback'),
    'success_url' => env('CYBERSOURCE_SUCCESS_URL', env('APP_URL') . '/payment/success'),
    'failure_url' => env('CYBERSOURCE_FAILURE_URL', env('APP_URL') . '/payment/failed'),
    
    /*
    |--------------------------------------------------------------------------
    | 3D Secure Configuration
    |--------------------------------------------------------------------------
    |
    | 3D Secure 2.2.0 settings for enhanced security.
    |
    */

    'three_ds' => [
        'enabled' => env('CYBERSOURCE_3DS_ENABLED', true),
        'version' => env('CYBERSOURCE_3DS_VERSION', '2.2.0'),
        'device_channel' => 'browser',
        'authentication_type' => '02', // CHALLENGE
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Payment Settings
    |--------------------------------------------------------------------------
    |
    | Default payment configuration.
    |
    */

    'default_currency' => env('CYBERSOURCE_DEFAULT_CURRENCY', 'USD'),
    'allowed_currencies' => [
        'USD',
        'CRC',
    ],
    'capture_on_authorization' => env('CYBERSOURCE_CAPTURE_ON_AUTH', true),
    'request_timeout' => env('CYBERSOURCE_REQUEST_TIMEOUT', 30),
    
    /*
    |--------------------------------------------------------------------------
    | Card Types Mapping
    |--------------------------------------------------------------------------
    |
    | CyberSource expects string types, not numeric codes.
    | Valid values: 'visa', 'mastercard', 'amex', 'discover'
    |
    */

    'card_types' => [
        'visa' => 'visa',
        'mastercard' => 'mastercard',
        'amex' => 'amex',
        'discover' => 'discover',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging & Debug
    |--------------------------------------------------------------------------
    |
    | Enable logging for debugging and auditing.
    |
    */

    'log_requests' => env('CYBERSOURCE_LOG_REQUESTS', true),
    'log_responses' => env('CYBERSOURCE_LOG_RESPONSES', true),
    'log_level' => env('CYBERSOURCE_LOG_LEVEL', 'info'),
    
    /*
    |--------------------------------------------------------------------------
    | Device Fingerprinting Configuration
    |--------------------------------------------------------------------------
    |
    | Device fingerprinting settings for fraud detection with Decision Manager.
    | OrgID values:
    |   Test: 1snn5n9w or 45ssiuz3
    |   Production: k8vif92e or 9ozphlqx
    |
    */
    
    'device_fingerprinting' => [
        'enabled' => env('CYBERSOURCE_DEVICE_FINGERPRINT_ENABLED', true),
        'org_id' => env('CYBERSOURCE_DEVICE_FINGERPRINT_ORG_ID', '1snn5n9w'),
        'profiling_domain' => env('CYBERSOURCE_DEVICE_FINGERPRINT_DOMAIN', 'h.online-metrix.net'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Merchant Defined Data (MDD)
    |--------------------------------------------------------------------------
    |
    | Custom fields sent to CyberSource for reporting and analysis.
    | MDD5: Business name / Store name
    | MDD6: Sales channel (Web, Mobile, Call Center, etc.)
    |
    */
    
    'merchant_defined_data' => [
        'mdd5_business_name' => env('CYBERSOURCE_MDD5_BUSINESS_NAME', env('APP_NAME', 'Pasarela CyberSource')),
        'mdd6_sales_channel' => env('CYBERSOURCE_MDD6_SALES_CHANNEL', 'Web'),
    ],
];

