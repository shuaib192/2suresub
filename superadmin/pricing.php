<?php
/**
 * 2SureSub - Pricing Management (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$success = '';
$tab = $_GET['tab'] ?? 'data';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $planId = (int)$_POST['plan_id'];
    $type = cleanInput($_POST['type']);
    $priceUser = (float)$_POST['price_user'];
    $priceReseller = (float)$_POST['price_reseller'];
    
    if ($type === 'data') {
        dbExecute("UPDATE data_plans SET price_user = ?, price_reseller = ? WHERE id = ?", [$priceUser, $priceReseller, $planId]);
    } elseif ($type === 'cable') {
        dbExecute("UPDATE cable_plans SET price_user = ?, price_reseller = ? WHERE id = ?", [$priceUser, $priceReseller, $planId]);
    } elseif ($type === 'exam') {
        dbExecute("UPDATE exam_types SET price_user = ?, price_reseller = ? WHERE id = ?", [$priceUser, $priceReseller, $planId]);
    }
    logActivity('pricing_update', "Updated pricing for plan ID: $planId", 'pricing');
    $success = 'Pricing updated!';
}

$dataPlans = dbFetchAll("SELECT dp.*, n.name as network FROM data_plans dp JOIN networks n ON dp.network_id = n.id ORDER BY n.name, dp.price_user");
$cablePlans = dbFetchAll("SELECT cp.*, p.name as provider FROM cable_plans cp JOIN cable_providers p ON cp.provider_id = p.id ORDER BY p.name, cp.price_user");
$examTypes = dbFetchAll("SELECT * FROM exam_types ORDER BY name");

$pageTitle = 'Pricing';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Pricing Management</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Tabs -->
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <a href="?tab=data" class="px-5 py-2 rounded-xl font-medium text-sm whitespace-nowrap <?php echo $tab === 'data' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 border'; ?>">Data</a>
            <a href="?tab=cable" class="px-5 py-2 rounded-xl font-medium text-sm whitespace-nowrap <?php echo $tab === 'cable' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 border'; ?>">Cable</a>
            <a href="?tab=exam" class="px-5 py-2 rounded-xl font-medium text-sm whitespace-nowrap <?php echo $tab === 'exam' ? 'bg-primary-500 text-white' : 'bg-white text-gray-600 border'; ?>">Exams</a>
        </div>
        
        <?php if ($tab === 'data'): ?>
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">Network</th>
                            <th class="px-3 py-2 text-left text-xs">Plan</th>
                            <th class="px-3 py-2 text-left text-xs">Cost (Inlomax) ₦</th>
                            <th class="px-3 py-2 text-left text-xs">User ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Profit ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Reseller ₦</th>
                            <th class="px-3 py-2 text-left text-xs"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($dataPlans as $plan): 
                            $profit = $plan['price_user'] - $plan['cost_price'];
                            $profitClass = $profit <= 0 ? 'text-red-600 font-bold' : 'text-green-600';
                        ?>
                        <tr>
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <input type="hidden" name="type" value="data">
                                <td class="px-3 py-2 font-medium text-xs"><?php echo $plan['network']; ?></td>
                                <td class="px-3 py-2 text-xs"><?php echo $plan['data_amount']; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-gray-500">₦<?php echo number_format($plan['cost_price'], 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_user" value="<?php echo $plan['price_user']; ?>" class="w-20 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2 text-xs <?php echo $profitClass; ?>">₦<?php echo number_format($profit, 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_reseller" value="<?php echo $plan['price_reseller']; ?>" class="w-20 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2"><button type="submit" class="px-2 py-1 bg-primary-500 text-white text-xs rounded shadow-sm hover:bg-primary-600 transition-colors">Save</button></td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif ($tab === 'cable'): ?>
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">Provider</th>
                            <th class="px-3 py-2 text-left text-xs">Plan</th>
                            <th class="px-3 py-2 text-left text-xs">Cost (Inlomax) ₦</th>
                            <th class="px-3 py-2 text-left text-xs">User ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Profit ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Reseller ₦</th>
                            <th class="px-3 py-2 text-left text-xs"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($cablePlans as $plan): 
                            $profit = $plan['price_user'] - $plan['cost_price'];
                            $profitClass = $profit <= 0 ? 'text-red-600 font-bold' : 'text-green-600';
                        ?>
                        <tr>
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <input type="hidden" name="type" value="cable">
                                <td class="px-3 py-2 font-medium text-xs"><?php echo $plan['provider']; ?></td>
                                <td class="px-3 py-2 text-xs truncate max-w-[120px]"><?php echo $plan['plan_name']; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-gray-500">₦<?php echo number_format($plan['cost_price'], 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_user" value="<?php echo $plan['price_user']; ?>" class="w-20 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2 text-xs <?php echo $profitClass; ?>">₦<?php echo number_format($profit, 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_reseller" value="<?php echo $plan['price_reseller']; ?>" class="w-20 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2"><button type="submit" class="px-2 py-1 bg-primary-500 text-white text-xs rounded shadow-sm hover:bg-primary-600 transition-colors">Save</button></td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs">Exam Type</th>
                            <th class="px-3 py-2 text-left text-xs">Cost (Inlomax) ₦</th>
                            <th class="px-3 py-2 text-left text-xs">User ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Profit ₦</th>
                            <th class="px-3 py-2 text-left text-xs">Reseller ₦</th>
                            <th class="px-3 py-2 text-left text-xs"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php foreach ($examTypes as $exam): 
                            $profit = $exam['price_user'] - $exam['cost_price'];
                            $profitClass = $profit <= 0 ? 'text-red-600 font-bold' : 'text-green-600';
                        ?>
                        <tr>
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="plan_id" value="<?php echo $exam['id']; ?>">
                                <input type="hidden" name="type" value="exam">
                                <td class="px-3 py-2 font-medium text-xs"><?php echo $exam['name']; ?></td>
                                <td class="px-3 py-2 text-xs font-semibold text-gray-500">₦<?php echo number_format($exam['cost_price'], 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_user" value="<?php echo $exam['price_user']; ?>" class="w-24 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2 text-xs <?php echo $profitClass; ?>">₦<?php echo number_format($profit, 2); ?></td>
                                <td class="px-3 py-2"><input type="number" step="0.01" name="price_reseller" value="<?php echo $exam['price_reseller']; ?>" class="w-24 px-2 py-1 border rounded text-xs"></td>
                                <td class="px-3 py-2"><button type="submit" class="px-2 py-1 bg-primary-500 text-white text-xs rounded shadow-sm hover:bg-primary-600 transition-colors">Save</button></td>
                            </form>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
