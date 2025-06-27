<?php
$page_title = 'Document Requests';
require_once '../includes/header.php';
require_once '../includes/functions.php';
require_login();

$user_id = get_current_user_id();
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Handle new document request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'new') {
    try {
        $document_type = sanitize_input($_POST['document_type']);
        $purpose = sanitize_input($_POST['purpose']);
        $pickup_instructions = sanitize_input($_POST['pickup_instructions']);
        
        // Validate inputs
        if (empty($document_type) || empty($purpose)) {
            throw new Exception("Document type and purpose are required.");
        }
        
        // Convert document type to slug for cost calculation
        $document_type_slug = strtolower(str_replace(' ', '_', $document_type));

        // Get document cost based on type
        // Note: This logic can be moved to the calculate_document_cost function in functions.php for consistency
        $document_costs = [
            'barangay_clearance' => 50.00,
            'certificate_of_residency' => 50.00,
            'certificate_of_indigency' => 0.00, // Usually free
            'barangay_id' => 100.00,
            'certificate_of_house_ownership' => 150.00,
            'construction_clearance' => 200.00,
            'business_clearance' => 500.00,
            'endorsement_letter_for_business' => 100.00,
            'barangay_blotter' => 0.00, // Usually free
        ];
        
        $cost = $document_costs[$document_type_slug] ?? 0.00;
        
        // Check if user is a senior citizen for discount
        $stmt = $conn->prepare("SELECT is_senior_citizen FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user['is_senior_citizen']) {
            $cost *= 0.8; // 20% discount for senior citizens
        }
        
        // Insert new document request
        $stmt = $conn->prepare("
            INSERT INTO document_requests (
                user_id, document_type, purpose, pickup_instructions, 
                cost, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, 'pending', NOW(), NOW())
        ");
        $stmt->bind_param("isssd", $user_id, $document_type, $purpose, $pickup_instructions, $cost);
        
        if ($stmt->execute()) {
            $request_id = $conn->insert_id;
            log_activity($user_id, 'document_requested', "Requested document #$request_id");
            $success = "Document request has been submitted successfully.";
            $action = 'list'; // Switch to list view after successful creation
        } else {
            throw new Exception("Failed to submit document request.");
        }
    } catch (Exception $e) {
        error_log("Document Request Error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Get document requests based on action
try {
    if ($action === 'list') {
        $stmt = $conn->prepare("
            SELECT * FROM document_requests 
            WHERE user_id = ? 
            ORDER BY 
                CASE 
                    WHEN status = 'pending' THEN 1
                    WHEN status = 'processing' THEN 2
                    WHEN status = 'ready' THEN 3
                    WHEN status = 'completed' THEN 4
                    ELSE 5
                END,
                created_at DESC
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $documents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
} catch (Exception $e) {
    error_log("Document List Error: " . $e->getMessage());
    $error = "An error occurred while loading document requests.";
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
    <!-- Always show the important info box above the form -->
    <div class="alert alert-info" style="background: #e3f7fc;">
        <strong><i class="fas fa-info-circle"></i> Important Information</strong>
        <ul class="mb-0">
            <li>Document processing typically takes 1-2 business days.</li>
            <li>Senior citizens are eligible for a 20% discount on document fees.</li>
            <li>Payment is required upon pickup.</li>
            <li>Please bring a valid ID when claiming your document.</li>
        </ul>
    </div>
    <!-- New Document Request Form -->
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0">Request New Document</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="?action=new" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="document_type" class="form-label">Document Type</label>
                        <select class="form-select" id="document_type" name="document_type" required>
                            <option value="" disabled selected>Select document type...</option>
                            <?php 
                                $document_types = get_document_types();
                                foreach ($document_types as $type): 
                            ?>
                                <option value="<?php echo htmlspecialchars($type); ?>"><?php echo htmlspecialchars($type); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a document type.</div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="purpose" class="form-label">Purpose of Request</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="3" 
                                  placeholder="E.g., for employment, for school application, for financial assistance, etc." required></textarea>
                        <div class="invalid-feedback">Please provide the purpose of your request.</div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="pickup_instructions" class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-control" id="pickup_instructions" name="pickup_instructions" rows="2" 
                                  placeholder="E.g., 'Please call me when it is ready for pickup.'"></textarea>
                    </div>
                </div>
                
                <div class="d-flex justify-content-end gap-2">
                    <a href="?action=list" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Document Requests List -->
    <div class="card">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">My Document Requests</h5>
            <a href="?action=new" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Request
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($documents)): ?>
                <p class="text-muted mb-0">No document requests found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Document</th>
                                <th>Cost</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($documents as $document): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file-alt me-2 text-primary"></i>
                                        <?php echo htmlspecialchars($document['document_type']); ?>
                                    </td>
                                    <td><?php echo format_currency($document['cost']); ?></td>
                                    <td><?php echo get_status_badge($document['status']); ?></td>
                                    <td><?php echo format_datetime($document['created_at']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-info" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#viewModal<?php echo $document['request_id']; ?>">
                                            <i class="fas fa-eye"></i> View
                                        </button>
                                    </td>
                                </tr>
                                
                                <!-- View Modal -->
                                <div class="modal fade" id="viewModal<?php echo $document['request_id']; ?>" 
                                     tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Document Request Details</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" 
                                                        aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <dl class="row mb-0">
                                                    <dt class="col-sm-4">Document Type</dt>
                                                    <dd class="col-sm-8">
                                                        <?php echo htmlspecialchars($document['document_type']); ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Purpose</dt>
                                                    <dd class="col-sm-8">
                                                        <?php echo nl2br(htmlspecialchars($document['purpose'])); ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Cost</dt>
                                                    <dd class="col-sm-8">
                                                        <?php echo format_currency($document['cost']); ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Status</dt>
                                                    <dd class="col-sm-8">
                                                        <?php echo get_status_badge($document['status']); ?>
                                                    </dd>
                                                    
                                                    <dt class="col-sm-4">Requested On</dt>
                                                    <dd class="col-sm-8"><?php echo format_datetime($document['created_at']); ?></dd>
                                                    
                                                    <?php if(!empty($document['pickup_instructions'])): ?>
                                                    <dt class="col-sm-4">Instructions</dt>
                                                    <dd class="col-sm-8"><?php echo htmlspecialchars($document['pickup_instructions']); ?></dd>
                                                    <?php endif; ?>
                                                </dl>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" 
                                                        data-bs-dismiss="modal">Close</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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

<!-- Add this script at the end of the file before the closing body tag -->
<script>
document.getElementById('document_type').addEventListener('change', function() {
    var otherGroup = document.getElementById('other_document_type_group');
    if (this.value === 'Other') {
        otherGroup.style.display = 'block';
        document.getElementById('other_document_type').required = true;
    } else {
        otherGroup.style.display = 'none';
        document.getElementById('other_document_type').required = false;
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 