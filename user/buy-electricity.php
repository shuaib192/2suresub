<?php
/**
 * 2SureSub - Electricity (with Inlomax API)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$discos = dbFetchAll("SELECT * FROM electricity_discos WHERE status = 'active' ORDER BY name");
$error = ''; $success = false; $token = '';

// Verify meter via AJAX
if (isset($_GET['verify'])) {
    header('Content-Type: application/json');
    $inlomax = getInlomaxAPI();
    $serviceID = $_GET['disco'] ?? '1';
    $meterNum = $_GET['meter'] ?? '';
    $meterType = $_GET['type'] ?? 1;
    
    if ($inlomax->isConfigured()) {
        $result = $inlomax->validateMeter($serviceID, $meterNum, $meterType);
        echo json_encode($result);
    } else {
        echo json_encode(['status' => 'success', 'data' => ['customerName' => 'SIMULATED CUSTOMER']]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $discoId = (int)$_POST['disco_id'];
    $meterNumber = cleanInput($_POST['meter_number']);
    $meterType = cleanInput($_POST['meter_type']);
    $amount = (float)$_POST['amount'];
    
    $disco = dbFetchOne("SELECT * FROM electricity_discos WHERE id = ?", [$discoId]);
    
    if (!$disco) {
        $error = 'Invalid distribution company';
    } elseif (strlen($meterNumber) < 10) {
        $error = 'Enter a valid meter number';
    } elseif ($amount < $disco['min_amount'] || $amount > $disco['max_amount']) {
        $error = "Amount must be between " . formatMoney($disco['min_amount']) . " and " . formatMoney($disco['max_amount']);
    } else {
        $totalAmount = $amount + $disco['service_charge'];
        
        if ($wallet['balance'] < $totalAmount) {
            $error = 'Insufficient balance';
        } else {
            $reference = generateReference('ELEC');
            $inlomax = getInlomaxAPI();
            $meterTypeInt = $meterType === 'prepaid' ? 1 : 2;
            
            if ($inlomax->isConfigured()) {
                if (deductWallet($user['id'], $totalAmount, "Electricity: {$disco['name']} - ₦{$amount}", $reference)) {
                    
                    // Get disco serviceID from code (you may need to map this)
                    $serviceID = $disco['code']; // Assuming code stores the Inlomax serviceID
                    
                    $apiResponse = $inlomax->payElectricity($serviceID, $meterNumber, $meterTypeInt, $amount);
                    $apiStatus = $apiResponse['status'] ?? 'failed';
                    $apiMessage = $apiResponse['message'] ?? 'Unknown error';
                    
                    if ($apiStatus === 'success') {
                        $token = $apiResponse['data']['token'] ?? '';
                        $externalRef = $apiResponse['data']['reference'] ?? '';
                        
                        dbInsert("INSERT INTO transactions (user_id, type, network, meter_number, amount, token, plan_name, reference, external_reference, api_response, status) VALUES (?, 'electricity', ?, ?, ?, ?, ?, ?, ?, ?, 'completed')",
                            [$user['id'], $disco['name'], $meterNumber, $totalAmount, $token, ucfirst($meterType) . " - ₦{$amount}", $reference, $externalRef, json_encode($apiResponse)]);
                        
                        logActivity('electricity_purchase', "Purchased ₦{$amount} electricity token via Inlomax", 'transactions');
                        createNotification($user['id'], 'Token Generated', "Your token: $token", 'success');
                        $wallet = getUserWallet($user['id']);
                        $success = true;
                        
                    } elseif ($apiStatus === 'processing') {
                        dbInsert("INSERT INTO transactions (user_id, type, network, meter_number, amount, plan_name, reference, api_response, status) VALUES (?, 'electricity', ?, ?, ?, ?, ?, ?, 'processing')",
                            [$user['id'], $disco['name'], $meterNumber, $totalAmount, ucfirst($meterType) . " - ₦{$amount}", $reference, json_encode($apiResponse)]);
                        $wallet = getUserWallet($user['id']);
                        $success = true;
                        $token = "PROCESSING - Check transaction history";
                        
                    } else {
                        creditWallet($user['id'], $totalAmount, "Refund: Electricity payment failed", $reference . '-REF');
                        dbInsert("INSERT INTO transactions (user_id, type, network, meter_number, amount, plan_name, reference, api_response, status) VALUES (?, 'electricity', ?, ?, ?, ?, ?, ?, 'failed')",
                            [$user['id'], $disco['name'], $meterNumber, $totalAmount, ucfirst($meterType) . " - ₦{$amount}", $reference, json_encode($apiResponse)]);
                        $wallet = getUserWallet($user['id']);
                        $error = "Failed: $apiMessage. Refunded.";
                    }
                }
            } else {
                // Simulated
                $token = 'TOKEN-' . strtoupper(substr(md5($reference), 0, 16));
                
                if (deductWallet($user['id'], $totalAmount, "Electricity: {$disco['name']} - ₦{$amount}", $reference)) {
                    dbInsert("INSERT INTO transactions (user_id, type, network, meter_number, amount, token, plan_name, reference, status) VALUES (?, 'electricity', ?, ?, ?, ?, ?, ?, 'completed')",
                        [$user['id'], $disco['name'], $meterNumber, $totalAmount, $token, ucfirst($meterType) . " - ₦{$amount}", $reference]);
                    logActivity('electricity_purchase', "Purchased ₦{$amount} electricity token (simulated)", 'transactions');
                    createNotification($user['id'], 'Token Generated', "Your token: $token", 'success');
                    $wallet = getUserWallet($user['id']);
                    $success = true;
                }
            }
        }
    }
}

$pageTitle = 'Electricity';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Electricity</h1>
                <p class="text-sm text-gray-500 hidden sm:block">Pay for prepaid or postpaid meter</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-xl">
                <span class="font-semibold text-primary-600 text-sm"><?php echo formatMoney($wallet['balance']); ?></span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        
        <?php if ($success): ?>
        <div class="mb-4 bg-green-50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center"><i class="fas fa-check text-white"></i></div>
                <div>
                    <h3 class="font-bold text-green-800">Token Generated!</h3>
                    <p class="text-green-600 text-sm">Copy the token below</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 mb-3">
                <p class="font-mono text-xl font-bold text-gray-900 break-all" id="token-display"><?php echo $token; ?></p>
            </div>
            <button onclick="navigator.clipboard.writeText('<?php echo $token; ?>'); this.innerHTML='<i class=\'fas fa-check mr-2\'></i>Copied!'" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg">
                <i class="fas fa-copy mr-2"></i>Copy Token
            </button>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="max-w-lg mx-auto">
            <?php echo csrfField(); ?>
            
            <!-- Disco -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Distribution Company</h2>
                <select name="disco_id" id="disco_id" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500">
                    <option value="">Select...</option>
                    <?php foreach ($discos as $disco): ?>
                    <option value="<?php echo $disco['id']; ?>" data-code="<?php echo $disco['code']; ?>"><?php echo $disco['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Meter Type -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Meter Type</h2>
                <div class="grid grid-cols-2 gap-3">
                    <label class="cursor-pointer">
                        <input type="radio" name="meter_type" value="prepaid" class="peer sr-only" checked>
                        <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                            <i class="fas fa-bolt text-2xl text-yellow-500 mb-2"></i>
                            <p class="font-semibold text-sm">Prepaid</p>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="meter_type" value="postpaid" class="peer sr-only">
                        <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center">
                            <i class="fas fa-file-invoice text-2xl text-blue-500 mb-2"></i>
                            <p class="font-semibold text-sm">Postpaid</p>
                        </div>
                    </label>
                </div>
            </div>
            
            <!-- Meter Number -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Meter Number</h2>
                <input type="text" name="meter_number" id="meter_number" required class="w-full px-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500" placeholder="Enter meter number">
                <div id="customer-info" class="mt-2 text-sm text-green-600 hidden"><i class="fas fa-check-circle mr-1"></i><span></span></div>
            </div>
            
            <!-- Amount -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Amount</h2>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500 font-bold">₦</span>
                    <input type="number" name="amount" required min="500" max="100000" class="w-full pl-10 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 text-lg font-bold" placeholder="Min ₦500">
                </div>
                <p class="mt-2 text-xs text-gray-500"><i class="fas fa-info-circle mr-1"></i>Service charge: ₦100</p>
            </div>
            
            <button type="submit" class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-bolt"></i> Pay Electricity
            </button>
        </form>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
