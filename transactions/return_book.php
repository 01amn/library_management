<?php
// Set page title
$pageTitle = "Return Book";

// Include header
require_once '../includes/header.php';

// Initialize variables
$issueId = $actualReturnDate = $remarks = $message = "";
$issue = null;
$error = false;

// Set default date
$today = date('Y-m-d');
$actualReturnDate = $today;

// Get active issues for selection
if (isAdmin()) {
$sql = "SELECT t.transaction_id, t.issue_date, t.return_date, t.remarks,
                   b.title, b.author, b.isbn, b.item_type,
                   u.full_name as user_name, u.email as user_email
            FROM transactions t
            JOIN books b ON t.book_id = b.book_id
            JOIN users u ON t.user_id = u.user_id
            WHERE t.status = 'issued'
            ORDER BY t.return_date ASC";
    $stmt = $conn->prepare($sql);
} else {
    $userId = $_SESSION['user_id'];
    $sql = "SELECT t.transaction_id, t.issue_date, t.return_date, t.remarks,
                   b.title, b.author, b.isbn, b.item_type
            FROM transactions t
            JOIN books b ON t.book_id = b.book_id
            WHERE t.user_id = ? AND t.status = 'issued'
            ORDER BY t.return_date ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
}

$stmt->execute();
$result = $stmt->get_result();
$activeIssues = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $activeIssues[] = $row;
    }
}

// Check if issue ID is provided
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $issueId = sanitizeInput($_GET['id']);
    
    // Get issue details
    if (isAdmin()) {
        $sql = "SELECT t.*, b.title, b.author, b.isbn, b.item_type, u.full_name as user_name, u.email as user_email
                FROM transactions t
                JOIN books b ON t.book_id = b.book_id
                JOIN users u ON t.user_id = u.user_id
                WHERE t.transaction_id = ? AND t.status = 'issued'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $issueId);
    } else {
        $userId = $_SESSION['user_id'];
        $sql = "SELECT t.*, b.title, b.author, b.isbn, b.item_type
                FROM transactions t
                JOIN books b ON t.book_id = b.book_id
                WHERE t.transaction_id = ? AND t.user_id = ? AND t.status = 'issued'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $issueId, $userId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $issue = $result->fetch_assoc();
    } else {
        $error = true;
        $message = showError("Issue record not found or already returned.");
    }
}

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    // Get form data
    $issueId = sanitizeInput($_POST["issue_id"]);
    $actualReturnDate = sanitizeInput($_POST["actual_return_date"]);
    $remarks = sanitizeInput($_POST["remarks"]);
    
    // Validate input
    if (empty($issueId) || empty($actualReturnDate)) {
        $error = true;
        $message = showError("All fields marked with * are required.");
    } else {
        // Get issue details
        if (isAdmin()) {
            $sql = "SELECT bi.*, b.title, b.author, b.isbn, b.item_type, u.name as user_name, u.email as user_email
                    FROM book_issues bi
                    JOIN books b ON bi.book_id = b.id
                    JOIN users u ON bi.user_id = u.id
                    WHERE bi.id = ? AND bi.status = 'issued'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $issueId);
        } else {
            $userId = $_SESSION['user_id'];
            $sql = "SELECT bi.*, b.title, b.author, b.isbn, b.item_type
                    FROM book_issues bi
                    JOIN books b ON bi.book_id = b.id
                    WHERE bi.id = ? AND bi.user_id = ? AND bi.status = 'issued'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $issueId, $userId);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $issue = $result->fetch_assoc();
            
            // Calculate fine if returned after due date
            $fine = 0;
            $dueDate = new DateTime($issue['return_date']);
            $returnDate = new DateTime($actualReturnDate);
            
            if ($returnDate > $dueDate) {
                $diff = $returnDate->diff($dueDate);
                $daysLate = $diff->days;
                $fine = $daysLate * 5; // $5 per day fine
            }
            
            // Redirect to fine payment page if there's a fine
            if ($fine > 0) {
                $_SESSION['fine_data'] = [
                    'issue_id' => $issueId,
                    'actual_return_date' => $actualReturnDate,
                    'fine_amount' => $fine,
                    'remarks' => $remarks
                ];
                redirect('pay_fine.php');
            } else {
                // Update issue record
                $sql = "UPDATE transactions SET actual_return_date = ?, remarks = CONCAT(remarks, ' ', ?), status = 'returned' WHERE transaction_id = ?";
                $stmt = $conn->prepare($sql);
                $updatedRemarks = "Returned on " . $actualReturnDate . ". " . $remarks;
                $stmt->bind_param("ssi", $actualReturnDate, $updatedRemarks, $issueId);
                
                if ($stmt->execute()) {
                    // Update available copies
                    $sql = "UPDATE books b JOIN transactions t ON t.book_id=b.book_id SET b.available_copies=b.available_copies+1 WHERE t.transaction_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $issueId);
                    
                    if ($stmt->execute()) {
                        $message = showSuccess("Book/Movie returned successfully.");
                        
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
                    $message = showError("Error returning book: " . $conn->error);
                }
            }
        } else {
            $error = true;
            $message = showError("Issue record not found or already returned.");
        }
    }
}
?>

