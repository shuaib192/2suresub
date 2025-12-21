<?php
/**
 * 2SureSub - Authentication Middleware
 */

require_once __DIR__ . '/functions.php';

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/auth/login.php', 'Please login to continue', 'warning');
    }
    
    // Check for email verification if enabled
    if (getSetting('email_verification', '0') === '1' && !isEmailVerified()) {
        $allowedPages = ['verify.php', 'logout.php'];
        $currentPage = basename($_SERVER['PHP_SELF']);
        
        if (!in_array($currentPage, $allowedPages)) {
            redirect(APP_URL . '/auth/verify.php');
        }
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        redirect(APP_URL . '/user/dashboard.php', 'Access denied', 'error');
    }
}

/**
 * Require minimum role level
 */
function requireMinRole($minRole) {
    requireLogin();
    if (!hasMinRole($minRole)) {
        redirect(APP_URL . '/user/dashboard.php', 'Access denied', 'error');
    }
}

/**
 * Redirect logged in users away from auth pages
 */
function redirectIfLoggedIn() {
    if (isLoggedIn()) {
        $user = getCurrentUser();
        $dashboardUrl = getDashboardUrl($user['role']);
        redirect($dashboardUrl);
    }
}

/**
 * Get dashboard URL based on role
 */
function getDashboardUrl($role) {
    switch ($role) {
        case ROLE_SUPERADMIN:
            return APP_URL . '/superadmin/dashboard.php';
        case ROLE_ADMIN:
            return APP_URL . '/admin/dashboard.php';
        case ROLE_RESELLER:
            return APP_URL . '/reseller/dashboard.php';
        default:
            return APP_URL . '/user/dashboard.php';
    }
}
