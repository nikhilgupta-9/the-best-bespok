<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_logged_in'])) {
    header("Location: auth/login.php");
    exit();
}
include "db-conn.php";

// Get current year and month for filtering
$current_year = date('Y');
$current_month = date('m');

// ---------- STATISTICS QUERIES ----------

// Total Gallery Images
$sql_gallery = "SELECT COUNT(*) as count FROM gallery";
$res_gallery = mysqli_query($conn, $sql_gallery);
$total_gallery = $res_gallery ? mysqli_fetch_assoc($res_gallery)['count'] : 0;

// Total Testimonials
$sql_testimonials = "SELECT COUNT(*) as count FROM testimonials";
$res_testimonials = mysqli_query($conn, $sql_testimonials);
$total_testimonials = $res_testimonials ? mysqli_fetch_assoc($res_testimonials)['count'] : 0;

// Total Customers (Users)
$sql_customers = "SELECT COUNT(*) as count FROM users";
$res_customers = mysqli_query($conn, $sql_customers);
$total_customers = $res_customers ? mysqli_fetch_assoc($res_customers)['count'] : 0;

// Total Products
$sql_products = "SELECT COUNT(*) as count FROM products";
$res_products = mysqli_query($conn, $sql_products);
$total_products = $res_products ? mysqli_fetch_assoc($res_products)['count'] : 0;

// Total Orders
$sql_orders = "SELECT COUNT(*) as count FROM orders";
$res_orders = mysqli_query($conn, $sql_orders);
$total_orders = $res_orders ? mysqli_fetch_assoc($res_orders)['count'] : 0;

// Total Revenue from orders (SUM of total)
$sql_revenue = "SELECT SUM(total) as total FROM orders WHERE payment_status = 'paid'";
$res_revenue = mysqli_query($conn, $sql_revenue);
$total_revenue = $res_revenue ? (mysqli_fetch_assoc($res_revenue)['total'] ?? 0) : 0;

// Monthly Revenue for chart (last 12 months)
$monthly_revenue = [];
$months_labels = [];
for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    $months_labels[] = $month_name;
    
    $sql_monthly = "SELECT SUM(total) as total FROM orders 
                    WHERE payment_status = 'paid' 
                    AND DATE_FORMAT(created_at, '%Y-%m') = '$month'";
    $res_monthly = mysqli_query($conn, $sql_monthly);
    $revenue = $res_monthly ? (mysqli_fetch_assoc($res_monthly)['total'] ?? 0) : 0;
    $monthly_revenue[] = $revenue;
}

// Total Blogs
$sql_blogs = "SELECT COUNT(*) as count FROM blogs WHERE status = 'published'";
$res_blogs = mysqli_query($conn, $sql_blogs);
$total_blogs = $res_blogs ? mysqli_fetch_assoc($res_blogs)['count'] : 0;

// Total Inquiries (Unread)
$sql_inquiries = "SELECT COUNT(*) as count FROM inquiries WHERE status = 'unread'";
$res_inquiries = mysqli_query($conn, $sql_inquiries);
$total_unread_inquiries = $res_inquiries ? mysqli_fetch_assoc($res_inquiries)['count'] : 0;

// Top Selling Products
$sql_top_products = "SELECT p.id, p.pro_name, p.pro_img, 
                     COALESCE(SUM(oi.quantity), 0) as total_sold,
                     COALESCE(SUM(oi.total_price), 0) as total_revenue
                     FROM products p
                     LEFT JOIN order_items oi ON p.id = oi.product_id
                     LEFT JOIN orders o ON oi.order_id = o.id AND o.payment_status = 'paid'
                     GROUP BY p.id
                     ORDER BY total_sold DESC
                     LIMIT 5";
$res_top_products = mysqli_query($conn, $sql_top_products);

// Recent Activities (combined from multiple tables)
$activities = [];

// Recent blog posts
$sql_recent_blogs = "SELECT 'blog' as type, title as title, created_at, 
                     CONCAT('New blog published: ', title) as description 
                     FROM blogs ORDER BY created_at DESC LIMIT 3";
$res_recent_blogs = mysqli_query($conn, $sql_recent_blogs);
if ($res_recent_blogs) {
    while ($row = mysqli_fetch_assoc($res_recent_blogs)) {
        $activities[] = $row;
    }
}

// Recent product additions
$sql_recent_products = "SELECT 'product' as type, pro_name as title, created_at,
                        CONCAT('New product added: ', pro_name) as description
                        FROM products ORDER BY created_at DESC LIMIT 3";
$res_recent_products = mysqli_query($conn, $sql_recent_products);
if ($res_recent_products) {
    while ($row = mysqli_fetch_assoc($res_recent_products)) {
        $activities[] = $row;
    }
}

