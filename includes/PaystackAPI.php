<?php
/**
 * 2SureSub - Paystack API Integration
 * 
 * For wallet funding via card payments
 * Documentation: https://paystack.com/docs/api/
 */

class PaystackAPI {
    private $secretKey;
    private $publicKey;
    private $baseUrl = 'https://api.paystack.co';
    
    public function __construct($secretKey = null, $publicKey = null) {
        if ($secretKey) {
            $this->secretKey = $secretKey;
            $this->publicKey = $publicKey;
        } else {
            // Get from database
            $apiSettings = dbFetchOne("SELECT * FROM api_settings WHERE provider_name = 'PayStack' AND is_active = 1");
            $this->secretKey = $apiSettings['secret_key'] ?? '';
            $this->publicKey = $apiSettings['api_key'] ?? '';
        }
    }
    
    /**
     * Make API request
     */
    private function request($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->secretKey,
            'Content-Type: application/json',
            'Cache-Control: no-cache'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['status' => false, 'message' => 'Connection error: ' . $error];
        }
        
        return json_decode($response, true) ?: ['status' => false, 'message' => 'Invalid response'];
    }
    
    /**
     * Initialize a transaction
     * @param string $email - Customer email
     * @param int $amount - Amount in kobo (multiply Naira by 100)
     * @param string $reference - Unique transaction reference
     * @param string $callbackUrl - URL to redirect after payment
     */
    public function initializeTransaction($email, $amount, $reference, $callbackUrl) {
        return $this->request('/transaction/initialize', [
            'email' => $email,
            'amount' => $amount * 100, // Convert to kobo
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'currency' => 'NGN'
        ]);
    }
    
    /**
     * Verify a transaction
     * @param string $reference - Transaction reference
     */
    public function verifyTransaction($reference) {
        return $this->request('/transaction/verify/' . rawurlencode($reference), [], 'GET');
    }
    
    /**
     * Get public key for inline JS
     */
    public function getPublicKey() {
        return $this->publicKey;
    }
    
    /**
     * Check if API is configured
     */
    public function isConfigured() {
        return !empty($this->secretKey) && !empty($this->publicKey);
    }
}

/**
 * Helper function to get Paystack API instance
 */
function getPaystackAPI() {
    return new PaystackAPI();
}
