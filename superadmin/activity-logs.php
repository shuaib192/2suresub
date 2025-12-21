<?php
/**
 * 2SureSub - Activity Logs (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireRole(ROLE_SUPERADMIN);

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50; $offset = ($page - 1) * $perPage;
$moduleFilter = $_GET['module'] ?? '';

$where = "1=1"; $params = [];
if ($moduleFilter) { $where .= " AND module = ?"; $params[] = $moduleFilter; }

$totalCount = dbFetchOne("SELECT COUNT(*) as c FROM activity_logs WHERE $where", $params)['c'];
$totalPages = ceil($totalCount / $perPage);
$logs = dbFetchAll("SELECT al.*, u.username FROM activity_logs al LEFT JOIN users u ON al.user_id = u.id WHERE $where ORDER BY al.created_at DESC LIMIT $perPage OFFSET $offset", $params);
$modules = dbFetchAll("SELECT DISTINCT module FROM activity_logs ORDER BY module");

$pageTitle = 'Activity Logs';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Activity Logs</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <!-- Filter -->
        <div class="bg-white rounded-xl border p-3 mb-4 flex items-center gap-3">
            <select onchange="window.location.href='?module='+this.value" class="px-3 py-2 border rounded-lg text-sm">
                <option value="">All Modules</option>
                <?php foreach ($modules as $m): ?>
                <option value="<?php echo $m['module']; ?>" <?php echo $moduleFilter === $m['module'] ? 'selected' : ''; ?>><?php echo ucfirst($m['module']); ?></option>
                <?php endforeach; ?>
            </select>
            <span class="text-gray-500 text-xs"><?php echo number_format($totalCount); ?> logs</span>
        </div>
        
        <!-- Logs -->
        <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
            <div class="divide-y">
                <?php foreach ($logs as $log): ?>
                <div class="p-3">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-medium text-sm"><?php echo $log['username'] ?: 'System'; ?></span>
                        <span class="text-xs text-gray-500"><?php echo date('M j, g:i A', strtotime($log['created_at'])); ?></span>
                    </div>
                    <p class="text-sm text-gray-700"><?php echo $log['action']; ?></p>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-600 text-xs rounded"><?php echo $log['module']; ?></span>
                        <span class="text-xs text-gray-400 font-mono"><?php echo $log['ip_address']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 border-t flex justify-between items-center">
                <span class="text-xs text-gray-500">Page <?php echo $page; ?>/<?php echo $totalPages; ?></span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?><a href="?page=<?php echo $page-1; ?>&module=<?php echo $moduleFilter; ?>" class="px-4 py-2 bg-gray-100 rounded-lg text-sm">Prev</a><?php endif; ?>
                    <?php if ($page < $totalPages): ?><a href="?page=<?php echo $page+1; ?>&module=<?php echo $moduleFilter; ?>" class="px-4 py-2 bg-primary-500 text-white rounded-lg text-sm">Next</a><?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
