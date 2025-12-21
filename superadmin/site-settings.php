<?php
/**
 * 2SureSub - Site Settings (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'site_name' => cleanInput($_POST['site_name']),
        'site_tagline' => cleanInput($_POST['site_tagline']),
        'site_email' => cleanInput($_POST['site_email']),
        'site_phone' => cleanInput($_POST['site_phone']),
        'site_address' => cleanInput($_POST['site_address']),
        'whatsapp_number' => cleanInput($_POST['whatsapp_number']),
        'min_wallet_fund' => (float)$_POST['min_wallet_fund'],
        'max_wallet_fund' => (float)$_POST['max_wallet_fund'],
        'referral_bonus' => (float)$_POST['referral_bonus'],
        'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
        'allow_registration' => isset($_POST['allow_registration']) ? '1' : '0',
        'email_verification' => isset($_POST['email_verification']) ? '1' : '0',
        'smtp_host' => cleanInput($_POST['smtp_host']),
        'smtp_port' => cleanInput($_POST['smtp_port']),
        'smtp_user' => cleanInput($_POST['smtp_user']),
        'smtp_pass' => $_POST['smtp_pass'], // Don't clean password as it might have special chars
        'smtp_encryption' => cleanInput($_POST['smtp_encryption']),
        'smtp_from_name' => cleanInput($_POST['smtp_from_name']),
        'api_access_key' => cleanInput($_POST['api_access_key']),
    ];
    foreach ($settings as $key => $value) { updateSetting($key, $value); }
    logActivity('site_settings_update', 'Site settings updated', 'settings');
    $success = 'Settings saved!';
}

$pageTitle = 'Site Settings';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Site Settings</h1>
        <p class="text-gray-500 text-sm">Configure your website</p>
    </header>
    
    <div class="p-4 lg:p-6 max-w-2xl">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm flex items-center gap-2"><i class="fas fa-check-circle"></i><?php echo $success; ?></div><?php endif; ?>
        
        <form method="POST">
            <?php echo csrfField(); ?>
            
            <!-- General -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-globe text-primary-500"></i>General</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Site Name</label>
                        <input type="text" name="site_name" value="<?php echo getSetting('site_name', '2SureSub'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Tagline</label>
                        <input type="text" name="site_tagline" value="<?php echo getSetting('site_tagline'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="site_email" value="<?php echo getSetting('site_email'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Phone</label>
                        <input type="tel" name="site_phone" value="<?php echo getSetting('site_phone'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" name="site_address" value="<?php echo getSetting('site_address'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-medium text-gray-700 mb-1">WhatsApp</label>
                        <input type="tel" name="whatsapp_number" value="<?php echo getSetting('whatsapp_number'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                </div>
            </div>
            
            <!-- Wallet -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-wallet text-green-500"></i>Wallet</h2>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Min Fund (₦)</label>
                        <input type="number" name="min_wallet_fund" value="<?php echo getSetting('min_wallet_fund', 100); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Max Fund (₦)</label>
                        <input type="number" name="max_wallet_fund" value="<?php echo getSetting('max_wallet_fund', 1000000); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Referral (₦)</label>
                        <input type="number" name="referral_bonus" value="<?php echo getSetting('referral_bonus', 100); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                </div>
            </div>
            
            <!-- Email Settings -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-envelope text-blue-500"></i>Email Configuration (SMTP)</h2>
                <div class="grid sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">SMTP Host</label>
                        <input type="text" name="smtp_host" value="<?php echo getSetting('smtp_host'); ?>" placeholder="e.g. smtp.gmail.com" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">SMTP Port</label>
                        <input type="text" name="smtp_port" value="<?php echo getSetting('smtp_port'); ?>" placeholder="e.g. 465 or 587" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">SMTP User / Email</label>
                        <input type="text" name="smtp_user" value="<?php echo getSetting('smtp_user'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">SMTP Password / App Key</label>
                        <input type="password" name="smtp_pass" value="<?php echo getSetting('smtp_pass'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Encryption</label>
                        <select name="smtp_encryption" class="w-full px-3 py-2 border rounded-xl text-sm">
                            <option value="ssl" <?php echo getSetting('smtp_encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            <option value="tls" <?php echo getSetting('smtp_encryption') === 'tls' ? 'selected' : ''; ?>>TLS</option>
                            <option value="none" <?php echo getSetting('smtp_encryption') === 'none' ? 'selected' : ''; ?>>None</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">From Name</label>
                        <input type="text" name="smtp_from_name" value="<?php echo getSetting('smtp_from_name'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm">
                    </div>
                </div>
                <p class="mt-3 text-[10px] text-gray-400">Note: Use your Webmail settings or Gmail App Password (16-char key).</p>
            </div>

            <!-- API Access -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-4">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-key text-purple-500"></i>Mobile App API Access</h2>
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">API Access Key (for Mobile App)</label>
                    <div class="flex gap-2">
                        <input type="text" name="api_access_key" id="api_key" value="<?php echo getSetting('api_access_key'); ?>" class="w-full px-3 py-2 border rounded-xl text-sm font-mono" readonly>
                        <button type="button" onclick="const k=Math.random().toString(36).substring(2,15)+Math.random().toString(36).substring(2,15); document.getElementById('api_key').value=k; document.getElementById('api_key').readOnly=false;" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs whitespace-nowrap"><i class="fas fa-sync mr-1"></i>Regenerate</button>
                    </div>
                </div>
                <p class="mt-2 text-[10px] text-gray-400">Share this key ONLY with your mobile app developer. It allows external access to your platform services.</p>
            </div>

            <!-- System -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-6">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm"><i class="fas fa-cog text-gray-500"></i>System</h2>
                <div class="space-y-3">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="maintenance_mode" value="1" <?php echo getSetting('maintenance_mode') == '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded text-red-500">
                        <div><span class="font-medium text-sm">Maintenance Mode</span><p class="text-xs text-gray-500">Disable site access</p></div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="allow_registration" value="1" <?php echo getSetting('allow_registration', '1') == '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded text-primary-500">
                        <div><span class="font-medium text-sm">Allow Registration</span><p class="text-xs text-gray-500">Allow new users</p></div>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" name="email_verification" value="1" <?php echo getSetting('email_verification', '0') == '1' ? 'checked' : ''; ?> class="w-5 h-5 rounded text-blue-500">
                        <div><span class="font-medium text-sm">Mandatory Email Verification</span><p class="text-xs text-gray-500">Users must verify email before access</p></div>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="w-full py-3 bg-primary-500 text-white font-semibold rounded-xl hover:bg-primary-600"><i class="fas fa-save mr-2"></i>Save Settings</button>
        </form>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
