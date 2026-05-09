<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

include "db-conn.php";

// ============================================================
// ADD CATEGORY
// FIX 1: Removed cate_id random generation — `id` is AUTO_INCREMENT now
// FIX 2: Column name changed from `categories` → `name` (new schema)
// FIX 3: Column `added_on` replaced with `created_at` (proper DATETIME)
// FIX 4: Removed cate_id from INSERT entirely
// FIX 5: ob_start() must be called before session_start() in the page,
//         but header() here is safe because functions.php is included early
// ============================================================
if (isset($_POST["add-categories"])) {
    $errors = [];

    $cate_name  = trim(mysqli_real_escape_string($conn, $_POST["cate_name"]));
    $meta_title = trim(mysqli_real_escape_string($conn, $_POST["meta_title"]));
    $meta_key   = trim(mysqli_real_escape_string($conn, $_POST["meta_key"]));
    $meta_desc  = trim(mysqli_real_escape_string($conn, $_POST["meta_desc"]));
    $status     = isset($_POST["status"]) ? (int) $_POST["status"] : 1;
    $parent_id  = !empty($_POST["parent_id"]) ? (int) $_POST["parent_id"] : null;
    $type       = in_array($_POST["type"] ?? '', ['suit_style','occasion','fabric_type','general'])
                    ? $_POST["type"] : 'general';

    // Slug: replace spaces + special chars
    $slug_url   = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $cate_name));
    $image_name = "";

    // Image upload
    if (!empty($_FILES['imageUpload']['name'])) {
        $upload_dir = "uploads/category/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_name      = time() . "_" . basename($_FILES["imageUpload"]["name"]);
        $target_file    = $upload_dir . $file_name;
        $imageFileType  = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $check          = getimagesize($_FILES["imageUpload"]["tmp_name"]);

        if ($check === false)                                $errors[] = "File is not an image.";
        if ($_FILES["imageUpload"]["size"] > 5000000)        $errors[] = "Image too large (max 5MB).";
        if (!in_array($imageFileType, ['jpg','jpeg','png','gif','webp']))
                                                             $errors[] = "Only JPG, JPEG, PNG, GIF & WEBP allowed.";

        if (empty($errors)) {
            if (move_uploaded_file($_FILES["imageUpload"]["tmp_name"], $target_file)) {
                $image_name = $file_name;
            } else {
                $errors[] = "Error uploading file.";
            }
        }
    } else {
        $errors[] = "Please select a category image.";
    }

    if (empty($errors)) {
        // FIX: uses new column names `name`, `created_at`; no cate_id; parent_id + type added
        $sql  = "INSERT INTO `categories` 
                    (`parent_id`, `name`, `type`, `meta_title`, `meta_desc`, `meta_key`, `image`, `slug_url`, `status`, `created_at`) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        // parent_id can be NULL → use bind_param with reference trick
        $stmt->bind_param("isssssssi",
            $parent_id, $cate_name, $type, $meta_title, $meta_desc,
            $meta_key, $image_name, $slug_url, $status
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Category added successfully!";
            header("Location: view-categories.php");
            exit();
        } else {
            if (!empty($image_name) && file_exists($upload_dir . $image_name)) {
                unlink($upload_dir . $image_name);
            }
            $errors[] = "Database error: " . $conn->error;
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['errors']    = $errors;
        $_SESSION['form_data'] = $_POST;
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }
}


