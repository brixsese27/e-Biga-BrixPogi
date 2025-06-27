<?php
$page_title = 'My Profile';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_login();

$user_id = get_current_user_id();
$error = '';
$success = '';

// Get user details
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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
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
        log_activity($user_id, 'password_changed', "Resident changed password");
        $success = "Password has been updated successfully.";
    } catch (Exception $e) {
        error_log("Password Change Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['change_password'])) {
    try {
        $is_picture_upload = isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK;
        $is_profile_update = isset($_POST['first_name']) || isset($_POST['last_name']) || isset($_POST['email']);

        if ($is_picture_upload && !$is_profile_update) {
            // Only handle profile picture upload
            $file_tmp = $_FILES['profile_picture']['tmp_name'];
            $file_name = basename($_FILES['profile_picture']['name']);
            $target_dir = '/capstone2/uploads/profile_pictures/';
            $target_path = $target_dir . uniqid() . '_' . $file_name;

            // Make sure the directory exists
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
        } else {
            // Handle profile info update as before
            $first_name = sanitize_input($_POST['first_name'] ?? '');
            $middle_name = sanitize_input($_POST['middle_name'] ?? '');
            $last_name = sanitize_input($_POST['last_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $contact_number = sanitize_input($_POST['contact_number'] ?? '');
            $address = sanitize_input($_POST['address'] ?? '');
            $birth_date = sanitize_input($_POST['birth_date'] ?? '');
            $gender = sanitize_input($_POST['gender'] ?? '');
            $civil_status = sanitize_input($_POST['civil_status'] ?? '');
            $occupation = sanitize_input($_POST['occupation'] ?? '');
            $nationality = sanitize_input($_POST['nationality'] ?? 'Filipino');
            $voter_status = isset($_POST['voter_status']) && $_POST['voter_status'] === '1';
            $emergency_contact = sanitize_input($_POST['emergency_contact'] ?? '');
            $emergency_number = sanitize_input($_POST['emergency_number'] ?? '');
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
            // Auto-calculate senior status
            $is_senior_citizen = 0;
            if (!empty($birth_date)) {
                $dob = new DateTime($birth_date);
                $now = new DateTime();
                $age = $now->diff($dob)->y;
                if ($age >= 60) {
                    $is_senior_citizen = 1;
                }
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
            // Update user details
            $stmt = $conn->prepare("
                UPDATE user_details 
                SET first_name = ?, middle_name = ?, last_name = ?, contact_number = ?, 
                    address = ?, birth_date = ?, gender = ?, civil_status = ?, 
                    nationality = ?, voter_status = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssssssssssi", 
                $first_name, $middle_name, $last_name, $contact_number, $address, 
                $birth_date, $gender, $civil_status, $nationality, $voter_status, $user_id
            );
            if (!$stmt->execute()) {
                throw new Exception("Failed to update profile information.");
            }
            // Update user table
            $stmt = $conn->prepare("
                UPDATE users 
                SET email = ?, is_senior_citizen = ?, updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->bind_param("sii", $email, $is_senior_citizen, $user_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user information.");
            }
            // Handle profile picture upload if present
            if ($is_picture_upload) {
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
            // Log the activity
            log_activity($user_id, 'profile_updated', "Updated profile information");
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
        }
    } catch (Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>

<!-- Display Messages -->
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

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="card shadow-lg border-0 mb-4" style="border-radius: 1.5rem;">
                <div class="card-header bg-success text-white text-center" style="border-top-left-radius: 1.5rem; border-top-right-radius: 1.5rem;">
                    <div>
                        <img src="<?php echo (!empty($user['profile_picture']) && $user['profile_picture'] !== 'NULL') ? $user['profile_picture'] : '/capstone2/images/profiledefault.png'; ?>"
                             alt="Profile Picture" class="rounded-circle mb-2" style="width: 90px; height: 90px; object-fit: cover; border: 3px solid #fff;">
                    </div>
                    <h4 class="mb-0 mt-2 fw-bold"><?php echo htmlspecialchars($user['first_name'] ?? '') . ' ' . htmlspecialchars($user['last_name'] ?? ''); ?></h4>
                    <p class="text-white-50 mb-2"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    <button class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#changePictureModal">Change Profile Picture</button>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <div class="card bg-light border-0">
                            <div class="card-header d-flex align-items-center bg-white border-0">
                                <img src="/capstone2/images/R.png" alt="Barangay Biga Logo" style="width: 24px; height: 24px; margin-right: 8px;">
                                <span class="fw-bold">Account Information</span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-2">
                                    <div class="col-6">
                                        <strong>Member Since</strong><br><?php echo !empty($user['created_at']) ? format_date($user['created_at']) : 'N/A'; ?>
                                    </div>
                                    <div class="col-6">
                                        <strong>Last Updated</strong><br><?php echo !empty($user['updated_at']) ? format_date($user['updated_at']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-12">
                                        <strong>Account Type</strong><br><?php echo !empty($user['role']) ? ucfirst($user['role']) : 'N/A'; ?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-12">
                                        <strong>Status</strong><br>
                                        <?php if (!empty($user['is_senior_citizen']) && $user['is_senior_citizen']): ?>
                                            <span class="badge bg-warning text-dark me-1">Senior Citizen</span>
                                        <?php endif; ?>
                                        <?php if (!empty($user['voter_status']) && $user['voter_status']): ?>
                                            <span class="badge bg-info text-dark">Registered Voter</span>
                                        <?php endif; ?>
                                        <?php if (empty($user['is_senior_citizen']) && empty($user['voter_status'])): ?>
                                            <span class="text-muted">No special status</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <form method="POST" action="" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name" value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_number" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <label for="address" class="form-label">Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="birth_date" class="form-label">Birth Date</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" value="<?php echo htmlspecialchars($user['birth_date'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender">
                                        <option value="">Select gender</option>
                                        <option value="Male" <?php echo (isset($user['gender']) && $user['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($user['gender']) && $user['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($user['gender']) && $user['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="civil_status" class="form-label">Civil Status</label>
                                    <select class="form-select" id="civil_status" name="civil_status">
                                        <option value="">Select status</option>
                                        <option value="Single" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'Married') ? 'selected' : ''; ?>>Married</option>
                                        <option value="Widowed" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                        <option value="Separated" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'Separated') ? 'selected' : ''; ?>>Separated</option>
                                        <option value="Divorced" <?php echo (isset($user['civil_status']) && $user['civil_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="nationality" class="form-label">Nationality</label>
                                    <input type="text" class="form-control" id="nationality" name="nationality" value="<?php echo htmlspecialchars($user['nationality'] ?? 'Filipino'); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label for="occupation" class="form-label">Occupation</label>
                                    <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-center">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="voter_status" name="voter_status" value="1" <?php echo (!empty($user['voter_status'])) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="voter_status">Registered Voter</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3 align-items-end">
                                <div class="col-md-6">
                                    <label for="emergency_contact" class="form-label">Emergency Name</label>
                                    <input type="text" class="form-control" id="emergency_contact" name="emergency_contact" placeholder="Enter emergency contact name" value="<?php echo htmlspecialchars($user['emergency_contact'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="emergency_number" class="form-label">Emergency Number</label>
                                    <input type="text" class="form-control" id="emergency_number" name="emergency_number" placeholder="Enter emergency contact number" value="<?php echo htmlspecialchars($user['emergency_number'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" class="btn btn-success px-4 py-2 fs-5">Save Changes</button>
                        </div>
                    </form>
                    <!-- Change Password Section -->
                    <hr class="my-4">
                    <div class="mb-3">
                        <h6 class="fw-bold mb-3"><i class="bi bi-key"></i> Change Password</h6>
                        <form method="POST" action="" autocomplete="off">
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <input type="password" name="current_password" class="form-control" placeholder="Current Password" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                                </div>
                                <div class="col-md-4">
                                    <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                                </div>
                            </div>
                            <div class="d-grid mt-3">
                                <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                            </div>
                        </form>
                    </div>
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
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" 
                               accept="image/*" required>
                        <div class="form-text">
                            Maximum file size: 2MB. Allowed formats: JPG, PNG, GIF.
                        </div>
                        <div class="invalid-feedback">Please select an image file.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Upload Picture</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form Validation Script -->
<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?> 