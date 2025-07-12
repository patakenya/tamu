```php
<?php
session_start();
include_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['product_id'], $_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: shop.php');
    exit;
}

$product_id = (int)$_GET['product_id'];
$ref_code = $_GET['ref'] ?? '';
$stmt = $conn->prepare('SELECT price, original_url FROM affiliate_products WHERE id = ?');
$stmt->bind_param('i', $product_id);
if (!$stmt->execute()) {
    error_log('Failed to fetch product: product_id=' . $product_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: shop.php');
    exit;
}
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = 'Product not found.';
    header('Location: shop.php');
    exit;
}

// Redirect to pay.php with product purchase details
header("Location: pay.php?type=product&product_id=$product_id&amount=" . $product['price'] . "&ref=$ref_code&original_url=" . urlencode($product['original_url']));
exit;
?>
```