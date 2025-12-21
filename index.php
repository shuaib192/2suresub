<?php
/**
 * 2SureSub - Landing Page
 */

require_once __DIR__ . '/includes/functions.php';

$siteName = getSetting('site_name', '2SureSub');
$siteTagline = getSetting('site_tagline', 'Your Trusted VTU Platform');

// Get some stats
$totalUsers = dbFetchOne("SELECT COUNT(*) as count FROM users")['count'] ?? 0;
$totalTransactions = dbFetchOne("SELECT COUNT(*) as count FROM transactions WHERE status = 'completed'")['count'] ?? 0;

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>

<!-- Navigation -->
<nav class="fixed top-0 left-0 right-0 z-50 glass">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
            <!-- Logo -->
            <a href="<?php echo APP_URL; ?>" class="flex items-center gap-3">
                <div class="w-12 h-12 bg-gradient-primary rounded-xl flex items-center justify-center shadow-lg shadow-primary-500/30">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
                <span class="text-2xl font-bold text-gray-900"><?php echo $siteName; ?></span>
            </a>
            
            <!-- Desktop Menu -->
            <div class="hidden md:flex items-center gap-8">
                <a href="#services" class="text-gray-600 hover:text-primary-500 font-medium transition-colors">Services</a>
                <a href="#pricing" class="text-gray-600 hover:text-primary-500 font-medium transition-colors">Pricing</a>
                <a href="#about" class="text-gray-600 hover:text-primary-500 font-medium transition-colors">About</a>
                <a href="#contact" class="text-gray-600 hover:text-primary-500 font-medium transition-colors">Contact</a>
            </div>
            
            <!-- Auth Buttons -->
            <div class="flex items-center gap-4">
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo getDashboardUrl(getCurrentUser()['role']); ?>" 
                       class="px-6 py-3 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all">
                        Dashboard
                    </a>
                <?php else: ?>
                    <a href="<?php echo APP_URL; ?>/auth/login.php" 
                       class="text-gray-600 hover:text-primary-500 font-medium transition-colors">
                        Login
                    </a>
                    <a href="<?php echo APP_URL; ?>/auth/register.php" 
                       class="px-6 py-3 bg-gradient-primary text-white font-semibold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all">
                        Get Started
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="relative min-h-screen flex items-center bg-gradient-hero hero-pattern overflow-hidden">
    <!-- Floating Elements -->
    <div class="absolute top-20 left-10 w-72 h-72 bg-blue-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
    <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
    
    <div class="absolute top-1/4 right-1/4 float-slow">
        <div class="w-20 h-20 bg-white/10 rounded-2xl backdrop-blur-sm flex items-center justify-center">
            <i class="fas fa-wifi text-white/60 text-3xl"></i>
        </div>
    </div>
    <div class="absolute bottom-1/3 left-1/4 float-slow" style="animation-delay: 2s;">
        <div class="w-16 h-16 bg-white/10 rounded-2xl backdrop-blur-sm flex items-center justify-center">
            <i class="fas fa-mobile-alt text-white/60 text-2xl"></i>
        </div>
    </div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-32 pb-20">
        <div class="grid lg:grid-cols-2 gap-12 items-center">
            <!-- Content -->
            <div class="text-white">
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 rounded-full backdrop-blur-sm mb-6">
                    <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                    <span class="text-sm font-medium">Trusted by <?php echo number_format($totalUsers); ?>+ Users</span>
                </div>
                
                <h1 class="text-5xl lg:text-6xl font-extrabold leading-tight mb-6">
                    Instant Data & Airtime at Your 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-200 to-purple-200">Fingertips</span>
                </h1>
                
                <p class="text-xl text-blue-100 mb-8 max-w-lg">
                    Top up data, airtime, pay bills, buy cable TV subscriptions, and more with Nigeria's most reliable VTU platform.
                </p>
                
                <div class="flex flex-wrap gap-4">
                    <a href="<?php echo APP_URL; ?>/auth/register.php" 
                       class="group px-8 py-4 bg-white text-primary-600 font-bold rounded-xl shadow-2xl hover:shadow-3xl transition-all flex items-center gap-2">
                        Start Now - It's Free
                        <i class="fas fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
                    </a>
                    <a href="#services" 
                       class="px-8 py-4 bg-white/10 text-white font-semibold rounded-xl backdrop-blur-sm border border-white/20 hover:bg-white/20 transition-all flex items-center gap-2">
                        <i class="fas fa-play-circle"></i>
                        See How It Works
                    </a>
                </div>
                
                <!-- Trust Badges -->
                <div class="flex flex-wrap items-center gap-6 mt-12">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-shield-alt text-green-400 text-xl"></i>
                        <span class="text-sm text-blue-100">Secure Payments</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-bolt text-yellow-400 text-xl"></i>
                        <span class="text-sm text-blue-100">Instant Delivery</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-headset text-purple-400 text-xl"></i>
                        <span class="text-sm text-blue-100">24/7 Support</span>
                    </div>
                </div>
            </div>
            
            <!-- Feature Cards -->
            <div class="relative hidden lg:block">
                <div class="absolute inset-0 bg-gradient-to-r from-primary-500/20 to-purple-500/20 rounded-3xl blur-3xl"></div>
                
                <div class="relative grid grid-cols-2 gap-4">
                    <!-- Network Cards -->
                    <div class="glass-dark rounded-2xl p-6 transform hover:scale-105 transition-transform">
                        <div class="w-14 h-14 bg-yellow-500 rounded-xl flex items-center justify-center mb-4">
                            <span class="font-bold text-white text-xl">MTN</span>
                        </div>
                        <h3 class="text-white font-semibold mb-1">MTN Data</h3>
                        <p class="text-gray-400 text-sm">From ₦150</p>
                    </div>
                    
                    <div class="glass-dark rounded-2xl p-6 transform hover:scale-105 transition-transform mt-8">
                        <div class="w-14 h-14 bg-red-500 rounded-xl flex items-center justify-center mb-4">
                            <span class="font-bold text-white text-lg">Airtel</span>
                        </div>
                        <h3 class="text-white font-semibold mb-1">Airtel Data</h3>
                        <p class="text-gray-400 text-sm">From ₦150</p>
                    </div>
                    
                    <div class="glass-dark rounded-2xl p-6 transform hover:scale-105 transition-transform">
                        <div class="w-14 h-14 bg-green-500 rounded-xl flex items-center justify-center mb-4">
                            <span class="font-bold text-white text-xl">Glo</span>
                        </div>
                        <h3 class="text-white font-semibold mb-1">Glo Data</h3>
                        <p class="text-gray-400 text-sm">From ₦150</p>
                    </div>
                    
                    <div class="glass-dark rounded-2xl p-6 transform hover:scale-105 transition-transform mt-8">
                        <div class="w-14 h-14 bg-emerald-600 rounded-xl flex items-center justify-center mb-4">
                            <span class="font-bold text-white text-sm">9Mobile</span>
                        </div>
                        <h3 class="text-white font-semibold mb-1">9Mobile Data</h3>
                        <p class="text-gray-400 text-sm">From ₦150</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Wave -->
    <div class="absolute bottom-0 left-0 right-0">
        <svg viewBox="0 0 1440 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M0 120L60 105C120 90 240 60 360 45C480 30 600 30 720 37.5C840 45 960 60 1080 67.5C1200 75 1320 75 1380 75L1440 75V120H1380C1320 120 1200 120 1080 120C960 120 840 120 720 120C600 120 480 120 360 120C240 120 120 120 60 120H0Z" fill="#f9fafb"/>
        </svg>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="py-24 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 bg-primary-100 text-primary-600 font-semibold rounded-full text-sm mb-4">
                Our Services
            </span>
            <h2 class="text-4xl font-bold text-gray-900 mb-4">Everything You Need, In One Place</h2>
            <p class="text-xl text-gray-500 max-w-2xl mx-auto">
                From mobile data to utility bills, we've got all your digital needs covered with instant delivery.
            </p>
        </div>
        
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Data -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover">
                <div class="icon-wrapper w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/30">
                    <i class="fas fa-wifi text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Buy Data</h3>
                <p class="text-gray-500 mb-4">Instant data top-up for all networks at unbeatable prices. SME & Gifting data available.</p>
                <a href="<?php echo APP_URL; ?>/user/buy-data.php" class="inline-flex items-center text-primary-500 font-semibold hover:gap-2 transition-all">
                    Buy Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <!-- Airtime -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover">
                <div class="icon-wrapper w-16 h-16 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-green-500/30">
                    <i class="fas fa-phone text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Buy Airtime</h3>
                <p class="text-gray-500 mb-4">Top up any phone instantly with airtime from all major networks. VTU & Share-and-Sell.</p>
                <a href="<?php echo APP_URL; ?>/user/buy-airtime.php" class="inline-flex items-center text-primary-500 font-semibold hover:gap-2 transition-all">
                    Buy Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <!-- Cable TV -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover">
                <div class="icon-wrapper w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-purple-500/30">
                    <i class="fas fa-tv text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Cable TV</h3>
                <p class="text-gray-500 mb-4">Subscribe to DStv, GOtv, StarTimes and Showmax instantly at great rates.</p>
                <a href="<?php echo APP_URL; ?>/user/buy-cable.php" class="inline-flex items-center text-primary-500 font-semibold hover:gap-2 transition-all">
                    Subscribe <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <!-- Electricity -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover">
                <div class="icon-wrapper w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-500 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-yellow-500/30">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Electricity</h3>
                <p class="text-gray-500 mb-4">Buy prepaid and postpaid electricity tokens for all distribution companies.</p>
                <a href="<?php echo APP_URL; ?>/user/buy-electricity.php" class="inline-flex items-center text-primary-500 font-semibold hover:gap-2 transition-all">
                    Pay Bill <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <!-- Exam Pins -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover">
                <div class="icon-wrapper w-16 h-16 bg-gradient-to-br from-red-500 to-red-600 rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-red-500/30">
                    <i class="fas fa-graduation-cap text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Exam Pins</h3>
                <p class="text-gray-500 mb-4">Get WAEC, NECO, NABTEB result checker pins instantly at the best prices.</p>
                <a href="<?php echo APP_URL; ?>/user/exam-pins.php" class="inline-flex items-center text-primary-500 font-semibold hover:gap-2 transition-all">
                    Buy Pin <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
            
            <!-- Reseller -->
            <div class="service-card rounded-2xl p-8 border border-gray-100 card-hover bg-gradient-to-br from-primary-500 to-primary-600 text-white">
                <div class="icon-wrapper w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-6 backdrop-blur-sm">
                    <i class="fas fa-store text-white text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold mb-3">Become a Reseller</h3>
                <p class="text-blue-100 mb-4">Start your VTU business with discounted prices and earn from referrals.</p>
                <a href="<?php echo APP_URL; ?>/auth/register.php?type=reseller" class="inline-flex items-center text-white font-semibold hover:gap-2 transition-all">
                    Join Now <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="py-20 bg-gradient-primary relative overflow-hidden">
    <div class="absolute inset-0 hero-pattern opacity-20"></div>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-8 text-center text-white">
            <div>
                <div class="text-5xl font-bold mb-2" data-count="<?php echo $totalUsers ?: 1000; ?>"><?php echo number_format($totalUsers ?: 1000); ?></div>
                <p class="text-blue-200">Happy Users</p>
            </div>
            <div>
                <div class="text-5xl font-bold mb-2" data-count="<?php echo $totalTransactions ?: 50000; ?>"><?php echo number_format($totalTransactions ?: 50000); ?></div>
                <p class="text-blue-200">Transactions</p>
            </div>
            <div>
                <div class="text-5xl font-bold mb-2">99.9%</div>
                <p class="text-blue-200">Uptime</p>
            </div>
            <div>
                <div class="text-5xl font-bold mb-2">24/7</div>
                <p class="text-blue-200">Support</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works -->
