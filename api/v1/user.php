<?php
/**
 * 2SureSub - User API (v1)
 */
require_once __DIR__ . '/api.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    apiResponse(false, 'Unauthorized', null, 401);
}

$user = dbFetchOne("SELECT id, name, email, role, created_at FROM users WHERE id = ?", [$userId]);
$wallet = getUserWallet($userId);

if ($user) {
    $data = [
        'profile' => $user,
        'wallet' => [
            'balance' => (float)$wallet['balance'],
            'referral_balance' => (float)$wallet['referral_balance']
        ]
    ];
    apiResponse(true, 'User data retrieved', $data);
} else {
    apiResponse(false, 'User not found', null, 404);
}
