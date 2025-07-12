
-- Create database
CREATE DATABASE IF NOT EXISTS mlm_platform
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE mlm_platform;

-- Tiers table: Stores subscription tier details
CREATE TABLE tiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(10,2) NOT NULL,
    levels_deep INT NOT NULL,
    commission_rates JSON NOT NULL,
    min_withdrawal DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table: Stores user account information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    phone_number VARCHAR(15) NOT NULL,
    password VARCHAR(255) NOT NULL,
    tier_id INT DEFAULT NULL,
    referral_code VARCHAR(10) NOT NULL,
    referred_by INT DEFAULT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    available_balance DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (phone_number),
    UNIQUE (referral_code),
    FOREIGN KEY (tier_id) REFERENCES tiers(id) ON DELETE SET NULL,
    FOREIGN KEY (referred_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_phone_number (phone_number),
    INDEX idx_referral_code (referral_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Pending Payments table: Tracks tier upgrade payments
CREATE TABLE pending_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tier_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tier_id) REFERENCES tiers(id) ON DELETE CASCADE,
    INDEX idx_user_id_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referrals table: Tracks referral relationships
CREATE TABLE referrals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL,
    referred_id INT NOT NULL,
    level INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE (referrer_id, referred_id),
    INDEX idx_referrer_id (referrer_id),
    INDEX idx_referred_id (referred_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transactions table: Records payments, withdrawals, book sales, and commissions
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('payment', 'withdrawal', 'book_sale', 'book_commission', 'article_payment', 'affiliate_commission', 'tier_payment') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_type_status (type, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Admins table: Stores admin account information
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Books table: Stores digital book details (supports both user and admin authors)
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    admin_id INT DEFAULT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    CONSTRAINT chk_author CHECK ((user_id IS NOT NULL AND admin_id IS NULL) OR (user_id IS NULL AND admin_id IS NOT NULL)),
    INDEX idx_title (title),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Book Sales table: Tracks book purchases and commissions
CREATE TABLE book_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    buyer_id INT NOT NULL,
    promoter_id INT DEFAULT NULL,
    sale_amount DECIMAL(10,2) NOT NULL,
    site_commission DECIMAL(10,2) NOT NULL,
    promoter_commission DECIMAL(10,2) DEFAULT 0.00,
    seller_amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (promoter_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_book_id (book_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Articles table: Stores articles submitted by Silver and Gold-tier users
CREATE TABLE articles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comments table: Stores user comments on articles
CREATE TABLE comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    article_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_article_id (article_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate Products table: Stores affiliate products with featured image
CREATE TABLE affiliate_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    original_url VARCHAR(255) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    featured_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate Links table: Tracks user-generated affiliate links
CREATE TABLE affiliate_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    affiliate_code VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES affiliate_products(id) ON DELETE CASCADE,
    UNIQUE (user_id, product_id),
    INDEX idx_affiliate_code (affiliate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Affiliate Sales table: Tracks affiliate sales for Gold-tier users
CREATE TABLE affiliate_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    affiliate_code VARCHAR(20) NOT NULL,
    sale_amount DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES affiliate_products(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_affiliate_code (affiliate_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert tiers
INSERT INTO tiers (name, price, levels_deep, commission_rates, min_withdrawal) VALUES
('Bronze', 300.00, 2, '{"1": 0.07, "2": 0.03}', 500.00),
('Silver', 500.00, 3, '{"1": 0.10, "2": 0.05, "3": 0.03}', 300.00),
('Gold', 1000.00, 5, '{"1": 0.15, "2": 0.08, "3": 0.05, "4": 0.03, "5": 0.02}', 200.00);

-- Insert test admin
INSERT INTO admins (username, email, password) VALUES
('admin1', 'admin1@example.com', '$2y$10$examplehashedpasswordadmin1');

-- Insert test users
INSERT INTO users (full_name, phone_number, password, referral_code, tier_id, referred_by, is_verified, available_balance) VALUES
('John Doe', '+254712345678', '$2y$10$examplehashedpassword12345', 'JD123', 3, NULL, TRUE, 550.00),
('Jane Smith', '+254723456789', '$2y$10$examplehashedpassword67890', 'JS456', 2, 1, TRUE, 300.00),
('Bob Johnson', '+254734567890', '$2y$10$examplehashedpassword09876', 'BJ789', 1, 1, TRUE, 50.00),
('Alice Brown', '+254745678901', '$2y$10$examplehashedpassword54321', 'AB012', NULL, 2, TRUE, 0.00);

-- Insert test referrals
INSERT INTO referrals (referrer_id, referred_id, level) VALUES
(1, 2, 1),
(1, 3, 1),
(2, 4, 1);

-- Insert test books (mix of user and admin authored)
INSERT INTO books (user_id, admin_id, title, description, price, commission, file_path, status) VALUES
(1, NULL, 'Mastering Wealth', 'A guide to financial freedom', 500.00, 50.00, '/uploads/mastering_wealth.pdf', 'approved'),
(1, NULL, 'Success Mindset', 'Unlock your potential', 750.00, 75.00, '/uploads/success_mindset.pdf', 'pending'),
(NULL, 1, 'Digital Marketing 101', 'Learn online marketing', 600.00, 60.00, '/uploads/digital_marketing.pdf', 'approved'),
(NULL, 1, 'Financial Freedom', 'Path to wealth', 800.00, 80.00, '/uploads/financial_freedom.pdf', 'pending');

-- Insert test transactions
INSERT INTO transactions (user_id, type, amount, status) VALUES
(1, 'book_sale', 500.00, 'approved'),
(1, 'book_commission', 50.00, 'approved'),
(2, 'article_payment', 300.00, 'approved'),
(3, 'withdrawal', 500.00, 'pending'),
(1, 'tier_payment', 1000.00, 'approved'),
(1, 'payment', 500.00, 'pending');

-- Insert test book sales
INSERT INTO book_sales (book_id, buyer_id, promoter_id, sale_amount, site_commission, promoter_commission, seller_amount) VALUES
(1, 2, 3, 500.00, 50.00, 50.00, 400.00),
(3, 4, NULL, 600.00, 60.00, 0.00, 540.00),
(3, 2, 1, 600.00, 60.00, 60.00, 480.00);

-- Insert test articles
INSERT INTO articles (user_id, title, content, featured_image, status) VALUES
(2, 'How to Save Money', 'Content about saving...', '/uploads/save_money.jpg', 'approved'),
(2, 'Investing Basics', 'Content about investing...', NULL, 'pending');

-- Insert test comments
INSERT INTO comments (article_id, user_id, content) VALUES
(1, 3, 'Great tips on saving money!'),
(1, 4, 'Really helpful article.');

-- Insert test affiliate products with featured images
INSERT INTO affiliate_products (name, description, price, original_url, commission, featured_image) VALUES
('Jumia Smartphone', 'Latest Android smartphone with 64GB storage.', 15000.00, 'https://jumia.co.ke/smartphone', 100.00, '/uploads/smartphone.jpg'),
('Online Course: Digital Marketing', 'Learn digital marketing from top experts.', 5000.00, 'https://example.com/course', 100.00, '/uploads/course.jpg'),
('Fashion Sneakers', 'Trendy sneakers for all occasions.', 3000.00, 'https://jumia.co.ke/sneakers', 100.00, '/uploads/sneakers.jpg');

-- Insert test affiliate links
INSERT INTO affiliate_links (user_id, product_id, affiliate_code) VALUES
(1, 1, 'JD123_PHONE'),
(1, 2, 'JD123_COURSE'),
(1, 3, 'JD123_SNEAKERS');

-- Insert test affiliate sales
INSERT INTO affiliate_sales (user_id, product_id, affiliate_code, sale_amount, commission) VALUES
(1, 1, 'JD123_PHONE', 15000.00, 100.00),
(1, 2, 'JD123_COURSE', 5000.00, 100.00);

-- Insert test pending payments
INSERT INTO pending_payments (user_id, tier_id, amount, status) VALUES
(4, 1, 300.00, 'pending'),
(3, 2, 500.00, 'approved');
