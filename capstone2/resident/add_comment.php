<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
header('Content-Type: application/json');

$user_id = get_current_user_id();
$announcement_id = (int)($_POST['id'] ?? 0);
$comment_text = trim($_POST['comment_text'] ?? '');

if ($announcement_id && !empty($comment_text) && $user_id) {
    try {
        $stmt = $conn->prepare('INSERT INTO announcement_comments (announcement_id, user_id, comment) VALUES (?, ?, ?)');
        $stmt->bind_param('iis', $announcement_id, $user_id, $comment_text);
        
        if ($stmt->execute()) {
            // After successful insertion, get the new total count of comments
            $count_stmt = $conn->prepare("SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = ? AND parent_id IS NULL");
            $count_stmt->bind_param('i', $announcement_id);
            $count_stmt->execute();
            $new_comment_count = $count_stmt->get_result()->fetch_row()[0];

            echo json_encode(['success' => true, 'new_comment_count' => $new_comment_count]);
        } else {
            throw new Exception("Failed to save comment.");
        }
    } catch (Exception $e) {
        // Log error properly in a real app
        // error_log('Add comment error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'An error occurred.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid data provided.']);
}