<?php
require_once '../config.php';
if (!isLoggedIn()) { redirect('../index.php'); }
if (!isAdmin()) { redirect('../user/dashboard.php'); }

// Set page title and then include header
$pageTitle = "Add Membership";
require_once '../includes/header.php';

// Initialize variables
$userId = $fullName = $membershipType = $message = "";
$error = false;

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $userId = sanitizeInput($_POST["user_id"]);
    $membershipType = sanitizeInput($_POST["membership_type"]);
    
    // Validate input
    if (empty($userId) || empty($membershipType)) {
        $error = true;
        $message = showError("All fields are required.");
    } else {
        // Check if user exists
        $sql = "SELECT user_id, full_name FROM users WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            $error = true;
            $message = showError("User not found.");
        } else {
            $user = $result->fetch_assoc();
            $fullName = $user['full_name'];
            
            // Calculate membership dates
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
            
            if ($membershipType == "6 months") {
                $endDate = date('Y-m-d', strtotime('+6 months'));
            } elseif ($membershipType == "1 year") {
                $endDate = date('Y-m-d', strtotime('+1 year'));
            } elseif ($membershipType == "2 years") {
                $endDate = date('Y-m-d', strtotime('+2 years'));
            }
            
            // Check if user already has an active membership
            $sql = "SELECT membership_id FROM memberships WHERE user_id = ? AND status = 'active'";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = true;
                $message = showError("User already has an active membership. Please update the existing membership instead.");
            } else {
                // Insert new membership
                $sql = "INSERT INTO memberships (user_id, membership_type, start_date, end_date, status) VALUES (?, ?, ?, ?, 'active')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isss", $userId, $membershipType, $startDate, $endDate);
                
                if ($stmt->execute()) {
                    $message = showSuccess("Membership added successfully.");
                    // Reset form
                    $userId = $fullName = $membershipType = "";
                } else {
                    $error = true;
                    $message = showError("Error adding membership: " . $conn->error);
                }
            }
        }
    }
}

// Get all users for dropdown
$sql = "SELECT user_id, username, full_name FROM users WHERE user_type = 'user'";
$users = $conn->query($sql);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Add New Membership</h5>
                </div>
                <div class="card-body">
                    <?php echo $message; ?>
                    
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="membershipForm">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Select User</label>
                            <select class="form-select" id="user_id" name="user_id" required>
                                <option value="">-- Select User --</option>
                                <?php if ($users && $users->num_rows > 0): ?>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <option value="<?php echo $user['user_id']; ?>" <?php echo ($userId == $user['user_id']) ? 'selected' : ''; ?>>
                                            <?php echo $user['full_name']; ?> (<?php echo $user['username']; ?>)
                                        </option>
                                    <?php endwhile; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Membership Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="sixMonths" value="6 months" <?php echo ($membershipType == "6 months" || empty($membershipType)) ? 'checked' : ''; ?> required>
                                <label class="form-check-label" for="sixMonths">
                                    6 Months
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="oneYear" value="1 year" <?php echo ($membershipType == "1 year") ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="oneYear">
                                    1 Year
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="membership_type" id="twoYears" value="2 years" <?php echo ($membershipType == "2 years") ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="twoYears">
                                    2 Years
                                </label>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Add Membership</button>
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