<?php
/**
 * 2SureSub - Global Header
 */

require_once __DIR__ . '/functions.php';

$siteName = getSetting('site_name', '2SureSub');
$siteTagline = getSetting('site_tagline', 'Your Trusted VTU Platform');
$primaryColor = getSetting('primary_color', '#3B82F6');

$currentUser = getCurrentUser();
$wallet = $currentUser ? getUserWallet($currentUser['id']) : null;
$notificationCount = $currentUser ? getUnreadNotificationCount($currentUser['id']) : 0;
$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $siteTagline; ?>">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' . $siteName : $siteName; ?></title>
    
    <!-- Compiled Tailwind CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/custom.css">
</head>
<body class="bg-gray-50 antialiased">

<?php if ($flash): ?>
<div id="flash-message" class="fixed top-4 right-4 z-50 toast-enter">
    <div class="flex items-center gap-3 px-6 py-4 rounded-xl shadow-lg <?php 
        echo match($flash['type']) {
            'success' => 'bg-green-500 text-white',
            'error' => 'bg-red-500 text-white',
            'warning' => 'bg-yellow-500 text-white',
            default => 'bg-blue-500 text-white'
        };
    ?>">
        <i class="fas <?php 
            echo match($flash['type']) {
                'success' => 'fa-check-circle',
                'error' => 'fa-times-circle',
                'warning' => 'fa-exclamation-triangle',
                default => 'fa-info-circle'
            };
        ?>"></i>
        <span class="font-medium"><?php echo $flash['message']; ?></span>
        <button onclick="this.parentElement.parentElement.remove()" class="ml-2 hover:opacity-75">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>
<script>
    setTimeout(() => {
        const flash = document.getElementById('flash-message');
        if (flash) flash.remove();
    }, 5000);
</script>
<?php endif; ?>
