<?php
require_once 'includes/auth.php';

$error = '';
$success = '';
$email = $_GET['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize_input($_POST['email'] ?? '');
    $code = sanitize_input($_POST['code'] ?? '');
    if (empty($email) || empty($code)) {
        $error = 'Please enter the code sent to your email.';
    } else {
        global $conn;
        $stmt = $conn->prepare('SELECT user_id, reset_code, reset_code_expires FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['reset_code'] === $code && $user['reset_code_expires'] && strtotime($user['reset_code_expires']) > time()) {
                // Code is valid, redirect to reset_password.php
                header('Location: reset_password.php?email=' . urlencode($email));
                exit();
            } else {
                $error = 'Invalid or expired code. Please try again.';
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
    <title>Verify Code - Barangay Biga MIS</title>
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
        .verify-container {
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
            letter-spacing: 8px;
            text-align: center;
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
        <div class="verify-container">
            <div class="card">
                <div class="card-header">
                    <img src="images/R.png" alt="Barangay Biga Logo" class="logo">
                    <h2 class="mb-0">Verify Code</h2>
                    <p class="mb-0">Enter the 6-digit code sent to your email</p>
                </div>
                <div class="card-body p-4">
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="">
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                        <div class="mb-3">
                            <label for="code" class="form-label">6-Digit Code</label>
                            <input type="text" class="form-control" id="code" name="code" maxlength="6" minlength="6" required pattern="[0-9]{6}" placeholder="------" autocomplete="one-time-code">
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Verify</button>
                        </div>
                    </form>
                    <div class="login-link">
                        <a href="forgot_password.php">Back to Forgot Password</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 