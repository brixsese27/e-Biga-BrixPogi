<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Ensure this is an admin and the request is a POST request
require_admin();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get the raw POST data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

$appointment_id = $data['appointment_id'] ?? null;
$new_status = $data['status'] ?? null;

if (!$appointment_id || !$new_status) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID or status.']);
    exit;
}

// Validate the status to ensure it's one of the allowed values
$allowed_statuses = ['Pending', 'Approved', 'Declined', 'Completed'];
if (!in_array($new_status, $allowed_statuses)) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Invalid status value provided.']);
    exit;
}

try {
    // The database connection is established in 'database.php' and is available globally.
    global $conn;

    // Update appointment status in the database
    $stmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?");
    $stmt->bind_param("si", $new_status, $appointment_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            log_activity($conn, get_current_user_id(), 'appointment_status_update', "Updated appointment #{$appointment_id} to {$new_status}");

            echo json_encode(['success' => true, 'message' => 'Appointment status updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Appointment not found or status is already the same.']);
        }
    } else {
        throw new Exception("Database update failed.");
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Update Appointment Status Error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating the status: ' . $e->getMessage()]);
} 