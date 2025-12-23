<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$ref = $_GET['id'] ?? '';

if (!$ref) die('Invalid reference');

$txn = dbFetchOne("SELECT * FROM transactions WHERE reference = ? AND user_id = ?", [$ref, $user['id']]);
if (!$txn) die('Transaction not found');

$siteName = getSetting('site_name', '2SureSub');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo $ref; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print { display: none; }
            body { background: white; }
            .receipt-card { border: none; box-shadow: none; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen py-8 px-4">
    <div class="max-w-md mx-auto bg-white rounded-2xl shadow-lg overflow-hidden receipt-card">
        <div class="p-8 text-center border-b border-dashed">
            <h1 class="text-2xl font-black text-blue-600 mb-1"><?php echo $siteName; ?></h1>
            <p class="text-gray-500 text-sm">Transaction Receipt</p>
        </div>
        
        <div class="p-8 space-y-4">
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Date</span>
                <span class="font-bold text-gray-900"><?php echo date('M j, Y - H:i', strtotime($txn['created_at'])); ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Transaction ID</span>
                <span class="font-bold text-gray-900"><?php echo $txn['reference']; ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Type</span>
                <span class="font-bold text-gray-900 capitalize"><?php echo $txn['type']; ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Beneficiary</span>
                <span class="font-bold text-gray-900"><?php echo $txn['phone_number'] ?: $txn['smart_card_number'] ?: 'N/A'; ?></span>
            </div>
            <div class="bg-gray-50 rounded-xl p-4 text-center my-6">
                <p class="text-xs text-gray-400 uppercase font-bold tracking-widest mb-1">Amount Paid</p>
                <p class="text-3xl font-black text-gray-900">₦<?php echo number_format($txn['amount'], 2); ?></p>
            </div>
            
            <div class="flex justify-between text-sm border-t pt-4">
                <span class="text-gray-400">Status</span>
                <span class="font-bold text-green-600 uppercase italic">SUCCESSFUL</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Description</span>
                <span class="font-bold text-gray-900 text-right text-xs max-w-[150px]"><?php echo $txn['plan_name'] ?: ($txn['type'] . ' purchase'); ?></span>
            </div>
        </div>
        
        <div class="p-8 bg-gray-50 text-center border-t border-dashed">
            <p class="text-xs text-gray-400">Thank you for choosing <?php echo $siteName; ?></p>
        </div>
    </div>
    
    <div class="max-w-md mx-auto mt-6 flex gap-4 no-print">
        <button onclick="window.print()" class="flex-1 py-4 bg-primary-600 bg-blue-600 text-white font-bold rounded-2xl shadow-xl hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
            <i class="fas fa-print"></i> Print Receipt
        </button>
        <button onclick="window.close()" class="flex-1 py-4 bg-white text-gray-600 font-bold rounded-2xl shadow border hover:bg-gray-50 transition-all">
            Close
        </button>
    </div>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</body>
</html>
