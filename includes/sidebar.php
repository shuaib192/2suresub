<?php
/**
 * 2SureSub - Dashboard Sidebar (Mobile Responsive)
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
$userRole = $user['role'] ?? 'user';
$wallet = getUserWallet($user['id']);

$menuItems = [
    'user' => [
        ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => APP_URL . '/user/dashboard.php', 'page' => 'dashboard.php'],
        ['icon' => 'fa-wifi', 'label' => 'Buy Data', 'url' => APP_URL . '/user/buy-data.php', 'page' => 'buy-data.php'],
        ['icon' => 'fa-phone', 'label' => 'Buy Airtime', 'url' => APP_URL . '/user/buy-airtime.php', 'page' => 'buy-airtime.php'],
        ['icon' => 'fa-tv', 'label' => 'Cable TV', 'url' => APP_URL . '/user/buy-cable.php', 'page' => 'buy-cable.php'],
        ['icon' => 'fa-bolt', 'label' => 'Electricity', 'url' => APP_URL . '/user/buy-electricity.php', 'page' => 'buy-electricity.php'],
        ['icon' => 'fa-graduation-cap', 'label' => 'Exam Pins', 'url' => APP_URL . '/user/exam-pins.php', 'page' => 'exam-pins.php'],
        ['icon' => 'fa-wallet', 'label' => 'Fund Wallet', 'url' => APP_URL . '/user/fund-wallet.php', 'page' => 'fund-wallet.php'],
        ['icon' => 'fa-clock-rotate-left', 'label' => 'Transactions', 'url' => APP_URL . '/user/transactions.php', 'page' => 'transactions.php'],
        ['icon' => 'fa-bell', 'label' => 'Notifications', 'url' => APP_URL . '/user/notifications.php', 'page' => 'notifications.php'],
        ['icon' => 'fa-headset', 'label' => 'Support', 'url' => APP_URL . '/user/support.php', 'page' => 'support.php'],
        ['icon' => 'fa-user', 'label' => 'Profile', 'url' => APP_URL . '/user/profile.php', 'page' => 'profile.php'],
    ],
    'reseller' => [
        ['icon' => 'fa-home', 'label' => 'Dashboard', 'url' => APP_URL . '/reseller/dashboard.php', 'page' => 'dashboard.php'],
        ['icon' => 'fa-users', 'label' => 'Downlines', 'url' => APP_URL . '/reseller/downlines.php', 'page' => 'downlines.php'],
        ['icon' => 'fa-coins', 'label' => 'Commissions', 'url' => APP_URL . '/reseller/commissions.php', 'page' => 'commissions.php'],
    ],
    'admin' => [
        ['icon' => 'fa-chart-line', 'label' => 'Dashboard', 'url' => APP_URL . '/admin/dashboard.php', 'page' => 'dashboard.php'],
        ['icon' => 'fa-users', 'label' => 'Users', 'url' => APP_URL . '/admin/users.php', 'page' => 'users.php'],
        ['icon' => 'fa-receipt', 'label' => 'Transactions', 'url' => APP_URL . '/admin/transactions.php', 'page' => 'transactions.php'],
        ['icon' => 'fa-headset', 'label' => 'Support', 'url' => APP_URL . '/admin/support.php', 'page' => 'support.php'],
    ],
    'superadmin' => [
        ['icon' => 'fa-gauge-high', 'label' => 'Dashboard', 'url' => APP_URL . '/superadmin/dashboard.php', 'page' => 'dashboard.php'],
        ['icon' => 'fa-plug', 'label' => 'API Settings', 'url' => APP_URL . '/superadmin/api-settings.php', 'page' => 'api-settings.php'],
        ['icon' => 'fa-cog', 'label' => 'Site Settings', 'url' => APP_URL . '/superadmin/site-settings.php', 'page' => 'site-settings.php'],
        ['icon' => 'fa-tags', 'label' => 'Pricing', 'url' => APP_URL . '/superadmin/pricing.php', 'page' => 'pricing.php'],
        ['icon' => 'fa-sync-alt', 'label' => 'Sync Prices', 'url' => APP_URL . '/superadmin/sync-prices.php', 'page' => 'sync-prices.php'],
        ['icon' => 'fa-clipboard-list', 'label' => 'Activity Logs', 'url' => APP_URL . '/superadmin/activity-logs.php', 'page' => 'activity-logs.php'],
        ['icon' => 'fa-users-cog', 'label' => 'Users', 'url' => APP_URL . '/superadmin/users.php', 'page' => 'users.php'],
    ]
];

function isActive($page, $currentPage) {
    return $page === $currentPage;
}
?>

<!-- Mobile Header -->
<div class="lg:hidden fixed top-0 left-0 right-0 z-40 bg-white border-b border-gray-100 px-4 py-3">
    <div class="flex items-center justify-between">
        <button id="mobile-menu-btn" class="p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg">
            <i class="fas fa-bars text-xl"></i>
        </button>
        <a href="<?php echo APP_URL; ?>" class="flex items-center gap-2">
            <div class="w-8 h-8 bg-gradient-primary rounded-lg flex items-center justify-center">
                <i class="fas fa-bolt text-white"></i>
            </div>
            <span class="font-bold text-gray-900"><?php echo getSetting('site_name', '2SureSub'); ?></span>
        </a>
        <div class="text-right">
            <p class="text-xs text-gray-500">Balance</p>
            <p class="text-sm font-bold text-primary-600"><?php echo formatMoney($wallet['balance'] ?? 0); ?></p>
        </div>
    </div>
</div>

<!-- Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-40 hidden lg:hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed left-0 top-0 h-full w-72 bg-white shadow-xl z-50 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 overflow-hidden">
    <!-- Close button mobile -->
    <button id="close-sidebar" class="lg:hidden absolute top-4 right-4 p-2 text-gray-500 hover:bg-gray-100 rounded-lg z-10">
        <i class="fas fa-times text-lg"></i>
    </button>
    
    <!-- Logo -->
    <div class="p-6 border-b border-gray-100">
        <a href="<?php echo APP_URL; ?>" class="flex items-center gap-3">
            <div class="w-12 h-12 bg-gradient-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                <i class="fas fa-bolt text-white text-2xl"></i>
            </div>
            <div>
                <span class="text-xl font-bold text-gray-900"><?php echo getSetting('site_name', '2SureSub'); ?></span>
                <p class="text-xs text-gray-500 capitalize"><?php echo $userRole; ?> Panel</p>
            </div>
        </a>
    </div>
    
    <!-- User Info -->
    <div class="p-4 mx-4 mt-4 bg-gradient-to-r from-primary-500 to-primary-600 rounded-2xl text-white">
        <div class="flex items-center gap-3 mb-3">
            <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center">
                <i class="fas fa-user"></i>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold truncate"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></p>
                <p class="text-xs text-white/80 truncate">@<?php echo $user['username']; ?></p>
            </div>
        </div>
        <div class="flex items-center justify-between bg-white/10 rounded-xl p-3">
            <div>
                <p class="text-xs text-white/80">Wallet Balance</p>
                <p class="text-lg font-bold"><?php echo formatMoney($wallet['balance'] ?? 0); ?></p>
            </div>
            <a href="<?php echo APP_URL; ?>/user/fund-wallet.php" class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center hover:bg-white/30">
                <i class="fas fa-plus text-sm"></i>
            </a>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="p-4 overflow-y-auto" style="max-height: calc(100vh - 340px);">
        
        <!-- User Menu -->
        <?php if (in_array($userRole, ['user', 'reseller', 'admin', 'superadmin'])): ?>
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Services</p>
            <ul class="space-y-1">
                <?php foreach ($menuItems['user'] as $item): ?>
                <li>
                    <a href="<?php echo $item['url']; ?>" 
                       class="flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php 
                           echo isActive($item['page'], $currentPage) 
                               ? 'bg-primary-50 text-primary-600 font-medium' 
                               : 'text-gray-600 hover:bg-gray-50'; 
                       ?>">
                        <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                        <span><?php echo $item['label']; ?></span>
                        <?php if (isActive($item['page'], $currentPage)): ?>
                        <span class="ml-auto w-2 h-2 bg-primary-500 rounded-full"></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Reseller Menu -->
        <?php if (in_array($userRole, ['reseller', 'admin', 'superadmin'])): ?>
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Reseller</p>
            <ul class="space-y-1">
                <?php foreach ($menuItems['reseller'] as $item): ?>
                <li>
                    <a href="<?php echo $item['url']; ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50">
                        <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Admin Menu -->
        <?php if (in_array($userRole, ['admin', 'superadmin'])): ?>
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Admin</p>
            <ul class="space-y-1">
                <?php foreach ($menuItems['admin'] as $item): ?>
                <li>
                    <a href="<?php echo $item['url']; ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50">
                        <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <!-- Superadmin Menu -->
        <?php if ($userRole === 'superadmin'): ?>
        <div class="mb-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider px-4 mb-2">Super Admin</p>
            <ul class="space-y-1">
                <?php foreach ($menuItems['superadmin'] as $item): ?>
                <li>
                    <a href="<?php echo $item['url']; ?>" class="flex items-center gap-3 px-4 py-3 rounded-xl text-gray-600 hover:bg-gray-50">
                        <i class="fas <?php echo $item['icon']; ?> w-5 text-center"></i>
                        <span><?php echo $item['label']; ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
    </nav>
    
    <!-- Logout -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-100 bg-white">
        <a href="<?php echo APP_URL; ?>/auth/logout.php" class="flex items-center gap-3 px-4 py-3 text-red-500 hover:bg-red-50 rounded-xl">
            <i class="fas fa-sign-out-alt w-5 text-center"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const menuBtn = document.getElementById('mobile-menu-btn');
    const closeBtn = document.getElementById('close-sidebar');
    
    function openSidebar() {
        sidebar.classList.remove('-translate-x-full');
        overlay.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }
    
    menuBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
    
    // Close sidebar on link click (mobile)
    sidebar?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 1024) closeSidebar();
        });
    });
});
</script>
