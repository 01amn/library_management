<?php
// Set page title
$pageTitle = "User Dashboard";

// Include header
require_once '../includes/header.php';

// Get user's active memberships
$sql = "SELECT * FROM memberships WHERE user_id = ? AND status = 'active'";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$membershipResult = $stmt->get_result();
$hasMembership = ($membershipResult && $membershipResult->num_rows > 0);

// Get user's active transactions
$sql = "SELECT t.*, b.title, b.author 
        FROM transactions t 
        JOIN books b ON t.book_id = b.book_id 
        WHERE t.user_id = ? AND t.status = 'issued' 
        ORDER BY t.issue_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$activeTransactions = $stmt->get_result();
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Welcome, <?php echo $username; ?>!</h5>
                </div>
                <div class="card-body">
                    <?php if ($hasMembership): ?>
                        <?php $membership = $membershipResult->fetch_assoc(); ?>
                        <p><strong>Membership Status:</strong> <span class="badge bg-success">Active</span></p>
                        <p><strong>Membership Type:</strong> <?php echo $membership['membership_type']; ?></p>
                        <p><strong>Valid Until:</strong> <?php echo $membership['end_date']; ?></p>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <p>You don't have an active membership. Please contact the library administrator.</p>
                        </div>
                    <?php endif; ?>
                    <div class="mt-3">
                        <a href="book_availability.php" class="btn btn-primary">Check Book Availability</a>
                        <a href="issue_book.php" class="btn btn-success">Issue a Book</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="book_availability.php" class="btn btn-outline-primary">Check Book Availability</a>
                        <a href="issue_book.php" class="btn btn-outline-success">Issue a Book</a>
                        <a href="return_book.php" class="btn btn-outline-warning">Return a Book</a>
                        <a href="book_list.php" class="btn btn-outline-info">View Book Catalog</a>
                        <a href="../reports/index.php" class="btn btn-outline-secondary">View Reports</a>
                        <a href="../chart.php" class="btn btn-outline-dark">View System Chart</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Your Current Books</h5>
                </div>
                <div class="card-body">
                    <?php if ($activeTransactions && $activeTransactions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Author</th>
                                        <th>Issue Date</th>
                                        <th>Return Date</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($transaction = $activeTransactions->fetch_assoc()): ?>
                                        <?php 
                                        $today = new DateTime();
                                        $returnDate = new DateTime($transaction['return_date']);
                                        $isOverdue = $returnDate < $today;
                                        ?>
                                        <tr>
                                            <td><?php echo $transaction['title']; ?></td>
                                            <td><?php echo $transaction['author']; ?></td>
                                            <td><?php echo $transaction['issue_date']; ?></td>
                                            <td><?php echo $transaction['return_date']; ?></td>
                                            <td>
                                                <?php if ($isOverdue): ?>
                                                    <span class="badge bg-danger">Overdue</span>
                                                <?php else: ?>
                                                    <span class="badge bg-primary">Issued</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="return_book.php?id=<?php echo $transaction['transaction_id']; ?>" class="btn btn-sm btn-warning">Return</a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <p>You don't have any books currently issued.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>