<?php
/**
 * 2SureSub - Transactions (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$typeFilter = $_GET['type'] ?? '';

$where = "user_id = ?"; $params = [$user['id']];
if ($typeFilter) { $where .= " AND type = ?"; $params[] = $typeFilter; }

$totalCount = dbFetchOne("SELECT COUNT(*) as count FROM transactions WHERE $where", $params)['count'];
$totalPages = ceil($totalCount / $perPage);
$transactions = dbFetchAll("SELECT * FROM transactions WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset", $params);

$pageTitle = 'Transactions';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Transaction History</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Filter -->
        <div class="bg-white rounded-xl border p-3 mb-4 flex gap-2 overflow-x-auto">
            <a href="?" class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap <?php echo !$typeFilter ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600'; ?>">All</a>
            <?php foreach (['data', 'airtime', 'cable', 'electricity', 'exam', 'funding'] as $type): ?>
            <a href="?type=<?php echo $type; ?>" class="px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap capitalize <?php echo $typeFilter === $type ? 'bg-primary-500 text-white' : 'bg-gray-100 text-gray-600'; ?>"><?php echo $type; ?></a>
            <?php endforeach; ?>
        </div>
        
        <!-- Transactions -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <?php if (empty($transactions)): ?>
            <div class="p-8 text-center">
                <i class="fas fa-receipt text-gray-300 text-4xl mb-3"></i>
                <p class="text-gray-500 text-sm">No transactions found</p>
            </div>
            <?php else: ?>
            <div class="divide-y">
                <?php foreach ($transactions as $txn): ?>
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0 <?php
                            echo match($txn['type']) {
                                'data' => 'bg-blue-100 text-blue-500',
                                'airtime' => 'bg-green-100 text-green-500',
                                'cable' => 'bg-purple-100 text-purple-500',
                                'electricity' => 'bg-yellow-100 text-yellow-500',
                                'exam' => 'bg-red-100 text-red-500',
                                'funding' => 'bg-indigo-100 text-indigo-500',
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
                                    'funding' => 'fa-credit-card',
                                    default => 'fa-receipt'
                                };
                            ?>"></i>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium text-gray-900 capitalize text-sm"><?php echo $txn['type']; ?></p>
                            <p class="text-xs text-gray-500 truncate"><?php echo $txn['phone_number'] ?: $txn['smart_card_number'] ?: $txn['reference']; ?></p>
                        </div>
                    </div>
                    <div class="text-right flex-shrink-0">
                        <p class="font-semibold text-gray-900 text-sm"><?php echo formatMoney($txn['amount']); ?></p>
                        <div class="flex items-center gap-2 justify-end">
                            <span class="inline-block px-2 py-0.5 text-xs rounded-full <?php
                                echo $txn['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                            ?>"><?php echo ucfirst($txn['status']); ?></span>
                            <span class="text-xs text-gray-400"><?php echo timeAgo($txn['created_at']); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 border-t flex justify-between items-center">
                <span class="text-xs text-gray-500">Page <?php echo $page; ?>/<?php echo $totalPages; ?></span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&type=<?php echo $typeFilter; ?>" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm">Prev</a><?php endif; ?>
                    <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&type=<?php echo $typeFilter; ?>" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Next</a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
