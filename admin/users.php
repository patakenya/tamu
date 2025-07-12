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

// Fetch tiers for dropdown
$stmt = $conn->prepare('SELECT id, name FROM tiers');
$stmt->execute();
$tiers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch user metrics
$stmt = $conn->prepare('SELECT COUNT(*) as total_users FROM users');
$stmt->execute();
$total_users = $stmt->get_result()->fetch_assoc()['total_users'];
$stmt->close();

$stmt = $conn->prepare('SELECT COUNT(*) as verified_users FROM users WHERE is_verified = TRUE');
$stmt->execute();
$verified_users = $stmt->get_result()->fetch_assoc()['verified_users'];
$stmt->close();

// Fetch users with search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$users = [];
if ($search) {
    $stmt = $conn->prepare('SELECT u.id, u.full_name, u.phone_number, u.tier_id, u.available_balance, u.is_verified, u.created_at, t.name as tier_name 
                            FROM users u LEFT JOIN tiers t ON u.tier_id = t.id 
                            WHERE u.full_name LIKE ? OR u.phone_number LIKE ? 
                            ORDER BY u.created_at DESC');
    $search_param = "%$search%";
    $stmt->bind_param('ss', $search_param, $search_param);
} else {
    $stmt = $conn->prepare('SELECT u.id, u.full_name, u.phone_number, u.tier_id, u.available_balance, u.is_verified, u.created_at, t.name as tier_name 
                            FROM users u LEFT JOIN tiers t ON u.tier_id = t.id 
                            ORDER BY u.created_at DESC');
}
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Handle edit/delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['csrf_token'])) {
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Invalid CSRF token.';
    } else {
        if (isset($_POST['edit_user']) && isset($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            $full_name = trim($_POST['full_name']);
            $phone_number = trim($_POST['phone_number']);
            $tier_id = !empty($_POST['tier_id']) ? (int)$_POST['tier_id'] : null;
            $is_verified = isset($_POST['is_verified']) ? 1 : 0;

            if (empty($full_name) || strlen($full_name) > 255) {
                $error = 'Full name is required and must be 255 characters or less.';
            } elseif (empty($phone_number) || !preg_match('/^\+254[0-9]{9}$/', $phone_number)) {
                $error = 'Valid phone number (+254 format) is required.';
            } else {
                $stmt = $conn->prepare('SELECT id FROM users WHERE phone_number = ? AND id != ?');
                $stmt->bind_param('si', $phone_number, $user_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    $error = 'Phone number already exists.';
                } else {
                    $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone_number = ?, tier_id = ?, is_verified = ? WHERE id = ?');
                    $stmt->bind_param('ssiii', $full_name, $phone_number, $tier_id, $is_verified, $user_id);
                    if ($stmt->execute()) {
                        $success = 'User updated successfully.';
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    } else {
                        $error = 'Error: Failed to update user.';
                    }
                }
                $stmt->close();
            }
        } elseif (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
            $user_id = (int)$_POST['user_id'];
            $stmt = $conn->prepare('DELETE FROM users WHERE id = ?');
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $success = 'User deleted successfully.';
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } else {
                $error = 'Error: Failed to delete user.';
            }
            $stmt->close();
        }
    }
}
?>

<?php include_once 'header.php'; ?>

