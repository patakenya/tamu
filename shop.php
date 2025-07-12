<?php
session_start();
include_once 'config.php';

// Initialize CSRF token for authenticated users
if (!empty($_SESSION['user_id']) && empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user data (if authenticated)
$user_id = $_SESSION['user_id'] ?? null;
$referral_code = '';
$tier_id = null;
$referral_link = 'http://localhost/kampus/shop.php'; // Default for guests
if ($user_id) {
    $stmt = $conn->prepare('SELECT referral_code, tier_id FROM users WHERE id = ?');
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        error_log('Failed to fetch user data: user_id=' . $user_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
        $error = 'An error occurred while loading your profile. Please try again later.';
    } else {
        $user = $stmt->get_result()->fetch_assoc();
        $referral_code = $user['referral_code'] ?? '';
        $tier_id = $user['tier_id'] ?? null;
        $referral_link = 'http://localhost/kampus/shop.php?ref=' . htmlspecialchars($referral_code);
    }
    $stmt->close();
}

// Handle referral code from URL
$ref_code = $_GET['ref'] ?? $referral_code;

// Fetch all approved books
$stmt = $conn->prepare('SELECT b.id, b.title, b.description, b.price, b.file_path, u.full_name 
                        FROM books b 
                        JOIN users u ON b.user_id = u.id 
                        WHERE b.status = ?');
$status = 'approved';
$stmt->bind_param('s', $status);
if (!$stmt->execute()) {
    error_log('Failed to fetch books: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading books. Please try again later.';
    $books = [];
} else {
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Fetch all affiliate products
$stmt = $conn->prepare('SELECT id, name, description, price, commission, original_url 
                        FROM affiliate_products');
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate products: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading products. Please try again later.';
    $affiliate_products = [];
} else {
    $affiliate_products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="text-center mb-12">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Digital Book & Product Shop</h2>
            <p class="mt-3 text-base text-gray-600 max-w-4xl mx-auto">
                Explore our collection of digital books and affiliate products. Pay via M-Pesa Till Number <strong>4178866</strong> and submit your transaction code after purchase.
            </p>
            <div class="mt-4 text-sm text-gray-600">
                <p>
                    <strong>Payment Instructions:</strong> Use M-Pesa to send payment to Till Number <strong>4178866</strong>. After payment, enter the transaction code on the payment page to complete your purchase.
                </p>
            </div>
            <?php if ($user_id && $tier_id == 3): ?>
                <a href="add_books.php" 
                   class="mt-4 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                   aria-label="Upload a new book" 
                   title="Upload a new book">Upload a Book</a>
            <?php elseif ($user_id): ?>
                <p class="mt-3 text-sm text-gray-600">Upgrade to Gold tier to upload and sell your own books!</p>
                <a href="pay.php?tier_id=3" 
                   class="mt-2 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                   aria-label="Upgrade to Gold tier" 
                   title="Upgrade to Gold tier to upload books">Upgrade to Gold</a>
            <?php else: ?>
                <p class="mt-3 text-sm text-gray-600">Sign up or log in to upload books or promote products for commissions!</p>
                <a href="login.php" 
                   class="mt-2 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                   aria-label="Log in to access more features" 
                   title="Log in to upload or promote">Log In / Sign Up</a>
            <?php endif; ?>
            <div class="mt-4 text-sm text-gray-600">
                <p>Your Referral Link: 
                    <a href="<?php echo htmlspecialchars($referral_link); ?>" 
                       class="text-primary hover:underline" 
                       aria-label="Referral link for sharing" 
                       title="Share this link to earn commissions"><?php echo htmlspecialchars($referral_link); ?></a>
                    <?php if ($user_id): ?>
                        <button id="copy-referral-link" 
                                class="ml-2 text-primary hover:underline" 
                                data-link="<?php echo htmlspecialchars($referral_link); ?>" 
                                aria-label="Copy referral link" 
                                title="Copy referral link to clipboard">
                            <i class="ri-clipboard-line"></i> Copy
                        </button>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Books Section -->
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-900 flex justify-between items-center">
                Digital Books
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="books">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
        </div>
        <div class="section-content mb-12 <?php echo empty($books) ? 'hidden' : ''; ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php foreach ($books as $book): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden min-w-[250px] hover:scale-102 transition-transform">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($book['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-3">
                                <?php echo htmlspecialchars($book['description'] ? substr($book['description'], 0, 100) . (strlen($book['description']) > 100 ? '...' : '') : 'No description available'); ?>
                            </p>
                            <p class="text-base font-medium text-gray-900 mb-3">Ksh <?php echo htmlspecialchars(number_format($book['price'], 2)); ?></p>
                            <p class="text-sm text-gray-600 mb-4">By: <?php echo htmlspecialchars($book['full_name']); ?></p>
                            <a href="buy_book.php?book_id=<?php echo htmlspecialchars($book['id']); ?>&ref=<?php echo htmlspecialchars($ref_code); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" 
                               class="w-full bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta whitespace-nowrap <?php echo !$user_id ? 'pointer-events-none opacity-60' : ''; ?>" 
                               aria-label="Buy book: <?php echo htmlspecialchars($book['title']); ?>" 
                               title="Buy <?php echo htmlspecialchars($book['title']); ?>"
                               data-book-id="<?php echo htmlspecialchars($book['id']); ?>">
                                Buy Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($books)): ?>
                    <div role="alert" class="text-center text-gray-600 col-span-full">No books available yet. Check back soon!</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Affiliate Products Section -->
        <div class="mb-6">
            <h3 class="text-xl font-semibold text-gray-900 flex justify-between items-center">
                Affiliate Products
                <button class="toggle-section text-primary hover:text-indigo-700" data-section="products">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
        </div>
        <div class="section-content <?php echo empty($affiliate_products) ? 'hidden' : ''; ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 sm:gap-8">
                <?php foreach ($affiliate_products as $product): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden min-w-[250px] hover:scale-102 transition-transform">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-3">
                                <?php echo htmlspecialchars($product['description'] ? substr($product['description'], 0, 100) . (strlen($product['description']) > 100 ? '...' : '') : 'No description available'); ?>
                            </p>
                            <p class="text-base font-medium text-gray-900 mb-3">Ksh <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                            <p class="text-sm text-gray-600 mb-4">Commission: Ksh <?php echo htmlspecialchars(number_format($product['commission'], 2)); ?></p>
                            <a href="buy_product.php?product_id=<?php echo htmlspecialchars($product['id']); ?>&ref=<?php echo htmlspecialchars($ref_code); ?>&csrf_token=<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>" 
                               class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-2 px-4 rounded-button font-medium hover:from-orange-600 hover:to-orange-700 transition-colors animate-pulse-cta whitespace-nowrap <?php echo !$user_id ? 'pointer-events-none opacity-60' : ''; ?>" 
                               aria-label="Buy product: <?php echo htmlspecialchars($product['name']); ?>" 
                               title="Buy <?php echo htmlspecialchars($product['name']); ?>"
                               data-product-id="<?php echo htmlspecialchars($product['id']); ?>">
                                Buy Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($affiliate_products)): ?>
                    <div role="alert" class="text-center text-gray-600 col-span-full">No affiliate products available yet. Check back soon!</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
// Copy Referral Link (only for authenticated users)
document.getElementById('copy-referral-link')?.addEventListener('click', () => {
    const link = document.getElementById('copy-referral-link').getAttribute('data-link');
    navigator.clipboard.writeText(link).then(() => {
        alert('Referral link copied to clipboard!');
    }).catch(err => {
        console.error('Failed to copy referral link:', err);
    });
});

// Toggle Sections with localStorage persistence
document.querySelectorAll('.toggle-section').forEach(button => {
    const sectionId = button.getAttribute('data-section');
    const section = button.parentElement.nextElementSibling;
    const icon = button.querySelector('i');

    // Load saved state from localStorage
    const isOpen = localStorage.getItem(`section-${sectionId}`) === 'open';
    if (isOpen || <?php echo empty($books) && empty($affiliate_products) ? 'false' : 'true'; ?>) {
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

// Validate Buy Now links
document.querySelectorAll('a[href*="buy_book.php"], a[href*="buy_product.php"]').forEach(link => {
    link.addEventListener('click', (e) => {
        const bookId = link.getAttribute('data-book-id');
        const productId = link.getAttribute('data-product-id');
        if (!bookId && !productId) {
            e.preventDefault();
            alert('Invalid item selection. Please try again.');
        }
        <?php if (!$user_id): ?>
            e.preventDefault();
            if (confirm('Please log in or sign up to complete your purchase. Proceed to login?')) {
                window.location.href = 'login.php';
            }
        <?php endif; ?>
    });
});
</script>

<?php include_once 'footer.php'; ?>
