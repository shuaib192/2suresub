<?php
/**
 * 2SureSub - Helper Functions
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Format currency
 */
function formatMoney($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Generate unique reference
 */
function generateReference($prefix = 'TXN') {
    return $prefix . strtoupper(uniqid()) . rand(100, 999);
}

/**
 * Generate referral code
 */
function generateReferralCode($length = 8) {
    return strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, $length));
}

/**
 * Clean input data
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (Nigerian format)
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 14;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get logged in user
 */
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return dbFetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

/**
 * Get user's wallet
 */
function getUserWallet($userId) {
    return dbFetchOne("SELECT * FROM wallets WHERE user_id = ?", [$userId]);
}

/**
 * Check user role
 */
function hasRole($role) {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

/**
 * Check if user email is verified
 */
function isEmailVerified() {
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    return $user && ($user['email_verified_at'] !== null || getSetting('email_verification', '0') === '0');
}

/**
 * Check if user has minimum role level
 */
function hasMinRole($minRole) {
    $roles = [ROLE_USER => 1, ROLE_RESELLER => 2, ROLE_ADMIN => 3, ROLE_SUPERADMIN => 4];
    if (!isLoggedIn()) return false;
    $user = getCurrentUser();
    if (!$user) return false;
    return isset($roles[$user['role']]) && $roles[$user['role']] >= $roles[$minRole];
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    header("Location: $url");
    exit;
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $msg = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $msg;
    }
    return null;
}

/**
 * Log activity
 */
function logActivity($action, $description, $module, $oldData = null, $newData = null) {
    $userId = $_SESSION['user_id'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $sql = "INSERT INTO activity_logs (user_id, action, description, module, ip_address, user_agent, old_data, new_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    dbInsert($sql, [
        $userId,
        $action,
        $description,
        $module,
        $ip,
        $userAgent,
        $oldData ? json_encode($oldData) : null,
        $newData ? json_encode($newData) : null
    ]);
}

/**
 * Get site setting
 */
function getSetting($key, $default = null) {
    $setting = dbFetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    return $setting ? $setting['setting_value'] : $default;
}

/**
 * Update site setting
 */
function updateSetting($key, $value) {
    return dbExecute("UPDATE site_settings SET setting_value = ? WHERE setting_key = ?", [$value, $key]);
}

/**
 * Get API setting
 */
function getApiSetting($providerName) {
    return dbFetchOne("SELECT * FROM api_settings WHERE provider_name = ? AND is_active = 1", [$providerName]);
}

/**
 * Get price based on user role
 */
function getPrice($basePrice, $resellerPrice, $userRole) {
    return ($userRole === ROLE_RESELLER || $userRole === ROLE_ADMIN || $userRole === ROLE_SUPERADMIN) 
        ? $resellerPrice 
        : $basePrice;
}

/**
 * Deduct from wallet
 */
function deductWallet($userId, $amount, $description, $reference) {
    $wallet = getUserWallet($userId);
    if (!$wallet || $wallet['balance'] < $amount) {
        return false;
    }
    
    $newBalance = $wallet['balance'] - $amount;
    
    // Update wallet
    dbExecute("UPDATE wallets SET balance = ?, total_spent = total_spent + ? WHERE user_id = ?", 
        [$newBalance, $amount, $userId]);
    
    // Log wallet transaction
    dbInsert("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) 
              VALUES (?, 'debit', ?, ?, ?, ?, ?, 'completed')",
        [$userId, $amount, $wallet['balance'], $newBalance, $description, $reference]);
    
    return true;
}

/**
 * Credit wallet
 */
function creditWallet($userId, $amount, $description, $reference) {
    $wallet = getUserWallet($userId);
    $balanceBefore = $wallet ? $wallet['balance'] : 0;
    $newBalance = $balanceBefore + $amount;
    
    if ($wallet) {
        dbExecute("UPDATE wallets SET balance = ?, total_funded = total_funded + ? WHERE user_id = ?", 
            [$newBalance, $amount, $userId]);
    } else {
        dbInsert("INSERT INTO wallets (user_id, balance, total_funded) VALUES (?, ?, ?)",
            [$userId, $amount, $amount]);
    }
    
    // Log wallet transaction
    dbInsert("INSERT INTO wallet_transactions (user_id, type, amount, balance_before, balance_after, description, reference, status) 
              VALUES (?, 'credit', ?, ?, ?, ?, ?, 'completed')",
        [$userId, $amount, $balanceBefore, $newBalance, $description, $reference]);
    
    return true;
}

/**
 * Create notification
 */
function createNotification($userId, $title, $message, $type = 'info', $link = null) {
    return dbInsert("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?, ?, ?, ?, ?)",
        [$userId, $title, $message, $type, $link]);
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    $result = dbFetchOne("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0", [$userId]);
    return $result ? $result['count'] : 0;
}

/**
 * Time ago format
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    
    return date('M j, Y', $time);
}

/**
 * CSRF Token generation
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Output CSRF field
 */
function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . generateCsrfToken() . '">';
}
