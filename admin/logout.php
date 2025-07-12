<?php
session_start();

// Unset admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['csrf_token']);

// Destroy session
session_destroy();

// Redirect to login
header('Location: login.php');
exit;
?>