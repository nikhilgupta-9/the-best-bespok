<?php
session_start();
include "functions.php";
include "db-conn.php"; // make sure DB connection exists as $conn

if (isset($_GET['id'])) {
    $id = intval($_GET['id']); // sanitize input

    $stmt = $conn->prepare("DELETE FROM sub_categories WHERE cate_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Sub Category deleted successfully!";
    } else {
        $_SESSION['error'] = "Failed to delete sub category!";
    }

    $stmt->close();
    header("Location: view-sub-categories.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sub Category Management | Admin Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
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
                                        <h2 class="mb-1 fw-bold">Sub Category Management</h2>
                                        <p class="text-muted mb-0 small">Manage product subcategories and their
                                            associations</p>
                                    </div>
                                    <div>
                                        <a href="add-sub-category.php" class="btn btn-primary btn-sm"
                                            data-bs-toggle="modal" data-bs-target="#addcategory">
                                            <i class="fas fa-plus me-2"></i>Add New Sub Category
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Search Filter -->
                            <div class="card-header bg-light border-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="searchInput"
                                                placeholder="Search subcategories..." onkeyup="searchSubCategories()">
                                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="statusFilter" id="all"
                                                autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" for="all">All</label>

                                            <input type="radio" class="btn-check" name="statusFilter" id="active"
                                                autocomplete="off">
                                            <label class="btn btn-outline-success" for="active">Active</label>

                                            <input type="radio" class="btn-check" name="statusFilter" id="inactive"
                                                autocomplete="off">
                                            <label class="btn btn-outline-danger" for="inactive">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-hover lms_table_active" id="subCategoryTable">
                                        <thead>
                                            <tr class="bg-light">
                                                <th scope="col" width="5%">#</th>
                                                <th scope="col" width="15%">Sub Category</th>
                                                <th scope="col" width="15%">Parent Category</th>
                                                <th scope="col" width="20%">Slug URL</th>
                                                <th scope="col" width="10%">Status</th>
                                                <th scope="col" width="15%">Created Date</th>
                                                <th scope="col" width="20%" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php echo get_Sub_Category(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>


    <!-- JavaScript for Search and Filter -->
    <script>
        function searchSubCategories() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('#subCategoryTable tbody tr');

            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function () {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

                  // Status filter functionality
               docu ment.querySelectorAll('input[name="statusFilter"]').forEach(radio => {
                radio.addEventListener('change', function () {
                    let status = this.id;
                    let rows = document.querySelectorAll('#subCategoryTable tbody tr');

                    rows.forEach(row => {
                        if (status === 'all') {
                            row.style.display = '';
                        } else {
                            let rowStatus = row.querySelector('.badge').textContent.toLowerCase();
                            row.style.display = rowStatus.includes(status) ? '' : 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>