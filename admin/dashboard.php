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

// Fetch platform metrics
// Total users
$stmt = $conn->prepare('SELECT COUNT(*) as total_users FROM users');
if (!$stmt->execute()) {
    error_log('Failed to fetch total users: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $total_users = 0;
} else {
    $total_users = $stmt->get_result()->fetch_assoc()['total_users'];
}
$stmt->close();

// Total revenue (book_sales + affiliate_sales)
$stmt = $conn->prepare('SELECT SUM(sale_amount) as total_revenue FROM book_sales WHERE sale_amount > 0');
if (!$stmt->execute()) {
    error_log('Failed to fetch book sales revenue: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $total_revenue = 0;
} else {
    $total_revenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
}
$stmt->close();

$stmt = $conn->prepare('SELECT SUM(sale_amount) as affiliate_revenue FROM affiliate_sales WHERE sale_amount > 0');
if (!$stmt->execute()) {
    error_log('Failed to fetch affiliate sales revenue: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $total_revenue += 0;
} else {
    $total_revenue += $stmt->get_result()->fetch_assoc()['affiliate_revenue'] ?? 0;
}
$stmt->close();

// Total pending transactions
$stmt = $conn->prepare('SELECT COUNT(*) as pending_count FROM transactions WHERE status = "pending" AND type IN ("payment", "withdrawal")');
if (!$stmt->execute()) {
    error_log('Failed to fetch pending transactions count: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $pending_count = 0;
} else {
    $pending_count = $stmt->get_result()->fetch_assoc()['pending_count'];
}
$stmt->close();

// Total approved articles
$stmt = $conn->prepare('SELECT COUNT(*) as approved_articles FROM articles WHERE status = "approved"');
if (!$stmt->execute()) {
    error_log('Failed to fetch approved articles: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $approved_articles = 0;
} else {
    $approved_articles = $stmt->get_result()->fetch_assoc()['approved_articles'];
}
$stmt->close();

// Total approved books
$stmt = $conn->prepare('SELECT COUNT(*) as approved_books FROM books WHERE status = "approved"');
if (!$stmt->execute()) {
    error_log('Failed to fetch approved books: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $approved_books = 0;
} else {
    $approved_books = $stmt->get_result()->fetch_assoc()['approved_books'];
}
$stmt->close();

// Fetch pending transactions
$stmt = $conn->prepare('SELECT t.id, t.user_id, t.type, t.amount, t.status, t.created_at, t.transaction_code, u.full_name 
                        FROM transactions t 
                        JOIN users u ON t.user_id = u.id 
                        WHERE t.status = "pending" AND t.type IN ("payment", "withdrawal") 
                        ORDER BY t.created_at DESC');
if (!$stmt->execute()) {
    error_log('Failed to fetch pending transactions: error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $pending_transactions = [];
} else {
    $pending_transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt->close();

// Handle transaction approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['transaction_id'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
        error_log('Invalid CSRF token: admin_id=' . $admin_id . ', transaction_id=' . $_POST['transaction_id'] . ', time=' . date('Y-m-d H:i:s'));
    } else {
        $transaction_id = (int)$_POST['transaction_id'];
        $action = $_POST['action'];
        $status = $action === 'approve' ? 'approved' : 'rejected';

        // Begin transaction
        $conn->begin_transaction();
        try {
            // Update transaction status
            $stmt = $conn->prepare('UPDATE transactions SET status = ? WHERE id = ? AND status = "pending"');
            $stmt->bind_param('si', $status, $transaction_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update transaction status: ' . $stmt->error);
            }
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            // If approving a deposit, update user's available_balance
            if ($action === 'approve' && $_POST['type'] === 'payment') {
                $stmt = $conn->prepare('UPDATE users SET available_balance = available_balance + ? WHERE id = ?');
                $amount = (float)$_POST['amount'];
                $user_id = (int)$_POST['user_id'];
                $stmt->bind_param('di', $amount, $user_id);
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update user balance: ' . $stmt->error);
                }
                $stmt->close();
            }

            $conn->commit();
            $success = "Transaction $status successfully.";
            error_log("Transaction $status: admin_id=$admin_id, transaction_id=$transaction_id, type=" . $_POST['type'] . ", user_id=" . $_POST['user_id'] . ", amount=" . $_POST['amount'] . ", time=" . date('Y-m-d H:i:s'));
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
            error_log('Transaction error: admin_id=' . $admin_id . ', transaction_id=' . $transaction_id . ', error=' . $e->getMessage() . ', time=' . date('Y-m-d H:i:s'));
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Admin Dashboard</h2>
            <a href="logout.php" class="flex items-center text-primary hover:text-indigo-700 font-medium transition-colors" aria-label="Logout">
                <i class="ri-logout-box-line mr-2"></i> Logout
            </a>
        </div>
        <p class="text-base text-gray-600 mb-8">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Monitor platform performance and manage transactions below.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Performance Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
            <div class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-user-line ri-xl mr-3"></i>
                    <h3 class="text-lg font-semibold">Total Users</h3>
                </div>
                <p class="text-2xl font-bold"><?php echo htmlspecialchars($total_users); ?></p>
            </div>
            <div class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-money-dollar-circle-line ri-xl mr-3"></i>
                    <h3 class="text-lg font-semibold">Total Revenue</h3>
                </div>
                <p class="text-2xl font-bold">Ksh <?php echo htmlspecialchars(number_format($total_revenue, 2)); ?></p>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-teal-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-file-text-line ri-xl mr-3"></i>
                    <h3 class="text-lg font-semibold">Pending Transactions</h3>
                </div>
                <p class="text-2xl font-bold"><?php echo htmlspecialchars($pending_count); ?></p>
            </div>
            <div class="bg-gradient-to-r from-purple-500 to-pink-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-article-line ri-xl mr-3"></i>
                    <h3 class="text-lg font-semibold">Approved Articles</h3>
                </div>
                <p class="text-2xl font-bold"><?php echo htmlspecialchars($approved_articles); ?></p>
            </div>
            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-book-line ri-xl mr-3"></i>
                    <h3 class="text-lg font-semibold">Approved Books</h3>
                </div>
                <p class="text-2xl font-bold"><?php echo htmlspecialchars($approved_books); ?></p>
            </div>
        </div>
        
        <!-- Pending Transactions -->
        <div class="bg-white p-6 rounded-lg shadow-md">
            <h3 class="text-xl font-semibold text-gray-900 mb-6">Pending Transactions</h3>
            <?php if (empty($pending_transactions)): ?>
                <p class="text-gray-600">No pending transactions.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transaction Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($pending_transactions as $transaction): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($transaction['full_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $transaction['type'] === 'payment' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo ucfirst(htmlspecialchars($transaction['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($transaction['amount'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($transaction['transaction_code'] ?? '-'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($transaction['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <form method="POST" action="dashboard.php" class="inline-flex space-x-3">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="transaction_id" value="<?php echo $transaction['id']; ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $transaction['user_id']; ?>">
                                            <input type="hidden" name="amount" value="<?php echo $transaction['amount']; ?>">
                                            <input type="hidden" name="type" value="<?php echo $transaction['type']; ?>">
                                            <button type="submit" name="action" value="approve" class="bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700 transition-colors" aria-label="Approve transaction">
                                                <i class="ri-check-line mr-1"></i> Approve
                                            </button>
                                            <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 transition-colors" aria-label="Reject transaction">
                                                <i class="ri-close-line mr-1"></i> Reject
                                            </button>
                                        </form>
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
