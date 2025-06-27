<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
// var_dump(function_exists('require_login')); // Should be true
require_login();
header('Content-Type: application/json');
$user_id = get_current_user_id();
$announcement_id = (int)($_POST['id'] ?? 0);
if (!$announcement_id) {
    echo json_encode(['success' => false]);
    exit;
}
// Check if already liked
$stmt = $conn->prepare('SELECT * FROM announcement_likes WHERE announcement_id = ? AND user_id = ?');
$stmt->bind_param('ii', $announcement_id, $user_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // Unlike
    $stmt = $conn->prepare('DELETE FROM announcement_likes WHERE announcement_id = ? AND user_id = ?');
    $stmt->bind_param('ii', $announcement_id, $user_id);
    if (!$stmt->execute()) {
        error_log('Unlike error: ' . $stmt->error);
    }
} else {
    // Like
    $stmt = $conn->prepare('INSERT INTO announcement_likes (announcement_id, user_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $announcement_id, $user_id);
    if (!$stmt->execute()) {
        error_log('Like insert error: ' . $stmt->error);
    }
}
// Get new like count
$stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM announcement_likes WHERE announcement_id = ?');
$stmt->bind_param('i', $announcement_id);
$stmt->execute();
$likes = $stmt->get_result()->fetch_assoc()['cnt'];
error_log('like_announcement.php called, POST: ' . json_encode($_POST));
echo json_encode(['success' => true, 'likes' => $likes]); 