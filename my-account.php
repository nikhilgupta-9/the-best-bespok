<?php
ob_start();
session_start();
include_once "config/connect.php"; // BASE_URL etc.
include_once "util/function.php";

// ── AUTH CHECK ────────────────────────────────────────────────
// If NOT logged in → send to home with a flag to open the login modal
if (empty($_SESSION['user_id'])) {
    // Store the intended destination so after login we redirect back here
    $_SESSION['login_redirect'] = 'my-account.php';
    // Redirect to index with ?login=1 — header.php watches for this flag
    // and auto-opens the login modal via JS
    header("Location: " . BASE_URL . "?open_login=1");
    exit();
}

// ── FETCH USER DATA ───────────────────────────────────────────
$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    // User in session doesn't exist in DB → clear session and redirect
    session_destroy();
    header("Location: " . BASE_URL . "?open_login=1");
    exit();
}

// ── UPDATE PROFILE ────────────────────────────────────────────
if (isset($_POST['update_profile'])) {
    $first_name  = trim($_POST['first_name'] ?? '');
    $last_name   = trim($_POST['last_name']  ?? '');
    $mobile      = trim($_POST['mobile']     ?? '');
    $address     = trim($_POST['address']    ?? '');
    $city        = trim($_POST['city']       ?? '');
    $state       = trim($_POST['state']      ?? '');
    $pincode     = trim($_POST['pincode']    ?? '');
    $country     = trim($_POST['country']    ?? '');
    $new_pass    = trim($_POST['password']   ?? '');
    $confirm     = trim($_POST['confirm_password'] ?? '');

    $errors = [];

    // Password update (optional)
    $pass_sql = '';
    $pass_val = null;
    if ($new_pass !== '') {
        if (strlen($new_pass) < 6) {
            $errors[] = "Password must be at least 6 characters.";
        } elseif ($new_pass !== $confirm) {
            $errors[] = "Passwords do not match.";
        } else {
            $pass_sql = ', password=?';
            $pass_val = password_hash($new_pass, PASSWORD_DEFAULT);
        }
    }

    if (empty($errors)) {
        if ($pass_val) {
            $s = $conn->prepare(
                "UPDATE users SET first_name=?, last_name=?, mobile=?, address=?, city=?, state=?, pincode=?, country=?, password=? WHERE id=?"
            );
            $s->bind_param("sssssssssi", $first_name, $last_name, $mobile, $address, $city, $state, $pincode, $country, $pass_val, $user_id);
        } else {
            $s = $conn->prepare(
                "UPDATE users SET first_name=?, last_name=?, mobile=?, address=?, city=?, state=?, pincode=?, country=? WHERE id=?"
            );
            $s->bind_param("ssssssssi", $first_name, $last_name, $mobile, $address, $city, $state, $pincode, $country, $user_id);
        }
        if ($s->execute()) {
            $_SESSION['success'] = "Profile updated successfully!";
            // Refresh user data
            $r = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
            $r->bind_param("i", $user_id); $r->execute();
            $user = $r->get_result()->fetch_assoc(); $r->close();
        } else {
            $_SESSION['error'] = "Update failed. Please try again.";
        }
        $s->close();
    } else {
        $_SESSION['error'] = implode(' ', $errors);
    }
    header("Location: my-account.php?tab=profile");
    exit();
}

// ── LOGOUT ────────────────────────────────────────────────────
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: " . BASE_URL);
    exit();
}

// ── FETCH ORDERS ──────────────────────────────────────────────
$orders_limit = (int)($_GET['show'] ?? 5);
$orders_query = $conn->prepare(
    "SELECT o.*, COUNT(oi.id) AS item_count
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC
     LIMIT ?"
);
$orders_query->bind_param("ii", $user_id, $orders_limit);
$orders_query->execute();
$orders_result = $orders_query->get_result();
$orders = [];
while ($o = $orders_result->fetch_assoc()) $orders[] = $o;
$orders_query->close();

