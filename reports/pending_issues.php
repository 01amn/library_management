<?php
// Set page title
$pageTitle = "Pending Issue Requests";

// Include header
require_once '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    // Redirect to dashboard if not admin
    header("Location: ../user/dashboard.php");
    exit;
}

// Initialize variables
$searchTerm = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// Build query for pending issue requests
$sql = "SELECT r.*, b.title, b.author, b.item_type, m.name as member_name, m.membership_id, m.email, m.phone
        FROM issue_requests r 
        JOIN books b ON r.book_id = b.id 
        JOIN members m ON r.user_id = m.id 
        WHERE r.status = 'pending'";
$params = [];
$types = "";

if (!empty($searchTerm)) {
    $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR m.name LIKE ? OR m.membership_id LIKE ?)";
    $searchParam = "%$searchTerm%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "ssss";
}

$sql .= " ORDER BY r.request_date ASC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$pendingRequests = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pendingRequests[] = $row;
    }
}

// Process approve/reject actions
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['request_id'])) {
    $requestId = sanitizeInput($_POST['request_id']);
    $action = sanitizeInput($_POST['action']);
    
    if ($action == 'approve') {
        // Get request details
        $sql = "SELECT * FROM issue_requests WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $request = $result->fetch_assoc();
            
            // Check if book is available
            $sql = "SELECT * FROM books WHERE id = ? AND available_copies > 0";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $request['book_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $book = $result->fetch_assoc();
                
                // Begin transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into issues table
                    $sql = "INSERT INTO issues (book_id, user_id, issue_date, expected_return_date, remarks) 
                            VALUES (?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iisss", $request['book_id'], $request['user_id'], 
                                     $request['requested_issue_date'], $request['requested_return_date'], 
                                     $request['remarks']);
                    $stmt->execute();
                    
                    // Update book availability
                    $sql = "UPDATE books SET available_copies = available_copies - 1 WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $request['book_id']);
                    $stmt->execute();
                    
                    // Update request status
                    $sql = "UPDATE issue_requests SET status = 'approved' WHERE id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $requestId);
                    $stmt->execute();
                    
                    // Commit transaction
                    $conn->commit();
                    
                    // Set success message
                    $_SESSION['success_message'] = "Issue request approved successfully.";
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $_SESSION['error_message'] = "Error approving request: " . $e->getMessage();
                }
            } else {
                $_SESSION['error_message'] = "Book is no longer available.";
            }
        } else {
            $_SESSION['error_message'] = "Request not found.";
        }
    } elseif ($action == 'reject') {
        // Update request status to rejected
        $sql = "UPDATE issue_requests SET status = 'rejected' WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $requestId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Issue request rejected.";
        } else {
            $_SESSION['error_message'] = "Error rejecting request.";
        }
    }
    
    // Redirect to refresh the page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Pending Issue Requests</h5>
                    <div>
                        <a href="../admin/dashboard.php" class="btn btn-sm btn-light">Back to Dashboard</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php endif; ?>
                
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
                                    <th>Member</th>
                                    <th>Membership ID</th>
                                    <th>Contact</th>
                                    <th>Requested Issue Date</th>
                                    <th>Requested Return Date</th>
                                    <th>Request Date</th>
                                    <th>Remarks</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pendingRequests)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">No pending issue requests found.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($pendingRequests as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['title']); ?></td>
                                    <td><?php echo htmlspecialchars($request['author']); ?></td>
                                    <td><?php echo ucfirst($request['item_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($request['membership_id']); ?></td>
                                    <td>
                                        <small>Email: <?php echo htmlspecialchars($request['email']); ?><br>
                                        Phone: <?php echo htmlspecialchars($request['phone']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($request['requested_issue_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['requested_return_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($request['remarks']); ?></td>
                                    <td>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this request?')">Approve</button>
                                        </form>
                                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to reject this request?')">Reject</button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        <p><strong>Total Pending Requests:</strong> <?php echo count($pendingRequests); ?></p>
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