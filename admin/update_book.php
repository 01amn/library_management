<?php
// Set page title
$pageTitle = "Update Book/Movie";

// Include header
require_once '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../user/dashboard.php');
}

// Initialize variables
$bookId = $title = $author = $isbn = $publicationYear = $category = $itemType = $totalCopies = $message = "";
$error = false;
$book = null;

// Check if book ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $bookId = sanitizeInput($_GET['id']);
    
    // Get book details
    $sql = "SELECT * FROM books WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        $title = $book['title'];
        $author = $book['author'];
        $isbn = $book['isbn'];
        $publicationYear = $book['publication_year'];
        $category = $book['category'];
        $itemType = $book['item_type'];
        $totalCopies = $book['total_copies'];
    } else {
        $error = true;
        $message = showError("Book/Movie not found.");
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST") {
    // No book ID provided and not a form submission
    $message = showError("Please select a book/movie to update.");
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $bookId = sanitizeInput($_POST["book_id"]);
    $title = sanitizeInput($_POST["title"]);
    $author = sanitizeInput($_POST["author"]);
    $isbn = sanitizeInput($_POST["isbn"]);
    $publicationYear = sanitizeInput($_POST["publication_year"]);
    $category = sanitizeInput($_POST["category"]);
    $itemType = sanitizeInput($_POST["item_type"]);
    $totalCopies = sanitizeInput($_POST["total_copies"]);
    
    // Validate input
    if (empty($title) || empty($author) || empty($category) || empty($itemType) || empty($totalCopies)) {
        $error = true;
        $message = showError("All fields marked with * are required.");
    } else {
        // Get current available copies
        $sql = "SELECT total_copies, available_copies FROM books WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        $currentBook = $result->fetch_assoc();
        
        // Calculate new available copies
        $copiesDifference = $totalCopies - $currentBook['total_copies'];
        $newAvailableCopies = $currentBook['available_copies'] + $copiesDifference;
        
        // Ensure available copies is not negative
        if ($newAvailableCopies < 0) {
            $newAvailableCopies = 0;
        }
        
        // Update book/movie
        $sql = "UPDATE books SET title = ?, author = ?, isbn = ?, publication_year = ?, 
                category = ?, item_type = ?, total_copies = ?, available_copies = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisiiii", $title, $author, $isbn, $publicationYear, $category, $itemType, $totalCopies, $newAvailableCopies, $bookId);
        
        if ($stmt->execute()) {
            $message = showSuccess("Item updated successfully.");
            
            // Get updated book details
            $sql = "SELECT * FROM books WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            $book = $result->fetch_assoc();
        } else {
            $error = true;
            $message = showError("Error updating item: " . $conn->error);
        }
    }
}

// Get all books for selection
$sql = "SELECT id, title, author, item_type FROM books ORDER BY title";
$result = $conn->query($sql);
$books = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $books[] = $row;
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Book/Movie</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <?php if (empty($bookId) || $error): ?>
                    <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="selectBookForm">
                        <div class="mb-3">
                            <label for="id" class="form-label">Select Book/Movie to Update *</label>
                            <select class="form-select" id="id" name="id" required>
                                <option value="">-- Select Book/Movie --</option>
                                <?php foreach ($books as $item): ?>
                                <option value="<?php echo $item['id']; ?>">
                                    <?php echo htmlspecialchars($item['title']) . ' by ' . htmlspecialchars($item['author']) . ' (' . ucfirst($item['item_type']) . ')'; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Select</button>
                            <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                    <?php else: ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="updateBookForm">
                        <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Item Type *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="item_type" id="book" value="book" <?php echo ($itemType == "book") ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="book">
                                    Book
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="item_type" id="movie" value="movie" <?php echo ($itemType == "movie") ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="movie">
                                    Movie
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title *</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author/Director *</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo htmlspecialchars($author); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="isbn" class="form-label">ISBN/Serial Number</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo htmlspecialchars($isbn); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="publication_year" class="form-label">Publication/Release Year</label>
                            <input type="number" class="form-control" id="publication_year" name="publication_year" value="<?php echo $publicationYear; ?>" min="1800" max="<?php echo date('Y'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category" class="form-label">Category *</label>
                            <select class="form-select" id="category" name="category" required>
                                <option value="">-- Select Category --</option>
                                <option value="Fiction" <?php echo ($category == "Fiction") ? 'selected' : ''; ?>>Fiction</option>
                                <option value="Non-Fiction" <?php echo ($category == "Non-Fiction") ? 'selected' : ''; ?>>Non-Fiction</option>
                                <option value="Science Fiction" <?php echo ($category == "Science Fiction") ? 'selected' : ''; ?>>Science Fiction</option>
                                <option value="Fantasy" <?php echo ($category == "Fantasy") ? 'selected' : ''; ?>>Fantasy</option>
                                <option value="Mystery" <?php echo ($category == "Mystery") ? 'selected' : ''; ?>>Mystery</option>
                                <option value="Romance" <?php echo ($category == "Romance") ? 'selected' : ''; ?>>Romance</option>
                                <option value="Thriller" <?php echo ($category == "Thriller") ? 'selected' : ''; ?>>Thriller</option>
                                <option value="Horror" <?php echo ($category == "Horror") ? 'selected' : ''; ?>>Horror</option>
                                <option value="Biography" <?php echo ($category == "Biography") ? 'selected' : ''; ?>>Biography</option>
                                <option value="History" <?php echo ($category == "History") ? 'selected' : ''; ?>>History</option>
                                <option value="Science" <?php echo ($category == "Science") ? 'selected' : ''; ?>>Science</option>
                                <option value="Self-Help" <?php echo ($category == "Self-Help") ? 'selected' : ''; ?>>Self-Help</option>
                                <option value="Drama" <?php echo ($category == "Drama") ? 'selected' : ''; ?>>Drama</option>
                                <option value="Comedy" <?php echo ($category == "Comedy") ? 'selected' : ''; ?>>Comedy</option>
                                <option value="Action" <?php echo ($category == "Action") ? 'selected' : ''; ?>>Action</option>
                                <option value="Documentary" <?php echo ($category == "Documentary") ? 'selected' : ''; ?>>Documentary</option>
                                <option value="Other" <?php echo ($category == "Other") ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="total_copies" class="form-label">Total Copies *</label>
                            <input type="number" class="form-control" id="total_copies" name="total_copies" value="<?php echo $totalCopies; ?>" min="1" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Item</button>
                            <a href="update_book.php" class="btn btn-secondary">Select Different Item</a>
                            <a href="maintenance.php" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
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