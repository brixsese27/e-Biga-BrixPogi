<?php
require_once 'includes/auth.php';
require_once 'config/email.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    if (empty($email)) {
        $error = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format.';
    } else {
        global $conn;
        $stmt = $conn->prepare('SELECT user_id, username FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $reset_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
            $stmt2 = $conn->prepare('UPDATE users SET reset_code = ?, reset_code_expires = ? WHERE user_id = ?');
            $stmt2->bind_param('ssi', $reset_code, $expires, $user['user_id']);
            $stmt2->execute();
            // Prepare email data
            $email_data = [
                'username' => $user['username'],
                'email' => $email,
                'reset_code' => $reset_code
            ];
            if (send_email($email, 'password_reset_code', $email_data)) {
                $success = 'A 6-digit code has been sent to your email. Please check your inbox.';
                // Optionally, redirect to verify_code.php?email=...
                header('Location: verify_code.php?email=' . urlencode($email));
                exit();
            } else {
                $error = 'Failed to send reset code. Please try again later.';
            }
        } else {
            $error = 'Email is not registered.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Barangay Biga MIS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .forgot-container {
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
        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
        }
        .login-link a {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="forgot-container">
            <div class="card">
                <div class="card-header">
                    <img src="images/R.png" alt="Barangay Biga Logo" class="logo">
                    <h2 class="mb-0">Forgot Password</h2>
                    <p class="mb-0">Enter your email to reset your password</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email" autocomplete="email">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Send Code</button>
                        </div>
                    </form>
                    <div class="login-link">
                        <a href="login.php">Back to Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 