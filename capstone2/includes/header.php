<?php
require_once __DIR__ . '/auth.php';

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);

// Get user role for menu items
$user_role = get_current_user_role();
$is_admin = is_admin();
$is_senior = is_senior_citizen();

// Fetch latest profile picture for navbar
$profile_picture = '/capstone2/images/profiledefault.png';
if (is_logged_in()) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['profile_picture']) && $row['profile_picture'] !== 'NULL') {
            $profile_picture = $row['profile_picture'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Barangay Biga MIS'; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #2E7D32;
            --primary-light: #4CAF50;
            --primary-dark: #1B5E20;
        }
        
        body {
            font-size: 16px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem;
        }
        
        .navbar-brand {
            color: white !important;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-size: 1.1rem;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: white !important;
        }
        
        .nav-link.active {
            color: white !important;
            font-weight: 600;
            background-color: var(--primary-dark);
            border-radius: 5px;
        }
        
        .dropdown-menu {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            border-radius: 8px;
        }
        
        .dropdown-item {
            font-size: 1rem;
            padding: 0.5rem 1.5rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: var(--primary-color);
        }
        
        .user-menu {
            color: white;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-menu img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid white;
        }
        
        .senior-badge {
            background-color: #FFC107;
            color: #000;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            margin-left: 5px;
        }
        
        .main-content {
            flex: 1;
            padding: 2rem 0;
        }
        
        .footer {
            background-color: var(--primary-dark);
            color: white;
            padding: 1.5rem 0;
            margin-top: auto;
        }
        
        @media (max-width: 768px) {
            .navbar-nav {
                margin-top: 1rem;
            }
            
            .nav-link {
                padding: 0.5rem 0 !important;
            }
            
            .user-menu {
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/capstone2/index.php">
                <img src="/capstone2/images/R.png" alt="Barangay Biga Logo" height="55" style="margin-right:18px;" class="logo">
                Barangay Biga MIS
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <?php if (is_logged_in()): ?>
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" 
                               href="/capstone2/<?php echo $user_role; ?>/dashboard.php">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                        </li>
                        <?php if ($is_admin): ?>
                            <!-- Admin Menu Items -->
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'services.php' ? 'active' : ''; ?>" 
                                   href="/capstone2/admin/services.php">
                                    <i class="fas fa-file-alt"></i> Services
                                </a>
                            </li>
                        <?php else: ?>
                            <!-- Resident Menu Items -->
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'appointments.php' ? 'active' : ''; ?>" 
                                   href="/capstone2/resident/appointments.php">
                                    <i class="fas fa-calendar-check"></i> Appointments
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?php echo $current_page === 'documents.php' ? 'active' : ''; ?>" 
                                   href="/capstone2/resident/documents.php">
                                    <i class="fas fa-file-alt"></i> Documents
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                    
                    <!-- User Menu -->
                    <div class="user-menu">
                        <div class="dropdown">
                            <button class="btn btn-link dropdown-toggle text-white text-decoration-none" type="button" 
                                    id="userMenu" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="<?php echo htmlspecialchars($profile_picture); ?>" 
                                     alt="Profile Picture">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                                <?php if ($is_senior): ?>
                                    <span class="senior-badge">Senior</span>
                                <?php endif; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                                <li>
                                    <a class="dropdown-item" href="/capstone2/<?php echo $user_role; ?>/profile.php">
                                        <i class="fas fa-user"></i> Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger" href="/capstone2/logout.php">
                                        <i class="fas fa-sign-out-alt"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <!-- Main Content Container -->
    <div class="main-content">
        <div class="container">
            <?php if (isset($page_title)): ?>
                <h1 class="mb-4"><?php echo htmlspecialchars($page_title); ?></h1>
            <?php endif; ?> 