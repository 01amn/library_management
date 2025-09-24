<?php
// Set page title
$pageTitle = "Reports";

// Include header
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Reports</h5>
                    <div>
                        <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-sm btn-light">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <!-- Master List of Books -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Master List of Books</h5>
                                    <p class="card-text">View complete list of all books and movies in the library with filtering options.</p>
                                </div>
                                <div class="card-footer">
                                    <a href="master_books.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Master List of Memberships -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Master List of Memberships</h5>
                                    <p class="card-text">View all memberships with status information and filtering options.</p>
                                </div>
                                <div class="card-footer">
                                    <a href="master_memberships.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Active Issues -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Active Issues</h5>
                                    <p class="card-text">View all currently active book and movie issues with status information.</p>
                                </div>
                                <div class="card-footer">
                                    <a href="active_issues.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Overdue Returns -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Overdue Returns</h5>
                                    <p class="card-text">View all overdue book and movie returns with fine calculation.</p>
                                </div>
                                <div class="card-footer">
                                    <a href="overdue_returns.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isAdmin()): ?>
                        <!-- Pending Issue Requests (Admin Only) -->
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Pending Issue Requests</h5>
                                    <p class="card-text">View and manage pending book and movie issue requests from users.</p>
                                </div>
                                <div class="card-footer">
                                    <a href="pending_issues.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>