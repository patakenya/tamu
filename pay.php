<?php
session_start();
include_once 'config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch user profile and balance
$stmt = $conn->prepare('SELECT u.full_name, u.tier_id, t.name AS tier_name, t.min_withdrawal,
                        (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type IN ("payment", "book_sale", "book_commission", "article_payment", "affiliate_commission") AND status = "approved")
                        - (SELECT COALESCE(SUM(amount), 0) FROM transactions WHERE user_id = ? AND type IN ("withdrawal", "tier_payment") AND status = "approved") AS balance
                        FROM users u
                        LEFT JOIN tiers t ON u.tier_id = t.id
                        WHERE u.id = ?');
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$user) {
    $error = 'User not found.';
    error_log("User fetch failed: user_id=$user_id, error=User not found, time=" . date('Y-m-d H:i:s'));
}
$tier_name = $user['tier_name'] ?? 'No Tier';
$available_balance = max($user['balance'] ?? 0, 0); // Prevent negative balance
$min_withdrawal = $user['min_withdrawal'] ?? 500;

// Fetch tiers for validation
$stmt = $conn->prepare('SELECT id, name, price, levels_deep, commission_rates FROM tiers WHERE id IN (1, 2, 3) ORDER BY price ASC');
$stmt->execute();
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$valid_tiers = array_column($tiers, null, 'id'); // Key by id for quick lookup