// ── FETCH CUSTOM ORDERS ───────────────────────────────────────
$custom_orders_q = $conn->prepare(
    "SELECT co.*, p.pro_name, p.pro_img FROM custom_orders co
     LEFT JOIN products p ON p.id = co.product_id
     WHERE co.user_id = ?
     ORDER BY co.created_at DESC LIMIT 10"
);
$custom_orders_q->bind_param("i", $user_id);
$custom_orders_q->execute();
$custom_orders = $custom_orders_q->get_result()->fetchAll(MYSQLI_ASSOC);
$custom_orders_q->close();

// ── ORDER STATS ───────────────────────────────────────────────
$stats = $conn->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered
     FROM orders WHERE user_id=?"
);
$stats->bind_param("i", $user_id);
$stats->execute();
$order_stats = $stats->get_result()->fetch_assoc();
$stats->close();

// ── WISHLIST COUNT ────────────────────────────────────────────
$wl_count = 0;
if ($conn->query("SHOW TABLES LIKE 'wishlist'")->num_rows > 0) {
    $wq = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id=?");
    $wq->bind_param("i", $user_id); $wq->execute();
    $wl_count = $wq->get_result()->fetch_row()[0]; $wq->close();
}

// Active tab
$active_tab = $_GET['tab'] ?? 'dashboard';

