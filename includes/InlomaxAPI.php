<?php
/**
 * 2SureSub - Inlomax API Integration
 * 
 * API Documentation: https://inlomax.com/docs/
 * 
 * Endpoints:
 * - GET  /api/services     - Get all services and pricing
 * - POST /api/data         - Buy data
 * - POST /api/airtime      - Buy airtime
 * - POST /api/validatecable - Verify IUC number
 * - POST /api/subcable     - Subscribe cable
 * - POST /api/validatemeter - Verify meter number
 * - POST /api/payelectric  - Pay electricity
 * - POST /api/education    - Buy exam pins
 */

class InlomaxAPI {
    private $apiKey;
    private $baseUrl = 'https://inlomax.com/api';
    
    public function __construct($apiKey = null) {
        if ($apiKey) {
            $this->apiKey = $apiKey;
        } else {
            // Get from database
            $apiSettings = dbFetchOne("SELECT * FROM api_settings WHERE provider_name = 'Inlomax' AND is_active = 1");
            $this->apiKey = $apiSettings['api_key'] ?? '';
        }
    }
    
    /**
     * Make API request
     */
    private function request($endpoint, $data = [], $method = 'POST') {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Token ' . $this->apiKey,
            'Content-Type: application/json'
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
            return ['status' => 'failed', 'message' => 'Connection error: ' . $error];
        }
        
        $result = json_decode($response, true);
        
        if (!$result) {
            return ['status' => 'failed', 'message' => 'Invalid API response'];
        }
        
        return $result;
    }
    
    /**
     * Get all services and pricing
     */
    public function getServices() {
        return $this->request('/services', [], 'GET');
    }
    
    /**
     * Buy Data
     * @param int $serviceID - Data plan service ID from getServices()
     * @param string $mobileNumber - Phone number to receive data
     */
    public function buyData($serviceID, $mobileNumber) {
        return $this->request('/data', [
            'serviceID' => (string)$serviceID,
            'mobileNumber' => $mobileNumber
        ]);
    }
    
    /**
     * Buy Airtime
     * @param int $serviceID - Network service ID (1=MTN, 2=Airtel, 3=Glo, 4=9Mobile)
     * @param int $amount - Airtime amount
     * @param string $mobileNumber - Phone number to receive airtime
     */
    public function buyAirtime($serviceID, $amount, $mobileNumber) {
        return $this->request('/airtime', [
            'serviceID' => (string)$serviceID,
            'amount' => (int)$amount,
            'mobileNumber' => $mobileNumber
        ]);
    }
    
    /**
     * Verify Cable IUC Number
     * @param int $serviceID - Cable plan service ID
     * @param string $iucNum - IUC/Smart card number
     */
    public function validateCable($serviceID, $iucNum) {
        return $this->request('/validatecable', [
            'serviceID' => (string)$serviceID,
            'iucNum' => $iucNum
        ]);
    }
    
    /**
     * Subscribe Cable TV
     * @param int $serviceID - Cable plan service ID
     * @param string $iucNum - IUC/Smart card number
     */
    public function subscribeCable($serviceID, $iucNum) {
        return $this->request('/subcable', [
            'serviceID' => (string)$serviceID,
            'iucNum' => $iucNum
        ]);
    }
    
    /**
     * Verify Electricity Meter
     * @param int $serviceID - Disco service ID
     * @param string $meterNum - Meter number
     * @param int $meterType - 1=prepaid, 2=postpaid
     */
    public function validateMeter($serviceID, $meterNum, $meterType = 1) {
        return $this->request('/validatemeter', [
            'serviceID' => (string)$serviceID,
            'meterNum' => $meterNum,
            'meterType' => (int)$meterType
        ]);
    }
    
    /**
     * Pay Electricity Bill
     * @param int $serviceID - Disco service ID
     * @param string $meterNum - Meter number
     * @param int $meterType - 1=prepaid, 2=postpaid
     * @param int $amount - Amount to pay
     */
    public function payElectricity($serviceID, $meterNum, $meterType, $amount) {
        return $this->request('/payelectric', [
            'serviceID' => (string)$serviceID,
            'meterNum' => $meterNum,
            'meterType' => (int)$meterType,
            'amount' => (int)$amount
        ]);
    }
    
    /**
     * Buy Education/Exam Pins
     * @param int $serviceID - Exam type service ID
     * @param int $quantity - Number of pins to buy
     */
    public function buyEducation($serviceID, $quantity = 1) {
        return $this->request('/education', [
            'serviceID' => (string)$serviceID,
            'quantity' => (int)$quantity
        ]);
    }
    
    /**
     * Check if API is configured
     */
    public function isConfigured() {
        return !empty($this->apiKey);
    }
    
    /**
     * Get wallet balance
     */
    public function getBalance() {
        return $this->request('/balance', [], 'GET');
    }
    
    /**
     * Get transaction status
     * @param string $reference - Transaction reference
     */
    public function getTransaction($reference) {
        return $this->request('/transaction', [
            'reference' => $reference
        ]);
    }
}

/**
 * Helper function to get Inlomax API instance
 */
function getInlomaxAPI() {
    return new InlomaxAPI();
}