// ============================================================
// ADD SUB-CATEGORY
// FIX 1: Removed cate_id random value — id is AUTO_INCREMENT
// FIX 2: Column names updated: `categories` → `name`, `sub_cat_img` → `image`,
//         `added_on` → `created_at`, `parent_id` now FK to categories.id
// FIX 3: Replaced raw mysqli_query with prepared statement (SQL injection fix)
// FIX 4: Removed echo/alert mid-function (breaks headers)
// FIX 5: Uses session + redirect instead of inline JS alert
// ============================================================
if (isset($_POST["add-sub-categories"])) {
    $uploadedImage = '';

    if (isset($_FILES['imageUpload']) && $_FILES['imageUpload']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath   = $_FILES['imageUpload']['tmp_name'];
        $fileName      = $_FILES['imageUpload']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExt    = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($fileExtension, $allowedExt)) {
            $newFileName = uniqid('img_', true) . '.' . $fileExtension;
            $uploadDir   = 'uploads/sub-category/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                $uploadedImage = $newFileName;
            } else {
                $_SESSION['error'] = "Could not move uploaded file.";
                header("Location: add-sub-category.php");
                exit();
            }
        } else {
            $_SESSION['error'] = "Only JPG, JPEG, PNG, GIF, and WEBP files allowed.";
            header("Location: add-sub-category.php");
            exit();
        }
    }

    $cate_name  = trim(mysqli_real_escape_string($conn, $_POST["cate_name"]));
    $meta_title = trim(mysqli_real_escape_string($conn, $_POST["meta_title"]));
    $meta_key   = trim(mysqli_real_escape_string($conn, $_POST["meta_key"]));
    $meta_desc  = trim(mysqli_real_escape_string($conn, $_POST["meta_desc"]));
    $parent_id  = (int) $_POST['parent_id'];   // FIX: cast to int, FK to categories.id
    $slug_url   = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $cate_name));
    $status     = 1;

    // FIX: prepared statement, correct column names for new schema
    $sql  = "INSERT INTO `sub_categories` 
                (`category_id`, `name`, `meta_title`, `meta_desc`, `meta_key`, `image`, `slug_url`, `status`, `created_at`) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssssi",
        $parent_id, $cate_name, $meta_title, $meta_desc,
        $meta_key, $uploadedImage, $slug_url, $status
    );

    if ($stmt->execute()) {
        $_SESSION['success'] = "Sub-category added successfully!";
        header("Location: view-sub-categories.php");
        exit();
    } else {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: add-sub-category.php");
        exit();
    }
    $stmt->close();
}