<div class="container-fluid">
    <?php if (empty($issueId) || $error): ?>
    <div class="row mb-4">
        <div class="col-md-10 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Select Book/Movie to Return</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <?php if (empty($activeIssues)): ?>
                    <div class="alert alert-info">
                        <p>No active issues found.</p>
                    </div>
                    <div class="d-grid gap-2 col-md-4 mx-auto">
                        <a href="<?php echo isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php'; ?>" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Author/Director</th>
                                    <?php if (isAdmin()): ?>
                                    <th>User</th>
                                    <?php endif; ?>
                                    <th>Issue Date</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeIssues as $activeIssue): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($activeIssue['title']); ?></td>
                                    <td><?php echo htmlspecialchars($activeIssue['author']); ?></td>
                                    <?php if (isAdmin()): ?>
                                    <td><?php echo htmlspecialchars($activeIssue['user_name']); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('M d, Y', strtotime($activeIssue['issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($activeIssue['return_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $today = new DateTime();
                                        $dueDate = new DateTime($activeIssue['return_date']);
                                        if ($today > $dueDate) {
                                            echo '<span class="badge bg-danger">Overdue</span>';
                                        } else {
                                            echo '<span class="badge bg-success">Active</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?id=' . $activeIssue['id']; ?>" class="btn btn-sm btn-primary">Return</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Return Book/Movie</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <div class="alert alert-info">
                        <h6>Book/Movie Details:</h6>
                        <p><strong>Title:</strong> <?php echo htmlspecialchars($issue['title']); ?><br>
                        <strong>Author/Director:</strong> <?php echo htmlspecialchars($issue['author']); ?><br>
                        <strong>Type:</strong> <?php echo ucfirst($issue['item_type']); ?><br>
                        <strong>ISBN/Serial Number:</strong> <?php echo htmlspecialchars($issue['isbn']); ?></p>
                        
                        <?php if (isAdmin()): ?>
                        <h6 class="mt-3">User Details:</h6>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($issue['user_name']); ?><br>
                        <strong>Email:</strong> <?php echo htmlspecialchars($issue['user_email']); ?></p>
                        <?php endif; ?>
                        
                        <h6 class="mt-3">Issue Details:</h6>
                        <p><strong>Issue Date:</strong> <?php echo date('M d, Y', strtotime($issue['issue_date'])); ?><br>
                        <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($issue['return_date'])); ?><br>
                        <?php 
                        $today = new DateTime();
                        $dueDate = new DateTime($issue['return_date']);
                        if ($today > $dueDate) {
                            $diff = $today->diff($dueDate);
                            $daysLate = $diff->days;
                            $estimatedFine = $daysLate * 5; // $5 per day fine
                            echo "<strong>Status:</strong> <span class='badge bg-danger'>Overdue by {$daysLate} days</span><br>";
                            echo "<strong>Estimated Fine:</strong> <span class='text-danger'>$" . number_format($estimatedFine, 2) . "</span>";
                        } else {
                            echo "<strong>Status:</strong> <span class='badge bg-success'>On Time</span>";
                        }
                        ?>
                        </p>
                    </div>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="returnForm">
                        <input type="hidden" name="issue_id" value="<?php echo $issueId; ?>">
                        
                        <div class="mb-3">
                            <label for="actual_return_date" class="form-label">Return Date *</label>
                            <input type="date" class="form-control" id="actual_return_date" name="actual_return_date" value="<?php echo $actualReturnDate; ?>" max="<?php echo $today; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="2"><?php echo $remarks; ?></textarea>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" name="submit" class="btn btn-primary">Return Book/Movie</button>
                            <a href="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="btn btn-secondary">Select Different Book/Movie</a>
                        </div>
                    </form>
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