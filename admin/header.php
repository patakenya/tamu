<?php
include_once '../config.php';

// Redirect logged-in admins from admin/index.php to admin/dashboard.php
if (isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) === 'index.php') {
    header('Location: dashboard.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Admin panel for WealthGrow MLM platform to manage transactions, articles, books, and affiliate products.">
    <meta name="keywords" content="MLM admin, WealthGrow admin, manage transactions, affiliate marketing, content approval">
    <title>WealthGrow - Admin Panel</title>
    <link rel="icon" href="../fav.png" type="image/x-icon">
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
    <link rel="stylesheet" href="/style.css">
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SiteNavigationElement",
        "name": "WealthGrow Admin Navigation",
        "url": [
            {"@type": "WebPage", "name": "Dashboard", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/dashboard.php"},
            {"@type": "WebPage", "name": "Users", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/users.php"},
            {"@type": "WebPage", "name": "Articles", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/articles.php"},
            {"@type": "WebPage", "name": "Books", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/books.php"},
            {"@type": "WebPage", "name": "Add Books", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/add_books.php"},
            {"@type": "WebPage", "name": "Affiliate Products", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/affiliate_products.php"},
            {"@type": "WebPage", "name": "Logout", "url": "http://<?php echo htmlspecialchars($_SERVER['HTTP_HOST']); ?>/admin/logout.php"}
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
                    <div class="text-2xl font-['Pacifico'] text-primary">WealthGrow Admin</div>
                </div>
                <?php if (isset($_SESSION['admin_id'])): ?>
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="dashboard.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Dashboard</a>
                        <a href="users.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Users</a>
                        <a href="articles.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Articles</a>
                        <a href="books.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Books</a>
                        <a href="add_books.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Add Books</a>
                        <a href="affiliate_products.php" class="text-gray-700 hover:text-primary font-medium transition-colors">Affiliate Products</a>
                        <a href="logout.php" class="bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta whitespace-nowrap">Logout</a>
                    </div>
                    <div class="md:hidden flex items-center">
                        <button id="mobile-menu-toggle" class="text-gray-700 hover:text-primary">
                            <div class="w-6 h-6 flex items-center justify-center">
                                <i class="ri-menu-line ri-lg"></i>
                            </div>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            <!-- Mobile Menu -->
            <?php if (isset($_SESSION['admin_id'])): ?>
                <div id="mobile-menu" class="hidden md:hidden">
                    <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                        <a href="dashboard.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Dashboard</a>
                        <a href="users.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Users</a>
                        <a href="articles.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Articles</a>
                        <a href="books.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Books</a>
                        <a href="add_books.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Add Books</a>
                        <a href="affiliate_products.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Affiliate Products</a>
                        <a href="logout.php" class="block text-gray-700 hover:text-primary font-medium transition-colors py-2">Logout</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </nav>

    <script>
        // Mobile menu toggle
        document.getElementById('mobile-menu-toggle')?.addEventListener('click', () => {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
            const icon = document.querySelector('#mobile-menu-toggle i');
            icon.classList.toggle('ri-menu-line');
            icon.classList.toggle('ri-close-line');
        });
    </script>
