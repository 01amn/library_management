<?php
require_once 'config.php';
require_once __DIR__ . '/includes/validation.php';

$errors = [];
$success = '';

// Local validation helpers
if (!function_exists('validateRequired')) {
    function validateRequired($fields) {
        $errs = [];
        foreach ($fields as $name => $val) {
            if (trim((string)$val) === '') {
                $label = ucfirst(str_replace('_', ' ', $name));
                $errs[] = "$label is required.";
            }
        }
        return $errs;
    }
}
if (!function_exists('validateEmail')) {
    function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
if (!function_exists('displayErrors')) {
    function displayErrors($errors) {
        if (empty($errors)) return '';
        $html = "<div class='alert alert-danger'><ul class='mb-0'>";
        foreach ($errors as $e) { $html .= '<li>'.htmlspecialchars($e).'</li>'; }
        $html .= '</ul></div>';
        return $html;
    }
}
if (!function_exists('displaySuccess')) {
    function displaySuccess($message) {
        if (empty($message)) return '';
        return "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
    }
}

// Initialize fields
$username = $fullName = $email = $phone = '';
$userType = 'user'; // default user

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitizeInput($_POST['username']);
    $fullName = sanitizeInput($_POST['full_name']);
    $email    = sanitizeInput($_POST['email']);
    $phone    = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm_password'];

    // Required fields check
    $errors = array_merge($errors, validateRequired([
        'username' => $username,
        'full_name' => $fullName,
        'email' => $email,
        'password' => $password,
        'confirm_password' => $confirm
    ]));

    if (!validateEmail($email)) { $errors[] = 'Invalid email address.'; }
    if (strlen($password) < 6) { $errors[] = 'Password must be at least 6 characters.'; }
    if ($password !== $confirm) { $errors[] = 'Passwords do not match.'; }

    // Check if username or email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = ? OR email = ?');
        $stmt->bind_param('ss', $username, $email);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $errors[] = 'Username or email already exists.';
        }
    }

    // Determine user type: first user = admin, others = user
    if (empty($errors)) {
        $countRes = $conn->query("SELECT COUNT(*) AS total FROM users");
        $row = $countRes->fetch_assoc();
        if ($row['total'] == 0) {
            $userType = 'admin'; // first signup = admin
        } else {
            $userType = 'user';  // rest = user
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare('INSERT INTO users (username, password, full_name, email, phone, user_type) VALUES (?,?,?,?,?,?)');
        $stmt->bind_param('ssssss', $username, $hash, $fullName, $email, $phone, $userType);
        if ($stmt->execute()) {
            $success = 'Account created successfully. You can now log in.';
            $username = $fullName = $email = $phone = '';
        } else {
            $errors[] = 'Failed to create account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Library Management</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .tab-pill .nav-link { border-radius:30px }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-7">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 id="formTitle" class="mb-0">Sign Up</h5>
                            <a class="btn btn-sm btn-light" href="index.php">Back to Login</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <?php echo ($userType === 'admin') ? 'Create administrator account' : 'Create user account'; ?>
                        </div>

                        <?php 
                            if (!empty($errors)) { echo displayErrors($errors); }
                            if (!empty($success)) { echo displaySuccess($success); }
                        ?>

                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Username *</label>
                                    <input class="form-control" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Full Name *</label>
                                    <input class="form-control" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email *</label>
                                    <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input class="form-control" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Password *</label>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Confirm Password *</label>
                                    <input type="password" class="form-control" name="confirm_password" required>
                                </div>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <button type="submit" class="btn btn-primary">Create Account</button>
                                <a href="chart.php" class="btn btn-outline-secondary">View System Chart</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
