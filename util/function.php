<?php
include_once __DIR__ . "/../config/connect.php";

// get category 
function get_category($limit)
{
    global $conn;

    $sql = "SELECT * FROM `categories` WHERE status = 1 limit $limit";
    $res = mysqli_query($conn, $sql);

    $categories = [];

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

function get_sub_category()
{
    global $conn;

    $sql = "SELECT * FROM `sub_categories` WHERE status = 1 ";
    $res = mysqli_query($conn, $sql);

    $categories = [];

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

function reverse_get_category($limit)
{
    global $conn;

    $sql = "SELECT * FROM `categories` WHERE status = 1 order by id desc limit $limit";
    $res = mysqli_query($conn, $sql);

    $categories = [];

    if ($res && mysqli_num_rows($res) > 0) {
        while ($row = mysqli_fetch_assoc($res)) {
            $categories[] = $row;
        }
    }

    return $categories;
}

// get abouts 
function fetch_about()
{
    global $conn;

    // Fetch all about sections ordered by section_order
    $sql = "SELECT * FROM `about_sections` ORDER BY `section_order` ASC";
    $sql_query = $conn->query($sql);

    if ($sql_query && $sql_query->num_rows > 0) {
        $sections = [];
        while ($row = $sql_query->fetch_assoc()) {
            $sections[] = [
                'title' => $row['title'] ?? '',
                'content' => $row['content'] ?? '',
                'image' => $row['image_url'] ?? '',
                'order' => $row['section_order'] ?? 0
            ];
        }
        return $sections;
    } else {
        // Return a default section if no records found
        return [
            [
                'title' => 'About Us',
                'content' => 'No about us sections found. Please add some content in the admin panel.',
                'image' => '',
                'order' => 1
            ]
        ];
    }
}

// logo 
function get_header_logo()
{
    global $conn;

    $sql_logo = "SELECT * FROM `logos` where `location` = 'header' order by id desc limit 1";
    $re_logo = mysqli_query($conn, $sql_logo);
    if (mysqli_num_rows($re_logo)) {
        $row = mysqli_fetch_assoc($re_logo);

        return "admin/uploads/" . $row['logo_path'];
    }
}

function get_email_logo()
{
    global $conn;

    $sql_logo = "SELECT * FROM `logos` where `location` = 'email' order by id desc limit 1";
    $re_logo = mysqli_query($conn, $sql_logo);
    if (mysqli_num_rows($re_logo)) {
        $row = mysqli_fetch_assoc($re_logo);

        return "admin/uploads/" . $row['logo_path'];
    }
}


function get_footer_logo()
{
    global $conn;

    $sql_logo = "SELECT * FROM `logos` where `location` = 'footer' order by id desc limit 1";
    $re_logo = mysqli_query($conn, $sql_logo);
    if (mysqli_num_rows($re_logo)) {
        $row = mysqli_fetch_assoc($re_logo);

        return "admin/uploads/" . $row['logo_path'];
    }
}
// logo end 


// fetch banners 
function fetch_banner()
{
    global $conn;
    $banners = [];

    // 1. Prepare the SQL template with a placeholder (?)
    $stmt = $conn->prepare("SELECT * FROM `banners` WHERE status = 0 order by id desc");

    if ($stmt) {
        // 2. Bind the variable to the placeholder ("i" for integer)

        // 3. Execute the query
        $stmt->execute();

        // 4. Get the result
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $banners[] = $row;
        }
        $stmt->close();
    }
    return $banners;
}

// get contact us page 
function contact_us()
{
    global $conn;

    if (!$conn || !$conn->ping()) {
        // Connection is not available or already closed
        return null;
    }

    $query = "SELECT * FROM `contacts` LIMIT 1";
    $sql_query = $conn->query($query);

    if ($sql_query && $sql_query->num_rows > 0) {
        $result = $sql_query->fetch_assoc();

        return [
            'company_name' => $result['company_name'] ?? '',
            'copyright' => $result['copyright'] ?? '',
            'phone' => $result['phone'] ?? '',
            'wp_number' => $result['wp_number'] ?? '',
            'telephone' => $result['telephone'] ?? '',
            'address' => $result['address'] ?? '',
            'address2' => $result['address2'] ?? '',
            'email' => $result['email'] ?? '',
            'contact_email' => $result['contact_email'] ?? '',
            'facebook' => $result['facebook'] ?? '',
            'instagram' => $result['instagram'] ?? '',
            'twitter' => $result['twitter'] ?? '',
            'linkdin' => $result['linkdin'] ?? '',
            'map' => $result['map'] ?? '',
            'working_hours' => $result['working_hours'] ?? ''
        ];
    }

    return null; // Or return [] if you prefer
}


// get gallery images 
function get_gallery()
{
    global $conn;

    $sql = "SELECT * FROM `gallery`";
    $sql_query = $conn->query($sql);

    $images = [];

    if ($sql_query && $sql_query->num_rows > 0) {
        while ($result = $sql_query->fetch_assoc()) {
            $images[] = "admin/" . ($result['image_path'] ?? '');
        }
    }

    return $images; // returns an empty array if no records
}


// get products for home page
function get_product_by_sub_cat($slug)
{
    global $conn;
    $products = [];

    $stmt = $conn->prepare("
        SELECT p.*, sc.slug_url as cat_slug, sc.cate_id, sc.categories 
        FROM products AS p
        INNER JOIN sub_categories AS sc 
            ON p.pro_sub_cate = sc.cate_id
        WHERE sc.slug_url = ? 
        AND p.status = 1
    ");

    if ($stmt) {
        $stmt->bind_param("s", $slug); // STRING
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }

    return $products;
}


function get_home_product()
{
    global $conn;
    $products = [];

    // Prepare the SQL statement with a placeholder (?)
    $stmt = $conn->prepare("SELECT * FROM `products` where status = 1 limit 8");

    if ($stmt) {
        // Bind the variable to the placeholder ("i" for integer)

        // Execute the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

function get_all_product()
{
    global $conn;
    $products = [];

    // Prepare the SQL statement with a placeholder (?)
    $stmt = $conn->prepare("SELECT * FROM `products` where status = 1");

    if ($stmt) {
        // Bind the variable to the placeholder ("i" for integer)

        // Execute the query
        $stmt->execute();

        // Get the result
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    return $products;
}

function get_category_by_id($cat_id)
{
    global $conn;
    $sub_category = [];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `categories` WHERE `cate_id` = ?  AND `status` = 1 ORDER BY `added_on` DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $cat_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $sub_category[] = $row;
        }

        mysqli_stmt_close($stmt);
    } else {
        error_log("Database error: " . mysqli_error($conn));
    }

    return $sub_category;
}
// Update your function in util/function.php
function get_sub_category_by_parent_id($parent_id)
{
    global $conn;
    $sub_category = [];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `sub_categories` WHERE `parent_id` = ? OR `cate_id` = ? OR `slug_url` = ? AND `status` = 1 ORDER BY `added_on` DESC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "iis", $parent_id, $parent_id, $parent_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $sub_category[] = $row;
        }

        mysqli_stmt_close($stmt);
    } else {
        error_log("Database error: " . mysqli_error($conn));
    }

    return $sub_category;
}

// fetch sub category with category slug  referece
function get_sub_category_with_category_slug()
{
    global $conn;
    $sub_category = [];

    if (!isset($_GET['alias'])) {
        header("Location: index.php");
        exit();
    }

    $alias = mysqli_real_escape_string($conn, $_GET['alias']);
    $category = "SELECT cate_id FROM `categories` where `slug_url` = '$alias'";
    $cate_res = mysqli_query($conn, $category);
    $cate_row = mysqli_fetch_assoc($cate_res);

    $cate_id = $cate_row['cate_id'];

    // Use prepared statement to prevent SQL injection
    $sql = "SELECT * FROM `sub_categories` where parent_id = $cate_id";

    $result = mysqli_query($conn, $sql);

    if (!$result) {
        // Log error or handle it appropriately
        error_log("Database error: " . mysqli_error($conn));
        return $sub_category; // Return empty array on error
    }

    while ($row = mysqli_fetch_assoc($result)) {
        $sub_category[] = $row;
    }

    return $sub_category;
}



// fetching trending product 
function get_trending_product(): array
{
    global $conn;

    $sql = "SELECT * FROM `products` where `status` = 1 order by rand() desc limit 8";
    $res = mysqli_query($conn, $sql);


    if (!$res) {
        header("Location: 500.php");
        exit();
    }

    $trendingProducts = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res)) {
        $trendingProducts[] = $row;
    }

    return $trendingProducts; // ✅ Return the result
}

