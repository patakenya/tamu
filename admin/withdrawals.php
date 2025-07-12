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

// Fetch withdrawal requests
$stmt = $conn->prepare('SELECT t.id, t.user_id, u.full_name, t.amount, t.status, t.created_at
                        FROM transactions t
                        JOIN users u ON t.user_id = u.id
                        WHERE t.type = "withdrawal"
                        ORDER BY t.created_at DESC');
$stmt->execute();
$withdrawals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle withdrawal approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    if (isset($_POST['approve_withdrawal']) || isset($_POST['reject_withdrawal'])) {
        $transaction_id = (int)$_POST['transaction_id'];
        $status = isset($_POST['approve_withdrawal']) ? 'approved' : 'rejected';

        // Verify transaction exists and is pending
        $stmt = $conn->prepare('SELECT status FROM transactions WHERE id = ? AND type = "withdrawal"');
        $stmt->bind_param('i', $transaction_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$result) {
            $error = 'Invalid withdrawal request.';
        } elseif ($result['status'] !== 'pending') {
            $error = 'Withdrawal request already processed.';
        } else {
            $stmt = $conn->prepare('UPDATE transactions SET status = ? WHERE id = ? AND type = "withdrawal"');
            $stmt->bind_param('si', $status, $transaction_id);
            if ($stmt->execute()) {
                $success = "Withdrawal $status successfully.";
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = 'Error: Failed to update withdrawal status.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 animate-slide-in">Manage Withdrawals</h2>
        <p class="text-lg text-gray-600 mb-12">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Review and approve or reject user withdrawal requests below.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Withdrawals -->
        <div class="bg-white p-8 rounded-xl shadow-lg animate-slide-in">
            <h3 class="text-2xl font-semibold text-gray-900 mb-6">Withdrawal Requests</h3>
            <?php if (empty($withdrawals)): ?>
                <p class="text-gray-600">No withdrawal requests at this time.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($withdrawals as $withdrawal): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($withdrawal['full_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($withdrawal['amount'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $withdrawal['status'] === 'approved' ? 'bg-green-100 text-green-800' : ($withdrawal['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                            <?php echo ucfirst(htmlspecialchars($withdrawal['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($withdrawal['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($withdrawal['status'] === 'pending'): ?>
                                            <form method="POST" action="withdrawals.php" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                <input type="hidden" name="transaction_id" value="<?php echo $withdrawal['id']; ?>">
                                                <button type="submit" name="approve_withdrawal" class="text-green-600 hover:text-green-800 mr-3 font-medium" aria-label="Approve withdrawal">Approve</button>
                                                <button type="submit" name="reject_withdrawal" class="text-red-600 hover:text-red-800 font-medium" aria-label="Reject withdrawal" onclick="return confirm('Are you sure you want to reject this withdrawal?');">Reject</button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-gray-500">Processed</span>
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
