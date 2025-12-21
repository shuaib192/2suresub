<?php
/**
 * 2SureSub - Superadmin Dashboard (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$user = getCurrentUser();
$totalUsers = dbFetchOne("SELECT COUNT(*) as c FROM users")['c'];
$totalTransactions = dbFetchOne("SELECT COUNT(*) as c FROM transactions")['c'];
$totalRevenue = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM transactions WHERE status = 'completed'")['t'];
$todayRevenue = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM transactions WHERE status = 'completed' AND DATE(created_at) = CURDATE()")['t'];
$recentTransactions = dbFetchAll("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
$recentUsers = dbFetchAll("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

$pageTitle = 'Superadmin Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Superadmin Dashboard</h1>
        <p class="text-gray-500 text-sm">System overview and management</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6">
            <div class="bg-gradient-primary rounded-xl lg:rounded-2xl p-4 lg:p-6 text-white">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-users"></i><span class="text-white/80 text-xs">Users</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($totalUsers); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-receipt text-green-500"></i><span class="text-gray-500 text-xs">Transactions</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($totalTransactions); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-chart-line text-purple-500"></i><span class="text-gray-500 text-xs">Revenue</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($totalRevenue); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-calendar-day text-orange-500"></i><span class="text-gray-500 text-xs">Today</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($todayRevenue); ?></p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-4 gap-2 lg:gap-4 mb-6">
            <a href="api-settings.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-plug text-primary-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">API</p>
            </a>
            <a href="site-settings.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-cog text-gray-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Settings</p>
            </a>
            <a href="pricing.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-tags text-green-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Pricing</p>
            </a>
            <a href="activity-logs.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-clipboard-list text-purple-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Logs</p>
            </a>
        </div>
        
        <div class="grid lg:grid-cols-2 gap-4 lg:gap-6">
            <!-- Recent Transactions -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b"><h2 class="font-semibold text-sm">Recent Transactions</h2></div>
                <div class="divide-y">
                    <?php foreach (array_slice($recentTransactions, 0, 5) as $txn): ?>
                    <div class="px-4 py-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-sm"><?php echo $txn['username']; ?></p>
                            <p class="text-xs text-gray-500 capitalize"><?php echo $txn['type']; ?></p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-sm"><?php echo formatMoney($txn['amount']); ?></p>
                            <p class="text-xs text-gray-500"><?php echo timeAgo($txn['created_at']); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Recent Users -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b"><h2 class="font-semibold text-sm">New Users</h2></div>
                <div class="divide-y">
                    <?php foreach ($recentUsers as $u): ?>
                    <div class="px-4 py-3 flex justify-between items-center">
                        <div>
                            <p class="font-medium text-sm"><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $u['email']; ?></p>
                        </div>
                        <span class="px-2 py-1 bg-primary-100 text-primary-700 text-xs rounded-full capitalize"><?php echo $u['role']; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
