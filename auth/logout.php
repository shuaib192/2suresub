<?php
/**
 * 2SureSub - Logout
 */

require_once __DIR__ . '/../includes/functions.php';

if (isLoggedIn()) {
    logActivity('logout', 'User logged out', 'auth');
    
    // Clear session
    session_unset();
    session_destroy();
}

// Redirect to login
header("Location: " . APP_URL . "/auth/login.php");
exit;