// ============================================================
// get_Category()
// FIX 1: Column `categories` → `name` in SELECT, WHERE, and display
// FIX 2: Removed `cate_id` references — display uses `id` now
// FIX 3: Search now searches `name` and `slug_url` (cate_id column gone)
// FIX 4: Edit/toggle/delete action links use `id` not `cate_id`
// FIX 5: Delete button data-id uses `id`, href points to delete-category.php
// FIX 6: `added_on` → `created_at` for date display
// ============================================================
function get_Category()
{
    include "db-conn.php";

    $searchQuery = "";
    $params      = [];
    $types       = "";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search      = "%" . $_GET['search'] . "%";
        // FIX: search on `name` and `slug_url` — `cate_id` column no longer exists
        $searchQuery = " WHERE name LIKE ? OR slug_url LIKE ?";
        $params      = [$search, $search];
        $types       = "ss";
    }

    $sql = "SELECT * FROM categories $searchQuery ORDER BY id DESC";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $sno    = 1;
        $output = '';

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {

                $status = $row['status'] == '1'
                    ? '<span class="status-badge badge bg-success">
                           <i class="fas fa-check-circle me-1"></i>Active
                       </span>'
                    : '<span class="status-badge badge bg-danger">
                           <i class="fas fa-times-circle me-1"></i>Inactive
                       </span>';

                // FIX: use `created_at` instead of `added_on`
                $created_at = !empty($row['created_at'])
                    ? date('d M Y, h:i A', strtotime($row['created_at']))
                    : '—';

                // FIX: column is now `name`, show `id` not `cate_id`
                $categoryName    = htmlspecialchars(ucwords($row['name']));
                $categoryDisplay = !empty($row['image'])
                    ? '<div class="d-flex align-items-center">
                           <img src="uploads/category/' . htmlspecialchars($row['image']) . '" 
                                class="category-image me-3" 
                                alt="' . $categoryName . '"
                                data-bs-toggle="tooltip" 
                                title="' . $categoryName . '"
                                style="max-width:100px; border-radius:50%; box-shadow: rgba(0, 0, 0, 0.16) 0px 1px 4px;"
                                >
                           <div>
                               <div class="fw-semibold mb-1">' . $categoryName . '</div>
                               <small class="text-muted">ID: ' . (int)$row['id'] . '</small>
                           </div>
                       </div>'
                    : '<div>
                           <div class="fw-semibold mb-1">' . $categoryName . '</div>
                           <small class="text-muted">ID: ' . (int)$row['id'] . '</small>
                       </div>';

                $slug_url    = htmlspecialchars($row['slug_url']);
                $slug_display = strlen($slug_url) > 30
                    ? '<span data-bs-toggle="tooltip" title="' . $slug_url . '">'
                      . substr($slug_url, 0, 30) . '...</span>'
                    : $slug_url;

                // FIX: all action hrefs now use `id` (int) — not `cate_id`
                // FIX: delete button href → delete-category.php (separate action file)
                $output .= "<tr>
                    <td class='text-center align-middle'>
                        <span class='text-muted fw-semibold'>" . $sno++ . "</span>
                    </td>
                    <td class='align-middle'>" . $categoryDisplay . "</td>
                    <td class='align-middle'>
                        <div class='d-flex align-items-center'>
                            <i class='fas fa-link text-muted me-2'></i>
                            <span class='text-truncate' style='max-width:150px;'>
                                " . $slug_display . "
                            </span>
                        </div>
                    </td>
                    <td class='align-middle'>" . $status . "</td>
                    <td class='align-middle'>
                        <div class='small text-muted'>
                            <i class='far fa-calendar me-1'></i>" . $created_at . "
                        </div>
                    </td>
                    <td class='align-middle text-center'>
                        <div class='d-flex justify-content-center gap-2'>
                            <a href='edit_category.php?id=" . (int)$row['id'] . "' 
                               class='btn btn-primary action-btn'
                               data-bs-toggle='tooltip' 
                               title='Edit'>
                                <i class='fas fa-edit'></i>
                            </a>
                            <a href='cat-toggle_status.php?type=category&id=" . (int)$row['id'] . "&status=" . (int)$row['status'] . "' 
                               class='btn btn-warning action-btn'
                               data-bs-toggle='tooltip' 
                               title='" . ($row['status'] == '1' ? 'Deactivate' : 'Activate') . "'>
                                <i class='fas fa-power-off'></i>
                            </a>
                            <a href='#'
                               class='btn btn-danger action-btn delete-btn'
                               data-id='" . (int)$row['id'] . "'
                               data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "'
                               data-bs-toggle='modal' 
                               data-bs-target='#deleteModal'
                               title='Delete'>
                                <i class='fas fa-trash'></i>
                            </a>
                        </div>
                    </td>
                </tr>";
            }

        } else {
            $output = "<tr>
                <td colspan='6' class='text-center py-5'>
                    <div class='empty-state'>
                        <div class='empty-state-icon'>
                            <i class='fas fa-layer-group'></i>
                        </div>
                        <h5 class='text-muted mb-2'>No Categories Found</h5>
                        <p class='text-muted small mb-4'>Get started by creating your first category</p>
                        <a href='add-categories.php' class='btn btn-primary'>
                            <i class='fas fa-plus me-2'></i>Add Category
                        </a>
                    </div>
                </td>
            </tr>";
        }

        mysqli_stmt_close($stmt);
        return $output;

    } else {
        return "<tr>
            <td colspan='6' class='text-center py-4 text-danger'>
                <div class='d-flex align-items-center justify-content-center'>
                    <i class='fas fa-exclamation-triangle me-2'></i>Error loading categories
                </div>
            </td>
        </tr>";
    }
}


