<?php
require_once '../config.php';
if (!isLoggedIn()) { redirect('../index.php'); }
if (!isAdmin()) { redirect('../user/dashboard.php'); }

// Set page title and then include header
$pageTitle = "Add Book/Movie";
require_once '../includes/header.php';

// Initialize variables
$title = $author = $isbn = $publicationYear = $category = $itemType = $totalCopies = "";
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $title = sanitizeInput($_POST["title"]);
    $author = sanitizeInput($_POST["author"]);
    $isbn = sanitizeInput($_POST["isbn"]);
    $publicationYear = sanitizeInput($_POST["publication_year"]);
    $category = sanitizeInput($_POST["category"]);
    $itemType = sanitizeInput($_POST["item_type"]);
    $totalCopies = sanitizeInput($_POST["total_copies"]);
    
    // Validate required fields
    $requiredFields = [
        'title' => $title,
        'author' => $author,
        'category' => $category,
        'item_type' => $itemType,
        'total_copies' => $totalCopies
    ];
    
    $errors = validateRequired($requiredFields);
    
    // Validate ISBN if provided
    if (!empty($isbn) && !validateISBN($isbn)) {
        $errors[] = "Invalid ISBN/Serial Number format.";
    }
    
    // Validate publication year if provided
    if (!empty($publicationYear) && !validateYear($publicationYear)) {
        $errors[] = "Invalid publication year. Year must be between 1000 and " . (date('Y') + 5) . ".";
    }
    
    // Validate total copies
    if (!empty($totalCopies) && !validatePositiveInteger($totalCopies)) {
        $errors[] = "Total copies must be a positive integer.";
    }
    
    // If no errors, insert the book/movie
    if (empty($errors)) {
        // Insert new book/movie
        $sql = "INSERT INTO books (title, author, isbn, publication_year, category, item_type, total_copies, available_copies) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssisiii", $title, $author, $isbn, $publicationYear, $category, $itemType, $totalCopies, $totalCopies);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item added successfully.";
            // Reset form
            $title = $author = $isbn = $publicationYear = $category = $itemType = $totalCopies = "";
        } else {
            $errors[] = "Error adding item: " . $conn->error;
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Book/Movie</h5>
                </div>
                <div class="card-body">
                    <?php 
                    // Display errors if any
                    if (!empty($errors)) {
                        echo displayErrors($errors);
                    }
                    
                    // Display success message if any
                    if (isset($_SESSION['success_message'])) {
                        echo displaySuccess($_SESSION['success_message']);
                        unset($_SESSION['success_message']);
                    }
                    ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="itemForm">
                        <div class="mb-3">
                            <label class="form-label">Item Type *</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="item_type" id="book" value="book" <?php echo ($itemType == "book" || empty($itemType)) ? 'checked' : ''; ?> required>
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
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="author" class="form-label">Author/Director *</label>
                            <input type="text" class="form-control" id="author" name="author" value="<?php echo $author; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="isbn" class="form-label">ISBN/Serial Number</label>
                            <input type="text" class="form-control" id="isbn" name="isbn" value="<?php echo $isbn; ?>">
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
                            <input type="number" class="form-control" id="total_copies" name="total_copies" value="<?php echo empty($totalCopies) ? '1' : $totalCopies; ?>" min="1" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add Item</button>
                            <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../includes/footer.php';
?>