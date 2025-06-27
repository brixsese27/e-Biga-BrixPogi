<?php
$page_title = 'Announcements';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_login();

$user_id = get_current_user_id();
$error = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Get announcements
try {
    $type_filter = $_GET['type'] ?? 'all';
    
    if ($id) {
        // Fetch only the selected announcement
        $query = "
            SELECT a.*, u.username as created_by_username,
                (SELECT COUNT(*) FROM announcement_comments c WHERE c.announcement_id = a.announcement_id) as comment_count,
                (SELECT COUNT(*) FROM announcement_likes l WHERE l.announcement_id = a.announcement_id) as like_count,
                (SELECT COUNT(*) FROM announcement_likes l WHERE l.announcement_id = a.announcement_id AND l.user_id = ?) as user_liked
            FROM announcements a 
            JOIN users u ON a.created_by = u.user_id 
            WHERE a.announcement_id = ? AND a.status = 'published' AND a.is_public = 1
        ";
        $params = [$user_id, $id];
        $types = "ii";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $query = "
            SELECT a.*, u.username as created_by_username,
                (SELECT COUNT(*) FROM announcement_comments c WHERE c.announcement_id = a.announcement_id) as comment_count,
                (SELECT COUNT(*) FROM announcement_likes l WHERE l.announcement_id = a.announcement_id) as like_count,
                (SELECT COUNT(*) FROM announcement_likes l WHERE l.announcement_id = a.announcement_id AND l.user_id = ?) as user_liked
            FROM announcements a 
            JOIN users u ON a.created_by = u.user_id 
            WHERE a.status = 'published' 
            AND a.is_public = 1
        ";
        $params = [$user_id];
        $types = "i";
        
        if ($type_filter !== 'all') {
            $query .= " AND a.type = ?";
            $params[] = $type_filter;
            $types .= "s";
        }
        
        $query .= " ORDER BY a.created_at DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Announcement List Error: " . $e->getMessage());
    $error = "An error occurred while loading announcements.";
    $announcements = [];
}
?>

<!-- Display Error Message -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type">
                    <option value="all" <?php echo $type_filter === 'all' ? 'selected' : ''; ?>>
                        All Types
                    </option>
                    <?php foreach (get_announcement_types() as $type): ?>
                        <option value="<?php echo $type; ?>" 
                            <?php echo $type_filter === $type ? 'selected' : ''; ?>>
                            <?php echo ucfirst($type); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Announcements List -->
<div class="row g-3">
    <?php if (empty($announcements)): ?>
        <div class="col-12">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                No announcements available at the moment.
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $announcement): ?>
            <div class="col-md-6 col-lg-4 d-flex">
                <div class="card mb-3 border-0 shadow-sm rounded-4 overflow-hidden position-relative announcement-card h-100">
                    <?php if (!empty($announcement['is_pinned'])): ?>
                        <span class="position-absolute top-0 end-0 m-2 badge bg-warning text-dark">Pinned</span>
                    <?php endif; ?>
                    <?php if (!empty($announcement['image'])): ?>
                        <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="card-img-top" alt="Announcement Image" style="max-height: 220px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <div class="d-flex gap-2 align-items-center mb-2">
                            <span class="badge bg-<?php echo $announcement['type'] === 'health' ? 'danger' : ($announcement['type'] === 'event' ? 'success' : 'primary'); ?>">
                                <?php echo ucfirst($announcement['type']); ?> Announcement
                            </span>
                            <span class="badge bg-primary text-light">Posted by: Barangay Official</span>
                            <small class="text-muted ms-auto" title="<?php echo format_datetime($announcement['created_at']); ?>">
                                <?php echo time_ago($announcement['created_at']); ?>
                            </small>
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                        <p class="mb-2 text-muted"><?php echo truncate_text($announcement['content'], 180); ?></p>
                        <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold d-inline-flex align-items-center gap-2 read-more-btn" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $announcement['announcement_id']; ?>">
                            Read More <i class="bi bi-arrow-right"></i>
                        </button>
                        <div class="d-flex align-items-center mb-2">
                            <button class="btn btn-link text-decoration-none me-2 like-btn <?php echo $announcement['user_liked'] ? 'text-primary' : 'text-muted'; ?>" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                                <i class="bi <?php echo $announcement['user_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up'; ?>"></i>
                                <span id="like-count-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['like_count']; ?></span>
                            </button>
                            <div class="like-avatars" id="like-avatars-<?php echo $announcement['announcement_id']; ?>">
                                <!-- Like avatars will be loaded here -->
                            </div>
                            <span class="ms-2 text-muted small">
                                <i class="bi bi-chat-left-text"></i>
                                <span class="comment-count comments-link" id="comment-count-<?php echo $announcement['announcement_id']; ?>" style="cursor:pointer;" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                                    <?php echo $announcement['comment_count']; ?>
                                </span> Comments
                            </span>
                        </div>
                        <div class="comments-section mt-3">
                            <h6 class="mb-2">Comments</h6>
                            <div class="add-comment-divider"></div>
                            <div class="comments-list" id="comments-list-<?php echo $announcement['announcement_id']; ?>">
                                <!-- Comments will be loaded here via AJAX -->
                            </div>
                            <form class="add-comment-form mt-2" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                                <div class="input-group">
                                    <input type="text" name="comment" class="form-control" placeholder="Add a comment..." required>
                                    <button class="btn btn-primary" type="submit">Post</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <!-- View Modal -->
                <div class="modal fade" id="viewModal<?php echo $announcement['announcement_id']; ?>" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Announcement Details</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (!empty($announcement['image'])): ?>
                                    <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="img-fluid rounded mb-3" alt="Announcement Image" style="max-height: 250px; object-fit: cover;">
                                <?php endif; ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <small class="text-muted">
                                        Posted on <?php echo format_datetime($announcement['created_at']); ?>
                                    </small>
                                </div>
                                <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <p class="text-muted mb-3">
                                    <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                </p>
                                <dl class="row mb-0">
                                    <dt class="col-sm-3">Type</dt>
                                    <dd class="col-sm-9">
                                        <?php echo ucfirst($announcement['type']); ?>
                                    </dd>
                                    <dt class="col-sm-3">Posted By</dt>
                                    <dd class="col-sm-9">
                                        <?php echo htmlspecialchars($announcement['created_by_username']); ?>
                                    </dd>
                                    <dt class="col-sm-3">Last Updated</dt>
                                    <dd class="col-sm-9">
                                        <?php echo format_datetime($announcement['updated_at']); ?>
                                    </dd>
                                </dl>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<style>
