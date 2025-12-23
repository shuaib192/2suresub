<?php
/**
 * 2SureSub - Buy Data (with Inlomax API)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$networks = dbFetchAll("SELECT * FROM networks WHERE status = 'active' ORDER BY name");
$selectedNetwork = $_GET['network'] ?? ($networks[0]['id'] ?? 1);
$dataPlans = dbFetchAll("SELECT * FROM data_plans WHERE network_id = ? AND status = 'active' ORDER BY price_user ASC", [$selectedNetwork]);

$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $networkId = (int)$_POST['network_id'];
    $planId = (int)$_POST['plan_id'];
    $phoneNumber = cleanInput($_POST['phone_number']);
    $isGiveaway = isset($_POST['is_giveaway']) && $_POST['is_giveaway'] == '1';
    
    if (!$isGiveaway && (empty($phoneNumber) || !isValidPhone($phoneNumber))) {
        $error = 'Please enter a valid phone number for direct purchase';
    } elseif (!$planId) {
        $error = 'Please select a data plan';
    } else {
        $plan = dbFetchOne("SELECT dp.*, n.name as network_name, n.code as network_code FROM data_plans dp JOIN networks n ON dp.network_id = n.id WHERE dp.id = ?", [$planId]);
        
        if ($plan) {
            $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
            
            if ($wallet['balance'] < $price) {
                $error = 'Insufficient balance. Please fund your wallet.';
            } else {
                $reference = generateReference($isGiveaway ? 'GIFT' : 'DATA');
                
                if ($isGiveaway) {
                    // Giveaway Logic: Generate code, deduct wallet, insert into data_gifts
                    $giftCode = strtoupper(bin2hex(random_bytes(4))); // 8 chars unique code
                    if (deductWallet($user['id'], $price, "Giveaway: {$plan['data_amount']} {$plan['network_name']}", $reference)) {
                        dbInsert("INSERT INTO data_gifts (user_id, plan_id, amount, code, status) VALUES (?, ?, ?, ?, 'pending')",
                            [$user['id'], $plan['id'], $price, $giftCode]);
                        
                        logActivity('giveaway_created', "Created {$plan['data_amount']} giveaway link", 'giveaways');
                        $success = "Giveaway created! Share this link: " . APP_URL . "/claim.php?code=" . $giftCode;
                        $wallet = getUserWallet($user['id']);
                        
                        // Set specific success for giveaway to show a copyable link UI if needed
                        $giveawayLink = APP_URL . "/claim.php?code=" . $giftCode;
                    } else {
                        $error = 'Wallet deduction failed. Please try again.';
                    }
                } else {
                    // Direct Purchase Logic
                    $inlomax = getInlomaxAPI();
                    if ($inlomax->isConfigured()) {
                        // Use real API
                        if (deductWallet($user['id'], $price, "Data: {$plan['data_amount']} {$plan['network_name']}", $reference)) {
                            $apiResponse = $inlomax->buyData($plan['plan_code'], $phoneNumber);
                            $apiStatus = $apiResponse['status'] ?? 'failed';
                            $apiMessage = $apiResponse['message'] ?? 'Unknown error';
                            
                            if ($apiStatus === 'success' || $apiStatus === 'processing') {
                                $dbStatus = ($apiStatus === 'success') ? 'completed' : 'processing';
                                $externalRef = $apiResponse['data']['reference'] ?? '';
                                dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, cost_price, plan_name, reference, external_reference, api_response, status) VALUES (?, 'data', ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                                    [$user['id'], $plan['network_name'], $phoneNumber, $price, $plan['cost_price'], $plan['plan_name'], $reference, $externalRef, json_encode($apiResponse), $dbStatus]);
                                
                                logActivity('data_purchase', "Purchased {$plan['data_amount']} for $phoneNumber", 'transactions');
                                createNotification($user['id'], 'Data Purchase Successful', "Your {$plan['data_amount']} data for $phoneNumber was successful.", 'success');
                                $success = "Success! Your transaction is " . $dbStatus;
                                $wallet = getUserWallet($user['id']);
                            } else {
                                creditWallet($user['id'], $price, "Refund: Data purchase failed", $reference . '-REF');
                                $error = "Transaction failed: $apiMessage. Your wallet has been refunded.";
                            }
                        }
                    } else {
                        // Simulated
                        if (deductWallet($user['id'], $price, "Data: {$plan['data_amount']} {$plan['network_name']} (Simulated)", $reference)) {
                            dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, cost_price, plan_name, reference, status) VALUES (?, 'data', ?, ?, ?, ?, ?, ?, 'completed')",
                                [$user['id'], $plan['network_name'], $phoneNumber, $price, $plan['cost_price'], $plan['plan_name'], $reference]);
                            $success = "Success! {$plan['data_amount']} sent (Simulated)";
                            $wallet = getUserWallet($user['id']);
                        }
                    }
                }
            }
        }
    }
}

$pageTitle = 'Buy Data';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<!-- Main Content with proper mobile spacing -->
<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Buy Data</h1>
                <p class="text-sm text-gray-500 hidden sm:block">Purchase data bundles for any network</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-xl">
                <i class="fas fa-wallet text-primary-500 text-sm"></i>
                <span class="font-semibold text-primary-600 text-sm"><?php echo formatMoney($wallet['balance']); ?></span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-2xl text-green-700">
                <div class="flex items-center gap-3 mb-2">
                    <i class="fas fa-check-circle text-xl"></i>
                    <span class="font-bold"><?php echo $isGiveaway ? 'Giveaway Link Generated!' : 'Successful!'; ?></span>
                </div>
                <p class="text-sm mb-3"><?php echo $success; ?></p>
                <?php if (isset($giveawayLink)): ?>
                    <div class="flex gap-2">
                        <input type="text" readonly value="<?php echo $giveawayLink; ?>" id="copy-link-input"
                               class="flex-1 bg-white border border-green-200 rounded-lg px-3 py-2 text-xs font-mono">
                        <button onclick="copyGiveawayLink()" class="px-4 py-2 bg-green-600 text-white text-xs font-bold rounded-lg hover:bg-green-700 transition-colors">
                            Copy
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="max-w-2xl mx-auto">
            <?php echo csrfField(); ?>
            
            <!-- Networks -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Network</h2>
                <div class="grid grid-cols-4 gap-2 sm:gap-4">
                    <?php foreach ($networks as $network): ?>
                    <label class="cursor-pointer">
                        <input type="radio" name="network_id" value="<?php echo $network['id']; ?>" 
                               class="peer sr-only" <?php echo $network['id'] == $selectedNetwork ? 'checked' : ''; ?>
                               onchange="location.href='?network=<?php echo $network['id']; ?>'">
                        <div class="p-2 sm:p-4 rounded-xl border-2 border-gray-200 peer-checked:border-primary-500 peer-checked:bg-primary-50 text-center transition-all">
                            <div class="w-10 h-10 sm:w-14 sm:h-14 rounded-lg flex items-center justify-center mx-auto mb-1 sm:mb-2 <?php 
                                echo match(strtolower($network['code'])) {
                                    'mtn' => 'bg-yellow-400',
                                    'airtel' => 'bg-red-500',
                                    'glo' => 'bg-green-500',
                                    '9mobile' => 'bg-emerald-600',
                                    default => 'bg-gray-400'
                                };
                            ?>">
                                <span class="text-white font-bold text-xs sm:text-sm"><?php echo strtoupper(substr($network['name'], 0, 3)); ?></span>
                            </div>
                            <span class="text-xs sm:text-sm font-medium text-gray-700"><?php echo $network['name']; ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Plans -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Data Plan</h2>
                <input type="hidden" name="plan_id" id="selected-plan" value="">
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 sm:gap-3">
                    <?php foreach ($dataPlans as $plan): 
                        $price = getPrice($plan['price_user'], $plan['price_reseller'], $user['role']);
                    ?>
                    <div class="plan-card p-4 rounded-2xl border-2 border-gray-100 cursor-pointer transition-all text-center relative overflow-hidden group hover:border-primary-300"
                         onclick="selectDataPlan(<?php echo $plan['id']; ?>, '<?php echo addslashes($plan['data_amount']); ?>', '<?php echo addslashes(formatMoney($price)); ?>', this)">
                        <div class="selected-check absolute top-2 right-2 hidden">
                            <i class="fas fa-check-circle text-primary-500"></i>
                        </div>
                        <p class="text-xl sm:text-2xl font-black text-gray-900 group-hover:text-primary-600 transition-colors"><?php echo $plan['data_amount']; ?></p>
                        <p class="text-xs font-medium text-gray-400 uppercase tracking-wider mb-2"><?php echo $plan['validity']; ?></p>
                        <div class="py-1 px-3 bg-primary-50 rounded-lg inline-block">
                            <p class="text-sm sm:text-lg font-bold text-primary-600"><?php echo formatMoney($price); ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Giveaway Toggle -->
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 mb-4">
                <label class="flex items-center justify-between cursor-pointer">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                            <i class="fas fa-gift text-lg"></i>
                        </div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">Create as Giveaway Link</p>
                            <p class="text-xs text-gray-500">Pay now, share a link for others to claim</p>
                        </div>
                    </div>
                    <div class="relative inline-block w-12 h-6 transition duration-200 ease-in-out">
                        <input type="checkbox" name="is_giveaway" id="is_giveaway" value="1" class="opacity-0 w-0 h-0 peer">
                        <span class="absolute cursor-pointer top-0 left-0 right-0 bottom-0 bg-gray-300 transition duration-200 rounded-full peer-checked:bg-primary-600 before:content-[''] before:absolute before:h-4 before:w-4 before:left-1 before:bottom-1 before:bg-white before:transition before:duration-200 before:rounded-full peer-checked:before:translate-x-6"></span>
                    </div>
                </label>
            </div>
            
            <!-- Phone -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4 transition-all duration-300 overflow-hidden" id="phone-field-container">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Phone Number</h2>
                <div class="relative">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="fas fa-phone"></i></span>
                    <input type="tel" name="phone_number" id="phone_number" required
                           class="w-full pl-12 pr-4 py-3 sm:py-4 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent text-base"
                           placeholder="08012345678"
                           value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                </div>
            </div>
            
            <button type="submit" id="submit-btn"
                    class="w-full py-4 bg-gradient-primary text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-primary-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-3">
                <i class="fas fa-shopping-cart"></i> Buy Data Now
            </button>
        </form>
    </div>
</main>

<!-- Purchase Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 opacity-0 transition-all duration-300" id="modal-content">
        <div class="p-6 text-center">
            <div class="w-20 h-20 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-shopping-basket text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1">Confirm Purchase</h3>
            <p class="text-gray-500 text-sm mb-6">Please verify the details below</p>
            
            <div class="bg-gray-50 rounded-2xl p-4 mb-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Network</span>
                    <span class="font-bold text-gray-900" id="conf-network"><?php 
                        foreach($networks as $n) if($n['id'] == $selectedNetwork) echo $n['name'];
                    ?></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Data Plan</span>
                    <span class="font-bold text-blue-600" id="conf-plan">-</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Phone Number</span>
                    <span class="font-bold text-gray-900" id="conf-phone">-</span>
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
let selectedPlanId = null;
let selectedPlanName = '';
let selectedPlanAmount = '';

function selectDataPlan(planId, name, amount, el) {
    document.querySelectorAll('.plan-card').forEach(c => {
        c.classList.remove('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
        c.classList.add('border-gray-100');
        c.querySelector('.selected-check').classList.add('hidden');
    });
    
    el.classList.remove('border-gray-100');
    el.classList.add('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
    el.querySelector('.selected-check').classList.remove('hidden');
    
    document.getElementById('selected-plan').value = planId;
    selectedPlanId = planId;
    selectedPlanName = name;
    selectedPlanAmount = amount;
}

const buyForm = document.querySelector('form');
const confirmModal = document.getElementById('confirm-modal');
const modalContent = document.getElementById('modal-content');
const isGiveawayToggle = document.getElementById('is_giveaway');
const phoneContainer = document.getElementById('phone-field-container');
const phoneNumberInput = document.getElementById('phone_number');
const submitBtn = document.getElementById('submit-btn');

isGiveawayToggle.addEventListener('change', function() {
    if (this.checked) {
        phoneContainer.style.height = '0';
        phoneContainer.style.margin = '0';
        phoneContainer.style.padding = '0';
        phoneContainer.style.opacity = '0';
        phoneNumberInput.removeAttribute('required');
        submitBtn.innerHTML = '<i class="fas fa-gift"></i> Create Giveaway Link';
    } else {
        phoneContainer.style.height = 'auto';
        phoneContainer.style.margin = '0 0 1rem 0';
        phoneContainer.style.padding = '1rem';
        phoneContainer.style.opacity = '1';
        phoneNumberInput.setAttribute('required', '');
        submitBtn.innerHTML = '<i class="fas fa-shopping-cart"></i> Buy Data Now';
    }
});

buyForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    if (!selectedPlanId) {
        Toast.error('Please select a data plan first');
        return;
    }

    const isGift = isGiveawayToggle.checked;
    const phone = phoneNumberInput.value.trim();

    if (!isGift && phone.length < 10) {
        Toast.error('Please enter a valid phone number');
        return;
    }
    
    // Fill modal
    document.getElementById('conf-plan').textContent = selectedPlanName;
    document.getElementById('conf-phone').textContent = isGift ? 'Giveaway (Shared Link)' : phone;
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

function copyGiveawayLink() {
    const input = document.getElementById('copy-link-input');
    input.select();
    document.execCommand('copy');
    Toast.success('Giveaway link copied!');
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
