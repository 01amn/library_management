<?php
// Set page title
$pageTitle = "Active Issues";

// Include header
require_once '../includes/header.php';

// Initialize variables
$userId = '';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// For regular users, only show their own issues
if (!isAdmin()) {
    $userId = $_SESSION['user_id'];
}

// Build query based on filters
$sql = "SELECT t.*, b.title, b.author, b.item_type, u.full_name as member_name, u.user_id as membership_id 
        FROM transactions t 
        JOIN books b ON t.book_id = b.book_id 
        JOIN users u ON t.user_id = u.user_id 
        WHERE t.status = 'issued'";
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

$sql .= " ORDER BY t.return_date ASC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$issues = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $issues[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Active Issues</h5>
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
                                    <?php endif; ?>
                                    <th>Issue Date</th>
                                    <th>Expected Return</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($issues)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '9' : '7'; ?>" class="text-center">No active issues found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($issues as $issue): ?>
                                <tr <?php echo (strtotime($issue['expected_return_date']) < strtotime(date('Y-m-d'))) ? 'class="table-danger"' : ''; ?>>
                                    <td><?php echo htmlspecialchars($issue['title']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['author']); ?></td>
                                    <td><?php echo ucfirst($issue['item_type']); ?></td>
                                    <?php if (isAdmin()): ?>
                                    <td><?php echo htmlspecialchars($issue['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($issue['membership_id']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('M d, Y', strtotime($issue['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($issue['expected_return_date'])); ?></td>
                                    <td>
                                        <?php if (strtotime($issue['expected_return_date']) < strtotime(date('Y-m-d'))): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                        <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php endif; ?>
                                    </td>
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
                        <p><strong>Total Active Issues:</strong> <?php echo count($issues); ?></p>
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