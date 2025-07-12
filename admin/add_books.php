<?php
session_start();
include_once '../config.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Initialize CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$admin_id = $_SESSION['admin_id'];
$error = '';
$success = '';

// Fetch admin details
$stmt = $conn->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'], $_POST['title'], $_POST['price'], $_POST['commission'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        $title = trim($_POST['title']);
        $description = trim($_POST['description'] ?? '');
        $price = (float)$_POST['price'];
        $commission = (float)$_POST['commission'];

        // Validate inputs
        if (empty($title) || strlen($title) > 100) {
            $error = 'Title is required and must be 100 characters or less.';
        } elseif ($price <= 0) {
            $error = 'Price must be a positive number.';
        } elseif ($commission < 0) {
            $error = 'Commission must be a non-negative number.';
        } elseif (empty($_FILES['book_file']['name'])) {
            $error = 'A PDF file is required.';
        } else {
            // Validate file
            $file = $_FILES['book_file'];
            $allowed_types = ['application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                $error = 'Only PDF files up to 5MB are allowed.';
            } else {
                // Generate unique filename
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $file_name = uniqid('book_') . '.' . $file_ext;
                $file_path = '/uploads/' . $file_name;

                // Move file
                if (!move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
                    $error = 'Failed to upload file.';
                } else {
                    // Insert book
                    $stmt = $conn->prepare('INSERT INTO books (admin_id, title, description, price, commission, file_path, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $status = 'pending';
                    $stmt->bind_param('issddss', $admin_id, $title, $description, $price, $commission, $file_path, $status);
                    if ($stmt->execute()) {
                        $success = 'Book added successfully. It is pending approval.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = 'Error: Failed to add book.';
                        unlink($upload_dir . $file_name); // Remove uploaded file on failure
                    }
                    $stmt->close();
                }
            }
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Add New Book</h2>
        <p class="text-lg text-gray-600 mb-12">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Add a new book as the author below. Set a commission for users who sell it.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Add Book Form -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <form method="POST" action="add_books.php" enctype="multipart/form-data" id="add-book-form">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Book Title</label>
                        <input type="text" name="title" id="title" required maxlength="100" class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter book title" aria-label="Book Title">
                    </div>
                    <div>
                        <label for="commission" class="block text-sm font-medium text-gray-700">Commission per Sale (Ksh)</label>
                        <input type="number" name="commission" id="commission" step="1" min="0" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter commission" aria-label="Commission">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea name="description" id="description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter book description" aria-label="Book Description"></textarea>
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price (Ksh)</label>
                        <input type="number" name="price" id="price" step="1" min="0" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter price" aria-label="Price">
                    </div>
                    <div>
                        <label for="book_file" class="block text-sm font-medium text-gray-700">Book File (PDF)</label>
                        <input type="file" name="book_file" id="book_file" accept=".pdf" required class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-indigo-700" aria-label="Upload PDF">
                    </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="mt-6 w-full sm:w-auto bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Add book">Add Book</button>
            </form>
        </div>
    </div>
</section>

<script>
document.getElementById('add-book-form')?.addEventListener('submit', (e) => {
    const title = document.getElementById('title').value;
    const price = parseFloat(document.getElementById('price').value);
    const commission = parseFloat(document.getElementById('commission').value);
    const file = document.getElementById('book_file').files[0];

    if (!title || title.length > 100) {
        e.preventDefault();
        alert('Title is required and must be 100 characters or less.');
    } else if (price <= 0) {
        e.preventDefault();
        alert('Price must be a positive number.');
    } else if (commission < 0) {
        e.preventDefault();
        alert('Commission must be a non-negative number.');
    } else if (!file || !file.name.endsWith('.pdf')) {
        e.preventDefault();
        alert('Please upload a valid PDF file.');
    } else if (file.size > 5 * 1024 * 1024) {
        e.preventDefault();
        alert('File size must not exceed 5MB.');
    }
});
</script>

<?php include_once 'footer.php'; ?>
