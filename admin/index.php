<?php
session_start();
include_once '../config.php';

// Redirect authenticated admins to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Fetch basic metrics for public view
$stmt = $conn->prepare('SELECT COUNT(*) as total_users FROM users');
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total_users'];
$stmt->close();

$stmt = $conn->prepare('SELECT SUM(sale_amount) as total_revenue FROM book_sales WHERE sale_amount > 0');
$stmt->execute();
$total_revenue = $stmt->get_result()->fetch_assoc()['total_revenue'] ?? 0;
$stmt->close();

$stmt = $conn->prepare('SELECT SUM(sale_amount) as affiliate_revenue FROM affiliate_sales WHERE sale_amount > 0');
$stmt->execute();
$total_revenue += $stmt->get_result()->fetch_assoc()['affiliate_revenue'] ?? 0;
$stmt->close();
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Hero Section -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4 animate-slide-in">Welcome to WealthGrow Admin Panel</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto mb-8">Manage users, transactions, books, articles, and affiliate products to grow the WealthGrow MLM platform.</p>
            <div class="flex justify-center space-x-4">
                <a href="login.php" class="bg-primary text-white py-2 px-6 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Login to admin panel">
                    <i class="ri-login-box-line mr-2"></i> Login
                </a>
                <a href="register.php" class="bg-secondary text-white py-2 px-6 rounded-button font-medium hover:bg-orange-600 transition-colors animate-pulse-cta" aria-label="Register as admin">
                    <i class="ri-admin-line mr-2"></i> Register
                </a>
            </div>
        </div>

        <!-- Metrics Section -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-user-line ri-2x mr-3"></i>
                    <h3 class="text-lg font-semibold">Total Users</h3>
                </div>
                <p class="text-3xl font-bold"><?php echo htmlspecialchars($total_users); ?></p>
            </div>
            <div class="bg-gradient-to-r from-orange-500 to-yellow-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-money-dollar-circle-line ri-2x mr-3"></i>
                    <h3 class="text-lg font-semibold">Total Revenue</h3>
                </div>
                <p class="text-3xl font-bold">Ksh <?php echo htmlspecialchars(number_format($total_revenue, 2)); ?></p>
            </div>
        </div>
    </div>
</section>

<?php include_once 'footer.php'; ?>
