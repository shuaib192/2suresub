<?php
/**
 * 2SureSub - Sync Prices from Inlomax
 * Fetches real pricing from Inlomax API and updates database
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/InlomaxAPI.php';
requireRole(ROLE_SUPERADMIN);

$success = ''; $error = '';
$inlomax = getInlomaxAPI();
$syncedData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sync'])) {
    if (!$inlomax->isConfigured()) {
        $error = 'Please configure your Inlomax API key first in API Settings.';
    } else {
        $result = $inlomax->getServices();
        
        if ($result['status'] === 'success' && isset($result['data'])) {
            $data = $result['data'];
            $synced = ['data' => 0, 'airtime' => 0, 'cable' => 0, 'electricity' => 0, 'education' => 0];
            
            // Sync Data Plans
            if (isset($data['dataPlans'])) {
                foreach ($data['dataPlans'] as $plan) {
                    $network = strtoupper($plan['network'] ?? '');
                    $networkRow = dbFetchOne("SELECT id FROM networks WHERE UPPER(name) = ?", [$network]);
                    if ($networkRow) {
                        $costPrice = (float)str_replace(',', '', $plan['amount']);
                        $userPrice = $costPrice * 1.10; // 10% markup
                        $resellerPrice = $costPrice * 1.05; // 5% markup
                        
                        // Check if exists
                        $existing = dbFetchOne("SELECT id FROM data_plans WHERE plan_code = ?", [$plan['serviceID']]);
                        
                        if ($existing) {
                            // ONLY update the cost price for existing plans
                            dbExecute("UPDATE data_plans SET cost_price = ? WHERE id = ?",
                                [$costPrice, $existing['id']]);
                        } else {
                            // Apply default markup ONLY for brand new plans
                            $userPrice = $costPrice * 1.10; // 10% markup
                            $resellerPrice = $costPrice * 1.05; // 5% markup
                            dbInsert("INSERT INTO data_plans (network_id, plan_name, plan_code, data_amount, validity, price_user, price_reseller, price_api, cost_price, plan_type, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'sme', 'active')",
                                [$networkRow['id'], $plan['dataPlan'] . ' ' . $plan['dataType'], $plan['serviceID'], $plan['dataPlan'], $plan['validity'] ?? '30 Days', $userPrice, $resellerPrice, $resellerPrice, $costPrice]);
                        }
                        $synced['data']++;
                    }
                }
            }
            
            // Sync Cable Plans
            if (isset($data['cablePlans'])) {
                foreach ($data['cablePlans'] as $plan) {
                    $cable = strtoupper($plan['cable'] ?? '');
                    $providerRow = dbFetchOne("SELECT id FROM cable_providers WHERE UPPER(name) = ?", [$cable]);
                    if ($providerRow) {
                        $costPrice = (float)str_replace(',', '', $plan['amount']);
                        $userPrice = $costPrice * 1.05; // 5% markup
                        $resellerPrice = $costPrice * 1.02; // 2% markup
                        
                        $existing = dbFetchOne("SELECT id FROM cable_plans WHERE plan_code = ?", [$plan['serviceID']]);
                        
                        if ($existing) {
                            // ONLY update the cost price for existing plans
                            dbExecute("UPDATE cable_plans SET cost_price = ? WHERE id = ?",
                                [$costPrice, $existing['id']]);
                        } else {
                            // Apply default markup ONLY for brand new plans
                            $userPrice = $costPrice * 1.05; // 5% markup
                            $resellerPrice = $costPrice * 1.02; // 2% markup
                            dbInsert("INSERT INTO cable_plans (provider_id, plan_name, plan_code, price_user, price_reseller, cost_price, status) VALUES (?, ?, ?, ?, ?, ?, 'active')",
                                [$providerRow['id'], $plan['cablePlan'], $plan['serviceID'], $userPrice, $resellerPrice, $costPrice]);
                        }
                        $synced['cable']++;
                    }
                }
            }
            
            // Sync Education/Exam
            if (isset($data['education'])) {
                foreach ($data['education'] as $exam) {
                    $costPrice = (float)str_replace(',', '', $exam['amount']);
                    $userPrice = $costPrice * 1.10;
                    $resellerPrice = $costPrice * 1.05;
                    
                    $existing = dbFetchOne("SELECT id FROM exam_types WHERE UPPER(code) = UPPER(?)", [$exam['type']]);
                    
                    if ($existing) {
                        // ONLY update the cost price for existing plans
                        dbExecute("UPDATE exam_types SET cost_price = ? WHERE id = ?",
                            [$costPrice, $existing['id']]);
                        $synced['education']++;
                    }
                }
            }
            
            logActivity('price_sync', 'Synced prices from Inlomax API', 'pricing');
            $success = "Synced successfully! Data: {$synced['data']}, Cable: {$synced['cable']}, Education: {$synced['education']}";
            $syncedData = $synced;
        } else {
            $error = 'Failed to fetch services from Inlomax: ' . ($result['message'] ?? 'Unknown error');
        }
    }
}

// Get current plan counts
$dataPlanCount = dbFetchOne("SELECT COUNT(*) as c FROM data_plans")['c'];
$cablePlanCount = dbFetchOne("SELECT COUNT(*) as c FROM cable_plans")['c'];
$examCount = dbFetchOne("SELECT COUNT(*) as c FROM exam_types")['c'];

$pageTitle = 'Sync Prices';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Sync Prices from Inlomax</h1>
        <p class="text-gray-500 text-sm">Automatically update your prices from Inlomax API</p>
    </header>
    
    <div class="p-4 lg:p-6 max-w-2xl">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-600 text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Current Stats -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-xl border p-4 text-center">
                <i class="fas fa-wifi text-primary-500 text-xl mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo $dataPlanCount; ?></p>
                <p class="text-xs text-gray-500">Data Plans</p>
            </div>
            <div class="bg-white rounded-xl border p-4 text-center">
                <i class="fas fa-tv text-orange-500 text-xl mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo $cablePlanCount; ?></p>
                <p class="text-xs text-gray-500">Cable Plans</p>
            </div>
            <div class="bg-white rounded-xl border p-4 text-center">
                <i class="fas fa-graduation-cap text-red-500 text-xl mb-2"></i>
                <p class="text-2xl font-bold text-gray-900"><?php echo $examCount; ?></p>
                <p class="text-xs text-gray-500">Exam Types</p>
            </div>
        </div>
        
        <!-- Sync Info -->
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-info-circle text-blue-500 mt-0.5"></i>
                <div>
                    <h3 class="font-semibold text-blue-800 text-sm">How Sync Works</h3>
                    <ul class="text-xs text-blue-700 mt-1 space-y-1">
                        <li>• Fetches current prices from Inlomax API.</li>
                        <li>• Updates <strong>Cost Prices</strong> only for existing plans.</li>
                        <li>• Does <strong>NOT</strong> overwrite your selling prices.</li>
                        <li>• New plans will get a default markup (10% User, 5% Reseller).</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- API Status -->
        <div class="bg-white rounded-2xl border shadow-sm p-4 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="font-semibold text-gray-900 text-sm">Inlomax API Status</h2>
                <?php if ($inlomax->isConfigured()): ?>
                <span class="px-3 py-1 bg-green-100 text-green-700 text-xs font-medium rounded-full"><i class="fas fa-check-circle mr-1"></i>Configured</span>
                <?php else: ?>
                <span class="px-3 py-1 bg-red-100 text-red-700 text-xs font-medium rounded-full"><i class="fas fa-times-circle mr-1"></i>Not Configured</span>
                <?php endif; ?>
            </div>
            
            <?php if (!$inlomax->isConfigured()): ?>
            <p class="text-sm text-gray-500 mb-3">You need to enter your Inlomax API key first.</p>
            <a href="<?php echo APP_URL; ?>/superadmin/api-settings.php" class="inline-block px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg">
                <i class="fas fa-cog mr-1"></i>Go to API Settings
            </a>
            <?php endif; ?>
        </div>
        
        <!-- Sync Button -->
        <?php if ($inlomax->isConfigured()): ?>
        <form method="POST">
            <?php echo csrfField(); ?>
            <button type="submit" name="sync" value="1" class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg flex items-center justify-center gap-2">
                <i class="fas fa-sync-alt"></i> Sync Prices Now
            </button>
        </form>
        <p class="text-center text-xs text-gray-400 mt-3">This will update all plans from Inlomax with automatic markup</p>
        <?php endif; ?>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
