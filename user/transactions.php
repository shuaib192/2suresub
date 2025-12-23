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
                    <div class="flex items-center gap-4">
                        <div class="text-right hidden sm:block">
                            <p class="font-semibold text-gray-900 text-sm"><?php echo formatMoney($txn['amount']); ?></p>
                            <div class="flex items-center gap-2 justify-end">
                                <span class="inline-block px-2 py-0.5 text-xs rounded-full <?php
                                    echo $txn['status'] === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700';
                                ?>"><?php echo ucfirst($txn['status']); ?></span>
                                <span class="text-xs text-gray-400"><?php echo timeAgo($txn['created_at']); ?></span>
                            </div>
                        </div>
                        <button onclick='viewTxn(<?php echo json_encode($txn); ?>)' class="p-2 text-gray-400 hover:text-primary-600 transition-colors">
                            <i class="fas fa-chevron-right"></i>
                        </button>
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
<div id="txn-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 opacity-0 transition-all duration-300" id="modal-content">
        <div class="p-6">
            <div class="text-center mb-6">
                <div id="txn-icon-container" class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3">
                    <i id="txn-icon" class="fas text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900" id="txn-type-title">Transaction Details</h3>
                <p class="text-gray-500 text-xs" id="txn-date">-</p>
            </div>
            
            <div class="bg-gray-50 rounded-2xl p-4 space-y-3 mb-6">
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Reference</span>
                    <span class="font-bold text-gray-900" id="txn-ref">-</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Beneficiary</span>
                    <span class="font-bold text-gray-900" id="txn-phone">-</span>
                </div>
                <div class="flex justify-between items-center text-sm">
                    <span class="text-gray-500 font-medium">Description</span>
                    <span class="font-bold text-gray-900 text-right text-xs max-w-[150px]" id="txn-desc">-</span>
                </div>
                <div class="pt-3 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-gray-900 font-bold">Amount</span>
                    <span class="text-lg font-black text-primary-600" id="txn-amount">-</span>
                </div>
                <div class="flex justify-between items-center text-xs pt-1">
                    <span class="text-gray-500 font-medium">Status</span>
                    <span id="txn-status" class="px-2 py-0.5 rounded-full font-bold uppercase">-</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button onclick="closeTxnModal()" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-colors">
                    Close
                </button>
                <a id="receipt-btn" href="#" target="_blank" class="flex-1 py-3 bg-gradient-primary text-white font-bold rounded-xl shadow-lg text-center">
                    <i class="fas fa-print mr-1"></i> Receipt
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const modal = document.getElementById('txn-modal');
const modalContent = document.getElementById('modal-content');

function viewTxn(txn) {
    // Basic fields
    document.getElementById('txn-type-title').textContent = txn.type.charAt(0).toUpperCase() + txn.type.slice(1);
    document.getElementById('txn-date').textContent = new Date(txn.created_at).toLocaleString();
    document.getElementById('txn-ref').textContent = txn.reference;
    document.getElementById('txn-phone').textContent = txn.phone_number || txn.smart_card_number || 'N/A';
    document.getElementById('txn-desc').textContent = txn.plan_name || txn.description || '-';
    document.getElementById('txn-amount').textContent = '₦' + parseFloat(txn.amount).toLocaleString(undefined, {minimumFractionDigits: 2});
    
    // Status color
    const statusEl = document.getElementById('txn-status');
    statusEl.textContent = txn.status;
    if (txn.status === 'completed') {
        statusEl.className = 'px-2 py-0.5 rounded-full font-bold uppercase bg-green-100 text-green-700';
    } else if (txn.status === 'failed') {
        statusEl.className = 'px-2 py-0.5 rounded-full font-bold uppercase bg-red-100 text-red-700';
    } else {
        statusEl.className = 'px-2 py-0.5 rounded-full font-bold uppercase bg-yellow-100 text-yellow-700';
    }
    
    // Receipt button link
    const receiptBtn = document.getElementById('receipt-btn');
    if (txn.status === 'completed') {
        receiptBtn.classList.remove('hidden');
        receiptBtn.href = 'receipt.php?id=' + txn.reference;
    } else {
        receiptBtn.classList.add('hidden');
    }
    
    // Icon
    const iconContainer = document.getElementById('txn-icon-container');
    const icon = document.getElementById('txn-icon');
    const types = {
        data: { bg: 'bg-blue-100', text: 'text-blue-500', icon: 'fa-wifi' },
        airtime: { bg: 'bg-green-100', text: 'text-green-500', icon: 'fa-phone' },
        cable: { bg: 'bg-purple-100', text: 'text-purple-500', icon: 'fa-tv' },
        electricity: { bg: 'bg-yellow-100', text: 'text-yellow-500', icon: 'fa-bolt' },
        exam: { bg: 'bg-red-100', text: 'text-red-500', icon: 'fa-graduation-cap' },
        funding: { bg: 'bg-indigo-100', text: 'text-indigo-500', icon: 'fa-credit-card' }
    };
    const t = types[txn.type] || { bg: 'bg-gray-100', text: 'text-gray-500', icon: 'fa-receipt' };
    iconContainer.className = `w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 ${t.bg} ${t.text}`;
    icon.className = `fas ${t.icon} text-2xl`;
    
    // Show modal
    modal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
}

function closeTxnModal() {
    modalContent.classList.replace('scale-100', 'scale-95');
    modalContent.classList.replace('opacity-100', 'opacity-0');
    setTimeout(() => {
        modal.classList.add('hidden');
    }, 300);
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