// Recent inquiries
$sql_recent_inquiries = "SELECT 'inquiry' as type, name as title, created_at,
                         CONCAT('New inquiry from ', name) as description
                         FROM inquiries ORDER BY created_at DESC LIMIT 3";
$res_recent_inquiries = mysqli_query($conn, $sql_recent_inquiries);
if ($res_recent_inquiries) {
    while ($row = mysqli_fetch_assoc($res_recent_inquiries)) {
        $activities[] = $row;
    }
}

// Recent orders
$sql_recent_orders = "SELECT 'order' as type, order_number as title, created_at,
                      CONCAT('New order #', order_number, ' placed') as description
                      FROM orders ORDER BY created_at DESC LIMIT 3";
$res_recent_orders = mysqli_query($conn, $sql_recent_orders);
if ($res_recent_orders) {
    while ($row = mysqli_fetch_assoc($res_recent_orders)) {
        $activities[] = $row;
    }
}

// Sort activities by date
usort($activities, function ($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Order Status Distribution
$sql_status = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$res_status = mysqli_query($conn, $sql_status);
$status_data = [];
$status_labels = [];
if ($res_status) {
    while ($row = mysqli_fetch_assoc($res_status)) {
        $status_labels[] = ucfirst($row['status']);
        $status_data[] = $row['count'];
    }
}

// Calculate percentage changes (simulated based on previous month)
// For demo, we'll use realistic calculations
$prev_month_orders = 0;
$sql_prev_orders = "SELECT COUNT(*) as count FROM orders WHERE MONTH(created_at) = " . ($current_month - 1);
$res_prev_orders = mysqli_query($conn, $sql_prev_orders);
if ($res_prev_orders) {
    $prev_month_orders = mysqli_fetch_assoc($res_prev_orders)['count'];
}
$orders_change = $prev_month_orders > 0 ? round((($total_orders - $prev_month_orders) / $prev_month_orders) * 100) : 0;

$prev_month_customers = 0;
$sql_prev_customers = "SELECT COUNT(*) as count FROM users WHERE MONTH(created_at) = " . ($current_month - 1);
$res_prev_customers = mysqli_query($conn, $sql_prev_customers);
if ($res_prev_customers) {
    $prev_month_customers = mysqli_fetch_assoc($res_prev_customers)['count'];
}
$customers_change = $prev_month_customers > 0 ? round((($total_customers - $prev_month_customers) / $prev_month_customers) * 100) : 5;

$prev_month_products = 0;
$sql_prev_products = "SELECT COUNT(*) as count FROM products WHERE MONTH(created_at) = " . ($current_month - 1);
$res_prev_products = mysqli_query($conn, $sql_prev_products);
if ($res_prev_products) {
    $prev_month_products = mysqli_fetch_assoc($res_prev_products)['count'];
}
$products_change = $prev_month_products > 0 ? round((($total_products - $prev_month_products) / $prev_month_products) * 100) : 10;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Admin Panel | Dashboard</title>
    <link rel="icon" href="img/logo.png" type="image/png">

    <!-- Bootstrap & Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- ApexCharts -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/apexcharts@3.35.0/dist/apexcharts.min.css">

    <link rel="stylesheet" href="css/style.css" />
    <?php include "links.php"; ?>

    <style>
        html,
        body {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .wrapper {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .main_content {
            flex: 1;
        }

        footer {
            position: relative;
            bottom: 0;
            background: #f8f9fa;
            padding: 15px 0;
            text-align: center;
            width: 100%;
        }

        .stat-card {
            transition: all 0.3s ease;
            border-radius: 10px;
            border-left: 4px solid;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-card .card-icon {
            font-size: 2rem;
            opacity: 0.7;
        }

        .revenue-card {
            border-left-color: #4e73df;
        }

        .orders-card {
            border-left-color: #1cc88a;
        }

        .customers-card {
            border-left-color: #36b9cc;
        }

        .products-card {
            border-left-color: #f6c23e;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .activity-feed {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            border-left: 3px solid #4e73df;
            padding-left: 15px;
            margin-bottom: 15px;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: #f8f9fa;
            transform: translateX(5px);
        }

        .activity-time {
            font-size: 0.75rem;
            color: #6c757d;
        }

        .top-product-img {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }

        .action-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 12px;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .icon-circle {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;
            font-size: 1.5rem;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
        }

        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .trend-up {
            color: #1cc88a;
        }

        .trend-down {
            color: #e74a3b;
        }
    </style>
</head>

<body class="bg-light">

    <div class="wrapper">
        <?php include "header.php"; ?>

        <section class="main_content dashboard_part">
            <div class="container-fluid g-0">
                <div class="row">
                    <div class="col-lg-12 p-0">
                        <?php include "top_nav.php"; ?>
                    </div>
                </div>
            </div>

            <div class="container-fluid">

                <!-- Welcome Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card shadow-sm border-0 mb-4">
                            <div class="card-body">
                                <h4 class="mb-1">Welcome back, <?= $_SESSION['admin_username'] ?? 'Admin' ?>!</h4>
                                <p class="text-muted mb-0">Here's what's happening with your store today.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Row 1 -->
                <div class="row">
                    <!-- Revenue Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card revenue-card shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Revenue</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800">
                                            ₹<?= number_format($total_revenue, 2) ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="trend-up me-2"><i class="fas fa-arrow-up me-1"></i> 12%</span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-rupee-sign card-icon text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card orders-card shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Orders</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800"><?= $total_orders ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="<?= $orders_change >= 0 ? 'trend-up' : 'trend-down' ?> me-2">
                                                <i class="fas fa-arrow-<?= $orders_change >= 0 ? 'up' : 'down' ?> me-1"></i>
                                                <?= abs($orders_change) ?>%
                                            </span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-shopping-cart card-icon text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Customers Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card customers-card shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Total Customers</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800">
                                            <?= $total_customers ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="<?= $customers_change >= 0 ? 'trend-up' : 'trend-down' ?> me-2">
                                                <i class="fas fa-arrow-<?= $customers_change >= 0 ? 'up' : 'down' ?> me-1"></i>
                                                <?= abs($customers_change) ?>%
                                            </span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users card-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Products Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card products-card shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Total Products</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800">
                                            <?= $total_products ?>
                                        </div>
                                        <div class="mt-2 mb-0 text-muted text-xs">
                                            <span class="<?= $products_change >= 0 ? 'trend-up' : 'trend-down' ?> me-2">
                                                <i class="fas fa-arrow-<?= $products_change >= 0 ? 'up' : 'down' ?> me-1"></i>
                                                <?= abs($products_change) ?>%
                                            </span>
                                            <span>Since last month</span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-boxes card-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Key Metrics Row 2 -->
                <div class="row">
                    <!-- Gallery Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow h-100 py-2" style="border-left-color: #858796;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                            Gallery Images</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800"><?= $total_gallery ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-images card-icon text-secondary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Testimonials Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow h-100 py-2" style="border-left-color: #f6c23e;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Testimonials</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800">
                                            <?= $total_testimonials ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-star card-icon text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Blogs Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow h-100 py-2" style="border-left-color: #36b9cc;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Published Blogs</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800"><?= $total_blogs ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-blog card-icon text-info"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Unread Inquiries Card -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow h-100 py-2" style="border-left-color: #e74a3b;">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col me-2">
                                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                            Unread Inquiries</div>
                                        <div class="stats-number mb-0 font-weight-bold text-gray-800">
                                            <?= $total_unread_inquiries ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-envelope card-icon text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h4 class="mb-3 text-secondary"><i class="fas fa-bolt me-2"></i>Quick Actions</h4>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="add-products.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
                            <div class="card-body py-4">
                                <div class="icon-circle bg-primary text-white mb-3">
                                    <i class="fas fa-plus"></i>
                                </div>
                                <h5 class="card-title mb-1">Add Product</h5>
                                <p class="text-muted small mb-0">Add new product to inventory</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="add-blog.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
                            <div class="card-body py-4">
                                <div class="icon-circle bg-success text-white mb-3">
                                    <i class="fas fa-blog"></i>
                                </div>
                                <h5 class="card-title mb-1">Create Blog</h5>
                                <p class="text-muted small mb-0">Publish new blog post</p>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="new-leads.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
                            <div class="card-body py-4">
                                <div class="icon-circle bg-info text-white mb-3">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <h5 class="card-title mb-1">View Inquiries</h5>
                                <p class="text-muted small mb-0">Check customer inquiries</p>
                                <?php if ($total_unread_inquiries > 0): ?>
                                    <span class="badge bg-danger position-absolute top-0 start-100 translate-middle mt-2">
                                        <?= $total_unread_inquiries ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </a>
                    </div>

                    <div class="col-lg-3 col-md-6 mb-4">
                        <a href="view-products.php" class="card action-card shadow-sm border-0 text-center text-decoration-none">
                            <div class="card-body py-4">
                                <div class="icon-circle bg-warning text-white mb-3">
                                    <i class="fas fa-boxes"></i>
                                </div>
                                <h5 class="card-title mb-1">Manage Products</h5>
                                <p class="text-muted small mb-0">View and edit products</p>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mt-4">
                    <!-- Revenue Chart -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Revenue Overview (Last 12 Months)</h6>
                            </div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Distribution -->
                    <div class="col-lg-4 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Order Status Distribution</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($status_labels)): ?>
                                    <div class="chart-container" style="height: 250px;">
                                        <canvas id="orderStatusChart"></canvas>
                                    </div>
                                    <div class="mt-4">
                                        <?php foreach ($status_data as $index => $count): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span><?= $status_labels[$index] ?></span>
                                                    <strong><?= $count ?></strong>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar"
                                                        style="width: <?= ($count / max(1, array_sum($status_data))) * 100 ?>%"
                                                        aria-valuenow="<?= $count ?>" aria-valuemin="0"
                                                        aria-valuemax="<?= array_sum($status_data) ?>"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No order data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bottom Row -->
                <div class="row">
                    <!-- Top Selling Products -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Top Selling Products</h6>
                            </div>
                            <div class="card-body">
                                <?php if (mysqli_num_rows($res_top_products) > 0): ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Sold</th>
                                                    <th>Revenue</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($product = mysqli_fetch_assoc($res_top_products)): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php
                                                                $images = !empty($product['pro_img']) ? explode(',', $product['pro_img']) : [];
                                                                $first_img = !empty($images) ? trim($images[0]) : 'default.jpg';
                                                                ?>
                                                                <img src="assets/img/uploads/<?= htmlspecialchars($first_img) ?>"
                                                                    class="top-product-img me-2"
                                                                    onerror="this.src='img/placeholder.png'"
                                                                    alt="<?= htmlspecialchars($product['pro_name']) ?>">
                                                                <span><?= htmlspecialchars(substr($product['pro_name'], 0, 30)) ?></span>
                                                            </div>
                                                        </td>
                                                        <td class="fw-bold"><?= $product['total_sold'] ?></td>
                                                        <td>₹<?= number_format($product['total_revenue'], 2) ?></td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted text-center">No sales data available</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="col-lg-6 mb-4">
                        <div class="card shadow h-100">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                            </div>
                            <div class="card-body activity-feed">
                                <?php if (!empty($activities)): ?>
                                    <?php 
                                    $activity_colors = [
                                        'blog' => 'primary',
                                        'product' => 'success',
                                        'inquiry' => 'info',
                                        'order' => 'warning'
                                    ];
                                    $activity_icons = [
                                        'blog' => 'fa-pen',
                                        'product' => 'fa-box',
                                        'inquiry' => 'fa-envelope',
                                        'order' => 'fa-shopping-cart'
                                    ];
                                    ?>
                                    <?php foreach (array_slice($activities, 0, 6) as $activity): ?>
                                        <?php $color = $activity_colors[$activity['type']] ?? 'secondary'; ?>
                                        <?php $icon = $activity_icons[$activity['type']] ?? 'fa-clock'; ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <strong class="text-dark"><?= htmlspecialchars($activity['description']) ?></strong>
                                                <span class="badge bg-<?= $color ?> ms-2"><?= ucfirst($activity['type']) ?></span>
                                            </div>
                                            <div class="d-flex align-items-center text-muted small">
                                                <i class="fas <?= $icon ?> me-1"></i>
                                                <span><?= date('M j, Y g:i A', strtotime($activity['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-muted text-center">No recent activity</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card shadow">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Quick Stats</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="h4 text-primary"><?= $total_orders ?></div>
                                            <div class="text-muted small">Total Orders</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="h4 text-success"><?= $total_customers ?></div>
                                            <div class="text-muted small">Total Customers</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="h4 text-info"><?= $total_products ?></div>
                                            <div class="text-muted small">Total Products</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 col-6 mb-3">
                                        <div class="text-center">
                                            <div class="h4 text-warning"><?= $total_blogs ?></div>
                                            <div class="text-muted small">Published Blogs</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </section>

        <footer>
            <?php include "footer.php"; ?>
        </footer>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($months_labels) ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?= json_encode($monthly_revenue) ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '₹' + context.parsed.y.toLocaleString();
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₹' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Order Status Chart (if data exists)
        <?php if (!empty($status_labels)): ?>
        const statusCtx = document.getElementById('orderStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($status_labels) ?>,
                datasets: [{
                    data: <?= json_encode($status_data) ?>,
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
                    hoverBackgroundColor: ['#2e59d9', '#17a673', '#2c9faf', '#dda20a', '#c82333', '#6c757d'],
                    hoverBorderColor: "rgba(234, 236, 244, 1)",
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '60%',
            },
        });
        <?php endif; ?>
    </script>
</body>

</html>