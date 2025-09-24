<?php
// Include configuration file
require_once '../config.php';

// Include validation utilities
require_once '../includes/validation.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../index.php');
    exit; // Make sure to exit after redirect
}

// Get user information
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'];
$username = $_SESSION['username'];

// Start output buffering to prevent "headers already sent" errors
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 d-md-block sidebar collapse bg-light">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4 mt-3">
                        <h4 class="text-primary">Library Management</h4>
                    </div>
                    <!-- Mobile menu toggle -->
                    <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    
                    <!-- Back button -->
                    <div class="mb-3 ps-3">
                        <button onclick="goBack()" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </button>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center py-3 border-bottom" href="dashboard.php">
                                <i class="fas fa-home me-2"></i> <span>Dashboard</span>
                            </a>
                        </li>
                        
                        <!-- Transactions Menu -->
                        <li class="nav-item">
                            <a class="nav-link d-flex align-items-center py-3 border-bottom" href="transactions.php">
                                <i class="fas fa-exchange-alt me-2"></i> <span>Transactions</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book_availability.php">
                                <i class="fas fa-search"></i> Check Book Availability
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="issue_book.php">
                                <i class="fas fa-book"></i> Issue a Book
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="return_book.php">
                                <i class="fas fa-undo"></i> Return a Book
                            </a>
                        </li>
                        
                        <!-- Reports Menu -->
                        <li class="nav-item">
                            <a class="nav-link" href="reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="book_list.php">
                                <i class="fas fa-list"></i> Master List of Books
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="movie_list.php">
                                <i class="fas fa-film"></i> Master List of Movies
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="membership_list.php">
                                <i class="fas fa-id-card"></i> Master List of Memberships
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="active_issues.php">
                                <i class="fas fa-clipboard-list"></i> Active Issues
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="overdue_returns.php">
                                <i class="fas fa-clock"></i> Overdue Returns
                            </a>
                        </li>
                        
                        <?php if (isAdmin()): ?>
                        <!-- Maintenance Menu (Admin Only) -->
                        <li class="nav-item">
                            <a class="nav-link" href="maintenance.php">
                                <i class="fas fa-tools"></i> Maintenance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_membership.php">
                                <i class="fas fa-plus-circle"></i> Add Membership
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="update_membership.php">
                                <i class="fas fa-edit"></i> Update Membership
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="add_book.php">
                                <i class="fas fa-plus"></i> Add Book/Movie
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="update_book.php">
                                <i class="fas fa-edit"></i> Update Book/Movie
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="user_management.php">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="../chart.php">
                                <i class="fas fa-sitemap"></i> System Chart
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary d-md-none" id="sidebarToggle">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user"></i> <?php echo $username; ?>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="../profile.php">Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>