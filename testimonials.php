<?php
session_start();
include_once 'config.php';
?>

<?php include 'header.php'; ?>

<section class="py-16 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
            <h2 class="text-3xl font-bold text-gray-900">What Our Members Say</h2>
            <p class="mt-4 text-lg text-gray-600 max-w-3xl mx-auto">Hear from our successful members who have transformed their financial future with our platform.</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"I was skeptical at first, but after joining the Gold tier, I've been able to build a team of over 50 people in just 3 months. My monthly earnings have exceeded Ksh 30,000!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">David Mwangi</h4>
                        <p class="text-sm text-gray-500">Gold Member, Nairobi</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-half-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"I started with the Silver tier and was able to upgrade to Gold within a month. The platform is user-friendly and the payment system is seamless. I've already referred 12 people!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">Faith Wambui</h4>
                        <p class="text-sm text-gray-500">Gold Member, Mombasa</p>
                    </div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <div class="flex items-center mb-4">
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                    <div class="w-5 h-5 flex items-center justify-center text-yellow-400 mr-1">
                        <i class="ri-star-fill"></i>
                    </div>
                </div>
                <p class="text-gray-600 mb-6">"Even with the Bronze tier, I've been able to earn a decent side income. The commission structure is fair and transparent. I'm planning to upgrade to Silver soon!"</p>
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center mr-3">
                        <div class="w-6 h-6 flex items-center justify-center text-gray-500">
                            <i class="ri-user-line"></i>
                        </div>
                    </div>
                    <div>
                        <h4 class="font-medium text-gray-900">James Odhiambo</h4>
                        <p class="text-sm text-gray-500">Bronze Member, Kisumu</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-12 text-center">
            <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'register.php'; ?>" class="bg-primary text-white px-8 py-3 rounded-button font-medium hover:bg-indigo-700 transition-colors whitespace-nowrap">Join Our Community</a>
        </div>
    </div>
</section>

<?php include 'footer.php'; ?>