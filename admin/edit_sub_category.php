<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "db-conn.php";
include "functions.php";

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit();
}

// Get sub-category id from URL
$sub_cat_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($sub_cat_id <= 0) {
    $_SESSION['error'] = "Invalid Sub-Category ID";
    header('Location: sub-categories.php');
    exit();
}

// Fetch current sub-category data
$sql = "SELECT * FROM `sub_categories` WHERE `cate_id` = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $sub_cat_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$subcategory = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$subcategory) {
    $_SESSION['error'] = "Sub-category not found";
    header('Location: sub-categories.php');
    exit();
}

// Fetch parent categories for dropdown
$parent_categories = [];
$parent_sql = "SELECT cate_id, categories FROM categories WHERE status = 1 ORDER BY categories";
$parent_result = mysqli_query($conn, $parent_sql);
while ($row = mysqli_fetch_assoc($parent_result)) {
    $parent_categories[] = $row;
}

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and sanitize form data
    $parent_id = mysqli_real_escape_string($conn, $_POST['parent_id']);
    $categories = mysqli_real_escape_string($conn, $_POST['categories']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $updated_on = date('Y-m-d H:i:s');

    // Handle image upload
    $imageName = $subcategory['sub_cat_img']; // Keep existing image by default
    $uploadDir = 'uploads/sub-category/';
    
    if (isset($_FILES['sub_cat_img']['name']) && $_FILES['sub_cat_img']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['sub_cat_img']['tmp_name'];
        $fileName = $_FILES['sub_cat_img']['name'];
        $fileSize = $_FILES['sub_cat_img']['size'];
        $fileType = $_FILES['sub_cat_img']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $maxFileSize = 5 * 1024 * 1024; // 5MB
        
        if (in_array($fileExtension, $allowedExtensions)) {
            if ($fileSize <= $maxFileSize) {
                // Create directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Generate unique filename
                $imageName = uniqid('subcat_') . '_' . time() . '.' . $fileExtension;
                $destPath = $uploadDir . $imageName;
                
                if (move_uploaded_file($fileTmpPath, $destPath)) {
                    // Delete old image if exists and not the default
                    if (!empty($subcategory['sub_cat_img']) && $subcategory['sub_cat_img'] != $imageName) {
                        $oldImagePath = $uploadDir . $subcategory['sub_cat_img'];
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                } else {
                    $error_message = "Failed to upload image";
                    $imageName = $subcategory['sub_cat_img']; // Revert to old image
                }
            } else {
                $error_message = "Image size should be less than 5MB";
            }
        } else {
            $error_message = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed";
        }
    } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
        // Remove existing image
        if (!empty($subcategory['sub_cat_img'])) {
            $oldImagePath = $uploadDir . $subcategory['sub_cat_img'];
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
        }
        $imageName = '';
    }

    // Update sub-category in database
    $updateQuery = "UPDATE sub_categories SET 
        parent_id = ?,
        categories = ?,
        meta_title = ?,
        meta_desc = ?,
        meta_key = ?,
        slug_url = ?,
        status = ?,
        sub_cat_img = ?,
        added_on = ?
        WHERE cate_id = ?";
    
    $stmt = mysqli_prepare($conn, $updateQuery);
    mysqli_stmt_bind_param($stmt, "ssssssssss", 
        $parent_id,
        $categories,
        $meta_title,
        $meta_desc,
        $meta_key,
        $slug_url,
        $status,
        $imageName,
        $updated_on,
        $sub_cat_id
    );
    
    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Sub-category updated successfully!";
        
        // Update session data
        $subcategory['parent_id'] = $parent_id;
        $subcategory['categories'] = $categories;
        $subcategory['meta_title'] = $meta_title;
        $subcategory['meta_desc'] = $meta_desc;
        $subcategory['meta_key'] = $meta_key;
        $subcategory['slug_url'] = $slug_url;
        $subcategory['status'] = $status;
        $subcategory['sub_cat_img'] = $imageName;
    } else {
        $error_message = "Error updating sub-category: " . mysqli_error($conn);
    }
    
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Sub-Category | Admin Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .page-header {
            padding: 20px 0;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 30px;
        }
        
        .form-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.05);
            padding: 30px;
        }
        
        .image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: #f8f9fa;
        }
        
        .image-upload-area {
            border: 2px dashed #4361ee;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .image-upload-area:hover {
            background: #e9ecef;
            border-color: #3a0ca3;
        }
        
        .upload-icon {
            font-size: 48px;
            color: #4361ee;
            margin-bottom: 15px;
        }
        
        .image-actions {
            margin-top: 15px;
        }
        
        .image-actions .btn {
            padding: 8px 15px;
            font-size: 14px;
        }
        
        .form-section {
            margin-bottom: 30px;
            padding-bottom: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .section-title {
            color: #4361ee;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .meta-counter {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .meta-counter.warning {
            color: #dc3545;
        }
        
        .slug-preview {
            background: #f8f9fa;
            padding: 10px 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .alert {
            border-radius: 8px;
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 8px;
        }
        
        .required:after {
            content: " *";
            color: #dc3545;
        }
        
        .back-btn {
            background: #6c757d;
            border: none;
            padding: 10px 20px;
            font-weight: 600;
        }
        
        .back-btn:hover {
            background: #5a6268;
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
                    <div class="col-xl-12">
                        <div class="white_card card_height_100 mb_30">
                            <!-- Header -->
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1 fw-bold">Edit Sub-Category</h2>
                                        <p class="text-muted mb-0 small">Update sub-category information and settings</p>
                                    </div>
                                    <div>
                                        <a href="view-sub-categories.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-arrow-left me-2"></i>Back to Sub-Categories
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <!-- Success/Error Messages -->
                                <?php if($success_message): ?>
                                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                                        <i class="fas fa-check-circle me-2"></i>
                                        <?= $success_message ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($error_message): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <?= $error_message ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="form-card">
                                    <form action="" method="post" enctype="multipart/form-data" id="editSubCategoryForm">
                                        <input type="hidden" name="id" value="<?= htmlspecialchars($subcategory['cate_id']) ?>">
                                        
                                        <!-- Basic Information Section -->
                                        <div class="form-section">
                                            <h4 class="section-title"><i class="fas fa-info-circle me-2"></i>Basic Information</h4>
                                            
                                            <div class="row g-4">
                                                <!-- Parent Category -->
                                                <div class="col-md-6">
                                                    <label class="form-label required">Parent Category</label>
                                                    <select name="parent_id" class="form-select" required>
                                                        <option value="">Select Parent Category</option>
                                                        <?php foreach($parent_categories as $parent): ?>
                                                            <option value="<?= htmlspecialchars($parent['cate_id']) ?>" 
                                                                <?= ($subcategory['parent_id'] == $parent['cate_id']) ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($parent['categories']) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                
                                                <!-- Sub-Category ID -->
                                                <div class="col-md-6">
                                                    <label class="form-label">Sub-Category ID</label>
                                                    <input type="text" class="form-control bg-light text-dark" 
                                                           value="<?= htmlspecialchars($subcategory['cate_id']) ?>" 
                                                           readonly>
                                                    <small class="text-muted">This ID is generated automatically and cannot be changed.</small>
                                                </div>
                                                
                                                <!-- Sub-Category Name -->
                                                <div class="col-md-6">
                                                    <label class="form-label required">Sub-Category Name</label>
                                                    <input type="text" name="categories" class="form-control" 
                                                           value="<?= htmlspecialchars($subcategory['categories']) ?>" 
                                                           required 
                                                           placeholder="Enter sub-category name">
                                                </div>
                                                
                                                <!-- Slug URL -->
                                                <div class="col-md-6">
                                                    <label class="form-label required">Slug URL</label>
                                                    <input type="text" name="slug_url" class="form-control" 
                                                           value="<?= htmlspecialchars($subcategory['slug_url']) ?>" 
                                                           required 
                                                           placeholder="e.g., electronics-accessories">
                                                    <div class="slug-preview">
                                                        <?= $site ?>category/<?= htmlspecialchars($subcategory['slug_url']) ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Status -->
                                                <div class="col-md-6">
                                                    <label class="form-label required">Status</label>
                                                    <select name="status" class="form-select" required>
                                                        <option value="1" <?= ($subcategory['status'] == 'Active') ? 'selected' : '' ?>>Active</option>
                                                        <option value="0" <?= ($subcategory['status'] == 'Inactive') ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Image Upload Section -->
                                        <div class="form-section">
                                            <h4 class="section-title"><i class="fas fa-image me-2"></i>Sub-Category Image</h4>
                                            
                                            <div class="row align-items-center">
                                                <div class="col-md-4">
                                                    <!-- Current Image Preview -->
                                                    <?php if(!empty($subcategory['sub_cat_img'])): ?>
                                                        <div class="text-center mb-3">
                                                            <img src="uploads/sub-category/<?= htmlspecialchars($subcategory['sub_cat_img']) ?>" 
                                                                 alt="Current Image" 
                                                                 class="image-preview mb-3">
                                                            <div class="image-actions">
                                                                <input type="hidden" name="remove_image" id="removeImage" value="0">
                                                                <button type="button" class="btn btn-sm btn-danger" onclick="removeImage()">
                                                                    <i class="fas fa-trash me-1"></i>Remove Image
                                                                </button>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-center">
                                                            <div class="image-upload-area" onclick="document.getElementById('imageUpload').click()">
                                                                <div class="upload-icon">
                                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                                </div>
                                                                <h6>Upload Image</h6>
                                                                <p class="text-muted small mb-0">Click to browse or drag & drop</p>
                                                                <p class="text-muted small">PNG, JPG, GIF up to 5MB</p>
                                                            </div>
                                                            <input type="file" name="sub_cat_img" id="imageUpload" class="d-none" accept="image/*">
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="col-md-8">
                                                    <!-- Upload/Replace Image -->
                                                    <div class="mb-4">
                                                        <label class="form-label"><?= empty($subcategory['sub_cat_img']) ? 'Upload' : 'Replace' ?> Image</label>
                                                        <input type="file" name="sub_cat_img" class="form-control" 
                                                               accept="image/*" 
                                                               onchange="previewImage(this)">
                                                        <small class="text-muted">Recommended size: 800x600px. Max file size: 5MB.</small>
                                                    </div>
                                                    
                                                    <!-- Image Preview -->
                                                    <div id="imagePreviewContainer" class="d-none">
                                                        <label class="form-label">Preview</label>
                                                        <div class="mb-3">
                                                            <img id="imagePreview" class="image-preview" alt="Preview">
                                                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" 
                                                                    onclick="clearImagePreview()">
                                                                <i class="fas fa-times me-1"></i>Clear Preview
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- SEO Section -->
                                        <div class="form-section">
                                            <h4 class="section-title"><i class="fas fa-search me-2"></i>SEO Settings</h4>
                                            
                                            <div class="row g-4">
                                                <!-- Meta Title -->
                                                <div class="col-md-12">
                                                    <label class="form-label">Meta Title</label>
                                                    <input type="text" name="meta_title" class="form-control" 
                                                           value="<?= htmlspecialchars($subcategory['meta_title']) ?>" 
                                                           placeholder="Meta title for search engines"
                                                           maxlength="60"
                                                           onkeyup="updateCharCounter(this, 'metaTitleCounter')">
                                                    <div class="meta-counter" id="metaTitleCounter">
                                                        <?= strlen($subcategory['meta_title']) ?>/60 characters
                                                    </div>
                                                </div>
                                                
                                                <!-- Meta Description -->
                                                <div class="col-md-12">
                                                    <label class="form-label">Meta Description</label>
                                                    <textarea name="meta_desc" class="form-control" 
                                                              rows="3" 
                                                              placeholder="Meta description for search engines"
                                                              maxlength="160"
                                                              onkeyup="updateCharCounter(this, 'metaDescCounter')"><?= htmlspecialchars($subcategory['meta_desc']) ?></textarea>
                                                    <div class="meta-counter" id="metaDescCounter">
                                                        <?= strlen($subcategory['meta_desc']) ?>/160 characters
                                                    </div>
                                                </div>
                                                
                                                <!-- Meta Keywords -->
                                                <div class="col-md-12">
                                                    <label class="form-label">Meta Keywords</label>
                                                    <input type="text" name="meta_key" class="form-control" 
                                                           value="<?= htmlspecialchars($subcategory['meta_key']) ?>" 
                                                           placeholder="Keyword1, Keyword2, Keyword3">
                                                    <small class="text-muted">Separate keywords with commas</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Action Buttons -->
                                        <div class="form-section">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <a href="sub-categories.php" class="btn btn-secondary">
                                                        <i class="fas fa-times me-2"></i>Cancel
                                                    </a>
                                                </div>
                                                <div class="d-flex gap-2">
                                                    <button type="reset" class="btn btn-outline-secondary">
                                                        <i class="fas fa-redo me-2"></i>Reset
                                                    </button>
                                                    <button type="submit" class="btn btn-primary submit-btn">
                                                        <i class="fas fa-save me-2"></i>Update Sub-Category
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php include "footer.php"; ?>
    </section>

    <script>
        // Image Preview Function
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreviewContainer').classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Clear Image Preview
        function clearImagePreview() {
            document.getElementById('imageUpload').value = '';
            document.getElementById('imagePreviewContainer').classList.add('d-none');
        }
        
        // Remove Existing Image
        function removeImage() {
            if (confirm('Are you sure you want to remove this image?')) {
                document.getElementById('removeImage').value = '1';
                // Hide current image and show upload area
                const imageContainer = document.querySelector('.image-preview').closest('.text-center');
                if (imageContainer) {
                    imageContainer.innerHTML = `
                        <div class="image-upload-area" onclick="document.getElementById('imageUpload').click()">
                            <div class="upload-icon">
                                <i class="fas fa-cloud-upload-alt"></i>
                            </div>
                            <h6>Upload Image</h6>
                            <p class="text-muted small mb-0">Click to browse or drag & drop</p>
                            <p class="text-muted small">PNG, JPG, GIF up to 5MB</p>
                        </div>
                        <input type="file" name="sub_cat_img" id="imageUpload" class="d-none" accept="image/*">
                    `;
                }
            }
        }
        
        // Character Counter for SEO Fields
        function updateCharCounter(input, counterId) {
            const counter = document.getElementById(counterId);
            const length = input.value.length;
            const maxLength = input.getAttribute('maxlength');
            
            counter.textContent = `${length}/${maxLength} characters`;
            
            if (length > maxLength * 0.9) {
                counter.classList.add('warning');
            } else {
                counter.classList.remove('warning');
            }
        }
        
        // Auto-generate slug from category name
        document.querySelector('input[name="categories"]').addEventListener('blur', function() {
            const slugInput = document.querySelector('input[name="slug_url"]');
            if (!slugInput.value) {
                const slug = this.value.toLowerCase()
                    .replace(/[^\w\s]/gi, '')
                    .replace(/\s+/g, '-')
                    .replace(/-+/g, '-');
                slugInput.value = slug;
                
                // Update preview
                document.querySelector('.slug-preview').textContent = 
                    '<?= $site ?>category/' + slug;
            }
        });
        
        // Form Validation
        document.getElementById('editSubCategoryForm').addEventListener('submit', function(e) {
            let valid = true;
            const requiredFields = this.querySelectorAll('[required]');
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    valid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });
            
            if (!valid) {
                e.preventDefault();
                alert('Please fill in all required fields.');
            }
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>