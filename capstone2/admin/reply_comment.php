<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_admin();
header('Content-Type: application/json');

$admin_user_id = get_current_user_id();
$announcement_id = (int)($_POST['announcement_id'] ?? 0);
$parent_id = (int)($_POST['parent_id'] ?? 0);
$reply_text = trim($_POST['reply'] ?? '');

if ($announcement_id && $parent_id && !empty($reply_text) && $admin_user_id) {
    try {
        $stmt = $conn->prepare('INSERT INTO announcement_comments (announcement_id, user_id, comment, parent_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('iisi', $announcement_id, $admin_user_id, $reply_text, $parent_id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            throw new Exception("Failed to save reply.");
        }
    } catch (Exception $e) {
        error_log('Admin reply error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred while saving the reply.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided for reply.']);
} 