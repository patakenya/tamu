<?php
session_start();
include_once '../config.php';

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password'], $_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validate inputs
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } else {
            // Check admin credentials
            $stmt = $conn->prepare('SELECT id, password FROM admins WHERE email = ?');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($admin = $result->fetch_assoc()) {
                if (password_verify($password, $admin['password'])) {
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'Invalid email or password.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include_once '../header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6 text-center">Admin Login</h2>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <form method="POST" action="login.php" id="login-form">
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                    <input type="email" name="email" id="email" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter email" aria-label="Email">
                </div>
                <div class="mb-4">
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <input type="password" name="password" id="password" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter password" aria-label="Password">
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="w-full bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Login as admin">Login</button>
            </form>
            <p class="mt-4 text-center text-sm text-gray-600">Don't have an account? <a href="register.php" class="text-primary hover:underline">Register here</a></p>
        </div>
    </div>
</section>

<script>
document.getElementById('login-form')?.addEventListener('submit', (e) => {
    const email = document.getElementById('email').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        e.preventDefault();
        alert('Invalid email format.');
    }
});
</script>

<?php include_once 'footer.php'; ?>
