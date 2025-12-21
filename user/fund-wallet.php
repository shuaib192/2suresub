<?php
/**
 * 2SureSub - Fund Wallet (with Paystack Integration)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/PaystackAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$paystack = getPaystackAPI();
$error = ''; $success = '';

// Callback from Paystack
if (isset($_GET['reference']) && isset($_GET['trxref'])) {
    $reference = cleanInput($_GET['reference']);
    
    // Check if already processed
    $existingTxn = dbFetchOne("SELECT * FROM transactions WHERE reference = ? AND type = 'funding'", [$reference]);
    
    if ($existingTxn && $existingTxn['status'] === 'completed') {
        $success = 'This payment has already been processed.';
    } elseif ($paystack->isConfigured()) {
        // Verify with Paystack
        $result = $paystack->verifyTransaction($reference);
        
        if ($result['status'] && $result['data']['status'] === 'success') {
            $amountPaid = $result['data']['amount'] / 100; // Convert from kobo
            
            // Credit wallet
            creditWallet($user['id'], $amountPaid, "Wallet funding via Paystack", $reference);
            
            // Log transaction
            if ($existingTxn) {
                dbExecute("UPDATE transactions SET status = 'completed', api_response = ? WHERE reference = ?", 
                    [json_encode($result), $reference]);
            } else {
                dbInsert("INSERT INTO transactions (user_id, type, amount, reference, external_reference, api_response, status) VALUES (?, 'funding', ?, ?, ?, ?, 'completed')",
                    [$user['id'], $amountPaid, $reference, $result['data']['reference'], json_encode($result)]);
            }
            
            logActivity('wallet_funded', "Funded wallet with " . formatMoney($amountPaid), 'wallet');
            createNotification($user['id'], 'Wallet Funded!', 'Your wallet has been credited with ' . formatMoney($amountPaid), 'success');
            
            $wallet = getUserWallet($user['id']);
            $success = 'Payment successful! Your wallet has been credited with ' . formatMoney($amountPaid);
        } else {
            $error = 'Payment verification failed. Please contact support if you were debited.';
        }
    }
}

// Initialize payment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'])) {
    $amount = (float)$_POST['amount'];
    $minAmount = (float)getSetting('min_wallet_fund', 100);
    $maxAmount = (float)getSetting('max_wallet_fund', 1000000);
    
    if ($amount < $minAmount || $amount > $maxAmount) {
        $error = "Amount must be between " . formatMoney($minAmount) . " and " . formatMoney($maxAmount);
    } elseif (!$paystack->isConfigured()) {
        $error = "Payment gateway not configured. Please contact admin.";
    } else {
        $reference = generateReference('FUND');
        $callbackUrl = APP_URL . '/user/fund-wallet.php';
        
        // Log pending transaction
        dbInsert("INSERT INTO transactions (user_id, type, amount, reference, status) VALUES (?, 'funding', ?, ?, 'pending')",
            [$user['id'], $amount, $reference]);
        
        // Initialize Paystack
        $result = $paystack->initializeTransaction($user['email'], $amount, $reference, $callbackUrl);
        
        if ($result['status'] && isset($result['data']['authorization_url'])) {
            // Redirect to Paystack
            header('Location: ' . $result['data']['authorization_url']);
            exit;
        } else {
            $error = $result['message'] ?? 'Failed to initialize payment. Try again.';
        }
    }
}

$pageTitle = 'Fund Wallet';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Fund Wallet</h1>
                <p class="text-sm text-gray-500 hidden sm:block">Add money to your wallet</p>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-600 text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div><?php endif; ?>
        
        <div class="max-w-lg mx-auto">
            <!-- Current Balance -->
            <div class="bg-gradient-to-r from-primary-500 to-primary-700 rounded-2xl p-6 text-white mb-6">
                <p class="text-white/80 text-sm mb-1">Current Balance</p>
                <p class="text-3xl font-bold"><?php echo formatMoney($wallet['balance']); ?></p>
            </div>
            
            <!-- Fund Form -->
            <form method="POST" class="bg-white rounded-2xl border shadow-sm p-4 mb-6">
                <?php echo csrfField(); ?>
                
                <h2 class="font-semibold text-gray-900 mb-4 text-sm">Enter Amount</h2>
                
                <!-- Quick Amounts -->
                <div class="grid grid-cols-3 gap-2 mb-4">
                    <?php foreach ([500, 1000, 2000, 5000, 10000, 20000] as $amt): ?>
                    <button type="button" onclick="document.getElementById('amount').value=<?php echo $amt; ?>" 
                            class="py-3 px-4 border-2 border-gray-200 rounded-xl font-bold text-sm text-gray-700 hover:border-primary-500 hover:bg-primary-50 transition-all">
                        ₦<?php echo number_format($amt); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Custom Amount -->
                <div class="relative mb-4">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold text-lg">₦</span>
                    <input type="number" name="amount" id="amount" required 
                           min="<?php echo getSetting('min_wallet_fund', 100); ?>" 
                           max="<?php echo getSetting('max_wallet_fund', 1000000); ?>"
                           class="w-full pl-12 pr-4 py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 text-xl font-bold"
                           placeholder="Enter amount">
                </div>
                
                <p class="text-xs text-gray-500 mb-4">
                    <i class="fas fa-info-circle mr-1"></i>
                    Min: <?php echo formatMoney(getSetting('min_wallet_fund', 100)); ?> | 
                    Max: <?php echo formatMoney(getSetting('max_wallet_fund', 1000000)); ?>
                </p>
                
                <?php if ($paystack->isConfigured()): ?>
                <button type="submit" class="w-full py-4 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all">
                    <i class="fas fa-lock"></i> Pay with Paystack
                </button>
                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-center">
                    <i class="fas fa-exclamation-triangle text-yellow-500 text-2xl mb-2"></i>
                    <p class="text-yellow-700 text-sm">Payment gateway not configured.</p>
                    <p class="text-yellow-600 text-xs">Admin needs to set up Paystack API keys.</p>
                </div>
                <?php endif; ?>
            </form>
            
            <!-- Payment Methods -->
            <div class="bg-white rounded-2xl border shadow-sm p-4">
                <h3 class="font-semibold text-gray-900 mb-3 text-sm">Secure Payment</h3>
                <div class="flex items-center gap-4">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/b/ba/Stripe_Logo%2C_revised_2016.svg" alt="Secure" class="h-6 opacity-50">
                    <div class="flex items-center gap-2 text-gray-400 text-xs">
                        <i class="fas fa-lock"></i>
                        <span>256-bit SSL encrypted</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
