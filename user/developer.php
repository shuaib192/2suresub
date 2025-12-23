<?php
/**
 * 2SureSub - Developer API
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_key'])) {
    $apiKey = bin2hex(random_bytes(24));
    if (dbExecute("UPDATE users SET api_key = ? WHERE id = ?", [$apiKey, $user['id']])) {
        $success = 'New API Key generated successfully!';
        $user['api_key'] = $apiKey; // Update local variable for display
    } else {
        $error = 'Failed to generate API Key.';
    }
}

$pageTitle = 'Developer API';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Developer API</h1>
        <p class="text-sm text-gray-500">Integrate 2SureSub into your own applications</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-xl text-green-600 text-sm"><i class="fas fa-check-circle mr-2"></i><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm"><i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?></div><?php endif; ?>

        <!-- API Key Card -->
        <div class="bg-white rounded-2xl border shadow-sm p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-2">Secret API Key</h2>
            <p class="text-sm text-gray-500 mb-6">Use this key to authenticate your API requests. Keep it secret!</p>
            
            <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
                <div class="relative flex-1 w-full sm:w-auto">
                    <input type="password" id="api-key-input" readonly value="<?php echo $user['api_key'] ?: ''; ?>" 
                           class="w-full pl-4 pr-12 py-3 bg-gray-50 border border-gray-200 rounded-xl font-mono text-sm" 
                           placeholder="No API key generated yet">
                    <button onclick="toggleKey()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <i id="eye-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                <div class="flex gap-2 w-full sm:w-auto">
                    <button onclick="copyKey()" class="flex-1 sm:flex-none px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all">
                        <i class="fas fa-copy mr-2"></i> Copy
                    </button>
                    <form method="POST" class="flex-1 sm:flex-none">
                        <button type="submit" name="generate_key" onclick="return confirm('Are you sure? This will invalidate your old API key.')" 
                                class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition-all">
                            Generate New
                        </button>
                    </form>
                </div>
            </div>
            <?php if (!$user['api_key']): ?>
            <p class="mt-4 text-xs text-orange-600 flex items-center gap-2">
                <i class="fas fa-info-circle"></i> Click "Generate New" to get started.
            </p>
            <?php endif; ?>
        </div>

        <!-- Documentation Card -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b bg-gray-50">
                <h2 class="text-lg font-bold text-gray-900">API Documentation (v1)</h2>
            </div>
            
            <div class="p-6 space-y-8">
                <!-- Base URL -->
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Base URL</h3>
                    <code class="block p-3 bg-gray-900 text-blue-400 rounded-xl font-mono text-xs sm:text-sm">
                        <?php echo APP_URL; ?>/api/v1/
                    </code>
                </div>

                <!-- Authentication -->
                <div>
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-2">Authentication</h3>
                    <p class="text-sm text-gray-600 mb-2">Pass your API key in the request header:</p>
                    <code class="block p-3 bg-gray-100 text-gray-700 rounded-xl font-mono text-xs">
                        X-API-KEY: YOUR_SECRET_KEY
                    </code>
                </div>

                <!-- Endpoints -->
                <div class="space-y-6">
                    <h3 class="text-sm font-bold text-gray-400 uppercase tracking-widest mb-4">Endpoints</h3>

                    <!-- 1. Get Services -->
                    <div class="border rounded-2xl p-4">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded uppercase">GET</span>
                            <span class="font-bold text-gray-900 text-sm">/services.php?type=data</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Fetch all available data plans and networks.</p>
                        <h4 class="text-xs font-bold text-gray-400 mb-2 uppercase">Response</h4>
                        <pre class="p-3 bg-gray-50 rounded-xl text-[10px] sm:text-xs overflow-auto font-mono text-gray-700">
{
  "status": "success",
  "data": {
    "networks": [...],
    "plans": [...]
  }
}</pre>
                    </div>

                    <!-- 2. Purchase -->
                    <div class="border rounded-2xl p-4 border-blue-100 bg-blue-50/20">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded uppercase">POST</span>
                            <span class="font-bold text-gray-900 text-sm">/purchase.php</span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4">Complete a purchase for Data or Airtime.</p>
                        <h4 class="text-xs font-bold text-gray-400 mb-2 uppercase">Payload (JSON)</h4>
                        <pre class="p-3 bg-white border border-blue-100 rounded-xl text-[10px] sm:text-xs overflow-auto font-mono text-gray-700 mb-4">
{
  "type": "data",
  "plan_id": 15,
  "phone": "08012345678"
}</pre>
                        <h4 class="text-xs font-bold text-gray-400 mb-2 uppercase">Airtime Payload</h4>
                        <pre class="p-3 bg-white border border-blue-100 rounded-xl text-[10px] sm:text-xs overflow-auto font-mono text-gray-700">
{
  "type": "airtime",
  "network_id": 1,
  "phone": "08012345678",
  "amount": 100
}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
function toggleKey() {
    const input = document.getElementById('api-key-input');
    const icon = document.getElementById('eye-icon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

function copyKey() {
    const input = document.getElementById('api-key-input');
    input.type = 'text';
    input.select();
    document.execCommand('copy');
    input.type = 'password';
    Toast.success('API Key copied to clipboard!');
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
