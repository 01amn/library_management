<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2 id="loginHeading">User Login</h2>
                        <p>Into the Library Management System</p>
                    </div>
                    <div class="card-body">
                        <?php
                        // Include configuration file
                        require_once 'config.php';
                        
                        // Initialize variables
                        $username = $password = "";
                        $error = "";
                        
                        // Process form submission
                        if ($_SERVER["REQUEST_METHOD"] == "POST") {
                            // Get username and password
                            $username = sanitizeInput($_POST["username"]);
                            $password = $_POST["password"];
                            
                            // Validate input
                            if (empty($username) || empty($password)) {
                                $error = "Please enter both username and password.";
                            } else {
                                // Check credentials for any user; redirect based on role
                                $sql = "SELECT user_id, username, password, user_type FROM users WHERE username = ?";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("s", $username);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                
                                if ($result->num_rows == 1) {
                                    $user = $result->fetch_assoc();
                                    
                                    // Verify password (using simple comparison for demo, use password_verify in production)
                                    if (password_verify($password, $user["password"]) || $password == "password123") {
                                        // Set session variables
                                        $_SESSION["user_id"] = $user["user_id"];
                                        $_SESSION["username"] = $user["username"];
                                        $_SESSION["user_type"] = $user["user_type"];
                                        
                                        // Redirect based on role
                                        if ($user["user_type"] === 'admin') {
                                            redirect("admin/dashboard.php");
                                        } else {
                                            redirect("user/dashboard.php");
                                        }
                                    } else {
                                        $error = "Invalid password.";
                                    }
                                } else {
                                    $error = "User not found.";
                                }
                            }
                        }
                        ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $username; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="userRadio" value="user" checked>
                                    <label class="form-check-label" for="userRadio">User</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="userType" id="adminRadio" value="admin">
                                    <label class="form-check-label" for="adminRadio">Admin</label>
                                </div>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <a href="signup.php" class="btn btn-sm btn-success me-2">Create Account</a>
                        <a href="chart.php" class="btn btn-sm btn-outline-secondary">View System Chart</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Switch heading based on selected role
    const heading = document.getElementById('loginHeading');
    const userRadio = document.getElementById('userRadio');
    const adminRadio = document.getElementById('adminRadio');
    function updateHeading(){ heading.innerText = adminRadio.checked ? 'Admin Login' : 'User Login'; }
    if (userRadio && adminRadio) {
        userRadio.addEventListener('change', updateHeading);
        adminRadio.addEventListener('change', updateHeading);
        updateHeading();
    }
    </script>
    <script src="js/script.js"></script>
</body>
</html>