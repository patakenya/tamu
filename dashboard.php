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

// Fetch user profile and tier details
$stmt = $conn->prepare('SELECT u.full_name, u.phone_number, u.referral_code, u.tier_id, t.name AS tier_name, t.min_withdrawal
                        FROM users u
                        LEFT JOIN tiers t ON u.tier_id = t.id
                        WHERE u.id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
$tier_name = $user['tier_name'] ?? 'No Tier';

// Fetch total earnings and available balance
$stmt = $conn->prepare('SELECT 
    COALESCE(SUM(CASE WHEN type IN ("payment", "book_sale", "book_commission", "article_payment", "affiliate_commission") AND status = "approved" THEN amount ELSE 0 END), 0) AS total_earnings,
    COALESCE(SUM(CASE WHEN type IN ("payment", "book_sale", "book_commission", "article_payment", "affiliate_commission") AND status = "approved" THEN amount ELSE 0 END), 0) 
    - COALESCE(SUM(CASE WHEN type IN ("withdrawal", "tier_payment") AND status = "approved" THEN amount ELSE 0 END), 0) AS balance
    FROM transactions WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$total_earnings = $result['total_earnings'] ?? 0;
$available_balance = max($result['balance'] ?? 0, 0); // Prevent negative balance
$stmt->close();

// Fetch referral stats
$stmt = $conn->prepare('SELECT COUNT(*) AS direct_referrals FROM referrals WHERE referrer_id = ? AND level = 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$direct_referrals = $stmt->get_result()->fetch_assoc()['direct_referrals'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) AS total_downline FROM referrals WHERE referrer_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$total_downline = $stmt->get_result()->fetch_assoc()['total_downline'];
$stmt->close();

// Fetch recent referrals (last 5)
$stmt = $conn->prepare('SELECT u.full_name, r.created_at
                        FROM referrals r
                        JOIN users u ON r.referred_id = u.id
                        WHERE r.referrer_id = ? AND r.level = 1
                        ORDER BY r.created_at DESC
                        LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$recent_referrals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent transactions (last 5)
$stmt = $conn->prepare('SELECT type, amount, status, created_at
                        FROM transactions
                        WHERE user_id = ?
                        ORDER BY created_at DESC
                        LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch uploaded books
$stmt = $conn->prepare('SELECT id, title, price, created_at FROM books WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$uploaded_books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch submitted articles
$stmt = $conn->prepare('SELECT id, title, status, created_at FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$submitted_articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch affiliate sales
$stmt = $conn->prepare('SELECT ap.name, a.sale_amount, a.commission, a.created_at
                        FROM affiliate_sales a
                        JOIN affiliate_products ap ON a.product_id = ap.id
                        WHERE a.user_id = ?
                        ORDER BY a.created_at DESC
                        LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$affiliate_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch book sales (as seller or promoter)
$stmt = $conn->prepare('SELECT b.title, bs.sale_amount, bs.promoter_commission, bs.seller_amount, bs.created_at
                        FROM book_sales bs
                        JOIN books b ON bs.book_id = b.id
                        WHERE bs.buyer_id = ? OR bs.promoter_id = ? OR b.user_id = ?
                        ORDER BY bs.created_at DESC
                        LIMIT 5');
$stmt->bind_param('iii', $user_id, $user_id, $user_id);
$stmt->execute();
$book_sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch recent activity (combined books, articles, transactions, affiliate sales)
$stmt = $conn->prepare('
    (SELECT "book_upload" AS type, title, created_at FROM books WHERE user_id = ?)
    UNION
    (SELECT "article_submission" AS type, title, created_at FROM articles WHERE user_id = ?)
    UNION
    (SELECT "affiliate_sale" AS type, ap.name AS title, a.created_at
     FROM affiliate_sales a
     JOIN affiliate_products ap ON a.product_id = ap.id
     WHERE a.user_id = ?)
    UNION
    (SELECT type, "Transaction" AS title, created_at FROM transactions WHERE user_id = ?)
    ORDER BY created_at DESC
    LIMIT 5');
$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$recent_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch leaderboard (top 5 earners)
$stmt = $conn->prepare('SELECT u.full_name, SUM(t.amount) AS total_earnings
                        FROM transactions t
                        JOIN users u ON t.user_id = u.id
                        WHERE t.type IN ("payment", "book_sale", "book_commission", "article_payment", "affiliate_commission") AND t.status = "approved"
                        GROUP BY t.user_id
                        ORDER BY total_earnings DESC
                        LIMIT 5');
$stmt->execute();
$leaderboard = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch pending payments
$stmt = $conn->prepare('SELECT pp.id, pp.amount, pp.status, pp.created_at, t.name AS tier_name
                        FROM pending_payments pp
                        JOIN tiers t ON pp.tier_id = t.id
                        WHERE pp.user_id = ?
                        ORDER BY pp.created_at DESC
                        LIMIT 5');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$pending_payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch tiers for upgrade section
$stmt = $conn->prepare('SELECT id, name, price, levels_deep, commission_rates FROM tiers WHERE id IN (1, 2, 3) ORDER BY price ASC');
$stmt->execute();
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle tier upgrade request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_upgrade']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $tier_id = (int)$_POST['tier_id'];
    $valid_tiers = array_column($tiers, null, 'id');
    
    if (!isset($valid_tiers[$tier_id])) {
        $error = 'Invalid tier selected.';
    } elseif ($user['tier_id'] >= $tier_id && $user['tier_id'] != 0) {
        $error = 'You cannot downgrade to a lower tier.';
    } else {
        header("Location: pay.php?tier_id=$tier_id");
        exit;
    }
}

// Handle withdrawal request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_withdrawal']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $amount = floatval($_POST['amount']);
    if ($amount <= 0) {
        $error = 'Withdrawal amount must be greater than zero.';
    } elseif ($amount > $available_balance) {
        $error = 'Insufficient available balance.';
    } elseif ($user['tier_id'] == 0) {
        $error = 'You must join a tier to withdraw earnings.';
    } elseif ($amount < ($user['min_withdrawal'] ?? 500)) {
        $error = 'Withdrawal amount must be at least Ksh ' . number_format($user['min_withdrawal'] ?? 500, 2);
    } else {
        $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, status, created_at) VALUES (?, ?, ?, ?, NOW())');
        $type = 'withdrawal';
        $status = 'pending';
        $stmt->bind_param('isds', $user_id, $type, $amount, $status);
        if ($stmt->execute()) {
            $success = 'Withdrawal request submitted successfully! Awaiting admin approval.';
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            // Log for debugging
            error_log("Withdrawal request submitted: user_id=$user_id, amount=$amount, status=pending, time=" . date('Y-m-d H:i:s'));
        } else {
            $error = 'Failed to submit withdrawal request: ' . htmlspecialchars($conn->error);
            error_log("Withdrawal request failed: user_id=$user_id, error=" . $conn->error);
        }
        $stmt->close();
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gradient-to-br from-indigo-50 to-purple-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 animate-slide-in">Welcome to Your WealthGrow Dashboard, <?php echo htmlspecialchars($user['full_name']); ?>!</h2>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Earnings -->
        <div class="mb-12 bg-gradient-to-r from-indigo-600 to-blue-500 text-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold mb-4 flex justify-between items-center">
                Your Earnings
                <button class="toggle-section text-white hover:text-blue-200" data-section="earnings">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="text-center min-w-[150px] max-w-sm mx-auto">
                        <p class="text-2xl md:text-3xl font-bold">Ksh <?php echo htmlspecialchars(number_format($available_balance, 2)); ?></p>
                        <p class="text-sm font-medium">Available Balance</p>
                        <button id="open-withdrawal-modal" 
                                class="mt-4 bg-white text-primary px-4 py-2 rounded-button font-medium hover:bg-gray-100 transition-colors animate-pulse-cta <?php echo $user['tier_id'] == 0 || $available_balance < ($user['min_withdrawal'] ?? 500) ? 'opacity-60 cursor-not-allowed' : ''; ?>" 
                                <?php echo $user['tier_id'] == 0 || $available_balance < ($user['min_withdrawal'] ?? 500) ? 'disabled aria-disabled="true"' : 'aria-disabled="false"'; ?> 
                                aria-label="Request Withdrawal"
                                title="<?php echo $user['tier_id'] == 0 ? 'Join a tier to withdraw earnings' : ($available_balance < ($user['min_withdrawal'] ?? 500) ? 'Insufficient balance (Min: Ksh ' . htmlspecialchars(number_format($user['min_withdrawal'] ?? 500, 2)) . ')' : 'Request a withdrawal'); ?>">
                            Request Withdrawal
                        </button>
                    </div>
                    <div class="text-center min-w-[150px] max-w-sm mx-auto">
                        <p class="text-2xl md:text-3xl font-bold">Ksh <?php echo htmlspecialchars(number_format($total_earnings, 2)); ?></p>
                        <p class="text-sm font-medium">Total Earnings</p>
                        <p class="text-sm text-blue-100 mt-2">Min Withdrawal: Ksh <?php echo htmlspecialchars(number_format($user['min_withdrawal'] ?? 500, 2)); ?></p>
                    </div>
                </div>
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

        <!-- Pending Payments -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Pending Payments
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="pending-payments">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($pending_payments)): ?>
                    <p class="text-gray-600">No pending payments.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($pending_payments as $payment): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($payment['tier_name']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($payment['amount'], 2)); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $payment['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($payment['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($payment['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($payment['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        
        
        <!-- Quick Actions -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Quick Actions
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="quick-actions">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="write_article.php" class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-4 py-2 rounded-button font-medium hover:from-purple-600 hover:to-purple-700 transition-colors text-center animate-pulse-cta">Write Article</a>
                    <a href="add_books.php" class="bg-gradient-to-r from-teal-500 to-teal-600 text-white px-4 py-2 rounded-button font-medium hover:from-teal-600 hover:to-teal-700 transition-colors text-center animate-pulse-cta">Upload Book</a>
                    <a href="affiliate.php" class="bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors text-center animate-pulse-cta">Promote Affiliate Products</a>
                    <button id="share-referral" class="bg-gradient-to-r from-secondary to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors text-center animate-pulse-cta">Share Referral Link</button>
                </div>
            </div>
        </div>

        <!-- Earning Opportunities -->
        <div class="mb-12 bg-gradient-to-r from-indigo-200 to-purple-300 p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Earning Opportunities
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="earning-opportunities">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm flex-1 min-w-[200px] flex flex-col hover:scale-102 transition-transform">
                        <div class="flex-1">
                            <div class="flex items-center mb-3">
                                <i class="ri-team-line text-primary text-xl mr-2"></i>
                                <h4 class="text-lg font-medium text-gray-900">Referrals</h4>
                            </div>
                            <p class="text-gray-600 text-sm">Earn up to 15% commissions by inviting friends.</p>
                        </div>
                        <button id="share-referral" class="mt-4 bg-gradient-to-r from-secondary to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors animate-pulse-cta">Share Link</button>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm flex-1 min-w-[200px] flex flex-col hover:scale-102 transition-transform">
                        <div class="flex-1">
                            <div class="flex items-center mb-3">
                                <i class="ri-article-line text-primary text-xl mr-2"></i>
                                <h4 class="text-lg font-medium text-gray-900">Write Articles</h4>
                            </div>
                            <p class="text-gray-600 text-sm">Earn Ksh 300 per approved article.</p>
                        </div>
                        <a href="write_article.php" class="mt-4 bg-gradient-to-r from-purple-500 to-purple-600 text-white px-4 py-2 rounded-button font-medium hover:from-purple-600 hover:to-purple-700 transition-colors animate-pulse-cta">Write Now</a>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm flex-1 min-w-[200px] flex flex-col hover:scale-102 transition-transform">
                        <div class="flex-1">
                            <div class="flex items-center mb-3">
                                <i class="ri-book-open-line text-primary text-xl mr-2"></i>
                                <h4 class="text-lg font-medium text-gray-900">Sell Books</h4>
                            </div>
                            <p class="text-gray-600 text-sm">Upload and sell e-books for high margins.</p>
                        </div>
                        <a href="add_books.php" class="mt-4 bg-gradient-to-r from-teal-500 to-teal-600 text-white px-4 py-2 rounded-button font-medium hover:from-teal-600 hover:to-teal-700 transition-colors animate-pulse-cta">Upload Book</a>
                    </div>
                    <div class="bg-white p-4 rounded-lg shadow-sm flex-1 min-w-[200px] flex flex-col hover:scale-102 transition-transform">
                        <div class="flex-1">
                            <div class="flex items-center mb-3">
                                <i class="ri-links-line text-primary text-xl mr-2"></i>
                                <h4 class="text-lg font-medium text-gray-900">Affiliate Marketing</h4>
                            </div>
                            <p class="text-gray-600 text-sm">Earn Ksh 500 per sale by promoting products.</p>
                        </div>
                        <a href="affiliate.php" class="mt-4 bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors animate-pulse-cta">Promote Now</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profile and Stats -->
        <div class="mb-12 grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-white p-6 rounded-xl shadow-lg animate-slide-in hover:shadow-xl transition-shadow">
                <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                    Profile
                    <button class="toggle-section text-primary hover:text-indigo-700" data-section="profile">
                        <i class="ri-arrow-down-s-line text-xl"></i>
                    </button>
                </h3>
                <div class="section-content hidden">
                    <div class="space-y-2">
                        <p><span class="font-medium text-gray-700">Name:</span> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><span class="font-medium text-gray-700">Phone:</span> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                        <p><span class="font-medium text-gray-700">Tier:</span> <?php echo htmlspecialchars($tier_name); ?></p>
                        <p><span class="font-medium text-gray-700">Referral Code:</span> <?php echo htmlspecialchars($user['referral_code']); ?> <button class="copy-link text-primary hover:underline" data-link="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/shop.php?ref=' . $user['referral_code']); ?>"><i class="ri-clipboard-line"></i> Copy</button></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg animate-slide-in hover:shadow-xl transition-shadow">
                <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                    Referral Stats
                    <button class="toggle-section text-primary hover:text-indigo-700" data-section="referral-stats">
                        <i class="ri-arrow-down-s-line text-xl"></i>
                    </button>
                </h3>
                <div class="section-content hidden">
                    <div class="space-y-2">
                        <p><span class="font-medium text-gray-700">Direct Referrals:</span> <?php echo htmlspecialchars($direct_referrals); ?></p>
                        <p><span class="font-medium text-gray-700">Total Downline:</span> <?php echo htmlspecialchars($total_downline); ?></p>
                        <p><span class="font-medium text-gray-700">Referral Link:</span> 
                            <a href="shop.php?ref=<?php echo htmlspecialchars($user['referral_code']); ?>" class="text-primary hover:underline" target="_blank">
                                <?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/shop.php?ref=' . $user['referral_code']); ?>
                            </a>
                            <button class="copy-link text-primary hover:underline" data-link="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/shop.php?ref=' . $user['referral_code']); ?>"><i class="ri-clipboard-line"></i> Copy</button>
                        </p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white p-6 rounded-xl shadow-lg animate-slide-in hover:shadow-xl transition-shadow">
                <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                    Earnings Summary
                    <button class="toggle-section text-primary hover:text-indigo-700" data-section="earnings-summary">
                        <i class="ri-arrow-down-s-line text-xl"></i>
                    </button>
                </h3>
                <div class="section-content hidden">
                    <div class="space-y-2">
                        <p><span class="font-medium text-gray-700">Total Earnings:</span> Ksh <?php echo htmlspecialchars(number_format($total_earnings, 2)); ?></p>
                        <p><span class="font-medium text-gray-700">Available Balance:</span> Ksh <?php echo htmlspecialchars(number_format($available_balance, 2)); ?></p>
                        <p><span class="font-medium text-gray-700">Min Withdrawal:</span> Ksh <?php echo htmlspecialchars(number_format($user['min_withdrawal'] ?? 500, 2)); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Referrals -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Recent Referrals
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="recent-referrals">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($recent_referrals)): ?>
                    <p class="text-gray-600">No recent referrals yet. Share your link to grow your network!</p>
                    <button id="share-referral" class="mt-4 bg-gradient-to-r from-secondary to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors animate-pulse-cta">Share Referral Link</button>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Join Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_referrals as $referral): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($referral['full_name']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($referral['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Affiliate Marketing Performance -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Affiliate Marketing Performance
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="affiliate-performance">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($affiliate_sales)): ?>
                    <p class="text-gray-600">No affiliate sales yet. Start promoting products!</p>
                    <a href="affiliate.php" class="mt-4 inline-block bg-gradient-to-r from-orange-500 to-orange-600 text-white px-4 py-2 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors animate-pulse-cta">Promote Products</a>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($affiliate_sales as $sale): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['name']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($sale['sale_amount'], 2)); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($sale['commission'], 2)); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($sale['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Uploaded Books -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Your Uploaded Books
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="uploaded-books">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($uploaded_books)): ?>
                    <p class="text-gray-600">No books uploaded yet.</p>
                    <a href="add_books.php" class="mt-4 inline-block bg-gradient-to-r from-teal-500 to-teal-600 text-white px-4 py-2 rounded-button font-medium hover:from-teal-600 hover:to-teal-700 transition-colors animate-pulse-cta">Upload a Book</a>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($uploaded_books as $book): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($book['price'], 2)); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($book['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Submitted Articles -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Your Submitted Articles
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="submitted-articles">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($submitted_articles)): ?>
                    <p class="text-gray-600">No articles submitted yet.</p>
                    <a href="write_article.php" class="mt-4 inline-block bg-gradient-to-r from-purple-500 to-purple-600 text-white px-4 py-2 rounded-button font-medium hover:from-purple-600 hover:to-purple-700 transition-colors animate-pulse-cta">Write an Article</a>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Earnings</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($submitted_articles as $article): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($article['title']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm">
                                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $article['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($article['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            Ksh <?php echo htmlspecialchars($article['status'] === 'approved' ? '300.00' : '0.00'); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($article['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Book Sales -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Recent Book Sales
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="book-sales">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($book_sales)): ?>
                    <p class="text-gray-600">No book sales yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Book Title</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sale Amount</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Earnings</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($book_sales as $sale): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($sale['title']); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($sale['sale_amount'], 2)); ?></td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                            Ksh <?php echo htmlspecialchars(number_format($sale['promoter_commission'] > 0 ? $sale['promoter_commission'] : $sale['seller_amount'], 2)); ?>
                                        </td>
                                        <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($sale['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Leaderboard -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Top Earners
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="leaderboard">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($leaderboard)): ?>
                    <p class="text-gray-600">No top earners yet.</p>
                <?php else: ?>
                    <ol class="space-y-3">
                        <?php foreach ($leaderboard as $index => $leader): ?>
                            <li class="flex items-center space-x-3">
                                <i class="ri-trophy-line text-yellow-500 text-lg"></i>
                                <span class="text-gray-900 font-medium"><?php echo htmlspecialchars($leader['full_name']); ?></span>
                                <span class="text-gray-500">Ksh <?php echo htmlspecialchars(number_format($leader['total_earnings'], 2)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <!-- Progress Tracker -->
        <div class="mb-12 bg-white p-6 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Progress Tracker
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="progress-tracker">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <div class="space-y-4">
                    <div>
                        <p class="text-sm font-medium text-gray-700">Referrals Milestone</p>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-primary to-blue-600 h-2 rounded-full" style="width: <?php echo min(($direct_referrals / 10) * 100, 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($direct_referrals); ?> / 10 Direct Referrals</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-700">Earnings Milestone</p>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-gradient-to-r from-primary to-blue-600 h-2 rounded-full" style="width: <?php echo min(($total_earnings / 5000) * 100, 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600 mt-1">Ksh <?php echo htmlspecialchars(number_format($total_earnings, 2)); ?> / Ksh 5,000</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Withdrawal Modal -->
        <div id="withdrawal-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl shadow-lg max-w-md w-full animate-fade-in">
                <h3 class="text-xl font-semibold text-gray-900 mb-4">Request Withdrawal</h3>
                <form action="dashboard.php" method="POST" id="withdrawal-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <div class="mb-4">
                        <label for="withdrawal-amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (Ksh)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-money-dollar-circle-line text-gray-400 text-lg"></i>
                            </div>
                            <input type="number" id="withdrawal-amount" name="amount" step="0.01" min="0" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter amount" required>
                        </div>
                        <p class="text-sm text-gray-600 mt-2">Minimum withdrawal: Ksh <?php echo htmlspecialchars(number_format($user['min_withdrawal'] ?? 500, 2)); ?></p>
                        <p class="text-sm text-gray-600 mt-1">Your request will be reviewed by an admin.</p>
                    </div>
                    <div class="flex justify-end space-x-4">
                        <button type="button" id="close-withdrawal-modal" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-button font-medium hover:bg-gray-300 transition-colors">Cancel</button>
                        <button type="submit" name="request_withdrawal" id="submit-withdrawal" class="bg-gradient-to-r from-primary to-indigo-700 text-white px-4 py-2 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

        <script>
        // Track modal state to prevent multiple openings
        let isWithdrawalModalOpen = false;

        // Prevent duplicate form submissions
        document.getElementById('withdrawal-form')?.addEventListener('submit', function(e) {
            const submitButton = document.getElementById('submit-withdrawal');
            const amount = parseFloat(document.getElementById('withdrawal-amount').value);
            if (amount <= 0) {
                e.preventDefault();
                alert('Withdrawal amount must be greater than zero.');
            } else if (amount > <?php echo $available_balance; ?>) {
                e.preventDefault();
                alert('Insufficient available balance.');
            } else if (amount < <?php echo $user['min_withdrawal'] ?? 500; ?>) {
                e.preventDefault();
                alert('Withdrawal amount must be at least Ksh <?php echo number_format($user['min_withdrawal'] ?? 500, 2); ?>.');
            } else {
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            }
        });

        // Prevent duplicate upgrade form submissions
        document.querySelectorAll('.tier-card form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitButton = form.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.classList.add('opacity-60', 'cursor-not-allowed');
            });
        });

        // Share Referral Link
        document.querySelectorAll('#share-referral').forEach(button => {
            button.addEventListener('click', () => {
                const link = '<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/shop.php?ref=' . $user['referral_code']); ?>';
                navigator.clipboard.writeText(link).then(() => {
                    alert('Referral link copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy referral link:', err);
                });
            });
        });

        // Copy Link
        document.querySelectorAll('.copy-link').forEach(button => {
            button.addEventListener('click', () => {
                const link = button.getAttribute('data-link');
                navigator.clipboard.writeText(link).then(() => {
                    alert('Link copied to clipboard!');
                }).catch(err => {
                    console.error('Failed to copy link:', err);
                });
            });
        });

        // Toggle Sections with localStorage persistence
        document.querySelectorAll('.toggle-section').forEach(button => {
            const sectionId = button.getAttribute('data-section');
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

        // Withdrawal Modal
        document.getElementById('open-withdrawal-modal')?.addEventListener('click', () => {
            if (isWithdrawalModalOpen) return;
            const modal = document.getElementById('withdrawal-modal');
            if (!modal) {
                console.error('Withdrawal modal not found');
                return;
            }
            isWithdrawalModalOpen = true;
            modal.classList.remove('hidden');
        });

        document.getElementById('close-withdrawal-modal')?.addEventListener('click', () => {
            const modal = document.getElementById('withdrawal-modal');
            if (!modal) {
                console.error('Withdrawal modal not found');
                return;
            }
            modal.classList.add('hidden');
            isWithdrawalModalOpen = false;
            const submitButton = document.getElementById('submit-withdrawal');
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.classList.remove('opacity-60', 'cursor-not-allowed');
            }
        });
        </script>

<?php include_once 'footer.php'; ?>
