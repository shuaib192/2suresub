<?php
/**
 * 2SureSub - Admin Dashboard (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_ADMIN);

$user = getCurrentUser();
$totalUsers = dbFetchOne("SELECT COUNT(*) as c FROM users WHERE role IN ('user', 'reseller')")['c'];
$pendingTickets = dbFetchOne("SELECT COUNT(*) as c FROM support_tickets WHERE status = 'open'")['c'];
$todayTransactions = dbFetchOne("SELECT COUNT(*) as c FROM transactions WHERE DATE(created_at) = CURDATE()")['c'];
$todayRevenue = dbFetchOne("SELECT COALESCE(SUM(amount), 0) as t FROM transactions WHERE status = 'completed' AND DATE(created_at) = CURDATE()")['t'];
$recentTransactions = dbFetchAll("SELECT t.*, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Admin Dashboard</h1>
        <p class="text-gray-500 text-sm">Manage users and transactions</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Stats -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 lg:gap-6 mb-6">
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-users text-primary-500"></i><span class="text-gray-500 text-xs">Users</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($totalUsers); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-ticket-alt text-yellow-500"></i><span class="text-gray-500 text-xs">Tickets</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($pendingTickets); ?></p>
            </div>
            <div class="bg-white rounded-xl lg:rounded-2xl p-4 lg:p-6 border shadow-sm">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-receipt text-green-500"></i><span class="text-gray-500 text-xs">Txns</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo number_format($todayTransactions); ?></p>
            </div>
            <div class="bg-gradient-primary rounded-xl lg:rounded-2xl p-4 lg:p-6 text-white">
                <div class="flex items-center gap-2 mb-2"><i class="fas fa-naira-sign"></i><span class="text-white/80 text-xs">Today</span></div>
                <p class="text-xl lg:text-3xl font-bold"><?php echo formatMoney($todayRevenue); ?></p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="grid grid-cols-4 gap-2 lg:gap-4 mb-6">
            <a href="users.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-users text-primary-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Users</p>
            </a>
            <a href="transactions.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-list text-green-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Txns</p>
            </a>
            <a href="tickets.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-headset text-purple-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Support</p>
            </a>
            <a href="reports.php" class="bg-white p-3 lg:p-4 rounded-xl border hover:shadow-lg transition-all text-center">
                <i class="fas fa-chart-bar text-orange-500 text-lg lg:text-2xl mb-1 lg:mb-2"></i>
                <p class="font-medium text-xs lg:text-sm">Reports</p>
            </a>
        </div>
        
        <!-- Recent Transactions -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b"><h2 class="font-semibold text-sm">Recent Transactions</h2></div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs">User</th>
                            <th class="px-4 py-3 text-left text-xs">Type</th>
                            <th class="px-4 py-3 text-left text-xs">Amount</th>
                            <th class="px-4 py-3 text-left text-xs">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach (array_slice($recentTransactions, 0, 8) as $txn): ?>
                        <tr>
                            <td class="px-4 py-3 text-sm"><?php echo $txn['username']; ?></td>
                            <td class="px-4 py-3 text-sm capitalize"><?php echo $txn['type']; ?></td>
                            <td class="px-4 py-3 text-sm font-medium"><?php echo formatMoney($txn['amount']); ?></td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded-full <?php echo $txn['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700'; ?>"><?php echo ucfirst($txn['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
