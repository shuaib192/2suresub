<?php
/**
 * 2SureSub - User Management (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$success = ''; $error = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$search = cleanInput($_GET['search'] ?? '');
$roleFilter = $_GET['role'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = (int)$_POST['user_id'];
    
    if ($action === 'update_role') {
        dbExecute("UPDATE users SET role = ? WHERE id = ?", [cleanInput($_POST['role']), $userId]);
        logActivity('user_role_change', "Changed role for user $userId", 'users');
        $success = 'Role updated!';
    } elseif ($action === 'toggle_status') {
        $user = dbFetchOne("SELECT status FROM users WHERE id = ?", [$userId]);
        $newStatus = $user['status'] === 'active' ? 'suspended' : 'active';
        dbExecute("UPDATE users SET status = ? WHERE id = ?", [$newStatus, $userId]);
        $success = 'Status updated!';
    } elseif ($action === 'credit_wallet') {
        $amount = (float)$_POST['amount'];
        creditWallet($userId, $amount, "Admin credit", generateReference('ADM'));
        $success = formatMoney($amount) . " credited!";
    }
}

$where = "1=1"; $params = [];
if ($search) { $where .= " AND (username LIKE ? OR email LIKE ?)"; $params = ["%$search%", "%$search%"]; }
if ($roleFilter) { $where .= " AND role = ?"; $params[] = $roleFilter; }

$total = dbFetchOne("SELECT COUNT(*) as c FROM users WHERE $where", $params)['c'];
$users = dbFetchAll("SELECT u.*, w.balance FROM users u LEFT JOIN wallets w ON u.id = w.user_id WHERE $where ORDER BY u.created_at DESC LIMIT 20 OFFSET " . (($page-1)*20), $params);

$pageTitle = 'Manage Users';
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
        <form class="bg-white rounded-xl border p-3 mb-4 flex flex-wrap gap-2">
            <input type="text" name="search" value="<?php echo $search; ?>" placeholder="Search..." class="flex-1 min-w-[150px] px-3 py-2 border rounded-lg text-sm">
            <select name="role" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Roles</option>
                <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>>User</option>
                <option value="reseller" <?php echo $roleFilter === 'reseller' ? 'selected' : ''; ?>>Reseller</option>
                <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Search</button>
        </form>
        
        <!-- Users -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="divide-y">
                <?php foreach ($users as $u): ?>
                <div class="p-4">
                    <div class="flex items-center justify-between mb-2">
                        <div>
                            <p class="font-medium text-sm"><?php echo $u['first_name'] . ' ' . $u['last_name']; ?></p>
                            <p class="text-xs text-gray-500">@<?php echo $u['username']; ?> • <?php echo $u['email']; ?></p>
                        </div>
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $u['status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>"><?php echo ucfirst($u['status']); ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <form method="POST" class="inline">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="action" value="update_role">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="role" onchange="this.form.submit()" class="text-xs px-2 py-1 border rounded">
                                    <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>User</option>
                                    <option value="reseller" <?php echo $u['role'] === 'reseller' ? 'selected' : ''; ?>>Reseller</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    <option value="superadmin" <?php echo $u['role'] === 'superadmin' ? 'selected' : ''; ?>>Superadmin</option>
                                </select>
                            </form>
                            <span class="text-sm font-semibold"><?php echo formatMoney($u['balance'] ?? 0); ?></span>
                        </div>
                        <div class="flex gap-1">
                            <form method="POST" class="inline"><input type="hidden" name="action" value="toggle_status"><input type="hidden" name="user_id" value="<?php echo $u['id']; ?>"><button type="submit" class="px-2 py-1 text-xs rounded <?php echo $u['status'] === 'active' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600'; ?>"><?php echo $u['status'] === 'active' ? 'Suspend' : 'Activate'; ?></button></form>
                            <button onclick="openCreditModal(<?php echo $u['id']; ?>,'<?php echo $u['username']; ?>')" class="px-2 py-1 text-xs bg-blue-100 text-blue-600 rounded">Credit</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<!-- Credit Modal -->
<div id="credit-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full">
        <h3 class="font-semibold mb-3">Credit Wallet</h3>
        <p class="text-gray-500 text-sm mb-3">User: <strong id="credit-username"></strong></p>
        <form method="POST">
            <?php echo csrfField(); ?>
            <input type="hidden" name="action" value="credit_wallet">
            <input type="hidden" name="user_id" id="credit-user-id">
            <input type="number" name="amount" placeholder="Amount" step="0.01" min="1" required class="w-full px-4 py-3 border rounded-xl mb-3">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 py-2 bg-primary-500 text-white rounded-xl">Credit</button>
                <button type="button" onclick="closeCreditModal()" class="flex-1 py-2 bg-gray-100 rounded-xl">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreditModal(userId, username) {
    document.getElementById('credit-user-id').value = userId;
    document.getElementById('credit-username').textContent = username;
    document.getElementById('credit-modal').classList.remove('hidden');
    document.getElementById('credit-modal').classList.add('flex');
}
function closeCreditModal() {
    document.getElementById('credit-modal').classList.add('hidden');
    document.getElementById('credit-modal').classList.remove('flex');
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