// Status config
$STATUS_COLOR = [
    'pending'       => 'text-warning',
    'confirmed'     => 'text-info',
    'processing'    => 'text-primary',
    'stitching'     => 'text-purple',
    'quality_check' => 'text-teal',
    'shipped'       => 'text-primary',
    'delivered'     => 'text-green',
    'cancelled'     => 'text-danger',
    'refunded'      => 'text-secondary',
];
$full_name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? 'User');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>My Account | <?= htmlspecialchars($full_name) ?></title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif">
    <style>
    /* Status badge colors matching your site */
    .text-green  { color: #27ae60 !important; font-weight: 600; }
    .text-purple { color: #9b59b6 !important; font-weight: 600; }
    .text-teal   { color: #1abc9c !important; font-weight: 600; }
    .text-red    { color: #e74c3c !important; font-weight: 600; }
    /* Custom order row */
    .custom-order-badge {
        background: linear-gradient(135deg, #c9a84c, #e8c96a);
        color: #fff; font-size: 10px; padding: 2px 8px;
        border-radius: 20px; font-weight: 600;
    }
    /* Account nav active state */
    .nav-btn-style.active { background: var(--color-primary, #222) !important; color: #fff !important; }
    </style>
</head>
<body>

<?php
include_once "login-popup.php";
include_once "includes/header.php";
include_once "includes/mobile-bottom-nav.php";
?>

<!-- Breadcrumb -->
<div class="breadcrumb-section mb-100"
     style="background-image:linear-gradient(180deg,rgba(0,0,0,.35),rgba(0,0,0,.35)),url(assets/image/background/contact_hero.jpg);">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-center">
                <div class="banner-content style-2 text-center">
                    <h1>My Account</h1>
                    <ul class="breadcrumb-list">
                        <li><a href="<?= BASE_URL ?>">Home</a></li>
                        <li><span>/</span> My Account</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (!empty($_SESSION['success'])): ?>
<div class="container"><div class="alert alert-success alert-dismissible fade show mt-3">
    <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="container"><div class="alert alert-danger alert-dismissible fade show mt-3">
    <i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div></div>
<?php endif; ?>

<!-- Dashboard -->
<div class="dashboard-section mb-100">
    <div class="container">
        <div class="row g-lg-4 gy-5">

            <!-- ── LEFT NAV ─────────────────────────── -->
            <div class="col-lg-3">
                <div class="dashboard-left">
                    <div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">

                        <a href="my-account.php?tab=dashboard"
                           class="nav-link nav-btn-style mx-auto <?= $active_tab==='dashboard'?'active':'' ?>">
                            <svg width="20" height="20" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#c1)"><path d="M8.47911 7.33339H1.60411C0.719559 7.33339 0 6.61383 0 5.72911V1.60411C0 0.719559 0.719559 0 1.60411 0H8.47911C9.36383 0 10.0834 0.719559 10.0834 1.60411V5.72911C10.0834 6.61383 9.36383 7.33339 8.47911 7.33339Z"/><path d="M8.47911 22H1.60411C0.719559 22 0 21.2805 0 20.3959V10.7709C0 9.88618 0.719559 9.16663 1.60411 9.16663H8.47911C9.36383 9.16663 10.0834 9.88618 10.0834 10.7709V20.3959C10.0834 21.2805 9.36383 22 8.47911 22Z"/><path d="M20.3953 22H13.5203C12.6356 22 11.916 21.2805 11.916 20.3959V16.2709C11.916 15.3862 12.6356 14.6667 13.5203 14.6667H20.3953C21.2798 14.6667 21.9994 15.3862 21.9994 16.2709V20.3959C21.9994 21.2805 21.2798 22 20.3953 22Z"/><path d="M20.3953 12.8334H13.5203C12.6356 12.8334 11.916 12.1138 11.916 11.2291V1.60411C11.916 0.719559 12.6356 0 13.5203 0H20.3953C21.2798 0 21.9994 0.719559 21.9994 1.60411V11.2291C21.9994 12.1138 21.2798 12.8334 20.3953 12.8334Z"/>
                                <defs><clipPath id="c1"><rect width="22" height="22" fill="white"/></clipPath></defs></g>
                            </svg>Dashboard
                        </a>

                        <a href="my-account.php?tab=profile"
                           class="nav-link nav-btn-style mx-auto <?= $active_tab==='profile'?'active':'' ?>">
                            <svg width="20" height="20" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                <path d="M18.7782 14.2218C17.5801 13.0237 16.1541 12.1368 14.5982 11.5999C16.2646 10.4522 17.3594 8.53136 17.3594 6.35938C17.3594 2.85282 14.5066 0 11 0C7.49345 0 4.64062 2.85282 4.64062 6.35938C4.64062 8.53136 5.73543 10.4522 7.40188 11.5999C5.84598 12.1368 4.41994 13.0237 3.22184 14.2218C1.14421 16.2995 0 19.0618 0 22H1.71875C1.71875 16.8823 5.88229 12.7188 11 12.7188C16.1177 12.7188 20.2812 16.8823 20.2812 22H22C22 19.0618 20.8558 16.2995 18.7782 14.2218ZM11 11C8.44117 11 6.35938 8.91825 6.35938 6.35938C6.35938 3.8005 8.44117 1.71875 11 1.71875C13.5588 1.71875 15.6406 3.8005 15.6406 6.35938C15.6406 8.91825 13.5588 11 11 11Z"/>
                            </svg>My Profile
                        </a>

                        <a href="my-account.php?tab=orders"
                           class="nav-link nav-btn-style mx-auto <?= $active_tab==='orders'?'active':'' ?>">
                            <svg width="20" height="20" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.7115 18.1422L18.729 5.7622C18.6678 4.96461 17.9932 4.3398 17.1933 4.3398H15.2527V4.25257C15.2527 1.90768 13.345 0 11.0002 0C8.65527 0 6.74758 1.90768 6.74758 4.25257V4.3398H4.80703C4.00708 4.3398 3.33251 4.96457 3.2715 5.76052L2.28872 18.1439C2.21266 19.1354 2.55663 20.1225 3.23235 20.852C3.90808 21.5815 4.86598 22 5.86041 22H16.1399C17.1342 22 18.0922 21.5816 18.768 20.852C19.4437 20.1224 19.7876 19.1354 19.7115 18.1422ZM8.03622 4.25257C8.03622 2.61826 9.36588 1.28863 11.0002 1.28863C12.6344 1.28863 13.9641 2.6183 13.9641 4.25257V4.3398H8.03622V4.25257Z"/>
                            </svg>My Orders
                        </a>

                        <a href="my-account.php?tab=custom"
                           class="nav-link nav-btn-style mx-auto <?= $active_tab==='custom'?'active':'' ?>">
                            <svg width="20" height="20" viewBox="0 0 22 22" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 1a10 10 0 1 0 10 10A10.011 10.011 0 0 0 11 1zm0 18a8 8 0 1 1 8-8 8.009 8.009 0 0 1-8 8z"/><path d="M11 6a1 1 0 0 0-1 1v4.586L7.707 9.293a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.414 0l4-4a1 1 0 0 0-1.414-1.414L12 11.586V7a1 1 0 0 0-1-1z"/>
                            </svg>Custom Suits
                        </a>

                        <a href="my-account.php?tab=track"
                           class="nav-link nav-btn-style mx-auto <?= $active_tab==='track'?'active':'' ?>">
                            <svg width="20" height="20" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.7115 18.1422L18.729 5.7622C18.6678 4.96461 17.9932 4.3398 17.1933 4.3398H15.2527V4.25257C15.2527 1.90768 13.345 0 11.0002 0C8.65527 0 6.74758 1.90768 6.74758 4.25257V4.3398H4.80703C4.00708 4.3398 3.33251 4.96457 3.2715 5.76052L2.28872 18.1439C2.21266 19.1354 2.55663 20.1225 3.23235 20.852C3.90808 21.5815 4.86598 22 5.86041 22H16.1399C17.1342 22 18.0922 21.5816 18.768 20.852C19.4437 20.1224 19.7876 19.1354 19.7115 18.1422ZM13.9035 10.9263C13.652 10.6746 13.244 10.6746 12.9924 10.9263L10.1154 13.8033L9.00909 12.697C8.75751 12.4454 8.34952 12.4454 8.0979 12.697C7.84627 12.9486 7.84627 13.3566 8.0979 13.6082L9.65977 15.1701C9.78558 15.2959 9.9505 15.3588 10.1153 15.3588C10.2802 15.3588 10.4451 15.2959 10.5709 15.1701L13.9034 11.8375C14.1551 11.5858 14.1551 11.1779 13.9035 10.9263Z"/>
                            </svg>Order Tracking
                        </a>

                        <a href="my-account.php?logout=1" class="nav-link nav-btn-style mx-auto"
                           onclick="return confirm('Are you sure you want to log out?')">
                            <svg width="20" height="20" viewBox="0 0 22 22" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#c2)"><path d="M21.7273 10.4732L19.3734 8.81368C18.9473 8.51333 18.3574 8.81866 18.3574 9.34047V12.6595C18.3574 13.1834 18.9485 13.4856 19.3733 13.1863L21.7272 11.5268C22.0916 11.2699 22.0906 10.7294 21.7273 10.4732Z"/><path d="M18.4963 15.1385C18.1882 14.9603 17.7939 15.0655 17.6156 15.3737C16.1016 17.9911 13.2715 19.7482 10.0374 19.7482C5.21356 19.7482 1.28906 15.8237 1.28906 11C1.28906 6.17625 5.21356 2.25171 10.0374 2.25171C13.2736 2.25171 16.1025 4.0105 17.6156 6.62617C17.7938 6.93434 18.1882 7.03949 18.4962 6.86138C18.8043 6.68315 18.9096 6.28887 18.7314 5.98074C16.9902 2.97053 13.738 0.962646 10.0374 0.962646C4.48967 0.962646 0 5.45184 0 11C0 16.5477 4.48919 21.0373 10.0374 21.0373C13.7396 21.0373 16.9909 19.028 18.7315 16.0191C18.9097 15.711 18.8044 15.3168 18.4963 15.1385Z"/><path d="M7.05469 10.3555C6.69873 10.3555 6.41016 10.644 6.41016 11C6.41016 11.356 6.69873 11.6445 7.05469 11.6445H17.0677V10.3555H7.05469Z"/>
                                <defs><clipPath id="c2"><rect width="22" height="22"/></clipPath></defs></g>
                            </svg>Logout
                        </a>
                    </div>
                </div>
            </div>

            <!-- ── RIGHT CONTENT ────────────────────── -->
            <div class="col-lg-9">

                <!-- ┌─ DASHBOARD ─────────────────────── -->
                <?php if ($active_tab === 'dashboard'): ?>
                <div class="dashboard-area">
                    <h6>Hello, <strong><?= htmlspecialchars($full_name) ?>!</strong></h6>
                    <p>From your account dashboard you can view your orders, manage your profile and track deliveries.</p>

                    <div class="row g-4 mt-30">
                        <div class="col-md-4 col-sm-6">
                            <div class="dashboard-card">
                                <div class="header"><h5>Total Orders</h5></div>
                                <div class="body">
                                    <div class="counter-item"><h2 class="counter"><?= (int)($order_stats['total'] ?? 0) ?></h2></div>
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                                            <path d="M19.7115 18.1422L18.729 5.7622C18.6678 4.96461 17.9932 4.3398 17.1933 4.3398H15.2527V4.25257C15.2527 1.90768 13.345 0 11.0002 0C8.65527 0 6.74758 1.90768 6.74758 4.25257V4.3398H4.80703C4.00708 4.3398 3.33251 4.96457 3.2715 5.76052L2.28872 18.1439C2.21266 19.1354 2.55663 20.1225 3.23235 20.852C3.90808 21.5815 4.86598 22 5.86041 22H16.1399C17.1342 22 18.0922 21.5816 18.768 20.852C19.4437 20.1224 19.7876 19.1354 19.7115 18.1422Z" transform="scale(2.27)"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="dashboard-card">
                                <div class="header"><h5>Pending Orders</h5></div>
                                <div class="body">
                                    <div class="counter-item"><h2 class="counter"><?= (int)($order_stats['pending'] ?? 0) ?></h2></div>
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                                            <path d="M25 5a20 20 0 1 0 20 20A20.023 20.023 0 0 0 25 5zm0 36a16 16 0 1 1 16-16 16.018 16.018 0 0 1-16 16z"/><path d="M24 14h2v12.414l6.293 6.293-1.414 1.414L24 27.121V14z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="dashboard-card">
                                <div class="header"><h5>Custom Suits</h5></div>
                                <div class="body">
                                    <div class="counter-item"><h2 class="counter"><?= count($custom_orders) ?></h2></div>
                                    <div class="icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50">
                                            <path d="M45 20H30V5a5 5 0 0 0-10 0v15H5a5 5 0 0 0 0 10h15v15a5 5 0 0 0 10 0V30h15a5 5 0 0 0 0-10z"/>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent orders quick view -->
                    <?php if (!empty($orders)): ?>
                    <div class="mt-40">
                        <h5 class="mb-3">Recent Orders</h5>
                        <div class="table-wrapper">
                            <table class="eg-table order-table table mb-0">
                                <thead>
                                    <tr><th>Order #</th><th>Items</th><th>Total</th><th>Status</th><th>Date</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach(array_slice($orders, 0, 3) as $o): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($o['order_number']) ?></td>
                                    <td><?= (int)$o['item_count'] ?></td>
                                    <td>₹<?= number_format((float)$o['total'],0) ?></td>
                                    <td class="<?= $STATUS_COLOR[$o['status']] ?? '' ?>"><?= ucfirst(str_replace('_',' ',$o['status'])) ?></td>
                                    <td><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <a href="my-account.php?tab=orders" class="primary-btn mt-3 d-inline-block">View All Orders</a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ┌─ PROFILE ─────────────────────────── -->
                <?php elseif ($active_tab === 'profile'): ?>
                <div class="dashboard-profile">
                    <div class="table-title-area">
                        <h3>Edit Your Profile</h3>
                        <p>Update your personal information and change your password.</p>
                    </div>
                    <div class="form-wrapper">
                        <form action="my-account.php?tab=profile" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="first_name" placeholder="First Name *"
                                               value="<?= htmlspecialchars($user['first_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="last_name" placeholder="Last Name"
                                               value="<?= htmlspecialchars($user['last_name'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="mobile" placeholder="Mobile Number"
                                               value="<?= htmlspecialchars($user['mobile'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="email" placeholder="Email Address"
                                               value="<?= htmlspecialchars($user['email'] ?? '') ?>" disabled>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                </div>
                                <div class="col-12 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="address" placeholder="Present Address"
                                               value="<?= htmlspecialchars($user['address'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="city" placeholder="City"
                                               value="<?= htmlspecialchars($user['city'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="state" placeholder="State"
                                               value="<?= htmlspecialchars($user['state'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="pincode" placeholder="Pincode"
                                               value="<?= htmlspecialchars($user['pincode'] ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-30">
                                    <div class="form-inner">
                                        <input type="text" name="country" placeholder="Country"
                                               value="<?= htmlspecialchars($user['country'] ?? 'India') ?>">
                                    </div>
                                </div>
                                <div class="col-12 mb-10">
                                    <p class="text-muted" style="font-size:13px;"><strong>Change Password</strong> — leave blank to keep current password</p>
                                </div>
                                <div class="col-12 mb-30">
                                    <div class="form-inner">
                                        <input type="password" name="password" id="password4" placeholder="New Password (min 6 chars)">
                                        <i class="bi bi-eye-slash" id="togglePassword4"></i>
                                    </div>
                                </div>
                                <div class="col-12 mb-30">
                                    <div class="form-inner mb-0">
                                        <input type="password" name="confirm_password" id="password5" placeholder="Confirm New Password">
                                        <i class="bi bi-eye-slash" id="togglePassword5"></i>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="button-group">
                                        <button type="submit" name="update_profile" class="primary-btn">Update Profile</button>
                                        <a href="my-account.php" class="primary-btn black-bg">Cancel</a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ┌─ ORDERS ───────────────────────────── -->
                <?php elseif ($active_tab === 'orders'): ?>
                <div class="table-title-area">
                    <h3>My Orders</h3>
                    <form method="GET">
                        <input type="hidden" name="tab" value="orders">
                        <select name="show" onchange="this.form.submit()">
                            <option value="5"  <?= $orders_limit==5?'selected':'' ?>>Last 5 Orders</option>
                            <option value="10" <?= $orders_limit==10?'selected':'' ?>>Last 10 Orders</option>
                            <option value="20" <?= $orders_limit==20?'selected':'' ?>>Last 20 Orders</option>
                            <option value="50" <?= $orders_limit==50?'selected':'' ?>>Last 50 Orders</option>
                        </select>
                    </form>
                </div>
                <div class="table-wrapper">
                    <table class="eg-table order-table table mb-0">
                        <thead>
                            <tr><th>Image</th><th>Order ID</th><th>Product</th><th>Total</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No orders yet. <a href="shop.php">Start shopping!</a></td></tr>
                        <?php else: foreach ($orders as $o):
                            // Get first item image
                            $img_q = $conn->prepare("SELECT product_img FROM order_items WHERE order_id=? AND product_img IS NOT NULL AND product_img!='' LIMIT 1");
                            $img_q->bind_param("i", $o['id']); $img_q->execute();
                            $img_row = $img_q->get_result()->fetch_assoc(); $img_q->close();
                            $oimg = $img_row ? explode(',', $img_row['product_img'])[0] : '';
                        ?>
                            <tr>
                                <td data-label="Image">
                                    <?php if ($oimg && file_exists("assets/img/uploads/$oimg")): ?>
                                        <img src="assets/img/uploads/<?= htmlspecialchars($oimg) ?>" alt="" style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                                    <?php else: ?>
                                        <div style="width:60px;height:60px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#ccc;"><i class="bi bi-bag"></i></div>
                                    <?php endif; ?>
                                </td>
                                <td data-label="Order ID">#<?= htmlspecialchars($o['order_number']) ?></td>
                                <td data-label="Product"><?= (int)$o['item_count'] ?> item<?= $o['item_count']!=1?'s':'' ?></td>
                                <td data-label="Total">₹<?= number_format((float)$o['total'],0) ?></td>
                                <td data-label="Status" class="<?= $STATUS_COLOR[$o['status']] ?? '' ?>">
                                    <?= ucfirst(str_replace('_',' ',$o['status'])) ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ┌─ CUSTOM ORDERS ──────────────────────── -->
                <?php elseif ($active_tab === 'custom'): ?>
                <div class="table-title-area">
                    <h3>My Custom Suit Orders</h3>
                    <a href="suit-configurator.php" class="primary-btn">+ New Custom Suit</a>
                </div>
                <div class="table-wrapper">
                    <table class="eg-table order-table table mb-0">
                        <thead>
                            <tr><th>Product</th><th>Total</th><th>Delivery</th><th>Status</th><th>Date</th></tr>
                        </thead>
                        <tbody>
                        <?php if (empty($custom_orders)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">No custom orders yet. <a href="suit-configurator.php">Design your suit!</a></td></tr>
                        <?php else: foreach ($custom_orders as $co):
                            $co_img = $co['pro_img'] ? explode(',', $co['pro_img'])[0] : '';
                            $STATUS_COLORS_CO = [
                                'draft'=>'text-muted','confirmed'=>'text-info','in_progress'=>'text-primary',
                                'stitching'=>'text-purple','quality_check'=>'text-teal',
                                'ready'=>'text-warning','delivered'=>'text-green','cancelled'=>'text-danger',
                            ];
                        ?>
                            <tr>
                                <td data-label="Product">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($co_img && file_exists("assets/img/uploads/$co_img")): ?>
                                            <img src="assets/img/uploads/<?= htmlspecialchars($co_img) ?>" style="width:50px;height:50px;object-fit:cover;border-radius:6px;">
                                        <?php endif; ?>
                                        <div>
                                            <?= htmlspecialchars($co['pro_name'] ?? 'Custom Suit') ?>
                                            <span class="custom-order-badge ms-1">Custom</span>
                                        </div>
                                    </div>
                                </td>
                                <td>₹<?= number_format((float)$co['total_price'],0) ?></td>
                                <td><?= $co['expected_delivery'] ? date('d M Y', strtotime($co['expected_delivery'])) : '—' ?></td>
                                <td class="<?= $STATUS_COLORS_CO[$co['status']] ?? '' ?>">
                                    <?= ucfirst(str_replace('_',' ',$co['status'])) ?>
                                </td>
                                <td><?= date('d M Y', strtotime($co['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- ┌─ ORDER TRACKING ─────────────────────── -->
                <?php elseif ($active_tab === 'track'): ?>
                <div class="order-traking-area">
                    <p>Enter your Order ID and billing email to track your order status.</p>
                    <form action="track-order.php" method="POST">
                        <div class="row justify-content-center">
                            <div class="col-md-8 mb-25">
                                <div class="form-inner">
                                    <label>Order ID</label>
                                    <input type="text" name="order_number" placeholder="e.g. ORD-2024-001"
                                           value="<?= htmlspecialchars($_GET['order_number'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="form-inner">
                                    <label>Billing Email</label>
                                    <input type="email" name="email" placeholder="Your email address"
                                           value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                                </div>
                            </div>
                            <div class="col-md-5 d-flex justify-content-center">
                                <div class="button-group">
                                    <button type="submit" class="primary-btn black-bg">Track Order</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php endif; ?>

            </div><!-- /col-lg-9 -->
        </div><!-- /row -->
    </div><!-- /container -->
</div><!-- /dashboard-section -->

<?php include_once "includes/footer.php"; ?>

<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/jquery-ui.js"></script>
<script src="assets/js/waypoints.js"></script>
<script src="assets/js/jquery.counterup.js"></script>
<script src="assets/js/jquery.marquee.min.js"></script>
<script src="assets/js/popper.min.js"></script>
<script src="assets/js/swiper-bundle.min.js"></script>
<script src="assets/js/jquery.fancybox.min.js"></script>
<script src="assets/js/jquery.nice-select.min.js"></script>
<script src="assets/js/wow.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Password show/hide
['togglePassword4','togglePassword5'].forEach(id => {
    const toggle = document.getElementById(id);
    if (!toggle) return;
    const inputId = id === 'togglePassword4' ? 'password4' : 'password5';
    toggle.addEventListener('click', function() {
        const input = document.getElementById(inputId);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.classList.toggle('bi-eye');
        this.classList.toggle('bi-eye-slash');
    });
});
$(".marquee_text2").marquee({ direction:"left",duration:25000,gap:50,delayBeforeStart:0,duplicated:true,startVisible:true });
</script>
</body>
</html>