/* Responsive grid for announcements */
.row.g-3 {
    row-gap: 2rem !important;
    column-gap: 2.5rem !important;
}
.card.announcement-card {
    border-radius: 1.5rem;
    box-shadow: 0 2px 16px rgba(44, 62, 80, 0.08);
    transition: box-shadow 0.2s, transform 0.2s;
    overflow: hidden;
    margin-bottom: 2rem;
    display: flex;
    flex-direction: column;
    min-height: 100%;
    padding-bottom: 0.5rem;
}
.card.announcement-card .card-body {
    padding-bottom: 0.5rem;
}
.card-img-top {
    border-top-left-radius: 1.5rem;
    border-top-right-radius: 1.5rem;
    object-fit: cover;
    min-height: 180px;
    max-height: 220px;
}
.announcement-card h5, .announcement-card h4 {
    font-weight: 700;
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}
.announcement-card .badge {
    font-size: 0.95em;
    border-radius: 0.7em;
    padding: 0.4em 1em;
}
.announcement-card .badge-ann-type {
    background: linear-gradient(90deg, #1976d2 60%, #43a047 100%);
    color: #fff;
}
.announcement-card .badge-ann-official {
    background: #1565c0;
    color: #fff;
}
.announcement-card .read-more-btn {
    background: linear-gradient(90deg, #1976d2 60%, #43a047 100%);
    border: none;
    color: #fff;
    border-radius: 2em;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(44,62,80,0.10);
    transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
    margin-bottom: 0.5rem;
}
.announcement-card .read-more-btn:hover {
    background: linear-gradient(90deg, #43a047 60%, #1976d2 100%);
    box-shadow: 0 4px 16px rgba(44,62,80,0.18);
    transform: translateY(-2px) scale(1.03);
    color: #fff;
}
.announcement-card .like-btn {
    border-radius: 2em;
    font-weight: 500;
    min-width: 80px;
}
.announcement-card .like-btn.btn-primary {
    background: linear-gradient(90deg, #43a047 60%, #1976d2 100%);
    border: none;
    color: #fff;
}
.announcement-card .like-btn.btn-outline-primary {
    background: #fff;
    color: #1976d2;
    border: 2px solid #1976d2;
}
.announcement-card .like-btn.btn-outline-primary:hover {
    background: #1976d2;
    color: #fff;
}
.announcement-card .comments-section {
    border-top: 1px solid #e0e0e0;
    margin-top: 1.2rem;
    padding-top: 1rem;
    background: #f7fafd;
    border-radius: 1em;
    padding-left: 1.2rem;
    padding-right: 1.2rem;
    margin-left: -1.2rem;
    margin-right: -1.2rem;
    box-shadow: 0 1px 4px rgba(44,62,80,0.04);
    max-width: 98%;
    margin-bottom: 0.5rem;
}
.comments-list {
    margin-bottom: 0.5rem;
}
.comment-item {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    margin-bottom: 0.7rem;
    background: #f8f9fa;
    border-radius: 1em;
    padding: 0.7em 1em;
    position: relative;
    min-height: 48px;
}
.comment-item.admin {
    background: #e8f5e9;
    border-left: 4px solid #43a047;
}
.comment-avatar {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #1976d2;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.1em;
    margin-top: 2px;
    flex-shrink: 0;
}
.comment-content {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.comment-meta {
    font-size: 0.92em;
    color: #888;
    margin-bottom: 0.2em;
}
.add-comment-divider {
    border-top: 1px solid #e0e0e0;
    margin: 0.7em 0 0.7em 0;
}
.add-comment-form {
    margin-top: 0.5em;
}
.add-comment-form .form-control {
    border-radius: 2em 0 0 2em;
    padding-left: 1.2em;
    font-size: 1em;
}
.add-comment-form .btn {
    border-radius: 0 2em 2em 0;
    font-weight: 600;
    padding: 0.5em 1.5em;
}
.show-all-btn, .show-less-btn, .view-all-comments {
    color: #1976d2;
    font-weight: 500;
    background: none;
    border: none;
    cursor: pointer;
    margin-top: 0.2em;
    margin-bottom: 0.5em;
    display: block;
    width: 100%;
    text-align: center;
    padding: 0.4em 0;
}
@media (max-width: 991px) {
    .col-lg-4, .col-md-6 { flex: 0 0 100%; max-width: 100%; }
    .row.g-3 { column-gap: 0.5rem !important; }
    .card.announcement-card { margin-bottom: 1.2rem; }
    .announcement-card .comments-section { padding-left: 0.5rem; padding-right: 0.5rem; margin-left: -0.5rem; margin-right: -0.5rem; }
}
@media (max-width: 600px) {
    body, .container { padding: 0 !important; }
    .login-container, .card.announcement-card { margin: 0.5rem 0.2rem !important; }
    .card.announcement-card { border-radius: 1rem; }
    .card-img-top { min-height: 120px; max-height: 140px; border-radius: 1rem 1rem 0 0; }
    .card-body { padding: 1rem 0.7rem 0.7rem 0.7rem; }
    .announcement-card h5, .announcement-card h4 { font-size: 1.05rem; }
    .announcement-card .badge, .announcement-card .badge-ann-type, .announcement-card .badge-ann-official { font-size: 0.85em; padding: 0.3em 0.7em; }
    .announcement-card .read-more-btn, .announcement-card .like-btn, .add-comment-form .btn { width: 100%; display: block; margin-bottom: 0.4em; font-size: 1em; padding: 0.6em 0; }
    .announcement-card .like-btn { min-width: 0; }
    .announcement-card .comments-section { padding: 0.7rem 0.3rem; margin: 0.5rem -0.3rem 0.5rem -0.3rem; border-radius: 0.7em; }
    .comment-item, .comment-item.admin { padding: 0.5em 0.6em; border-radius: 0.7em; font-size: 0.97em; }
    .comment-avatar { width: 32px; height: 32px; font-size: 1em; }
    .add-comment-form .form-control { font-size: 0.97em; padding-left: 0.8em; border-radius: 1.5em 0 0 1.5em; }
    .add-comment-form { margin-top: 0.3em; }
    .show-all-btn, .show-less-btn, .view-all-comments { font-size: 0.97em; padding: 0.3em 0; }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function() {
// Like button AJAX
$(document).on('click', '.like-btn', function() {
    var btn = $(this);
    var announcementId = btn.data('announcement-id');
    $.post('/capstone2/resident/like_announcement.php', { id: announcementId }, function(data) {
        if (data.success) {
            $('#like-count-' + announcementId).text(data.likes);
            btn.toggleClass('btn-outline-primary btn-primary');
            $('#like-avatars-' + announcementId).html(data.avatars_html);
        } else {
            alert('Failed to like. Please try again.');
        }
    }, 'json').fail(function(xhr) {
        alert('Error: Unable to process like. Please make sure you are logged in.');
    });
});
// Load like avatars and comment count
$('.like-avatars, .comment-count').each(function() {
    var idAttr = $(this).attr('id');
    if (!idAttr) {
        console.warn('like-avatars or comment-count element without id:', this);
        return; // Skip if no id
    }
    var announcementId = idAttr.replace(/(like-avatars|comment-count)-/, '');
    console.log('Loading like/comment count for announcementId:', announcementId);
    $.get('/capstone2/resident/social_counts.php', { id: announcementId }, function(data) {
        $('#like-avatars-' + announcementId).html(data.avatars_html);
        $('#comment-count-' + announcementId).text(data.comment_count);
    }, 'json').fail(function(xhr) {
        alert('Error: Unable to load like/comment counts.');
    });
});
// Load 2 comments by default
$('.comments-list').each(function() {
    var list = $(this);
    var idAttr = list.attr('id');
    if (!idAttr) {
        console.warn('comments-list element without id:', list);
        return; // Skip if no id
    }
    var announcementId = idAttr.replace('comments-list-', '');
    console.log('Initial load comments for announcementId:', announcementId);
    $.get('/capstone2/resident/get_comments.php', { id: announcementId }, function(data) {
        list.html(data.html);
        // If there are more than 2 comments, show the 'Show all comments' link
        var commentCount = parseInt($('#comment-count-' + announcementId).text());
        if (commentCount > 2) {
            var showAllBtn = '<button class="btn btn-link p-0 show-all-btn" data-announcement-id="' + announcementId + '">Show all comments</button>';
            list.append(showAllBtn);
        }
    }, 'json').fail(function(xhr) {
        list.html('<div class="text-danger">Failed to load comments.</div>');
    });
});
// Show all comments when 'Show all comments' is clicked
$(document).on('click', '.show-all-btn', function() {
    var announcementId = $(this).data('announcement-id');
    var list = $('#comments-list-' + announcementId);
    $.get('/capstone2/resident/get_comments.php', { id: announcementId, all: 1 }, function(data) {
        console.log('Show all comments response:', data);
        // Add Show less button after all comments
        var showLessBtn = '<button class="btn btn-link p-0 show-less-btn" data-announcement-id="' + announcementId + '">Show less</button>';
        list.html(data.html + showLessBtn);
    }, 'json').fail(function(xhr) {
        list.html('<div class="text-danger">Failed to load all comments.</div>');
    });
});
// Add handler for Show less button
$(document).on('click', '.show-less-btn', function() {
    var announcementId = $(this).data('announcement-id');
    var list = $('#comments-list-' + announcementId);
    $.get('/capstone2/resident/get_comments.php', { id: announcementId }, function(data) {
        list.html(data.html);
        // If there are more than 2 comments, show the 'Show all comments' link again
        var commentCount = parseInt($('#comment-count-' + announcementId).text());
        if (commentCount > 2) {
            var showAllBtn = '<button class="btn btn-link p-0 show-all-btn" data-announcement-id="' + announcementId + '">Show all comments</button>';
            list.append(showAllBtn);
        }
    }, 'json').fail(function(xhr) {
        list.html('<div class="text-danger">Failed to reload comments.</div>');
    });
});
// Add comment AJAX
$(document).on('submit', '.add-comment-form', function(e) {
    e.preventDefault();
    var form = $(this);
    var announcementId = form.data('announcement-id');
    var comment = form.find('input[name="comment"]').val();
    $.post('/capstone2/resident/add_comment.php', { id: announcementId, comment: comment }, function(data) {
        if (data.success) {
            $('#comments-list-' + announcementId).html(data.html);
            $('#comment-count-' + announcementId).text(data.comment_count);
            form.find('input[name="comment"]').val('').blur();
            // Re-append show all button if needed
            if (data.comment_count > 2) {
                var showAllBtn = '<button class="btn btn-link p-0 show-all-btn" data-announcement-id="' + announcementId + '">Show all comments</button>';
                $('#comments-list-' + announcementId).append(showAllBtn);
            }
        } else {
            alert('Failed to post comment. Please try again.');
        }
    }, 'json').fail(function(xhr) {
        alert('Error: Unable to post comment. Please make sure you are logged in.');
    });
});
// Share button
$(document).on('click', '.share-btn', function() {
    var title = $(this).data('title');
    var id = $(this).data('id');
    var url = window.location.origin + '/capstone2/resident/announcements.php?id=' + id;
    navigator.clipboard.writeText(url);
    alert('Link copied to clipboard!');
});
});
</script>

<?php require_once '../includes/footer.php'; ?> 