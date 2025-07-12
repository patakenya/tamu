<?php
session_start();
include_once 'config.php';

// Fetch tiers from database
$stmt = $conn->prepare('SELECT id, name, price, levels_deep, commission_rates, min_withdrawal FROM tiers ORDER BY price ASC');
$stmt->execute();
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user tier if logged in
$user_tier_id = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user_tier_id = $stmt->get_result()->fetch_assoc()['tier_id'];
    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<!-- Hero Section -->
<section class="hero-section relative overflow-hidden">
    <div class="absolute inset-0 bg-gradient-to-r from-indigo-900/90 to-primary/70"></div>
    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24 md:py-32">
        <div class="w-full md:w-2/3 lg:w-1/2 text-white animate-fade-in">
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 leading-tight animate-slide-up">Your Path to Financial Freedom Starts Here</h1>
            <p class="text-lg md:text-xl mb-8 text-indigo-100 animate-slide-up" style="animation-delay: 0.2s;">
                Discover a proven path to financial freedom with our innovative platform, designed for Kenyans to thrive. Whether you're in Nairobi or beyond, earn passive income through referrals, content creation, digital product sales, and affiliate marketing—all with seamless MPESA integration.
            </p>
            <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 animate-slide-up" style="animation-delay: 0.4s;">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="bg-primary text-white px-8 py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Start Earning Now</a>
                <button id="learn-more-btn" class="bg-white text-primary px-8 py-3 rounded-button font-semibold hover:bg-gray-100 transition-colors animate-pulse-cta">Discover How</button>
            </div>
            <div class="mt-12 flex flex-wrap gap-8 animate-slide-up" style="animation-delay: 0.6s;">
                <div class="text-center">
                    <i class="ri-group-line text-3xl text-indigo-200 mb-2"></i>
                    <p class="text-3xl font-bold">15,000+</p>
                    <p class="text-sm text-indigo-200">Active Members</p>
                </div>
                <div class="text-center">
                    <i class="ri-wallet-line text-3xl text-indigo-200 mb-2"></i>
                    <p class="text-3xl font-bold">Ksh 30M+</p>
                    <p class="text-sm text-indigo-200">Paid to Members</p>
                </div>
                <div class="text-center">
                    <i class="ri-star-line text-3xl text-indigo-200 mb-2"></i>
                    <p class="text-3xl font-bold">99%</p>
                    <p class="text-sm text-indigo-200">Satisfaction Rate</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Why Join Us -->
<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-slide-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Why Join Our Platform?</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Unlock multiple income streams and build your network with ease, right from Nairobi or anywhere in Kenya.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-wallet-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Multiple Income Streams</h3>
                <p class="text-gray-600">Earn through referrals, selling digital products, creating content, and promoting affiliate products.</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-smartphone-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">MPESA Integration</h3>
                <p class="text-gray-600">Fast, secure payments and withdrawals via MPESA, tailored for Kenyan users.</p>
            </div>
            <div class="bg-gray-50 p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-rocket-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Scalable Growth</h3>
                <p class="text-gray-600">Start small and scale up with higher tiers for greater earning potential.</p>
            </div>
        </div>
    </div>
</section>

