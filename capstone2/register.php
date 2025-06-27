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
$success = '';

// Detect admin registration mode
$is_admin_registration = isset($_GET['admin']) && $_GET['admin'] == '1';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $email = sanitize_input($_POST['email'] ?? '');
    $birth_date = sanitize_input($_POST['birth_date'] ?? '');
    $first_name = sanitize_input($_POST['first_name'] ?? '');
    $last_name = sanitize_input($_POST['last_name'] ?? '');
    $address = sanitize_input($_POST['address'] ?? '');
    $agreed = isset($_POST['terms']);
    
    // Compute age
    $age = 0;
    if ($birth_date) {
        $dob = new DateTime($birth_date);
        $now = new DateTime();
        $age = $now->diff($dob)->y;
    }
    $is_senior_citizen = ($age >= 60) ? true : false;
    $role = $is_admin_registration ? 'admin' : 'resident';
    
    // Validate input
    if (empty($username) || empty($password) || empty($confirm_password) || empty($email) || empty($birth_date) || empty($first_name) || empty($last_name) || empty($address)) {
        $error = 'All fields are required';
    } elseif (!$agreed) {
        $error = 'You must agree to the Terms and Conditions';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        $result = register_user($username, $password, $email, $role, $is_senior_citizen, $birth_date, $first_name, $last_name, $address);
        
        if ($result['success']) {
            $success = $result['message'] ?? 'Registration successful! You can now login.';
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Barangay Biga MIS</title>
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
        
        .register-container {
            max-width: 600px;
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
        
        .form-check {
            margin-top: 10px;
        }
        
        .form-check-input {
            width: 20px;
            height: 20px;
            margin-top: 0.2em;
        }
        
        .form-check-label {
            font-size: 16px;
            padding-left: 10px;
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
        <div class="register-container">
            <div class="card">
                <div class="card-header">
                    <img src="images/R.png" alt="Barangay Biga Logo" class="logo">
                    <h2 class="mb-0"><?php echo $is_admin_registration ? 'Create Admin Account' : 'Create Account'; ?></h2>
                    <p class="mb-0"><?php echo $is_admin_registration ? 'Barangay Biga Admin Registration' : 'Barangay Biga Management Information System'; ?></p>
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
                            <br>
                            <a href="login.php" class="alert-link">Click here to login</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="" id="registrationForm">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="username" name="username" required placeholder="Choose a username" autocomplete="username">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email" autocomplete="email">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required placeholder="Create a password" autocomplete="new-password">
                                    <div class="password-requirements">Password must be at least 8 characters long</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Confirm your password" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="birth_date" class="form-label">Birthdate</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required placeholder="Enter your first name">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required placeholder="Enter your last name">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" required placeholder="Enter your address">
                                </div>
                            </div>
                            <div class="form-check mb-4">
                                <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                <label class="form-check-label" for="terms">
                                    I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a>
                                </label>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Register</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <div class="login-link">
                        Already have an account? <a href="login.php">Login here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Form Validation Script -->
    <script>
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password.length < 8) {
                e.preventDefault();
                alert('Password must be at least 8 characters long');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match');
                return;
            }
        });
    </script>
    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body" style="max-height: 400px; overflow-y: auto;">
            <p><strong>Sample Terms and Conditions</strong></p>
            <ul>
              <li>All information provided must be true and correct.</li>
              <li>Personal data will be used for barangay services only.</li>
              <li>Any misuse of the system may result in account suspension.</li>
              <li>By registering, you agree to comply with barangay policies.</li>
            </ul>
            <p>For the full policy, please contact the barangay office.</p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
          </div>
        </div>
      </div>
    </div>
</body>
</html> 