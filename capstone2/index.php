<?php
session_start();
// Redirect logged-in users to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'resident';
    header("Location: /capstone2/$role/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Barangay Biga MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .welcome-card {
            max-width: 500px;
            margin: 80px auto;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.08);
        }
        .logo {
            width: 80px;
            height: 80px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card welcome-card text-center p-4">
            <img src="/capstone2/images/R.png" alt="Barangay Biga Logo" class="logo">
            <h2 class="mb-3">Welcome to Barangay Biga MIS</h2>
            <p class="mb-4">A comprehensive system for managing barangay services and resident information.<br>Login or register to get started.</p>
            <a href="/capstone2/login.php" class="btn btn-success btn-lg mb-2">Login</a><br>
            <a href="/capstone2/register.php" class="btn btn-outline-success">Register</a>
        </div>
    </div>
</body>
</html> 