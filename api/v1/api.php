<?php
/**
 * 2SureSub - API Core Handler (v1)
 * Provides JSON response formatting and basic routing
 */
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

// Global API response helper
function apiResponse($status, $message = '', $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status ? 'success' : 'error',
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

// Simple API Key Validation
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_GET['api_key'] ?? '';
$siteApiKey = getSetting('api_access_key');

// If no API key is set in settings yet, generate one for security
if (!$siteApiKey) {
    $siteApiKey = bin2hex(random_bytes(16));
    updateSetting('api_access_key', $siteApiKey);
}

if ($apiKey !== $siteApiKey) {
    // Check if user is already logged in via session (for local JS calls)
    if (!isset($_SESSION['user_id'])) {
        apiResponse(false, 'Unauthorized: Invalid or missing API key', null, 401);
    }
}

// Helper to get raw JSON input
function getApiInput() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}
