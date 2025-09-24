<?php
$pageTitle = "User Management";
require_once '../includes/header.php';
if (!isAdmin()) { redirect('../user/dashboard.php'); }

$message = '';

// Handle create/update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = sanitizeInput($_POST['mode']); // new | existing
    $name = sanitizeInput($_POST['full_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $userType = sanitizeInput($_POST['user_type']);
    $userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;

    $errors = validateRequired(['full_name'=>$name]);
    if (!empty($email) && !validateEmail($email)) { $errors[] = 'Invalid email.'; }
    if (!empty($phone) && !validatePhone($phone)) { $errors[] = 'Invalid phone.'; }

    if (empty($errors)) {
        if ($mode === 'new') {
            if (empty($username)) { $errors[] = 'Username is required.'; }
            $pwd = password_hash('password123', PASSWORD_BCRYPT);
            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO users (username,password,full_name,email,phone,user_type) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("ssssss", $username, $pwd, $name, $email, $phone, $userType);
                if ($stmt->execute()) { $message = showSuccess('User created with default password: password123'); }
                else { $message = showError('Failed to create user.'); }
            }
        } else {
            // existing
            if ($userId <= 0) { $errors[] = 'Select an existing user.'; }
            if (empty($errors)) {
                $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, user_type=? WHERE user_id=?");
                $stmt->bind_param("ssssi", $name, $email, $phone, $userType, $userId);
                if ($stmt->execute()) { $message = showSuccess('User updated.'); }
                else { $message = showError('Failed to update user.'); }
            }
        }
    }

    if (!empty($errors)) { $message = displayErrors($errors); }
}

// Load users for dropdown
$users = $conn->query("SELECT user_id, username, full_name, user_type FROM users ORDER BY full_name");
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">User Management</h5></div>
                <div class="card-body">
                    <?php echo $message; ?>
                    <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" id="userForm">
                        <div class="mb-3">
                            <label class="form-label">Mode</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_new" value="new" checked>
                                <label class="form-check-label" for="mode_new">New User</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="mode" id="mode_existing" value="existing">
                                <label class="form-check-label" for="mode_existing">Existing User</label>
                            </div>
                        </div>

                        <div id="existingUserSelect" class="mb-3" style="display:none;">
                            <label class="form-label">Select User</label>
                            <select class="form-select" name="user_id">
                                <option value="">-- Select --</option>
                                <?php if ($users && $users->num_rows>0) { while ($u=$users->fetch_assoc()) { ?>
                                    <option value="<?php echo $u['user_id']; ?>"><?php echo htmlspecialchars($u['full_name']).' ('.$u['username'].')'; ?></option>
                                <?php } } ?>
                            </select>
                        </div>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Full Name *</label>
                                <input class="form-control" name="full_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Username (new only)</label>
                                <input class="form-control" name="username">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Phone</label>
                                <input class="form-control" name="phone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">User Type</label>
                                <select class="form-select" name="user_type">
                                    <option value="user">User</option>
                                    <option value="admin">Admin</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">Confirm</button>
                            <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('input[name="mode"]').forEach(r=>{
  r.addEventListener('change',()=>{
    const showExisting = document.getElementById('mode_existing').checked;
    document.getElementById('existingUserSelect').style.display = showExisting? 'block':'none';
  });
});
</script>

<?php require_once '../includes/footer.php'; ?>


