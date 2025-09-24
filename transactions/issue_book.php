<?php
// Gate before output to avoid header already sent
require_once '../config.php';
if (!isLoggedIn()) { redirect('../index.php'); }

// Set page title and then include header
$pageTitle = "Issue Book";
require_once '../includes/header.php';

// Initialize variables
$bookId = $userId = $issueDate = $returnDate = $remarks = "";
$book = null;
$errors = [];

// Check if book ID is provided
if (isset($_POST['book_id']) && !empty($_POST['book_id'])) {
    $bookId = sanitizeInput($_POST['book_id']);
    
    // Get book details
    $sql = "SELECT * FROM books WHERE book_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $bookId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $book = $result->fetch_assoc();
        
        // Check if book is available
        if ($book['available_copies'] <= 0) {
            $errors[] = "This book/movie is not available for issue.";
        }
    } else {
        $errors[] = "Book/Movie not found.";
    }
} else if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['submit'])) {
    // No book ID provided and not a form submission
    redirect('check_availability.php');
}

// Set default dates
$today = date('Y-m-d');
$issueDate = $today;
$returnDate = date('Y-m-d', strtotime('+15 days'));

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Get form data
    $bookId = sanitizeInput($_POST["book_id"]);
    $userId = sanitizeInput($_POST["user_id"]);
    $issueDate = sanitizeInput($_POST["issue_date"]);
    $returnDate = sanitizeInput($_POST["return_date"]);
    $remarks = sanitizeInput($_POST["remarks"]);
    
    // Validate input
    $requiredFields = [
        'book_id' => $bookId,
        'user_id' => $userId,
        'issue_date' => $issueDate,
        'return_date' => $returnDate
    ];
    
    $errors = validateRequired($requiredFields);
    
    // Validate dates
    if (!validateFutureDate($issueDate, $today)) {
        $errors[] = "Issue date cannot be earlier than today.";
    }
    
    if (!empty($issueDate) && !empty($returnDate) && strtotime($returnDate) < strtotime($issueDate)) {
        $errors[] = "Return date cannot be earlier than issue date.";
    }
    
    if (!empty($issueDate) && !empty($returnDate) && strtotime($returnDate) > strtotime('+15 days', strtotime($issueDate))) {
        $errors[] = "Return date cannot be more than 15 days from issue date.";
    }
    
    if (empty($errors)) {
        // Check if user has active membership
        $sql = "SELECT * FROM memberships WHERE user_id = ? AND end_date >= CURDATE() AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $errors[] = "User does not have an active membership.";
        } else {
            // Check if book is available
            $sql = "SELECT * FROM books WHERE book_id = ? AND available_copies > 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $bookId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows == 0) {
                $errors[] = "This book/movie is not available for issue.";
            } else {
                $book = $result->fetch_assoc();
                
                // Check if user has already issued this book
                $sql = "SELECT * FROM transactions WHERE book_id = ? AND user_id = ? AND status = 'issued'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $bookId, $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $errors[] = "User has already issued this book/movie.";
                } else {
                    // Insert transaction
                    $sql = "INSERT INTO transactions (book_id, user_id, issue_date, return_date, remarks, status) 
                            VALUES (?, ?, ?, ?, ?, 'issued')";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisss", $bookId, $userId, $issueDate, $returnDate, $remarks);
                    
                    if ($stmt->execute()) {
                        // Update available copies
                        $sql = "UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $bookId);
                        
                        if ($stmt->execute()) {
                            $_SESSION['success_message'] = "Book/Movie issued successfully.";
                            $message = showSuccess("Book/Movie issued successfully.");
                            
                            // Reset form
                            $userId = $remarks = "";
                            $issueDate = $today;
                            $returnDate = date('Y-m-d', strtotime('+15 days'));
                            
                            // Redirect to dashboard after 2 seconds
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = '" . (isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php') . "';
                                }, 2000);
                            </script>";
                        } else {
                            $error = true;
                            $message = showError("Error updating book availability: " . $conn->error);
                        }
                    } else {
                        $error = true;
                        $message = showError("Error issuing book: " . $conn->error);
                    }
                }
            }
        }
    }
    
    // If there was an error, get book details again
    if ($error) {
        $sql = "SELECT * FROM books WHERE book_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $bookId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $book = $result->fetch_assoc();
        }
    }
}

// Get all users for selection (only regular users)
$sql = "SELECT u.user_id, u.full_name, u.email, m.end_date 
        FROM users u 
        LEFT JOIN memberships m ON u.user_id = m.user_id AND m.status = 'active' AND m.end_date >= CURDATE()
        WHERE u.user_type = 'user'
        ORDER BY u.full_name";
$result = $conn->query($sql);
$users = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) { $users[] = $row; }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Issue Book/Movie</h5>
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
                    
                    <?php if ($book && empty($errors)): ?>
                    <div class="alert alert-info">
                        <h6>Selected Item Details:</h6>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($book['title']); ?><br>
                        <strong>Author/Director:</strong> <?php echo htmlspecialchars($book['author']); ?><br>
                        <strong>Type:</strong> <?php echo ucfirst($book['item_type']); ?><br>
                        <strong>Category:</strong> <?php echo htmlspecialchars($book['category']); ?><br>
                        <strong>Available Copies:</strong> <?php echo $book['available_copies']; ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($book && $book['available_copies'] > 0): ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="issueForm">
                        <input type="hidden" name="book_id" value="<?php echo $bookId; ?>">
                        
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select User *</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo ($userId == $user['user_id']) ? 'selected' : ''; ?> <?php echo (empty($user['end_date'])) ? 'disabled' : ''; ?>>
                                    <?php echo htmlspecialchars($user['full_name']) . ' (' . htmlspecialchars($user['email']) . ')'; ?>
                                    <?php echo (empty($user['end_date'])) ? ' - No active membership' : ''; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="issue_date" class="form-label">Issue Date *</label>
                                    <input type="date" class="form-control" id="issue_date" name="issue_date" value="<?php echo $issueDate; ?>" min="<?php echo $today; ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="return_date" class="form-label">Return Date *</label>
                                    <input type="date" class="form-control" id="return_date" name="return_date" value="<?php echo $returnDate; ?>" min="<?php echo $issueDate; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"><?php echo $remarks; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">Issue Book/Movie</button>
                            <a href="check_availability.php" class="btn btn-secondary">Back to Search</a>
                        </div>
                    </form>
                    <?php elseif (!$error): ?>
                    <div class="alert alert-warning">
                        <p>No book/movie selected or the selected item is not available.</p>
                    </div>
                    <div class="d-grid gap-2">
                        <a href="check_availability.php" class="btn btn-primary">Search Books/Movies</a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set max return date to 15 days from issue date
document.getElementById('issue_date').addEventListener('change', function() {
    const issueDate = new Date(this.value);
    const maxReturnDate = new Date(issueDate);
    maxReturnDate.setDate(maxReturnDate.getDate() + 15);
    
    const returnDateInput = document.getElementById('return_date');
    returnDateInput.min = this.value;
    
    // Format date as YYYY-MM-DD for input value
    const year = maxReturnDate.getFullYear();
    const month = String(maxReturnDate.getMonth() + 1).padStart(2, '0');
    const day = String(maxReturnDate.getDate()).padStart(2, '0');
    const formattedDate = `${year}-${month}-${day}`;
    
    // Set return date to max 15 days if current value is beyond that
    const currentReturnDate = new Date(returnDateInput.value);
    if (currentReturnDate > maxReturnDate) {
        returnDateInput.value = formattedDate;
    }
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>