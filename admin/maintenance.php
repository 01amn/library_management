<?php
// Set page title
$pageTitle = "Maintenance";

require_once '../includes/header.php';

if (!isAdmin()) {
    redirect('../user/dashboard.php');
}
?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white"><h5 class="mb-0">Maintenance Menu</h5></div>
                <div class="card-body">
                    <div class="row row-cols-1 row-cols-md-3 g-3">
                        <div class="col">
                            <a class="text-decoration-none" href="add_membership.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">Add Membership</div>
                                    <small>Create a new member subscription.</small>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a class="text-decoration-none" href="update_membership.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">Update Membership</div>
                                    <small>Extend or cancel existing memberships.</small>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a class="text-decoration-none" href="add_book.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">Add Book/Movie</div>
                                    <small>Add new catalog items.</small>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a class="text-decoration-none" href="update_book.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">Update Book/Movie</div>
                                    <small>Edit catalog details and copies.</small>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a class="text-decoration-none" href="user_management.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">User Management</div>
                                    <small>Add or update users.</small>
                                </div>
                            </a>
                        </div>
                        <div class="col">
                            <a class="text-decoration-none" href="../chart.php">
                                <div class="border rounded p-3 h-100">
                                    <div class="fw-semibold mb-1">System Chart</div>
                                    <small>Open the reference flow chart.</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>


