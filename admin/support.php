<?php
/**
 * 2SureSub - Support Tickets (Admin)
 */
require_once __DIR__ . '/../includes/auth.php';
requireMinRole(ROLE_ADMIN);

$success = '';
$statusFilter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticketId = (int)$_POST['ticket_id'];
    $action = $_POST['action'] ?? '';
    
    if ($action === 'reply') {
        $reply = cleanInput($_POST['reply']);
        dbExecute("UPDATE support_tickets SET admin_reply = ?, status = 'answered', answered_at = NOW(), answered_by = ? WHERE id = ?", [$reply, getCurrentUser()['id'], $ticketId]);
        logActivity('ticket_reply', "Replied to ticket $ticketId", 'support');
        $success = 'Reply sent!';
    } elseif ($action === 'close') {
        dbExecute("UPDATE support_tickets SET status = 'closed' WHERE id = ?", [$ticketId]);
        $success = 'Ticket closed!';
    }
}

$where = "1=1"; $params = [];
if ($statusFilter) { $where .= " AND status = ?"; $params[] = $statusFilter; }

$tickets = dbFetchAll("SELECT t.*, u.username, u.email FROM support_tickets t JOIN users u ON t.user_id = u.id WHERE $where ORDER BY t.created_at DESC", $params);

$pageTitle = 'Support Tickets';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Support Tickets</h1>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Filter -->
        <div class="flex gap-2 mb-4 overflow-x-auto pb-2">
            <a href="?" class="px-4 py-2 rounded-lg text-sm whitespace-nowrap <?php echo !$statusFilter ? 'bg-primary-500 text-white' : 'bg-white border text-gray-600'; ?>">All</a>
            <a href="?status=open" class="px-4 py-2 rounded-lg text-sm whitespace-nowrap <?php echo $statusFilter === 'open' ? 'bg-primary-500 text-white' : 'bg-white border text-gray-600'; ?>">Open</a>
            <a href="?status=answered" class="px-4 py-2 rounded-lg text-sm whitespace-nowrap <?php echo $statusFilter === 'answered' ? 'bg-primary-500 text-white' : 'bg-white border text-gray-600'; ?>">Answered</a>
            <a href="?status=closed" class="px-4 py-2 rounded-lg text-sm whitespace-nowrap <?php echo $statusFilter === 'closed' ? 'bg-primary-500 text-white' : 'bg-white border text-gray-600'; ?>">Closed</a>
        </div>
        
        <!-- Tickets -->
        <?php if (empty($tickets)): ?>
        <div class="bg-white rounded-2xl border p-8 text-center">
            <i class="fas fa-ticket-alt text-gray-300 text-4xl mb-3"></i>
            <p class="text-gray-500 text-sm">No tickets found</p>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($tickets as $ticket): ?>
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 text-xs rounded-full font-medium <?php
                            echo match($ticket['status']) {
                                'open' => 'bg-yellow-100 text-yellow-700',
                                'answered' => 'bg-blue-100 text-blue-700',
                                'closed' => 'bg-gray-100 text-gray-600',
                                default => ''
                            };
                        ?>"><?php echo ucfirst($ticket['status']); ?></span>
                        <span class="text-sm font-medium">#<?php echo $ticket['id']; ?></span>
                    </div>
                    <span class="text-xs text-gray-500"><?php echo timeAgo($ticket['created_at']); ?></span>
                </div>
                <div class="p-4">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                            <span class="text-primary-600 font-bold text-xs"><?php echo strtoupper(substr($ticket['username'], 0, 1)); ?></span>
                        </div>
                        <div>
                            <p class="font-medium text-sm"><?php echo $ticket['username']; ?></p>
                            <p class="text-xs text-gray-500"><?php echo $ticket['email']; ?></p>
                        </div>
                    </div>
                    
                    <h3 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                    <p class="text-sm text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                    
                    <?php if ($ticket['admin_reply']): ?>
                    <div class="bg-blue-50 rounded-xl p-3 mb-4">
                        <p class="text-xs text-blue-600 font-medium mb-1"><i class="fas fa-reply mr-1"></i>Your Reply</p>
                        <p class="text-sm text-blue-800"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket['status'] !== 'closed'): ?>
                    <form method="POST" class="flex flex-col gap-2">
                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                        <textarea name="reply" rows="2" placeholder="Type your reply..." class="w-full px-3 py-2 border rounded-xl text-sm resize-none"></textarea>
                        <div class="flex gap-2">
                            <button type="submit" name="action" value="reply" class="px-4 py-2 bg-primary-500 text-white text-sm font-medium rounded-lg"><i class="fas fa-reply mr-1"></i>Reply</button>
                            <button type="submit" name="action" value="close" class="px-4 py-2 bg-gray-100 text-gray-600 text-sm font-medium rounded-lg">Close</button>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
