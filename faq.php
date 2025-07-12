<?php
session_start();
include_once 'config.php';
?>

<?php include 'header.php'; ?>

<section class="py-16 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900">Frequently Asked Questions</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Find answers to common questions about our multi-level marketing platform.</p>
        </div>
        <div class="max-w-3xl mx-auto">
            <div class="mb-6">
                <button class="flex justify-between items-center w-full text-left font-medium text-gray-900 p-4 bg-gray-50 rounded-lg focus:outline-none" id="faq-1-button">
                    <span>How do I get started?</span>
                    <div class="w-5 h-5 flex items-center justify-center text-primary">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </button>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg hidden" id="faq-1-content">
                    <p class="text-gray-600">Getting started is easy! Simply register with your phone number, verify with OTP, choose your subscription tier, make payment via MPESA, and start referring others to earn commissions.</p>
                </div>
            </div>
            <div class="mb-6">
                <button class="flex justify-between items-center w-full text-left font-medium text-gray-900 p-4 bg-gray-50 rounded-lg focus:outline-none" id="faq-2-button">
                    <span>How much can I earn?</span>
                    <div class="w-5 h-5 flex items-center justify-center text-primary">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </button>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg hidden" id="faq-2-content">
                    <p class="text-gray-600">Your earnings depend on your subscription tier and the number of referrals in your downline. Gold members can earn from all 5 levels with higher commission rates, while Bronze members earn from 2 levels with lower rates. Some of our top performers earn over Ksh 50,000 per month.</p>
                </div>
            </div>
            <div class="mb-6">
                <button class="flex justify-between items-center w-full text-left font-medium text-gray-900 p-4 bg-gray-50 rounded-lg focus:outline-none" id="faq-3-button">
                    <span>When and how can I withdraw my earnings?</span>
                    <div class="w-5 h-5 flex items-center justify-center text-primary">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </button>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg hidden" id="faq-3-content">
                    <p class="text-gray-600">You can request withdrawals once you've reached the minimum threshold for your tier (Gold: Ksh 200, Silver: Ksh 300, Bronze: Ksh 500). Withdrawals are processed within 24 hours and sent directly to your MPESA account. You must have referred at least 3 people to unlock withdrawals.</p>
                </div>
            </div>
            <div class="mb-6">
                <button class="flex justify-between items-center w-full text-left font-medium text-gray-900 p-4 bg-gray-50 rounded-lg focus:outline-none" id="faq-4-button">
                    <span>Can I upgrade my subscription tier?</span>
                    <div class="w-5 h-5 flex items-center justify-center text-primary">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </button>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg hidden" id="faq-4-content">
                    <p class="text-gray-600">Yes, you can upgrade your subscription tier at any time. You'll only need to pay the difference between your current tier and the new tier. Your downline structure and earnings will be preserved, but you'll start earning at the new tier's commission rates immediately after upgrading.</p>
                </div>
            </div>
            <div class="mb-6">
                <button class="flex justify-between items-center w-full text-left font-medium text-gray-900 p-4 bg-gray-50 rounded-lg focus:outline-none" id="faq-5-button">
                    <span>Is there a recurring subscription fee?</span>
                    <div class="w-5 h-5 flex items-center justify-center text-primary">
                        <i class="ri-arrow-down-s-line"></i>
                    </div>
                </button>
                <div class="mt-2 p-4 bg-gray-50 rounded-lg hidden" id="faq-5-content">
                    <p class="text-gray-600">No, our subscription is a one-time payment. Once you've paid for your tier, you'll have lifetime access to the platform and can earn commissions as long as you remain active by referring at least one new member every 6 months.</p>
                </div>
            </div>
        </div>
        <div class="mt-12 text-center">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="bg-primary text-white px-8 py-3 rounded-button font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">Get Started Now</a>
        </div>
    </div>
</section>

<script src="script.js"></script>
<?php include 'footer.php'; ?>