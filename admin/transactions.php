<?php
/**
 * 2SureSub - Admin Transactions
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_ADMIN);

$success = '';
$page = max(1, (int)($_GET['page'] ?? 1));
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $txnId = (int)$_POST['txn_id'];
    $newStatus = cleanInput($_POST['status']);
    dbExecute("UPDATE transactions SET status = ? WHERE id = ?", [$newStatus, $txnId]);
    logActivity('transaction_status_change', "Changed transaction $txnId status to $newStatus", 'transactions');
    $success = 'Transaction updated!';
}

$where = "1=1"; $params = [];
if ($typeFilter) { $where .= " AND t.type = ?"; $params[] = $typeFilter; }
if ($statusFilter) { $where .= " AND t.status = ?"; $params[] = $statusFilter; }

$total = dbFetchOne("SELECT COUNT(*) as c FROM transactions t WHERE $where", $params)['c'];
$transactions = dbFetchAll("SELECT t.*, u.username, u.email FROM transactions t JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.created_at DESC LIMIT 30 OFFSET " . (($page-1)*30), $params);

$pageTitle = 'Transactions';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">All Transactions</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Filters -->
        <div class="bg-white rounded-xl border p-3 mb-4 flex flex-wrap gap-2">
            <select onchange="location.href='?type='+this.value+'&status=<?php echo $statusFilter; ?>'" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Types</option>
                <?php foreach (['data', 'airtime', 'cable', 'electricity', 'exam', 'funding'] as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $typeFilter === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
                <?php endforeach; ?>
            </select>
            <select onchange="location.href='?type=<?php echo $typeFilter; ?>&status='+this.value" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Status</option>
                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
            </select>
            <span class="text-gray-500 text-xs self-center"><?php echo number_format($total); ?> transactions</span>
        </div>
        
        <!-- Transactions -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">User</th>
                            <th class="px-3 py-2 text-left text-xs">Type</th>
                            <th class="px-3 py-2 text-left text-xs">Details</th>
                            <th class="px-3 py-2 text-left text-xs">Amount</th>
                            <th class="px-3 py-2 text-left text-xs">Status</th>
                            <th class="px-3 py-2 text-left text-xs">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td class="px-3 py-2">
                                <p class="font-medium text-xs"><?php echo $txn['username']; ?></p>
                            </td>
                            <td class="px-3 py-2 capitalize text-xs"><?php echo $txn['type']; ?></td>
                            <td class="px-3 py-2 text-xs text-gray-500 max-w-[120px] truncate"><?php echo $txn['phone_number'] ?: $txn['smart_card_number'] ?: $txn['reference']; ?></td>
                            <td class="px-3 py-2 font-medium text-xs"><?php echo formatMoney($txn['amount']); ?></td>
                            <td class="px-3 py-2">
                                <form method="POST" class="inline">
                                    <input type="hidden" name="txn_id" value="<?php echo $txn['id']; ?>">
                                    <select name="status" onchange="this.form.submit()" class="text-xs px-2 py-1 border rounded <?php
                                        echo match($txn['status']) {
                                            'completed' => 'bg-green-50 text-green-700',
                                            'pending' => 'bg-yellow-50 text-yellow-700',
                                            'failed' => 'bg-red-50 text-red-700',
                                            default => ''
                                        };
                                    ?>">
                                        <option value="completed" <?php echo $txn['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="pending" <?php echo $txn['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="failed" <?php echo $txn['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                    </select>
                                </form>
                            </td>
                            <td class="px-3 py-2 text-xs text-gray-500"><?php echo date('M j, g:i A', strtotime($txn['created_at'])); ?></td>
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
