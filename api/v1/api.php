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

// API Authentication logic
$apiKey = $_SERVER['HTTP_X_API_KEY'] ?? $_REQUEST['api_key'] ?? '';
$authenticatedUser = null;

if ($apiKey) {
    // 1. Check for individual User API Key
    $sql = "SELECT id, first_name, last_name, email, role, status FROM users WHERE api_key = ? AND api_key IS NOT NULL AND status = 'active'";
    $authenticatedUser = dbFetchOne($sql, [$apiKey]);
    
    // 2. Fallback to System API Key (Backward compatibility)
    if (!$authenticatedUser) {
        $siteApiKey = getSetting('api_access_key');
        if (!$siteApiKey) {
            $siteApiKey = bin2hex(random_bytes(16));
            updateSetting('api_access_key', $siteApiKey);
        }
        
        if ($apiKey === $siteApiKey) {
            // If it's the site key, we don't have a specific user context unless they are in a session
            if (isset($_SESSION['user_id'])) {
                $authenticatedUser = dbFetchOne("SELECT id, first_name, last_name, email, role, status FROM users WHERE id = ?", [$_SESSION['user_id']]);
            }
        }
    }
}

// 3. Fallback to Session (for local frontend calls)
if (!$authenticatedUser && isset($_SESSION['user_id'])) {
    $authenticatedUser = dbFetchOne("SELECT id, first_name, last_name, email, role, status FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

if (!$authenticatedUser) {
    apiResponse(false, 'Unauthorized: Invalid or missing API key', null, 401);
}

// Global user context for all API endpoints
$user = $authenticatedUser;

// Helper to get raw JSON input
function getApiInput() {
    return json_decode(file_get_contents('php://input'), true) ?: [];
}
