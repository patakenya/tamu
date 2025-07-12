```php
<?php
session_start();
include_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['book_id'], $_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
    $_SESSION['error'] = 'Invalid request.';
    header('Location: shop.php');
    exit;
}

$book_id = (int)$_GET['book_id'];
$ref_code = $_GET['ref'] ?? '';
$stmt = $conn->prepare('SELECT price FROM books WHERE id = ? AND status = "approved"');
$stmt->bind_param('i', $book_id);
if (!$stmt->execute()) {
    error_log('Failed to fetch book: book_id=' . $book_id . ', error=' . $stmt->error . ', time=' . date('Y-m-d H:i:s'));
    $_SESSION['error'] = 'An error occurred. Please try again.';
    header('Location: shop.php');
    exit;
}
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    $_SESSION['error'] = 'Book not found or not approved.';
    header('Location: shop.php');
    exit;
}

// Redirect to pay.php with book purchase details
header("Location: pay.php?type=book&book_id=$book_id&amount=" . $book['price'] . "&ref=$ref_code");
exit;
?>
```