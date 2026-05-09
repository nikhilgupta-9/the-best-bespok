<?php
include "db-conn.php";

// Enhanced security function for file validation
$pro_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if(isset($_GET['del_pro'])){
    // Validate and sanitize input
    $del_pro = intval($_GET['del_pro']);
    
    if($del_pro <= 0){
        die("Invalid image ID.");
    }
    
    // Check if image exists in database
    $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ?");
    $stmt->bind_param("i", $del_pro);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0){
        die("Image does not exist.");
    }
    
    $row = $result->fetch_assoc();
    $image_path = $row['image_path'];
    
    // Delete file from server
    $base_directory = "assets/img/uploads/";
    $full_path = $base_directory . $image_path;
    
    // Security: Prevent directory traversal
    $full_path = realpath($full_path);
    $base_directory_real = realpath($base_directory);
    
    if($full_path === false || strpos($full_path, $base_directory_real) !== 0) {
        die("Invalid file path.");
    }
    
    if(file_exists($full_path)){
        if(unlink($full_path)){
            // File deleted successfully, now remove from database
            $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
            $delete_stmt->bind_param("i", $del_pro);
            
            if($delete_stmt->execute()){
                echo "<script>alert('Image deleted successfully!'); window.location.href='show-products.php';</script>";
            } else {
                echo "<script>alert('Image removed from server but database deletion failed.');</script>";
            }
            $delete_stmt->close();
        } else {
            echo "<script>alert('Failed to delete file from server.');</script>";
        }
    } else {
        // File doesn't exist on server, but remove database record anyway
        $delete_stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
        $delete_stmt->bind_param("i", $del_pro);
        $delete_stmt->execute();
        $delete_stmt->close();
        
        echo "<script>alert('File not found on server, but database record removed.'); window.location.href='show-products.php';</script>";
    }
    
    $stmt->close();
}
function isFileSafe($tmp_name, $name) {
    // Check if file is actually an image
    $check = getimagesize($tmp_name);
    if($check === false) return false;
    
    // Whitelist allowed extensions and MIME types
    $allowedTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png', 
        'gif' => 'image/gif',
        'webp' => 'image/webp'
    ];
    
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if(!array_key_exists($ext, $allowedTypes)) return false;
    
    // Verify MIME type matches extension
    $detectedType = mime_content_type($tmp_name);
    if($allowedTypes[$ext] !== $detectedType) return false;
    
    // Check file size (max 10MB)
    if(filesize($tmp_name) > 10 * 1024 * 1024) return false;
    
    return true;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    
    // Validate product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE pro_id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows === 0) {
        die("Product does not exist.");
    }
    
    $product_row = $result->fetch_assoc();
    $internal_product_id = $product_row['id'];
    
    // Count existing images
    $count_stmt = $conn->prepare("SELECT COUNT(*) as image_count FROM product_images WHERE product_id = ?");
    $count_stmt->bind_param("i", $internal_product_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $image_count = $count_result->fetch_assoc()['image_count'];
    
    $uploaded_files = $_FILES['productImages1'];
    $success_count = 0;
    
    foreach($uploaded_files['tmp_name'] as $key => $tmp_name) {
        // Check maximum images limit
        if($image_count >= 5) {
            echo "<script>alert('Maximum 5 images allowed per product.'); window.history.back();</script>";
            break;
        }
        
        if(isFileSafe($tmp_name, $uploaded_files['name'][$key])) {
            $ext = strtolower(pathinfo($uploaded_files['name'][$key], PATHINFO_EXTENSION));
            $newFilename = bin2hex(random_bytes(8)) . '.' . $ext;
            $target_path = "assets/img/uploads/" . $newFilename;
            
            if(move_uploaded_file($tmp_name, $target_path)) {
                // Insert into database with prepared statement
                $insert_stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path, file_size, mime_type) VALUES (?, ?, ?, ?)");
                $insert_stmt->bind_param("isis", $internal_product_id, $newFilename, $uploaded_files['size'][$key], mime_content_type($tmp_name));
                
                if($insert_stmt->execute()) {
                    $success_count++;
                    $image_count++;
                }
                $insert_stmt->close();
            }
        }
    }
    
    if($success_count > 0) {
        echo "<script>alert('Successfully uploaded {$success_count} images!'); window.location.href='add-more-image.php?id={$product_id}';</script>";
    } else {
        echo "<script>alert('No images were uploaded. Please check file types and try again.'); window.history.back();</script>";
    }
    
    exit();
}
?>
<!DOCTYPE html>
<html lang="zxx">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Product Image Management</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .upload-area:hover, .upload-area.dragover {
            border-color: #007bff;
            background: #e7f3ff;
        }
        .image-preview-container {
            position: relative;
            transition: transform 0.2s ease;
        }
        .image-preview-container:hover {
            transform: scale(1.05);
        }
        .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        .image-preview-container:hover .delete-btn {
            opacity: 1;
        }
        .progress {
            height: 8px;
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

        <div class="main_content_iner ">
            <div class="container-fluid p-3">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="mb-0 fw-bold">Manage Product Images</h2>
                                        <p class="text-muted mb-0">Upload and manage product gallery images</p>
                                    </div>
                                </div>
                            </div>
                            <div class="white_card_body">
                                <!-- Upload Section -->
                                <div class="row mb-5">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 class="mb-0"><i class="ti-cloud-up me-2"></i>Upload New Images</h5>
                                            </div>
                                            <div class="card-body">
                                                <form action="upload.php" method="POST" enctype="multipart/form-data" id="uploadForm">
                                                    <input type="hidden" name="product_id" value="<?=$pro_id?>">
                                                    
                                                    <div class="upload-area border-dashed p-5 text-center mb-3" id="uploadArea">
                                                        <i class="ti-cloud-up display-4 text-muted d-block mb-3"></i>
                                                        <h5>Drag & Drop your images here</h5>
                                                        <p class="text-muted">or click to browse</p>
                                                        <small class="text-muted d-block">Supports JPG, PNG, GIF, WEBP (Max 5 files total, 10MB each)</small>
                                                        
                                                        <input type="file" class="form-control d-none" name="productImages1[]" id="productImages" 
                                                               multiple accept="image/jpeg,image/png,image/gif,image/webp" />
                                                    </div>
                                                    
                                                    <!-- Image Preview -->
                                                    <div class="row g-3 mt-3" id="imagePreview"></div>
                                                    
                                                    <!-- Upload Feedback -->
                                                    <div class="alert alert-info mt-3 d-none" id="uploadFeedback">
                                                        <div class="d-flex align-items-center">
                                                            <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                            <span>Ready to upload <span id="fileCount">0</span> files</span>
                                                        </div>
                                                        <div class="progress mt-2">
                                                            <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                                                 role="progressbar" style="width: 0%"></div>
                                                        </div>
                                                    </div>
                                                    
                                                    <button type="submit" class="btn btn-primary mt-3" id="uploadBtn" disabled>
                                                        <i class="ti-upload me-2"></i>Upload Images
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Existing Images Section -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0"><i class="ti-gallery me-2"></i>Existing Product Images</h5>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $product_id = 1; // This should be dynamic
                                                $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY uploaded_at DESC";
                                                $stmt = $conn->prepare($query);
                                                $stmt->bind_param("i", $pro_id);
                                                $stmt->execute();
                                                $result = $stmt->get_result();
                                                
                                                if ($result && $result->num_rows > 0) {
                                                    echo '<div class="table-responsive">';
                                                    echo '<table class="table table-hover">';
                                                    echo '<thead class="table-light">';
                                                    echo '<tr><th>#</th><th>Preview</th><th>Filename</th><th>Size</th><th>Uploaded</th><th>Actions</th></tr>';
                                                    echo '</thead><tbody>';
                                                    
                                                    $sno = 1;
                                                    while ($row = $result->fetch_assoc()) {
                                                        $image_id = $row['id'];
                                                        $image_path = htmlspecialchars($row['image_path']);
                                                        $file_size = round($row['file_size'] / 1024, 2); // Convert to KB
                                                        $uploaded_at = date('M j, Y g:i A', strtotime($row['uploaded_at']));
                                                        
                                                        echo "<tr>";
                                                        echo "<td>{$sno}</td>";
                                                        echo "<td>";
                                                        echo "<img src='assets/img/uploads/{$image_path}' class='img-thumbnail' 
                                                                  style='width: 80px; height: 80px; object-fit: cover; cursor: pointer;' 
                                                                  data-bs-toggle='modal' data-bs-target='#imageModal' 
                                                                  onclick='openImageModal(\"assets/img/uploads/{$image_path}\")'>";
                                                        echo "</td>";
                                                        echo "<td class='text-truncate' style='max-width: 200px;'>{$image_path}</td>";
                                                        echo "<td>{$file_size} KB</td>";
                                                        echo "<td>{$uploaded_at}</td>";
                                                        echo "<td>";
                                                        echo "<a class='btn btn-sm btn-outline-danger' href='multiple_img.php?del_pro={$image_id}'  onclick='return confirm(\"Are you sure you want to delete this image?\")>";
                                                        echo "<i class='ti-trash me-1'></i>Delete";
                                                        echo "</a>";    
                                                        echo "</td>";
                                                        echo "</tr>";
                                                        $sno++;
                                                    }
                                                    echo '</tbody></table></div>';
                                                } else {
                                                    echo '<div class="text-center py-4">';
                                                    echo '<i class="ti-gallery display-4 text-muted d-block mb-3"></i>';
                                                    echo '<h5 class="text-muted">No images uploaded yet</h5>';
                                                    echo '<p class="text-muted">Upload your first product image using the form above.</p>';
                                                    echo '</div>';
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Image Modal -->
        <div class="modal fade" id="imageModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Image Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <img src="" id="modalImage" class="img-fluid" style="max-height: 70vh;">
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <script>
        // Enhanced JavaScript for better UX
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('productImages');
        const imagePreview = document.getElementById('imagePreview');
        const uploadFeedback = document.getElementById('uploadFeedback');
        const fileCount = document.getElementById('fileCount');
        const uploadBtn = document.getElementById('uploadBtn');
        const uploadForm = document.getElementById('uploadForm');

        // Drag and drop functionality
        uploadArea.addEventListener('click', () => fileInput.click());
        
        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            fileInput.files = e.dataTransfer.files;
            handleFileSelection();
        });

        fileInput.addEventListener('change', handleFileSelection);

        function handleFileSelection() {
            const files = fileInput.files;
            imagePreview.innerHTML = '';
            uploadFeedback.classList.add('d-none');
            uploadBtn.disabled = true;

            if(files.length > 5) {
                alert('Maximum 5 files allowed. Please select fewer files.');
                fileInput.value = '';
                return;
            }

            let validFiles = 0;

            for(let i = 0; i < files.length; i++) {
                if(!files[i].type.match('image.*')) {
                    alert('Only image files are allowed.');
                    fileInput.value = '';
                    return;
                }

                if(files[i].size > 10 * 1024 * 1024) {
                    alert('File size must be less than 10MB.');
                    fileInput.value = '';
                    return;
                }

                validFiles++;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    const col = document.createElement('div');
                    col.className = 'col-md-3 col-6';
                    col.innerHTML = `
                        <div class="image-preview-container">
                            <img src="${e.target.result}" class="img-thumbnail w-100" 
                                 style="height: 120px; object-fit: cover;">
                            <button type="button" class="btn btn-sm btn-danger delete-btn" 
                                    onclick="this.parentElement.parentElement.remove(); updateFileInput();">
                                <i class="ti-trash"></i>
                            </button>
                        </div>
                    `;
                    imagePreview.appendChild(col);
                }
                reader.readAsDataURL(files[i]);
            }

            if(validFiles > 0) {
                fileCount.textContent = validFiles;
                uploadFeedback.classList.remove('d-none');
                uploadBtn.disabled = false;
            }
        }

        function updateFileInput() {
            // This would require more complex logic to actually update the file input
            // For simplicity, we'll just enable the button if there are previews
            uploadBtn.disabled = imagePreview.children.length === 0;
        }

        function openImageModal(src) {
            document.getElementById('modalImage').src = src;
        }

        // Form submission feedback
        uploadForm.addEventListener('submit', function() {
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="ti-loading me-2"></i>Uploading...';
        });
    </script>
</body>
</html>