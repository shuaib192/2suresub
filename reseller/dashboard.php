<?php
/**
 * 2SureSub - Reseller Dashboard (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_RESELLER);

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$todayTotal = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM transactions WHERE user_id = ? AND DATE(created_at) = CURDATE() AND status = 'completed'", [$user['id']])['t'];
$monthTotal = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM transactions WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND status = 'completed'", [$user['id']])['t'];
$totalCommissions = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM commissions WHERE user_id = ?", [$user['id']])['t'];
$downlineCount = dbFetchOne("SELECT COUNT(*) as c FROM users WHERE referred_by = ?", [$user['id']])['c'];
$recentTransactions = dbFetchAll("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$user['id']]);

$pageTitle = 'Reseller Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Reseller Dashboard</h1>
                <p class="text-gray-500 text-sm">Welcome, <?php echo $user['first_name']; ?>!</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-1.5 bg-green-100 text-green-700 rounded-full text-sm">
                <i class="fas fa-star"></i><span class="font-medium">Reseller</span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6">
            <div class="bg-gradient-primary rounded-xl lg:rounded-2xl p-4 lg:p-6 text-white">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-wallet"></i><span class="text-white/80 text-xs">Balance</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($wallet['balance']); ?></p>
                <a href="<?php echo APP_URL; ?>/user/fund-wallet.php" class="inline-block mt-2 text-xs bg-white/20 px-3 py-1 rounded-lg">+ Fund</a>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-calendar-day text-green-500"></i><span class="text-gray-500 text-xs">Today</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($todayTotal); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-coins text-yellow-500"></i><span class="text-gray-500 text-xs">Commissions</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($totalCommissions); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-user-plus text-purple-500"></i><span class="text-gray-500 text-xs">Downlines</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($downlineCount); ?></p>
            </div>
        </div>
        
        <!-- Referral Code -->
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 rounded-2xl p-4 lg:p-6 text-white mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="font-semibold mb-1 text-sm">Your Referral Code</h3>
                    <p class="text-white/80 text-xs mb-2">Share to earn commissions</p>
                    <p class="text-xl lg:text-2xl font-mono font-bold"><?php echo $user['referral_code']; ?></p>
                </div>
                <button onclick="navigator.clipboard.writeText('<?php echo $user['referral_code']; ?>'); this.innerHTML='<i class=\'fas fa-check mr-2\'></i>Copied!'" class="px-5 py-2.5 bg-white text-purple-600 font-semibold rounded-xl hover:bg-gray-100">
                    <i class="fas fa-copy mr-2"></i>Copy
                </button>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 lg:gap-3 mb-6">
            <a href="<?php echo APP_URL; ?>/user/buy-data.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-wifi text-blue-500 text-lg mb-1"></i><p class="text-xs font-medium">Data</p>
            </a>
            <a href="<?php echo APP_URL; ?>/user/buy-airtime.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-phone text-green-500 text-lg mb-1"></i><p class="text-xs font-medium">Airtime</p>
            </a>
            <a href="<?php echo APP_URL; ?>/user/buy-cable.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-tv text-purple-500 text-lg mb-1"></i><p class="text-xs font-medium">Cable</p>
            </a>
            <a href="<?php echo APP_URL; ?>/user/buy-electricity.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-bolt text-yellow-500 text-lg mb-1"></i><p class="text-xs font-medium">Electric</p>
            </a>
            <a href="<?php echo APP_URL; ?>/user/exam-pins.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-graduation-cap text-red-500 text-lg mb-1"></i><p class="text-xs font-medium">Exams</p>
            </a>
            <a href="downlines.php" class="bg-white p-3 rounded-xl border hover:shadow-md text-center">
                <i class="fas fa-users text-indigo-500 text-lg mb-1"></i><p class="text-xs font-medium">Downlines</p>
            </a>
        </div>
        
        <!-- Recent Transactions -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b flex justify-between items-center">
                <h2 class="font-semibold text-sm">Recent Transactions</h2>
                <a href="<?php echo APP_URL; ?>/user/transactions.php" class="text-primary-500 text-xs">View All</a>
            </div>
            <div class="divide-y">
                <?php foreach ($recentTransactions as $txn): ?>
                <div class="px-4 py-3 flex justify-between items-center">
                    <div>
                        <p class="font-medium text-sm capitalize"><?php echo $txn['type']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $txn['phone_number'] ?: $txn['reference']; ?></p>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-sm"><?php echo formatMoney($txn['amount']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo timeAgo($txn['created_at']); ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