// blog fetch for home page 
function get_blog_home()
{
    global $conn;

    $sql_blog = "SELECT * FROM `blogs` limit 3";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php"); // ✅ Remove spaces around colon
        exit(); // ✅ Always add exit after header redirect
    }

    $blog = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res_blog)) {
        $blog[] = $row;
    }

    return $blog; // ✅ Return the result
}


// blog fetch for blog page 
function get_blog($limit)
{
    global $conn;

    $sql_blog = "SELECT * FROM `blogs` limit $limit ";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php"); // ✅ Remove spaces around colon
        exit(); // ✅ Always add exit after header redirect
    }

    $blog = []; // ✅ Initialize the array before using
    while ($row = mysqli_fetch_assoc($res_blog)) {
        $blog[] = $row;
    }

    return $blog; // ✅ Return the result
}

// blog details fetch 
function fetch_blog_detail()
{
    global $conn;
    global $site;

    if (!isset($_GET['alias'])) {
        header("Location: index.php");
        exit();
    }

    $alias = mysqli_real_escape_string($conn, $_GET['alias']);
    // $blog_slug = mysqli_real_escape_string($conn, $slug);
    // die($slug);

    $sql_blog = "SELECT * FROM `blogs` WHERE `slug_url` = '$alias' LIMIT 1";
    $res_blog = mysqli_query($conn, $sql_blog);

    if (!$res_blog) {
        header("Location: 500.php");
        exit();
    }

    $blog_det = mysqli_fetch_assoc($res_blog);

    if (!$blog_det) {
        header("Location: " . $site . "404.php");
        exit();
    }

    return $blog_det;
}

