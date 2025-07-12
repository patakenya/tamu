<?php
include_once 'config.php';

// Redirect logged-in users from index.php to dashboard.php
if (isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) === 'index.php') {
    header('Location: dashboard.php');
    exit;
}

// Validate session and fetch user tier
$user_tier_id = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $user_tier_id = $stmt->get_result()->fetch_assoc()['tier_id'] ?? 0;
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Join our MLM platform in Kenya to earn up to 15% referral commissions, sell e-books, write articles (Ksh 300), and promote affiliate products (Ksh 100/sale). Start for Ksh 300!">
    <meta name="keywords" content="MLM Kenya, passive income, affiliate marketing, content creation, earn online">
    <title>MLM Platform - Grow Your Network</title>
    <link rel="icon" href="fav.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#f97316'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <link rel="stylesheet" href="style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SiteNavigationElement",
        "name": "WealthGrow Navigation",
        "url": [
            {"@type": "WebPage", "name": "Home", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/index.php"},
            {"@type": "WebPage", "name": "How It Works", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/how_it_works.php"},
            {"@type": "WebPage", "name": "Pricing", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/pricing.php"},
            {"@type": "WebPage", "name": "Shop", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/shop.php"},
            <?php if (isset($_SESSION['user_id']) && $user_tier_id >= 2): ?>
            {"@type": "WebPage", "name": "Write Article", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/write_article.php"},
            <?php endif; ?>
            <?php if (isset($_SESSION['user_id']) && $user_tier_id == 3): ?>
            {"@type": "WebPage", "name": "Affiliate Marketing", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/affiliate.php"},
            <?php endif; ?>
            {"@type": "WebPage", "name": "Testimonials", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/testimonials.php"},
            {"@type": "WebPage", "name": "FAQ", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/faq.php"}
        ]
    }
    </script>
</head>
<body>
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <div class="text-2xl font-['Pacifico'] text-primary">WealthGrow</div>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="how_it_works.php" class="text-gray-700 hover:text-primary font-medium transition-colors">How It Works</a>
                    <a href="articles.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Articles</a>
                    <a href="shop.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Shop</a>
                    <?php if (isset($_SESSION['user_id']) && $user_tier_id >= 2): ?>
                        <a href="write_article.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Write Article</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id']) && $user_tier_id == 3): ?>
                        <a href="affiliate.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Affiliate Marketing</a>
                    <?php endif; ?>
                    <a href="testimonials.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Testimonials</a>
                    <a href="faq.php" class="text-gray-700 hover:text-primary font-medium transition-colors">FAQ</a>
                </div>
                <div class="flex items-center space-x-4">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="bg-white text-primary border border-primary px-4 py-2 rounded-button font-medium hover:bg-primary hover:text-white transition-colors animate-pulse-cta whitespace-nowrap">Dashboard</a>
                        <a href="logout.php" class="bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta whitespace-nowrap">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="bg-white text-primary border border-primary px-4 py-2 rounded-button font-medium hover:bg-primary hover:text-white transition-colors animate-pulse-cta whitespace-nowrap">Sign In</a>
                        <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta whitespace-nowrap">Register</a>
                    <?php endif; ?>
                </div>
                <div class="md:hidden flex items-center">
                    <button id="mobile-menu-toggle" class="text-gray-700 hover:text-primary">
                        <div class="w-6 h-6 flex items-center justify-center">
                            <i class="ri-menu-line ri-lg"></i>
                        </div>
                    </button>
                </div>
            </div>
            <!-- Mobile Menu -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="how_it_works.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">How It Works</a>
                    <a href="articles.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Articles</a>
                    <a href="shop.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Shop</a>
                    <?php if (isset($_SESSION['user_id']) && $user_tier_id >= 2): ?>
                        <a href="write_article.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Write Article</a>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id']) && $user_tier_id == 3): ?>
                        <a href="affiliate.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Affiliate Marketing</a>
                    <?php endif; ?>
                    <a href="testimonials.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Testimonials</a>
                    <a href="faq.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">FAQ</a>
                    <!-- <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Dashboard</a>
                        <a href="logout.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Sign In</a>
                        <a href="register.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Register</a>
                    <?php endif; ?> -->
                </div>
            </div>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-toggle').addEventListener('click', () => {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
            const icon = document.querySelector('#mobile-menu-toggle i');
            icon.classList.toggle('ri-menu-line');
            icon.classList.toggle('ri-close-line');
        });
    </script>