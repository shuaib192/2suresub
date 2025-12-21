<?php
/**
 * 2SureSub - Application Constants
 */

// Application Info
define('APP_NAME', '2SureSub');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/2suresubs');

// Session Config
define('SESSION_LIFETIME', 86400); // 24 hours

// Pagination
define('ITEMS_PER_PAGE', 20);

// File Upload
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);

// User Roles
define('ROLE_USER', 'user');
define('ROLE_RESELLER', 'reseller');
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPERADMIN', 'superadmin');

// Transaction Types
define('TXN_DATA', 'data');
define('TXN_AIRTIME', 'airtime');
define('TXN_CABLE', 'cable');
define('TXN_ELECTRICITY', 'electricity');
define('TXN_EXAM', 'exam');
define('TXN_FUNDING', 'funding');
define('TXN_TRANSFER', 'transfer');
define('TXN_COMMISSION', 'commission');

// Transaction Status
define('STATUS_PENDING', 'pending');
define('STATUS_PROCESSING', 'processing');
define('STATUS_COMPLETED', 'completed');
define('STATUS_FAILED', 'failed');
define('STATUS_REFUNDED', 'refunded');

// Currency
define('CURRENCY_SYMBOL', '₦');
define('CURRENCY_CODE', 'NGN');
