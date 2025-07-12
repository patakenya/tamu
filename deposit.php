
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

// Fetch user data (phone_number for M-Pesa)
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare('SELECT phone_number FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
if (!$stmt->execute()) {
    error_log('Failed to fetch user data: user_id=' . $user_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $error = 'An error occurred while loading your profile. Please try again later.';
}
$user = $stmt->get_result()->fetch_assoc();
$phone_number = $user['phone_number'] ?? '';
$stmt->close();

// Handle deposit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['amount'], $_POST['transaction_code'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token. Please try again.';
    } else {
        $amount = (float)$_POST['amount'];
        $transaction_code = trim($_POST['transaction_code']);
        if ($amount < 300) {
            $error = 'Minimum deposit amount is Ksh 300.00.';
        } elseif (empty($phone_number)) {
            $error = 'Phone number not found. Please update your profile.';
        } elseif (empty($transaction_code) || !preg_match('/^[A-Za-z0-9]{10,12}$/', $transaction_code)) {
            $error = 'Invalid M-Pesa transaction code. It should be 10-12 alphanumeric characters.';
        } else {
            // Insert pending deposit with transaction code
            $stmt = $conn->prepare('INSERT INTO transactions (user_id, type, amount, status, transaction_code) VALUES (?, ?, ?, ?, ?)');
            $type = 'payment';
            $status = 'pending'; // Stays pending until admin verification
            $stmt->bind_param('isdss', $user_id, $type, $amount, $status, $transaction_code);
            if ($stmt->execute()) {
                $transaction_id = $stmt->insert_id;
                $success = 'Deposit of Ksh ' . number_format($amount, 2) . ' with M-Pesa transaction code ' . htmlspecialchars($transaction_code) . ' initiated. Awaiting admin verification...';
                error_log('Deposit initiated: user_id=' . $user_id . ', transaction_id=' . $transaction_id . ', amount=' . $amount . ', transaction_code=' . $transaction_code . ', time=' . date('Y-m-d H:i:s'));
                // Redirect to dashboard after 2 seconds
                echo '<script>setTimeout(() => { window.location.href = "dashboard.php"; }, 2000);</script>';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token
            } else {
                error_log('Failed to insert deposit: user_id=' . $user_id . ', amount=' . $amount . ', transaction_code=' . $transaction_code . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
                $error = 'An error occurred while processing your deposit. Please try again.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-8 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl md:text-3xl font-bold text-gray-900">Deposit Funds</h2>
            <a href="dashboard.php" class="text-primary hover:underline" aria-label="Back to dashboard" title="Back to dashboard">
                <i class="ri-arrow-left-line mr-1"></i> Back to Dashboard
            </a>
        </div>
        <p class="text-center text-base text-gray-600 max-w-4xl mx-auto mb-8">Add funds to your available balance using M-Pesa Till Number <strong>4178866</strong>. Enter the M-Pesa transaction code below. Minimum deposit is Ksh 300.00. Deposits require admin verification.</p>
        <?php if (isset($error)): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if (isset($success)): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-6">
                <form method="POST" action="deposit.php" id="deposit-form" class="space-y-6">
                    <div>
                        <label for="amount" class="block text-sm font-medium text-gray-700">Deposit Amount (Ksh)</label>
                        <div class="mt-2">
                            <input type="number" name="amount" id="amount" step="1" min="300" required 
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" 
                                   placeholder="Enter amount (min 300)" aria-label="Deposit amount in Ksh">
                        </div>
                    </div>
                    <div>
                        <label for="transaction_code" class="block text-sm font-medium text-gray-700">M-Pesa Transaction Code</label>
                        <div class="mt-2">
                            <input type="text" name="transaction_code" id="transaction_code" required 
                                   class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" 
                                   placeholder="Enter M-Pesa transaction code (e.g., QJ7X9P2K3L)" aria-label="M-Pesa transaction code">
                        </div>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pay to M-Pesa Till Number: <strong>4178866</strong></p>
                        <p class="text-sm text-gray-600 mt-2">Depositing to: <?php echo htmlspecialchars($phone_number); ?></p>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <button type="submit" 
                            class="w-full bg-gradient-to-r from-primary to-indigo-700 text-white py-2 px-4 rounded-button font-medium hover:from-indigo-700 hover:to-blue-800 transition-colors animate-pulse-cta" 
                            aria-label="Deposit funds via M-Pesa" 
                            title="Deposit funds via M-Pesa">
                        Submit Deposit
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<script>
// Client-side validation for deposit form
document.getElementById('deposit-form')?.addEventListener('submit', (e) => {
    const amount = parseFloat(document.getElementById('amount').value);
    const transactionCode = document.getElementById('transaction_code').value.trim();
    if (isNaN(amount) || amount < 300) {
        e.preventDefault();
        alert('Minimum deposit amount is Ksh 300.00.');
    } else if (!transactionCode.match(/^[A-Za-z0-9]{10,12}$/)) {
        e.preventDefault();
        alert('Invalid M-Pesa transaction code. It should be 10-12 alphanumeric characters.');
    }
});
</script>

<?php include_once 'footer.php'; ?>
