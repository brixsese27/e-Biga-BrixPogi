<?php
$page_title = 'Appointments';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_login();

$user_id = get_current_user_id();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle appointment cancellation
if ($action === 'cancel' && isset($_GET['id'])) {
    try {
        $appointment_id = (int)$_GET['id'];
        
        // Verify appointment belongs to user
        $stmt = $conn->prepare("
            SELECT * FROM appointments 
            WHERE appointment_id = ? AND user_id = ? AND status = 'pending'
        ");
        $stmt->bind_param("ii", $appointment_id, $user_id);
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
        
        if ($appointment) {
            $stmt = $conn->prepare("
                UPDATE appointments 
                SET status = 'cancelled', updated_at = NOW() 
                WHERE appointment_id = ?
            ");
            $stmt->bind_param("i", $appointment_id);
            
            if ($stmt->execute()) {
                log_activity($conn, $user_id, 'appointment_cancelled', "Cancelled appointment #$appointment_id");
                $success = "Appointment has been cancelled successfully.";
            } else {
                throw new Exception("Failed to cancel appointment.");
            }
        } else {
            throw new Exception("Invalid appointment or already processed.");
        }
    } catch (Exception $e) {
        error_log("Appointment Cancellation Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle new appointment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'new') {
    try {
        $appointment_type = sanitize_input($_POST['appointment_type']);
        $appointment_date = sanitize_input($_POST['appointment_date']);
        $appointment_time = sanitize_input($_POST['appointment_time']);
        $purpose = sanitize_input($_POST['purpose']);
        $other_type = isset($_POST['other_type']) ? sanitize_input($_POST['other_type']) : '';
        $official_with = isset($_POST['official_with']) ? sanitize_input($_POST['official_with']) : '';
        
        // Validate inputs
        if (empty($appointment_type) || empty($appointment_date) || empty($appointment_time)) {
            throw new Exception("All fields are required.");
        }
        
        if (!is_valid_date($appointment_date)) {
            throw new Exception("Invalid date format.");
        }
        
        if (!is_valid_time($appointment_time)) {
            throw new Exception("Invalid time format.");
        }
        
        // Check if date is in the future
        if (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
            throw new Exception("Appointment date must be in the future.");
        }
        
        // Check for existing appointments at the same time
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count FROM appointments 
            WHERE appointment_date = ? AND appointment_time = ? AND status != 'cancelled'
        ");
        $stmt->bind_param("ss", $appointment_date, $appointment_time);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result['count'] > 0) {
            throw new Exception("This time slot is already booked. Please choose another time.");
        }
        
        // Compose purpose with extra info if needed
        if ($appointment_type === 'other' && $other_type) {
            $purpose = '[Other: ' . $other_type . '] ' . $purpose;
        }
        if ($appointment_type === 'meeting' && $official_with) {
            $purpose = '[Meeting with: ' . $official_with . '] ' . $purpose;
        }
        
        // Insert new appointment
        $stmt = $conn->prepare("
            INSERT INTO appointments (user_id, appointment_type, appointment_date, appointment_time, purpose, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
        ");
        $stmt->bind_param("issss", $user_id, $appointment_type, $appointment_date, $appointment_time, $purpose);
        
        if ($stmt->execute()) {
            $appointment_id = $conn->insert_id;
            log_activity($conn, $user_id, 'appointment_created', "Created new appointment #$appointment_id");
            $success = "Appointment has been scheduled successfully.";
            $action = 'list'; // Switch to list view after successful creation
        } else {
            throw new Exception("Failed to schedule appointment.");
        }
    } catch (Exception $e) {
        error_log("Appointment Creation Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Get appointments based on action
try {
    if ($action === 'list') {
        $stmt = $conn->prepare("
            SELECT * FROM appointments 
            WHERE user_id = ? 
            ORDER BY 
                CASE 
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'approved' THEN 2
                    WHEN status = 'completed' THEN 3
                    ELSE 4
                END,
                appointment_date ASC, 
                appointment_time ASC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Appointment List Error: " . $e->getMessage());
    $error = "An error occurred while loading appointments.";
}
?>

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

<?php if ($action === 'new'): ?>
    <!-- New Appointment Form -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Schedule New Appointment</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="?action=new" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="appointment_type" class="form-label">Appointment Type</label>
                        <select class="form-select" id="appointment_type" name="appointment_type" required>
                            <option value="">Select type...</option>
                            <option value="document">Document Request</option>
                            <option value="meeting">Official Meeting</option>
                            <option value="other">Other</option>
                        </select>
                        <div class="invalid-feedback">Please select an appointment type.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="appointment_date" class="form-label">Date</label>
                        <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                               min="<?php echo date('Y-m-d'); ?>" required>
                        <div class="invalid-feedback">Please select a valid date.</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="appointment_time" class="form-label">Time</label>
                        <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                        <div class="invalid-feedback">Please select a time slot.</div>
                    </div>
                    
                    <div class="col-md-12 mb-3" id="officialWithRow" style="display:none;">
                        <label for="official_with" class="form-label">Who is the meeting with?</label>
                        <input type="text" class="form-control" id="official_with" name="official_with" placeholder="Enter name or position">
                    </div>
                    
                    <div class="col-md-12 mb-3" id="otherTypeRow" style="display:none;">
                        <label for="other_type" class="form-label">Please specify other appointment type</label>
                        <input type="text" class="form-control" id="other_type" name="other_type" placeholder="Enter appointment type">
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="purpose" class="form-label">Purpose</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                  placeholder="Please describe the purpose of your appointment..." required></textarea>
                        <div class="invalid-feedback">Please provide the purpose of your appointment.</div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Schedule Appointment</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Appointments List -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">My Appointments</h5>
            <a href="?action=new" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Appointment
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($appointments)): ?>
                <p class="text-muted mb-0">No appointments found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Date & Time</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointments as $appointment): ?>
                                <tr>
                                    <td>
                                        <?php
                                        $type_icons = [
                                            'health' => 'fa-heartbeat',
                                            'document' => 'fa-file-alt',
                                            'meeting' => 'fa-handshake',
                                            'other' => 'fa-calendar'
                                        ];
                                        $icon = $type_icons[$appointment['appointment_type']] ?? 'fa-calendar';
                                        ?>
                                        <i class="fas <?php echo $icon; ?> me-2"></i>
                                        <?php echo ucfirst($appointment['appointment_type']); ?>
                                    </td>
                                    <td>
                                        <?php 
                                        echo format_date($appointment['appointment_date']) . ' ' . 
                                             format_time($appointment['appointment_time']); 
                                        ?>
                                    </td>
                                    <td><?php echo truncate_text($appointment['purpose'], 50); ?></td>
                                    <td><?php echo get_status_badge($appointment['status']); ?></td>
                                    <td><?php echo format_datetime($appointment['created_at']); ?></td>
                                    <td>
                                        <?php if ($appointment['status'] === 'pending'): ?>
                                            <a href="?action=cancel&id=<?php echo $appointment['appointment_id']; ?>" 
                                               class="btn btn-sm btn-outline-danger"
                                               onclick="return confirm('Are you sure you want to cancel this appointment?')">
                                                <i class="fas fa-times"></i> Cancel
                                            </a>
                                        <?php endif; ?>
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

document.getElementById('appointment_type').addEventListener('change', function() {
    var type = this.value;
    document.getElementById('otherTypeRow').style.display = (type === 'other') ? 'block' : 'none';
    document.getElementById('officialWithRow').style.display = (type === 'meeting') ? 'block' : 'none';
});
</script>

<?php require_once '../includes/footer.php'; ?> 