<?php
require_once '../includes/header.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barangay Services - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f5f5f5;
        }
        .services-header {
            background: #2E7D32;
            color: #fff;
            border-radius: 1rem 1rem 0 0;
            padding: 2rem 1rem 1rem 1rem;
            text-align: center;
        }
        .service-card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 2px 12px rgba(46,125,50,0.08);
            transition: box-shadow 0.2s, transform 0.2s;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .service-card:hover {
            box-shadow: 0 4px 24px rgba(46,125,50,0.18);
            transform: translateY(-4px) scale(1.02);
            color: #2E7D32;
            text-decoration: none;
        }
        .service-icon {
            font-size: 2.5rem;
            color: #388e3c;
        }
        .doc-list {
            list-style: none;
            padding-left: 0;
        }
        .doc-list li {
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }
        .status-badge {
            font-size: 0.95rem;
            margin-left: 0.5rem;
        }
        .note {
            background: #e8f5e9;
            border-left: 5px solid #388e3c;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 1.5rem;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .services-header { font-size: 1.2rem; padding: 1.2rem 0.5rem 0.5rem 0.5rem; }
            .service-icon { font-size: 2rem; }
        }
    </style>
</head>
<body>
<div class="container py-4">
    <div class="services-header mb-4">
        <h2 class="fw-bold mb-1"><i class="bi bi-gear-fill me-2"></i>Barangay Services Management</h2>
        <p class="mb-0">Manage all barangay services, document requests, and appointments in one place.</p>
    </div>
    <div class="row g-4 justify-content-center">
        <div class="col-md-6 col-lg-4">
            <a href="/capstone2/admin/announcements.php" class="card service-card h-100">
                <div class="card-body text-center">
                    <div class="service-icon mb-2"><i class="bi bi-megaphone-fill"></i></div>
                    <h5 class="fw-bold">General Announcements</h5>
                    <p class="mb-2">Post and manage barangay-wide announcements for residents and officials.</p>
                </div>
            </a>
        </div>
        <div class="col-md-6 col-lg-4">
            <a href="/capstone2/admin/appointments.php" class="card service-card h-100">
                <div class="card-body text-center">
                    <div class="service-icon mb-2"><i class="bi bi-calendar-check-fill"></i></div>
                    <h5 class="fw-bold">Appointments</h5>
                    <p class="mb-2">Manage appointments with barangay officials and track schedules.</p>
                </div>
            </a>
        </div>
        <div class="col-12">
            <a href="/capstone2/admin/documents.php" class="card service-card">
                <div class="card-body">
                    <div class="d-flex align-items-center mb-2">
                        <div class="service-icon me-2"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <h5 class="fw-bold mb-0">Document Requests</h5>
                    </div>
                    <p class="mb-2">Process and track requests for official barangay documents. Set cost and pickup instructions for each document type.</p>
                    <ul class="doc-list">
                        <li><i class="bi bi-dot"></i> Barangay Clearance</li>
                        <li><i class="bi bi-dot"></i> Certificate of Residency</li>
                        <li><i class="bi bi-dot"></i> Certificate of Indigency</li>
                        <li><i class="bi bi-dot"></i> Barangay ID</li>
                        <li><i class="bi bi-dot"></i> Certificate of House Ownership / House Location</li>
                        <li><i class="bi bi-dot"></i> Construction Clearance</li>
                        <li><i class="bi bi-dot"></i> Business Clearance</li>
                        <li><i class="bi bi-dot"></i> Endorsement Letter for Business</li>
                        <li><i class="bi bi-dot"></i> Barangay Blotter / Incident Report</li>
                    </ul>
                    <div class="mt-3">
                        <span class="fw-bold">Real-Time Status Tracker:</span>
                        <span class="badge bg-secondary status-badge">Pending</span>
                        <span class="badge bg-warning text-dark status-badge">In Progress</span>
                        <span class="badge bg-info text-dark status-badge">For Pickup</span>
                        <span class="badge bg-success status-badge">Completed</span>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="note mt-4">
        <i class="bi bi-info-circle-fill me-2"></i>
        <strong>Note:</strong> All document requests are <span class="fw-bold">free upon request</span>. Payment (if any) is collected only upon pickup at the barangay office. Please set cost and pickup instructions per document as needed.
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php require_once '../includes/footer.php'; ?> 