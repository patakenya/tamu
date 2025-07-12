<?php
session_start();

// Clear session data
$_SESSION = [];
session_destroy();

// Clear remember-me cookie
if (isset($_COOKIE['user_id'])) {
    setcookie('user_id', '', time() - 3600, '/');
}

// Redirect to index.php
header('Location: index.php');
exit;
?>