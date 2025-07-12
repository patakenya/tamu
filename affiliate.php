<?php
session_start();
include_once 'config.php';

// Redirect if not logged in or not Gold-tier
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
if (!$stmt->execute()) {
    error_log('Failed to fetch user tier: user_id=' . $_SESSION['user_id'] . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    header('Location: login.php');
    exit;
}
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();
if ($user['tier_id'] != 3) {
    header('Location: select_tier.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Fetch affiliate products
$stmt = $conn->prepare('SELECT id, name, description, price, commission, featured_image FROM affiliate_products');
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate products: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading products. Please try again later.';
}
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user's affiliate links
$stmt = $conn->prepare('SELECT ap.id, ap.name, ap.commission, al.affiliate_code 
                        FROM affiliate_links al 
                        JOIN affiliate_products ap ON al.product_id = ap.id 
                        WHERE al.user_id = ?');
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate links: user_id=' . $user_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading your affiliate links. Please try again later.';
}
$affiliate_links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch affiliate earnings
$stmt = $conn->prepare('SELECT SUM(amount) AS total_affiliate_earnings 
                        FROM transactions 
                        WHERE user_id = ? AND type = "affiliate_commission" AND status = "approved"');
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate earnings: user_id=' . $user_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading your earnings. Please try again later.';
}
$total_affiliate_earnings = $stmt->get_result()->fetch_assoc()['total_affiliate_earnings'] ?? 0;
$stmt->close();

// Handle affiliate link generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_link']) && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $product_id = intval($_POST['product_id']);
    
    // Check if link already exists
    $stmt = $conn->prepare('SELECT id FROM affiliate_links WHERE user_id = ? AND product_id = ?');
    $stmt->bind_param('ii', $user_id, $product_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $error = 'You have already generated a link for this product.';
        error_log('Duplicate affiliate link attempt: user_id=' . $user_id . ', product_id=' . $product_id . ', time=' . date('Y-m-d H:i:s'));
    } else {
        // Generate unique affiliate code
        $affiliate_code = 'AFF' . $user_id . '_' . $product_id . '_' . substr(md5(uniqid()), 0, 8);
        $stmt = $conn->prepare('INSERT INTO affiliate_links (user_id, product_id, affiliate_code) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $user_id, $product_id, $affiliate_code);
        if ($stmt->execute()) {
            $success = 'Affiliate link generated successfully!';
            error_log('Affiliate link generated: user_id=' . $user_id . ', product_id=' . $product_id . ', affiliate_code=' . $affiliate_code . ', time=' . date('Y-m-d H:i:s'));
            // Refresh affiliate links
            $stmt = $conn->prepare('SELECT ap.id, ap.name, ap.commission, al.affiliate_code 
                                    FROM affiliate_links al 
                                    JOIN affiliate_products ap ON al.product_id = ap.id 
                                    WHERE al.user_id = ?');
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $affiliate_links = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $error = 'Failed to generate affiliate link: ' . htmlspecialchars($conn->error);
            error_log('Failed to generate affiliate link: user_id=' . $user_id . ', product_id=' . $product_id . ', error=' . $conn->error . ', time=' . date('Y-m-d H:i:s'));
        }
    }
    $stmt->close();
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-6 text-center animate-slide-in">Affiliate Marketing</h2>
        <p class="text-base text-gray-600 mb-6 text-center max-w-3xl mx-auto">Earn commissions by promoting popular products. Generate your unique affiliate links and share them to start earning!</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Affiliate Earnings -->
        <div class="bg-white p-4 rounded-lg shadow-sm mb-6 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-3">Your Affiliate Earnings</h3>
            <p class="text-gray-600">Total Earned: <span class="font-medium">Ksh <?php echo number_format($total_affiliate_earnings, 2); ?></span></p>
        </div>

        <!-- Affiliate Products -->
        <div class="bg-white p-4 rounded-lg shadow-sm mb-6 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-3 flex justify-between items-center">
                Available Products
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="products">
                    <i class="ri-arrow-down-s-line text-lg"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($products)): ?>
                    <p class="text-gray-600">No affiliate products available yet.</p>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($products as $product): ?>
                            <div class="bg-gray-50 p-4 rounded-lg shadow-sm hover:scale-102 transition-transform">
                                <?php if (!empty($product['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['featured_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-32 object-cover rounded-lg mb-3">
                                <?php else: ?>
                                    <div class="w-full h-32 bg-gray-200 rounded-lg mb-3 flex items-center justify-center">
                                        <span class="text-gray-500">No Image</span>
                                    </div>
                                <?php endif; ?>
                                <h4 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($product['name']); ?></h4>
                                <p class="text-gray-600 text-sm mb-2">
                                    <?php echo htmlspecialchars($product['description'] ? substr($product['description'], 0, 100) . (strlen($product['description']) > 100 ? '...' : '') : 'No description available'); ?>
                                </p>
                                <p class="text-gray-600 text-sm mb-2">Price: Ksh <?php echo number_format($product['price'], 2); ?></p>
                                <p class="text-gray-600 text-sm mb-3">Commission: Ksh <?php echo number_format($product['commission'], 2); ?> per sale</p>
                                <form action="affiliate.php" method="POST" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="generate_link" class="w-full bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Generate Affiliate Link</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Your Affiliate Links -->
        <div class="bg-white p-4 rounded-lg shadow-sm animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-3 flex justify-between items-center">
                Your Affiliate Links
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="links">
                    <i class="ri-arrow-down-s-line text-lg"></i>
                </button>
            </h3>
            <div class="section-content hidden">
                <?php if (empty($affiliate_links)): ?>
                    <p class="text-gray-600">No affiliate links generated yet.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Affiliate Link</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($affiliate_links as $link): ?>
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-gray-900"><?php echo htmlspecialchars($link['name']); ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900">Ksh <?php echo number_format($link['commission'], 2); ?></td>
                                        <td class="px-4 py-3 text-sm text-primary">
                                            <a href="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/aff_redirect.php?code=' . $link['affiliate_code']); ?>" target="_blank">
                                                <?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/aff_redirect.php?code=' . $link['affiliate_code']); ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-sm">
                                            <button class="copy-link text-primary hover:text-indigo-700" data-link="<?php echo htmlspecialchars('http://' . $_SERVER['HTTP_HOST'] . '/aff_redirect.php?code=' . $link['affiliate_code']); ?>">
                                                <i class="ri-clipboard-line"></i> Copy
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
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

// Copy affiliate links
document.querySelectorAll('.copy-link').forEach(button => {
    button.addEventListener('click', () => {
        const link = button.getAttribute('data-link');
        navigator.clipboard.writeText(link).then(() => {
            alert('Affiliate link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy affiliate link:', err);
        });
    });
});
</script>

<?php include_once 'footer.php'; ?>
