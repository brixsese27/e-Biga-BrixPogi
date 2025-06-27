<?php
$page_title = 'Resident Dashboard';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_login();

// Get user information
try {
    $user_id = get_current_user_id();
    
    // Get user details
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.email, u.profile_picture, ud.first_name, ud.last_name, u.is_senior_citizen, ud.voter_status
        FROM users u 
        LEFT JOIN user_details ud ON u.user_id = ud.user_id 
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    
    if (!$user) {
        // Handle case where user details might not exist yet, though this is unlikely with current registration flow
        $stmt = $conn->prepare("SELECT user_id, username, email, profile_picture, is_senior_citizen FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $user['first_name'] = 'New';
        $user['last_name'] = 'Resident';
    }

    // Get ALL appointments, newest first
    $stmt = $conn->prepare("
        SELECT * FROM appointments 
        WHERE user_id = ?
        ORDER BY appointment_date DESC, appointment_time DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get ALL document requests
    $stmt = $conn->prepare("
        SELECT * FROM document_requests 
        WHERE user_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $document_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get all active announcements
    $stmt = $conn->prepare("
        SELECT
            a.*,
            (SELECT COUNT(*) FROM announcement_likes al WHERE al.announcement_id = a.announcement_id) as like_count,
            (SELECT COUNT(*) FROM announcement_comments ac WHERE ac.announcement_id = a.announcement_id) as comment_count,
            (SELECT COUNT(*) FROM announcement_likes al_user WHERE al_user.announcement_id = a.announcement_id AND al_user.user_id = ?) > 0 as user_liked
        FROM
            announcements a
        WHERE
            a.is_active = 1
        ORDER BY
            a.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    // Log the detailed error message for the admin
    error_log("Resident Dashboard Error: " . $e->getMessage());
    // Show a generic, user-friendly error on the page
    $error = "An error occurred while loading your dashboard data. Please try again later.";
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Dashboard - Barangay Biga</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2E7D32; /* Deep Green */
            --secondary-color: #1976D2; /* Bright Blue */
            --accent-color: #FFC107; /* Amber */
            --light-gray: #f8f9fa;
            --dark-gray: #6c757d;
        }

        body {
            background-color: var(--light-gray);
            font-family: 'Poppins', sans-serif;
        }

        .welcome-header {
            background: linear-gradient(135deg, var(--primary-color), #4CAF50);
            color: white;
            padding: 2.5rem 2rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
        }
        
        .profile-pic {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border: 3px solid white;
        }

        .quick-action-card {
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            background-color: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .quick-action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(46,125,50,0.15);
        }

        .quick-action-icon {
            font-size: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        .icon-blue { background-color: #e3f2fd; color: var(--secondary-color); }
        .icon-green { background-color: #e8f5e9; color: var(--primary-color); }
        .icon-cyan { background-color: #e0f7fa; color: #0097a7; }
        .icon-yellow { background-color: #fffde7; color: var(--accent-color); }

        .card {
            border-radius: 1rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            border-radius: 1rem 1rem 0 0;
            padding: 1.25rem;
            font-weight: 600;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .newsfeed-card {
            background-color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .newsfeed-img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
            cursor: zoom-in;
        }

        .newsfeed-body {
            max-height: 800px; /* Adjust as needed */
            overflow-y: auto;
            padding: 1rem;
            background-color: #f8f9fa;
        }

        .announcement-post {
            background: #fff;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.07);
            padding: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .announcement-post:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .announcement-post .newsfeed-img {
            border-radius: 0.5rem;
            margin-bottom: 1.25rem;
        }

        /* Lightbox for Images */
        #img-lightbox {
            display: none;
            position: fixed;
            z-index: 9999;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            justify-content: center;
            align-items: center;
        }
        #img-lightbox.show {
            display: flex;
        }
        #img-lightbox img {
            max-width: 90vw;
            max-height: 90vh;
            border-radius: 0.5rem;
        }
        #img-lightbox .close-btn {
            position: absolute;
            top: 20px;
            right: 30px;
            font-size: 2.5rem;
            color: #fff;
            cursor: pointer;
            border: none;
            background: none;
        }

        /* --- Enhanced Comments & Likes Section --- */
        .comments-section h6 {
            font-weight: 700;
            color: #1976d2;
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
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
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
            object-fit: cover;
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
        .add-comment-form {
            margin-top: 0.5em;
            background: #f7fafd;
            border-radius: 2em;
            padding: 0.3em 0.7em;
            box-shadow: 0 1px 4px rgba(44,62,80,0.04);
        }
        .add-comment-form input.form-control {
            border-radius: 2em;
            font-size: 1em;
            border: 1px solid #d0d7de;
            background: #fff;
        }
        .add-comment-form button.btn {
            border-radius: 2em;
            font-weight: 600;
            padding: 0.5em 1.5em;
        }
        .like-btn {
            border-radius: 2em !important;
            font-weight: 500;
            min-width: 80px;
            transition: background 0.2s, color 0.2s;
        }
        .like-btn.liked, .like-btn.text-primary {
            background: linear-gradient(90deg, #43a047 60%, #1976d2 100%) !important;
            color: #fff !important;
            border: none !important;
        }
        .like-btn:not(.liked):hover {
            background: #1976d2 !important;
            color: #fff !important;
        }
        @media (max-width: 600px) {
            .comments-section, .add-comment-form { padding: 0.5rem 0.2rem; }
            .comment-item, .comment-item.admin { padding: 0.5em 0.6em; border-radius: 0.7em; font-size: 0.97em; }
            .comment-avatar { width: 32px; height: 32px; font-size: 1em; }
            .add-comment-form input.form-control { font-size: 0.97em; padding-left: 0.8em; border-radius: 1.5em; }
        }
    </style>
</head>
<body>
<div class="container py-4">

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <!-- Welcome Header -->
    <div class="welcome-header d-flex align-items-center">
        <?php if ($user): ?>
            <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '/capstone2/images/profiledefault.png'; ?>"
                 alt="Profile Picture" class="rounded-circle me-4 profile-pic">
            <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                    <h2 class="h4 mb-0 fw-bold">Welcome, <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>!</h2>
                    <?php if (!empty($user['is_senior_citizen'])): ?>
                        <span class="badge bg-warning text-dark">Senior Citizen</span>
                    <?php endif; ?>
                    <?php if (!empty($user['voter_status'])): ?>
                        <span class="badge bg-info text-dark">Registered Voter</span>
                    <?php endif; ?>
                </div>
                <p class="mb-0 opacity-75">Here's what's happening in your community.</p>
            </div>
        <?php else: ?>
            <div>
                <h2 class="h4 mb-0 fw-bold text-warning">User information not found.</h2>
                <p class="mb-0 opacity-75">Please update your profile to get the full experience.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4 g-4">
        <div class="col-md-3 col-6">
            <a href="/capstone2/resident/appointments.php?action=new" class="quick-action-card text-center p-3 h-100">
                <div class="quick-action-icon icon-blue mx-auto"><i class="fas fa-calendar-plus"></i></div>
                <h6 class="fw-bold">Book Appointment</h6>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/capstone2/resident/documents.php?action=new" class="quick-action-card text-center p-3 h-100">
                <div class="quick-action-icon icon-green mx-auto"><i class="fas fa-file-alt"></i></div>
                <h6 class="fw-bold">Request Document</h6>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="/capstone2/resident/profile.php" class="quick-action-card text-center p-3 h-100">
                <div class="quick-action-icon icon-cyan mx-auto"><i class="fas fa-user-circle"></i></div>
                <h6 class="fw-bold">Update Profile</h6>
            </a>
        </div>
        <div class="col-md-3 col-6">
            <a href="#newsfeed" class="quick-action-card text-center p-3 h-100">
                <div class="quick-action-icon icon-yellow mx-auto"><i class="fas fa-bullhorn"></i></div>
                <h6 class="fw-bold">View Announcements</h6>
            </a>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row g-4">
        <!-- Left Column -->
        <div class="col-lg-5 d-flex flex-column gap-4">
            <!-- My Appointments -->
            <div class="card mb-4 h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-check me-2 text-success"></i>My Appointments</h5>
                </div>
                <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                    <?php if (empty($appointments)): ?>
                        <p class="text-muted text-center mt-3">You have no upcoming appointments.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col">Date & Time</th>
                                        <th scope="col">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $appointment): ?>
                                        <tr>
                                            <td><?php echo format_datetime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']); ?></td>
                                            <td><?php echo get_status_badge($appointment['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="/capstone2/resident/appointments.php" class="btn btn-outline-primary">Manage All Appointments</a>
                </div>
            </div>

            <!-- My Document Requests -->
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-info"></i>My Document Requests</h5>
                </div>
                <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                    <?php if (empty($document_requests)): ?>
                        <p class="text-muted text-center mt-3">You have no document requests.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th scope="col" style="width: 50%;">Requested On</th>
                                        <th scope="col" style="width: 30%;">Document</th>
                                        <th scope="col" style="width: 20%;">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($document_requests as $doc): ?>
                                        <tr>
                                            <td><?php echo format_datetime($doc['created_at']); ?></td>
                                            <td><?php echo htmlspecialchars($doc['document_type']); ?></td>
                                            <td><?php echo get_status_badge($doc['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="/capstone2/resident/documents.php" class="btn btn-outline-primary">Manage All Documents</a>
                </div>
            </div>
        </div>

        <!-- Right Column (Newsfeed) -->
        <div class="col-lg-7" id="newsfeed">
             <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn me-2"></i>Barangay Newsfeed</h5>
                    <a href="/capstone2/resident/announcements.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body newsfeed-body p-2">
                    <?php if (empty($announcements)): ?>
                        <p class="text-muted text-center mb-0">No announcements to display right now.</p>
                    <?php else: ?>
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="announcement-post">
                                <?php if (!empty($announcement['image'])): ?>
                                    <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="newsfeed-img img-fluid" alt="Announcement Image">
                                <?php endif; ?>
                                <h4 class="fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                <p class="text-muted small mb-2"><i class="bi bi-calendar-event"></i> <?php echo time_ago($announcement['created_at']); ?></p>
                                <p><?php echo nl2br(htmlspecialchars(truncate_text($announcement['content'], 150))); ?></p>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $announcement['announcement_id']; ?>">
                                        <i class="bi bi-book-half me-1"></i> Read More & Comments
                                    </button>
                                    <div class="interaction-buttons d-flex align-items-center">
                                        <button class="btn btn-link text-decoration-none me-2 like-btn <?php echo $announcement['user_liked'] ? 'text-primary' : 'text-muted'; ?>" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                                            <i class="bi <?php echo $announcement['user_liked'] ? 'bi-hand-thumbs-up-fill' : 'bi-hand-thumbs-up'; ?>"></i>
                                            <span id="like-count-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['like_count']; ?></span>
                                        </button>
                                        <span class="d-flex align-items-center ms-2">
                                            <i class="bi bi-chat-left-text me-1"></i>
                                            <span id="comment-count-btn-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['comment_count']; ?></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox -->
<div id="img-lightbox">
    <button class="close-btn">&times;</button>
    <img src="" alt="Full size preview">
</div>

<!-- Modals -->
<?php foreach ($announcements as $announcement): ?>
<div class="modal fade" id="viewModal<?php echo $announcement['announcement_id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel<?php echo $announcement['announcement_id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel<?php echo $announcement['announcement_id']; ?>"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if (!empty($announcement['image'])): ?>
                    <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="newsfeed-img img-fluid mb-3" alt="Announcement Image">
                <?php endif; ?>

                <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                <hr>
                <!-- Likes and Comments count in Modal -->
                <div class="d-flex justify-content-start align-items-center mb-3">
                     <span class="text-muted me-3">
                        <i class="bi bi-hand-thumbs-up-fill text-primary"></i> <span id="like-count-modal-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['like_count']; ?></span> Likes
                    </span>
                    <span class="text-muted">
                        <i class="bi bi-chat-fill text-success"></i> <span id="comment-count-modal-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['comment_count']; ?></span> Comments
                    </span>
                </div>

                <!-- Comments Section -->
                <div class="comments-section mt-4">
                    <h6 class="fw-bold mb-3">Comments</h6>
                    <div id="comments-list-<?php echo $announcement['announcement_id']; ?>" class="mb-3">
                        <!-- Comments will be loaded here via AJAX -->
                        <p class="text-center text-muted">Loading comments...</p>
                    </div>
                    <form class="add-comment-form d-flex align-items-center gap-2" data-announcement-id="<?php echo $announcement['announcement_id']; ?>">
                        <img src="<?php echo htmlspecialchars($_SESSION['profile_picture'] ?? '/capstone2/images/profiledefault.png'); ?>" class="rounded-circle me-2" style="width:38px;height:38px;object-fit:cover;" alt="Me">
                        <input type="text" name="comment_text" class="form-control rounded-pill px-3" placeholder="Write a comment..." required style="flex:1;">
                        <button class="btn btn-primary rounded-pill px-4" type="submit">Post</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php require_once '../includes/footer.php'; ?>

<script>
$(document).ready(function() {
    // Function to load comments for an announcement
    function loadComments(announcementId, showAll = false) {
        $.get('/capstone2/resident/get_comments.php', { id: announcementId, show_all: showAll })
            .done(function(data) {
                if (data && typeof data.html !== 'undefined') {
                    $('#comments-list-' + announcementId).html(data.html);
                    $('#comment-count-' + announcementId).text(data.total_comments);
                }
            })
            .fail(function() {
                $('#comments-list-' + announcementId).html('<p class="text-danger">Could not load comments.</p>');
            });
    }

    // Image Lightbox
    $(document).on('click', '.announcement-img-clickable', function() {
        const src = $(this).attr('src');
        $('#img-lightbox img').attr('src', src);
        $('#img-lightbox').addClass('show');
    });

    $('#img-lightbox .close-btn, #img-lightbox').on('click', function(e) {
        if (e.target === this || $(e.target).hasClass('close-btn')) {
            $('#img-lightbox').removeClass('show');
        }
    });

    // Load initial comments when modal opens
    $('.modal').on('show.bs.modal', function (event) {
        var modal = $(this);
        var announcementId = modal.attr('id').replace('viewModal', '');
        loadComments(announcementId, false);
    });

    // Handle "View all comments" click
    $(document).on('click', '.view-all-comments', function(e) {
        e.preventDefault();
        var announcementId = $(this).data('announcement-id');
        loadComments(announcementId, true);
    });

    // Handle comment submission
    $(document).on('submit', '.add-comment-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var announcementId = form.data('announcement-id');
        var commentInput = form.find('input[name="comment_text"]');

        $.post('/capstone2/resident/add_comment.php', { id: announcementId, comment_text: commentInput.val() })
            .done(function(data) {
                if(data.success) {
                    commentInput.val(''); // Clear input
                    loadComments(announcementId, true); // Reload all comments to show the new one
                } else {
                    alert(data.message || 'Failed to post comment.');
                }
            })
            .fail(function() {
                alert('An error occurred. Please try again.');
            });
    });

    // Handle Like button
    $(document).on('click', '.heart-btn', function() {
        var btn = $(this);
        var announcementId = btn.data('announcement-id');

        $.post('/capstone2/resident/like_announcement.php', { id: announcementId })
            .done(function(data) {
                if(data.success) {
                    btn.toggleClass('liked', data.liked);
                    // Update like count
                    var likeCountSpan = btn.find('span');
                    likeCountSpan.text(data.likes);
                } else {
                    alert(data.message || 'Could not process like.');
                }
            })
            .fail(function() {
                alert('An error occurred. Please try again.');
            });
    });
});
</script>

</body>
</html> 