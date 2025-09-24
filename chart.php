<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Flow Chart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body{background:#f8fafc}
        .box{background:#eaf2ff;border:1px solid #cfe1ff;border-radius:10px;padding:14px}
        .title{font-weight:600}
    </style>
</head>
<body class="py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="m-0">Library Management System – Flow</h3>
            <a class="btn btn-outline-primary" href="index.php">Back to Login</a>
        </div>
        <p class="text-muted">Reference chart to navigate the app per assignment. A link to this page appears across the site.</p>

        <div class="row g-3">
            <div class="col-md-6">
                <div class="box">
                    <div class="title mb-2">Logins</div>
                    <ul class="mb-0">
                        <li>User login → user dashboard</li>
                        <li>Admin login → admin dashboard</li>
                    </ul>
                </div>
            </div>
            <div class="col-md-6">
                <div class="box">
                    <div class="title mb-2">Access Rules</div>
                    <ul class="mb-0">
                        <li>Admin: Maintenance, Reports, Transactions</li>
                        <li>User: Reports, Transactions (no Maintenance)</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-12">
                <div class="box">
                    <div class="title mb-2">Maintenance</div>
                    <span>Memberships (add/update), Books/Movies (add/update), User Management</span>
                </div>
            </div>

            <div class="col-md-12">
                <div class="box">
                    <div class="title mb-2">Transactions</div>
                    <span>Check Availability → Search Results (radio select) → Issue Book → Return Book → Pay Fine</span>
                </div>
            </div>

            <div class="col-md-12">
                <div class="box">
                    <div class="title mb-2">Reports</div>
                    <span>Master list of books/movies, memberships, active issues, overdue returns, issue requests</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>