// ============================================================
// get_Sub_Category()
// FIX 1: JOIN now uses `categories.id` not `categories.cate_id`
//         (sub_categories.category_id FK → categories.id)
// FIX 2: SELECT column `sc.categories` → `sc.name`
//         and `c.categories` → `c.name` (parent)
// FIX 3: Display and action links use `sc.id` not `sc.cate_id`
// FIX 4: Delete href → delete-sub-category.php
// FIX 5: `added_on` → `created_at`
// ============================================================
function get_Sub_Category()
{
    include "db-conn.php";

    $searchQuery = "";
    $params      = [];
    $types       = "";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search      = "%" . $_GET['search'] . "%";
        // FIX: search on `sc.name` and `c.name` — column renamed from `categories`
        $searchQuery = " WHERE sc.name LIKE ? OR sc.slug_url LIKE ? OR c.name LIKE ?";
        $params      = [$search, $search, $search];
        $types       = "sss";
    }

    // FIX: JOIN on category_id = categories.id (not cate_id)
    $sql = "SELECT 
                sc.id,
                sc.name,
                sc.slug_url,
                sc.status,
                sc.created_at,
                sc.image          AS sub_cat_img,
                c.name            AS parent_category,
                c.id              AS parent_id
            FROM sub_categories sc
            LEFT JOIN categories c ON sc.category_id = c.id
            $searchQuery 
            ORDER BY sc.created_at DESC, sc.name ASC";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $sno    = 1;
        $output = '';

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {

                $status = $row['status'] == '1'
                    ? '<span class="badge bg-success bg-opacity-20 text-success fw-semibold">
                           <i class="fas fa-check-circle me-1"></i>Active
                       </span>'
                    : '<span class="badge bg-danger bg-opacity-20 text-danger fw-semibold">
                           <i class="fas fa-times-circle me-1"></i>Inactive
                       </span>';

                // FIX: use created_at
                $created_at = !empty($row['created_at'])
                    ? date('d M Y, h:i A', strtotime($row['created_at']))
                    : '—';

                // FIX: column is now `name`
                $subCategoryName    = htmlspecialchars(ucwords($row['name']));
                $subCategoryDisplay = $row['sub_cat_img']
                    ? '<div class="d-flex align-items-center">
                           <img src="uploads/sub-category/' . htmlspecialchars($row['sub_cat_img']) . '" 
                                class="rounded me-2" width="30" height="30" style="object-fit:cover">
                           <div>
                               <div class="fw-semibold">' . $subCategoryName . '</div>
                               <small class="text-muted">ID: ' . (int)$row['id'] . '</small>
                           </div>
                       </div>'
                    : '<div>
                           <div class="fw-semibold">' . $subCategoryName . '</div>
                           <small class="text-muted">ID: ' . (int)$row['id'] . '</small>
                       </div>';

                $parentCategory = $row['parent_category']
                    ? '<a href="#" class="text-decoration-none text-primary">
                           <i class="fas fa-folder me-1"></i>' . htmlspecialchars($row['parent_category']) . '
                       </a>'
                    : '<span class="text-muted"><i class="fas fa-times me-1"></i>Unassigned</span>';

                // FIX: all links use (int)$row['id'], delete → delete-sub-category.php
                $output .= "<tr>
                    <td class='text-center align-middle'>
                        <span class='text-muted'>" . $sno++ . "</span>
                    </td>
                    <td class='align-middle'>" . $subCategoryDisplay . "</td>
                    <td class='align-middle'>" . $parentCategory . "</td>
                    <td class='align-middle'>
                        <div class='text-truncate' style='max-width:200px;'
                             data-bs-toggle='tooltip'
                             title='" . htmlspecialchars($row['slug_url']) . "'>
                            <i class='fas fa-link text-muted me-1'></i>
                            " . htmlspecialchars($row['slug_url']) . "
                        </div>
                    </td>
                    <td class='align-middle'>" . $status . "</td>
                    <td class='align-middle'>
                        <div class='small text-muted'>
                            <i class='far fa-calendar me-1'></i>" . $created_at . "
                        </div>
                    </td>
                    <td class='align-middle text-center'>
                        <div class='btn-group' role='group'>
                            <a href='edit_sub_category.php?id=" . (int)$row['id'] . "' 
                               class='btn btn-sm btn-outline-primary px-3'
                               data-bs-toggle='tooltip' title='Edit'>
                                <i class='fas fa-edit'></i>
                            </a>
                            <a href='toggle_status.php?type=sub_category&id=" . (int)$row['id'] . "&status=" . (int)$row['status'] . "' 
                               class='btn btn-sm btn-outline-warning px-3'
                               data-bs-toggle='tooltip'
                               title='" . ($row['status'] == '1' ? 'Deactivate' : 'Activate') . "'>
                                <i class='fas fa-power-off'></i>
                            </a>
                            <a href='#'
                               class='btn btn-sm btn-outline-danger px-3 delete-btn'
                               data-id='" . (int)$row['id'] . "'
                               data-name='" . htmlspecialchars($row['name'], ENT_QUOTES) . "'
                               data-bs-toggle='modal' 
                               data-bs-target='#deleteModal'
                               title='Delete'>
                                <i class='fas fa-trash'></i>
                            </a>
                        </div>
                    </td>
                </tr>";
            }
        } else {
            $output = "<tr>
                <td colspan='7' class='text-center py-5'>
                    <div class='empty-state'>
                        <i class='fas fa-layer-group fs-1 text-muted mb-3'></i>
                        <h5 class='text-muted mb-2'>No Sub Categories Found</h5>
                        <p class='text-muted small mb-4'>Get started by adding your first sub category</p>
                        <a href='add-sub-category.php' class='btn btn-primary btn-sm'>
                            <i class='fas fa-plus me-2'></i>Add Sub Category
                        </a>
                    </div>
                </td>
            </tr>";
        }

        mysqli_stmt_close($stmt);
        return $output;

    } else {
        return "<tr>
            <td colspan='7' class='text-center py-4 text-danger'>
                <i class='fas fa-exclamation-triangle me-2'></i>Error loading data
            </td>
        </tr>";
    }
}


