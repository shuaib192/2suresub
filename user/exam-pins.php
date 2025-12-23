<?php
/**
 * 2SureSub - Exam Pins (with Inlomax API)
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$examTypes = dbFetchAll("SELECT * FROM exam_types WHERE status = 'active' ORDER BY name");
$error = ''; $success = false; $generatedPins = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $examId = (int)$_POST['exam_id'];
    $quantity = max(1, min(10, (int)$_POST['quantity']));
    
    $exam = dbFetchOne("SELECT * FROM exam_types WHERE id = ?", [$examId]);
    
    if (!$exam) {
        $error = 'Invalid exam type';
    } else {
        $price = getPrice($exam['price_user'], $exam['price_reseller'], $user['role']);
        $totalPrice = $price * $quantity;
        
        if ($wallet['balance'] < $totalPrice) {
            $error = 'Insufficient balance';
        } else {
            $reference = generateReference('EXAM');
            $inlomax = getInlomaxAPI();
            
            if ($inlomax->isConfigured()) {
                if (deductWallet($user['id'], $totalPrice, "Exam Pin: {$exam['name']} x{$quantity}", $reference)) {
                    
                    // Use exam code as serviceID
                    $serviceID = $exam['code'];
                    $apiResponse = $inlomax->buyEducation($serviceID, $quantity);
                    $apiStatus = $apiResponse['status'] ?? 'failed';
                    $apiMessage = $apiResponse['message'] ?? 'Unknown error';
                    
                    if ($apiStatus === 'success') {
                        // Extract pins from response
                        $pinsData = $apiResponse['data']['pins'] ?? [];
                        $pinStrings = [];
                        foreach ($pinsData as $p) {
                            $pin = trim($p['pin'] ?? '');
                            $serial = trim($p['serialNo'] ?? '');
                            if ($pin) {
                                $generatedPins[] = ['pin' => $pin, 'serial' => $serial];
                                $pinStrings[] = "PIN: $pin" . ($serial ? " | Serial: $serial" : "");
                            }
                        }
                        $pinsText = implode("\n", $pinStrings);
                        $externalRef = $apiResponse['data']['reference'] ?? '';
                        
                        dbInsert("INSERT INTO transactions (user_id, type, network, amount, cost_price, token, plan_name, reference, external_reference, api_response, status) VALUES (?, 'exam', ?, ?, ?, ?, ?, ?, ?, ?, 'completed')",
                            [$user['id'], $exam['name'], $totalPrice, $exam['cost_price'] * $quantity, $pinsText, "{$exam['name']} x{$quantity}", $reference, $externalRef, json_encode($apiResponse)]);
                        
                        logActivity('exam_pin_purchase', "Purchased {$exam['name']} x{$quantity} via Inlomax", 'transactions');
                        createNotification($user['id'], 'Exam PIN Generated', "{$exam['name']} PIN purchased successfully", 'success');
                        $wallet = getUserWallet($user['id']);
                        $success = true;
                        
                    } elseif ($apiStatus === 'processing') {
                        dbInsert("INSERT INTO transactions (user_id, type, network, amount, plan_name, reference, api_response, status) VALUES (?, 'exam', ?, ?, ?, ?, ?, 'processing')",
                            [$user['id'], $exam['name'], $totalPrice, "{$exam['name']} x{$quantity}", $reference, json_encode($apiResponse)]);
                        $wallet = getUserWallet($user['id']);
                        $error = "Processing! Check transaction history for PIN details.";
                        
                    } else {
                        creditWallet($user['id'], $totalPrice, "Refund: Exam PIN purchase failed", $reference . '-REF');
                        dbInsert("INSERT INTO transactions (user_id, type, network, amount, plan_name, reference, api_response, status) VALUES (?, 'exam', ?, ?, ?, ?, ?, 'failed')",
                            [$user['id'], $exam['name'], $totalPrice, "{$exam['name']} x{$quantity}", $reference, json_encode($apiResponse)]);
                        $wallet = getUserWallet($user['id']);
                        $error = "Failed: $apiMessage. Refunded.";
                    }
                }
            } else {
                // Simulated mode
                for ($i = 0; $i < $quantity; $i++) {
                    $generatedPins[] = [
                        'pin' => strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 12)),
                        'serial' => 'SRN' . strtoupper(substr(md5(uniqid()), 0, 8))
                    ];
                }
                $pinStrings = array_map(fn($p) => "PIN: {$p['pin']} | Serial: {$p['serial']}", $generatedPins);
                $pinsText = implode("\n", $pinStrings);
                
                if (deductWallet($user['id'], $totalPrice, "Exam Pin: {$exam['name']} x{$quantity}", $reference)) {
                    dbInsert("INSERT INTO transactions (user_id, type, network, amount, cost_price, token, plan_name, reference, status) VALUES (?, 'exam', ?, ?, ?, ?, ?, ?, 'completed')",
                        [$user['id'], $exam['name'], $totalPrice, $exam['cost_price'] * $quantity, $pinsText, "{$exam['name']} x{$quantity}", $reference]);
                    logActivity('exam_pin_purchase', "Purchased {$exam['name']} x{$quantity} (simulated)", 'transactions');
                    $wallet = getUserWallet($user['id']);
                    $success = true;
                }
            }
        }
    }
}

$pageTitle = 'Exam Pins';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Exam Pins</h1>
                <p class="text-sm text-gray-500 hidden sm:block">WAEC, NECO, NABTEB & more</p>
            </div>
            <div class="flex items-center gap-2 px-3 py-2 bg-primary-50 rounded-xl">
                <span class="font-semibold text-primary-600 text-sm"><?php echo formatMoney($wallet['balance']); ?></span>
            </div>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        
        <?php if ($success && !empty($generatedPins)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 rounded-2xl p-4">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center"><i class="fas fa-check text-white"></i></div>
                <div>
                    <h3 class="font-bold text-green-800">PIN(s) Generated!</h3>
                    <p class="text-green-600 text-sm">Copy the PIN(s) below</p>
                </div>
            </div>
            <div class="bg-white rounded-xl p-4 mb-3 space-y-2">
                <?php foreach ($generatedPins as $idx => $pin): ?>
                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg">
                    <div>
                        <p class="font-mono font-bold text-gray-900"><?php echo $pin['pin']; ?></p>
                        <p class="text-xs text-gray-500">Serial: <?php echo $pin['serial']; ?></p>
                    </div>
                    <button onclick="navigator.clipboard.writeText('<?php echo $pin['pin']; ?>'); this.innerHTML='<i class=\'fas fa-check\'></i>'" class="px-3 py-1 bg-green-100 text-green-600 text-xs rounded-lg">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <button onclick="copyAllPins()" class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg">
                <i class="fas fa-copy mr-2"></i>Copy All
            </button>
        </div>
        <?php endif; ?>
        
        <form method="POST" class="max-w-lg mx-auto">
            <?php echo csrfField(); ?>
            
            <!-- Exam Type -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Select Exam Type</h2>
                <input type="hidden" name="exam_id" id="selected-exam" value="">
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($examTypes as $exam): 
                        $price = getPrice($exam['price_user'], $exam['price_reseller'], $user['role']);
                    ?>
                    <div class="plan-card p-4 rounded-2xl border-2 border-gray-100 cursor-pointer transition-all hover:border-primary-300 relative overflow-hidden group mb-2"
                         onclick="selectExamPin(<?php echo $exam['id']; ?>, '<?php echo $exam['name']; ?>', <?php echo $price; ?>, this)">
                        <div class="selected-check absolute top-2 right-2 hidden">
                            <i class="fas fa-check-circle text-primary-500"></i>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-red-50 text-red-500 rounded-xl flex items-center justify-center group-hover:bg-red-500 group-hover:text-white transition-all">
                                <i class="fas fa-graduation-cap text-xl"></i>
                            </div>
                            <div class="flex-1">
                                <h3 class="font-bold text-gray-900 group-hover:text-primary-600 transition-colors"><?php echo $exam['name']; ?></h3>
                                <p class="text-xl font-black text-primary-600"><?php echo formatMoney($price); ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Quantity -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-3 text-sm">Quantity</h2>
                <div class="flex items-center justify-center gap-4">
                    <button type="button" onclick="adjustQty(-1)" class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200">
                        <i class="fas fa-minus"></i>
                    </button>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="10" readonly class="w-20 text-center text-2xl font-bold border-0 focus:ring-0">
                    <button type="button" onclick="adjustQty(1)" class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center hover:bg-gray-200">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                <p class="mt-4 text-center text-lg font-semibold text-gray-900">Total: <span id="total-price" class="text-primary-600">₦0.00</span></p>
            </div>
            
            <button type="submit" id="submit-btn" class="w-full py-4 bg-gradient-primary text-white font-bold text-lg rounded-2xl shadow-xl hover:shadow-primary-200 hover:-translate-y-0.5 active:translate-y-0 transition-all flex items-center justify-center gap-3">
                <i class="fas fa-ticket-alt"></i> Generate PIN Now
            </button>
        </form>
    </div>
</main>

<!-- Purchase Confirmation Modal -->
<div id="confirm-modal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm hidden">
    <div class="bg-white rounded-3xl w-full max-w-sm overflow-hidden shadow-2xl scale-95 opacity-0 transition-all duration-300" id="modal-content">
        <div class="p-6 text-center">
            <div class="w-20 h-20 bg-primary-100 text-primary-600 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-graduation-cap text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-1">Confirm Purchase</h3>
            <p class="text-gray-500 text-sm mb-6">Please verify the details below</p>
            
            <div class="bg-gray-50 rounded-2xl p-4 mb-6 space-y-3">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Exam Type</span>
                    <span class="font-bold text-gray-900" id="conf-exam">-</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Quantity</span>
                    <span class="font-bold text-gray-900" id="conf-qty">-</span>
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
let selectedExamId = null;
let selectedExamName = '';
let selectedPrice = 0;
const allPins = <?php echo json_encode($generatedPins); ?>;

function selectExamPin(examId, name, price, el) {
    document.querySelectorAll('.plan-card').forEach(c => {
        c.classList.remove('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
        c.classList.add('border-gray-100');
        c.querySelector('.selected-check').classList.add('hidden');
    });
    
    el.classList.remove('border-gray-100');
    el.classList.add('border-primary-500', 'bg-primary-50', 'ring-4', 'ring-primary-100');
    el.querySelector('.selected-check').classList.remove('hidden');
    
    document.getElementById('selected-exam').value = examId;
    selectedExamId = examId;
    selectedExamName = name;
    selectedPrice = price;
    updateTotal();
}

function adjustQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    val = Math.max(1, Math.min(10, val));
    input.value = val;
    updateTotal();
}

function updateTotal() {
    const qty = parseInt(document.getElementById('quantity').value);
    const total = selectedPrice * qty;
    document.getElementById('total-price').textContent = '₦' + total.toLocaleString('en-NG', {minimumFractionDigits: 2});
}

function copyAllPins() {
    const text = allPins.map(p => 'PIN: ' + p.pin + ' | Serial: ' + p.serial).join('\n');
    navigator.clipboard.writeText(text);
    event.target.innerHTML = '<i class="fas fa-check mr-2"></i>Copied!';
}

const buyForm = document.querySelector('form');
const confirmModal = document.getElementById('confirm-modal');
const modalContent = document.getElementById('modal-content');

buyForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const qty = parseInt(document.getElementById('quantity').value);
    
    if (!selectedExamId) {
        Toast.error('Please select an exam type first');
        return;
    }
    
    // Fill modal
    document.getElementById('conf-exam').textContent = selectedExamName;
    document.getElementById('conf-qty').textContent = qty;
    document.getElementById('conf-amount').textContent = document.getElementById('total-price').textContent;
    
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
