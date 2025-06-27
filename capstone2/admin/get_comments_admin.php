<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

require_login();
require_admin();
header('Content-Type: application/json');

$announcement_id = (int)($_GET['id'] ?? 0);
$response = ['html' => '<p class="text-danger">Invalid request.</p>', 'total_comments' => 0];

if ($announcement_id) {
    // Get total count of all comments for this announcement
    $count_stmt = $conn->prepare("SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = ?");
    $count_stmt->bind_param('i', $announcement_id);
    $count_stmt->execute();
    $total_comments = $count_stmt->get_result()->fetch_row()[0];

    // Fetch all top-level comments
    $sql = "
        SELECT c.id, c.comment, c.created_at, u.username, u.profile_picture
        FROM announcement_comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.announcement_id = ? AND c.parent_id IS NULL
        ORDER BY c.created_at DESC
    ";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $announcement_id);
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $html = '';
    if ($comments) {
        foreach ($comments as $comment) {
            $profile_pic = !empty($comment['profile_picture']) ? htmlspecialchars($comment['profile_picture']) : '/capstone2/images/profiledefault.png';
            $html .= '
                <div class="d-flex align-items-start mb-3">
                    <img src="' . $profile_pic . '" class="rounded-circle me-3" style="width:40px; height:40px; object-fit:cover;">
                    <div class="w-100">
                        <div class="bg-light rounded p-2">
                            <strong>' . htmlspecialchars($comment['username']) . '</strong>
                            <p class="mb-0">' . nl2br(htmlspecialchars($comment['comment'])) . '</p>
                        </div>
                        <small class="text-muted">' . time_ago($comment['created_at']) . '</small>';

            // Fetch admin replies for this comment
            $reply_stmt = $conn->prepare("
                SELECT c.id, c.comment, c.created_at, u.username
                FROM announcement_comments c
                JOIN users u ON c.user_id = u.user_id
                WHERE c.parent_id = ? ORDER BY c.created_at ASC
            ");
            $reply_stmt->bind_param('i', $comment['id']);
            $reply_stmt->execute();
            $replies = $reply_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            if ($replies) {
                foreach ($replies as $reply) {
                    $html .= '
                        <div class="d-flex align-items-start mt-2 ms-4 admin-reply p-2 rounded">
                            <i class="fas fa-reply fa-flip-horizontal me-2 text-success"></i>
                            <div class="w-100">
                                <strong>' . htmlspecialchars($reply['username']) . ' (Admin)</strong>
                                <p class="mb-0">' . nl2br(htmlspecialchars($reply['comment'])) . '</p>
                                <small class="text-muted">' . time_ago($reply['created_at']) . '</small>
                            </div>
                        </div>';
                }
            }
            
            // Admin reply form
            $html .= '
                <div class="ms-4 mt-2">
                    <form class="admin-reply-form" data-announcement-id="' . $announcement_id . '" data-parent-id="' . $comment['id'] . '">
                        <div class="input-group">
                            <textarea name="reply" class="form-control form-control-sm" placeholder="Write a reply..." rows="1"></textarea>
                            <button class="btn btn-sm btn-success" type="submit">Reply</button>
                        </div>
                    </form>
                </div>';

            $html .= '</div></div><hr>';
        }
    } else {
        $html = '<div class="text-center text-muted p-3">No comments yet.</div>';
    }
    
    $response = ['html' => $html, 'total_comments' => $total_comments];
}

echo json_encode($response); 