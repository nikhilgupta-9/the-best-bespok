<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include "db-conn.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update-product'])) {
    // Get all POST data with correct field names
    $pro_id = intval($_POST['id']);
    $pro_name = mysqli_real_escape_string($conn, $_POST['pro_name'] ?? '');
    $brand_name = mysqli_real_escape_string($conn, $_POST['brand_name'] ?? '');
    
    // Use correct field names from form
    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $sub_category_id = isset($_POST['sub_category_id']) ? intval($_POST['sub_category_id']) : 0;
    
    $short_desc = mysqli_real_escape_string($conn, $_POST['short_desc'] ?? '');
    $description = mysqli_real_escape_string($conn, $_POST['description'] ?? '');
    $new_arrival = intval($_POST['new_arrival'] ?? 0);
    $trending = intval($_POST['trending'] ?? 0);
    $is_customizable = intval($_POST['is_customizable'] ?? 0);
    $product_type = mysqli_real_escape_string($conn, $_POST['product_type'] ?? 'suit');
    $fit_type = mysqli_real_escape_string($conn, $_POST['fit_type'] ?? 'Regular Fit');
    $mrp = floatval($_POST['mrp'] ?? 0);
    $selling_price = floatval($_POST['selling_price'] ?? 0);
    $base_price = floatval($_POST['base_price'] ?? 0);
    $custom_surcharge = floatval($_POST['custom_surcharge'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $meta_title = mysqli_real_escape_string($conn, $_POST['meta_title'] ?? '');
    $meta_desc = mysqli_real_escape_string($conn, $_POST['meta_desc'] ?? '');
    $meta_key = mysqli_real_escape_string($conn, $_POST['meta_key'] ?? '');
    $status = intval($_POST['status'] ?? 1);
    $slug_url = mysqli_real_escape_string($conn, $_POST['slug_url'] ?? '');

    // VALIDATION: Check if category exists
    if ($category_id > 0) {
        $check_category = "SELECT id FROM categories WHERE id = $category_id";
        $cat_result = mysqli_query($conn, $check_category);
        if (!$cat_result || mysqli_num_rows($cat_result) == 0) {
            die("Error: Selected category does not exist. Please select a valid category.");
        }
    } else {
        die("Error: Category is required.");
    }

    // If subcategory is selected, verify it belongs to the selected category
    if ($sub_category_id > 0) {
        $check_subcategory = "SELECT id FROM sub_categories WHERE id = $sub_category_id AND cate_id = $category_id";
        $sub_result = mysqli_query($conn, $check_subcategory);
        if (!$sub_result || mysqli_num_rows($sub_result) == 0) {
            $sub_category_id = 0; // Invalid subcategory, set to NULL
        }
    }

    // Handle images
    $current_images = [];
    $query = "SELECT pro_img FROM products WHERE id = $pro_id";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $current_images = !empty($row['pro_img']) ? explode(',', $row['pro_img']) : [];
    }

    // Remove images marked for deletion
    $removed_images = isset($_POST['removed_images']) && !empty($_POST['removed_images']) 
        ? explode(',', $_POST['removed_images']) : [];
    
    foreach ($removed_images as $removed) {
        $removed = trim($removed);
        if (!empty($removed) && ($key = array_search($removed, $current_images)) !== false) {
            unset($current_images[$key]);
            // Delete the physical file
            $file_path = "assets/img/uploads/" . $removed;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }

    // Handle new file uploads
    $new_images = [];
    if (!empty($_FILES['pro_img']['name'][0])) {
        $upload_dir = "assets/img/uploads/";
        
        // Create directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        foreach ($_FILES['pro_img']['tmp_name'] as $key => $tmp_name) {
            if ($_FILES['pro_img']['error'][$key] === 0) {
                $file_name = $_FILES['pro_img']['name'][$key];
                $file_size = $_FILES['pro_img']['size'][$key];
                
                // Check file size (max 5MB)
                if ($file_size > 5 * 1024 * 1024) {
                    continue; // Skip files larger than 5MB
                }
                
                // Generate unique filename
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                
                if (in_array($file_ext, $allowed_extensions)) {
                    $new_file_name = time() . '_' . uniqid() . '.' . $file_ext;
                    $destination = $upload_dir . $new_file_name;

                    if (move_uploaded_file($tmp_name, $destination)) {
                        $new_images[] = $new_file_name;
                    }
                }
            }
        }
    }

    // Merge old and new images
    $all_images = array_merge($current_images, $new_images);
    $pro_img = !empty($all_images) ? implode(',', $all_images) : '';

    // Update query - handle NULL values for foreign keys
    $category_id_sql = $category_id > 0 ? $category_id : 'NULL';
    $sub_category_id_sql = $sub_category_id > 0 ? $sub_category_id : 'NULL';
    
    $update_query = "UPDATE products SET 
                pro_name = '$pro_name',
                brand_name = '$brand_name',
                category_id = $category_id_sql,
                sub_category_id = $sub_category_id_sql,
                short_desc = '$short_desc',
                description = '$description',
                new_arrival = $new_arrival,
                trending = $trending,
                is_customizable = $is_customizable,
                product_type = '$product_type',
                fit_type = '$fit_type',
                mrp = $mrp,
                selling_price = $selling_price,
                base_price = $base_price,
                custom_surcharge = $custom_surcharge,
                stock = $stock,
                pro_img = '$pro_img',
                meta_title = '$meta_title',
                meta_desc = '$meta_desc',
                meta_key = '$meta_key',
                status = $status,
                slug_url = '$slug_url',
                updated_at = NOW()
              WHERE id = $pro_id";

    if (mysqli_query($conn, $update_query)) {
        // Success - redirect to edit page with success flag
        header("Location: edit_products.php?id=$pro_id&success=1");
        exit();
    } else {
        // Error - show detailed message
        echo "MySQL Error: " . mysqli_error($conn) . "<br>";
        echo "<hr>";
        echo "Query: " . $update_query . "<br>";
        echo "<hr>";
        echo "Category ID: " . $category_id . "<br>";
        echo "Subcategory ID: " . $sub_category_id;
    }
} else {
    header("Location: view-products.php");
    exit();
}
?>