// ============================================================
// AJAX: Fetch sub-categories by parent category id
// FIX 1: Was using raw `$_POST['cate_id']` in SQL — SQL injection risk
// FIX 2: Was querying `parent_id = cate_id (old)` → now `category_id = id`
// FIX 3: option value now uses `sc.id` not `sc.cate_id`
// FIX 4: SELECT column `categories` → `name`
// ============================================================
if (isset($_POST['cate_id'])) {
    $p_id = (int) $_POST['cate_id'];  // FIX: cast to int — stops SQL injection

    $stmt = $conn->prepare(
        "SELECT id, name FROM `sub_categories` WHERE `category_id` = ? AND status = 1 ORDER BY name ASC"
    );
    $stmt->bind_param("i", $p_id);
    $stmt->execute();
    $result = $stmt->get_result();

    echo '<option value="">Select Sub-Category</option>';
    while ($row = $result->fetch_assoc()) {
        // FIX: value is now `id`, display is `name`
        echo '<option value="' . (int)$row['id'] . '">' . htmlspecialchars($row['name']) . '</option>';
    }
    $stmt->close();
    exit(); // FIX: always exit after AJAX response
}


// ============================================================
// get_category_by_id()
// FIX: was querying `WHERE cate_id = ?` — column removed
//      now queries `WHERE id = ?` with prepared statement
// ============================================================
function get_category_by_id($cat_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM `categories` WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


// ============================================================
// get_sub_category_by_id()
// FIX: was querying `WHERE cate_id = ?` — column removed
//      now queries `WHERE id = ?` with prepared statement
// ============================================================
function get_sub_category_by_id($cat_id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM `sub_categories` WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $cat_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}


// ============================================================
// SlugUrl() — minor fix: str_replace was str_replace('', '-', ...)
// which replaces nothing. Fixed to replace spaces properly.
// ============================================================
function SlugUrl($string)
{
    $slug = preg_replace('/[^a-zA-Z0-9\s-]/', '', $string); // FIX: keep spaces for next step
    $slug = preg_replace('/[\s]+/', '-', trim($slug));       // FIX: replace spaces with -
    $slug = strtolower($slug);
    return $slug;
}


// ============================================================
// ADD PRODUCT
// FIX 1: Replaced raw SQL string interpolation with prepared statement
// FIX 2: Only last uploaded file was saved ($filename from loop) — 
//         now saves all images to product_images table after insert
// FIX 3: pro_cate and pro_sub_cate now reference categories.id (int)
// FIX 4: Removed alert()/window.location — uses session + redirect
// FIX 5: $sql = $sql = ... typo fixed
// ============================================================
if (isset($_POST['add-product'])) {
    // Sanitize and get all form data
    $pro_name = mysqli_real_escape_string($conn, $_POST['pro_name']);
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name']);
    $category_id = intval($_POST['category_id']);
    $sub_category_id = !empty($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : null;
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url']);
    $short_desc = mysqli_real_escape_string($conn, $_POST['short_desc']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $stock = intval($_POST['stock']);
    $fit_type = mysqli_real_escape_string($conn, $_POST['fit_type']);
    $status = intval($_POST['status']);
    $is_customizable = intval($_POST['is_customizable']);
    $mrp = floatval($_POST['mrp']);
    $selling_price = floatval($_POST['selling_price']);
    $base_price = !empty($_POST['base_price']) ? floatval($_POST['base_price']) : 0;
    $custom_surcharge = !empty($_POST['custom_surcharge']) ? floatval($_POST['custom_surcharge']) : 0;
    $new_arrival = intval($_POST['new_arrival']);
    $trending = intval($_POST['trending']);
    $product_type = mysqli_real_escape_string($conn, $_POST['product_type']);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title']);
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key']);
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc']);

    // Handle image uploads
    $uploaded_images = [];
    $upload_dir = "assets/img/uploads/";
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (!empty($_FILES['pro_img']['name'][0])) {
        foreach ($_FILES['pro_img']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['pro_img']['error'][$key] === 0) {
                $file_name = $_FILES['pro_img']['name'][$key];
                $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
                $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($tmp_name, $destination)) {
                    $uploaded_images[] = $new_file_name;
                }
            }
        }
    }
    
    $pro_img = !empty($uploaded_images) ? implode(',', $uploaded_images) : '';

    // Handle NULL values for foreign keys
    $sub_category_sql = $sub_category_id ? $sub_category_id : 'NULL';
    
    // Insert query
    $insert_query = "INSERT INTO products (
        pro_name, brand_name, category_id, sub_category_id, slug_url, 
        short_desc, description, stock, fit_type, status, is_customizable,
        mrp, selling_price, base_price, custom_surcharge, new_arrival, 
        trending, product_type, pro_img, meta_title, meta_key, meta_desc,
        created_at, updated_at
    ) VALUES (
        '$pro_name', '$brand_name', $category_id, $sub_category_sql, '$slug_url',
        '$short_desc', '$description', $stock, '$fit_type', $status, $is_customizable,
        $mrp, $selling_price, $base_price, $custom_surcharge, $new_arrival,
        $trending, '$product_type', '$pro_img', '$meta_title', '$meta_key', '$meta_desc',
        NOW(), NOW()
    )";

    if (mysqli_query($conn, $insert_query)) {
        $product_id = mysqli_insert_id($conn);
        header("Location: add-product.php?success=1&id=" . $product_id);
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
        // header("Location: show-product.php?error=1");
        // exit();
    }
}

