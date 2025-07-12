<?php
session_start();
include_once '../config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$admin_id = $_SESSION['admin_id'];
$error = '';
$success = '';

// Fetch admin details
$stmt = $conn->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch articles
$stmt = $conn->prepare('SELECT a.id, a.user_id, a.title, a.status, a.created_at, u.full_name 
                        FROM articles a 
                        JOIN users u ON a.user_id = u.id 
                        WHERE u.tier_id >= 2 
                        ORDER BY a.created_at DESC');
$stmt->execute();
$articles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle article approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['article_id'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $article_id = (int)$_POST['article_id'];
        $action = $_POST['action'];
        $status = $action === 'approve' ? 'approved' : 'rejected';

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Update article status
            $stmt = $conn->prepare('UPDATE articles SET status = ? WHERE id = ? AND status = "pending"');
            $stmt->bind_param('si', $status, $article_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update article status.');
            }
            $stmt->close();

            // If approving, add transaction
            if ($action === 'approve') {
                $amount = 300.00;
                $user_id = (int)$_POST['user_id'];
                $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, status) VALUES (?, ?, ?, ?)');
                $type = 'article_payment';
                $status = 'approved';
                $stmt->bind_param('isds', $user_id, $type, $amount, $status);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to insert transaction.');
                }
                $stmt->close();

                // Update user balance
                $stmt = $conn->prepare('UPDATE users SET available_balance = available_balance + ? WHERE id = ?');
                $stmt->bind_param('di', $amount, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update user balance.');
                }
                $stmt->close();
            }

            $conn->commit();
            $success = "Article $status successfully.";
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Manage Articles</h2>
        <p class="text-lg text-gray-600 mb-12">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Review and manage submitted articles below.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Articles -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Submitted Articles</h3>
            <?php if (empty($articles)): ?>
                <p class="text-gray-600">No articles submitted.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($articles as $article): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($article['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($article['full_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $article['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($article['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($article['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($article['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($article['status'] === 'pending'): ?>
                                            <form method="POST" action="articles.php" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="article_id" value="<?php echo $article['id']; ?>">
                                                <input type="hidden" name="user_id" value="<?php echo $article['user_id']; ?>">
                                                <button type="submit" name="action" value="approve" class="text-green-600 hover:text-green-800 mr-2" aria-label="Approve article">Approve</button>
                                                <button type="submit" name="action" value="reject" class="text-red-600 hover:text-red-800" aria-label="Reject article">Reject</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include_once 'footer.php'; ?>
