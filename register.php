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
$otp_display = ''; // Variable to store OTP for display

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register']) && $_POST['csrf_token'] === $_SESSION['csrf_token']) {
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $referred_by_code = trim($_POST['referral_code'] ?? '');

    // Validate inputs
    if (empty($full_name) || empty($phone_number) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif (!preg_match('/^\+254[0-9]{9}$/', $phone_number)) {
        $error = 'Invalid phone number format. Use +254XXXXXXXXX.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        // Check if phone number exists
        $stmt = $conn->prepare('SELECT id FROM users WHERE phone_number = ?');
        $stmt->bind_param('s', $phone_number);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            $error = 'Phone number already registered.';
        }
        $stmt->close();

        // Validate referral code
        $referred_by_id = null;
        if (!empty($referred_by_code)) {
            $stmt = $conn->prepare('SELECT id FROM users WHERE referral_code = ?');
            $stmt->bind_param('s', $referred_by_code);
            $stmt->execute();
            $referred_by = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($referred_by) {
                $referred_by_id = $referred_by['id'];
            } else {
                $error = 'Invalid referral code.';
            }
        }

        if (empty($error)) {
            // Generate referral code
            $referral_code = strtoupper(substr(md5(uniqid($phone_number, true)), 0, 6));

            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Register user with tier_id = NULL
            $stmt = $conn->prepare('INSERT INTO users (full_name, phone_number, password, tier_id, referral_code, referred_by, is_verified) VALUES (?, ?, ?, NULL, ?, ?, ?)');
            $is_verified = false;
            $stmt->bind_param('ssssii', $full_name, $phone_number, $hashed_password, $referral_code, $referred_by_id, $is_verified);
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // Generate OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['otp_user_id'] = $user_id;
                $_SESSION['otp_expiry'] = time() + 300; // 5 minutes
                // Store OTP for display (development only)
                $otp_display = $otp;
                $success = 'Registration successful! OTP sent to your phone number. For development purposes, your OTP is: <strong>' . htmlspecialchars($otp) . '</strong>. Please enter it below to verify.';
                $show_otp = true;
            } else {
                $error = 'Registration failed: ' . $conn->error;
            }
            $stmt->close();
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
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center animate-slide-in">Join WealthGrow</h2>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo $success; ?></div>
        <?php endif; ?>
        <form action="register.php" method="POST" class="bg-white p-6 rounded-lg shadow-sm animate-slide-in">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <?php if (!$show_otp): ?>
                <div class="mb-4">
                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-user-line text-gray-400"></i>
                        </div>
                        <input type="text" id="full_name" name="full_name" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter your full name" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-phone-line text-gray-400"></i>
                        </div>
                        <input type="text" id="phone_number" name="phone_number" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="+254XXXXXXXXX" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-lock-line text-gray-400"></i>
                        </div>
                        <input type="password" id="password" name="password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter your password" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-lock-line text-gray-400"></i>
                        </div>
                        <input type="password" id="confirm_password" name="confirm_password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Confirm your password" required>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="referral_code" class="block text-sm font-medium text-gray-700">Referral Code (Optional)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-links-line text-gray-400"></i>
                        </div>
                        <input type="text" id="referral_code" name="referral_code" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter referral code">
                    </div>
                </div>
                <button type="submit" name="register" class="w-full bg-primary text-white py-3 rounded-button font-semibold hover:bg-indigo-700 transition-colors animate-pulse-cta">Register</button>
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
        <p class="mt-4 text-center text-gray-600">Already have an account? <a href="login.php" class="text-primary hover:underline">Sign In</a></p>
    </div>
</section>

<?php include 'footer.php'; ?>