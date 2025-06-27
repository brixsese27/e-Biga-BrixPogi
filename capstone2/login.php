<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Redirect if already logged in
if (is_logged_in()) {
    $role = get_current_user_role();
    header('Location: ' . ($role === 'admin' ? '/capstone2/admin/dashboard.php' : '/capstone2/resident/dashboard.php'));
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $result = login_user($username, $password);
        
        if (isset($result['success']) && $result['success']) {
            // On successful login, redirect based on role
            $redirect_url = ($result['role'] === 'admin') 
                ? '/capstone2/admin/dashboard.php' 
                : '/capstone2/resident/dashboard.php';
            header('Location: ' . $redirect_url);
            exit();
        } else {
            // On failed login, use the message from the function
            $error = $result['message'] ?? 'An unknown error occurred.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Barangay Biga MIS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
        }
        
        body {
            background-color: #f5f5f5;
            font-size: 16px;
        }
        
        .login-container {
            max-width: 400px;
            margin: 50px auto;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .card-header {
            background-color: var(--primary-color);
            color: white;
            text-align: center;
            border-radius: 15px 15px 0 0 !important;
            padding: 20px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 20px;
            font-size: 18px;
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .form-control {
            padding: 12px;
            font-size: 16px;
            border-radius: 8px;
        }
        
        .form-label {
            font-size: 16px;
            font-weight: 500;
            color: #333;
        }
        
        .alert {
            font-size: 16px;
            border-radius: 8px;
        }
        
        .logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        
        .register-link {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }
        
        .register-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header">
                    <img src="images/R.png" alt="Barangay Biga Logo" class="logo">
                    <h2 class="mb-0">Barangay Biga MIS</h2>
                    <p class="mb-0">Management Information System</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required 
                                   placeholder="Enter your username" autocomplete="username">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Enter your password" autocomplete="current-password">
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                    </form>
                    
                    <div class="register-link">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                    <div class="login-link" style="text-align:center; margin-top:10px;">
                        <a href="forgot_password.php">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 