<?php
session_start();
include_once 'config.php';

// Redirect if not logged in or not Gold-tier
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$stmt = $conn->prepare('SELECT tier_id FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$tier_id = $stmt->get_result()->fetch_assoc()['tier_id'];
$stmt->close();
if ($tier_id != 3) {
    header('Location: select_tier.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_books'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);

    // Validate inputs
    if (empty($title) || strlen($title) < 3) {
        $error = 'Title must be at least 3 characters.';
    } elseif (empty($description)) {
        $error = 'Description is required.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than zero.';
    } elseif (!isset($_FILES['book_file']) || $_FILES['book_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please upload a valid PDF file.';
    } else {
        // Validate file
        $file = $_FILES['book_file'];
        $allowed_types = ['application/pdf'];
        $max_size = 5 * 1024 * 1024; // 5MB
        if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
            $error = 'Only PDF files up to 5MB are allowed.';
        } else {
            // Save file
            $upload_dir = 'uploads/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            $file_name = uniqid() . '_' . basename($file['name']);
            $file_path = $upload_dir . $file_name;
            if (move_uploaded_file($file['tmp_name'], $file_path)) {
                // Insert book
                $stmt = $conn->prepare('INSERT INTO books (user_id, title, description, price, file_path) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('issds', $_SESSION['user_id'], $title, $description, $price, $file_path);
                if ($stmt->execute()) {
                    $success = 'Book uploaded successfully!';
                } else {
                    $error = 'Failed to upload book: ' . $conn->error;
                }
                $stmt->close();
            } else {
                $error = 'Failed to save file.';
            }
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50">
    <div class="max-w-md mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 text-center mb-6">Upload a Digital Book</h2>
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <form action="add_books.php" method="POST" enctype="multipart/form-data">
                <div class="mb-6">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Book Title</label>
                    <input type="text" id="title" name="title" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                </div>
                <div class="mb-6">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea id="description" name="description" rows="4" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required></textarea>
                </div>
                <div class="mb-6">
                    <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price (Ksh)</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                </div>
                <div class="mb-6">
                    <label for="book_file" class="block text-sm font-medium text-gray-700 mb-1">Upload PDF</label>
                    <input type="file" id="book_file" name="book_file" accept=".pdf" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-primary focus:border-primary" required>
                </div>
                <button type="submit" name="upload_book" class="w-full bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors">Upload Book</button>
            </form>
        </div>
    </div>
</section>

<?php include_once 'footer.php'; ?>