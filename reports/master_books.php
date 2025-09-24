<?php
// Set page title
$pageTitle = "Master List of Books";

// Include header
require_once '../includes/header.php';

// Initialize variables
$filterType = isset($_GET['filter_type']) ? sanitizeInput($_GET['filter_type']) : 'all';
$filterCategory = isset($_GET['filter_category']) ? sanitizeInput($_GET['filter_category']) : '';

// Get all categories for filter
$sql = "SELECT DISTINCT category FROM books ORDER BY category";
$result = $conn->query($sql);
$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row['category'];
    }
}

// Build query based on filters
$sql = "SELECT * FROM books WHERE 1=1";
$params = [];
$types = "";

if ($filterType != 'all') {
    $sql .= " AND item_type = ?";
    $params[] = $filterType;
    $types .= "s";
}

if (!empty($filterCategory)) {
    $sql .= " AND category = ?";
    $params[] = $filterCategory;
    $types .= "s";
}

$sql .= " ORDER BY title";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$books = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Master List of Books/Movies</h5>
                    <div>
                        <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-sm btn-light">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="filterForm" class="mb-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="filter_type" class="form-label">Filter by Type</label>
                                <select class="form-select" id="filter_type" name="filter_type" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($filterType == 'all') ? 'selected' : ''; ?>>All Types</option>
                                    <option value="book" <?php echo ($filterType == 'book') ? 'selected' : ''; ?>>Books Only</option>
                                    <option value="movie" <?php echo ($filterType == 'movie') ? 'selected' : ''; ?>>Movies Only</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="filter_category" class="form-label">Filter by Category</label>
                                <select class="form-select" id="filter_category" name="filter_category" onchange="this.form.submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category; ?>" <?php echo ($filterCategory == $category) ? 'selected' : ''; ?>>
                                        <?php echo $category; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
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
                                    <th>Title</th>
                                    <th>Author/Director</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>ISBN/Serial</th>
                                    <th>Publication Year</th>
                                    <th>Total Copies</th>
                                    <th>Available</th>
                                    <?php if (isAdmin()): ?>
                                    <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($books)): ?>
                                <tr>
                                    <td colspan="<?php echo isAdmin() ? '9' : '8'; ?>" class="text-center">No books/movies found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($books as $book): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($book['title']); ?></td>
                                    <td><?php echo htmlspecialchars($book['author']); ?></td>
                                    <td><?php echo ucfirst($book['item_type']); ?></td>
                                    <td><?php echo htmlspecialchars($book['category']); ?></td>
                                    <td><?php echo htmlspecialchars($book['isbn']); ?></td>
                                    <td><?php echo $book['publication_year']; ?></td>
                                    <td><?php echo $book['total_copies']; ?></td>
                                    <td>
                                        <?php if ($book['available_copies'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $book['available_copies']; ?> Available</span>
                                        <?php else: ?>
                                        <span class="badge bg-danger">Not Available</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php if (isAdmin()): ?>
                                    <td>
                                        <a href="../admin/update_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Total Items:</strong> <?php echo count($books); ?></p>
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