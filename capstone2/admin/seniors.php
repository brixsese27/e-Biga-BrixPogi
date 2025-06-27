<?php
require_once '../includes/header.php';
require_admin();

// Handle edit action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_senior'])) {
    $user_id = (int)$_POST['user_id'];
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $contact_number = trim($_POST['contact_number']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    try {
        // Update user_details
        $stmt = $conn->prepare("UPDATE user_details SET first_name=?, last_name=?, contact_number=?, address=? WHERE user_id=?");
        $stmt->bind_param("ssssi", $first_name, $last_name, $contact_number, $address, $user_id);
        $stmt->execute();
        // Update users (email)
        $stmt = $conn->prepare("UPDATE users SET email=? WHERE user_id=?");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        $success = 'Senior info updated successfully!';
    } catch (Exception $e) {
        $error = 'Failed to update senior info.';
    }
}
// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_senior'])) {
    $user_id = (int)$_POST['user_id'];
    try {
        $stmt = $conn->prepare("DELETE FROM user_details WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $success = 'Senior deleted successfully!';
    } catch (Exception $e) {
        $error = 'Failed to delete senior.';
    }
}
// Fetch all senior citizens
$seniors = [];
try {
    $stmt = $conn->prepare("SELECT u.user_id, u.email, ud.first_name, ud.last_name, ud.contact_number, ud.address FROM users u JOIN user_details ud ON u.user_id = ud.user_id WHERE u.role = 'resident' AND u.is_senior_citizen = 1");
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $seniors[] = $row;
    }
} catch (Exception $e) {
    $error = 'Failed to load senior citizens list.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Senior Citizens - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f5f5f5; }
        .senior-header {
            background: #388e3c;
            color: #fff;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
        }
        .senior-icon { font-size: 3rem; color: #fff; }
        .card { border-radius: 1rem; }
        .info-card { background: #e8f5e9; border: none; }
        @media (max-width: 768px) { .senior-header { font-size: 1.2rem; padding: 1.2rem 0.5rem 0.5rem 0.5rem; } .senior-icon { font-size: 2rem; } }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="senior-header mb-4">
        <div class="senior-icon mb-2"><i class="bi bi-person-badge-fill"></i></div>
        <h2 class="fw-bold mb-1">Senior Citizens Management</h2>
        <p class="mb-0">View, manage, and support all registered senior citizens in Barangay Biga.</p>
    </div>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card info-card p-4">
                <h5 class="fw-bold mb-2"><i class="bi bi-people-fill me-2"></i>Senior Citizens List</h5>
                <p class="mb-2">This section will display all senior citizens, their benefits, and support programs. You can add, edit, or remove records as needed.</p>
                <div class="alert alert-info mt-3 mb-4">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <strong>Tip:</strong> Use the search and filter options to quickly find a resident.
                </div>
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <!-- Search Bar -->
                <div class="mb-3">
                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name, email, or contact...">
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="seniorsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Address</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($seniors)): ?>
                            <tr><td colspan="5" class="text-center text-muted">No senior citizens found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($seniors as $senior): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($senior['email']); ?></td>
                                    <td><?php echo htmlspecialchars($senior['contact_number']); ?></td>
                                    <td><?php echo htmlspecialchars($senior['address']); ?></td>
                                    <td>
                                        <!-- Edit Button -->
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $senior['user_id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <!-- Delete Button -->
                                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $senior['user_id']; ?>">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <!-- Edit Modal -->
                                <div class="modal fade" id="editModal<?php echo $senior['user_id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $senior['user_id']; ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content">
                                      <form method="POST">
                                        <div class="modal-header">
                                          <h5 class="modal-title" id="editModalLabel<?php echo $senior['user_id']; ?>">Edit Senior Info</h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                          <input type="hidden" name="user_id" value="<?php echo $senior['user_id']; ?>">
                                          <div class="mb-3">
                                            <label class="form-label">First Name</label>
                                            <input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($senior['first_name']); ?>" required>
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Last Name</label>
                                            <input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($senior['last_name']); ?>" required>
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($senior['email']); ?>" required>
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Contact Number</label>
                                            <input type="text" class="form-control" name="contact_number" value="<?php echo htmlspecialchars($senior['contact_number']); ?>">
                                          </div>
                                          <div class="mb-3">
                                            <label class="form-label">Address</label>
                                            <input type="text" class="form-control" name="address" value="<?php echo htmlspecialchars($senior['address']); ?>">
                                          </div>
                                        </div>
                                        <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                          <button type="submit" name="edit_senior" class="btn btn-primary">Save Changes</button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                                <!-- Delete Modal -->
                                <div class="modal fade" id="deleteModal<?php echo $senior['user_id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $senior['user_id']; ?>" aria-hidden="true">
                                  <div class="modal-dialog">
                                    <div class="modal-content">
                                      <form method="POST">
                                        <div class="modal-header">
                                          <h5 class="modal-title" id="deleteModalLabel<?php echo $senior['user_id']; ?>">Delete Senior</h5>
                                          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                          <input type="hidden" name="user_id" value="<?php echo $senior['user_id']; ?>">
                                          <p>Are you sure you want to delete <strong><?php echo htmlspecialchars($senior['first_name'] . ' ' . $senior['last_name']); ?></strong>?</p>
                                        </div>
                                        <div class="modal-footer">
                                          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                          <button type="submit" name="delete_senior" class="btn btn-danger">Delete</button>
                                        </div>
                                      </form>
                                    </div>
                                  </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Instant search for seniors table
const searchInput = document.getElementById('searchInput');
const table = document.getElementById('seniorsTable');
if (searchInput && table) {
    searchInput.addEventListener('keyup', function() {
        const filter = searchInput.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(filter) ? '' : 'none';
        });
    });
}
</script>
</body>
</html>
<?php require_once '../includes/footer.php'; ?> 