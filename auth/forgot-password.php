<?php
/**
 * 2SureSub - Forgot Password
 */

require_once __DIR__ . '/../includes/auth.php';

redirectIfLoggedIn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Please enter your email address';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } else {
        $user = dbFetchOne("SELECT id, first_name, email FROM users WHERE email = ?", [$email]);
        
        if ($user) {
            // Generate reset token (in production, send this via email)
            $token = bin2hex(random_bytes(32));
            $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Store token (you'd need a password_resets table in production)
            // For now, just show success message
            logActivity('password_reset_request', "Password reset requested for: $email", 'auth');
            
            $success = 'If an account exists with this email, you will receive password reset instructions.';
        } else {
            // Don't reveal if email exists
            $success = 'If an account exists with this email, you will receive password reset instructions.';
        }
    }
}

$pageTitle = 'Forgot Password';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-hero hero-pattern py-12 px-4">
    <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
    
    <div class="w-full max-w-md relative">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo APP_URL; ?>" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl">
                    <i class="fas fa-bolt text-primary-500 text-3xl"></i>
                </div>
                <span class="text-3xl font-bold text-white"><?php echo getSetting('site_name', '2SureSub'); ?></span>
            </a>
        </div>
        
        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-key text-primary-500 text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Forgot Password?</h1>
                <p class="text-gray-500">Enter your email to receive reset instructions</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-600">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $success; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <?php echo csrfField(); ?>
                
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter your email">
                    </div>
                </div>
                
                <button type="submit" 
                        class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all">
                    Reset Password
                </button>
            </form>
            
            <div class="mt-8 text-center">
                <a href="<?php echo APP_URL; ?>/auth/login.php" class="text-primary-500 font-semibold hover:text-primary-600">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Login
                </a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
