<?php
require_once '../includes/functions.php';
require_once '../includes/auth.php'; // For require_admin and session

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle announcement actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'create':
                case 'edit':
                    $announcement_id = $_POST['announcement_id'] ?? null;
                    $title = sanitize_input($_POST['title']);
                    $content = sanitize_input($_POST['content']);
                    $type = sanitize_input($_POST['type']);
                    $status = sanitize_input($_POST['status']);
                    $user_id = get_current_user_id();
                    $image_filename = null;
                    // Validate inputs
                    if (empty($title) || empty($content) || empty($type)) {
                        throw new Exception("Title, content, and type are required.");
                    }
                    global $conn;
                    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                        $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/capstone2/uploads/announcements/';
                        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
                        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
                        $image_filename = uniqid('ann_') . '.' . $ext;
                        move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image_filename);
                        error_log('DEBUG: image_filename = ' . var_export($image_filename, true));
                    } else {
                        $image_filename = null;
                    }
                    if ($_POST['action'] === 'create') {
                        // Set is_public automatically
                        $is_public = ($status === 'published') ? 1 : 0;
                        $stmt = $conn->prepare("
                            INSERT INTO announcements (
                                title, content, type, status, image, created_by, created_at, updated_at
                            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                        ");
                        $stmt->bind_param("sssssi",
                            $title, $content, $type, $status,
                            $image_filename, $user_id
                        );
                        if ($stmt->execute()) {
                            $announcement_id = $conn->insert_id;
                            log_activity($user_id, 'announcement_created',
                                "Created announcement: $title");
                            $success = "Announcement has been created successfully.";
                        } else {
                            throw new Exception("Failed to create announcement.");
                        }
                    } else {
                        $is_public = ($status === 'published') ? 1 : 0;
                        if ($image_filename) {
                            $stmt = $conn->prepare("
                                UPDATE announcements
                                SET title = ?, content = ?, type = ?, status = ?, is_public = ?, image = ?, updated_at = NOW()
                                WHERE announcement_id = ?
                            ");
                            $stmt->bind_param("ssssssi",
                                $title, $content, $type, $status,
                                $is_public, $image_filename, $announcement_id
                            );
                        } else {
                            $stmt = $conn->prepare("
                                UPDATE announcements
                                SET title = ?, content = ?, type = ?, status = ?, is_public = ?, updated_at = NOW()
                                WHERE announcement_id = ?
                            ");
                            $stmt->bind_param("sssssi",
                                $title, $content, $type, $status,
                                $is_public, $announcement_id
                            );
                        }
                        if ($stmt->execute()) {
                            log_activity($user_id, 'announcement_updated',
                                "Updated announcement: $title");
                            $success = "Announcement has been updated successfully.";
                        } else {
                            throw new Exception("Failed to update announcement.");
                        }
                    }
                    header("Location: announcements.php?success=" . urlencode($success));
                    exit;
                case 'delete':
                    $announcement_id = (int)$_POST['announcement_id'];
                    global $conn;
                    $stmt = $conn->prepare("
                        SELECT title FROM announcements WHERE announcement_id = ?
                    ");
                    $stmt->bind_param("i", $announcement_id);
                    $stmt->execute();
                    $announcement = $stmt->get_result()->fetch_assoc();
                    $stmt = $conn->prepare("
                        DELETE FROM announcements WHERE announcement_id = ?
                    ");
                    $stmt->bind_param("i", $announcement_id);
                    if ($stmt->execute()) {
                        log_activity($user_id, 'announcement_deleted', 
                            "Deleted announcement: " . $announcement['title']);
                        $success = "Announcement has been deleted successfully.";
                    } else {
                        throw new Exception("Failed to delete announcement.");
                    }
                    header("Location: announcements.php?success=" . urlencode($success));
                    exit;
                case 'update_status':
                    $announcement_id = (int)$_POST['announcement_id'];
                    $new_status = sanitize_input($_POST['status']);
                    $valid_statuses = ['draft', 'published', 'archived'];
                    if (!in_array($new_status, $valid_statuses)) {
                        throw new Exception("Invalid status.");
                    }
                    global $conn;
                    $stmt = $conn->prepare("
                        SELECT title FROM announcements WHERE announcement_id = ?
                    ");
                    $stmt->bind_param("i", $announcement_id);
                    $stmt->execute();
                    $announcement = $stmt->get_result()->fetch_assoc();
                    $stmt = $conn->prepare("
                        UPDATE announcements 
                        SET status = ?, updated_at = NOW() 
                        WHERE announcement_id = ?
                    ");
                    $stmt->bind_param("si", $new_status, $announcement_id);
                    if ($stmt->execute()) {
                        log_activity($user_id, 'announcement_status_updated', 
                            "Updated announcement status to $new_status: " . 
                            $announcement['title']);
                        $success = "Announcement status has been updated successfully.";
                    } else {
                        throw new Exception("Failed to update announcement status.");
                    }
                    header("Location: announcements.php?success=" . urlencode($success));
                    exit;
            }
        }
    } catch (Exception $e) {
        error_log("Announcement Management Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

require_once '../includes/header.php';
require_admin();

// Get announcement details for edit action
if ($action === 'edit' && isset($_GET['id'])) {
    try {
        $announcement_id = (int)$_GET['id'];
        $stmt = $conn->prepare("
            SELECT a.*, u.username as created_by_username 
            FROM announcements a 
            JOIN users u ON a.created_by = u.user_id 
            WHERE a.announcement_id = ?
        ");
        $stmt->bind_param("i", $announcement_id);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        
        if (!$announcement) {
            throw new Exception("Announcement not found.");
        }
    } catch (Exception $e) {
        error_log("Announcement Edit Error: " . $e->getMessage());
        $error = $e->getMessage();
        $action = 'list';
    }
}

// Get announcements list
try {
    $status_filter = $_GET['status'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';
    
    $query = "
        SELECT a.*, u.username as created_by_username
        FROM announcements a 
        JOIN users u ON a.created_by = u.user_id 
        WHERE 1=1
    ";
    $params = [];
    $types = "";
    
    if ($status_filter !== 'all') {
        $query .= " AND a.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if ($type_filter !== 'all') {
        $query .= " AND a.type = ?";
        $params[] = $type_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY a.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Announcement List Error: " . $e->getMessage());
    $error = "An error occurred while loading announcements: " . $e->getMessage();
    $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .ann-header { background: #388e3c; color: #fff; border-radius: 1rem 1rem 0 0; padding: 2rem 1rem 1rem 1rem; text-align: center; }
        .ann-icon { font-size: 3rem; color: #fff; }
        @media (max-width: 768px) { .ann-header { font-size: 1.2rem; padding: 1.2rem 0.5rem 0.5rem 0.5rem; } .ann-icon { font-size: 2rem; } }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="ann-header mb-4">
        <div class="ann-icon mb-2"><i class="bi bi-megaphone-fill"></i></div>
        <h2 class="fw-bold mb-1">Announcement Management</h2>
        <p class="mb-0">Create, edit, and manage all barangay announcements for residents and officials.</p>
    </div>
</div>

<!-- Display Messages -->
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($action === 'create' || $action === 'edit'): ?>
    <!-- Announcement Form -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">
                <?php echo $action === 'create' ? 'Create New Announcement' : 'Edit Announcement'; ?>
            </h5>
        </div>
        <div class="card-body">
            <form method="POST" class="needs-validation" novalidate enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="announcement_id" 
                           value="<?php echo $announcement['announcement_id']; ?>">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo $action === 'edit' ? 
                                     htmlspecialchars($announcement['title']) : ''; ?>" 
                               required>
                        <div class="invalid-feedback">Please enter a title.</div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="draft" <?php echo ($action === 'edit' && 
                                $announcement['status'] === 'draft') ? 'selected' : ''; ?>>
                                Draft
                            </option>
                            <option value="published" <?php echo ($action === 'edit' && 
                                $announcement['status'] === 'published') ? 'selected' : ''; ?>>
                                Published
                            </option>
                            <option value="archived" <?php echo ($action === 'edit' && 
                                $announcement['status'] === 'archived') ? 'selected' : ''; ?>>
                                Archived
                            </option>
                        </select>
                        <div class="invalid-feedback">Please select a status.</div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" 
                                  rows="6" required><?php 
                            echo $action === 'edit' ? 
                                 htmlspecialchars($announcement['content']) : ''; 
                        ?></textarea>
                        <div class="invalid-feedback">Please enter the announcement content.</div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="image" class="form-label">Image</label>
                        <input type="file" class="form-control" id="image" name="image" accept="image/*">
                        <div class="form-text">Optional. Upload an image for this announcement.</div>
                        <?php if ($action === 'edit' && !empty($announcement['image'])): ?>
                            <img src="/capstone2/uploads/announcements/<?php echo htmlspecialchars($announcement['image']); ?>" alt="Announcement Image" class="img-thumbnail mt-2" style="max-width: 200px;">
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="type" class="form-label">Type</label>
                        <select class="form-select" id="type" name="type" required>
                            <option value="">Select Type</option>
                            <?php foreach (get_announcement_types() as $type): ?>
                                <option value="<?php echo $type; ?>" 
                                    <?php echo ($action === 'edit' && 
                                              $announcement['type'] === $type) ? 
                                              'selected' : ''; ?>>
                                    <?php echo ucfirst($type); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a type.</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="announcements.php" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <?php echo $action === 'create' ? 'Create Announcement' : 'Save Changes'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>
                            All Status
                        </option>
                        <option value="draft" <?php echo $status_filter === 'draft' ? 'selected' : ''; ?>>
                            Draft
                        </option>
                        <option value="published" <?php echo $status_filter === 'published' ? 'selected' : ''; ?>>
                            Published
                        </option>
                        <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>
                            Archived
                        </option>
                    </select>
                </div>
                
                <div class="col-md-3">
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
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                    <a href="announcements.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Announcements List -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Announcements</h5>
            <a href="?action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Create Announcement
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($announcements)): ?>
                <p class="text-muted mb-0">No announcements found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($announcements as $announcement): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <h6 class="mb-0">
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo truncate_text($announcement['content'], 50); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td><?php echo ucfirst($announcement['type']); ?></td>
                                    <td><?php echo get_status_badge($announcement['status']); ?></td>
                                    <td><?php echo htmlspecialchars($announcement['created_by_username']); ?></td>
                                    <td><?php echo format_datetime($announcement['created_at']); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="?action=edit&id=<?php echo $announcement['announcement_id']; ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($announcement['status'] === 'draft'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-success"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal<?php 
                                                            echo $announcement['announcement_id']; ?>">
                                                    <i class="fas fa-paper-plane"></i>
                                                </button>
                                            <?php elseif ($announcement['status'] === 'published'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-info"
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#statusModal<?php 
                                                            echo $announcement['announcement_id']; ?>">
                                                    <i class="fas fa-archive"></i>
                                                </button>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php 
                                                        echo $announcement['announcement_id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewModal<?php 
                                                        echo $announcement['announcement_id']; ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                        
                                        <!-- Status Update Modal -->
                                        <div class="modal fade" id="statusModal<?php 
                                            echo $announcement['announcement_id']; ?>" 
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="update_status">
                                                        <input type="hidden" name="announcement_id" 
                                                               value="<?php echo $announcement['announcement_id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Announcement Status</h5>
                                                            <button type="button" class="btn-close" 
                                                                    data-bs-dismiss="modal" 
                                                                    aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="mb-3">
                                                                <label for="status" class="form-label">New Status</label>
                                                                <select class="form-select" name="status" required>
                                                                    <?php if ($announcement['status'] === 'draft'): ?>
                                                                        <option value="published">Publish Announcement</option>
                                                                        <option value="archived">Archive Announcement</option>
                                                                    <?php elseif ($announcement['status'] === 'published'): ?>
                                                                        <option value="archived">Archive Announcement</option>
                                                                    <?php endif; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" 
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary">
                                                                Update Status
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php 
                                            echo $announcement['announcement_id']; ?>" 
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="announcement_id" 
                                                               value="<?php echo $announcement['announcement_id']; ?>">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Announcement</h5>
                                                            <button type="button" class="btn-close" 
                                                                    data-bs-dismiss="modal" 
                                                                    aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this announcement?</p>
                                                            <p class="mb-0 text-danger">
                                                                This action cannot be undone.
                                                            </p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" 
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-danger">
                                                                Delete Announcement
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?php 
                                            echo $announcement['announcement_id']; ?>" 
                                             tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Announcement Details</h5>
                                                        <button type="button" class="btn-close" 
                                                                data-bs-dismiss="modal" 
                                                                aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                                        <p class="text-muted mb-3">
                                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                                        </p>
                                                        
                                                        <dl class="row mb-0">
                                                            <dt class="col-sm-3">Type</dt>
                                                            <dd class="col-sm-9">
                                                                <?php echo ucfirst($announcement['type']); ?>
                                                            </dd>
                                                            
                                                            <dt class="col-sm-3">Status</dt>
                                                            <dd class="col-sm-9">
                                                                <?php echo get_status_badge($announcement['status']); ?>
                                                            </dd>
                                                            
                                                            <dt class="col-sm-3">Created By</dt>
                                                            <dd class="col-sm-9">
                                                                <?php echo htmlspecialchars($announcement['created_by_username']); ?>
                                                            </dd>
                                                            
                                                            <dt class="col-sm-3">Created</dt>
                                                            <dd class="col-sm-9">
                                                                <?php echo format_datetime($announcement['created_at']); ?>
                                                            </dd>
                                                            
                                                            <dt class="col-sm-3">Last Updated</dt>
                                                            <dd class="col-sm-9">
                                                                <?php echo format_datetime($announcement['updated_at']); ?>
                                                            </dd>
                                                        </dl>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" 
                                                                data-bs-dismiss="modal">Close</button>
                                                        <a href="?action=edit&id=<?php 
                                                            echo $announcement['announcement_id']; ?>" 
                                                           class="btn btn-primary">Edit Announcement</a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Form Validation Script -->
<script>
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()
</script>

<?php require_once '../includes/footer.php'; ?> 

