<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
header('Content-Type: application/json');
$comment_id = (int)($_POST['id'] ?? 0);
if ($comment_id) {
    global $conn;
    $stmt = $conn->prepare('DELETE FROM announcement_comments WHERE comment_id = ?');
    $stmt->bind_param('i', $comment_id);
    $stmt->execute();
    echo json_encode(['success' => true]);
    exit;
}
echo json_encode(['success' => false]); 