<!-- How It Works with Subscription Tiers -->
<section class="py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-slide-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">How It Works</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Follow these simple steps to start earning today.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-16">
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-user-add-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">1. Sign Up</h3>
                <p class="text-gray-600">Register with your phone number and verify via OTP in seconds.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-bank-card-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">2. Choose a Plan</h3>
                <p class="text-gray-600">Pick a plan (Bronze, Silver, or Gold) to unlock earning opportunities.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-share-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">3. Build Your Network</h3>
                <p class="text-gray-600">Share your referral link or promote products to grow your earnings.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-money-dollar-circle-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">4. Earn Income</h3>
                <p class="text-gray-600">Earn commissions from referrals, content, and affiliate promotions.</p>
            </div>
        </div>
        <!-- Subscription Tiers Sub-Section -->
        <div class="animate-slide-in">
            <h3 class="text-2xl font-semibold text-gray-900 mb-8 text-center">Choose Your Plan</h3>
            <p class="text-lg text-gray-600 max-w-3xl mx-auto text-center mb-8">Select a tier to unlock powerful earning opportunities tailored to your ambitions.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php foreach ($tiers as $index => $tier): ?>
                    <?php
                    $commission_rates = json_decode($tier['commission_rates'], true);
                    $is_popular = $tier['name'] === 'Gold';
                    $is_current = isset($user_tier_id) && $tier['id'] === $user_tier_id;
                    ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 tier-card <?php echo $is_popular ? 'border-2 border-primary scale-105' : ''; ?> animate-slide-in hover:scale-102">
                        <div class="p-6 bg-gradient-to-r <?php echo $tier['name'] === 'Bronze' ? 'from-amber-200 to-amber-400' : ($tier['name'] === 'Silver' ? 'from-primary to-indigo-600' : 'from-yellow-300 to-yellow-400'); ?>">
                            <div class="flex justify-between items-center">
                                <h4 class="text-2xl font-bold <?php echo $tier['name'] === 'Gold' ? 'text-white' : ($tier['name'] === 'Bronze' ? 'text-amber-800' : 'text-yellow-800'); ?>">
                                    <?php echo htmlspecialchars($tier['name']); ?>
                                </h4>
                                <?php if ($is_popular): ?>
                                    <span class="bg-white text-primary text-xs font-bold px-2 py-1 rounded-full">MOST POPULAR</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-4 flex items-end">
                                <span class="text-3xl font-bold <?php echo $tier['name'] === 'Gold' ? 'text-white' : 'text-gray-900'; ?>">
                                    Ksh <?php echo number_format($tier['price'], 2); ?>
                                </span>
                                <span class="ml-1 <?php echo $tier['name'] === 'Gold' ? 'text-yellow-200' : 'text-gray-600'; ?>">/one-time</span>
                            </div>
                        </div>
                        <div class="p-6">
                            <ul class="space-y-2">
                                <li class="flex items-start">
                                    <i class="ri-team-line text-green-500 mr-2 text-lg"></i>
                                    <span class="text-gray-600">Referrals: <?php echo $tier['levels_deep']; ?> levels deep</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                                    <span class="text-gray-600"><?php echo ($commission_rates[1] * 100); ?>% commission on level 1</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="ri-book-open-line text-green-500 mr-2 text-lg"></i>
                                    <span class="text-gray-600">Sell digital products</span>
                                </li>
                                <li class="flex items-start">
                                    <?php if ($tier['name'] !== 'Bronze'): ?>
                                        <i class="ri-article-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600">Earn Ksh 300 per article</span>
                                    <?php else: ?>
                                        <i class="ri-close-line text-red-500 mr-2 text-lg"></i>
                                        <span class="text-gray-400">Content creation</span>
                                    <?php endif; ?>
                                </li>
                                <li class="flex items-start">
                                    <?php if ($tier['name'] === 'Gold'): ?>
                                        <i class="ri-links-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600">Affiliate marketing: Ksh 500/sale</span>
                                    <?php else: ?>
                                        <i class="ri-close-line text-red-500 mr-2 text-lg"></i>
                                        <span class="text-gray-400">Affiliate marketing</span>
                                    <?php endif; ?>
                                </li>
                                <li class="flex items-start">
                                    <?php if ($tier['name'] === 'Gold'): ?>
                                        <i class="ri-vip-crown-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600">Priority support</span>
                                    <?php else: ?>
                                        <i class="ri-close-line text-red-500 mr-2 text-lg"></i>
                                        <span class="text-gray-400">Priority support</span>
                                    <?php endif; ?>
                                </li>
                            </ul>
                            <div class="mt-6 flex justify-center">
                                <a href="<?php echo $is_current ? '#' : (isset($_SESSION['user_id']) ? 'select_tier.php?tier_id=' . $tier['id'] : 'register.php'); ?>" 
                                   class="w-full max-w-xs <?php echo $is_current ? 'bg-gray-300 text-gray-700 cursor-not-allowed' : ($tier['name'] === 'Bronze' ? 'bg-amber-500 hover:bg-amber-600' : ($tier['name'] === 'Silver' ? 'bg-primary hover:bg-indigo-700' : 'bg-yellow-500 hover:bg-yellow-600')); ?> text-white py-2 px-6 rounded-button font-semibold transition-colors animate-pulse-cta text-center">
                                    <?php echo $is_current ? 'Current Tier' : (isset($_SESSION['user_id']) ? 'Upgrade to ' . htmlspecialchars($tier['name']) : 'Join ' . htmlspecialchars($tier['name'])); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Make Money Writing Articles & Selling Digital Books -->
