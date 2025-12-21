<?php
/**
 * 2SureSub - Cable TV (with Inlomax API)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$providers = dbFetchAll("SELECT * FROM cable_providers WHERE status = 'active' ORDER BY name");
$selectedProvider = $_GET['provider'] ?? ($providers[0]['id'] ?? 1);
$cablePlans = dbFetchAll("SELECT * FROM cable_plans WHERE provider_id = ? AND status = 'active' ORDER BY price_user ASC", [$selectedProvider]);

$error = ''; $success = ''; $customerName = '';

// Verify IUC via AJAX
if (isset($_GET['verify']) && isset($_GET['iuc']) && isset($_GET['plan'])) {
    header('Content-Type: application/json');
    $inlomax = getInlomaxAPI();
    if ($inlomax->isConfigured()) {
        $result = $inlomax->validateCable($_GET['plan'], $_GET['iuc']);
        echo json_encode($result);
    } else {
        echo json_encode(['status' => 'success', 'data' => ['customerName' => 'SIMULATED CUSTOMER']]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int)$_POST['plan_id'];
    $smartCardNumber = cleanInput($_POST['smart_card_number']);
    
    if (empty($smartCardNumber) || strlen($smartCardNumber) < 10) {
        $error = 'Please enter a valid IUC number';
    } elseif (!$planId) {
        $error = 'Please select a plan';
    } else {
        $plan = dbFetchOne("SELECT cp.*, p.name as provider_name, p.code as provider_code FROM cable_plans cp JOIN cable_providers p ON cp.provider_id = p.id WHERE cp.id = ?", [$planId]);
        
        if ($plan) {
            $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
            
            if ($wallet['balance'] < $price) {
                $error = 'Insufficient balance';
            } else {
                $reference = generateReference('CABLE');
                $inlomax = getInlomaxAPI();
                
                if ($inlomax->isConfigured()) {
                    if (deductWallet($user['id'], $price, "Cable: {$plan['plan_name']}", $reference)) {
                        
                        $apiResponse = $inlomax->subscribeCable($plan['plan_code'], $smartCardNumber);
                        $apiStatus = $apiResponse['status'] ?? 'failed';
                        $apiMessage = $apiResponse['message'] ?? 'Unknown error';
                        
                        if ($apiStatus === 'success') {
                            $externalRef = $apiResponse['data']['reference'] ?? '';
                            
                            dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, cost_price, plan_name, reference, external_reference, api_response, status) VALUES (?, 'cable', ?, ?, ?, ?, ?, ?, ?, ?, 'completed')",
                                [$user['id'], $plan['provider_name'], $smartCardNumber, $price, $plan['cost_price'], $plan['plan_name'], $reference, $externalRef, json_encode($apiResponse)]);
                            
                            logActivity('cable_purchase', "Subscribed {$plan['plan_name']} for IUC: $smartCardNumber via Inlomax", 'transactions');
                            createNotification($user['id'], 'Cable Subscription Successful', "{$plan['plan_name']} activated!", 'success');
                            $wallet = getUserWallet($user['id']);
                            $success = "Success! {$plan['plan_name']} activated for IUC: $smartCardNumber.";
                            
                        } elseif ($apiStatus === 'processing') {
                            dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, cost_price, plan_name, reference, api_response, status) VALUES (?, 'cable', ?, ?, ?, ?, ?, ?, ?, 'processing')",
                                [$user['id'], $plan['provider_name'], $smartCardNumber, $price, $plan['cost_price'], $plan['plan_name'], $reference, json_encode($apiResponse)]);
                            $wallet = getUserWallet($user['id']);
                            $success = "Processing! Your subscription is being activated.";
                            
                        } else {
                            creditWallet($user['id'], $price, "Refund: Cable subscription failed", $reference . '-REF');
                            dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, plan_name, reference, api_response, status) VALUES (?, 'cable', ?, ?, ?, ?, ?, ?, 'failed')",
                                [$user['id'], $plan['provider_name'], $smartCardNumber, $price, $plan['plan_name'], $reference, json_encode($apiResponse)]);
                            $wallet = getUserWallet($user['id']);
                            $error = "Failed: $apiMessage. Refunded.";
                        }
                    }
                } else {
                    // Simulated
                    if (deductWallet($user['id'], $price, "Cable: {$plan['plan_name']}", $reference)) {
                        dbInsert("INSERT INTO transactions (user_id, type, network, smart_card_number, amount, cost_price, plan_name, reference, status) VALUES (?, 'cable', ?, ?, ?, ?, ?, ?, 'completed')",
                            [$user['id'], $plan['provider_name'], $smartCardNumber, $price, $plan['cost_price'], $plan['plan_name'], $reference]);
                        logActivity('cable_purchase', "Subscribed {$plan['plan_name']} for IUC: $smartCardNumber (simulated)", 'transactions');
                        $wallet = getUserWallet($user['id']);
                        $success = "Success! {$plan['plan_name']} activated. (Simulated)";
                    }
                }
            }
        }
    }
}

$pageTitle = 'Cable TV';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Cable TV</h1>
                <p class="text-sm text-gray-500 hidden sm:block">DStv, GOtv, StarTimes & more</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-xl">
                <span class="font-semibold text-primary-600 text-sm"><?php echo formatMoney($wallet['balance']); ?></span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-600 text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST" class="max-w-2xl mx-auto">
            <?php echo csrfField(); ?>
            
            <!-- Providers -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Provider</h2>
                <div class="grid grid-cols-4 gap-2">
                    <?php foreach ($providers as $provider): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="provider_id" value="<?php echo $provider['id']; ?>" class="peer sr-only" <?php echo $provider['id'] == $selectedProvider ? 'checked' : ''; ?> onchange="location.href='?provider=<?php echo $provider['id']; ?>'">
                        <div class="p-2 sm:p-4 rounded-xl border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center transition-all">
                            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center mx-auto mb-1 <?php
                                echo match(strtolower($provider['code'])) {
                                    'dstv' => 'bg-blue-600', 'gotv' => 'bg-orange-500', 'startimes' => 'bg-red-500', 'showmax' => 'bg-purple-600', default => 'bg-gray-500'
                                };
                            ?>">
                                <i class="fas fa-tv text-white text-lg"></i>
                            </div>
                            <span class="text-xs font-medium text-gray-700"><?php echo $provider['name']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- IUC -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Smart Card / IUC Number</h2>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-credit-card"></i></span>
                    <input type="text" name="smart_card_number" id="iuc-input" required class="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500" placeholder="Enter IUC Number">
                </div>
                <div id="customer-name" class="mt-2 text-sm text-green-600 hidden"><i class="fas fa-check-circle mr-1"></i><span></span></div>
            </div>
            
            <!-- Plans -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Package</h2>
                <input type="hidden" name="plan_id" id="selected-plan" value="">
                <div class="space-y-2 max-h-64 overflow-y-auto">
                    <?php foreach ($cablePlans as $plan): 
                        $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
                    ?>
                    <div class="plan-card p-3 rounded-xl border-2 border-gray-200 flex items-center justify-between cursor-pointer transition-all"
                         onclick="selectPlan(<?php echo $plan['id']; ?>, '<?php echo $plan['plan_code']; ?>', this)">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center">
                                <i class="fas fa-tv text-primary-500"></i>
                            </div>
                            <div>
                                <h3 class="font-medium text-gray-900 text-sm"><?php echo $plan['plan_name']; ?></h3>
                                <p class="text-xs text-gray-500"><?php echo $plan['validity']; ?></p>
                            </div>
                        </div>
                        <p class="text-lg font-bold text-primary-600"><?php echo formatMoney($price); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" id="submit-btn" disabled class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg disabled:opacity-50 flex items-center justify-center gap-2">
                <i class="fas fa-paper-plane"></i> Subscribe Now
            </button>
        </form>
    </div>
</main>

<script>
let selectedPlanCode = '';
function selectPlan(planId, planCode, el) {
    document.querySelectorAll('.plan-card').forEach(c => { c.classList.remove('border-primary-500', 'bg-primary-50'); c.classList.add('border-gray-200'); });
    el.classList.remove('border-gray-200'); el.classList.add('border-primary-500', 'bg-primary-50');
    document.getElementById('selected-plan').value = planId;
    selectedPlanCode = planCode;
    document.getElementById('submit-btn').disabled = false;
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