// product page fetch product 
function fetch_product_page()
{
    global $conn;

    if (!isset($_GET['alias'])) {
        header("Location: index.php");
        exit();
    }

    $alias = $_GET['alias'];

    // Use prepared statement for security
    $stmt = $conn->prepare("
        SELECT 
            sc.cate_id      AS sub_cate_id,
            sc.categories   AS sub_cat_name,
            sc.meta_title,
            sc.meta_key,
            sc.meta_desc,

            p.pro_id,
            p.pro_name,
            p.mrp,
            p.selling_price,
            p.pro_img,
            p.slug_url AS product_slug
        FROM sub_categories sc
        LEFT JOIN products p 
            ON sc.cate_id = p.pro_sub_cate
            AND p.status = 1
        WHERE sc.slug_url = ?
    ");

    $stmt->bind_param("s", $alias);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        header("Location: 404.php");
        exit();
    }

    // Fetch all products for that subcategory
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    // Store meta and subcategory data in session (optional)
    $_SESSION['sub_cat_name'] = $data[0]['sub_cat_name'];
    $_SESSION['meta_title'] = $data[0]['meta_title'];
    $_SESSION['meta_key'] = $data[0]['meta_key'];
    $_SESSION['meta_desc'] = $data[0]['meta_desc'];

    $stmt->close();

    return $data;
}

function fetch_product_details()
{
    global $conn;

    if (!isset($_GET['alias']) || empty($_GET['alias'])) {
        die("Invalid product URL. Alias parameter is missing.");
    }

    $alias = mysqli_real_escape_string($conn, $_GET['alias']);

    $sql = "SELECT * FROM `products` WHERE `slug_url` = '$alias' LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if ($result && $row = mysqli_fetch_assoc($result)) {
        return [
            'pro_id' => $row['pro_id'] ?? '',
            'pro_name' => $row['pro_name'] ?? '',
            'short_desc' => $row['short_desc'] ?? '',
            'description' => $row['description'] ?? '',
            'pro_cate' => $row['pro_cate'] ?? '',
            'pro_sub_cate' => $row['pro_sub_cate'] ?? '',
            'pro_img' => $row['pro_img'] ?? 'image/product-not-found.gif',
            'slug_url' => $row['slug_url'] ?? '',
            'mrp' => $row['mrp'] ?? '00',
            'selling_price' => $row['selling_price'] ?? '00',
            'meta_title' => $row['meta_title'] ?? '',
            'meta_desc' => $row['meta_desc'] ?? '',
            'meta_key' => $row['meta_key'] ?? ''
        ];
    } else {
        // If product not found, return default values
        return [
            'pro_name' => 'No Product Available',
            'short_desc' => '',
            'description' => '',
            'pro_sub_cate' => '',
            'pro_img' => 'image/product-not-found.gif',
            'slug_url' => '',
            'meta_title' => 'Product Not Found',
            'meta_desc' => '',
            'meta_key' => ''
        ];
    }
}

