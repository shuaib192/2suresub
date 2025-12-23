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
                    <div class="plan-card p-4 rounded-2xl border-2 border-gray-100 flex items-center justify-between cursor-pointer transition-all hover:border-primary-300 relative overflow-hidden group mb-2"
                         onclick="selectCablePlan(<?php echo $plan['id']; ?>, '<?php echo $plan['plan_code']; ?>', '<?php echo $plan['plan_name']; ?>', '<?php echo formatMoney($price); ?>', this)">
                        <div class="selected-check absolute top-2 right-2 hidden">
                            <i class="fas fa-check-circle text-primary-500"></i>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-primary-50 text-primary-500 rounded-xl flex items-center justify-center group-hover:bg-primary-500 group-hover:text-white transition-all">
                                <i class="fas fa-tv text-xl"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-900 group-hover:text-primary-600 transition-colors"><?php echo $plan['plan_name']; ?></h3>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-wider"><?php echo $plan['validity']; ?></p>
                            </div>
                        </div>
                        <p class="text-xl font-black text-primary-600"><?php echo formatMoney($price); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" id="submit-btn" class="w-full py-4 bg-gradient-primary text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-primary-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-3">
                <i class="fas fa-shopping-cart"></i> Subscribe Now
            </button>
        </form>
    </div>
</main>

<!-- Purchase Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 opacity-0 transition-all duration-300" id="modal-content">
        <div class="p-6 text-center">
            <div class="w-20 h-20 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-tv text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1">Confirm Subscription</h3>
            <p class="text-gray-500 text-sm mb-6">Please verify the details below</p>
            
            <div class="bg-gray-50 rounded-2xl p-4 mb-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Provider</span>
                    <span class="font-bold text-gray-900"><?php 
                        foreach($providers as $p) if($p['id'] == $selectedProvider) echo $p['name'];
                    ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Package</span>
                    <span class="font-bold text-blue-600" id="conf-plan">-</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">IUC Number</span>
                    <span class="font-bold text-gray-900" id="conf-iuc">-</span>
                </div>
                <div class="pt-3 border-t border-gray-200 flex justify-between items-center">
                    <span class="text-gray-900 font-bold">Total Cost</span>
                    <span class="text-xl font-black text-primary-600" id="conf-amount">-</span>
                </div>
            </div>
            
            <div class="flex gap-3">
                <button type="button" onclick="closeConfirmModal()" class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-xl transition-colors">
                    Back
                </button>
                <button type="button" id="final-confirm-btn" class="flex-1 py-3 bg-gradient-primary text-white font-bold rounded-xl shadow-lg hover:shadow-primary-200 hover:-translate-y-0.5 transition-all">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let selectedPlanCode = '';
let selectedPlanName = '';
let selectedPlanAmount = '';

function selectCablePlan(planId, planCode, name, amount, el) {
    document.querySelectorAll('.plan-card').forEach(c => {
        c.classList.remove('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
        c.classList.add('border-gray-100');
        c.querySelector('.selected-check').classList.add('hidden');
    });
    
    el.classList.remove('border-gray-100');
    el.classList.add('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
    el.querySelector('.selected-check').classList.remove('hidden');
    
    document.getElementById('selected-plan').value = planId;
    selectedPlanCode = planCode;
    selectedPlanName = name;
    selectedPlanAmount = amount;
    
    // If IUC is already entered, verify it for the new plan
    const iuc = document.getElementById('iuc-input').value;
    if (iuc.length >= 10) verifyIUC();
}

async function verifyIUC() {
    const iuc = document.getElementById('iuc-input').value;
    const plan = selectedPlanCode;
    const infoDiv = document.getElementById('customer-name');
    
    if (iuc.length < 10 || !plan) return;
    
    infoDiv.classList.remove('hidden');
    infoDiv.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Verifying...';
    
    try {
        const response = await fetch(`?verify=1&iuc=${iuc}&plan=${plan}`);
        const result = await response.json();
        
        if (result.status === 'success' && result.data && result.data.customerName) {
            infoDiv.innerHTML = `<i class="fas fa-check-circle mr-1"></i> ${result.data.customerName}`;
            infoDiv.classList.replace('text-red-600', 'text-green-600');
        } else {
            infoDiv.innerHTML = `<i class="fas fa-times-circle mr-1"></i> ${result.message || 'Invalid IUC number'}`;
            infoDiv.classList.replace('text-green-600', 'text-red-600');
        }
    } catch (e) {
        infoDiv.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i> Verification failed';
    }
}

document.getElementById('iuc-input').addEventListener('blur', verifyIUC);

const buyForm = document.querySelector('form');
const confirmModal = document.getElementById('confirm-modal');
const modalContent = document.getElementById('modal-content');

buyForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const iuc = document.getElementById('iuc-input').value.trim();
    if (!selectedPlanCode) {
        Toast.error('Please select a package first');
        return;
    }
    if (iuc.length < 10) {
        Toast.error('Please enter a valid IUC number');
        return;
    }
    
    // Fill modal
    document.getElementById('conf-plan').textContent = selectedPlanName;
    document.getElementById('conf-iuc').textContent = iuc;
    document.getElementById('conf-amount').textContent = selectedPlanAmount;
    
    // Show modal
    confirmModal.classList.remove('hidden');
    setTimeout(() => {
        modalContent.classList.remove('scale-95', 'opacity-0');
        modalContent.classList.add('scale-100', 'opacity-100');
    }, 10);
});

function closeConfirmModal() {
    modalContent.classList.replace('scale-100', 'scale-95');
    modalContent.classList.replace('opacity-100', 'opacity-0');
    setTimeout(() => {
        confirmModal.classList.add('hidden');
    }, 300);
}

document.getElementById('final-confirm-btn').addEventListener('click', () => {
    buyForm.submit();
});
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