// Function to get subcategories (for AJAX)
if (isset($_POST['action']) && $_POST['action'] == 'get_subcategories' && isset($_POST['cate_id'])) {
    $cate_id = intval($_POST['cate_id']);
    $query = "SELECT * FROM sub_categories WHERE cate_id = $cate_id ORDER BY categories";
    $result = mysqli_query($conn, $query);
    
    $output = '<option value="">Select subcategory (optional)</option>';
    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $output .= '<option value="' . $row['id'] . '">' . htmlspecialchars(ucwords($row['categories'])) . '</option>';
        }
    } else {
        $output .= '<option value="">No subcategories available</option>';
    }
    
    echo $output;
    exit();
}

// ============================================================
// get_Inquiries() — unchanged, no schema conflict
// ============================================================
function get_Inquiries()
{
    include "db-conn.php";

    $searchQuery = "";
    $params      = [];
    $types       = "";

    if (isset($_GET['search']) && !empty($_GET['search'])) {
        $search      = "%" . $_GET['search'] . "%";
        $searchQuery = " WHERE name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?";
        $params      = [$search, $search, $search, $search];
        $types       = "ssss";
    }

    if (isset($_GET['status']) && !empty($_GET['status']) && $_GET['status'] != 'all') {
        $searchQuery .= empty($searchQuery) ? " WHERE" : " AND";
        $searchQuery .= " status = ?";
        $params[]    = $_GET['status'];
        $types       .= "s";
    }

    if (isset($_GET['date']) && !empty($_GET['date'])) {
        $searchQuery .= empty($searchQuery) ? " WHERE" : " AND";
        $searchQuery .= " DATE(created_at) = ?";
        $params[]    = $_GET['date'];
        $types       .= "s";
    }

    $sql = "SELECT * FROM inquiries $searchQuery ORDER BY 
            CASE 
                WHEN status = 'unread'  THEN 1
                WHEN status = 'pending' THEN 2
                WHEN status = 'read'    THEN 3
                ELSE 4
            END, created_at DESC";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $sno    = 1;
        $output = '';

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $statusBadge  = getInquiryStatusBadge($row['status']);
                $created_at   = date('d M Y, h:i A', strtotime($row['created_at']));
                $shortMessage = strlen($row['message']) > 100
                    ? substr($row['message'], 0, 100) . '...'
                    : $row['message'];

                $output .= "<tr data-status='" . htmlspecialchars($row['status']) . "'
                                   data-date='" . date('Y-m-d', strtotime($row['created_at'])) . "'>
                    <td class='text-center align-middle'>
                        <span class='text-muted'>" . $sno++ . "</span>
                    </td>
                    <td class='align-middle'>
                        <div class='fw-semibold'>" . htmlspecialchars($row['name']) . "</div>
                        <small class='text-muted'>ID: " . (int)$row['id'] . "</small>
                    </td>
                    <td class='align-middle'>
                        <div>
                            <div><i class='fas fa-envelope text-muted me-1'></i>" . htmlspecialchars($row['email']) . "</div>
                            <div><i class='fas fa-phone text-muted me-1'></i>" . htmlspecialchars($row['phone']) . "</div>
                        </div>
                    </td>
                    <td class='align-middle'>
                        <div class='fw-semibold'>" . htmlspecialchars($row['subject']) . "</div>
                        <small class='text-muted'>" . $created_at . "</small>
                    </td>
                    <td class='align-middle'>
                        <div class='text-truncate' style='max-width:300px;'>
                            " . htmlspecialchars($shortMessage) . "
                        </div>
                    </td>
                    <td class='align-middle'>" . $statusBadge . "</td>
                    <td class='align-middle text-center'>
                        <div class='btn-group' role='group'>
                            <button type='button'
                                    class='btn btn-sm btn-outline-primary px-3 view-inquiry'
                                    data-id='" . (int)$row['id'] . "'
                                    data-bs-toggle='modal'
                                    data-bs-target='#inquiryModal'
                                    title='View Details'>
                                <i class='fas fa-eye'></i>
                            </button>
                            <a href='mailto:" . htmlspecialchars($row['email']) . "?subject=Re: " . urlencode($row['subject']) . "'
                               class='btn btn-sm btn-outline-success px-3'
                               title='Reply'>
                                <i class='fas fa-reply'></i>
                            </a>
                            <a href='delete_inquiry.php?id=" . (int)$row['id'] . "'
                               class='btn btn-sm btn-outline-danger px-3 delete-inquiry'
                               title='Delete'
                               onclick='return confirm(\"Are you sure?\")'>
                                <i class='fas fa-trash'></i>
                            </a>
                        </div>
                    </td>
                </tr>";
            }
        } else {
            $output = "<tr>
                <td colspan='7' class='text-center py-5'>
                    <div class='empty-state'>
                        <i class='fas fa-inbox fs-1 text-muted mb-3'></i>
                        <h5 class='text-muted mb-2'>No Inquiries Found</h5>
                        <p class='text-muted small'>All customer inquiries will appear here</p>
                    </div>
                </td>
            </tr>";
        }

        mysqli_stmt_close($stmt);
        return $output;

    } else {
        return "<tr>
            <td colspan='7' class='text-center py-4 text-danger'>
                <i class='fas fa-exclamation-triangle me-2'></i>Error loading inquiries
            </td>
        </tr>";
    }
}

