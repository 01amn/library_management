<?php
// Set page title
$pageTitle = "Overdue Returns";

// Include header
require_once '../includes/header.php';

// Initialize variables
$userId = '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// For regular users, only show their own issues
if (!isAdmin()) {
    $userId = $_SESSION['user_id'];
}

// Build query for overdue issues
$sql = "SELECT t.*, b.title, b.author, b.item_type, u.full_name as member_name, u.user_id as membership_id, u.email, u.phone,
        DATEDIFF(CURRENT_DATE, t.return_date) as days_overdue,
        GREATEST(DATEDIFF(CURRENT_DATE, t.return_date),0) * 5 as fine_amount
        FROM transactions t 
        JOIN books b ON t.book_id = b.book_id 
        JOIN users u ON t.user_id = u.user_id 
        WHERE t.status = 'issued' AND t.return_date < CURRENT_DATE";
$params = [];
$types = "";

if (!empty($userId)) {
    $sql .= " AND i.user_id = ?";
    $params[] = $userId;
    $types .= "i";
}

if (!empty($searchTerm)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR m.name LIKE ? OR m.membership_id LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

$sql .= " ORDER BY days_overdue DESC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$overdueIssues = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $overdueIssues[] = $row;
    }
}

// Calculate total fine amount
$totalFine = 0;
foreach ($overdueIssues as $issue) {
    $totalFine += $issue['fine_amount'];
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Overdue Returns</h5>
                    <div>
                        <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-sm btn-light">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filterForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by title, author, member name or ID">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Search</button>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author/Director</th>
                                    <th>Type</th>
                                    <?php if (isAdmin()): ?>
                                    <th>Member</th>
                                    <th>Membership ID</th>
                                    <th>Contact</th>
                                    <?php endif; ?>
                                    <th>Issue Date</th>
                                    <th>Expected Return</th>
                                    <th>Days Overdue</th>
                                    <th>Fine Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($overdueIssues)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '11' : '8'; ?>" class="text-center">No overdue returns found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($overdueIssues as $issue): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($issue['title']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['author']); ?></td>
                                    <td><?php echo ucfirst($issue['item_type']); ?></td>
                                    <?php if (isAdmin()): ?>
                                    <td><?php echo htmlspecialchars($issue['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['membership_id']); ?></td>
                                    <td>
                                        <small>Email: <?php echo htmlspecialchars($issue['email']); ?><br>
                                        Phone: <?php echo htmlspecialchars($issue['phone']); ?></small>
                                    </td>
                                    <?php endif; ?>
                                    <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['expected_return_date'])); ?></td>
                                    <td><span class="badge bg-danger"><?php echo $issue['days_overdue']; ?> days</span></td>
                                    <td>$<?php echo number_format($issue['fine_amount'], 2); ?></td>
                                    <td>
                                        <a href="../transactions/return_book.php?id=<?php echo $issue['id']; ?>" class="btn btn-sm btn-primary">Return</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Total Overdue Items:</strong> <?php echo count($overdueIssues); ?></p>
                        <p><strong>Total Fine Amount:</strong> $<?php echo number_format($totalFine, 2); ?></p>
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