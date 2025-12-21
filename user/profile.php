<?php
/**
 * 2SureSub - Profile (Mobile Responsive)
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user = getCurrentUser();
$wallet = getUserWallet($user['id']);
$error = ''; $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = cleanInput($_POST['first_name']);
    $lastName = cleanInput($_POST['last_name']);
    $phone = cleanInput($_POST['phone']);
    
    if (empty($firstName) || empty($lastName) || empty($phone)) {
        $error = 'All fields are required';
    } else {
        dbExecute("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?", [$firstName, $lastName, $phone, $user['id']]);
        logActivity('profile_update', 'Profile updated', 'profile');
        $success = 'Profile updated!';
        $user = getCurrentUser();
    }
}

$pageTitle = 'Profile';
include __DIR__ . '/../includes/header.php';
include __DIR__ . '/../includes/sidebar.php';
?>

<main class="min-h-screen pt-16 lg:pt-0 lg:ml-72">
    <header class="bg-white border-b border-gray-100 px-4 py-4 sticky top-16 lg:top-0 z-20">
        <h1 class="text-lg lg:text-2xl font-bold text-gray-900">My Profile</h1>
    </header>
    
    <div class="p-4 lg:p-6 max-w-lg mx-auto">
        <?php if ($error): ?><div class="mb-4 p-3 bg-red-50 text-red-600 rounded-xl text-sm"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="mb-4 p-3 bg-green-50 text-green-600 rounded-xl text-sm"><?php echo $success; ?></div><?php endif; ?>
        
        <!-- Profile Card -->
        <div class="bg-white rounded-2xl border shadow-sm p-6 mb-4">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-16 h-16 bg-primary-100 rounded-full flex items-center justify-center">
                    <span class="text-2xl font-bold text-primary-600"><?php echo strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)); ?></span>
                </div>
                <div>
                    <h2 class="text-lg font-bold"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h2>
                    <p class="text-gray-500 text-sm">@<?php echo $user['username']; ?></p>
                    <span class="inline-block mt-1 px-3 py-1 bg-primary-100 text-primary-700 text-xs rounded-full capitalize"><?php echo $user['role']; ?></span>
                </div>
            </div>
            
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" value="<?php echo $user['first_name']; ?>" required class="w-full px-4 py-3 border rounded-xl">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" value="<?php echo $user['last_name']; ?>" required class="w-full px-4 py-3 border rounded-xl">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" value="<?php echo $user['email']; ?>" disabled class="w-full px-4 py-3 border rounded-xl bg-gray-50">
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="tel" name="phone" value="<?php echo $user['phone']; ?>" required class="w-full px-4 py-3 border rounded-xl">
                </div>
                <button type="submit" class="w-full py-3 bg-primary-500 text-white font-semibold rounded-xl hover:bg-primary-600">Update Profile</button>
            </form>
        </div>
        
        <!-- Referral Code -->
        <div class="bg-white rounded-2xl border shadow-sm p-4">
            <h3 class="font-semibold text-gray-900 mb-3 text-sm">Referral Code</h3>
            <div class="flex items-center gap-3">
                <input type="text" value="<?php echo $user['referral_code']; ?>" readonly class="flex-1 px-4 py-3 border rounded-xl bg-gray-50 font-mono">
                <button onclick="navigator.clipboard.writeText('<?php echo $user['referral_code']; ?>'); this.innerHTML='<i class=\'fas fa-check\'></i>'" class="px-4 py-3 bg-gray-100 rounded-xl hover:bg-gray-200">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>
    </div>
</main>
<script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body></html>
