<?php
/**
 * 2SureSub - Login Page
 */

require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = cleanInput($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please enter email and password';
    } else {
        $user = dbFetchOne("SELECT * FROM users WHERE email = ? OR username = ?", [$email, $email]);
        
        if ($user && verifyPassword($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account has been suspended. Contact support.';
            } else {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                
                // Log activity
                logActivity('login', 'User logged in', 'auth');
                
                // Redirect to dashboard
                redirect(getDashboardUrl($user['role']), 'Welcome back, ' . $user['first_name'] . '!', 'success');
            }
        } else {
            $error = 'Invalid email or password';
            logActivity('login_failed', 'Failed login attempt for: ' . $email, 'auth');
        }
    }
}

$pageTitle = 'Login';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-hero hero-pattern py-12 px-4">
    <!-- Floating Elements -->
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
        
        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Welcome Back!</h1>
                <p class="text-gray-500">Login to access your dashboard</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <?php echo csrfField(); ?>
                
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email or Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="text" name="email" required
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter email or username"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" name="password" id="password" required
                               class="w-full pl-12 pr-12 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter your password">
                        <button type="button" onclick="togglePassword('password')" 
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fas fa-eye" id="password-toggle"></i>
                        </button>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" 
                               class="w-4 h-4 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="<?php echo APP_URL; ?>/auth/forgot-password.php" 
                       class="text-sm text-primary-500 hover:text-primary-600 font-medium">
                        Forgot Password?
                    </a>
                </div>
                
                <button type="submit" 
                        class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all flex items-center justify-center gap-2">
                    <span>Login</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="mt-8 text-center">
                <p class="text-gray-500">
                    Don't have an account? 
                    <a href="<?php echo APP_URL; ?>/auth/register.php" class="text-primary-500 font-semibold hover:text-primary-600">
                        Create one
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Back to home -->
        <div class="text-center mt-6">
            <a href="<?php echo APP_URL; ?>" class="text-white/80 hover:text-white transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Home
            </a>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '-toggle');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>

</body>
</html>