<section class="py-16 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900">Manage Users</h2>
            <a href="logout.php" class="flex items-center text-primary hover:text-indigo-700 font-medium transition-colors" aria-label="Logout">
                <i class="ri-logout-box-line mr-2"></i> Logout
            </a>
        </div>
        <p class="text-lg text-gray-600 mb-12">Welcome, <?php echo htmlspecialchars($admin['username']); ?>. Manage user accounts below.</p>
        
        <?php if ($error): ?>
            <div class="mb-8 p-4 bg-red-100 text-red-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="mb-8 p-4 bg-green-100 text-green-700 rounded-lg animate-slide-in"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <!-- User Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-12">
            <div class="bg-gradient-to-r from-indigo-600 to-blue-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-user-line ri-2x mr-3"></i>
                    <h3 class="text-lg font-semibold">Total Users</h3>
                </div>
                <p class="text-3xl font-bold"><?php echo htmlspecialchars($total_users); ?></p>
            </div>
            <div class="bg-gradient-to-r from-green-500 to-teal-500 text-white p-6 rounded-lg shadow-md hover:shadow-lg transition-shadow animate-slide-in">
                <div class="flex items-center mb-4">
                    <i class="ri-check-double-line ri-2x mr-3"></i>
                    <h3 class="text-lg font-semibold">Verified Users</h3>
                </div>
                <p class="text-3xl font-bold"><?php echo htmlspecialchars($verified_users); ?></p>
            </div>
        </div>
        
        <!-- Search Bar -->
        <div class="mb-8">
            <form method="GET" action="users.php" class="flex items-center">
                <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name or phone number" class="w-full sm:w-1/2 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" aria-label="Search users">
                <button type="submit" class="ml-2 bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Search">
                    <i class="ri-search-line mr-1"></i> Search
                </button>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
            <h3 class="text-xl font-semibold text-gray-900 mb-4">User Accounts</h3>
            <?php if (empty($users)): ?>
                <p class="text-gray-600">No users found.</p>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Verified</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($users as $user): ?>
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($user['phone_number']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $user['tier_id'] ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo htmlspecialchars($user['tier_name'] ?? 'None'); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Ksh <?php echo htmlspecialchars(number_format($user['available_balance'], 2)); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php echo $user['is_verified'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $user['is_verified'] ? 'Verified' : 'Unverified'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <button class="text-blue-600 hover:text-blue-800 mr-2 edit-user-btn" 
                                            data-user-id="<?php echo $user['id']; ?>" 
                                            data-full-name="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                            data-phone-number="<?php echo htmlspecialchars($user['phone_number']); ?>" 
                                            data-tier-id="<?php echo $user['tier_id'] ?? ''; ?>" 
                                            data-is-verified="<?php echo $user['is_verified'] ? '1' : '0'; ?>" 
                                            aria-label="Edit user">Edit</button>
                                        <form method="POST" action="users.php" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="text-red-600 hover:text-red-800" aria-label="Delete user" onclick="return confirm('Are you sure you want to delete this user? This will also delete related data.');">Delete</button>
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

<!-- Edit User Modal -->
<div id="edit-user-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded-lg shadow-lg max-w-md w-full animate-slide-in">
        <h3 class="text-xl font-semibold text-gray-900 mb-4">Edit User</h3>
        <form method="POST" action="users.php" id="edit-user-form">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <input type="hidden" name="user_id" id="edit-user-id">
            <div class="mb-4">
                <label for="edit-full-name" class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="full_name" id="edit-full-name" required maxlength="255" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Enter full name" aria-label="Full Name">
            </div>
            <div class="mb-4">
                <label for="edit-phone-number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <input type="text" name="phone_number" id="edit-phone-number" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="+254123456789" aria-label="Phone Number">
            </div>
            <div class="mb-4">
                <label for="edit-tier-id" class="block text-sm font-medium text-gray-700">Tier</label>
                <select name="tier_id" id="edit-tier-id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" aria-label="Select Tier">
                    <option value="">None</option>
                    <?php foreach ($tiers as $tier): ?>
                        <option value="<?php echo $tier['id']; ?>"><?php echo htmlspecialchars($tier['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700">
                    <input type="checkbox" name="is_verified" id="edit-is-verified" class="mr-2">
                    Verified
                </label>
            </div>
            <div class="flex justify-end space-x-4">
                <button type="button" id="close-edit-user-modal" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-button font-medium hover:bg-gray-400 transition-colors">Cancel</button>
                <button type="submit" name="edit_user" class="bg-primary text-white px-4 py-2 rounded-button font-medium hover:bg-indigo-700 transition-colors animate-pulse-cta" aria-label="Save user changes">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.edit-user-btn').forEach(button => {
    button.addEventListener('click', () => {
        const modal = document.getElementById('edit-user-modal');
        const userId = button.getAttribute('data-user-id');
        const fullName = button.getAttribute('data-full-name');
        const phoneNumber = button.getAttribute('data-phone-number');
        const tierId = button.getAttribute('data-tier-id');
        const isVerified = button.getAttribute('data-is-verified');

        document.getElementById('edit-user-id').value = userId;
        document.getElementById('edit-full-name').value = fullName;
        document.getElementById('edit-phone-number').value = phoneNumber;
        document.getElementById('edit-tier-id').value = tierId;
        document.getElementById('edit-is-verified').checked = isVerified === '1';

        modal.classList.remove('hidden');
    });
});

document.getElementById('close-edit-user-modal')?.addEventListener('click', () => {
    document.getElementById('edit-user-modal').classList.add('hidden');
});

document.getElementById('edit-user-form')?.addEventListener('submit', (e) => {
    const fullName = document.getElementById('edit-full-name').value;
    const phoneNumber = document.getElementById('edit-phone-number').value;
    if (!fullName || fullName.length > 255) {
        e.preventDefault();
        alert('Full name is required and must be 255 characters or less.');
    } else if (!phoneNumber || !/^\+254[0-9]{9}$/.test(phoneNumber)) {
        e.preventDefault();
        alert('Valid phone number (+254 format) is required.');
    }
});
</script>

<?php include_once 'footer.php'; ?>
