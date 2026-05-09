<?php
include "db-conn.php";

// Start session at the very top
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$sql = "SELECT * FROM `categories` ORDER BY id DESC";
$check = mysqli_query($conn, $sql);

function SlugUrl($string) {
    $slug = preg_replace('/[^a-zA-Z0-9 -]/', '', $string);
    $slug = str_replace(' ', '-', $slug);
    $slug = strtolower($slug);
    return $slug;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Sub Category | Admin Panel</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    
    <?php include "links.php"; ?>
    <style>
        .form-section {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 6px;
            padding: 10px 15px;
            border: 1px solid #e0e0e0;
        }
        .form-control:focus {
            border-color: #7367f0;
            box-shadow: 0 0 0 3px rgba(115,103,240,.15);
        }
        .card-header {
            background: #fff;
            border-bottom: 1px solid #eee;
            padding: 20px 30px;
        }
        .main-title h2 {
            color: #2c2c2c;
            font-weight: 600;
        }
        .btn-primary {
            background-color:rgba(14, 18, 230, 0.84);
            border-color: #7367f0;
            padding: 10px 25px;
            border-radius: 6px;
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        .btn-primary:hover {
            background-color: #5d50e6;
            border-color: #5d50e6;
        }
        .preview-image {
            max-width: 150px;
            max-height: 150px;
            margin-top: 15px;
            border-radius: 4px;
            border: 1px dashed #ddd;
            padding: 5px;
            display: none;
        }
    </style>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    
    <section class="main_content dashboard_part">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    
                    <div class="col-lg-12">
                    <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                    <h2 class="mb-0">Add Sub Category</h2>
                                    <p class="text-muted mb-0 small">Manage your product Sub Categories</p>
                                    </div>
                                    <div>
                                        <a href="view-sub-categories.php" class="btn btn-primary" data-bs-toggle="modal"
                                            data-bs-target="#addcategory">
                                            <i class="fas fa-plus me-2"></i>View Sub Category
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <div class="form-section">
                            <form id="subCategoryForm" action="functions.php" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <!-- Parent Category -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Parent Category <span class="text-danger">*</span></label>
                                        <select class="form-select" name="parent_id" required>
                                            <option value="" selected disabled>Select Parent Category</option>
                                            <?php while ($row = mysqli_fetch_assoc($check)): ?>
                                                <option value="<?= $row['cate_id'] ?>"><?= htmlspecialchars($row['categories']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Sub Category Name -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Sub Category Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="cate_name" placeholder="Enter sub category name" required>
                                    </div>
                                    
                                    <!-- Meta Title -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Meta Title</label>
                                        <input type="text" class="form-control" name="meta_title" placeholder="Meta title for SEO">
                                        <small class="text-muted">Recommended: 50-60 characters</small>
                                    </div>
                                    
                                    <!-- Meta Keywords -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" name="meta_key" placeholder="Comma separated keywords">
                                    </div>
                                    
                                    <!-- Meta Description -->
                                    <div class="col-md-12 mb-4">
                                        <label class="form-label">Meta Description</label>
                                        <textarea class="form-control" name="meta_desc" rows="3" placeholder="Meta description for SEO"></textarea>
                                        <small class="text-muted">Recommended: 150-160 characters</small>
                                    </div>
                                    
                                    <!-- Image Upload -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Category Image</label>
                                        <input type="file" class="form-control" name="imageUpload" id="imageUpload" accept="image/*">
                                        <img id="imagePreview" src="#" alt="Preview" class="preview-image">
                                    </div>
                                    
                                    <!-- Status -->
                                    <div class="col-md-6 mb-4">
                                        <label class="form-label">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="1" selected>Active</option>
                                            <option value="0">Inactive</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Submit Button -->
                                    <div class="col-12 mt-3">
                                        <button type="submit" class="btn btn-primary" name="add-sub-categories">
                                            <i class="fas fa-plus me-2"></i> Add Sub Category
                                        </button>
                                        <a href="categories.php" class="btn btn-outline-secondary ms-2">
                                            <i class="fas fa-times me-2"></i> Cancel
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>

        <script>
            // Image preview functionality
            document.getElementById('imageUpload').addEventListener('change', function(e) {
                const preview = document.getElementById('imagePreview');
                const file = e.target.files[0];
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.style.display = 'block';
                    preview.src = e.target.result;
                }
                
                if (file) {
                    reader.readAsDataURL(file);
                }
            });

            // Form validation
            document.getElementById('subCategoryForm').addEventListener('submit', function(e) {
                const parentCategory = document.querySelector('[name="parent_id"]');
                const categoryName = document.querySelector('[name="cate_name"]');
                
                if (!parentCategory.value) {
                    e.preventDefault();
                    alert('Please select a parent category');
                    parentCategory.focus();
                    return false;
                }
                
                if (!categoryName.value.trim()) {
                    e.preventDefault();
                    alert('Please enter a sub category name');
                    categoryName.focus();
                    return false;
                }
                
                return true;
            });
        </script>
</body>
</html>