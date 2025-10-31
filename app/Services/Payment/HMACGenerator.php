<?php

namespace App\Services\Payment;

use Illuminate\Support\Facades\Log;

class HMACGenerator
{
    /**
     * Generate HMAC SHA-256 signature for CyberSource
     *
     * @param string $merchantId Merchant ID
     * @param string $apiSecret API secret (base64 encoded)
     * @param string $date Date string in RFC 7231 format
     * @param string $requestTarget Request target (e.g., "post /tms/v1/payments")
     * @param string $digest Digest of the request body
     * @return string Base64 encoded HMAC signature
     */
    public function generateSignature(
        string $merchantId,
        string $apiSecret,
        string $date,
        string $requestTarget,
        string $digest
    ): string {
        $host = 'apitest.cybersource.com';
        $signingString = "host: $host\nv-c-date: $date\nrequest-target: $requestTarget\ndigest: $digest\nv-c-merchant-id: $merchantId";
        
        try {
            $signature = base64_encode(
                hash_hmac('sha256', $signingString, base64_decode($apiSecret), true)
            );
            
            return $signature;
        } catch (\Exception $e) {
            Log::error('HMAC signature generation failed: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate SHA-256 digest of request payload
     *
     * @param string $payload Request body
     * @return string Digest in format "SHA-256=<base64_encoded_hash>"
     */
    public function generateDigest(string $payload): string
    {
        return 'SHA-256=' . base64_encode(hash('sha256', $payload, true));
    }
    
    /**
     * Generate complete signature header for CyberSource API
     *
     * @param string $merchantId Merchant ID
     * @param string $apiKey API Key
     * @param string $apiSecret API Secret
     * @param string $date Date string
     * @param string $requestTarget Request target
     * @param string $payload Request body
     * @return string Complete signature header
     */
    public function generateSignatureHeader(
        string $merchantId,
        string $apiKey,
        string $apiSecret,
        string $date,
        string $requestTarget,
        string $payload
    ): string {
        $digest = $this->generateDigest($payload);
        $signature = $this->generateSignature($merchantId, $apiSecret, $date, $requestTarget, $digest);
        
        return sprintf(
            'keyid="%s", algorithm="HmacSHA256", headers="host v-c-date request-target digest v-c-merchant-id", signature="%s"',
            $apiKey,
            $signature
        );
    }
}