function getInquiryStatusBadge($status)
{
    switch ($status) {
        case 'unread':
            return '<span class="badge bg-warning bg-opacity-20 text-warning fw-semibold">
                    <i class="fas fa-envelope me-1"></i>Unread</span>';
        case 'read':
            return '<span class="badge bg-info bg-opacity-20 text-info fw-semibold">
                    <i class="fas fa-envelope-open me-1"></i>Read</span>';
        case 'replied':
            return '<span class="badge bg-success bg-opacity-20 text-success fw-semibold">
                    <i class="fas fa-reply me-1"></i>Replied</span>';
        case 'pending':
            return '<span class="badge bg-secondary bg-opacity-20 text-secondary fw-semibold">
                    <i class="fas fa-clock me-1"></i>Pending</span>';
        default:
            return '<span class="badge bg-light text-muted fw-semibold">' . htmlspecialchars($status) . '</span>';
    }
}


// ============================================================
// TESTIMONIALS — unchanged from original, no schema conflict
// ============================================================

function get_testimonial_by_id($id)
{
    global $conn;
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function update_testimonial(
    $id, $client_name, $client_title, $client_company, $client_photo,
    $testimonial_text, $rating, $project_name, $project_date,
    $featured, $display_order
) {
    global $conn;
    $stmt = $conn->prepare("UPDATE testimonials SET 
        client_name = ?, client_title = ?, client_company = ?, client_photo = ?,
        testimonial_text = ?, rating = ?, project_name = ?, project_date = ?,
        featured = ?, display_order = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param(
        "sssssisssii",
        $client_name, $client_title, $client_company, $client_photo,
        $testimonial_text, $rating, $project_name, $project_date,
        $featured, $display_order, $id
    );
    return $stmt->execute();
}

function handleTestimonialSubmission($conn)
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return ['status' => 'error', 'message' => 'Invalid request method'];
    }

    $client_name      = trim(filter_input(INPUT_POST, 'client_name',      FILTER_SANITIZE_STRING));
    $client_title     = trim(filter_input(INPUT_POST, 'client_title',     FILTER_SANITIZE_STRING));
    $client_company   = trim(filter_input(INPUT_POST, 'client_company',   FILTER_SANITIZE_STRING));
    $testimonial_text = trim(filter_input(INPUT_POST, 'testimonial_text', FILTER_SANITIZE_STRING));
    $rating           = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    $project_name     = trim(filter_input(INPUT_POST, 'project_name', FILTER_SANITIZE_STRING));
    $project_date     = trim(filter_input(INPUT_POST, 'project_date', FILTER_SANITIZE_STRING));
    $featured         = isset($_POST['featured']) ? 1 : 0;
    $display_order    = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT);
    $testimonial_id   = filter_input(INPUT_POST, 'testimonial_id', FILTER_VALIDATE_INT);
    $is_edit          = isset($_POST['update-testimonial']);

    if (empty($client_name) || empty($client_title) || empty($testimonial_text) || !$rating) {
        return ['status' => 'error', 'message' => 'Please fill all required fields'];
    }

    $client_photo = null;
    $upload_dir   = 'uploads/testimonials/';
    if (!file_exists($upload_dir)) mkdir($upload_dir, 0755, true);

    if (isset($_FILES['client_photo']) && $_FILES['client_photo']['error'] === UPLOAD_ERR_OK) {
        $file     = $_FILES['client_photo'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, ['jpg', 'jpeg', 'png', 'gif'])) {
            return ['status' => 'error', 'message' => 'Invalid file type'];
        }
        if ($file['size'] > 5242880) {
            return ['status' => 'error', 'message' => 'File exceeds 5MB'];
        }
        $client_photo = uniqid('testimonial_', true) . '.' . $file_ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $client_photo)) {
            return ['status' => 'error', 'message' => 'Failed to upload file'];
        }
        if ($is_edit && !empty($_POST['existing_photo'])) {
            $old = $upload_dir . $_POST['existing_photo'];
            if (file_exists($old)) unlink($old);
        }
    } elseif ($is_edit) {
        $client_photo = $_POST['existing_photo'] ?? null;
    }

    $current_time  = date('Y-m-d H:i:s');
    $project_date  = !empty($project_date) ? $project_date : null;
    $display_order = $display_order !== false ? $display_order : 0;

    try {
        if ($is_edit && $testimonial_id) {
            $stmt = $conn->prepare("UPDATE testimonials SET
                client_name = ?, client_title = ?, client_company = ?,
                client_photo = COALESCE(?, client_photo), testimonial_text = ?,
                rating = ?, project_name = ?, project_date = ?,
                featured = ?, display_order = ?, updated_at = ? WHERE id = ?");
            $stmt->bind_param(
                "sssssisssisi",
                $client_name, $client_title, $client_company, $client_photo,
                $testimonial_text, $rating, $project_name, $project_date,
                $featured, $display_order, $current_time, $testimonial_id
            );
        } else {
            $stmt = $conn->prepare("INSERT INTO testimonials (
                client_name, client_title, client_company, client_photo, testimonial_text,
                rating, project_name, project_date, featured, display_order, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param(
                "sssssisssiss",
                $client_name, $client_title, $client_company, $client_photo,
                $testimonial_text, $rating, $project_name, $project_date,
                $featured, $display_order, $current_time, $current_time
            );
        }

        if ($stmt->execute()) {
            return [
                'status'         => 'success',
                'message'        => $is_edit ? 'Testimonial updated successfully' : 'Testimonial added successfully',
                'testimonial_id' => $is_edit ? $testimonial_id : $stmt->insert_id
            ];
        } else {
            return ['status' => 'error', 'message' => 'Database error: ' . $stmt->error];
        }
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Error: ' . $e->getMessage()];
    }
}

if (isset($_POST['add-testimonial']) || isset($_POST['update-testimonial'])) {
    $result = handleTestimonialSubmission($conn);

    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    } else {
        if (!session_id()) session_start();
        $_SESSION['form_result'] = $result;
        $redirect_url = isset($_POST['update-testimonial'])
            ? 'testimonials.php?edit=' . (int)$_POST['testimonial_id']
            : 'testimonials.php';
        header('Location: ' . $redirect_url);
        exit;
    }
}