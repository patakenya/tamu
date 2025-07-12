<?php
// Generate CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<form action="register.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <div class="mb-6">
        <label for="register-name" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-user-line"></i>
                </div>
            </div>
            <input type="text" id="register-name" name="full_name" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter your full name" required>
        </div>
    </div>
    <div class="mb-6">
        <label for="register-phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-phone-line"></i>
                </div>
            </div>
            <input type="tel" id="register-phone" name="phone_number" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="e.g. 07XXXXXXXX" required>
        </div>
    </div>
    <div class="mb-6">
        <label for="register-password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-lock-line"></i>
                </div>
            </div>
            <input type="password" id="register-password" name="password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Create a password" required>
        </div>
    </div>
    <div class="mb-6">
        <label for="register-confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-lock-line"></i>
                </div>
            </div>
            <input type="password" id="register-confirm-password" name="confirm_password" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Confirm your password" required>
        </div>
    </div>
    <div class="mb-6">
        <label for="referral-code" class="block text-sm font-medium text-gray-700 mb-1">Referral Code (Optional)</label>
        <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <div class="w-5 h-5 flex items-center justify-center text-gray-400">
                    <i class="ri-link-m"></i>
                </div>
            </div>
            <input type="text" id="referral-code" name="referral_code" class="pl-10 w-full py-2 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" placeholder="Enter referral code if any">
        </div>
    </div>
    <div class="mb-6">
        <label class="custom-checkbox">
            <span class="text-sm text-gray-600">I agree to the <a href="#" class="text-primary hover:text-indigo-700">Terms of Service</a> and <a href="#" class="text-primary hover:text-indigo-700">Privacy Policy</a></span>
            <input type="checkbox" name="terms" required>
            <span class="checkmark"></span>
        </label>
    </div>
    <button type="submit" name="register" class="w-full bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap mb-4">Register</button>
</form>
<div class="text-center">
    <p class="text-sm text-gray-600">Already have an account? <a href="login.php" class="text-primary hover:text-indigo-700 font-medium">Sign In</a></p>
</div>