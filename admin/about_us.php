<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include "db-conn.php";

// Initialize variables
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // Handle section updates/inserts
        if (isset($_POST['sections'])) {
            foreach ($_POST['sections'] as $section_id => $section_data) {
                $title = trim($section_data['title']);
                $content = trim($section_data['content']);
                $section_order = intval($section_data['order']);
                
                // Validate required fields
                if (empty($title) || empty($content)) {
                    throw new Exception("Title and content are required for all sections");
                }

                // Handle file upload for this section
                $image_path = $section_data['current_image'] ?? '';
                
                if (isset($_FILES['sections']['tmp_name'][$section_id]['image'])) {
                    $file_tmp = $_FILES['sections']['tmp_name'][$section_id]['image'];
                    $file_error = $_FILES['sections']['error'][$section_id]['image'];
                    
                    if ($file_error === UPLOAD_ERR_OK) {
                        $upload_dir = 'uploads/about_us/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                        $file_type = $_FILES['sections']['type'][$section_id]['image'];

                        if (!in_array($file_type, $allowed_types)) {
                            throw new Exception("Only JPG, PNG, GIF, and WEBP images are allowed");
                        }
                        
                        $file_ext = pathinfo($_FILES['sections']['name'][$section_id]['image'], PATHINFO_EXTENSION);
                        $file_name = 'about-section-' . $section_id . '-' . time() . '.' . $file_ext;
                        $target_path = $upload_dir . $file_name;
                        
                        if (!move_uploaded_file($file_tmp, $target_path)) {
                            throw new Exception("Failed to upload image for section");
                        }
                        
                        // Delete old image if it exists
                        if (!empty($image_path)) {
                            if (file_exists($image_path)) {
                                unlink($image_path);
                            }
                        }
                        
                        $image_path = $target_path;
                    }
                }
                
                // Check if remove image checkbox is checked
                if (isset($section_data['remove_image']) && $section_data['remove_image'] == '1') {
                    if (!empty($image_path)) {
                        if (file_exists($image_path)) {
                            unlink($image_path);
                        }
                    }
                    $image_path = '';
                }
                
                if ($section_id == 'new') {
                    // Insert new section
                    $stmt = $conn->prepare("INSERT INTO about_sections (title, content, image_url, section_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                    $stmt->bind_param('sssi', $title, $content, $image_path, $section_order);
                } else {
                    // Update existing section
                    $stmt = $conn->prepare("UPDATE about_sections SET title = ?, content = ?, image_url = ?, section_order = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('sssii', $title, $content, $image_path, $section_order, $section_id);
                }
                
                if (!$stmt->execute()) {
                    throw new Exception("Database error: " . $conn->error);
                }
            }
        }
        
        // Handle section deletions
        if (isset($_POST['delete_sections'])) {
            foreach ($_POST['delete_sections'] as $section_id) {
                // Get section to delete (to remove image file)
                $sql = "SELECT image_url FROM about_sections WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $section_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $section = $result->fetch_assoc();
                
                // Delete image file if exists
                if (!empty($section['image_url'])) {
                    if (file_exists($section['image_url'])) {
                        unlink($section['image_url']);
                    }
                }
                
                // Delete section from database
                $stmt = $conn->prepare("DELETE FROM about_sections WHERE id = ?");
                $stmt->bind_param('i', $section_id);
                $stmt->execute();
            }
        }
        
        $success_message = "About Us sections saved successfully!";
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}

// Fetch all about sections ordered by section_order
$sections = [];
$sql = "SELECT * FROM about_sections ORDER BY section_order ASC";
$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $sections[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>About Us Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <link rel="stylesheet" href="assets/css/contact.css">
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
                    <div class="col-12">
                        <div class="page-header mb-4">
                            <div class="d-flex align-items-center justify-content-between">
                                <h2 class="mb-0">About Us Sections</h2>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-12">
                        <div class="about-form">
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= $success_message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($error_message)): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= $error_message ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <form method="post" enctype="multipart/form-data" id="sectionsForm">
                                <div id="sectionsContainer">
                                    <?php foreach ($sections as $index => $section): ?>
                                        <div class="section-card" data-id="<?= $section['id'] ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="mb-0 d-flex align-items-center">
                                                    <i class="fas fa-arrows-alt section-handle me-2"></i>
                                                    Section #<?= $index + 1 ?>
                                                </h5>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="delete_sections[]" value="<?= $section['id'] ?>" id="deleteSection<?= $section['id'] ?>">
                                                    <label class="form-check-label text-danger" for="deleteSection<?= $section['id'] ?>">
                                                        Delete Section
                                                    </label>
                                                </div>
                                            </div>
                                            
                                            <input type="hidden" name="sections[<?= $section['id'] ?>][current_image]" value="<?= htmlspecialchars($section['image_url']) ?>">
                                            
                                            <div class="row">
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Title <span class="text-danger">*</span></label>
                                                    <input type="text" class="form-control" name="sections[<?= $section['id'] ?>][title]" required value="<?= htmlspecialchars($section['title']) ?>">
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Content <span class="text-danger">*</span></label>
                                                    <textarea class="form-control" name="sections[<?= $section['id'] ?>][content]" rows="5" required><?= htmlspecialchars($section['content']) ?></textarea>
                                                </div>
                                                
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label">Section Order</label>
                                                    <input type="number" class="form-control" name="sections[<?= $section['id'] ?>][order]" value="<?= $section['section_order'] ?>">
                                                </div>
                                                
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Featured Image</label>
                                                    <div class="file-upload mb-3">
                                                        <label class="file-upload-label">
                                                            <i class="fas fa-cloud-upload-alt me-2"></i>Choose Image
                                                            <input type="file" name="sections[<?= $section['id'] ?>][image]" class="file-upload-input" accept="image/*">
                                                        </label>
                                                        <small class="d-block text-muted mt-1">Recommended size: 1200x800px (JPG, PNG, GIF, WEBP)</small>
                                                    </div>
                                                    
                                                    <?php if (!empty($section['image_url'])): ?>
                                                        <div class="current-image">
                                                            <p class="mb-2">Current Image:</p>
                                                            <img src="<?= $section['image_url'] ?>" alt="Current Section Image" class="image-preview">
                                                            <div class="form-check mt-2">
                                                                <input class="form-check-input" type="checkbox" name="sections[<?= $section['id'] ?>][remove_image]" value="1" id="removeImage<?= $section['id'] ?>">
                                                                <label class="form-check-label" for="removeImage<?= $section['id'] ?>">
                                                                    Remove current image
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <div class="text-center mt-4 mb-4">
                                    <button type="button" id="addSectionBtn" class="btn add-section-btn text-white">
                                        <i class="fas fa-plus me-2"></i> Add New Section
                                    </button>
                                </div>
                                
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="dashboard.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-2"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i> Save All Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <!-- Template for new section -->
    <template id="newSectionTemplate">
        <div class="section-card" data-id="new">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="fas fa-arrows-alt section-handle me-2"></i>
                    <span class="new-section-title">New Section</span>
                </h5>
                <button type="button" class="btn btn-sm btn-danger remove-section-btn">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="sections[new][title]" required>
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Content <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="sections[new][content]" rows="5" required></textarea>
                </div>
                
                <div class="col-md-6 mb-3">
                    <label class="form-label">Section Order</label>
                    <input type="number" class="form-control" name="sections[new][order]" value="0">
                </div>
                
                <div class="col-md-12 mb-3">
                    <label class="form-label">Featured Image</label>
                    <div class="file-upload mb-3">
                        <label class="file-upload-label">
                            <i class="fas fa-cloud-upload-alt me-2"></i>Choose Image
                            <input type="file" name="sections[new][image]" class="file-upload-input" accept="image/*">
                        </label>
                        <small class="d-block text-muted mt-1">Recommended size: 1200x800px (JPG, PNG, GIF, WEBP)</small>
                    </div>
                </div>
            </div>
        </div>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
    <script>
        // Initialize sortable for sections
        new Sortable(document.getElementById('sectionsContainer'), {
            handle: '.section-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: function() {
                // Update section order numbers based on their position
                document.querySelectorAll('#sectionsContainer .section-card').forEach((card, index) => {
                    const orderInput = card.querySelector('input[name$="[order]"]');
                    if (orderInput) {
                        orderInput.value = index + 1;
                    }
                    
                    // Update section number in title
                    const titleElement = card.querySelector('h5 span');
                    if (titleElement && !titleElement.classList.contains('new-section-title')) {
                        titleElement.textContent = `Section #${index + 1}`;
                    }
                });
            }
        });
        
        // Add new section
        document.getElementById('addSectionBtn').addEventListener('click', function() {
            const template = document.getElementById('newSectionTemplate');
            const clone = template.content.cloneNode(true);
            const container = document.getElementById('sectionsContainer');
            
            // Set order number for new section
            const sectionCount = container.querySelectorAll('.section-card').length;
            const orderInput = clone.querySelector('input[name$="[order]"]');
            if (orderInput) {
                orderInput.value = sectionCount + 1;
            }
            
            container.appendChild(clone);
            
            // Add event listener for remove button
            const newSection = container.lastElementChild;
            const removeBtn = newSection.querySelector('.remove-section-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    newSection.remove();
                });
            }
            
            // Initialize image preview for new section
            const fileInput = newSection.querySelector('.file-upload-input');
            if (fileInput) {
                fileInput.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            let preview = newSection.querySelector('.image-preview');
                            if (!preview) {
                                preview = document.createElement('img');
                                preview.className = 'image-preview';
                                fileInput.closest('.file-upload').after(preview);
                            }
                            preview.src = e.target.result;
                        }
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
        
        // Initialize image preview for existing sections
        document.querySelectorAll('.file-upload-input').forEach(input => {
            input.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const sectionCard = input.closest('.section-card');
                        let preview = sectionCard.querySelector('.image-preview');
                        if (!preview) {
                            preview = document.createElement('img');
                            preview.className = 'image-preview';
                            input.closest('.file-upload').after(preview);
                        }
                        preview.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        });
        
        // Confirm before submitting form with delete checkboxes checked
        document.getElementById('sectionsForm').addEventListener('submit', function(e) {
            const deleteCheckboxes = document.querySelectorAll('input[name^="delete_sections"]:checked');
            if (deleteCheckboxes.length > 0) {
                if (!confirm('Are you sure you want to delete the selected sections? This action cannot be undone.')) {
                    e.preventDefault();
                }
            }
        });
    </script>
</body>
</html>