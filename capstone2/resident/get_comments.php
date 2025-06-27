<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
header('Content-Type: application/json');

$announcement_id = (int)($_GET['id'] ?? 0);
$show_all = isset($_GET['show_all']);
$response = ['html' => '<p class="text-danger">Invalid request.</p>', 'total_comments' => 0];

if ($announcement_id) {
    // Get total count of top-level comments for this announcement
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = ? AND parent_id IS NULL");
    $count_stmt->bind_param('i', $announcement_id);
    $count_stmt->execute();
    $total_comments = $count_stmt->get_result()->fetch_row()[0];
    
    $limit_sql = $show_all ? '' : 'LIMIT 3';

    $sql = "
        SELECT 
            c.id, c.comment, c.created_at,
            u.username, u.profile_picture
        FROM announcement_comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.announcement_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at DESC
        $limit_sql
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $announcement_id);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $html = '';
    if ($comments) {
        foreach ($comments as $comment) {
            // Get initials for avatar
            $name = $comment['username'];
            $initials = strtoupper(substr($name, 0, 1));
            $profile_pic_path = !empty($comment['profile_picture']) ? htmlspecialchars($comment['profile_picture']) : '';
            $avatar_html = $profile_pic_path
                ? '<img src="' . $profile_pic_path . '" alt="User" class="comment-avatar" style="width:38px;height:38px;object-fit:cover;">'
                : '<div class="comment-avatar">' . $initials . '</div>';

            $html .= '<div class="comment-item">'
                . $avatar_html .
                '<div class="comment-content">'
                . '<div class="comment-meta"><strong>' . htmlspecialchars($comment['username']) . '</strong> &middot; ' . time_ago($comment['created_at']) . '</div>'
                . '<div>' . nl2br(htmlspecialchars($comment['comment'])) . '</div>';

            // Fetch and display admin replies
            $reply_stmt = $conn->prepare("
                SELECT c.comment, c.created_at, u.username
                FROM announcement_comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.parent_id = ? ORDER BY c.created_at ASC
            ");
            $reply_stmt->bind_param('i', $comment['id']);
            $reply_stmt->execute();
            $replies = $reply_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if ($replies) {
                foreach ($replies as $reply) {
                    $admin_initials = strtoupper(substr($reply['username'], 0, 1));
                    $html .= '<div class="comment-item admin mt-2 ms-4">'
                        . '<div class="comment-avatar" style="background:#43a047;">' . $admin_initials . '</div>'
                        . '<div class="comment-content">'
                        . '<div class="comment-meta"><strong>' . htmlspecialchars($reply['username']) . ' (Admin)</strong> &middot; ' . time_ago($reply['created_at']) . '</div>'
                        . '<div>' . nl2br(htmlspecialchars($reply['comment'])) . '</div>'
                        . '</div>'
                        . '</div>';
                }
            }

            $html .= '</div></div>';
        }

        if (!$show_all && $total_comments > 3) {
            $remaining = $total_comments - 3;
            $html .= '<a href="#" class="view-all-comments" data-announcement-id="' . $announcement_id . '">View ' . $remaining . ' more comment(s)</a>';
        }
    } else {
        $html = '<div class="text-center text-muted p-3">Be the first to comment.</div>';
    }
    
    $response = [
        'html' => $html,
        'total_comments' => $total_comments
    ];
}

echo json_encode($response); 