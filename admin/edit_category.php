<?php
session_start();
include "db-conn.php";
include "functions.php";

// Check if category ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "Invalid category ID";
    header("Location: view-categories.php");
    exit();
}

$cat_id = mysqli_real_escape_string($conn, $_GET['id']);

// Fetch category details from database - Updated column names
$sql = "SELECT * FROM categories WHERE id = '$cat_id'";
$result = mysqli_query($conn, $sql);
$category = mysqli_fetch_assoc($result);

if (!$category) {
    $_SESSION['error'] = "Category not found";
    header("Location: view-categories.php");
    exit();
}

// Fetch parent categories for dropdown
$parent_sql = "SELECT id, name FROM categories WHERE type = 'product' AND status = 1 AND id != '$cat_id' ORDER BY name";
$parent_result = mysqli_query($conn, $parent_sql);

// Process form submission for updating the category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_category'])) {
    $parent_id = mysqli_real_escape_string($conn, $_POST['parent_id'] ?? 0);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url']);
    $display_order = mysqli_real_escape_string($conn, $_POST['display_order'] ?? 0);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title'] ?? '');
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc'] ?? '');
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key'] ?? '');

    // Handle image upload
    $image = $category['image']; // Keep existing image

    if (isset($_FILES['imageUpload']['name']) && !empty($_FILES['imageUpload']['name'])) {
        $filename = $_FILES['imageUpload']['name'];
        $tempname = $_FILES['imageUpload']['tmp_name'];

        // Create uploads directory if it doesn't exist
        $folder = 'uploads/category/';
        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        // Delete old image if exists
        if (!empty($category['image']) && file_exists($folder . $category['image'])) {
            unlink($folder . $category['image']);
        }

        // Generate unique filename
        $file_extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $uniqueName = uniqid() . '_' . time() . '.' . $file_extension;
        $destination = $folder . $uniqueName;

        if (move_uploaded_file($tempname, $destination)) {
            $image = $uniqueName;
        }
    }

    // Update category in database - Updated column names
    $update_sql = "UPDATE categories SET 
                   parent_id = '$parent_id',
                   name = '$name',
                   type = '$type',
                   description = '$description',
                   slug_url = '$slug_url',
                   image = '$image',
                   display_order = '$display_order',
                   status = '$status',
                   meta_title = '$meta_title',
                   meta_desc = '$meta_desc',
                   meta_key = '$meta_key'
                   WHERE id = '$cat_id'";

    if (mysqli_query($conn, $update_sql)) {
        $_SESSION['success'] = "Category updated successfully!";
        header("Location: view-categories.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update category: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit Category | Admin Dashboard</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
    
    <style>
        .image-preview-container {
            margin-top: 10px;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 5px;
            display: none;
        }
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 5px;
            object-fit: cover;
        }
        .featured-image-container {
            position: relative;
            display: inline-block;
        }
        .remove-image-btn {
            position: absolute;
            top: -10px;
            right: -10px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            display: none;
            cursor: pointer;
            font-size: 14px;
            line-height: 1;
        }
        .remove-image-btn:hover {
            background: #dc2626;
        }
        .form-section {
            background: #f8fafc;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .form-section-title {
            font-size: 16px;
            font-weight: 600;
            color: #334155;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e2e8f0;
        }
        .slug-preview {
            background: #f1f5f9;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 14px;
            color: #475569;
            margin-top: 5px;
        }
        .badge-category-type {
            padding: 5px 10px;
            border-radius: 6px;
            font-size: 12px;
        }
        .type-product {
            background: #dbeafe;
            color: #1e40af;
        }
        .type-post {
            background: #dcfce7;
            color: #166534;
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
                            <!-- Header -->
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1 fw-bold"><i class="fas fa-edit me-2"></i>Edit Category</h2>
                                        <p class="text-muted mb-0 small">Update category information and settings</p>
                                    </div>
                                    <div>
                                        <a href="view-categories.php" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-arrow-left me-2"></i>Back to List
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Success/Error Messages -->
                            <?php if (isset($_SESSION['success'])): ?>
                                <div class="alert alert-success alert-dismissible fade show mx-4 mt-3 mb-0" role="alert">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['success']); ?>
                            <?php endif; ?>
                            
                            <?php if (isset($_SESSION['error'])): ?>
                                <div class="alert alert-danger alert-dismissible fade show mx-4 mt-3 mb-0" role="alert">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $_SESSION['error']; ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                                <?php unset($_SESSION['error']); ?>
                            <?php endif; ?>

                            <div class="white_card_body">
                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <!-- Left Column - Main Information -->
                                        <div class="col-lg-8">
                                            <!-- Basic Information Section -->
                                            <div class="form-section">
                                                <h5 class="form-section-title">
                                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                                </h5>
                                                
                                                <!-- Category Name -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Category Name *</label>
                                                    <input type="text" class="form-control" name="name" 
                                                           value="<?= htmlspecialchars($category['name']) ?>" 
                                                           required placeholder="Enter category name">
                                                    <div class="form-text">This will be displayed on your website</div>
                                                </div>
                                                
                                                <!-- Parent Category -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Parent Category</label>
                                                    <select class="form-select" name="parent_id">
                                                        <option value="0">-- None (Top Level) --</option>
                                                        <?php while ($parent = mysqli_fetch_assoc($parent_result)): ?>
                                                            <option value="<?= $parent['id'] ?>" 
                                                                <?= $category['parent_id'] == $parent['id'] ? 'selected' : '' ?>>
                                                                <?= htmlspecialchars($parent['name']) ?>
                                                            </option>
                                                        <?php endwhile; ?>
                                                    </select>
                                                    <div class="form-text">Select parent category if this is a subcategory</div>
                                                </div>
                                                
                                                <!-- Category Type -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Category Type *</label>
                                                    <select class="form-select" name="type" required>
                                                        <option value="product" <?= $category['type'] == 'product' ? 'selected' : '' ?>>Product Category</option>
                                                        <option value="post" <?= $category['type'] == 'post' ? 'selected' : '' ?>>Blog/Post Category</option>
                                                    </select>
                                                    <div class="form-text">Product categories are for store, Post categories for blog</div>
                                                </div>
                                                
                                                <!-- Description -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Description</label>
                                                    <textarea class="form-control" name="description" rows="4" 
                                                              placeholder="Enter category description"><?= htmlspecialchars($category['description']) ?></textarea>
                                                    <div class="form-text">Brief description of this category</div>
                                                </div>
                                                
                                                <!-- Slug URL -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Slug URL *</label>
                                                    <input type="text" class="form-control" name="slug_url" 
                                                           value="<?= htmlspecialchars($category['slug_url']) ?>" 
                                                           required placeholder="category-name-url">
                                                    <div class="slug-preview">
                                                        <i class="fas fa-link me-1"></i>
                                                        URL Preview: <?= isset($site) ? $site : '' ?>category/<?= htmlspecialchars($category['slug_url']) ?>
                                                    </div>
                                                    <div class="form-text">SEO-friendly URL for this category</div>
                                                </div>
                                                
                                                <!-- Display Order -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Display Order</label>
                                                    <input type="number" class="form-control" name="display_order" 
                                                           value="<?= $category['display_order'] ?? 0 ?>" 
                                                           placeholder="0">
                                                    <div class="form-text">Order in which categories are displayed (lower numbers first)</div>
                                                </div>
                                                
                                                <!-- Status -->
                                                <div class="mb-4">
                                                    <label class="form-label fw-semibold">Status *</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="1" <?= $category['status'] == '1' ? 'selected' : '' ?>>Active</option>
                                                        <option value="0" <?= $category['status'] == '0' ? 'selected' : '' ?>>Inactive</option>
                                                    </select>
                                                    <div class="form-text">Active categories are visible on the website</div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Right Column - Image & Meta -->
                                        <div class="col-lg-4">
                                            <!-- Current Image Section -->
                                            <div class="form-section">
                                                <h5 class="form-section-title">
                                                    <i class="fas fa-image me-2"></i>Category Image
                                                </h5>
                                                
                                                <!-- Current Image -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Current Image</label>
                                                    <?php if (!empty($category['image'])): ?>
                                                        <div class="featured-image-container">
                                                            <img src="uploads/category/<?= htmlspecialchars($category['image']) ?>" 
                                                                 class="current-image" 
                                                                 alt="<?= htmlspecialchars($category['name']) ?>"
                                                                 onerror="this.src='assets/img/default-category.jpg'">
                                                        </div>
                                                        <div class="form-text mt-2">
                                                            <small class="text-muted">Upload new image to replace this</small>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="text-center py-4 border rounded bg-light">
                                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                                            <p class="text-muted small mb-0">No image uploaded</p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- New Image Upload -->
                                                <div class="mb-3">
                                                    <label class="form-label fw-semibold">Upload New Image</label>
                                                    <input type="file" class="form-control" name="imageUpload" 
                                                           id="imageUpload" accept="image/*" onchange="previewImage(this)">
                                                    <img id="imagePreview" class="image-preview mt-2" src="#" alt="Preview">
                                                    <div class="form-text mt-2">
                                                        <small>Recommended: 300x300px, Max 2MB (JPG, PNG, GIF, WEBP)</small>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- SEO Section -->
                                            <div class="form-section">
                                                <h5 class="form-section-title">
                                                    <i class="fas fa-search me-2"></i>SEO Settings
                                                </h5>
                                                
                                                <!-- Meta Title -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Meta Title</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="meta_title" 
                                                           value="<?= htmlspecialchars($category['meta_title'] ?? '') ?>"
                                                           placeholder="Meta title for search engines">
                                                    <div class="form-text small">Max 60 characters recommended</div>
                                                </div>
                                                
                                                <!-- Meta Description -->
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Meta Description</label>
                                                    <textarea class="form-control form-control-sm" name="meta_desc" 
                                                              rows="3" placeholder="Meta description for search engines"><?= htmlspecialchars($category['meta_desc'] ?? '') ?></textarea>
                                                    <div class="form-text small">Max 160 characters recommended</div>
                                                </div>
                                                
                                                <!-- Meta Keywords -->
                                                <div class="mb-0">
                                                    <label class="form-label small fw-semibold">Meta Keywords</label>
                                                    <input type="text" class="form-control form-control-sm" 
                                                           name="meta_key" 
                                                           value="<?= htmlspecialchars($category['meta_key'] ?? '') ?>"
                                                           placeholder="keyword1, keyword2, keyword3">
                                                    <div class="form-text small">Comma separated keywords</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Action Buttons -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center border-top pt-4">
                                                <a href="view-categories.php" class="btn btn-outline-secondary">
                                                    <i class="fas fa-times me-2"></i>Cancel
                                                </a>
                                                <div>
                                                    <button type="submit" name="update_category" class="btn btn-primary">
                                                        <i class="fas fa-save me-2"></i>Update Category
                                                    </button>
                                                </div>
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

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Image preview functionality
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Auto-generate slug from category name
        document.addEventListener('DOMContentLoaded', function() {
            const categoryInput = document.querySelector('input[name="name"]');
            const slugInput = document.querySelector('input[name="slug_url"]');
            
            if (categoryInput && slugInput) {
                categoryInput.addEventListener('blur', function() {
                    if (!slugInput.value.trim()) {
                        const slug = this.value
                            .toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/\s+/g, '-')
                            .replace(/-+/g, '-')
                            .trim();
                        slugInput.value = slug;
                        
                        // Update preview
                        const previewElement = document.querySelector('.slug-preview');
                        if (previewElement) {
                            previewElement.innerHTML = '<i class="fas fa-link me-1"></i> URL Preview: <?= isset($site) ? $site : '' ?>category/' + slug;
                        }
                    }
                });
                
                // Slug input validation
                slugInput.addEventListener('input', function() {
                    this.value = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9\-]/g, '-')
                        .replace(/-+/g, '-')
                        .replace(/^-|-$/g, '');
                    
                    // Update preview
                    const previewElement = document.querySelector('.slug-preview');
                    if (previewElement) {
                        previewElement.innerHTML = '<i class="fas fa-link me-1"></i> URL Preview: <?= isset($site) ? $site : '' ?>category/' + this.value;
                    }
                });
            }
            
            // Form validation
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const categoryName = document.querySelector('input[name="name"]').value.trim();
                    
                    if (!categoryName) {
                        e.preventDefault();
                        alert('Please enter category name');
                        return false;
                    }
                    
                    const slug = document.querySelector('input[name="slug_url"]').value.trim();
                    if (!slug) {
                        e.preventDefault();
                        alert('Please enter slug URL');
                        return false;
                    }
                    
                    return true;
                });
            }
        });
    </script>
