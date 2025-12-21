<?php
/**
 * 2SureSub - Registration Page
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/email_helper.php';

// Redirect if already logged in
redirectIfLoggedIn();

$error = '';
$success = '';
$isReseller = isset($_GET['type']) && $_GET['type'] === 'reseller';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = cleanInput($_POST['first_name'] ?? '');
    $lastName = cleanInput($_POST['last_name'] ?? '');
    $username = cleanInput($_POST['username'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $phone = cleanInput($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $accountType = $_POST['account_type'] ?? 'user';
    $referralCode = cleanInput($_POST['referral_code'] ?? '');
    
    // Validation
    if (empty($firstName) || empty($lastName) || empty($username) || empty($email) || empty($phone) || empty($password)) {
        $error = 'All fields are required';
    } elseif (!isValidEmail($email)) {
        $error = 'Please enter a valid email address';
    } elseif (!isValidPhone($phone)) {
        $error = 'Please enter a valid phone number';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters';
    } else {
        // Check if email exists
        $existingEmail = dbFetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($existingEmail) {
            $error = 'Email already registered';
        } else {
            // Check if username exists
            $existingUsername = dbFetchOne("SELECT id FROM users WHERE username = ?", [$username]);
            if ($existingUsername) {
                $error = 'Username already taken';
            }
        }
    }
    
    if (empty($error)) {
        // Check referral code
        $referredBy = null;
        if (!empty($referralCode)) {
            $referrer = dbFetchOne("SELECT id FROM users WHERE referral_code = ?", [$referralCode]);
            if ($referrer) {
                $referredBy = $referrer['id'];
            }
        }
        
        // Determine role
        $role = ($accountType === 'reseller') ? 'reseller' : 'user';
        
        // Generate referral code for new user
        $newReferralCode = generateReferralCode();
        
        // Email Verification Token
        $verificationToken = bin2hex(random_bytes(32));
        $isVerificationEnabled = (getSetting('email_verification', '0') === '1');
        
        // Create user
        $userId = dbInsert(
            "INSERT INTO users (first_name, last_name, username, email, phone, password, role, referral_code, referred_by, verification_token, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')",
            [$firstName, $lastName, $username, $email, $phone, hashPassword($password), $role, $newReferralCode, $referredBy, $verificationToken]
        );
        
        if ($userId) {
            // Create wallet
            dbInsert("INSERT INTO wallets (user_id, balance) VALUES (?, 0)", [$userId]);
            
            // Log activity
            logActivity('registration', "New $role account created: $email", 'auth', null, ['user_id' => $userId]);
            
            // Give referral bonus if applicable
            if ($referredBy) {
                $bonusAmount = getSetting('referral_bonus', 100);
                creditWallet($referredBy, $bonusAmount, "Referral bonus for $username", generateReference('REF'));
                createNotification($referredBy, 'New Referral!', "You earned ₦$bonusAmount from referring $username", 'success');
            }
            
            // Create welcome notification
            createNotification($userId, 'Welcome to ' . getSetting('site_name', '2SureSub') . '!', 
                'Your account has been created successfully. Fund your wallet to get started.', 'success');
            
            if ($isVerificationEnabled) {
                sendVerificationEmail($email, $verificationToken);
                redirect(APP_URL . '/auth/verify.php?email=' . urlencode($email), 'Account created! Please check your email to verify your account.', 'success');
            } else {
                redirect(APP_URL . '/auth/login.php', 'Registration successful! Please login.', 'success');
            }
        } else {
            $error = 'Registration failed. Please try again.';
        }
    }
}

$pageTitle = 'Create Account';
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gradient-hero hero-pattern py-12 px-4">
    <!-- Floating Elements -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
    
    <div class="w-full max-w-lg relative">
        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="<?php echo APP_URL; ?>" class="inline-flex items-center gap-3">
                <div class="w-14 h-14 bg-white rounded-2xl flex items-center justify-center shadow-xl">
                    <i class="fas fa-bolt text-primary-500 text-3xl"></i>
                </div>
                <span class="text-3xl font-bold text-white"><?php echo getSetting('site_name', '2SureSub'); ?></span>
            </a>
        </div>
        
        <!-- Register Card -->
        <div class="bg-white rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Create Account</h1>
                <p class="text-gray-500">Join thousands of users enjoying instant services</p>
            </div>
            
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-xl flex items-center gap-3 text-red-600">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" data-validate>
                <?php echo csrfField(); ?>
                
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <input type="text" name="first_name" required
                               class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="John"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <input type="text" name="last_name" required
                               class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Doe"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">@</span>
                        <input type="text" name="username" required
                               class="w-full pl-10 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="johndoe"
                               value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" name="email" required
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="john@example.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-phone"></i>
                        </span>
                        <input type="tel" name="phone" required data-validate-phone
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="08012345678"
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                   class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="••••••">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" required
                                   class="w-full px-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                                   placeholder="••••••">
                        </div>
                    </div>
                </div>
                
                <!-- Account Type -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="relative cursor-pointer">
                            <input type="radio" name="account_type" value="user" class="peer sr-only" <?php echo !$isReseller ? 'checked' : ''; ?>>
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 peer-checked:bg-primary-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-user text-gray-500"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">User</p>
                                        <p class="text-xs text-gray-500">Personal use</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                        
                        <label class="relative cursor-pointer">
                            <input type="radio" name="account_type" value="reseller" class="peer sr-only" <?php echo $isReseller ? 'checked' : ''; ?>>
                            <div class="p-4 border-2 border-gray-200 rounded-xl peer-checked:border-primary-500 peer-checked:bg-primary-50 transition-all">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gray-100 peer-checked:bg-primary-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-store text-gray-500"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900">Reseller</p>
                                        <p class="text-xs text-gray-500">Sell to others</p>
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Referral Code -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Referral Code (Optional)</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="fas fa-gift"></i>
                        </span>
                        <input type="text" name="referral_code"
                               class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-all"
                               placeholder="Enter referral code"
                               value="<?php echo htmlspecialchars($_POST['referral_code'] ?? $_GET['ref'] ?? ''); ?>">
                    </div>
                </div>
                
                <!-- Terms -->
                <div class="mb-6">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="terms" required
                               class="w-4 h-4 mt-0.5 rounded border-gray-300 text-primary-500 focus:ring-primary-500">
                        <span class="text-sm text-gray-600">
                            I agree to the <a href="#" class="text-primary-500 hover:underline">Terms of Service</a> 
                            and <a href="#" class="text-primary-500 hover:underline">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <button type="submit" 
                        class="w-full py-4 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all flex items-center justify-center gap-2">
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <div class="mt-8 text-center">
                <p class="text-gray-500">
                    Already have an account? 
                    <a href="<?php echo APP_URL; ?>/auth/login.php" class="text-primary-500 font-semibold hover:text-primary-600">
                        Login
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

</body>
</html>