// Handle tier upgrade request
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['tier_id'])) {
    $tier_id = (int)$_GET['tier_id'];
    
    // Validate tier_id
    if (!isset($valid_tiers[$tier_id])) {
        $error = 'Invalid tier selected.';
        error_log("Tier upgrade failed: user_id=$user_id, tier_id=$tier_id, error=Invalid tier, time=" . date('Y-m-d H:i:s'));
    } elseif ($user['tier_id'] >= $tier_id && $user['tier_id'] != 0) {
        $error = 'You cannot downgrade to a lower tier.';
        error_log("Tier upgrade failed: user_id=$user_id, tier_id=$tier_id, error=Cannot downgrade, current_tier_id={$user['tier_id']}, time=" . date('Y-m-d H:i:s'));
    } elseif ($available_balance < $valid_tiers[$tier_id]['price']) {
        $error = 'Insufficient balance to upgrade to ' . htmlspecialchars($valid_tiers[$tier_id]['name']) . '.';
        error_log("Tier upgrade failed: user_id=$user_id, tier_id=$tier_id, error=Insufficient balance, balance=$available_balance, required={$valid_tiers[$tier_id]['price']}, time=" . date('Y-m-d H:i:s'));
    } else {
        // Begin transaction for atomicity
        $conn->begin_transaction();
        try {
            // Insert tier_payment transaction
            $amount = $valid_tiers[$tier_id]['price'];
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, status, created_at) VALUES (?, ?, ?, ?, NOW())');
            $type = 'tier_payment';
            $status = 'approved';
            $stmt->bind_param('isds', $user_id, $type, $amount, $status);
            $stmt->execute();
            $transaction_id = $stmt->insert_id;
            $stmt->close();

            // Update user tier
            $stmt = $conn->prepare('UPDATE users SET tier_id = ? WHERE id = ?');
            $stmt->bind_param('ii', $tier_id, $user_id);
            $stmt->execute();
            $stmt->close();

            // Commit transaction
            $conn->commit();
            $success = 'Successfully upgraded to ' . htmlspecialchars($valid_tiers[$tier_id]['name']) . ' tier!';
            error_log("Tier upgrade successful: user_id=$user_id, transaction_id=$transaction_id, tier_id=$tier_id, amount=$amount, time=" . date('Y-m-d H:i:s'));
            header('Location: dashboard.php');
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to process tier upgrade: ' . htmlspecialchars($e->getMessage());
            error_log("Tier upgrade failed: user_id=$user_id, tier_id=$tier_id, error=" . $e->getMessage() . ", time=" . date('Y-m-d H:i:s'));
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gradient-to-r from-indigo-100 to-purple-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 animate-slide-in">Tier Upgrade Payment</h2>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Current Tier and Balance -->
        <div class="mb-6 bg-white p-4 rounded-lg shadow-sm animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Your Account
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="account">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="text-center">
                        <p class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($tier_name); ?></p>
                        <p class="text-sm font-medium text-gray-600">Current Tier</p>
                    </div>
                    <div class="text-center">
                        <p class="text-xl font-bold text-gray-900">Ksh <?php echo htmlspecialchars(number_format($available_balance, 2)); ?></p>
                        <p class="text-sm font-medium text-gray-600">Available Balance</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Deposit Section -->
        <?php if (isset($_SESSION['user_id'])): ?>
            <div class="deposit-section mb-6 text-center">
                <p class="text-sm text-gray-600 mb-3">Need funds for tier upgrades?</p>
                <a href="deposit.php" 
                   class="inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-4 py-2 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                   aria-label="Deposit funds via M-Pesa" 
                   title="Deposit funds to your account">
                    Deposit Funds
                </a>
            </div>
        <?php endif; ?>

        <!-- Available Tiers -->
         <!-- Upgrade Your Tier -->
        <div id="upgrade-tier" class="mb-16 bg-white p-8 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-2xl font-semibold text-gray-900 mb-6 flex justify-between items-center">
                Upgrade Your Tier
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="upgrade-tier">
                    <i class="ri-arrow-down-s-line text-2xl"></i>
                </button>
            </h3>
            
            <div class="section-content hidden">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <?php foreach ($tiers as $tier): ?>
                        <?php $commission_rates = json_decode($tier['commission_rates'] ?? '{"1":0.1}', true); ?>
                        <div class="bg-white rounded-lg shadow-lg overflow-hidden transition-all duration-300 tier-card animate-slide-in hover:scale-102">
                            <div class="p-6 bg-gradient-to-r <?php echo $tier['name'] === 'Bronze' ? 'from-amber-400 to-amber-600' : ($tier['name'] === 'Silver' ? 'from-indigo-500 to-blue-600' : 'from-yellow-400 to-yellow-600'); ?>">
                                <h4 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($tier['name']); ?></h4>
                                <div class="mt-4 flex items-end">
                                    <span class="text-3xl font-bold text-white">Ksh <?php echo htmlspecialchars(number_format($tier['price'], 2)); ?></span>
                                    <span class="ml-2 text-white opacity-80">/one-time</span>
                                </div>
                            </div>
                            <div class="p-6">
                                <ul class="space-y-3">
                                    <li class="flex items-start">
                                        <i class="ri-team-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600">Referrals: <?php echo htmlspecialchars($tier['levels_deep'] ?? 1); ?> levels</span>
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                                        <span class="text-gray-600"><?php echo htmlspecialchars(($commission_rates[1] * 100)); ?>% commission on level 1</span>
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
                                </ul>
                                <form action="pay.php" method="GET" class="mt-6">
                                    <input type="hidden" name="tier_id" value="<?php echo $tier['id']; ?>">
                                    <button type="submit" name="request_upgrade" class="w-full bg-primary text-white py-3 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta <?php echo ($user['tier_id'] >= $tier['id'] && $user['tier_id'] != 0) || $available_balance < $tier['price'] ? 'opacity-60 cursor-not-allowed' : ''; ?>" <?php echo ($user['tier_id'] >= $tier['id'] && $user['tier_id'] != 0) || $available_balance < $tier['price'] ? 'disabled aria-disabled="true"' : 'aria-disabled="false"'; ?> aria-label="<?php echo ($user['tier_id'] >= $tier['id'] && $user['tier_id'] != 0) ? 'Current or higher tier selected' : ($available_balance < $tier['price'] ? 'Insufficient balance for ' . htmlspecialchars($tier['name']) : 'Upgrade to ' . htmlspecialchars($tier['name'])); ?>">
                                        <?php echo ($user['tier_id'] == $tier['id'] && $user['tier_id'] != 0) ? 'Current Tier' : 'Join ' . htmlspecialchars($tier['name']); ?>
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
// Toggle Sections with localStorage persistence
document.querySelectorAll('.toggle-section').forEach(button => {
    const sectionId = button.getAttribute('data-section') || 'section-' + Math.random().toString(36).substr(2, 9);
    const section = button.parentElement.nextElementSibling;
    const icon = button.querySelector('i');
    
    // Load saved state from localStorage
    const isOpen = localStorage.getItem(`section-${sectionId}`) === 'open';
    if (isOpen) {
        section.classList.remove('hidden');
        icon.classList.replace('ri-arrow-down-s-line', 'ri-arrow-up-s-line');
    } else {
        section.classList.add('hidden');
        icon.classList.replace('ri-arrow-up-s-line', 'ri-arrow-down-s-line');
    }

    button.addEventListener('click', () => {
        section.classList.toggle('hidden');
        const isNowOpen = !section.classList.contains('hidden');
        icon.classList.toggle('ri-arrow-down-s-line', !isNowOpen);
        icon.classList.toggle('ri-arrow-up-s-line', isNowOpen);
        localStorage.setItem(`section-${sectionId}`, isNowOpen ? 'open' : 'closed');
    });
});
</script>

<?php include_once 'footer.php'; ?>
