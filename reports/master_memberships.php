<?php
// Set page title
$pageTitle = "Master List of Memberships";

// Include header
require_once '../includes/header.php';

// Initialize variables
$filterStatus = isset($_GET['filter_status']) ? sanitizeInput($_GET['filter_status']) : 'all';
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query based on filters
$sql = "SELECT * FROM memberships WHERE 1=1";
$params = [];
$types = "";

if ($filterStatus != 'all') {
    if ($filterStatus == 'active') {
        $sql .= " AND end_date >= CURDATE()";
    } else {
        $sql .= " AND end_date < CURDATE()";
    }
}

if (!empty($searchTerm)) {
    $sql .= " AND (name LIKE ? OR email LIKE ? OR membership_id LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

$sql .= " ORDER BY name";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$members = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $members[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Master List of Memberships</h5>
                    <div>
                        <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-sm btn-light">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filterForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="filter_status" class="form-label">Filter by Status</label>
                                <select class="form-select" id="filter_status" name="filter_status" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($filterStatus == 'all') ? 'selected' : ''; ?>>All Memberships</option>
                                    <option value="active" <?php echo ($filterStatus == 'active') ? 'selected' : ''; ?>>Active Memberships</option>
                                    <option value="expired" <?php echo ($filterStatus == 'expired') ? 'selected' : ''; ?>>Expired Memberships</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="search" class="form-label">Search</label>
                                <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search by name, email or ID">
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                                <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Membership ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <?php if (isAdmin()): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '8' : '7'; ?>" class="text-center">No memberships found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['membership_id']); ?></td>
                                    <td><?php echo htmlspecialchars($member['name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($member['start_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($member['end_date'])); ?></td>
                                    <td>
                                        <?php if (strtotime($member['end_date']) >= strtotime(date('Y-m-d'))): ?>
                                        <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Expired</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                    <td>
                                        <a href="../admin/update_membership.php?id=<?php echo $member['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Total Members:</strong> <?php echo count($members); ?></p>
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