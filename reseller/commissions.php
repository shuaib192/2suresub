<?php
/**
 * 2SureSub - Reseller Commissions
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_RESELLER);

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);

$commissions = dbFetchAll("SELECT c.*, u.first_name, u.last_name, u.username FROM commissions c LEFT JOIN users u ON c.from_user_id = u.id WHERE c.user_id = ? ORDER BY c.created_at DESC LIMIT 50", [$user['id']]);
$totalEarned = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM commissions WHERE user_id = ?", [$user['id']])['t'];
$thisMonthEarned = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM commissions WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE())", [$user['id']])['t'];

$pageTitle = 'Commissions';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Commissions</h1>
        <p class="text-gray-500 text-sm">Earnings from your downlines</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 gap-4 mb-6">
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 rounded-xl p-4 text-white">
                <div class="flex items-center gap-2 mb-1"><i class="fas fa-coins"></i><span class="text-white/80 text-xs">Total Earned</span></div>
                <p class="text-2xl font-bold"><?php echo formatMoney($totalEarned); ?></p>
            </div>
            <div class="bg-white rounded-xl p-4 border shadow-sm">
                <div class="flex items-center gap-2 mb-1"><i class="fas fa-calendar text-green-500"></i><span class="text-gray-500 text-xs">This Month</span></div>
                <p class="text-2xl font-bold"><?php echo formatMoney($thisMonthEarned); ?></p>
            </div>
        </div>
        
        <!-- Commission Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <div>
                    <h3 class="font-semibold text-blue-800 text-sm">How it works</h3>
                    <p class="text-xs text-blue-700">Earn commissions when your downlines make purchases. Referral bonus: <?php echo formatMoney(getSetting('referral_bonus', 100)); ?> per signup.</p>
                </div>
            </div>
        </div>
        
        <!-- Commission History -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b"><h2 class="font-semibold text-sm">Commission History</h2></div>
            <?php if (empty($commissions)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-coins text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No commissions yet</p>
            </div>
            <?php else: ?>
            <div class="divide-y">
                <?php foreach ($commissions as $c): ?>
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-coins text-green-500"></i>
                        </div>
                        <div>
                            <p class="font-medium text-sm"><?php echo $c['description'] ?: 'Referral Commission'; ?></p>
                            <p class="text-xs text-gray-500">From: <?php echo $c['first_name'] ? $c['first_name'] . ' ' . $c['last_name'] : 'User'; ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-bold text-green-600">+<?php echo formatMoney($c['amount']); ?></p>
                        <p class="text-xs text-gray-400"><?php echo timeAgo($c['created_at']); ?></p>
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
