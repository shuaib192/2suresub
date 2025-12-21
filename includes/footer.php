    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- Brand -->
                <div class="col-span-1 md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-gradient-primary rounded-xl flex items-center justify-center">
                            <i class="fas fa-bolt text-white text-xl"></i>
                        </div>
                        <span class="text-2xl font-bold"><?php echo getSetting('site_name', '2SureSub'); ?></span>
                    </div>
                    <p class="text-gray-400 mb-6 max-w-md">
                        Nigeria's most reliable platform for instant data, airtime, cable TV subscriptions, and utility payments.
                    </p>
                    <div class="flex gap-4">
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary-500 transition-colors">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary-500 transition-colors">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-primary-500 transition-colors">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-green-500 transition-colors">
                            <i class="fab fa-whatsapp"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Quick Links -->
                <div>
                    <h4 class="font-semibold text-lg mb-4">Quick Links</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="<?php echo APP_URL; ?>" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="<?php echo APP_URL; ?>/user/buy-data.php" class="hover:text-white transition-colors">Buy Data</a></li>
                        <li><a href="<?php echo APP_URL; ?>/user/buy-airtime.php" class="hover:text-white transition-colors">Buy Airtime</a></li>
                        <li><a href="<?php echo APP_URL; ?>/user/buy-cable.php" class="hover:text-white transition-colors">Cable TV</a></li>
                        <li><a href="<?php echo APP_URL; ?>/user/buy-electricity.php" class="hover:text-white transition-colors">Electricity</a></li>
                    </ul>
                </div>
                
                <!-- Contact -->
                <div>
                    <h4 class="font-semibold text-lg mb-4">Contact Us</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li class="flex items-center gap-2">
                            <i class="fas fa-envelope text-primary-400"></i>
                            <?php echo getSetting('site_email', 'support@2suresub.com'); ?>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-phone text-primary-400"></i>
                            <?php echo getSetting('site_phone', '+234 XXX XXX XXXX'); ?>
                        </li>
                        <li class="flex items-center gap-2">
                            <i class="fas fa-map-marker-alt text-primary-400"></i>
                            <?php echo getSetting('site_address', 'Lagos, Nigeria'); ?>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-12 pt-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <p class="text-gray-400 text-sm">
                    &copy; <?php echo date('Y'); ?> <?php echo getSetting('site_name', '2SureSub'); ?>. All rights reserved.
                </p>
                <div class="flex gap-6 text-sm text-gray-400">
                    <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
                    <a href="#" class="hover:text-white transition-colors">FAQ</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Main JavaScript -->
    <script src="<?php echo APP_URL; ?>/assets/js/app.js"></script>
</body>
</html>
