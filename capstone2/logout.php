<?php
require_once 'includes/auth.php';

// Log the logout action
if (isset($_SESSION['user_id'])) {
    global $conn;
    log_activity($conn, $_SESSION['user_id'], 'logout', 'User logged out');
}

// Perform logout
logout_user();

// Redirect to login page
header('Location: login.php');
exit();
?> 