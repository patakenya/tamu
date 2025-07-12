<?php
session_start();
include_once 'config.php';

// Redirect if not logged in or not Silver/Gold-tier
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$tier_id = $stmt->get_result()->fetch_assoc()['tier_id'];
$stmt->close();
if ($tier_id < 2) {
    header('Location: select_tier.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_article']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $featured_image = null;

    // Validate inputs
    if (empty($title) || strlen($title) < 10) {
        $error = 'Title must be at least 10 characters.';
    } elseif (empty($content) || strlen(strip_tags($content)) < 200) {
        $error = 'Article content must be at least 200 characters (excluding HTML).';
    } elseif (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 2 * 1024 * 1024; // 2MB
        if (!in_array($_FILES['featured_image']['type'], $allowed_types)) {
            $error = 'Featured image must be JPEG, PNG, or GIF.';
        } elseif ($_FILES['featured_image']['size'] > $max_size) {
            $error = 'Featured image must be under 2MB.';
        } else {
            $upload_dir = 'uploads/articles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $image_name = uniqid('img_') . '.' . pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
            $image_path = $upload_dir . $image_name;
            if (!move_uploaded_file($_FILES['featured_image']['tmp_name'], $image_path)) {
                $error = 'Failed to upload featured image.';
            } else {
                $featured_image = $image_path;
            }
        }
    }

    if (!$error) {
        // Check daily article limit
        $today = date('Y-m-d');
        $stmt = $conn->prepare('SELECT COUNT(*) AS article_count 
                                FROM articles 
                                WHERE user_id = ? AND DATE(created_at) = ?');
        $stmt->bind_param('is', $_SESSION['user_id'], $today);
        $stmt->execute();
        $article_count = $stmt->get_result()->fetch_assoc()['article_count'];
        $stmt->close();

        if ($article_count >= 2) {
            $error = 'You have reached the daily limit of 2 articles.';
        } else {
            // Insert article
            $stmt = $conn->prepare('INSERT INTO articles (user_id, title, content, featured_image) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('isss', $_SESSION['user_id'], $title, $content, $featured_image);
            if ($stmt->execute()) {
                $success = 'Article submitted successfully! Awaiting approval for Ksh 300 payment.';
            } else {
                $error = 'Failed to submit article: ' . $conn->error;
                if ($featured_image && file_exists($featured_image)) {
                    unlink($featured_image); // Remove uploaded image on failure
                }
            }
            $stmt->close();
        }
    }
}

// Fetch recent articles
$stmt = $conn->prepare('SELECT id, title, status, created_at FROM articles WHERE user_id = ? ORDER BY created_at DESC LIMIT 5');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$recent_articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gradient-to-b from-gray-50 to-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Write-Up -->
        <div class="text-center mb-12 animate-slide-in">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900">Write & Earn</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-2xl mx-auto">
                Create high-quality articles and earn Ksh 300 per approved submission as a Silver or Gold-tier member. Add a featured image to make your content stand out!
            </p>
        </div>

        <!-- Guidelines -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Submission Guidelines
                <button class="toggle-section text-primary hover:text-indigo-700">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content">
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start">
                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                        Minimum 200 characters (excluding HTML).
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                        Maximum 2 articles per day.
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                        Featured image (optional): JPEG, PNG, or GIF, max 2MB.
                    </li>
                    <li class="flex items-start">
                        <i class="ri-check-line text-green-500 mr-2 text-lg"></i>
                        Content must be original, relevant, and well-formatted.
                    </li>
                </ul>
            </div>
        </div>

        <!-- Article Submission Form -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Submit Your Article</h3>
            <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form action="write_article.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Article Title</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-article-line text-gray-400 text-lg"></i>
                        </div>
                        <input type="text" id="title" name="title" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 mb-1">Featured Image (Optional)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-image-line text-gray-400 text-lg"></i>
                        </div>
                        <input type="file" id="featured_image" name="featured_image" accept="image/jpeg,image/png,image/gif" class="pl-10 w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary">
                    </div>
                    <p class="text-sm text-gray-600 mt-1">JPEG, PNG, or GIF, max 2MB</p>
                </div>
                <div class="mb-6">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-1">Article Content</label>
                    <div id="editor" class="border border-gray-300 rounded-lg min-h-[200px]"></div>
                    <input type="hidden" id="content" name="content">
                </div>
                <button type="submit" name="submit_article" class="w-full bg-primary text-white py-3 px-4 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Submit Article</button>
            </form>
        </div>

        <!-- Recent Articles -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8 animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Your Recent Articles
                <button class="toggle-section text-primary hover:text-indigo-700">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content">
                <?php if (empty($recent_articles)): ?>
                    <p class="text-gray-600">No articles submitted yet.</p>
                    <a href="#editor" class="mt-4 inline-block bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Write Your First Article</a>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Earnings</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($recent_articles as $article): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($article['title']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                <?php echo $article['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($article['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                                <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Ksh <?php echo $article['status'] === 'approved' ? '300.00' : '0.00'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($article['created_at']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tips for Success -->
        <div class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
            <h3 class="text-xl font-semibold text-gray-900 mb-4 flex justify-between items-center">
                Tips for Successful Articles
                <button class="toggle-section text-primary hover:text-indigo-700">
                    <i class="ri-arrow-down-s-line text-xl"></i>
                </button>
            </h3>
            <div class="section-content">
                <ul class="space-y-2 text-gray-600">
                    <li class="flex items-start">
                        <i class="ri-lightbulb-line text-primary mr-2 text-lg"></i>
                        <span>Write original, engaging content relevant to our audience (e.g., business, tech, lifestyle).</span>
                    </li>
                    <li class="flex items-start">
                        <i class="ri-lightbulb-line text-primary mr-2 text-lg"></i>
                        <span>Use clear headings, bullet points, and concise paragraphs for readability.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="ri-lightbulb-line text-primary mr-2 text-lg"></i>
                        <span>Include a high-quality featured image to boost engagement.</span>
                    </li>
                    <li class="flex items-start">
                        <i class="ri-lightbulb-line text-primary mr-2 text-lg"></i>
                        <span>Proofread for grammar and spelling before submission.</span>
                    </li>
                </ul>
                <?php if ($tier_id < 3): ?>
                    <a href="select_tier.php" class="mt-4 inline-block bg-primary text-white px-4 py-2 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Upgrade to Gold for More Benefits</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Quill.js for rich text editing -->
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
    // Initialize Quill editor
    const quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline'],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                [{ 'header': 1 }, { 'header': 2 }],
                ['link', 'image']
            ]
        }
    });

    // Sync Quill content with hidden input
    quill.on('text-change', () => {
        document.getElementById('content').value = quill.root.innerHTML;
    });
</script>
<script src="script.js"></script>
<?php include_once 'footer.php'; ?>