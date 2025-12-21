<?php
/**
 * 2SureSub - Email Verification Page
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_helper.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

// Handle Token Verification (via URL link)
if (!empty($token)) {
    $user = dbFetchOne("SELECT id, email FROM users WHERE verification_token = ? AND email_verified_at IS NULL", [$token]);
    
    if ($user) {
        dbExecute("UPDATE users SET email_verified_at = NOW(), verification_token = NULL WHERE id = ?", [$user['id']]);
        $success = "Email verified successfully! You can now login.";
        logActivity('email_verified', "User {$user['email']} verified their email", 'auth');
    } else {
        $error = "Invalid or expired verification link.";
    }
}

// Handle Resend Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    $resendEmail = cleanInput($_POST['email']);
    $user = dbFetchOne("SELECT * FROM users WHERE email = ? AND email_verified_at IS NULL", [$resendEmail]);
    
    if ($user) {
        // Generate new token
        $newToken = bin2hex(random_bytes(32));
        dbExecute("UPDATE users SET verification_token = ? WHERE id = ?", [$newToken, $user['id']]);
        
        if (sendVerificationEmail($resendEmail, $newToken)) {
            $success = "Verification email has been resent to " . htmlspecialchars($resendEmail);
        } else {
            $error = "Failed to send email. Please check your SMTP settings.";
        }
    } else {
        $error = "Account not found or already verified.";
    }
}

$pageTitle = 'Verify Email';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-hero hero-pattern py-12 px-4">
    <div class="w-full max-w-lg relative">
        <div class="text-center mb-8">
            <a href="<?php echo APP_URL; ?>" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl">
                    <i class="fas fa-envelope-open-text text-primary-500 text-3xl"></i>
                </div>
                <span class="text-3xl font-bold text-white"><?php echo getSetting('site_name', '2SureSub'); ?></span>
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-2xl p-8 text-center">
            <?php if ($success): ?>
                <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Success!</h1>
                <p class="text-gray-600 mb-8"><?php echo $success; ?></p>
                <a href="<?php echo APP_URL; ?>/auth/login.php" class="inline-block w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg">
                    Go to Login
                </a>
            <?php else: ?>
                <div class="w-20 h-20 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-paper-plane text-4xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Verify Your Email</h1>
                <p class="text-gray-600 mb-8">
                    We've sent a verification link to your email address<?php echo $email ? ": <strong>".htmlspecialchars($email)."</strong>" : ""; ?>. 
                    Please click the link in the email to continue.
                </p>

                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl text-red-600 text-sm">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <p class="text-sm text-gray-500 mb-4">Didn't receive the email?</p>
                    <button type="submit" name="resend" class="text-primary-600 font-bold hover:underline">
                        Resend Verification Email
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="text-center mt-6">
            <a href="<?php echo APP_URL; ?>/auth/logout.php" class="text-white/80 hover:text-white transition-colors">
                <i class="fas fa-sign-out-alt mr-2"></i> Log Out
            </a>
        </div>
    </div>
</div>

</body>
</html>
