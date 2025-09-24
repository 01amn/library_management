<?php
// Set page title
$pageTitle = "Check Book Availability";

// Include header
require_once '../includes/header.php';

// Initialize variables
$searchTerm = $searchType = "";
$searchResults = [];
$message = "";

// Process search form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $searchTerm = sanitizeInput($_POST["search_term"]);
    $searchType = sanitizeInput($_POST["search_type"]);
    
    if (empty($searchTerm)) {
        $message = showError("Please enter a search term.");
    } else {
        // Build query based on search type
        $sql = "SELECT * FROM books WHERE ";
        
        if ($searchType == "title") {
            $sql .= "title LIKE ?";
            $searchParam = "%$searchTerm%";
        } elseif ($searchType == "author") {
            $sql .= "author LIKE ?";
            $searchParam = "%$searchTerm%";
        } elseif ($searchType == "category") {
            $sql .= "category LIKE ?";
            $searchParam = "%$searchTerm%";
        } else {
            $sql .= "(title LIKE ? OR author LIKE ? OR category LIKE ?)";
            $searchParam = "%$searchTerm%";
        }
        
        $sql .= " ORDER BY title";
        
        // Execute query
        if ($searchType == "all") {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
        } else {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $searchParam);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $searchResults[] = $row;
            }
        } else {
            $message = showInfo("No books/movies found matching your search criteria.");
        }
    }
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Check Book/Movie Availability</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="searchForm">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="search_term" class="form-label">Search Term</label>
                                <input type="text" class="form-control" id="search_term" name="search_term" value="<?php echo $searchTerm; ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="search_type" class="form-label">Search By</label>
                                <select class="form-select" id="search_type" name="search_type">
                                    <option value="all" <?php echo ($searchType == "all" || empty($searchType)) ? 'selected' : ''; ?>>All Fields</option>
                                    <option value="title" <?php echo ($searchType == "title") ? 'selected' : ''; ?>>Title</option>
                                    <option value="author" <?php echo ($searchType == "author") ? 'selected' : ''; ?>>Author/Director</option>
                                    <option value="category" <?php echo ($searchType == "category") ? 'selected' : ''; ?>>Category</option>
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
    
    <?php if (!empty($searchResults) || !empty($message)): ?>
    <div class="row">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Search Results</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <?php if (!empty($searchResults)): ?>
                    <form method="post" action="issue_book.php" id="selectBookForm">
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
                                    <?php foreach ($searchResults as $book): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td><?php echo htmlspecialchars($book['author']); ?></td>
                                        <td><?php echo ucfirst($book['item_type']); ?></td>
                                        <td><?php echo htmlspecialchars($book['category']); ?></td>
                                        <td>
                                            <?php if ($book['available_copies'] > 0): ?>
                                                <span class="badge bg-success"><?php echo $book['available_copies']; ?> Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Not Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="book_id" id="book_<?php echo $book['book_id']; ?>" value="<?php echo $book['book_id']; ?>" <?php echo ($book['available_copies'] <= 0) ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="book_<?php echo $book['book_id']; ?>">
                                                    Select
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-grid gap-2 col-md-4 mx-auto mt-3">
                            <button type="submit" class="btn btn-primary">Issue Selected Book/Movie</button>
                            <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-secondary">Back to Dashboard</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>