<section class="py-16 bg-gradient-to-r from-indigo-100 to-purple-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-slide-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Earn by Writing Articles & Selling Digital Books</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Create content and digital products to unlock high-margin income streams, with seamless MPESA payouts.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-article-line text-primary text-3xl mr-3"></i>
                    <h3 class="text-xl font-semibold text-gray-900">Write Articles</h3>
                </div>
                <p class="text-gray-600 mb-4">Earn Ksh 300 per approved article as a Silver or Gold-tier member. Share your expertise on business, tech, or lifestyle topics!</p>
                <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Sample Article Preview</h4>
                    <p class="text-gray-600 text-sm line-clamp-3">Discover how to grow your income with our platform. Write engaging content and get paid Ksh 300 per approved submission...</p>
                </div>
                <?php if (isset($_SESSION['user_id']) && $user_tier_id >= 2): ?>
                    <a href="write_article.php" class="inline-block bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Write an Article</a>
                <?php else: ?>
                    <a href="<?php echo isset($_SESSION['user_id']) ? 'select_tier.php' : 'register.php'; ?>" class="inline-block bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta"><?php echo isset($_SESSION['user_id']) ? 'Upgrade to Write' : 'Join to Write'; ?></a>
                <?php endif; ?>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-book-open-line text-primary text-3xl mr-3"></i>
                    <h3 class="text-xl font-semibold text-gray-900">Sell Digital Books</h3>
                </div>
                <p class="text-gray-600 mb-4">Create and sell e-books on any topic, earning high margins on every sale, available to all tiers.</p>
                <div class="mb-4">
                    <img src="sell.png" alt="Sample e-book cover" class="w-full h-40 object-cover rounded-lg">
                </div>
                <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php#sell-books' : 'register.php'; ?>" class="inline-block bg-primary text-white px-6 py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta"><?php echo isset($_SESSION['user_id']) ? 'Start Selling Books' : 'Join to Sell'; ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Platform Features -->
<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16 animate-slide-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Powerful Earning Opportunities</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Discover multiple ways to earn and grow your income with our platform.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-team-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Referral Program</h3>
                <p class="text-gray-600">Invite friends and earn commissions up to 15% from their activities, up to 5 levels deep.</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-book-open-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Sell Digital Products</h3>
                <p class="text-gray-600">Create and sell digital books, earning high margins on each sale.</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-article-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Content Creation</h3>
                <p class="text-gray-600">Write articles and earn Ksh 300 per approved submission.</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-links-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Affiliate Marketing</h3>
                <p class="text-gray-600">Promote popular products and earn Ksh 500 per sale through your links.</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-line-chart-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Real-time Analytics</h3>
                <p class="text-gray-600">Track your earnings, referrals, and performance with an intuitive dashboard.</p>
            </div>
            <div class="p-6 bg-white rounded-lg shadow-sm animate-slide-in">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4">
                    <i class="ri-customer-service-2-line text-primary text-2xl"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">24/7 Support</h3>
                <p class="text-gray-600">Get help anytime via WhatsApp or email from our dedicated team.</p>
            </div>
        </div>
    </div>
</section>

<!-- Success Stories -->
<<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900">What Our Members Say</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Hear from our successful members who have transformed their financial future with our platform.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"I was skeptical at first, but after joining the Gold tier, I've been able to build a team of over 50 people in just 3 months. My monthly earnings have exceeded Ksh 30,000!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">David Mwangi</h4>
                        <p class="text-sm text-gray-500">Gold Member, Nairobi</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-half-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"I started with the Silver tier and was able to upgrade to Gold within a month. The platform is user-friendly and the payment system is seamless. I've already referred 12 people!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">Faith Wambui</h4>
                        <p class="text-sm text-gray-500">Gold Member, Mombasa</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"Even with the Bronze tier, I've been able to earn a decent side income. The commission structure is fair and transparent. I'm planning to upgrade to Silver soon!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">James Odhiambo</h4>
                        <p class="text-sm text-gray-500">Bronze Member, Kisumu</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-12 text-center">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="bg-primary text-white px-8 py-3 rounded-button font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">Join Our Community</a>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="py-16 bg-primary">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl md:text-4xl font-bold text-white mb-6 animate-slide-in">Join Thousands of Kenyans Earning Today</h2>
        <p class="text-xl text-indigo-100 mb-8 max-w-3xl mx-auto animate-slide-in">Start your journey to financial freedom with our proven platform. Sign up now and unlock multiple ways to earn!</p>
        <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4 animate-slide-in">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="bg-white text-primary px-8 py-3 rounded-button font-semibold hover:bg-gray-100 transition-colors animate-pulse-cta">Get Started Now</a>
            <button id="learn-more-btn-cta" class="bg-transparent text-white border border-white px-8 py-3 rounded-button font-semibold hover:bg-white/10 transition-colors animate-pulse-cta">Learn More</button>
        </div>
    </div>
</section>

<!-- Learn More Modal -->
<div id="learn-more-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-lg w-full animate-fade-in">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Discover Our Platform</h3>
        <p class="text-gray-600 mb-6">Our platform empowers you to earn through referrals, digital product sales, content creation, and affiliate marketing. With MPESA integration and a user-friendly dashboard, you can start building your income from anywhere in Kenya. Join now to take control of your financial future!</p>
        <div class="flex justify-end space-x-4">
            <button id="close-learn-more-modal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-button font-semibold hover:bg-gray-400 transition-colors">Close</button>
            <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-indigo-700 transition-colors">Sign Up</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="script.js"></script>
<?php include 'footer.php'; ?>