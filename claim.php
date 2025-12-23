<?php
/**
 * 2SureSub - Claim Your Data Giveaway
 * Public Page
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/InlomaxAPI.php';

$code = cleanInput($_GET['code'] ?? '');
$gift = null;
$error = '';
$success = '';

if ($code) {
    $gift = dbFetchOne("
        SELECT g.*, dp.plan_code, dp.data_amount, n.name as network_name, u.username as gifter_name 
        FROM data_gifts g 
        JOIN data_plans dp ON g.plan_id = dp.id 
        JOIN networks n ON dp.network_id = n.id 
        JOIN users u ON g.user_id = u.id
        WHERE g.code = ?
    ", [$code]);
    
    if (!$gift) {
        $error = "Invalid giveaway code.";
    } elseif ($gift['status'] !== 'pending') {
        $error = "This giveaway has already been claimed.";
    }
} else {
    $error = "No giveaway code provided.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $gift && $gift['status'] === 'pending') {
    $phone = cleanInput($_POST['phone_number']);
    
    if (empty($phone) || !isValidPhone($phone)) {
        $error = "Please enter a valid phone number.";
    } else {
        $inlomax = getInlomaxAPI();
        
        if ($inlomax->isConfigured()) {
            // Trigger purchase
            $apiResponse = $inlomax->buyData($gift['plan_code'], $phone);
            $status = $apiResponse['status'] ?? 'failed';
            
            if ($status === 'success' || $status === 'processing') {
                $dbStatus = ($status === 'success') ? 'completed' : 'processing';
                
                // Update gift status
                dbExecute("UPDATE data_gifts SET status = 'claimed', claimed_by = ?, transaction_id = ? WHERE id = ?", 
                    [$phone, $apiResponse['data']['reference'] ?? 0, $gift['id']]);
                
                // Create transaction record for the gifter
                dbInsert("INSERT INTO transactions (user_id, type, network, phone_number, amount, plan_name, reference, status, api_response) VALUES (?, 'data', ?, ?, ?, ?, ?, ?, ?)",
                    [$gift['user_id'], $gift['network_name'], $phone, $gift['amount'], $gift['data_amount'], generateReference('GIFT-CLAIM'), $dbStatus, json_encode($apiResponse)]);
                
                $success = "Congratulations! Your account $phone has been credited with {$gift['data_amount']} data.";
                $gift['status'] = 'claimed'; // Update local state for UI
            } else {
                $error = "External API Error: " . ($apiResponse['message'] ?? 'Unable to process at the moment.');
            }
        } else {
            // Simulated
            dbExecute("UPDATE data_gifts SET status = 'claimed', claimed_by = ? WHERE id = ?", [$phone, $gift['id']]);
            $success = "[Simulated] Your account $phone has been credited with {$gift['data_amount']} data.";
            $gift['status'] = 'claimed';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claim Your Data Giveaway - 2SureSub</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #f8fafc; }
        .gift-card { background: linear-gradient(135deg, #ffffff 0%, #f1f5f9 100%); }
        .gradient-text { background: linear-gradient(to right, #2563eb, #7c3aed); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
    </style>
</head>
<body class="min-h-screen flex flex-col">
    <nav class="p-6">
        <div class="max-w-7xl mx-auto flex justify-center">
            <a href="index.php" class="flex items-center gap-2">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/30">
                    <i class="fas fa-bolt text-white text-xl"></i>
                </div>
                <span class="text-2xl font-black text-gray-900 tracking-tight">2Sure<span class="text-blue-600">Sub</span></span>
            </a>
        </div>
    </nav>

    <div class="flex-1 flex items-center justify-center p-4 sm:p-6 lg:p-8">
        <div class="w-full max-w-lg">
            <?php if ($success): ?>
                <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden text-center p-8 sm:p-12 animate-[bounce_1s_ease-in-out]">
                    <div class="w-24 h-24 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-check text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-gray-900 mb-4">You got it!</h1>
                    <p class="text-gray-600 leading-relaxed mb-8"><?php echo $success; ?></p>
                    <a href="index.php" class="inline-block w-full py-4 bg-gray-900 text-white font-bold rounded-2xl hover:bg-gray-800 transition-all">
                        Create Your Own Link →
                    </a>
                </div>
            <?php elseif ($error): ?>
                <div class="bg-white rounded-[2rem] shadow-2xl p-8 sm:p-12 text-center">
                    <div class="w-24 h-24 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-exclamation-triangle text-4xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-gray-900 mb-2">Oops!</h1>
                    <p class="text-gray-600 mb-8"><?php echo $error; ?></p>
                    <a href="index.php" class="inline-block w-full py-4 bg-blue-600 text-white font-bold rounded-2xl shadow-xl shadow-blue-200 hover:shadow-none transition-all">
                        Back to Home
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-white rounded-[2rem] shadow-2xl overflow-hidden gift-card border border-white">
                    <!-- Top Section -->
                    <div class="p-8 sm:p-12 text-center relative">
                        <div class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-blue-600 to-purple-600"></div>
                        <div class="w-20 h-20 bg-blue-600 text-white rounded-2xl flex items-center justify-center mx-auto mb-6 rotate-12 shadow-xl shadow-blue-500/40">
                            <i class="fas fa-gift text-3xl"></i>
                        </div>
                        <p class="text-blue-600 font-bold uppercase tracking-widest text-xs mb-2">New Giveaway Received</p>
                        <h1 class="text-4xl font-black text-gray-900 mb-4">
                            You've Been Gifted <span class="gradient-text"><?php echo $gift['data_amount']; ?></span>!
                        </h1>
                        <p class="text-gray-500 text-sm italic">"A small token from <span class="font-bold text-gray-700">@<?php echo htmlspecialchars($gift['gifter_name']); ?></span>"</p>
                    </div>

                    <!-- Input Section -->
                    <div class="bg-white p-8 sm:p-12">
                        <form method="POST" class="space-y-6">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-3">Enter Destination Number</label>
                                <div class="relative">
                                    <span class="absolute left-5 top-1/2 -translate-y-1/2 text-gray-400">
                                        <i class="fas fa-mobile-screen-button"></i>
                                    </span>
                                    <input type="tel" name="phone_number" required 
                                           class="w-full pl-14 pr-6 py-5 bg-gray-50 border border-gray-100 rounded-2xl focus:ring-4 focus:ring-blue-100 focus:border-blue-600 outline-none transition-all text-xl font-bold tracking-widest"
                                           placeholder="08012345678">
                                </div>
                                <p class="mt-3 text-center text-xs text-gray-400">
                                    <i class="fas fa-lock mr-1"></i> Fast & Secure claiming powered by 2SureSub
                                </p>
                            </div>
                            
                            <button type="submit" class="w-full py-5 bg-blue-600 text-white font-black text-xl rounded-2xl shadow-2xl shadow-blue-200 hover:shadow-none hover:-translate-y-1 active:translate-y-0 transition-all flex items-center justify-center gap-3">
                                <span>Claim Data Now</span>
                                <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="p-8 text-center text-gray-400 text-xs">
        &copy; <?php echo date('Y'); ?> 2SureSub. All rights reserved.
    </footer>
</body>
</html>
