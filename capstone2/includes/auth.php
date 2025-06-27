<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

/**
 * Authentication Helper Functions
 */

// Function to register a new user
function register_user($data) {
    global $conn;

    $username = $data['username'];
    $email = $data['email'];
    $password = $data['password'];
    $role = $data['role'];
    $first_name = $data['first_name'];
    $last_name = $data['last_name'];
    $birth_date = $data['birth_date'];

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $conn->begin_transaction();

    try {
        $stmt_users = $conn->prepare("INSERT INTO users (username, email, password, role, is_senior_citizen) VALUES (?, ?, ?, ?, ?)");
        $is_senior = is_senior_citizen_age($birth_date) ? 1 : 0;
        $stmt_users->bind_param("ssssi", $username, $email, $hashed_password, $role, $is_senior);
        $stmt_users->execute();
        $user_id = $conn->insert_id;

        $stmt_details = $conn->prepare("INSERT INTO user_details (user_id, first_name, last_name, birth_date) VALUES (?, ?, ?, ?)");
        $stmt_details->bind_param("isss", $user_id, $first_name, $last_name, $birth_date);
        $stmt_details->execute();

        $conn->commit();

        if (isset($data['send_email']) && $data['send_email']) {
            $email_data = [
                'recipient_email' => $email,
                'recipient_name' => $first_name,
                'username' => $username,
                'password' => $password, 
                'role' => ucfirst($role)
            ];
            send_registration_email($email_data);
        }

        return ['success' => true, 'user_id' => $user_id];
    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Function to login user
function login_user($username, $password) {
    global $conn;
    $stmt = $conn->prepare("SELECT user_id, username, password, role FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return [
                'success' => true,
                'role' => $user['role'],
                'message' => 'Login successful.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Incorrect password.'
            ];
        }
    } else {
        return [
            'success' => false,
            'message' => 'Username not found.'
        ];
    }
}

// Function to logout user
function logout_user() {
    session_unset();
    session_destroy();
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to check if user is senior citizen
function is_senior_citizen() {
    return isset($_SESSION['is_senior_citizen']) && $_SESSION['is_senior_citizen'] === true;
}

// Function to get current user ID
function get_current_user_id() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user role
function get_current_user_role() {
    return $_SESSION['role'] ?? null;
}

// Function to log user activity
function log_activity($conn, $user_id, $action, $details) {
    if (!$conn) {
        error_log("DB_FAIL | User: {$user_id}, Action: {$action}, Details: {$details}\n");
        return;
    }
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, description) VALUES (?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("iss", $user_id, $action, $details);
        $stmt->execute();
        $stmt->close();
    } else {
        error_log("Failed to prepare activity log statement: " . $conn->error);
    }
}

// Function to require login
function require_login() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /capstone2/login.php');
        exit;
    }
}

// Function to require admin
function require_admin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /capstone2/login.php');
        exit;
    }
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden: Admin access required.']);
            exit;
        }
        header('Location: /capstone2/resident/dashboard.php');
        exit;
    }
}

// Function to update user profile
function update_user_profile($user_id, $data) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE user_details SET 
            first_name = ?, 
            last_name = ?, 
            middle_name = ?, 
            birth_date = ?, 
            gender = ?, 
            address = ?, 
            contact_number = ?, 
            emergency_contact = ?, 
            emergency_number = ? 
            WHERE user_id = ?");
            
        $stmt->bind_param("sssssssssi", 
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'],
            $data['birth_date'],
            $data['gender'],
            $data['address'],
            $data['contact_number'],
            $data['emergency_contact'],
            $data['emergency_number'],
            $user_id
        );
        
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'profile_update', 'User profile updated');
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Profile update failed'];
    } catch (Exception $e) {
        error_log("Profile Update Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating profile'];
    }
}

// Function to update profile picture
function update_profile_picture($user_id, $picture_path) {
    global $conn;
    
    try {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("si", $picture_path, $user_id);
        
        if ($stmt->execute()) {
            log_activity($conn, $user_id, 'profile_picture_update', 'Profile picture updated');
            return ['success' => true];
        }
        
        return ['success' => false, 'message' => 'Profile picture update failed'];
    } catch (Exception $e) {
        error_log("Profile Picture Update Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'An error occurred while updating profile picture'];
    }
}
?> 