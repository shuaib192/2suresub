<?php
/**
 * 2SureSub - User Notifications
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);

// Mark all as read
if (isset($_GET['mark_read'])) {
    dbExecute("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$user['id']]);
    header("Location: notifications.php");
    exit;
}

$notifications = dbFetchAll("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50", [$user['id']]);

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Notifications</h1>
            <a href="?mark_read=1" class="text-primary-500 text-sm"><i class="fas fa-check-double mr-1"></i>Mark all read</a>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if (empty($notifications)): ?>
        <div class="bg-white rounded-2xl border p-8 text-center">
            <i class="fas fa-bell text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500 text-sm">No notifications yet</p>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="divide-y">
                <?php foreach ($notifications as $notif): ?>
                <div class="p-4 flex items-start gap-3 <?php echo !$notif['is_read'] ? 'bg-primary-50' : ''; ?>">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 <?php
                        echo match($notif['type']) {
                            'success' => 'bg-green-100 text-green-500',
                            'warning' => 'bg-yellow-100 text-yellow-500',
                            'error' => 'bg-red-100 text-red-500',
                            default => 'bg-blue-100 text-blue-500'
                        };
                    ?>">
                        <i class="fas <?php
                            echo match($notif['type']) {
                                'success' => 'fa-check-circle',
                                'warning' => 'fa-exclamation-triangle',
                                'error' => 'fa-times-circle',
                                default => 'fa-bell'
                            };
                        ?>"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($notif['title']); ?></p>
                        <p class="text-sm text-gray-600 mt-0.5"><?php echo htmlspecialchars($notif['message']); ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?php echo timeAgo($notif['created_at']); ?></p>
                    </div>
                    <?php if (!$notif['is_read']): ?>
                    <span class="w-2 h-2 bg-primary-500 rounded-full flex-shrink-0 mt-2"></span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
