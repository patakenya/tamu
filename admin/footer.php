
<footer class="bg-gray-900 text-white py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (isset($_SESSION['admin_id'])): ?>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="text-2xl font-['Pacifico'] text-white mb-4">WealthGrow Admin</div>
                    <p class="text-gray-400 mb-4">Managing financial freedom through network marketing since 2022.</p>
                    <div class="flex space-x-4">
                        <a href="https://facebook.com" class="text-gray-400 hover:text-white" aria-label="Facebook">
                            <div class="w-8 h-8 flex items-center justify-center">
                                <i class="ri-facebook-fill ri-lg"></i>
                            </div>
                        </a>
                        <a href="https://x.com" class="text-gray-400 hover:text-white" aria-label="Twitter">
                            <div class="w-8 h-8 flex items-center justify-center">
                                <i class="ri-twitter-x-fill ri-lg"></i>
                            </div>
                        </a>
                        <a href="https://instagram.com" class="text-gray-400 hover:text-white" aria-label="Instagram">
                            <div class="w-8 h-8 flex items-center justify-center">
                                <i class="ri-instagram-fill ri-lg"></i>
                            </div>
                        </a>
                        <a href="https://whatsapp.com" class="text-gray-400 hover:text-white" aria-label="WhatsApp">
                            <div class="w-8 h-8 flex items-center justify-center">
                                <i class="ri-whatsapp-fill ri-lg"></i>
                            </div>
                        </a>
                    </div>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Admin Links</h3>
                    <ul class="space-y-2">
                        <li><a href="./dashboard.php" class="text-gray-400 hover:text-white">Dashboard</a></li>
                        <li><a href="./articles.php" class="text-gray-400 hover:text-white">Articles</a></li>
                        <li><a href="./books.php" class="text-gray-400 hover:text-white">Books</a></li>
                        <li><a href="./add_books.php" class="text-gray-400 hover:text-white">Add Books</a></li>
                        <li><a href="./affiliate_products.php" class="text-gray-400 hover:text-white">Affiliate Products</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Legal</h3>
                    <ul class="space-y-2">
                        <li><a href="./terms.php" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="./privacy.php" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="./refund.php" class="text-gray-400 hover:text-white">Refund Policy</a></li>
                        <li><a href="./cookie.php" class="text-gray-400 hover:text-white">Cookie Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact Us</h3>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-400 mr-2 mt-0.5">
                                <i class="ri-map-pin-line"></i>
                            </div>
                            <span class="text-gray-400">Westlands, Nairobi, Kenya</span>
                        </li>
                        <li class="flex items-start">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-400 mr-2 mt-0.5">
                                <i class="ri-phone-line"></i>
                            </div>
                            <span class="text-gray-400">+254 712 345 678</span>
                        </li>
                        <li class="flex items-start">
                            <div class="w-5 h-5 flex items-center justify-center text-gray-400 mr-2 mt-0.5">
                                <i class="ri-mail-line"></i>
                            </div>
                            <span class="text-gray-400">support@mlmplatform.co.ke</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-400 text-sm">© 2025 WealthGrow Admin. All rights reserved.</p>
            </div>
        <?php else: ?>
            <div class="text-center text-gray-400">
                <p class="text-sm">Please log in to access the admin panel.</p>
            </div>
        <?php endif; ?>
    </div>
</footer>
