<?php
ob_start();
session_start();
include "functions.php";
include "db-conn.php";

// Handle delete request (add this at the top)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_category'])) {
    $delete_id = mysqli_real_escape_string($conn, $_POST['delete_id']);
    
    // Get image path
    $sql = "SELECT image FROM categories WHERE id = '$delete_id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);
    
    // Delete image file
    if (!empty($row['image']) && file_exists("uploads/category/" . $row['image'])) {
        unlink("uploads/category/" . $row['image']);
    }
    
    // Delete category
    $delete_sql = "DELETE FROM categories WHERE id = '$delete_id'";
    if (mysqli_query($conn, $delete_sql)) {
        $_SESSION['success'] = "Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete category: " . mysqli_error($conn);
    }
    
    header("Location: view-categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Category Management | Admin Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php include "links.php"; ?>
    
    <style>
        .delete-btn, .action-btn {
            cursor: pointer;
        }
        .modal-header.bg-danger {
            background: #dc3545 !important;
        }
        .category-image {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1 fw-bold">Category Management</h2>
                                        <p class="text-muted mb-0 small">Manage your product categories efficiently</p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="search-box me-3">
                                            <i class="fas fa-search search-icon"></i>
                                            <input type="text" class="form-control" id="searchInput"
                                                placeholder="Search categories..." onkeyup="searchCategories()">
                                        </div>
                                        <a href="add-categories.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add New
                                        </a>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="text-muted me-2">Filter by:</span>
                                            <button class="btn btn-outline-secondary filter-btn active"
                                                onclick="filterCategories('all', event)">
                                                <i class="fas fa-list me-1"></i>All
                                            </button>
                                            <button class="btn btn-outline-success filter-btn"
                                                onclick="filterCategories('active', event)">
                                                <i class="fas fa-check-circle me-1"></i>Active
                                            </button>
                                            <button class="btn btn-outline-danger filter-btn"
                                                onclick="filterCategories('inactive', event)">
                                                <i class="fas fa-times-circle me-1"></i>Inactive
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-hover lms_table_active" id="categoryTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th scope="col" width="5%">#</th>
                                                <th scope="col" width="25%">Category</th>
                                                <th scope="col" width="20%">Slug URL</th>
                                                <th scope="col" width="15%">Status</th>
                                                <th scope="col" width="15%">Created Date</th>
                                                <th scope="col" width="20%" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php echo get_Category(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

            <!-- Delete Confirmation Modal - FIXED -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="" id="deleteForm">
                    <div class="modal-body">
                        <input type="hidden" name="delete_id" id="delete_id">
                        <div class="text-center mb-3">
                            <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                            <h5 class="mb-2">Delete Category</h5>
                            <p class="text-muted">Are you sure you want to delete "<span id="deleteItemName" class="fw-bold text-danger"></span>"?</p>
                            <p class="text-danger small">
                                <i class="fas fa-exclamation-circle me-1"></i>
                                This action cannot be undone. All sub-categories and products under this category will be affected.
                            </p>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="delete_category" class="btn btn-danger">
                            <i class="fas fa-trash me-2"></i>Delete Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>


        <?php include "footer.php"; ?>
    </section>


    <script>
        function searchCategories() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#categoryTable tbody tr');
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        function filterCategories(status, event) {
            document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
            if (event && event.target) {
                event.target.classList.add('active');
            }

            let rows = document.querySelectorAll('#categoryTable tbody tr');
            rows.forEach(row => {
                if (status === 'all') {
                    row.style.display = '';
                } else {
                    let badge = row.querySelector('.status-badge');
                    if (badge) {
                        let rowStatus = badge.textContent.trim().toLowerCase();
                        row.style.display = rowStatus.includes(status) ? '' : 'none';
                    }
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Fix for delete modal - prevent page jump
            const deleteModal = document.getElementById('deleteModal');
            if (deleteModal) {
                deleteModal.addEventListener('show.bs.modal', function (event) {
                    // Get the button that triggered the modal
                    const button = event.relatedTarget;
                    if (button) {
                        const itemId = button.getAttribute('data-id');
                        const itemName = button.getAttribute('data-name');
                        
                        if (itemId && itemName) {
                            document.getElementById('delete_id').value = itemId;
                            document.getElementById('deleteItemName').textContent = itemName;
                        }
                    }
                });
            }

            // Tooltips initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(el => new bootstrap.Tooltip(el));

            // Show notifications
            <?php if (isset($_SESSION['success'])): ?>
                showNotification('success', '<?php echo addslashes($_SESSION["success"]); ?>');
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                showNotification('error', '<?php echo addslashes($_SESSION["error"]); ?>');
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
        });

        function showNotification(type, message) {
            const toast = document.createElement('div');
            toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
            toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            toast.innerHTML = `
                <strong>${type === 'success' ? 'Success!' : 'Error!'}</strong> ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        }
    </script>



