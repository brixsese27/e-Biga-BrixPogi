<?php
$page_title = 'Appointment Management';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_admin();

$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Get appointments based on action and filters
try {
    $status_filter = $_GET['status'] ?? 'all';
    $type_filter = $_GET['type'] ?? 'all';
    
    $query = "
        SELECT a.*, u.username, ud.first_name, ud.last_name, ud.contact_number 
        FROM appointments a 
        JOIN users u ON a.user_id = u.user_id 
        JOIN user_details ud ON u.user_id = ud.user_id 
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
        $query .= " AND a.appointment_type = ?";
        $params[] = $type_filter;
        $types .= "s";
    }
    
    $query .= " ORDER BY 
        CASE 
            WHEN a.status = 'Pending' THEN 1
            WHEN a.status = 'Approved' THEN 2
            WHEN a.status = 'Completed' THEN 3
            WHEN a.status = 'Declined' THEN 4
            ELSE 5
        END,
        a.appointment_date DESC, 
        a.appointment_time DESC";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get distinct appointment types for the filter dropdown
    $types_result = $conn->query("SELECT DISTINCT appointment_type FROM appointments ORDER BY appointment_type");
    $appointment_types = $types_result->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    error_log("Appointment List Error: " . $e->getMessage());
    $error = "An error occurred while loading appointments.";
}
$all_statuses = ['Pending', 'Approved', 'Declined', 'Completed'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .appt-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 1rem; padding: 2rem; text-align: center; }
        .appt-icon { font-size: 3rem; color: #fff; }
        .status-select {
            border: 2px solid;
            font-weight: 500;
            transition: all 0.2s ease-in-out;
        }
        .status-Pending { border-color: #ffc107; background-color: #fff9e6; color: #997404; }
        .status-Approved { border-color: #198754; background-color: #e8f5e9; color: #0f5132; }
        .status-Declined { border-color: #dc3545; background-color: #fdeeee; color: #842029; }
        .status-Completed { border-color: #0d6efd; background-color: #e7f0ff; color: #084298; }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="appt-header mb-4">
        <div class="appt-icon mb-2"><i class="bi bi-calendar2-week"></i></div>
        <h2 class="fw-bold mb-1">Appointment Management</h2>
        <p class="mb-0">View, approve, and manage all appointments with barangay officials and staff.</p>
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

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <?php foreach ($all_statuses as $status): ?>
                    <option value="<?php echo $status; ?>" <?php if ($status_filter === $status) echo 'selected'; ?>>
                        <?php echo ucfirst($status); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="type" class="form-label">Type</label>
                <select class="form-select" id="type" name="type" onchange="this.form.submit()">
                    <option value="all" <?php if ($type_filter === 'all') echo 'selected'; ?>>All Types</option>
                    <?php foreach ($appointment_types as $type): ?>
                    <option value="<?php echo htmlspecialchars($type['appointment_type']); ?>" <?php if ($type_filter === $type['appointment_type']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars(ucfirst($type['appointment_type'])); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3 d-flex align-items-end">
                <a href="?" class="btn btn-outline-secondary">Reset Filters</a>
            </div>
        </form>
    </div>
</div>

<!-- Appointments List -->
<div class="card">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">All Appointments</h5>
        <a href="/capstone2/admin/appointments.php?action=create" class="btn btn-primary"><i class="bi bi-plus-circle me-2"></i>New Appointment</a>
    </div>
    <div class="card-body">
        <div id="alert-container"></div>
        <?php if (empty($appointments)): ?>
            <p class="text-muted mb-0">No appointments found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Resident</th>
                            <th>Type</th>
                            <th>Date & Time</th>
                            <th>Purpose</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars($appointment['username']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(ucfirst($appointment['appointment_type'])); ?></td>
                                <td>
                                    <?php echo format_date($appointment['appointment_date']); ?>
                                    <br>
                                    <small class="text-muted"><?php echo format_time($appointment['appointment_time']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars(truncate_text($appointment['purpose'], 40)); ?></td>
                                <td><?php echo htmlspecialchars($appointment['contact_number']); ?></td>
                                <td>
                                    <select class="form-select form-select-sm status-select status-<?php echo str_replace(' ', '_', $appointment['status']); ?>" data-appointment-id="<?php echo $appointment['appointment_id']; ?>">
                                        <?php foreach ($all_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php if ($appointment['status'] === $status) echo 'selected'; ?>>
                                            <?php echo $status; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td><?php echo format_datetime($appointment['created_at']); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal-<?php echo $appointment['appointment_id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- View Modals -->
<?php foreach ($appointments as $appointment): ?>
<div class="modal fade" id="viewModal-<?php echo $appointment['appointment_id']; ?>" tabindex="-1" aria-labelledby="viewModalLabel-<?php echo $appointment['appointment_id']; ?>" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewModalLabel-<?php echo $appointment['appointment_id']; ?>">Appointment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <dl class="row">
                    <dt class="col-sm-4">Resident:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></dd>
                    
                    <dt class="col-sm-4">Username:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($appointment['username']); ?></dd>

                    <dt class="col-sm-4">Contact:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($appointment['contact_number']); ?></dd>

                    <dt class="col-sm-4">Type:</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars(ucfirst($appointment['appointment_type'])); ?></dd>

                    <dt class="col-sm-4">Date & Time:</dt>
                    <dd class="col-sm-8"><?php echo format_datetime($appointment['appointment_date'] . ' ' . $appointment['appointment_time']); ?></dd>

                    <dt class="col-sm-4">Status:</dt>
                    <dd class="col-sm-8"><?php echo get_status_badge($appointment['status']); ?></dd>

                    <dt class="col-sm-4">Full Purpose:</dt>
                    <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($appointment['purpose'])); ?></dd>
                    
                    <dt class="col-sm-4">Requested On:</dt>
                    <dd class="col-sm-8"><?php echo format_datetime($appointment['created_at']); ?></dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

</div> <!-- end container -->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const statusSelects = document.querySelectorAll('.status-select');
    
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const appointmentId = this.dataset.appointmentId;
            const newStatus = this.value;
            
            // Update dropdown color class
            this.className = 'form-select form-select-sm status-select status-' + newStatus.replace(' ', '_');

            updateAppointmentStatus(appointmentId, newStatus, this);
        });
    });

    function updateAppointmentStatus(appointmentId, status, element) {
        fetch('/capstone2/admin/update_appointment_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                appointment_id: appointmentId,
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            showAlert(data.message, data.success ? 'success' : 'danger');
            if (!data.success) {
                // Optional: Revert selection on failure
                // This would require storing the original value
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An unexpected error occurred. Please check the console.', 'danger');
        });
    }

    function showAlert(message, type) {
        const alertContainer = document.getElementById('alert-container');
        const alert = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        alertContainer.innerHTML = alert;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>
</body>
</html> 