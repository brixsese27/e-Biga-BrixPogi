<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'resident';
    $is_senior_citizen = 0;
    $birth_date = $_POST['birth_date'] ?? null;
    $address = '';

    // Basic validation
    if (!$first_name || !$last_name || !$email || !$username || !$password || !$role || !$birth_date) {
        header('Location: dashboard.php?error=Please+fill+in+all+fields+including+birth+date');
        exit;
    }
    if ($password !== $confirm_password) {
        header('Location: dashboard.php?error=Passwords+do+not+match');
        exit;
    }

    $result = register_user($username, $password, $email, $role, $is_senior_citizen, $birth_date, $first_name, $last_name, $address);
    if ($result['success']) {
        $params = http_build_query([
            'account_success' => 1,
            'username' => $username,
            'password' => $password,
            'role' => $role
        ]);
        header('Location: dashboard.php?' . $params);
        exit;
    } else {
        $msg = urlencode($result['message'] ?? 'Registration failed');
        header('Location: dashboard.php?error=' . $msg);
        exit;
    }
} else {
    header('Location: dashboard.php');
    exit;
} 