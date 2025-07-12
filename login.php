<?php
session_start();
include_once 'config.php';

// Redirect logged-in users to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';
$show_otp = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];

    // Validate inputs
    if (empty($phone_number) || empty($password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^\+254[0-9]{9}$/', $phone_number)) {
        $error = 'Invalid phone number format. Use +254XXXXXXXXX.';
    } else {
        // Check user
        $stmt = $conn->prepare('SELECT id, password, is_verified FROM users WHERE phone_number = ?');
        $stmt->bind_param('s', $phone_number);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            if (!$user['is_verified']) {
                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_user_id'] = $user['id'];
                $_SESSION['otp_expiry'] = time() + 300; // 5 minutes
                // Simulate sending OTP (in production, use SMS API)
                $success = 'OTP sent to your phone number. Please enter it below.';
                $show_otp = true;
            } else {
                $_SESSION['user_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            }
        } else {
            $error = 'Invalid phone number or password.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $otp = trim($_POST['otp']);

    if (empty($otp)) {
        $error = 'OTP is required.';
    } elseif (!isset($_SESSION['otp']) || !isset($_SESSION['otp_user_id']) || !isset($_SESSION['otp_expiry']) || time() > $_SESSION['otp_expiry']) {
        $error = 'OTP has expired or is invalid.';
    } elseif ($otp != $_SESSION['otp']) {
        $error = 'Invalid OTP.';
    } else {
        $stmt = $conn->prepare('UPDATE users SET is_verified = TRUE WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['otp_user_id']);
        $stmt->execute();
        $stmt->close();

        $_SESSION['user_id'] = $_SESSION['otp_user_id'];
        unset($_SESSION['otp'], $_SESSION['otp_user_id'], $_SESSION['otp_expiry']);
        header('Location: dashboard.php');
        exit;
    }
}
?>

<?php include 'header.php'; ?>

<section class="py-16 bg-gradient-to-r from-indigo-100 to-purple-200">
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center animate-slide-in">Sign In to WealthGrow</h2>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <form action="login.php" method="POST" class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if (!$show_otp): ?>
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-phone-line text-gray-400"></i>
                        </div>
                        <input type="text" id="phone_number" name="phone_number" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="+254XXXXXXXXX" required>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-lock-line text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter your password" required>
                    </div>
                </div>
                <button type="submit" name="login" class="w-full bg-primary text-white py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Sign In</button>
            <?php else: ?>
                <div class="mb-6">
                    <label for="otp" class="block text-sm font-medium text-gray-700">Enter OTP</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-shield-keyhole-line text-gray-400"></i>
                        </div>
                        <input type="text" id="otp" name="otp" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter 6-digit OTP" required>
                    </div>
                </div>
                <button type="submit" name="verify_otp" class="w-full bg-primary text-white py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Verify OTP</button>
            <?php endif; ?>
        </form>
        <p class="mt-4 text-center text-gray-600">Don't have an account? <a href="register.php" class="text-primary hover:underline">Register</a></p>
    </div>
</section>

<?php include 'footer.php'; ?>