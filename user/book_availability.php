<?php
// Set page title
$pageTitle = "Check Book Availability";

// Include header (handles auth)
require_once '../includes/header.php';

// Initialize vars
$searchTerm = isset($_POST['search_term']) ? sanitizeInput($_POST['search_term']) : '';
$searchType = isset($_POST['search_type']) ? sanitizeInput($_POST['search_type']) : 'all';
$results = [];
$message = '';

if ($_SERVER["REQUEST_METHOD"] === 'POST') {
    if (empty($searchTerm)) {
        $message = showError('Please enter a search term.');
    } else {
        // Build query
        $sql = "SELECT * FROM books WHERE ";
        $param = "%$searchTerm%";
        if ($searchType === 'title') { $sql .= "title LIKE ?"; }
        elseif ($searchType === 'author') { $sql .= "author LIKE ?"; }
        elseif ($searchType === 'category') { $sql .= "category LIKE ?"; }
        else { $sql .= "(title LIKE ? OR author LIKE ? OR category LIKE ?)"; }
        $sql .= " ORDER BY title";

        if ($searchType === 'all') {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('sss', $param, $param, $param);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $param);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $results[] = $row; }
        if (empty($results)) { $message = showInfo('No books/movies matched your search.'); }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Search Books/Movies</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Search Term</label>
                                <input class="form-control" name="search_term" value="<?php echo htmlspecialchars($searchTerm); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Search By</label>
                                <select class="form-select" name="search_type">
                                    <option value="all" <?php echo $searchType==='all'?'selected':''; ?>>All</option>
                                    <option value="title" <?php echo $searchType==='title'?'selected':''; ?>>Title</option>
                                    <option value="author" <?php echo $searchType==='author'?'selected':''; ?>>Author/Director</option>
                                    <option value="category" <?php echo $searchType==='category'?'selected':''; ?>>Category</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary w-100">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($message) || !empty($results)): ?>
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Search Results</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <?php if (!empty($results)): ?>
                    <form method="post" action="../transactions/issue_book.php">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Author/Director</th>
                                        <th>Type</th>
                                        <th>Category</th>
                                        <th>Available</th>
                                        <th>Select</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($results as $b): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($b['title']); ?></td>
                                        <td><?php echo htmlspecialchars($b['author']); ?></td>
                                        <td><?php echo ucfirst($b['item_type']); ?></td>
                                        <td><?php echo htmlspecialchars($b['category']); ?></td>
                                        <td>
                                            <?php if ((int)$b['available_copies'] > 0): ?>
                                                <span class="badge bg-success"><?php echo (int)$b['available_copies']; ?> Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <input class="form-check-input" type="radio" name="book_id" value="<?php echo (int)$b['book_id']; ?>" <?php echo ((int)$b['available_copies']<=0)?'disabled':''; ?> />
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-grid gap-2 col-md-4 mx-auto mt-2">
                            <button type="submit" class="btn btn-primary">Issue Selected</button>
                            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>


