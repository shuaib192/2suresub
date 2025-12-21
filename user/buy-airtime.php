<?php
/**
 * 2SureSub - Buy Airtime (with Inlomax API)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$networks = dbFetchAll("SELECT * FROM networks WHERE status = 'active' ORDER BY name");
$error = ''; $success = '';

// Network to Inlomax serviceID mapping
$networkServiceIDs = [
    'mtn' => '1',
    'airtel' => '2', 
    'glo' => '3',
    '9mobile' => '4'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $networkId = (int)$_POST['network_id'];
    $phoneNumber = cleanInput($_POST['phone_number']);
    $amount = (float)$_POST['amount'];
    
    $network = dbFetchOne("SELECT * FROM networks WHERE id = ?", [$networkId]);
    
    if (!isValidPhone($phoneNumber)) {
        $error = 'Please enter a valid phone number';
    } elseif ($amount < 50 || $amount > 50000) {
        $error = 'Amount must be between ₦50 and ₦50,000';
    } elseif (!$network) {
        $error = 'Invalid network selected';
    } elseif ($wallet['balance'] < $amount) {
        $error = 'Insufficient balance. Please fund your wallet.';
    } else {
        $discount = $user['role'] === 'reseller' ? $amount * 0.02 : 0;
        $finalAmount = $amount - $discount;
        $reference = generateReference('AIR');
        
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            // Use real API
            if (deductWallet($user['id'], $finalAmount, "Airtime: ₦{$amount} {$network['name']}", $reference)) {
                
                $serviceID = $networkServiceIDs[strtolower($network['code'])] ?? '1';
                $apiResponse = $inlomax->buyAirtime($serviceID, $amount, $phoneNumber);
                
                $apiStatus = $apiResponse['status'] ?? 'failed';
                $apiMessage = $apiResponse['message'] ?? 'Unknown error';
                
                if ($apiStatus === 'success') {
                    $externalRef = $apiResponse['data']['reference'] ?? '';
                    
                    dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, plan_name, reference, external_reference, api_response, status) VALUES (?, 'airtime', ?, ?, ?, ?, ?, ?, ?, 'completed')",
                        [$user['id'], $network['name'], $phoneNumber, $finalAmount, "₦{$amount} Airtime", $reference, $externalRef, json_encode($apiResponse)]);
                    
                    logActivity('airtime_purchase', "Purchased ₦{$amount} airtime for $phoneNumber via Inlomax", 'transactions');
                    createNotification($user['id'], 'Airtime Sent!', "₦{$amount} airtime sent to $phoneNumber", 'success');
                    $wallet = getUserWallet($user['id']);
                    $success = "Success! ₦{$amount} airtime sent to $phoneNumber.";
                    
                } elseif ($apiStatus === 'processing') {
                    dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, plan_name, reference, api_response, status) VALUES (?, 'airtime', ?, ?, ?, ?, ?, ?, 'processing')",
                        [$user['id'], $network['name'], $phoneNumber, $finalAmount, "₦{$amount} Airtime", $reference, json_encode($apiResponse)]);
                    $wallet = getUserWallet($user['id']);
                    $success = "Processing! Your airtime is being delivered.";
                    
                } else {
                    // Refund on failure
                    creditWallet($user['id'], $finalAmount, "Refund: Airtime purchase failed", $reference . '-REF');
                    dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, plan_name, reference, api_response, status) VALUES (?, 'airtime', ?, ?, ?, ?, ?, ?, 'failed')",
                        [$user['id'], $network['name'], $phoneNumber, $finalAmount, "₦{$amount} Airtime", $reference, json_encode($apiResponse)]);
                    logActivity('airtime_failed', "Airtime failed: $apiMessage", 'transactions');
                    $wallet = getUserWallet($user['id']);
                    $error = "Transaction failed: $apiMessage. Refunded.";
                }
            }
        } else {
            // Simulated mode
            if (deductWallet($user['id'], $finalAmount, "Airtime: ₦{$amount} {$network['name']}", $reference)) {
                dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, plan_name, reference, status) VALUES (?, 'airtime', ?, ?, ?, ?, ?, 'completed')",
                    [$user['id'], $network['name'], $phoneNumber, $finalAmount, "₦{$amount} Airtime", $reference]);
                logActivity('airtime_purchase', "Purchased ₦{$amount} airtime for $phoneNumber (simulated)", 'transactions');
                createNotification($user['id'], 'Airtime Sent!', "₦{$amount} airtime sent to $phoneNumber", 'success');
                $wallet = getUserWallet($user['id']);
                $success = "Success! ₦{$amount} airtime sent to $phoneNumber. (Simulated)";
            }
        }
    }
}

$pageTitle = 'Buy Airtime';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Buy Airtime</h1>
                <p class="text-sm text-gray-500 hidden sm:block">Top up airtime for any network</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-xl">
                <i class="fas fa-wallet text-primary-500 text-sm"></i>
                <span class="font-semibold text-primary-600 text-sm"><?php echo formatMoney($wallet['balance']); ?></span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl flex items-center gap-2 text-red-600 text-sm"><i class="fas fa-exclamation-circle"></i><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl flex items-center gap-2 text-green-600 text-sm"><i class="fas fa-check-circle"></i><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" class="max-w-lg mx-auto">
            <?php echo csrfField(); ?>
            
            <!-- Networks -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Network</h2>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($networks as $idx => $network): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="network_id" value="<?php echo $network['id']; ?>" class="peer sr-only" <?php echo $idx === 0 ? 'checked' : ''; ?>>
                        <div class="p-2 sm:p-4 rounded-xl border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center transition-all">
                            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center mx-auto mb-1 <?php 
                                echo match(strtolower($network['code'])) {
                                    'mtn' => 'bg-yellow-400', 'airtel' => 'bg-red-500', 'glo' => 'bg-green-500', '9mobile' => 'bg-emerald-600', default => 'bg-gray-400'
                                };
                            ?>">
                                <span class="text-white font-bold text-xs sm:text-sm"><?php echo strtoupper(substr($network['name'], 0, 3)); ?></span>
                            </div>
                            <span class="text-xs font-medium text-gray-700"><?php echo $network['name']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Amount -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Amount</h2>
                <div class="grid grid-cols-3 gap-2 mb-3">
                    <?php foreach ([100, 200, 500, 1000, 2000, 5000] as $amt): ?>
                    <button type="button" onclick="document.getElementById('amount').value=<?php echo $amt; ?>" class="py-2 px-3 border-2 border-gray-200 rounded-xl font-bold text-sm text-gray-700 hover:border-primary-500 hover:bg-primary-50 transition-all">
                        ₦<?php echo number_format($amt); ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">₦</span>
                    <input type="number" name="amount" id="amount" required min="50" max="50000" step="50"
                           class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg font-bold"
                           placeholder="Amount">
                </div>
                <?php if ($user['role'] === 'reseller'): ?>
                <p class="mt-2 text-xs text-green-600"><i class="fas fa-tag mr-1"></i>2% discount applied</p>
                <?php endif; ?>
            </div>
            
            <!-- Phone -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Phone Number</h2>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone_number" required class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500" placeholder="08012345678">
                </div>
            </div>
            
            <button type="submit" class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Buy Airtime
            </button>
        </form>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
