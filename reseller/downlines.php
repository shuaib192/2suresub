<?php
/**
 * 2SureSub - Reseller Downlines
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_RESELLER);

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);

$downlines = dbFetchAll("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE u.referred_by = ? ORDER BY u.created_at DESC", [$user['id']]);
$totalDownlines = count($downlines);
$activeDownlines = count(array_filter($downlines, fn($d) => $d['status'] === 'active'));

$pageTitle = 'My Downlines';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">My Downlines</h1>
        <p class="text-gray-500 text-sm">Users registered with your referral code</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 border shadow-sm">
                <div class="flex items-center gap-2 mb-1"><i class="fas fa-users text-primary-500"></i><span class="text-gray-500 text-xs">Total</span></div>
                <p class="text-2xl font-bold"><?php echo $totalDownlines; ?></p>
            </div>
            <div class="bg-white rounded-xl p-4 border shadow-sm">
                <div class="flex items-center gap-2 mb-1"><i class="fas fa-user-check text-green-500"></i><span class="text-gray-500 text-xs">Active</span></div>
                <p class="text-2xl font-bold"><?php echo $activeDownlines; ?></p>
            </div>
        </div>
        
        <!-- Referral Code -->
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl p-4 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="text-white/80 text-sm">Your Referral Code</p>
                    <p class="text-xl font-mono font-bold"><?php echo $user['referral_code']; ?></p>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo $user['referral_code']; ?>'); this.innerHTML='<i class=\'fas fa-check mr-2\'></i>Copied!'" class="px-4 py-2 bg-white text-purple-600 font-semibold rounded-xl text-sm">
                    <i class="fas fa-copy mr-2"></i>Copy
                </button>
            </div>
        </div>
        
        <!-- Downlines List -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <?php if (empty($downlines)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-users text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No downlines yet</p>
                <p class="text-gray-400 text-xs mt-1">Share your referral code to start earning</p>
            </div>
            <?php else: ?>
            <div class="divide-y">
                <?php foreach ($downlines as $d): ?>
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="font-bold text-primary-600 text-sm"><?php echo strtoupper(substr($d['first_name'], 0, 1)); ?></span>
                        </div>
                        <div>
                            <p class="font-medium text-sm"><?php echo $d['first_name'] . ' ' . $d['last_name']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $d['email']; ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-block px-2 py-0.5 text-xs rounded-full <?php echo $d['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?php echo ucfirst($d['status']); ?></span>
                        <p class="text-xs text-gray-400 mt-1"><?php echo date('M j, Y', strtotime($d['created_at'])); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
