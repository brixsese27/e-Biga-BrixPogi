<?php
$page_title = 'Admin Dashboard';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_admin();

$current_admin_id = get_current_user_id();

// Get statistics
try {
    // Total residents
    $total_residents = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'resident'")->fetch_assoc()['total'];
    // Senior citizens
    $total_seniors = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'resident' AND is_senior_citizen = 1")->fetch_assoc()['total'];
    // Pending appointments
    $pending_appointments = $conn->query("SELECT COUNT(*) as total FROM appointments WHERE status = 'pending'")->fetch_assoc()['total'];
    // Pending document requests
    $pending_documents = $conn->query("SELECT COUNT(*) as total FROM document_requests WHERE status = 'pending'")->fetch_assoc()['total'];

    // Recent appointments
    $recent_appointments_stmt = $conn->prepare("
        SELECT a.*, ud.first_name, ud.last_name 
        FROM appointments a 
        JOIN user_details ud ON a.user_id = ud.user_id 
        WHERE a.appointment_date >= CURDATE() 
        ORDER BY a.appointment_date ASC, a.appointment_time ASC 
        LIMIT 5
    ");
    $recent_appointments_stmt->execute();
    $recent_appointments = $recent_appointments_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Recent document requests
    $recent_docs_stmt = $conn->prepare("
        SELECT d.*, ud.first_name, ud.last_name 
        FROM document_requests d 
        JOIN user_details ud ON d.user_id = ud.user_id 
        ORDER BY d.created_at DESC 
        LIMIT 5
    ");
    $recent_docs_stmt->execute();
    $recent_documents = $recent_docs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch all announcements with counts
    $announcements_stmt = $conn->prepare("
        SELECT a.*,
               (SELECT COUNT(*) FROM announcement_likes WHERE announcement_id = a.announcement_id) as like_count,
               (SELECT COUNT(*) FROM announcement_comments WHERE announcement_id = a.announcement_id) as comment_count
        FROM announcements a
        WHERE a.is_active = 1
        ORDER BY a.created_at DESC
    ");
    $announcements_stmt->execute();
    $announcements = $announcements_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Admin Dashboard Error: " . $e->getMessage());
    $error = "An error occurred while loading dashboard data.";
}

// Show success modal if account was created
$showAccountSuccess = isset($_GET['account_success']) && $_GET['account_success'] == 1;
$usernameCreated = $_GET['username'] ?? '';
$passwordCreated = $_GET['password'] ?? '';
$roleCreated = $_GET['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .dash-header { background: #2e7d32; color: #fff; border-radius: 1rem 1rem 0 0; padding: 2rem 1rem 1rem 1rem; text-align: center; }
        .dash-icon { font-size: 3rem; color: #fff; }
        @media (max-width: 768px) { .dash-header { font-size: 1.2rem; padding: 1.2rem 0.5rem 0.5rem 0.5rem; } .dash-icon { font-size: 2rem; } }
        .recent-announcement-card {
            max-width: 400px;
            margin: 0 auto 1.5rem auto;
            border-radius: 1.5rem;
            box-shadow: 0 2px 16px rgba(44, 62, 80, 0.08);
            overflow: hidden;
            background: #fff;
            text-align: center;
            padding: 1.5rem 1rem 1rem 1rem;
        }
        .recent-announcement-card img {
            border-radius: 1rem;
            max-height: 180px;
            object-fit: cover;
            margin-bottom: 1rem;
            width: 100%;
        }
        .recent-announcement-card .badge {
            margin: 0 0.25em 0.5em 0.25em;
            font-size: 0.95em;
        }
        .recent-announcement-card .ann-title {
            font-size: 1.2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }
        .recent-announcement-card .ann-date {
            color: #888;
            font-size: 0.95em;
            margin-bottom: 0.5rem;
        }
        .recent-announcement-card .ann-content {
            color: #444;
            font-size: 1em;
            margin-bottom: 1rem;
        }
        .recent-announcement-card .read-more-btn {
            background: linear-gradient(90deg, #1976d2 60%, #43a047 100%);
            border: none;
            color: #fff;
            font-weight: 600;
            border-radius: 2em;
            padding: 0.5em 1.5em;
            box-shadow: 0 2px 8px rgba(44,62,80,0.10);
            transition: background 0.2s, box-shadow 0.2s, transform 0.2s;
        }
        .recent-announcement-card .read-more-btn:hover {
            background: linear-gradient(90deg, #43a047 60%, #1976d2 100%);
            box-shadow: 0 4px 16px rgba(44,62,80,0.18);
            transform: translateY(-2px) scale(1.03);
            color: #fff;
        }
        .announcement-modal-img {
            width: 100%;
            max-height: 320px;
            object-fit: cover;
            border-radius: 0.75rem 0.75rem 0 0;
            display: block;
        }
        .announcement-modal-body {
            padding-left: 0;
            padding-right: 0;
        }
        .newsfeed-card {
            background-color: white;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .newsfeed-img {
            width: 100%;
            max-height: 400px;
            object-fit: cover;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
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
        .admin-reply {
            background-color: #e9f5e9; /* Light green background for admin replies */
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="dash-header mb-4">
        <div class="dash-icon mb-2"><i class="bi bi-speedometer2"></i></div>
        <h2 class="fw-bold mb-1">Admin Dashboard</h2>
        <p class="mb-0">Monitor, manage, and get a quick overview of all barangay activities and services.</p>
    </div>
</div>

<!-- Account Created Success Modal -->
<div class="modal fade" id="accountSuccessModal" tabindex="-1" aria-labelledby="accountSuccessModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="accountSuccessModalLabel">Account Created Successfully!</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>The new account has been created. Here are the credentials:</p>
        <ul class="list-group mb-3">
          <li class="list-group-item"><strong>Username:</strong> <span id="createdUsername"><?php echo htmlspecialchars($usernameCreated); ?></span></li>
          <li class="list-group-item"><strong>Password:</strong> <span id="createdPassword"><?php echo htmlspecialchars($passwordCreated); ?></span></li>
          <li class="list-group-item"><strong>Role:</strong> <span id="createdRole"><?php echo htmlspecialchars(ucfirst($roleCreated)); ?></span></li>
        </ul>
        <div class="alert alert-info mb-0">Please copy and give these credentials to the user.</div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>
<script>
<?php if ($showAccountSuccess): ?>
window.addEventListener('DOMContentLoaded', function() {
  var modal = new bootstrap.Modal(document.getElementById('accountSuccessModal'));
  modal.show();
});
<?php endif; ?>
</script>

<!-- Dashboard Overview -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Total Residents</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($total_residents); ?></h2>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="/capstone2/admin/residents.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Senior Citizens</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($total_seniors); ?></h2>
                    </div>
                    <i class="fas fa-user-clock fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="/capstone2/admin/seniors.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Pending Appointments</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($pending_appointments); ?></h2>
                    </div>
                    <i class="fas fa-calendar-check fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="/capstone2/admin/appointments.php" class="text-dark text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="card-title mb-0">Pending Documents</h6>
                        <h2 class="mt-2 mb-0"><?php echo number_format($pending_documents); ?></h2>
                    </div>
                    <i class="fas fa-file-alt fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a href="/capstone2/admin/documents.php" class="text-white text-decoration-none">View Details</a>
                <i class="fas fa-angle-right"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="row">
    <!-- Recent Appointments -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Appointments</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_appointments)): ?>
                    <p class="text-muted mb-0">No upcoming appointments</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Type</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_appointments as $appointment): ?>
                                    <tr onclick="window.location='/capstone2/admin/appointments.php';" style="cursor:pointer;">
                                        <td>
                                            <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['appointment_type']); ?></td>
                                        <td>
                                            <?php 
                                            echo format_date($appointment['appointment_date']) . ' ' . 
                                                 format_time($appointment['appointment_time']); 
                                            ?>
                                        </td>
                                        <td><?php echo get_status_badge($appointment['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <a href="/capstone2/admin/appointments.php" class="btn btn-sm btn-outline-primary">View All Appointments</a>
            </div>
        </div>
    </div>
    
    <!-- Recent Document Requests -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Recent Document Requests</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_documents)): ?>
                    <p class="text-muted mb-0">No recent document requests</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Resident</th>
                                    <th>Document</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_documents as $document): ?>
                                    <tr onclick="window.location='/capstone2/admin/documents.php';" style="cursor:pointer;">
                                        <td>
                                            <?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                        <td><?php echo format_datetime($document['created_at']); ?></td>
                                        <td><?php echo get_status_badge($document['status']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white">
                <a href="/capstone2/admin/documents.php" class="btn btn-sm btn-outline-primary">View All Documents</a>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions and Announcements -->
<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-3">
        <div class="card mb-4">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body d-grid gap-2">
                <a href="/capstone2/admin/appointments.php?action=create" class="btn btn-outline-primary"><i class="bi bi-calendar-plus"></i> New Appointment</a>
                <a href="/capstone2/admin/documents.php?action=create" class="btn btn-outline-primary"><i class="bi bi-file-earmark-plus"></i> New Document Request</a>
                <a href="/capstone2/admin/announcements.php?action=create" class="btn btn-outline-primary"><i class="bi bi-pencil-square"></i> New Post</a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAccountModal"><i class="bi bi-person-plus"></i> New Account</button>
            </div>
        </div>
    </div>
    
    <!-- Recent Announcements -->
    <div class="col-md-9 mb-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0"><i class="bi bi-newspaper me-2"></i>Barangay Newsfeed</h5>
            </div>
            <div class="card-body newsfeed-body p-2">
                <?php if (empty($announcements)): ?>
                    <p class="text-muted text-center mb-0">No announcements found.</p>
                <?php else: ?>
                    <?php foreach ($announcements as $announcement): ?>
                        <div class="announcement-post">
                            <?php if (!empty($announcement['image'])): ?>
                                <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="newsfeed-img img-fluid" alt="Announcement Image">
                            <?php endif; ?>
                            <h4 class="fw-bold"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <p class="text-muted small mb-2"><i class="bi bi-calendar-event"></i> <?php echo format_datetime($announcement['created_at']); ?></p>
                            <p><?php echo nl2br(htmlspecialchars(truncate_text($announcement['content'], 150))); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $announcement['announcement_id']; ?>">
                                    <i class="bi bi-book-half me-1"></i> Read More & Comments
                                </button>
                                <div class="text-muted">
                                    <span class="me-3"><i class="bi bi-hand-thumbs-up-fill text-primary"></i> <?php echo $announcement['like_count']; ?></span>
                                    <span><i class="bi bi-chat-fill text-success"></i> <?php echo $announcement['comment_count']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Announcement Modal -->
                        <div class="modal fade" id="viewModal<?php echo $announcement['announcement_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <?php if (!empty($announcement['image'])): ?>
                                            <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" class="announcement-modal-img img-fluid mb-3" alt="Announcement Image">
                                        <?php endif; ?>
                                        <p class="mb-3"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                        <hr>
                                        <div class="d-flex align-items-center mb-3">
                                            <span class="text-muted me-3"><i class="bi bi-hand-thumbs-up-fill text-primary"></i> <span id="like-count-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['like_count']; ?></span> Likes</span>
                                            <span class="text-muted"><i class="bi bi-chat-fill text-success"></i> <span id="comment-count-<?php echo $announcement['announcement_id']; ?>"><?php echo $announcement['comment_count']; ?></span> Comments</span>
                                        </div>
                                        <div class="comments-section mt-3">
                                            <h6 class="mb-2">Comments</h6>
                                            <div class="comments-list" id="comments-list-<?php echo $announcement['announcement_id']; ?>"><p class="text-muted">Loading comments...</p></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-white text-end">
                <a href="/capstone2/admin/announcements.php" class="btn btn-sm btn-outline-primary">View All Announcements</a>
            </div>
        </div>
    </div>
</div>

<!-- New Account Modal -->
<div class="modal fade" id="newAccountModal" tabindex="-1" aria-labelledby="newAccountModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="/capstone2/admin/register_account.php" id="newAccountForm">
        <div class="modal-header">
          <h5 class="modal-title" id="newAccountModalLabel">Create New Account</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="first_name" class="form-label">First Name</label>
            <input type="text" class="form-control" id="first_name" name="first_name" required>
          </div>
          <div class="mb-3">
            <label for="last_name" class="form-label">Last Name</label>
            <input type="text" class="form-control" id="last_name" name="last_name" required>
          </div>
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
          </div>
          <div class="mb-3">
            <label for="username" class="form-label">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
          </div>
          <div class="mb-3">
            <label for="confirm_password" class="form-label">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <div class="invalid-feedback">Passwords do not match.</div>
          </div>
          <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select class="form-select" id="role" name="role" required>
              <option value="resident">Resident</option>
              <option value="admin">Admin</option>
            </select>
          </div>
          <div class="mb-3">
            <label for="birth_date" class="form-label">Birth Date</label>
            <input type="date" class="form-control" id="birth_date" name="birth_date" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Account</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
// Password match validation
const newAccountForm = document.getElementById('newAccountForm');
if (newAccountForm) {
  newAccountForm.addEventListener('submit', function(e) {
    const pw = document.getElementById('password');
    const cpw = document.getElementById('confirm_password');
    if (pw.value !== cpw.value) {
      cpw.classList.add('is-invalid');
      e.preventDefault();
      e.stopPropagation();
    } else {
      cpw.classList.remove('is-invalid');
    }
  });
}
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Function to load comments for an announcement
    function loadAdminComments(announcementId) {
        $.get('/capstone2/admin/get_comments_admin.php', { id: announcementId })
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

    // Load comments when a modal opens
    $('.modal').on('show.bs.modal', function (event) {
        var modal = $(this);
        var announcementId = modal.attr('id').replace('viewModal', '');
        if(announcementId) {
            loadAdminComments(announcementId);
        }
    });

    // Handle admin reply submission
    $(document).on('submit', '.admin-reply-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var announcementId = form.data('announcement-id');
        var parentId = form.data('parent-id');
        var replyInput = form.find('textarea[name="reply"]');

        $.post('/capstone2/admin/reply_comment.php', {
            announcement_id: announcementId,
            parent_id: parentId,
            reply: replyInput.val()
        })
        .done(function(data) {
            if(data.success) {
                loadAdminComments(announcementId); // Reload comments to show the new reply
            } else {
                alert(data.message || 'Failed to post reply.');
            }
        })
        .fail(function() {
            alert('An error occurred while posting the reply.');
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?> 