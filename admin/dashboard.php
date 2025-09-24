<?php
// Set page title
$pageTitle = "Admin Dashboard";

// Include header
require_once '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../user/dashboard.php');
}

// Get dashboard statistics
$totalBooks = 0;
$totalMembers = 0;
$activeIssues = 0;
$overdueReturns = 0;

// Get total books
$sql = "SELECT COUNT(*) as total FROM books";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalBooks = $row['total'];
}

// Get total members
$sql = "SELECT COUNT(*) as total FROM memberships WHERE status = 'active'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalMembers = $row['total'];
}

// Get active issues
$sql = "SELECT COUNT(*) as total FROM transactions WHERE status = 'issued'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $activeIssues = $row['total'];
}

// Get overdue returns
$today = date('Y-m-d');
$sql = "SELECT COUNT(*) as total FROM transactions WHERE status = 'issued' AND return_date < '$today'";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $overdueReturns = $row['total'];
}

// Get recent transactions
$sql = "SELECT t.*, u.full_name, b.title, b.author 
        FROM transactions t 
        JOIN users u ON t.user_id = u.user_id 
        JOIN books b ON t.book_id = b.book_id 
        ORDER BY t.transaction_id DESC LIMIT 5";
$recentTransactions = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">Total Books</h5>
                            <h2 class="card-text"><?php echo $totalBooks; ?></h2>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-book icon text-primary"></i>
                        </div>
                    </div>
                    <a href="book_list.php" class="btn btn-sm btn-outline-primary mt-3">View Details</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card members">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">Active Members</h5>
                            <h2 class="card-text"><?php echo $totalMembers; ?></h2>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-users icon text-warning"></i>
                        </div>
                    </div>
                    <a href="membership_list.php" class="btn btn-sm btn-outline-warning mt-3">View Details</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card transactions">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">Active Issues</h5>
                            <h2 class="card-text"><?php echo $activeIssues; ?></h2>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-exchange-alt icon text-danger"></i>
                        </div>
                    </div>
                    <a href="active_issues.php" class="btn btn-sm btn-outline-danger mt-3">View Details</a>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-4">
            <div class="card dashboard-card books">
                <div class="card-body">
                    <div class="row">
                        <div class="col-8">
                            <h5 class="card-title">Overdue Returns</h5>
                            <h2 class="card-text"><?php echo $overdueReturns; ?></h2>
                        </div>
                        <div class="col-4 text-end">
                            <i class="fas fa-clock icon text-success"></i>
                        </div>
                    </div>
                    <a href="overdue_returns.php" class="btn btn-sm btn-outline-success mt-3">View Details</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5>Recent Transactions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Member</th>
                                    <th>Book</th>
                                    <th>Issue Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentTransactions && $recentTransactions->num_rows > 0): ?>
                                    <?php while ($transaction = $recentTransactions->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $transaction['transaction_id']; ?></td>
                                            <td><?php echo $transaction['full_name']; ?></td>
                                            <td><?php echo $transaction['title']; ?> (<?php echo $transaction['author']; ?>)</td>
                                            <td><?php echo $transaction['issue_date']; ?></td>
                                            <td><?php echo $transaction['return_date']; ?></td>
                                            <td>
                                                <?php if ($transaction['status'] == 'issued'): ?>
                                                    <span class="badge bg-primary">Issued</span>
                                                <?php elseif ($transaction['status'] == 'returned'): ?>
                                                    <span class="badge bg-success">Returned</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No recent transactions</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="add_book.php" class="btn btn-primary">Add New Book/Movie</a>
                        <a href="add_membership.php" class="btn btn-success">Add New Membership</a>
                        <a href="user_management.php" class="btn btn-warning">Manage Users</a>
                        <a href="book_availability.php" class="btn btn-info">Check Book Availability</a>
                        <a href="../reports/index.php" class="btn btn-secondary">View Reports</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>System Information</h5>
                </div>
                <div class="card-body">
                    <p><strong>User Type:</strong> Admin</p>
                    <p><strong>Username:</strong> <?php echo $username; ?></p>
                    <p><strong>Last Login:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                    <p>
                        <strong>Access Level:</strong> 
                        <span class="badge bg-success">Maintenance</span>
                        <span class="badge bg-success">Reports</span>
                        <span class="badge bg-success">Transactions</span>
                    </p>
                    <p class="mb-0">
                        <a href="../chart.php" class="btn btn-sm btn-outline-secondary">View System Chart</a>
                        <a href="../logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>