<?php
// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form action="login.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-6">
        <label for="login-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-phone-line"></i>
                </div>
            </div>
            <input type="tel" id="login-phone" name="phone_number" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="e.g. 07XXXXXXXX" required>
        </div>
    </div>
    <div class="mb-6">
        <label for="login-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-lock-line"></i>
                </div>
            </div>
            <input type="password" id="login-password" name="password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter your password" required>
        </div>
    </div>
    <div class="flex items-center justify-between mb-6">
        <label class="custom-checkbox">
            <span class="text-sm text-gray-600">Remember me</span>
            <input type="checkbox" name="remember_me">
            <span class="checkmark"></span>
        </label>
        <a href="#" class="text-sm text-primary hover:text-indigo-700">Forgot password?</a>
    </div>
    <button type="submit" name="login" class="w-full bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap mb-4">Sign In</button>
</form>
<div class="text-center">
    <p class="text-sm text-gray-600">Don't have an account? <a href="register.php" class="text-primary hover:text-indigo-700 font-medium">Register</a></p>
</div>