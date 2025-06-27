<?php
require_once '../includes/auth.php';
file_put_contents(__DIR__ . '/error_log_social_counts.txt', date('Y-m-d H:i:s') . ' - Script started. GET: ' . json_encode($_GET) . "\n", FILE_APPEND);
require_once '../includes/functions.php';
require_login();
global $conn;
header('Content-Type: application/json');
$announcement_id = (int)($_GET['id'] ?? 0);
$avatars_html = '';
$comment_count = 0;
try {
    if ($announcement_id) {
        // Like avatars/usernames
        $stmt = $conn->prepare('SELECT u.username, u.profile_picture FROM announcement_likes l JOIN users u ON l.user_id = u.user_id WHERE l.announcement_id = ? ORDER BY l.created_at DESC LIMIT 5');
        $stmt->bind_param('i', $announcement_id);
        $stmt->execute();
        $likes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        foreach ($likes as $like) {
            $img = !empty($like['profile_picture']) && $like['profile_picture'] !== 'NULL' ? $like['profile_picture'] : '/capstone2/images/profiledefault.png';
            $avatars_html .= '<img src="' . htmlspecialchars($img) . '" class="rounded-circle me-1" style="width:24px;height:24px;object-fit:cover;" title="' . htmlspecialchars($like['username']) . '">';
        }
        // Comment count
        $stmt = $conn->prepare('SELECT COUNT(*) as cnt FROM announcement_comments WHERE announcement_id = ?');
        $stmt->bind_param('i', $announcement_id);
        $stmt->execute();
        $comment_count = $stmt->get_result()->fetch_assoc()['cnt'];
    }
    echo json_encode(['avatars_html' => $avatars_html, 'comment_count' => $comment_count]);
} catch (Throwable $e) {
    file_put_contents(__DIR__ . '/error_log_social_counts.txt', date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n", FILE_APPEND);
    echo json_encode(['avatars_html' => '', 'comment_count' => 0, 'error' => $e->getMessage()]);
} 