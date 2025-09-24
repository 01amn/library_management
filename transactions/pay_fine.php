<?php
// Set page title
$pageTitle = "Pay Fine";

// Include header
require_once '../includes/header.php';

$message = '';
$fineData = isset($_SESSION['fine_data']) ? $_SESSION['fine_data'] : null;

// If user came here without flow, still allow confirmation path with zero fine
if (!$fineData) {
    $fineData = [
        'issue_id' => isset($_GET['id']) ? (int)$_GET['id'] : 0,
        'actual_return_date' => date('Y-m-d'),
        'fine_amount' => 0,
        'remarks' => ''
    ];
}

// Load issue details
$issue = null;
if ($fineData['issue_id'] > 0) {
    $sql = "SELECT bi.*, b.title, b.author, b.item_type FROM book_issues bi JOIN books b ON bi.book_id=b.id WHERE bi.id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $fineData['issue_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $issue = $res->fetch_assoc();
    }
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $issueId = (int)$_POST['issue_id'];
    $paid = isset($_POST['fine_paid']) ? 1 : 0;
    $actualReturnDate = sanitizeInput($_POST['actual_return_date']);
    $remarks = sanitizeInput($_POST['remarks']);
    $fineAmount = (float)$_POST['fine_amount'];

    if ($fineAmount > 0 && !$paid) {
        $message = showError('Please confirm Fine Paid to complete the return.');
    } else {
        // Mark returned and update copies
        $sql = "UPDATE book_issues SET actual_return_date=?, remarks=CONCAT(remarks,' ',?), status='returned' WHERE id=?";
        $stmt = $conn->prepare($sql);
        $updatedRemarks = "Returned on " . $actualReturnDate . ". " . $remarks;
        $stmt->bind_param("ssi", $actualReturnDate, $updatedRemarks, $issueId);
        if ($stmt->execute()) {
            // Update copies
            $sql = "UPDATE books b JOIN book_issues bi ON bi.book_id=b.id SET b.available_copies=b.available_copies+1 WHERE bi.id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $issueId);
            $stmt->execute();

            unset($_SESSION['fine_data']);
            $_SESSION['success_message'] = 'Return completed successfully.';
            echo "<script>setTimeout(function(){ window.location.href='" . (isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php') . "'; }, 1500);</script>";
        } else {
            $message = showError('Unable to complete return.');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Fine Payment</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <?php if ($issue): ?>
                    <div class="alert alert-info">
                        <strong>Item:</strong> <?php echo htmlspecialchars($issue['title']); ?> by <?php echo htmlspecialchars($issue['author']); ?> (<?php echo ucfirst($issue['item_type']); ?>)
                    </div>
                    <?php endif; ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <input type="hidden" name="issue_id" value="<?php echo (int)$fineData['issue_id']; ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Calculated Fine</label>
                                <input type="text" class="form-control" name="fine_amount" value="<?php echo number_format((float)$fineData['fine_amount'], 2); ?>" readonly>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check mt-4">
                                    <input class="form-check-input" type="checkbox" id="fine_paid" name="fine_paid">
                                    <label class="form-check-label" for="fine_paid">Fine Paid</label>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label">Return Date</label>
                                <input type="date" class="form-control" name="actual_return_date" value="<?php echo $fineData['actual_return_date']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Remarks</label>
                                <input type="text" class="form-control" name="remarks" value="<?php echo htmlspecialchars($fineData['remarks']); ?>">
                            </div>
                        </div>
                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Confirm</button>
                            <a href="return_book.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


