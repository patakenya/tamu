<?php
session_start();
include_once 'config.php';

// Initialize CSRF token for authenticated users
if (!empty($_SESSION['user_id']) && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch tiers from database
$stmt = $conn->prepare('SELECT id, name, price, levels_deep, commission_rates, min_withdrawal FROM tiers ORDER BY price ASC');
if (!$stmt->execute()) {
    error_log('Failed to fetch tiers: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading tiers. Please try again later.';
}
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user tier and balance if logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_tier_name = null;
$available_balance = 0;
if ($user_id) {
    $stmt = $conn->prepare('SELECT t.name, 
                            (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type IN ("payment", "book_sale", "book_commission", "article_payment", "affiliate_commission") AND status = "approved")
                            - (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type IN ("withdrawal", "tier_payment") AND status = "approved") AS balance
                            FROM users u 
                            LEFT JOIN tiers t ON u.tier_id = t.id 
                            WHERE u.id = ?');
    $stmt->bind_param('iii', $user_id, $user_id, $user_id);
    if (!$stmt->execute()) {
        error_log('Failed to fetch user tier: user_id=' . $user_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
        $error = 'An error occurred while loading your profile. Please try again later.';
    } else {
        $result = $stmt->get_result()->fetch_assoc();
        $user_tier_name = $result['name'] ?? null;
        $available_balance = max($result['balance'] ?? 0, 0); // Prevent negative balance
    }
    $stmt->close();
}
?>

<?php include 'header.php'; ?>

<section class="py-8 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <!-- Write-Up -->
        <div class="text-center mb-12 animate-slide-in">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Earn Money with Ease</h2>
            <p class="mt-4 text-base text-gray-600 max-w-3xl mx-auto">
                Join thousands of Kenyans building wealth through our platform. Earn passive income by referring friends, selling digital products, writing articles, and promoting affiliate products—all with seamless M-Pesa integration.
            </p>
        </div>

        <!-- How It Works -->
        <div class="mb-12 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-8 text-center">How It Works</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="ri-user-add-line text-primary text-xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 text-center">1. Register</h4>
                    <p class="text-gray-600 text-sm text-center">
                        Sign up with your phone number and verify via OTP in seconds. No complicated forms, just quick access to start earning.
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="ri-bank-card-line text-primary text-xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 text-center">2. Choose a Tier</h4>
                    <p class="text-gray-600 text-sm text-center">
                        Select a plan (Bronze, Silver, or Gold) to unlock earning opportunities. Pay via M-Pesa Till Number <strong>4178866</strong>.
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="ri-share-line text-primary text-xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 text-center">3. Build Your Network</h4>
                    <p class="text-gray-600 text-sm text-center">
                        Share your unique referral link or promote digital and affiliate products to grow your income effortlessly.
                    </p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow">
                    <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center mb-4 mx-auto">
                        <i class="ri-money-dollar-circle-line text-primary text-xl"></i>
                    </div>
                    <h4 class="text-lg font-semibold text-gray-900 mb-3 text-center">4. Earn & Withdraw</h4>
                    <p class="text-gray-600 text-sm text-center">
                        Earn commissions from referrals, sales, articles, and affiliate promotions. Withdraw via M-Pesa after admin verification.
                    </p>
                </div>
            </div>
            <div class="mt-10 text-center">
                <a href="<?php echo $user_id ? 'dashboard.php' : 'register.php'; ?>" 
                   class="bg-gradient-to-r from-primary to-indigo-700 text-white px-8 py-3 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">
                    Get Started Now
                </a>
            </div>
        </div>

        <!-- Deposit Section -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="deposit-section mb-12 text-center">
                <p class="text-sm text-gray-600 mb-3">Need funds for purchases or tier upgrades?</p>
                <a href="deposit.php" 
                   class="inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-4 py-2 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                   aria-label="Deposit funds via M-Pesa" 
                   title="Deposit funds to your account">
                    Deposit Funds
                </a>
            </div>
        <?php endif; ?>

        <!-- Upgrade Your Tier -->
        <div id="upgrade-tier" class="mb-12 bg-white p-6 rounded-xl shadow-md animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-6 flex justify-between items-center">
                Upgrade Your Tier
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="upgrade-tier">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($tiers as $tier): ?>
                        <?php $commission_rates = json_decode($tier['commission_rates'] ?? '{"1":0.1}', true); ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 tier-card animate-slide-in hover:scale-102">
                            <div class="p-6 bg-gradient-to-r <?php echo $tier['name'] === 'Bronze' ? 'from-amber-400 to-amber-600' : ($tier['name'] === 'Silver' ? 'from-indigo-500 to-blue-600' : 'from-yellow-400 to-yellow-600'); ?>">
                                <h4 class="text-xl font-bold text-white"><?php echo htmlspecialchars($tier['name']); ?></h4>
                                <div class="mt-3 flex items-end">
                                    <span class="text-2xl font-bold text-white">Ksh <?php echo htmlspecialchars(number_format($tier['price'], 2)); ?></span>
                                    <span class="ml-2 text-white opacity-80 text-sm">/one-time</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <i class="ri-team-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600 text-sm">Referrals: <?php echo htmlspecialchars($tier['levels_deep'] ?? 1); ?> levels</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600 text-sm"><?php echo htmlspecialchars(($commission_rates[1] * 100)); ?>% commission on level 1</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-book-open-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600 text-sm">Sell digital products</span>
                                    </li>
                                    <li class="flex items-start">
                                        <?php if ($tier['name'] !== 'Bronze'): ?>
                                            <i class="ri-article-line text-green-500 mr-2 text-lg"></i>
                                            <span class="text-gray-600 text-sm">Earn Ksh 300 per article</span>
                                        <?php else: ?>
                                            <i class="ri-close-line text-red-500 mr-2 text-lg"></i>
                                            <span class="text-gray-400 text-sm">Content creation</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="flex items-start">
                                        <?php if ($tier['name'] === 'Gold'): ?>
                                            <i class="ri-links-line text-green-500 mr-2 text-lg"></i>
                                            <span class="text-gray-600 text-sm">Affiliate marketing: Ksh 500/sale</span>
                                        <?php else: ?>
                                            <i class="ri-close-line text-red-500 mr-2 text-lg"></i>
                                            <span class="text-gray-400 text-sm">Affiliate marketing</span>
                                        <?php endif; ?>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-wallet-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600 text-sm">Min. withdrawal: Ksh <?php echo htmlspecialchars(number_format($tier['min_withdrawal'], 2)); ?></span>
                                    </li>
                                </ul>
                                <form action="pay.php" method="GET" class="mt-6">
                                    <input type="hidden" name="tier_id" value="<?php echo $tier['id']; ?>">
                                    <button type="submit" name="request_upgrade" 
                                            class="w-full bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta <?php echo ($user_id && $user_tier_name == $tier['name'] || $available_balance < $tier['price']) ? 'opacity-60 cursor-not-allowed' : ''; ?>" 
                                            <?php echo ($user_id && $user_tier_name == $tier['name'] || $available_balance < $tier['price']) ? 'disabled aria-disabled="true"' : 'aria-disabled="false"'; ?> 
                                            aria-label="<?php echo ($user_id && $user_tier_name == $tier['name']) ? 'Current tier selected' : ($available_balance < $tier['price'] ? 'Insufficient balance for ' . htmlspecialchars($tier['name']) : 'Upgrade to ' . htmlspecialchars($tier['name'])); ?>">
                                        <?php echo ($user_id && $user_tier_name == $tier['name']) ? 'Current Tier' : 'Join ' . htmlspecialchars($tier['name']); ?>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
// Toggle sections with localStorage persistence
document.querySelectorAll('.toggle-section').forEach(button => {
    const sectionId = button.getAttribute('data-section') || 'section-' + Math.random().toString(36).substr(2, 9);
    const section = button.parentElement.nextElementSibling;
    const icon = button.querySelector('i');

    // Load saved state from localStorage
    const isOpen = localStorage.getItem(`section-${sectionId}`) === 'open';
    if (isOpen) {
        section.classList.remove('hidden');
        icon?.classList.replace('ri-arrow-down-s-line', 'ri-arrow-up-s-line');
    } else {
        section.classList.add('hidden');
        icon?.classList.replace('ri-arrow-up-s-line', 'ri-arrow-down-s-line');
    }

    button.addEventListener('click', () => {
        section.classList.toggle('hidden');
        const isNowOpen = !section.classList.contains('hidden');
        icon?.classList.toggle('ri-arrow-down-s-line', !isNowOpen);
        icon?.classList.toggle('ri-arrow-up-s-line', isNowOpen);
        localStorage.setItem(`section-${sectionId}`, isNowOpen ? 'open' : 'closed');
    });
});
</script>

<?php include 'footer.php'; ?>
