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
if (!$stmt->execute()) {
    error_log('Failed to fetch admin: admin_id=' . $admin_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading admin profile. Please try again later.';
}
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch books (user-authored and admin-authored)
$stmt = $conn->prepare('SELECT b.id, b.user_id, b.admin_id, b.title, b.price, b.status, b.created_at, 
                        COALESCE(u.full_name, a.username) AS author_name 
                        FROM books b 
                        LEFT JOIN users u ON b.user_id = u.id 
                        LEFT JOIN admins a ON b.admin_id = a.id 
                        ORDER BY b.created_at DESC');
if (!$stmt->execute()) {
    error_log('Failed to fetch books: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading books. Please try again later.';
    $books = [];
} else {
    $books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Handle book approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['book_id'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
        error_log('Invalid CSRF token: admin_id=' . $admin_id . ', book_id=' . $_POST['book_id'] . ', time=' . date('Y-m-d H:i:s'));
    } else {
        $book_id = (int)$_POST['book_id'];
        $action = $_POST['action'];
        $status = $action === 'approve' ? 'approved' : 'rejected';

        // Update book status
        $stmt = $conn->prepare('UPDATE books SET status = ? WHERE id = ? AND status = "pending"');
        $stmt->bind_param('si', $status, $book_id);
        if (!$stmt->execute()) {
            $error = 'Error: Failed to update book status: ' . htmlspecialchars($stmt->error);
            error_log('Failed to update book status: admin_id=' . $admin_id . ', book_id=' . $book_id . ', action=' . $action . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
        } else {
            $success = "Book $status successfully.";
            error_log("Book $status: admin_id=$admin_id, book_id=$book_id, time=" . date('Y-m-d H:i:s'));
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $stmt->close();
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Manage Books</h2>
            <div class="flex space-x-4">
                <a href="dashboard.php" class="flex items-center text-primary hover:text-indigo-700 font-medium transition-colors" aria-label="Back to dashboard">
                    <i class="ri-arrow-left-line mr-2"></i> Back to Dashboard
                </a>
                <a href="logout.php" class="flex items-center text-primary hover:text-indigo-700 font-medium transition-colors" aria-label="Logout">
                    <i class="ri-logout-box-line mr-2"></i> Logout
                </a>
            </div>
        </div>
        <p class="text-base text-gray-600 mb-8">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Review and manage uploaded books below. Approve or reject user-submitted books to make them available for sale.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Books -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-900 mb-6">Uploaded Books</h3>
            <?php if (empty($books)): ?>
                <p class="text-gray-600">No books uploaded.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Author</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Uploaded</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($books as $book): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($book['author_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($book['price'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $book['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($book['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($book['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($book['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($book['status'] === 'pending'): ?>
                                            <form method="POST" action="books.php" class="inline-flex space-x-3">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                                <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700 transition-colors" aria-label="Approve book">
                                                    <i class="ri-check-line mr-1"></i> Approve
                                                </button>
                                                <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition-colors" aria-label="Reject book">
                                                    <i class="ri-close-line mr-1"></i> Reject
                                                </button>
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
