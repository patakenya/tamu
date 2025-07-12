<?php
session_start();
include_once 'config.php';

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user tier if logged in
$user_tier_id = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    if (!$stmt->execute()) {
        error_log('Failed to fetch user tier: user_id=' . $_SESSION['user_id'] . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    } else {
        $user_tier_id = $stmt->get_result()->fetch_assoc()['tier_id'];
    }
    $stmt->close();
}

// Fetch article
$article_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$article = null;
if ($article_id) {
    $stmt = $conn->prepare('
        SELECT a.id, a.title, a.content, a.featured_image, a.created_at, u.full_name 
        FROM articles a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.id = ? AND a.status = "approved"
    ');
    $stmt->bind_param('i', $article_id);
    if (!$stmt->execute()) {
        error_log('Failed to fetch article: article_id=' . $article_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
        $error = 'An error occurred while loading the article.';
    } else {
        $article = $stmt->get_result()->fetch_assoc();
    }
    $stmt->close();
}

// Fetch tiers
$stmt = $conn->prepare('SELECT name, price, levels_deep, commission_rates FROM tiers ORDER BY price ASC');
if (!$stmt->execute()) {
    error_log('Failed to fetch tiers: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $tiers = [];
} else {
    $tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Fetch approved books
$stmt = $conn->prepare('SELECT id, title, price FROM books WHERE status = "approved" ORDER BY created_at DESC LIMIT 3');
if (!$stmt->execute()) {
    error_log('Failed to fetch books: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $books = [];
} else {
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Fetch affiliate products
$stmt = $conn->prepare('SELECT id, name, price, original_url FROM affiliate_products ORDER BY created_at DESC LIMIT 3');
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate products: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $products = [];
} else {
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Handle comment submission
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_comment']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (!isset($_SESSION['user_id'])) {
        $error = 'You must be logged in to comment.';
    } else {
        $content = trim($_POST['comment']);
        if (empty($content) || strlen($content) < 10) {
            $error = 'Comment must be at least 10 characters.';
        } else {
            $stmt = $conn->prepare('INSERT INTO comments (article_id, user_id, content) VALUES (?, ?, ?)');
            $stmt->bind_param('iis', $article_id, $_SESSION['user_id'], $content);
            if ($stmt->execute()) {
                $success = 'Comment submitted successfully!';
                error_log('Comment submitted: user_id=' . $_SESSION['user_id'] . ', article_id=' . $article_id . ', time=' . date('Y-m-d H:i:s'));
                header("Location: article.php?id=$article_id");
                exit;
            } else {
                $error = 'Failed to submit comment: ' . htmlspecialchars($stmt->error);
                error_log('Failed to submit comment: user_id=' . $_SESSION['user_id'] . ', article_id=' . $article_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
            }
            $stmt->close();
        }
    }
}

// Fetch comments
$comments = [];
if ($article_id) {
    $stmt = $conn->prepare('
        SELECT c.content, c.created_at, u.full_name 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.article_id = ? 
        ORDER BY c.created_at DESC
    ');
    $stmt->bind_param('i', $article_id);
    if (!$stmt->execute()) {
        error_log('Failed to fetch comments: article_id=' . $article_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    } else {
        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Article</h2>
            <a href="articles.php" class="flex items-center text-primary hover:text-indigo-700 font-medium transition-colors" aria-label="Back to articles">
                <i class="ri-arrow-left-line mr-2"></i> Back to Articles
            </a>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <!-- Main Content -->
            <div class="lg:w-2/3">
                <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <?php if (!$article): ?>
                    <p class="text-gray-600">Article not found or not approved.</p>
                <?php else: ?>
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <?php if ($article['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="Featured image for <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-64 object-cover rounded-lg mb-6">
                        <?php endif; ?>
                        <h3 class="text-xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($article['title']); ?></h3>
                        <p class="text-gray-600 text-sm mb-4">
                            By <?php echo htmlspecialchars($article['full_name']); ?> on <?php echo htmlspecialchars(date('M d, Y', strtotime($article['created_at']))); ?>
                        </p>
                        <div class="prose max-w-none mb-6"><?php echo $article['content']; ?></div>
                        <div class="flex space-x-2 mb-6">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://localhost/kampus/article.php?id=' . $article['id']); ?>&quote=<?php echo urlencode('Read "' . $article['title'] . '" on our platform'); ?>" 
                               target="_blank" 
                               class="text-gray-600 hover:text-primary" 
                               aria-label="Share on Facebook">
                                <i class="ri-facebook-fill text-lg"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://localhost/kampus/article.php?id=' . $article['id']); ?>&text=<?php echo urlencode('Read "' . $article['title'] . '" on our platform'); ?>" 
                               target="_blank" 
                               class="text-gray-600 hover:text-primary" 
                               aria-label="Share on X">
                                <i class="ri-twitter-fill text-lg"></i>
                            </a>
                            <a href="https://api.whatsapp.com/send?text=<?php echo urlencode('Read "' . $article['title'] . '" on our platform: http://localhost/kampus/article.php?id=' . $article['id']); ?>" 
                               target="_blank" 
                               class="text-gray-600 hover:text-primary" 
                               aria-label="Share on WhatsApp">
                                <i class="ri-whatsapp-fill text-lg"></i>
                            </a>
                        </div>
                        <!-- Comments -->
                        <div class="border-t pt-6">
                            <h4 class="text-lg font-semibold text-gray-900 mb-4">Comments</h4>
                            <?php if (empty($comments)): ?>
                                <p class="text-gray-600">No comments yet. Be the first to comment!</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="mb-4">
                                        <p class="text-gray-600 text-sm">
                                            <strong><?php echo htmlspecialchars($comment['full_name']); ?></strong> on 
                                            <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($comment['created_at']))); ?>
                                        </p>
                                        <p class="text-gray-700"><?php echo htmlspecialchars($comment['content']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form action="article.php?id=<?php echo $article['id']; ?>" method="POST" class="mt-6 space-y-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                    <div>
                                        <label for="comment" class="block text-sm font-medium text-gray-700 mb-1">Add a Comment</label>
                                        <textarea id="comment" name="comment" rows="4" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required></textarea>
                                    </div>
                                    <button type="submit" name="submit_comment" class="bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Post Comment</button>
                                </form>
                            <?php else: ?>
                                <p class="text-gray-600 mt-4">Please <a href="login.php" class="text-primary hover:text-indigo-700">log in</a> to comment.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="lg:w-1/3">
                <div class="space-y-6">
                    <!-- Tiers -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Join Our Platform</h3>
                        <?php foreach ($tiers as $tier): ?>
                            <?php $commission_rates = json_decode($tier['commission_rates'] ?? '{"1":0.1}', true); ?>
                            <div class="mb-4">
                                <h4 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($tier['name']); ?></h4>
                                <p class="text-gray-600 text-sm mt-1">Ksh <?php echo htmlspecialchars(number_format($tier['price'], 2)); ?> (One-time)</p>
                                <ul class="mt-2 space-y-1 text-gray-600 text-sm">
                                    <li class="flex items-start">
                                        <i class="ri-check-line text-green-500 mr-2"></i>
                                        Referrals: <?php echo htmlspecialchars($tier['levels_deep']); ?> levels
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-check-line text-green-500 mr-2"></i>
                                        <?php echo htmlspecialchars(($commission_rates[1] * 100)); ?>% commission on level 1
                                    </li>
                                    <li class="flex items-start">
                                        <i class="ri-check-line text-green-500 mr-2"></i>
                                        Sell digital products
                                    </li>
                                    <li class="flex items-start">
                                        <?php echo $tier['name'] !== 'Bronze' ? '<i class="ri-check-line text-green-500 mr-2"></i>' : '<i class="ri-close-line text-red-500 mr-2"></i>'; ?>
                                        <?php echo $tier['name'] !== 'Bronze' ? 'Earn Ksh 300 per article' : 'Content creation'; ?>
                                    </li>
                                    <li class="flex items-start">
                                        <?php echo $tier['name'] === 'Gold' ? '<i class="ri-check-line text-green-500 mr-2"></i>' : '<i class="ri-close-line text-red-500 mr-2"></i>'; ?>
                                        <?php echo $tier['name'] === 'Gold' ? 'Affiliate marketing: Ksh 100/sale' : 'Affiliate marketing'; ?>
                                    </li>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                        <a href="<?php echo isset($_SESSION['user_id']) ? 'select_tier.php' : 'register.php'; ?>" 
                           class="block bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta text-center">
                            <?php echo isset($_SESSION['user_id']) ? 'Upgrade Now' : 'Sign Up Now'; ?>
                        </a>
                    </div>

                    <!-- Books -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Books</h3>
                        <?php if (empty($books)): ?>
                            <p class="text-gray-600 text-sm">No books available.</p>
                        <?php else: ?>
                            <?php foreach ($books as $book): ?>
                                <div class="mb-4">
                                    <h4 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($book['title']); ?></h4>
                                    <p class="text-gray-600 text-sm">Ksh <?php echo htmlspecialchars(number_format($book['price'], 2)); ?></p>
                                    <a href="shop.php?book_id=<?php echo $book['id']; ?>" class="text-primary hover:text-indigo-700 text-sm">Buy Now</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- Affiliate Products -->
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Featured Products</h3>
                        <?php if (empty($products)): ?>
                            <p class="text-gray-600 text-sm">No products available.</p>
                        <?php else: ?>
                            <?php foreach ($products as $product): ?>
                                <div class="mb-4">
                                    <h4 class="text-base font-semibold text-gray-900"><?php echo htmlspecialchars($product['name']); ?></h4>
                                    <p class="text-gray-600 text-sm">Ksh <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></p>
                                    <a href="<?php echo htmlspecialchars($product['original_url']); ?>" target="_blank" class="text-primary hover:text-indigo-700 text-sm">Buy Now</a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include_once 'footer.php'; ?>
