<?php
$page_title = 'Document Request Management';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_admin();

// Logic for creating/updating a request would go here...

// Get document requests based on filters
try {
    $status_filter = $_GET['status'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';
    $date_filter = $_GET['date'] ?? '';
    
    $query = "
        SELECT dr.*, u.username, ud.first_name, ud.last_name, ud.contact_number,
               u.is_senior_citizen
        FROM document_requests dr 
        JOIN users u ON dr.user_id = u.user_id 
        JOIN user_details ud ON u.user_id = ud.user_id 
        WHERE 1=1
    ";
    $params = [];
    $types = "";
    
    if ($status_filter !== 'all') {
        $query .= " AND dr.status = ?";
        $params[] = $status_filter;
        $types .= "s";
    }
    
    if ($type_filter !== 'all') {
        $query .= " AND dr.document_type = ?";
        $params[] = $type_filter;
        $types .= "s";
    }
    
    if ($date_filter) {
        $query .= " AND DATE(dr.created_at) = ?";
        $params[] = $date_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY dr.created_at DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Get distinct document types for the filter dropdown
    $types_result = $conn->query("SELECT DISTINCT document_type FROM document_requests WHERE document_type IS NOT NULL AND document_type != '' ORDER BY document_type");
    $document_types = $types_result->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    error_log("Document List Error: " . $e->getMessage());
    $error = "An error occurred while loading document requests.";
}
$all_statuses = ['Pending', 'Approved', 'Declined', 'Ready for Pickup', 'Claimed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .doc-header { background: linear-gradient(135deg, #00B4DB 0%, #0083B0 100%); color: #fff; border-radius: 1rem; padding: 2rem; text-align: center; }
        .doc-icon { font-size: 3rem; color: #fff; }
        .status-select {
            border: 2px solid;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .status-Pending { border-color: #ffc107; background-color: #fff9e6; color: #997404; }
        .status-Approved { border-color: #198754; background-color: #e8f5e9; color: #0f5132; }
        .status-Declined { border-color: #dc3545; background-color: #fdeeee; color: #842029; }
        .status-Ready_for_Pickup { border-color: #0dcaf0; background-color: #e7fbff; color: #057d9b; }
        .status-Claimed { border-color: #0d6efd; background-color: #e7f0ff; color: #084298; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="doc-header mb-4">
        <div class="doc-icon mb-2"><i class="bi bi-file-earmark-text"></i></div>
        <h2 class="fw-bold mb-1">Document Request Management</h2>
        <p class="mb-0">Process, track, and manage all barangay document requests efficiently.</p>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                        <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <?php foreach ($all_statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php if ($status_filter === $status) echo 'selected'; ?>>
                            <?php echo $status; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">Document Type</label>
                    <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                        <option value="all" <?php if ($type_filter === 'all') echo 'selected'; ?>>All Types</option>
                        <?php foreach ($document_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type['document_type']); ?>" <?php if ($type_filter === $type['document_type']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($type['document_type']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="date" class="form-label">Request Date</label>
                    <input type="date" class="form-control" id="date" name="date" 
                           value="<?php echo $date_filter; ?>" onchange="this.form.submit()">
                </div>
                
                <div class="col-md-3">
                    <a href="?" class="btn btn-outline-secondary w-100">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Document Requests List -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">All Document Requests</h5>
            <a href="/capstone2/resident/documents.php" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>New Request</a>
        </div>
        <div class="card-body">
            <div id="alert-container"></div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Document</th>
                            <th>Purpose</th>
                            <th>Cost</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($documents)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No document requests found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td>
                                        <?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo htmlspecialchars($document['username']); ?>
                                            <?php if ($document['is_senior_citizen']): ?>
                                                <span class="badge bg-info text-dark">Senior Citizen</span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td><?php echo htmlspecialchars($document['document_type']); ?></td>
                                    <td><?php echo htmlspecialchars(truncate_text($document['purpose'], 40)); ?></td>
                                    <td>
                                        <?php 
                                        $cost = calculate_document_cost($document['document_type'], $document['is_senior_citizen']);
                                        echo format_currency($cost);
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($document['contact_number']); ?></td>
                                    <td>
                                        <select class="form-select form-select-sm status-select status-<?php echo str_replace(' ', '_', $document['status']); ?>" data-request-id="<?php echo $document['request_id']; ?>">
                                            <?php foreach ($all_statuses as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php if ($document['status'] === $status) echo 'selected'; ?>>
                                                <?php echo $status; ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td><?php echo format_datetime($document['created_at']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal-<?php echo $document['request_id']; ?>">
                                            <i class="bi bi-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- View Modals -->
<?php foreach ($documents as $document): ?>
<div class="modal fade" id="viewModal-<?php echo $document['request_id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel-<?php echo $document['request_id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel-<?php echo $document['request_id']; ?>">Document Request Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Resident:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($document['first_name'] . ' ' . $document['last_name']); ?></dd>
                    
                    <dt class="col-sm-4">Username:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($document['username']); ?></dd>

                    <dt class="col-sm-4">Contact:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($document['contact_number']); ?></dd>

                    <dt class="col-sm-4">Document:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($document['document_type']); ?></dd>
                    
                    <dt class="col-sm-4">Cost:</dt>
                    <dd class="col-sm-8"><?php echo format_currency(calculate_document_cost($document['document_type'], $document['is_senior_citizen'])); ?></dd>

                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8"><?php echo get_status_badge($document['status']); ?></dd>

                    <dt class="col-sm-4">Full Purpose:</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($document['purpose'])); ?></dd>
                    
                    <dt class="col-sm-4">Requested On:</dt>
                    <dd class="col-sm-8"><?php echo format_datetime($document['created_at']); ?></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const requestId = this.dataset.requestId;
            const newStatus = this.value;

            // Update dropdown color class
            this.className = 'form-select form-select-sm status-select status-' + newStatus.replace(' ', '_');

            updateDocumentStatus(requestId, newStatus);
        });
    });

    function updateDocumentStatus(requestId, status) {
        fetch('/capstone2/admin/update_document_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                request_id: requestId,
                status: status
            })
        })
        .then(response => {
            if (!response.ok) {
                // If response is not ok (e.g., 403, 404, 500), throw an error to be caught by .catch
                return response.json().then(errorInfo => {
                    throw new Error(errorInfo.message || 'Server responded with an error.');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
            } else {
                showAlert(data.message || 'An unknown error occurred.', 'danger');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert(error.message || 'An unexpected error occurred. Please check the console.', 'danger');
        });
    }

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alertId = 'alert-' + Date.now();
        const alert = `
            <div id="${alertId}" class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertContainer.innerHTML = alert; // Replace previous alert with the new one

        setTimeout(() => {
            const activeAlert = document.getElementById(alertId);
            if (activeAlert) {
                new bootstrap.Alert(activeAlert).close();
            }
        }, 5000);
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html> 