<?php
/**
 * 2SureSub - API Settings (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$success = ''; $error = '';
$apiSettings = dbFetchAll("SELECT * FROM api_settings ORDER BY provider_type, provider_name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $providerId = (int)$_POST['provider_id'];
    $apiKey = cleanInput($_POST['api_key']);
    $secretKey = cleanInput($_POST['secret_key']);
    $baseUrl = cleanInput($_POST['base_url']);
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'] ?? '';
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $isLive = isset($_POST['is_live']) ? 1 : 0;
    
    dbExecute("UPDATE api_settings SET api_key = ?, secret_key = ?, base_url = ?, username = ?, password = ?, is_active = ?, is_live = ? WHERE id = ?",
        [$apiKey, $secretKey, $baseUrl, $username, $password, $isActive, $isLive, $providerId]);
    logActivity('api_settings_update', "Updated API settings", 'settings');
    $success = 'Settings saved!';
    $apiSettings = dbFetchAll("SELECT * FROM api_settings ORDER BY provider_type, provider_name");
}

$pageTitle = 'API Settings';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">API Settings</h1>
        <p class="text-gray-500 text-sm">Configure VTU and payment APIs</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm flex items-center gap-2"><i class="fas fa-check-circle"></i><?php echo $success; ?></div><?php endif; ?>
        
        <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                <div>
                    <h3 class="font-semibold text-yellow-800 text-sm">Important</h3>
                    <p class="text-xs text-yellow-700">Only enable "Live Mode" when ready for real transactions.</p>
                </div>
            </div>
        </div>
        
        <!-- VTU APIs -->
        <div class="mb-8">
            <h2 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-plug text-primary-500"></i> VTU Providers</h2>
            <div class="space-y-4">
                <?php foreach ($apiSettings as $api): if ($api['provider_type'] !== 'vtu') continue; ?>
                <form method="POST" class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center"><i class="fas fa-server text-primary-500"></i></div>
                            <div>
                                <h3 class="font-semibold text-sm"><?php echo $api['provider_name']; ?></h3>
                                <span class="text-xs px-2 py-0.5 rounded-full <?php echo $api['is_active'] ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'; ?>"><?php echo $api['is_active'] ? 'Active' : 'Inactive'; ?></span>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <label class="flex items-center gap-1 cursor-pointer"><input type="checkbox" name="is_active" value="1" <?php echo $api['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 rounded"><span>Active</span></label>
                            <label class="flex items-center gap-1 cursor-pointer"><input type="checkbox" name="is_live" value="1" <?php echo $api['is_live'] ? 'checked' : ''; ?> class="w-4 h-4 rounded text-green-500"><span>Live</span></label>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="provider_id" value="<?php echo $api['id']; ?>">
                        <div class="grid sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">API Key</label>
                                <input type="text" name="api_key" value="<?php echo $api['api_key']; ?>" placeholder="API Key" class="w-full px-3 py-2 border rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Secret Key</label>
                                <input type="password" name="secret_key" value="<?php echo $api['secret_key']; ?>" placeholder="Secret Key" class="w-full px-3 py-2 border rounded-xl text-sm">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Base URL</label>
                            <input type="url" name="base_url" value="<?php echo $api['base_url']; ?>" placeholder="https://api.example.com" class="w-full px-3 py-2 border rounded-xl text-sm">
                        </div>
                        <input type="hidden" name="username" value="<?php echo $api['username']; ?>">
                        <input type="hidden" name="password" value="<?php echo $api['password']; ?>">
                        <button type="submit" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-xl hover:bg-primary-600"><i class="fas fa-save mr-1"></i>Save</button>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Payment APIs -->
        <div>
            <h2 class="text-sm font-semibold text-gray-900 mb-4 flex items-center gap-2"><i class="fas fa-credit-card text-green-500"></i> Payment Gateways</h2>
            <div class="space-y-4">
                <?php foreach ($apiSettings as $api): if ($api['provider_type'] !== 'payment') continue; ?>
                <form method="POST" class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-gray-50 border-b flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center"><i class="fas fa-credit-card text-green-500"></i></div>
                            <h3 class="font-semibold text-sm"><?php echo $api['provider_name']; ?></h3>
                        </div>
                        <div class="flex items-center gap-3 text-xs">
                            <label class="flex items-center gap-1"><input type="checkbox" name="is_active" value="1" <?php echo $api['is_active'] ? 'checked' : ''; ?> class="w-4 h-4 rounded"><span>Active</span></label>
                            <label class="flex items-center gap-1"><input type="checkbox" name="is_live" value="1" <?php echo $api['is_live'] ? 'checked' : ''; ?> class="w-4 h-4 rounded"><span>Live</span></label>
                        </div>
                    </div>
                    <div class="p-4">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="provider_id" value="<?php echo $api['id']; ?>">
                        <div class="grid sm:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Public Key</label>
                                <input type="text" name="api_key" value="<?php echo $api['api_key']; ?>" placeholder="pk_..." class="w-full px-3 py-2 border rounded-xl text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Secret Key</label>
                                <input type="password" name="secret_key" value="<?php echo $api['secret_key']; ?>" placeholder="sk_..." class="w-full px-3 py-2 border rounded-xl text-sm">
                            </div>
                        </div>
                        <input type="hidden" name="base_url" value="<?php echo $api['base_url']; ?>">
                        <input type="hidden" name="username" value="">
                        <input type="hidden" name="password" value="">
                        <button type="submit" class="px-4 py-2 bg-green-500 text-white text-sm font-medium rounded-xl hover:bg-green-600"><i class="fas fa-save mr-1"></i>Save</button>
                    </div>
                </form>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
