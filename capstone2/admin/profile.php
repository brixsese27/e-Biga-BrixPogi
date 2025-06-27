<?php
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_admin();

$user_id = get_current_user_id();
$error = '';
$success = '';

// Get admin details
try {
    $stmt = $conn->prepare("
        SELECT u.*, ud.* 
        FROM users u 
        JOIN user_details ud ON u.user_id = ud.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if (!$user) {
        throw new Exception("User not found.");
    }
} catch (Exception $e) {
    error_log("Profile Error: " . $e->getMessage());
    $error = "An error occurred while loading profile information.";
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password_change'])) {
    try {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            throw new Exception("All password fields are required.");
        }
        if ($new_password !== $confirm_password) {
            throw new Exception("New password and confirm password do not match.");
        }
        // Check current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        if (!$row || !password_verify($current_password, $row['password'])) {
            throw new Exception("Current password is incorrect.");
        }
        // Update password
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("si", $hashed, $user_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to update password.");
        }
        log_activity($user_id, 'password_changed', "Admin changed password");
        $success = "Password has been updated successfully.";
    } catch (Exception $e) {
        error_log("Password Change Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['profile_update'])) {
    // If only profile picture is being uploaded (from modal)
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK
        && empty($_POST['first_name']) && empty($_POST['last_name']) && empty($_POST['email'])) {
        try {
            $file_tmp = $_FILES['profile_picture']['tmp_name'];
            $file_name = basename($_FILES['profile_picture']['name']);
            $target_dir = '/capstone2/uploads/profile_pictures/';
            $target_path = $target_dir . uniqid() . '_' . $file_name;
            if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $target_dir)) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $target_dir, 0777, true);
            }
            if (move_uploaded_file($file_tmp, $_SERVER['DOCUMENT_ROOT'] . $target_path)) {
                $upload_result = update_profile_picture($user_id, $target_path);
                if (!$upload_result['success']) {
                    throw new Exception($upload_result['message']);
                }
            } else {
                throw new Exception('Failed to upload profile picture.');
            }
            log_activity($user_id, 'profile_picture_updated', "Updated profile picture");
            $success = "Profile picture has been updated successfully.";
            // Refresh user data
            $stmt = $conn->prepare("
                SELECT u.*, ud.* 
                FROM users u 
                JOIN user_details ud ON u.user_id = ud.user_id 
                WHERE u.user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Profile Picture Update Error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    } else {
        try {
            $first_name = sanitize_input($_POST['first_name'] ?? '');
            $last_name = sanitize_input($_POST['last_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $contact_number = sanitize_input($_POST['contact_number'] ?? '');
            $address = sanitize_input($_POST['address'] ?? '');
            $birth_date = sanitize_input($_POST['birth_date'] ?? '');
            $gender = sanitize_input($_POST['gender'] ?? '');
            $civil_status = sanitize_input($_POST['civil_status'] ?? '');
            $occupation = sanitize_input($_POST['occupation'] ?? '');
            // Validate inputs
            if (empty($first_name) || empty($last_name) || empty($email)) {
                throw new Exception("First name, last name, and email are required.");
            }
            if (!is_valid_email($email)) {
                throw new Exception("Invalid email format.");
            }
            if (!empty($contact_number) && !is_valid_phone($contact_number)) {
                throw new Exception("Invalid contact number format.");
            }
            if (!empty($birth_date) && !is_valid_date($birth_date)) {
                throw new Exception("Invalid birth date format.");
            }
            // Check if email is already taken by another user
            $stmt = $conn->prepare("
                SELECT user_id FROM users 
                WHERE email = ? AND user_id != ?
            ");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Email is already taken by another user.");
            }
            // Update user_details
            $stmt = $conn->prepare("
                UPDATE user_details 
                SET first_name = ?, last_name = ?, contact_number = ?, address = ?, birth_date = ?, gender = ?, civil_status = ?, occupation = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssssssssi", $first_name, $last_name, $contact_number, $address, $birth_date, $gender, $civil_status, $occupation, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update profile information.");
            }
            // Update user table
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->bind_param("si", $email, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information.");
            }
            // Handle profile picture upload if present
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['profile_picture']['tmp_name'];
                $file_name = basename($_FILES['profile_picture']['name']);
                $target_dir = '/capstone2/uploads/profile_pictures/';
                $target_path = $target_dir . uniqid() . '_' . $file_name;
                if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $target_dir)) {
                    mkdir($_SERVER['DOCUMENT_ROOT'] . $target_dir, 0777, true);
                }
                if (move_uploaded_file($file_tmp, $_SERVER['DOCUMENT_ROOT'] . $target_path)) {
                    $upload_result = update_profile_picture($user_id, $target_path);
                    if (!$upload_result['success']) {
                        throw new Exception($upload_result['message']);
                    }
                } else {
                    throw new Exception('Failed to upload profile picture.');
                }
            }
            log_activity($user_id, 'profile_updated', "Updated admin profile information");
            $success = "Profile has been updated successfully.";
            // Refresh user data
            $stmt = $conn->prepare("
                SELECT u.*, ud.* 
                FROM users u 
                JOIN user_details ud ON u.user_id = ud.user_id 
                WHERE u.user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Profile Update Error: " . $e->getMessage());
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f5f5f5; }
        .profile-header { background: #1565c0; color: #fff; border-radius: 1rem 1rem 0 0; padding: 2rem 1rem 1rem 1rem; text-align: center; }
        .profile-icon { font-size: 3rem; color: #fff; }
        .card { border-radius: 1rem; }
        .info-card { background: #e3f2fd; border: none; }
        @media (max-width: 768px) { .profile-header { font-size: 1.2rem; padding: 1.2rem 0.5rem 0.5rem 0.5rem; } .profile-icon { font-size: 2rem; } }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="profile-header mb-4">
        <div class="profile-icon mb-2"><i class="bi bi-person-circle"></i></div>
        <h2 class="fw-bold mb-1">Admin Profile</h2>
        <p class="mb-0">View and update your admin profile information and credentials.</p>
    </div>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <div class="row justify-content-center">
        <div class="col-lg-8 col-md-10">
            <div class="card shadow-lg border-0 mb-4 info-card">
                <div class="card-header bg-primary text-white text-center" style="border-top-left-radius: 1rem; border-top-right-radius: 1rem;">
                    <div>
                        <img src="<?php echo (!empty($user['profile_picture']) && $user['profile_picture'] !== 'NULL') ? $user['profile_picture'] : '/capstone2/images/profiledefault.png'; ?>"
                             alt="Profile Picture" class="rounded-circle mb-2" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid #fff;">
                    </div>
                    <h4 class="mb-0 mt-2 fw-bold"><?php echo htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''); ?></h4>
                    <p class="text-white-50 mb-2"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#changePictureModal">Change Profile Picture</button>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="profile_update" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label for="birth_date" class="form-label">Birth Date</label>
                                <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender">
                                    <option value="">Select gender</option>
                                    <option value="male" <?php echo (isset($user['gender']) && $user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo (isset($user['gender']) && $user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="civil_status" class="form-label">Civil Status</label>
                                <select class="form-select" id="civil_status" name="civil_status">
                                    <option value="">Select status</option>
                                    <option value="single" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'single') ? 'selected' : ''; ?>>Single</option>
                                    <option value="married" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'married') ? 'selected' : ''; ?>>Married</option>
                                    <option value="widowed" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'widowed') ? 'selected' : ''; ?>>Widowed</option>
                                    <option value="separated" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'separated') ? 'selected' : ''; ?>>Separated</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="occupation" class="form-label">Occupation</label>
                                <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-primary px-4 py-2 fs-5">Save Changes</button>
                        </div>
                    </form>
                    <hr class="my-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-key me-2"></i>Change Password</h5>
                    <form method="POST" action="">
                        <input type="hidden" name="password_change" value="1">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            <div class="col-md-4">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            <div class="col-md-4">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-warning px-4 py-2 fs-5">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Change Profile Picture Modal -->
<div class="modal fade" id="changePictureModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <div class="modal-header">
                    <h5 class="modal-title">Change Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="profile_picture" class="form-label">Select Image</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*" required>
                        <div class="form-text">Maximum file size: 2MB. Allowed formats: JPG, PNG, GIF.</div>
                        <div class="invalid-feedback">Please select an image file.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="profile_update" value="1" class="btn btn-primary">Upload Picture</button>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>
<?php require_once '../includes/footer.php'; ?> 