<?php
// Set page title
$pageTitle = "Update Membership";

// Include header
require_once '../includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('../user/dashboard.php');
}

// Initialize variables
$membershipId = $userId = $fullName = $membershipType = $action = $message = "";
$error = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $membershipId = sanitizeInput($_POST["membership_id"]);
    $action = sanitizeInput($_POST["action"]);
    $membershipType = isset($_POST["membership_type"]) ? sanitizeInput($_POST["membership_type"]) : "";
    
    // Validate input
    if (empty($membershipId) || empty($action)) {
        $error = true;
        $message = showError("All fields are required.");
    } else {
        // Get membership details
        $sql = "SELECT m.*, u.full_name FROM memberships m JOIN users u ON m.user_id = u.user_id WHERE m.membership_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $membershipId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $error = true;
            $message = showError("Membership not found.");
        } else {
            $membership = $result->fetch_assoc();
            
            if ($action == "extend") {
                // Validate membership type
                if (empty($membershipType)) {
                    $error = true;
                    $message = showError("Please select a membership type.");
                } else {
                    // Calculate new end date
                    $currentEndDate = $membership['end_date'];
                    $newEndDate = $currentEndDate;
                    
                    if ($membershipType == "6 months") {
                        $newEndDate = date('Y-m-d', strtotime($currentEndDate . ' +6 months'));
                    } elseif ($membershipType == "1 year") {
                        $newEndDate = date('Y-m-d', strtotime($currentEndDate . ' +1 year'));
                    } elseif ($membershipType == "2 years") {
                        $newEndDate = date('Y-m-d', strtotime($currentEndDate . ' +2 years'));
                    }
                    
                    // Update membership
                    $sql = "UPDATE memberships SET end_date = ?, status = 'active' WHERE membership_id = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("si", $newEndDate, $membershipId);
                    
                    if ($stmt->execute()) {
                        $message = showSuccess("Membership extended successfully.");
                    } else {
                        $error = true;
                        $message = showError("Error extending membership: " . $conn->error);
                    }
                }
            } elseif ($action == "cancel") {
                // Update membership status
                $sql = "UPDATE memberships SET status = 'cancelled' WHERE membership_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $membershipId);
                
                if ($stmt->execute()) {
                    $message = showSuccess("Membership cancelled successfully.");
                } else {
                    $error = true;
                    $message = showError("Error cancelling membership: " . $conn->error);
                }
            }
        }
    }
}

// Get membership by ID if provided in GET request
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $membershipId = sanitizeInput($_GET['id']);
    
    $sql = "SELECT m.*, u.full_name FROM memberships m JOIN users u ON m.user_id = u.user_id WHERE m.membership_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $membershipId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $membership = $result->fetch_assoc();
        $userId = $membership['user_id'];
        $fullName = $membership['full_name'];
        $membershipType = $membership['membership_type'];
    }
}

// Get all active memberships for dropdown
$sql = "SELECT m.membership_id, m.membership_type, m.start_date, m.end_date, u.full_name 
        FROM memberships m 
        JOIN users u ON m.user_id = u.user_id 
        WHERE m.status = 'active' 
        ORDER BY m.end_date ASC";
$memberships = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Update Membership</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="updateMembershipForm">
                        <div class="mb-3">
                            <label for="membership_id" class="form-label">Select Membership</label>
                            <select class="form-select" id="membership_id" name="membership_id" required>
                                <option value="">-- Select Membership --</option>
                                <?php if ($memberships && $memberships->num_rows > 0): ?>
                                    <?php while ($m = $memberships->fetch_assoc()): ?>
                                        <option value="<?php echo $m['membership_id']; ?>" <?php echo ($membershipId == $m['membership_id']) ? 'selected' : ''; ?>>
                                            <?php echo $m['full_name']; ?> (<?php echo $m['membership_type']; ?>, Expires: <?php echo $m['end_date']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Action</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="extendMembership" value="extend" checked>
                                <label class="form-check-label" for="extendMembership">
                                    Extend Membership
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="action" id="cancelMembership" value="cancel">
                                <label class="form-check-label" for="cancelMembership">
                                    Cancel Membership
                                </label>
                            </div>
                        </div>
                        
                        <div id="extensionOptions" class="mb-3">
                            <label class="form-label">Extension Period</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="sixMonths" value="6 months" checked>
                                <label class="form-check-label" for="sixMonths">
                                    6 Months
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="oneYear" value="1 year">
                                <label class="form-check-label" for="oneYear">
                                    1 Year
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="twoYears" value="2 years">
                                <label class="form-check-label" for="twoYears">
                                    2 Years
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Membership</button>
                            <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show/hide extension options based on action
    $('input[name="action"]').on('change', function() {
        if ($(this).val() === 'extend') {
            $('#extensionOptions').show();
        } else {
            $('#extensionOptions').hide();
        }
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>