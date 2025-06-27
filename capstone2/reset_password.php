<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/auth.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } else {
        global $conn;
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt2 = $conn->prepare('UPDATE users SET password = ?, reset_code = NULL, reset_code_expires = NULL WHERE user_id = ?');
            $stmt2->bind_param('si', $hashed_password, $user['user_id']);
            if ($stmt2->execute()) {
                // Fetch username for email notification
                $stmt3 = $conn->prepare('SELECT username FROM users WHERE user_id = ?');
                $stmt3->bind_param('i', $user['user_id']);
                $stmt3->execute();
                $user_row = $stmt3->get_result()->fetch_assoc();
                $username = $user_row ? $user_row['username'] : '';
                // Send password reset notification email
                send_email($email, 'password_reset_notification', [
                    'username' => $username
                ]);
                $success = 'Your password has been reset successfully! You can now <a href="login.php" class="alert-link">login</a>.';
            } else {
                $error = 'Failed to reset password. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Barangay Biga MIS</title>
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
        .reset-container {
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
        .password-requirements {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="reset-container">
            <div class="card">
                <div class="card-header">
                    <img src="images/R.png" alt="Barangay Biga Logo" class="logo">
                    <h2 class="mb-0">Reset Password</h2>
                    <p class="mb-0">Enter your new password</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo $success; ?>
                        </div>
                    <?php else: ?>
                    <form method="POST" action="">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" required minlength="8" placeholder="Enter new password" autocomplete="new-password">
                            <div class="password-requirements">
                                Password must be at least 8 characters long
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8" placeholder="Confirm new password" autocomplete="new-password">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Reset Password</button>
                        </div>
                    </form>
                    <?php endif; ?>
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