<section id="about" class="py-24 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <span class="inline-block px-4 py-2 bg-primary-100 text-primary-600 font-semibold rounded-full text-sm mb-4">
                Easy Process
            </span>
            <h2 class="text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
            <p class="text-xl text-gray-500 max-w-2xl mx-auto">
                Get started in just 3 simple steps and enjoy instant services.
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6 relative">
                    <span class="text-3xl font-bold text-primary-500">1</span>
                    <div class="absolute -right-4 top-1/2 hidden md:block">
                        <i class="fas fa-arrow-right text-gray-300 text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Create Account</h3>
                <p class="text-gray-500">Sign up for free in less than a minute with your email and phone number.</p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6 relative">
                    <span class="text-3xl font-bold text-primary-500">2</span>
                    <div class="absolute -right-4 top-1/2 hidden md:block">
                        <i class="fas fa-arrow-right text-gray-300 text-2xl"></i>
                    </div>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Fund Wallet</h3>
                <p class="text-gray-500">Add money to your wallet using card, bank transfer, or USSD.</p>
            </div>
            
            <div class="text-center">
                <div class="w-20 h-20 bg-primary-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <span class="text-3xl font-bold text-primary-500">3</span>
                </div>
                <h3 class="text-xl font-bold text-gray-900 mb-3">Make Purchase</h3>
                <p class="text-gray-500">Choose your service, enter details, and get instant delivery.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-20 bg-gray-900 relative overflow-hidden">
    <div class="absolute inset-0">
        <div class="absolute top-0 left-1/4 w-96 h-96 bg-primary-500/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl"></div>
    </div>
    
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative">
        <h2 class="text-4xl font-bold text-white mb-6">Ready to Get Started?</h2>
        <p class="text-xl text-gray-400 mb-8">
            Join thousands of users enjoying instant data, airtime, and more at the best prices.
        </p>
        <a href="<?php echo APP_URL; ?>/auth/register.php" 
           class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-primary text-white font-bold rounded-xl shadow-lg shadow-primary-500/30 hover:shadow-xl hover:shadow-primary-500/40 transition-all pulse-glow">
            Create Free Account
            <i class="fas fa-arrow-right"></i>
        </a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
