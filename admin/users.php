<?php
/**
 * 2SureSub - Admin Users Management
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_ADMIN);

$success = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$search = cleanInput($_GET['search'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)$_POST['user_id'];
    
    if ($action === 'toggle_status') {
        $u = dbFetchOne("SELECT status FROM users WHERE id = ?", [$userId]);
        $newStatus = $u['status'] === 'active' ? 'suspended' : 'active';
        dbExecute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        logActivity('user_status_change', "Changed user $userId status to $newStatus", 'users');
        $success = 'User status updated!';
    }
}

$where = "role IN ('user', 'reseller')"; $params = [];
if ($search) { $where .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?)"; $params = ["%$search%", "%$search%", "%$search%"]; }

$total = dbFetchOne("SELECT COUNT(*) as c FROM users WHERE $where", $params)['c'];
$users = dbFetchAll("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE $where ORDER BY u.created_at DESC LIMIT 20 OFFSET " . (($page-1)*20), $params);

$pageTitle = 'Users';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Manage Users</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Search -->
        <form class="bg-white rounded-xl border p-3 mb-4 flex gap-2">
            <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search users..." class="flex-1 px-3 py-2 border rounded-lg text-sm">
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Search</button>
        </form>
        
        <p class="text-gray-500 text-xs mb-4"><?php echo number_format($total); ?> users found</p>
        
        <!-- Users -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="divide-y">
                <?php foreach ($users as $u): ?>
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-primary-100 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="font-bold text-primary-600 text-sm"><?php echo strtoupper(substr($u['first_name'], 0, 1)); ?></span>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-sm"><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></p>
                            <p class="text-xs text-gray-500 truncate"><?php echo $u['email']; ?> • <?php echo $u['phone']; ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 text-xs rounded-full capitalize <?php echo $u['role'] === 'reseller' ? 'bg-purple-100 text-purple-700' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $u['role']; ?></span>
                        <form method="POST" class="inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                            <button type="submit" class="px-2 py-1 text-xs rounded <?php echo $u['status'] === 'active' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>">
                                <?php echo $u['status'] === 'active' ? 'Suspend' : 'Activate'; ?>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