function fetch_product_images($pro_id)
{
    global $conn;
    $pro_id = $pro_id ?? 0;
    $images = []; // Initialize the array

    // Use a prepared statement to prevent SQL injection:cite[4]
    $sql = "SELECT * FROM `product_images` WHERE `product_id` = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $pro_id); // "i" for integer
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        // Fetch all the image paths
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $images[] = $row['image_path'];
            }
        }
        mysqli_stmt_close($stmt);
    }
    return $images; // Return the array:cite[8]
}

// footer product 
function footer_product()
{
    global $conn;

    $sql_foot = "SELECT * FROM `products` order by rand() limit 8 ";
    $res_foot = mysqli_query($conn, $sql_foot);

    $product = [];

    if (!$res_foot) {
        header('Location: 500.php');
    }
    while ($row = mysqli_fetch_assoc($res_foot)) {
        if (!$row) {
            header("Location: 404.php");
        } else {
            $product[] = $row;
        }
    }
    return $product;
}

function fetch_product_by_sub_cat($sub_cat_id)
{
    global $conn;

    $sql_foot = "SELECT * FROM `products` where `pro_sub_cate` = $sub_cat_id OR slug_url limit 8";
    $res_foot = mysqli_query($conn, $sql_foot);

    $product = [];

    if (!$res_foot) {
        header('Location: 500.php');
    }
    while ($row = mysqli_fetch_assoc($res_foot)) {
        if (!$row) {
            header("Location: 404.php");
        } else {
            $product[] = $row;
        }
    }
    return $product;
}

function testimonial()
{
    global $conn;

    $test = [];
    $sql_test = "SELECT * FROM `testimonials`";

    try {
        // Execute query
        $res_test = mysqli_query($conn, $sql_test);

        if (!$res_test) {
            throw new Exception("Database query failed");
        }

        // Fetch all results
        while ($row = mysqli_fetch_assoc($res_test)) {
            $test[] = $row;
        }

        // Check if any results were found
        if (empty($test)) {
            http_response_code(404);
            header('Location: 404.php');
            exit;
        }

    } catch (Exception $e) {
        // Log the actual error for administration
        error_log("Database error: " . $e->getMessage());

        // Show generic error to user
        http_response_code(500);
        header('Location: 500.php');
        exit;
    }

    return $test;
}



// get best brand 
function get_best_brand()
{
    global $conn;

    $sql_brand = "SELECT * FROM `brands`";
    $res_brand = mysqli_query($conn, $sql_brand);

    $brand = [];

    if (!$res_brand) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($res_brand)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $brand[] = $row;
            }
        }
    }
    return $brand;
}


// select query 

function select_query($table, $condition){
    global $conn;

    $query = mysqli_query($conn, "SELECT * FROM $table $condition");
    if (!$query) {
        die("Query Failed: " . mysqli_error($conn));
    }

    return $query;

}

// fetch product by their category 

function getProductByCategory($slug){
    global $conn;

    $sql = "
        SELECT 
            p.*, 
            c.slug_url AS category_slug, 
            c.id AS category_id, 
            c.name AS category_name
        FROM products AS p
        LEFT JOIN categories AS c 
            ON p.category_id = c.id
        WHERE p.status = 1 
        AND c.slug_url = '$slug'
    ";

    $stmt = mysqli_query($conn, $sql);

    $brand = [];

    if (!$stmt) {
        header('Location: 500.php');
    } else {
        while ($row = mysqli_fetch_assoc($stmt)) {
            if (!$row) {
                header('Location: 404.php');
            } else {
                $brand[] = $row;
            }
        }
    }
    return $brand;
}
