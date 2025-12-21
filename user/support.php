<?php
/**
 * 2SureSub - User Support Tickets
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$success = ''; $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = cleanInput($_POST['subject']);
    $message = cleanInput($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $error = 'Please fill all fields';
    } else {
        dbInsert("INSERT INTO support_tickets (user_id, subject, message, status) VALUES (?, ?, ?, 'open')", [$user['id'], $subject, $message]);
        logActivity('ticket_created', "Created support ticket: $subject", 'support');
        $success = 'Ticket submitted! We\'ll respond soon.';
    }
}

$tickets = dbFetchAll("SELECT * FROM support_tickets WHERE user_id = ? ORDER BY created_at DESC", [$user['id']]);

$pageTitle = 'Support';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">Support</h1>
        <p class="text-gray-500 text-sm">Get help from our team</p>
    </header>
    
    <div class="p-4 lg:p-6">
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-700 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm"><?php echo $error; ?></div><?php endif; ?>
        
        <div class="max-w-2xl mx-auto">
            <!-- New Ticket Form -->
            <div class="bg-white rounded-2xl border shadow-sm p-4 mb-6">
                <h2 class="font-semibold text-gray-900 mb-4 flex items-center gap-2 text-sm">
                    <i class="fas fa-plus-circle text-primary-500"></i> New Ticket
                </h2>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Subject</label>
                        <input type="text" name="subject" required class="w-full px-4 py-3 border rounded-xl text-sm" placeholder="Brief description of your issue">
                    </div>
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Message</label>
                        <textarea name="message" rows="4" required class="w-full px-4 py-3 border rounded-xl text-sm resize-none" placeholder="Describe your issue in detail..."></textarea>
                    </div>
                    <button type="submit" class="w-full py-3 bg-primary-500 text-white font-semibold rounded-xl hover:bg-primary-600">
                        <i class="fas fa-paper-plane mr-2"></i>Submit Ticket
                    </button>
                </form>
            </div>
            
            <!-- Ticket History -->
            <div class="bg-white rounded-2xl border shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b"><h2 class="font-semibold text-sm">My Tickets</h2></div>
                <?php if (empty($tickets)): ?>
                <div class="p-8 text-center">
                    <i class="fas fa-ticket-alt text-gray-300 text-4xl mb-3"></i>
                    <p class="text-gray-500 text-sm">No tickets yet</p>
                </div>
                <?php else: ?>
                <div class="divide-y">
                    <?php foreach ($tickets as $ticket): ?>
                    <div class="p-4">
                        <div class="flex items-center justify-between mb-2">
                            <h3 class="font-medium text-sm"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                            <span class="px-2 py-0.5 text-xs rounded-full <?php
                                echo match($ticket['status']) {
                                    'open' => 'bg-yellow-100 text-yellow-700',
                                    'answered' => 'bg-green-100 text-green-700',
                                    'closed' => 'bg-gray-100 text-gray-600',
                                    default => ''
                                };
                            ?>"><?php echo ucfirst($ticket['status']); ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-2 line-clamp-2"><?php echo htmlspecialchars($ticket['message']); ?></p>
                        
                        <?php if ($ticket['admin_reply']): ?>
                        <div class="bg-blue-50 rounded-lg p-3 mt-2">
                            <p class="text-xs text-blue-600 font-medium mb-1"><i class="fas fa-reply mr-1"></i>Admin Reply</p>
                            <p class="text-sm text-blue-800"><?php echo nl2br(htmlspecialchars($ticket['admin_reply'])); ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <p class="text-xs text-gray-400 mt-2"><?php echo timeAgo($ticket['created_at']); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
