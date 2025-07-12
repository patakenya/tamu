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

// Pagination
$articles_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $articles_per_page;

// Fetch total articles
$stmt = $conn->prepare('SELECT COUNT(*) AS total FROM articles WHERE status = "approved"');
if (!$stmt->execute()) {
    error_log('Failed to fetch total articles: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $total_articles = 0;
} else {
    $total_articles = $stmt->get_result()->fetch_assoc()['total'];
}
$stmt->close();
$total_pages = ceil($total_articles / $articles_per_page);

// Fetch articles
$stmt = $conn->prepare('
    SELECT a.id, a.title, a.content, a.featured_image, a.created_at, u.full_name 
    FROM articles a 
    JOIN users u ON a.user_id = u.id 
    WHERE a.status = "approved" 
    ORDER BY a.created_at DESC 
    LIMIT ? OFFSET ?
');
$stmt->bind_param('ii', $articles_per_page, $offset);
if (!$stmt->execute()) {
    error_log('Failed to fetch articles: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $articles = [];
} else {
    $articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Write-Up -->
        <div class="text-center mb-12 animate-slide-in">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Explore Our Community Articles</h2>
            <p class="mt-4 text-base text-gray-600 max-w-2xl mx-auto">
                Discover insights, tips, and stories from our community. Read, comment, and share your favorites on social media!
            </p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="mt-4 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Join Now to Comment</a>
            <?php elseif ($user_tier_id < 2): ?>
                <a href="select_tier.php" class="mt-4 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Upgrade to Write Articles</a>
            <?php else: ?>
                <a href="write_article.php" class="mt-4 inline-block bg-gradient-to-r from-primary to-indigo-700 text-white px-6 py-3 rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta">Write an Article</a>
            <?php endif; ?>
        </div>

        <!-- Articles -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-slide-in">
            <?php if (empty($articles)): ?>
                <p class="text-center text-gray-600 col-span-full">No articles available yet.</p>
            <?php else: ?>
                <?php foreach ($articles as $article): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden transition-all duration-300 hover:scale-102">
                        <?php if ($article['featured_image']): ?>
                            <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="Featured image for <?php echo htmlspecialchars($article['title']); ?>" class="w-full h-48 object-cover">
                        <?php else: ?>
                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                <i class="ri-image-line text-gray-400 text-4xl"></i>
                            </div>
                        <?php endif; ?>
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4">
                                By <?php echo htmlspecialchars($article['full_name']); ?> on <?php echo htmlspecialchars(date('M d, Y', strtotime($article['created_at']))); ?>
                            </p>
                            <p class="text-gray-600 mb-4 line-clamp-3"><?php echo htmlspecialchars(strip_tags($article['content'])); ?></p>
                            <div class="flex justify-between items-center">
                                <a href="article.php?id=<?php echo $article['id']; ?>" class="text-primary hover:text-indigo-700 font-medium">Read More</a>
                                <div class="flex space-x-2">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('http://localhost/kampus/article.php?id=' . $article['id']); ?>" 
                                       target="_blank" 
                                       class="text-gray-600 hover:text-primary" 
                                       aria-label="Share on Facebook">
                                        <i class="ri-facebook-fill text-lg"></i>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('http://localhost/kampus/article.php?id=' . $article['id']); ?>&text=<?php echo urlencode($article['title']); ?>" 
                                       target="_blank" 
                                       class="text-gray-600 hover:text-primary" 
                                       aria-label="Share on X">
                                        <i class="ri-twitter-fill text-lg"></i>
                                    </a>
                                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($article['title'] . ' - http://localhost/kampus/article.php?id=' . $article['id']); ?>" 
                                       target="_blank" 
                                       class="text-gray-600 hover:text-primary" 
                                       aria-label="Share on WhatsApp">
                                        <i class="ri-whatsapp-fill text-lg"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="mt-12 flex justify-center space-x-2 animate-slide-in">
                <?php if ($page > 1): ?>
                    <a href="articles.php?page=<?php echo $page - 1; ?>" class="px-4 py-2 bg-gradient-to-r from-primary to-indigo-700 text-white rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors">Previous</a>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="articles.php?page=<?php echo $i; ?>" class="px-4 py-2 <?php echo $i === $page ? 'bg-gradient-to-r from-primary to-indigo-700 text-white' : 'bg-gray-200 text-gray-700'; ?> rounded-button font-semibold hover:bg-indigo-700 hover:text-white transition-colors"><?php echo $i; ?></a>
                <?php endfor; ?>
                <?php if ($page < $total_pages): ?>
                    <a href="articles.php?page=<?php echo $page + 1; ?>" class="px-4 py-2 bg-gradient-to-r from-primary to-indigo-700 text-white rounded-button font-semibold hover:from-indigo-700 hover:to-blue-800 transition-colors">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include_once 'footer.php'; ?>
