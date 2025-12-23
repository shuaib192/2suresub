<?php
/**
 * 2SureSub - My Giveaways
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();

$giveaways = dbFetchAll("
    SELECT g.*, dp.data_amount, n.name as network_name 
    FROM data_gifts g 
    JOIN data_plans dp ON g.plan_id = dp.id 
    JOIN networks n ON dp.network_id = n.id 
    WHERE g.user_id = ? 
    ORDER BY g.created_at DESC
", [$user['id']]);

$pageTitle = 'My Giveaways';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-lg lg:text-2xl font-bold text-gray-900">My Giveaways</h1>
                <p class="text-sm text-gray-500">Manage and track your data giveaway links</p>
            </div>
            <a href="buy-data.php" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-bold shadow-lg hover:bg-primary-700 transition-all">
                <i class="fas fa-plus mr-1"></i> New
            </a>
        </div>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if (empty($giveaways)): ?>
        <div class="bg-white rounded-3xl border shadow-sm p-12 text-center max-w-lg mx-auto">
            <div class="w-20 h-20 bg-blue-50 text-blue-500 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-gift text-3xl"></i>
            </div>
            <h2 class="text-xl font-bold text-gray-900 mb-2">No Giveaways Yet</h2>
            <p class="text-gray-500 text-sm mb-6">Create your first data giveaway link and share it on social media to engage your followers!</p>
            <a href="buy-data.php" class="inline-block bg-primary-600 text-white px-8 py-3 rounded-xl font-bold shadow-xl hover:shadow-primary-200 hover:-translate-y-0.5 transition-all">
                Start a Giveaway
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($giveaways as $gift): 
                $claimUrl = APP_URL . "/claim.php?code=" . $gift['code'];
            ?>
            <div class="bg-white rounded-2xl border shadow-sm p-5 relative overflow-hidden group">
                <?php if ($gift['status'] === 'claimed'): ?>
                    <div class="absolute top-0 right-0 p-2">
                        <span class="px-2 py-1 bg-green-100 text-green-700 text-[10px] font-bold rounded-lg uppercase tracking-wider">Claimed</span>
                    </div>
                <?php else: ?>
                    <div class="absolute top-0 right-0 p-2">
                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-[10px] font-bold rounded-lg uppercase tracking-wider">Active</span>
                    </div>
                <?php endif; ?>

                <div class="flex items-center gap-4 mb-4">
                    <div class="w-12 h-12 bg-gray-50 rounded-xl flex items-center justify-center text-gray-400 group-hover:bg-primary-50 group-hover:text-primary-500 transition-colors">
                        <i class="fas fa-wifi text-xl"></i>
                    </div>
                    <div>
                        <p class="font-black text-gray-900 text-lg"><?php echo $gift['data_amount']; ?></p>
                        <p class="text-xs text-gray-500"><?php echo $gift['network_name']; ?></p>
                    </div>
                </div>

                <div class="space-y-3 mb-4">
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-400">Created</span>
                        <span class="text-gray-700 font-medium"><?php echo date('M d, Y h:i A', strtotime($gift['created_at'])); ?></span>
                    </div>
                    <?php if ($gift['status'] === 'claimed'): ?>
                    <div class="flex justify-between text-xs">
                        <span class="text-gray-400">Claimed By</span>
                        <span class="text-green-600 font-bold"><?php echo $gift['claimed_by']; ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($gift['status'] === 'pending'): ?>
                <div class="flex gap-2">
                    <input type="text" readonly value="<?php echo $claimUrl; ?>" 
                           class="flex-1 bg-gray-50 border border-gray-100 rounded-lg px-3 py-2 text-[10px] font-mono text-gray-400" id="link-<?php echo $gift['id']; ?>">
                    <button onclick="copyLink('link-<?php echo $gift['id']; ?>')" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg transition-colors">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <?php else: ?>
                    <div class="py-2 px-4 bg-gray-50 rounded-xl border border-dashed border-gray-200 text-center">
                        <p class="text-[10px] text-gray-400">Completed Transaction</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>

<script>
function copyLink(id) {
    const input = document.getElementById(id);
    input.select();
    document.execCommand('copy');
    Toast.success('Link copied to clipboard!');
}
</script>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
