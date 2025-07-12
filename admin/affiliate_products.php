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

// Fetch affiliate products
$stmt = $conn->prepare('SELECT id, name, price, commission, original_url, featured_image, created_at FROM affiliate_products ORDER BY created_at DESC');
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle add/edit/delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        if (isset($_POST['add_product'])) {
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $commission = (float)$_POST['commission'];
            $original_url = trim($_POST['original_url']);
            $featured_image = null;

            // Validate inputs
            if (empty($name) || $price <= 0 || $commission <= 0 || empty($original_url) || !filter_var($original_url, FILTER_VALIDATE_URL)) {
                $error = 'All fields are required, and URL must be valid.';
            } else {
                // Handle file upload
                if (!empty($_FILES['featured_image']['name'])) {
                    $file = $_FILES['featured_image'];
                    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                        $error = 'Only PNG, JPG, or JPEG files up to 2MB are allowed.';
                    } else {
                        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $file_name = uniqid('product_') . '.' . $file_ext;
                        $file_path = '/uploads/' . $file_name;

                        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
                            $error = 'Failed to upload image.';
                        } else {
                            $featured_image = $file_path;
                        }
                    }
                }

                if (!$error) {
                    $stmt = $conn->prepare('INSERT INTO affiliate_products (name, price, commission, original_url, featured_image) VALUES (?, ?, ?, ?, ?)');
                    $stmt->bind_param('sddss', $name, $price, $commission, $original_url, $featured_image);
                    if ($stmt->execute()) {
                        $success = 'Product added successfully.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = 'Error: Failed to add product.';
                        if ($featured_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $featured_image)) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $featured_image);
                        }
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['edit_product']) && isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
            $name = trim($_POST['name']);
            $price = (float)$_POST['price'];
            $commission = (float)$_POST['commission'];
            $original_url = trim($_POST['original_url']);
            $featured_image = $_POST['existing_image'] ?: null;

            // Validate inputs
            if (empty($name) || $price <= 0 || $commission <= 0 || empty($original_url) || !filter_var($original_url, FILTER_VALIDATE_URL)) {
                $error = 'All fields are required, and URL must be valid.';
            } else {
                // Handle file upload
                if (!empty($_FILES['featured_image']['name'])) {
                    $file = $_FILES['featured_image'];
                    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
                    $max_size = 2 * 1024 * 1024; // 2MB
                    if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                        $error = 'Only PNG, JPG, or JPEG files up to 2MB are allowed.';
                    } else {
                        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $file_ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                        $file_name = uniqid('product_') . '.' . $file_ext;
                        $file_path = '/uploads/' . $file_name;

                        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $file_name)) {
                            $error = 'Failed to upload image.';
                        } else {
                            $featured_image = $file_path;
                            // Delete old image if exists
                            if (!empty($_POST['existing_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $_POST['existing_image'])) {
                                unlink($_SERVER['DOCUMENT_ROOT'] . $_POST['existing_image']);
                            }
                        }
                    }
                }

                if (!$error) {
                    $stmt = $conn->prepare('UPDATE affiliate_products SET name = ?, price = ?, commission = ?, original_url = ?, featured_image = ? WHERE id = ?');
                    $stmt->bind_param('sddssi', $name, $price, $commission, $original_url, $featured_image, $product_id);
                    if ($stmt->execute()) {
                        $success = 'Product updated successfully.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = 'Error: Failed to update product.';
                        if ($featured_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $featured_image) && $featured_image !== $_POST['existing_image']) {
                            unlink($_SERVER['DOCUMENT_ROOT'] . $featured_image);
                        }
                    }
                    $stmt->close();
                }
            }
        } elseif (isset($_POST['delete_product']) && isset($_POST['product_id'])) {
            $product_id = (int)$_POST['product_id'];
            // Fetch existing image to delete
            $stmt = $conn->prepare('SELECT featured_image FROM affiliate_products WHERE id = ?');
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $existing_image = $result['featured_image'];
            $stmt->close();

            $stmt = $conn->prepare('DELETE FROM affiliate_products WHERE id = ?');
            $stmt->bind_param('i', $product_id);
            if ($stmt->execute()) {
                if ($existing_image && file_exists($_SERVER['DOCUMENT_ROOT'] . $existing_image)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $existing_image);
                }
                $success = 'Product deleted successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = 'Error: Failed to delete product.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">Manage Affiliate Products</h2>
        <p class="text-lg text-gray-600 mb-12">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Manage affiliate products for Gold-tier users below.</p>
        
        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-6 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- Add Product -->
        <div class="mb-12 bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Add New Product</h3>
            <form method="POST" action="affiliate_products.php" enctype="multipart/form-data" id="add-product-form">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">Product Name</label>
                        <input type="text" name="name" id="name" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter product name" aria-label="Product Name">
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700">Price (Ksh)</label>
                        <input type="number" name="price" id="price" step="0.01" min="0" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter price" aria-label="Price">
                    </div>
                    <div>
                        <label for="commission" class="block text-sm font-medium text-gray-700">Commission (Ksh)</label>
                        <input type="number" name="commission" id="commission" step="0.01" min="0" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter commission" aria-label="Commission">
                    </div>
                    <div>
                        <label for="original_url" class="block text-sm font-medium text-gray-700">Product URL</label>
                        <input type="url" name="original_url" id="original_url" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter product URL" aria-label="Product URL">
                    </div>
                    <div class="sm:col-span-2">
                        <label for="featured_image" class="block text-sm font-medium text-gray-700">Featured Image (PNG/JPG, max 2MB)</label>
                        <input type="file" name="featured_image" id="featured_image" accept=".png,.jpg,.jpeg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-indigo-700" aria-label="Upload Image">
                    </div>
                </div>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" name="add_product" class="mt-4 bg-primary text-white py-2 px-4 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Add product">Add Product</button>
            </form>
        </div>
        
        <!-- Products -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">Affiliate Products</h3>
            <?php if (empty($products)): ?>
                <p class="text-gray-600">No affiliate products available.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Image</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">URL</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php if ($product['featured_image']): ?>
                                            <a href="<?php echo htmlspecialchars($product['featured_image']); ?>" target="_blank">
                                                <img src="<?php echo htmlspecialchars($product['featured_image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-16 h-16 object-cover rounded-md hover:scale-105 transition-transform">
                                            </a>
                                        <?php else: ?>
                                            No Image
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($product['price'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($product['commission'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <a href="<?php echo htmlspecialchars($product['original_url']); ?>" class="text-primary hover:underline" target="_blank">Link</a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($product['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-blue-600 hover:text-blue-800 mr-2 edit-product-btn" 
                                            data-product-id="<?php echo $product['id']; ?>" 
                                            data-name="<?php echo htmlspecialchars($product['name']); ?>" 
                                            data-price="<?php echo $product['price']; ?>" 
                                            data-commission="<?php echo $product['commission']; ?>" 
                                            data-original-url="<?php echo htmlspecialchars($product['original_url']); ?>" 
                                            data-featured-image="<?php echo htmlspecialchars($product['featured_image'] ?: ''); ?>" 
                                            aria-label="Edit product">Edit</button>
                                        <form method="POST" action="affiliate_products.php" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="text-red-600 hover:text-red-800" aria-label="Delete product" onclick="return confirm('Are you sure you want to delete this product?');">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Edit Product Modal -->
<div id="edit-product-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full animate-slide-in">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Edit Product</h3>
        <form method="POST" action="affiliate_products.php" enctype="multipart/form-data" id="edit-product-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="product_id" id="edit-product-id">
            <input type="hidden" name="existing_image" id="edit-existing-image">
            <div class="mb-4">
                <label for="edit-name" class="block text-sm font-medium text-gray-700">Product Name</label>
                <input type="text" name="name" id="edit-name" required class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm py-2 px-3" placeholder="Enter product name" aria-label="Product Name">
            </div>
            <div class="mb-4">
                <label for="edit-price" class="block text-sm font-medium text-gray-700">Price (Ksh)</label>
                <input type="number" name="price" id="edit-price" step="0.01" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter price" aria-label="Price">
            </div>
            <div class="mb-4">
                <label for="edit-commission" class="block text-sm font-medium text-gray-700">Commission (Ksh)</label>
                <input type="number" name="commission" id="edit-commission" step="0.01" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter commission" aria-label="Commission">
            </div>
            <div class="mb-4">
                <label for="edit-original-url" class="block text-sm font-medium text-gray-700">Product URL</label>
                <input type="url" name="original_url" id="edit-original-url" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter product URL" aria-label="Product URL">
            </div>
            <div class="mb-4">
                <label for="edit-featured-image" class="block text-sm font-medium text-gray-700">Featured Image (PNG/JPG, max 2MB)</label>
                <input type="file" name="featured_image" id="edit-featured-image" accept=".png,.jpg,.jpeg" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-primary file:text-white hover:file:bg-indigo-700" aria-label="Upload Image">
                <p id="existing-image-text" class="mt-2 text-sm text-gray-500"></p>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="close-edit-product-modal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-button font-medium hover:bg-gray-400 transition-colors">Cancel</button>
                <button type="submit" name="edit_product" class="bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Save product changes">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-product-btn').forEach(button => {
    button.addEventListener('click', () => {
        const modal = document.getElementById('edit-product-modal');
        const productId = button.getAttribute('data-product-id');
        const name = button.getAttribute('data-name');
        const price = button.getAttribute('data-price');
        const commission = button.getAttribute('data-commission');
        const originalUrl = button.getAttribute('data-original-url');
        const featuredImage = button.getAttribute('data-featured-image');

        document.getElementById('edit-product-id').value = productId;
        document.getElementById('edit-name').value = name;
        document.getElementById('edit-price').value = price;
        document.getElementById('edit-commission').value = commission;
        document.getElementById('edit-original-url').value = originalUrl;
        document.getElementById('edit-existing-image').value = featuredImage;
        document.getElementById('existing-image-text').textContent = featuredImage ? `Current: ${featuredImage}` : 'No image uploaded';

        modal.classList.remove('hidden');
    });
});

document.getElementById('close-edit-product-modal')?.addEventListener('click', () => {
    document.getElementById('edit-product-modal').classList.add('hidden');
});

document.getElementById('add-product-form')?.addEventListener('submit', (e) => {
    const name = document.getElementById('name').value;
    const price = parseFloat(document.getElementById('price').value);
    const commission = parseFloat(document.getElementById('commission').value);
    const originalUrl = document.getElementById('original_url').value;
    const file = document.getElementById('featured_image').files[0];

    if (!name || price <= 0 || commission <= 0 || !originalUrl || !/^(https?:\/\/[^\s$.?#].[^\s]*)$/.test(originalUrl)) {
        e.preventDefault();
        alert('All fields are required, and URL must be valid.');
    } else if (file && (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type) || file.size > 2 * 1024 * 1024)) {
        e.preventDefault();
        alert('Only PNG, JPG, or JPEG files up to 2MB are allowed.');
    }
});

document.getElementById('edit-product-form')?.addEventListener('submit', (e) => {
    const name = document.getElementById('edit-name').value;
    const price = parseFloat(document.getElementById('edit-price').value);
    const commission = parseFloat(document.getElementById('edit-commission').value);
    const originalUrl = document.getElementById('edit-original-url').value;
    const file = document.getElementById('edit-featured-image').files[0];

    if (!name || price <= 0 || commission <= 0 || !originalUrl || !/^(https?:\/\/[^\s$.?#].[^\s]*)$/.test(originalUrl)) {
        e.preventDefault();
        alert('All fields are required, and URL must be valid.');
    } else if (file && (!['image/png', 'image/jpeg', 'image/jpg'].includes(file.type) || file.size > 2 * 1024 * 1024)) {
        e.preventDefault();
        alert('Only PNG, JPG, or JPEG files up to 2MB are allowed.');
    }
});
</script>

<?php include_once 'footer.php'; ?>