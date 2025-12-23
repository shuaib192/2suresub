<?php
/**
 * 2SureSub - User Dashboard (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$recentTransactions = dbFetchAll("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 5", [$user['id']]);
$todayTotal = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND DATE(created_at) = CURDATE() AND status = 'completed'", [$user['id']])['total'];
$monthTotal = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as total FROM transactions WHERE user_id = ? AND MONTH(created_at) = MONTH(CURDATE()) AND status = 'completed'", [$user['id']])['total'];
$totalTxns = dbFetchOne("SELECT COUNT(*) as count FROM transactions WHERE user_id = ?", [$user['id']])['count'];

$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500">Welcome back, <?php echo $user['first_name']; ?>!</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6">
            <div class="bg-gradient-primary rounded-xl lg:rounded-2xl p-4 lg:p-6 text-white relative overflow-hidden">
                <div class="absolute top-0 right-0 w-20 h-20 bg-white/10 rounded-full -translate-y-1/2 translate-x-1/2"></div>
                <div class="relative">
                    <div class="flex items-center gap-2 mb-2">
                        <i class="fas fa-wallet text-lg"></i>
                        <span class="text-white/80 text-xs">Balance</span>
                    </div>
                    <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($wallet['balance'] ?? 0); ?></p>
                    <a href="fund-wallet.php" class="inline-flex items-center gap-1 mt-2 text-xs bg-white/20 px-3 py-1 rounded-lg hover:bg-white/30">
                        <i class="fas fa-plus"></i> Fund
                    </a>
                </div>
            </div>
            
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-chart-line text-green-500"></i>
                    <span class="text-gray-500 text-xs">Today</span>
                </div>
                <p class="text-xl lg:text-3xl font-bold text-gray-900"><?php echo formatMoney($todayTotal); ?></p>
            </div>
            
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-calendar text-purple-500"></i>
                    <span class="text-gray-500 text-xs">This Month</span>
                </div>
                <p class="text-xl lg:text-3xl font-bold text-gray-900"><?php echo formatMoney($monthTotal); ?></p>
            </div>
            
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <i class="fas fa-receipt text-orange-500"></i>
                    <span class="text-gray-500 text-xs">Transactions</span>
                </div>
                <p class="text-xl lg:text-3xl font-bold text-gray-900"><?php echo number_format($totalTxns); ?></p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="mb-6">
            <h2 class="text-sm lg:text-lg font-semibold text-gray-900 mb-3">Quick Actions</h2>
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 lg:gap-4">
                <a href="buy-data.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-wifi text-blue-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Data</span>
                </a>
                <a href="buy-airtime.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-green-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-phone text-green-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Airtime</span>
                </a>
                <a href="buy-cable.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-tv text-purple-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Cable</span>
                </a>
                <a href="buy-electricity.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-yellow-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-bolt text-yellow-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Electric</span>
                </a>
                <a href="exam-pins.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-red-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-graduation-cap text-red-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Exams</span>
                </a>
                <a href="fund-wallet.php" class="group bg-white rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center">
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-credit-card text-indigo-500 text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-medium text-gray-700">Fund</span>
                </a>
                <a href="giveaways.php" class="group bg-blue-50 border-blue-100 rounded-xl p-3 lg:p-6 border shadow-sm hover:shadow-lg transition-all text-center relative overflow-hidden">
                    <div class="absolute -top-1 -right-1">
                        <span class="flex h-3 w-3">
                          <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                          <span class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"></span>
                        </span>
                    </div>
                    <div class="w-10 h-10 lg:w-14 lg:h-14 bg-blue-600 rounded-xl flex items-center justify-center mx-auto mb-2 group-hover:scale-110 transition-transform">
                        <i class="fas fa-gift text-white text-lg lg:text-2xl"></i>
                    </div>
                    <span class="text-xs lg:text-sm font-bold text-blue-700">Giveaway</span>
                </a>
            </div>
        </div>
        
        <!-- Recent Transactions -->
        <div class="bg-white rounded-xl lg:rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b flex items-center justify-between">
                <h2 class="font-semibold text-gray-900 text-sm lg:text-base">Recent Transactions</h2>
                <a href="transactions.php" class="text-primary-500 text-xs lg:text-sm">View All →</a>
            </div>
            
            <?php if (empty($recentTransactions)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-receipt text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No transactions yet</p>
            </div>
            <?php else: ?>
            <div class="divide-y">
                <?php foreach ($recentTransactions as $txn): ?>
                <div class="px-4 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center <?php
                            echo match($txn['type']) {
                                'data' => 'bg-blue-100 text-blue-500',
                                'airtime' => 'bg-green-100 text-green-500',
                                'cable' => 'bg-purple-100 text-purple-500',
                                'electricity' => 'bg-yellow-100 text-yellow-500',
                                'exam' => 'bg-red-100 text-red-500',
                                default => 'bg-gray-100 text-gray-500'
                            };
                        ?>">
                            <i class="fas <?php
                                echo match($txn['type']) {
                                    'data' => 'fa-wifi',
                                    'airtime' => 'fa-phone',
                                    'cable' => 'fa-tv',
                                    'electricity' => 'fa-bolt',
                                    'exam' => 'fa-graduation-cap',
                                    default => 'fa-receipt'
                                };
                            ?>"></i>
                        </div>
                        <div>
                            <p class="font-medium text-gray-900 text-sm capitalize"><?php echo $txn['type']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $txn['phone_number'] ?: $txn['reference']; ?></p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="font-semibold text-gray-900 text-sm"><?php echo formatMoney($txn['amount']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo timeAgo($txn['created_at']); ?></p>
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
