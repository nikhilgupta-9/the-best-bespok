<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── AUTO-CREATE orders + order_items tables ──────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `orders` (
        `id`               INT(11)       NOT NULL AUTO_INCREMENT,
        `user_id`          INT(11)       DEFAULT NULL,
        `order_number`     VARCHAR(20)   NOT NULL,
        `status`           ENUM('pending','confirmed','processing','stitching','quality_check','shipped','delivered','cancelled','refunded')
                           NOT NULL DEFAULT 'pending',
        `subtotal`         DECIMAL(10,2) DEFAULT 0.00,
        `discount`         DECIMAL(10,2) DEFAULT 0.00,
        `tax`              DECIMAL(10,2) DEFAULT 0.00,
        `shipping_charge`  DECIMAL(10,2) DEFAULT 0.00,
        `total`            DECIMAL(10,2) DEFAULT 0.00,
        `payment_method`   ENUM('cod','online','upi','card') DEFAULT 'cod',
        `payment_status`   ENUM('pending','paid','failed','refunded') DEFAULT 'pending',
        `payment_id`       VARCHAR(100)  DEFAULT NULL,
        `shipping_name`    VARCHAR(100)  DEFAULT NULL,
        `shipping_phone`   VARCHAR(20)   DEFAULT NULL,
        `shipping_address` TEXT          DEFAULT NULL,
        `shipping_city`    VARCHAR(100)  DEFAULT NULL,
        `shipping_state`   VARCHAR(100)  DEFAULT NULL,
        `shipping_pincode` VARCHAR(10)   DEFAULT NULL,
        `notes`            TEXT          DEFAULT NULL,
        `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`       DATETIME      DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_order_number` (`order_number`),
        KEY `idx_user` (`user_id`),
        KEY `idx_status` (`status`),
        KEY `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$conn->query("
    CREATE TABLE IF NOT EXISTS `order_items` (
        `id`              INT(11)       NOT NULL AUTO_INCREMENT,
        `order_id`        INT(11)       NOT NULL,
        `product_id`      INT(10)       DEFAULT NULL,
        `custom_order_id` INT(11)       DEFAULT NULL,
        `product_name`    VARCHAR(255)  NOT NULL,
        `product_img`     VARCHAR(255)  DEFAULT NULL,
        `size`            VARCHAR(10)   DEFAULT NULL,
        `quantity`        INT(11)       DEFAULT 1,
        `unit_price`      DECIMAL(10,2) DEFAULT 0.00,
        `total_price`     DECIMAL(10,2) DEFAULT 0.00,
        PRIMARY KEY (`id`),
        KEY `idx_order` (`order_id`),
        CONSTRAINT `fk_oi_order` FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// ── STATUS UPDATE ────────────────────────────────────────────
if (isset($_POST['update_status'])) {
    $id     = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $valid  = ['pending','confirmed','processing','stitching','quality_check','shipped','delivered','cancelled','refunded'];
    if (in_array($status, $valid)) {
        $s = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $s->bind_param("si", $status, $id);
        $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
            ? "Order status updated to " . ucfirst($status) . "!"
            : "Update failed.";
        $s->close();
    }
    header("Location: view-orders.php");
    exit();
}

// ── PAYMENT STATUS UPDATE ────────────────────────────────────
if (isset($_POST['update_payment'])) {
    $id     = (int)$_POST['order_id'];
    $pstatus = $_POST['payment_status'];
    $valid   = ['pending','paid','failed','refunded'];
    if (in_array($pstatus, $valid)) {
        $s = $conn->prepare("UPDATE orders SET payment_status=? WHERE id=?");
        $s->bind_param("si", $pstatus, $id);
        $s->execute(); $s->close();
        $_SESSION['success'] = "Payment status updated!";
    }
    header("Location: view-orders.php");
    exit();
}

// ── FILTERS ──────────────────────────────────────────────────
$filter_status  = trim($_GET['status']  ?? '');
$filter_payment = trim($_GET['payment'] ?? '');
$filter_search  = trim($_GET['search']  ?? '');
$filter_date    = trim($_GET['date']    ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

$where  = [];
$params = [];
$types  = '';

if ($filter_status)  { $where[] = "o.status=?";         $params[] = $filter_status;  $types .= 's'; }
if ($filter_payment) { $where[] = "o.payment_status=?";  $params[] = $filter_payment; $types .= 's'; }
if ($filter_date)    { $where[] = "DATE(o.created_at)=?"; $params[] = $filter_date;    $types .= 's'; }
if ($filter_search) {
    $like     = "%$filter_search%";
    $where[]  = "(o.order_number LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ? OR o.shipping_city LIKE ?)";
    $params   = array_merge($params, [$like,$like,$like,$like]);
    $types   .= 'ssss';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count
$count_sql = "SELECT COUNT(*) FROM orders o $where_sql";
if ($params) {
    $cs = $conn->prepare($count_sql);
    $cs->bind_param($types, ...$params);
    $cs->execute();
    $total_orders = $cs->get_result()->fetch_row()[0];
    $cs->close();
} else {
    $total_orders = $conn->query($count_sql)->fetch_row()[0];
}
$total_pages = (int)ceil($total_orders / $perPage);

// Fetch orders
$sql = "SELECT o.*,
               u.first_name, u.last_name, u.email AS user_email,
               (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $where_sql
        ORDER BY o.created_at DESC
        LIMIT ? OFFSET ?";

$fetch_params = array_merge($params, [$perPage, $offset]);
$fetch_types  = $types . 'ii';
$st = $conn->prepare($sql);
$st->bind_param($fetch_types, ...$fetch_params);
$st->execute();
$orders = $st->get_result();
$st->close();

// ── STATS ────────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='pending'   THEN 1 ELSE 0 END) AS pending,
        SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status='stitching' THEN 1 ELSE 0 END) AS stitching,
        SUM(CASE WHEN status='shipped'   THEN 1 ELSE 0 END) AS shipped,
        SUM(CASE WHEN status='delivered' THEN 1 ELSE 0 END) AS delivered,
        SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
        SUM(CASE WHEN payment_status='paid' THEN total ELSE 0 END) AS revenue,
        SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today
    FROM orders
")->fetch_assoc();

// Status config
$STATUS_CONFIG = [
    'pending'       => ['label'=>'Pending',       'color'=>'#f39c12', 'bg'=>'#fff3cd', 'icon'=>'fa-clock'],
    'confirmed'     => ['label'=>'Confirmed',      'color'=>'#3498db', 'bg'=>'#d1ecf1', 'icon'=>'fa-check-circle'],
    'processing'    => ['label'=>'Processing',     'color'=>'#9b59b6', 'bg'=>'#e8d5f5', 'icon'=>'fa-cog'],
    'stitching'     => ['label'=>'Stitching',      'color'=>'#e91e8c', 'bg'=>'#fce4f2', 'icon'=>'fa-cut'],
    'quality_check' => ['label'=>'Quality Check',  'color'=>'#1abc9c', 'bg'=>'#d4efea', 'icon'=>'fa-search'],
    'shipped'       => ['label'=>'Shipped',        'color'=>'#2980b9', 'bg'=>'#d6eaf8', 'icon'=>'fa-truck'],
    'delivered'     => ['label'=>'Delivered',      'color'=>'#27ae60', 'bg'=>'#d5f5e3', 'icon'=>'fa-box'],
    'cancelled'     => ['label'=>'Cancelled',      'color'=>'#e74c3c', 'bg'=>'#fadbd8', 'icon'=>'fa-times-circle'],
    'refunded'      => ['label'=>'Refunded',       'color'=>'#7f8c8d', 'bg'=>'#eaecee', 'icon'=>'fa-undo'],
];
$PAYMENT_CONFIG = [
    'pending'  => ['label'=>'Pending',  'class'=>'bg-warning text-dark'],
    'paid'     => ['label'=>'Paid',     'class'=>'bg-success'],
    'failed'   => ['label'=>'Failed',   'class'=>'bg-danger'],
    'refunded' => ['label'=>'Refunded', 'class'=>'bg-secondary'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Order Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .stat-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; }
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
        .stat-val  { font-size:22px; font-weight:700; line-height:1; }
        .stat-lbl  { font-size:12px; color:#6c757d; margin-top:2px; }
        .status-pill {
            display:inline-flex; align-items:center; gap:5px;
            font-size:12px; font-weight:500; padding:4px 10px;
            border-radius:20px;
        }
        .status-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .filter-tab { padding:6px 14px; border-radius:20px; font-size:12px; font-weight:500;
                      border:1.5px solid #dee2e6; background:#fff; cursor:pointer; text-decoration:none;
                      color:#495057; white-space:nowrap; transition:all 0.15s; }
        .filter-tab:hover { border-color:#2c3e50; color:#2c3e50; }
        .filter-tab.active { background:#2c3e50; border-color:#2c3e50; color:#fff; }
        .order-row { transition:background 0.12s; }
        .order-row:hover { background:#f8f9fa; cursor:pointer; }
        .action-btn { width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; font-size:12px; }
        .payment-method-icon { font-size:14px; }
        .search-box { position:relative; }
        .search-box input { padding-left:38px; border-radius:20px; }
        .search-box i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#aaa; pointer-events:none; }
        .empty-state { padding:50px 20px; text-align:center; color:#6c757d; }
        .empty-state i { font-size:3.5rem; opacity:0.2; margin-bottom:16px; }
        /* Status update select */
        .status-select { border:none; background:transparent; font-size:12px; font-weight:500; cursor:pointer; padding:0; }
        .status-select:focus { outline:none; box-shadow:none; }
    </style>
</head>
<body class="crm_body_bg">
    <?php include "header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row"><div class="col-lg-12 p-0"><?php include "top_nav.php"; ?></div></div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-3">

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- ── Stats row ──────────────────────────── -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_items = [
                        ['icon'=>'fa-shopping-bag',  'color'=>'#3498db', 'bg'=>'#d6eaf8', 'val'=>$stats['total'],     'lbl'=>'Total Orders'],
                        ['icon'=>'fa-clock',          'color'=>'#f39c12', 'bg'=>'#fff3cd', 'val'=>$stats['pending'],   'lbl'=>'Pending'],
                        ['icon'=>'fa-cut',            'color'=>'#e91e8c', 'bg'=>'#fce4f2', 'val'=>$stats['stitching'], 'lbl'=>'In Stitching'],
                        ['icon'=>'fa-truck',          'color'=>'#2980b9', 'bg'=>'#d6eaf8', 'val'=>$stats['shipped'],   'lbl'=>'Shipped'],
                        ['icon'=>'fa-box',            'color'=>'#27ae60', 'bg'=>'#d5f5e3', 'val'=>$stats['delivered'], 'lbl'=>'Delivered'],
                        ['icon'=>'fa-rupee-sign',     'color'=>'#27ae60', 'bg'=>'#d5f5e3', 'val'=>'₹'.number_format($stats['revenue']?? 0,2), 'lbl'=>'Revenue (Paid)'],
                        ['icon'=>'fa-calendar-day',   'color'=>'#9b59b6', 'bg'=>'#e8d5f5', 'val'=>$stats['today'],    'lbl'=>"Today's Orders"],
                        ['icon'=>'fa-times-circle',   'color'=>'#e74c3c', 'bg'=>'#fadbd8', 'val'=>$stats['cancelled'],'lbl'=>'Cancelled'],
                    ];
                    foreach ($stat_items as $si):
                    ?>
                    <div class="col-6 col-sm-4 col-lg-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:<?= $si['bg'] ?>;">
                                <i class="fas <?= $si['icon'] ?>" style="color:<?= $si['color'] ?>;"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $si['val'] ?></div>
                                <div class="stat-lbl"><?= $si['lbl'] ?></div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="white_card mb_30">
                    <!-- ── Header + filters ───────────────── -->
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                            <div>
                                <h2 class="fw-bold mb-0">Order Management</h2>
                                <p class="text-muted small mb-0">
                                    <?= $total_orders ?> order<?= $total_orders != 1 ? 's' : '' ?>
                                    <?= $filter_status ? ' · filtered: ' . htmlspecialchars($filter_status) : '' ?>
                                </p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <!-- Search -->
                                <form method="GET" class="search-box mb-0">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control form-control-sm" name="search"
                                           placeholder="Order #, name, phone, city..."
                                           value="<?= htmlspecialchars($filter_search) ?>"
                                           style="min-width:220px;">
                                    <?php if ($filter_status)  echo "<input type='hidden' name='status' value='".htmlspecialchars($filter_status)."'>"; ?>
                                    <?php if ($filter_payment) echo "<input type='hidden' name='payment' value='".htmlspecialchars($filter_payment)."'>"; ?>
                                    <?php if ($filter_date)    echo "<input type='hidden' name='date' value='".htmlspecialchars($filter_date)."'>"; ?>
                                </form>
                                <!-- Date filter -->
                                <form method="GET">
                                    <input type="date" class="form-control form-control-sm" name="date"
                                           value="<?= htmlspecialchars($filter_date) ?>"
                                           onchange="this.form.submit()"
                                           title="Filter by date"
                                           style="width:150px;">
                                    <?php if ($filter_status)  echo "<input type='hidden' name='status' value='".htmlspecialchars($filter_status)."'>"; ?>
                                    <?php if ($filter_search)  echo "<input type='hidden' name='search' value='".htmlspecialchars($filter_search)."'>"; ?>
                                    <?php if ($filter_payment) echo "<input type='hidden' name='payment' value='".htmlspecialchars($filter_payment)."'>"; ?>
                                </form>
                                <?php if ($filter_status || $filter_search || $filter_date || $filter_payment): ?>
                                    <a href="view-orders.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status filter tabs -->
                        <div class="d-flex flex-wrap gap-2">
                            <a href="?<?= $filter_search ? 'search='.urlencode($filter_search).'&' : '' ?>"
                               class="filter-tab <?= !$filter_status ? 'active' : '' ?>">
                                All <span class="ms-1 badge <?= !$filter_status ? 'bg-light text-dark' : 'bg-secondary' ?>"><?= $stats['total'] ?></span>
                            </a>
                            <?php foreach ($STATUS_CONFIG as $key => $cfg): ?>
                                <?php $cnt = $stats[$key] ?? 0; ?>
                                <a href="?status=<?= $key ?><?= $filter_search ? '&search='.urlencode($filter_search) : '' ?>"
                                   class="filter-tab <?= $filter_status === $key ? 'active' : '' ?>"
                                   style="<?= $filter_status === $key ? '' : "border-color:{$cfg['color']}20;" ?>">
                                    <span class="status-dot" style="background:<?= $cfg['color'] ?>;"></span>
                                    <?= $cfg['label'] ?>
                                    <?php if ($cnt > 0): ?>
                                        <span class="ms-1 badge" style="background:<?= $cfg['color'] ?>20;color:<?= $cfg['color'] ?>;"><?= $cnt ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- ── Orders table ───────────────────── -->
                    <div class="white_card_body p-0">
                        <?php if ($total_orders == 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-shopping-bag d-block"></i>
                                <h5 class="text-muted">No orders found</h5>
                                <?php if ($filter_status || $filter_search): ?>
                                    <a href="view-orders.php" class="btn btn-sm btn-outline-secondary mt-2">Clear filters</a>
                                <?php else: ?>
                                    <p class="text-muted small">Orders placed by customers will appear here.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order</th>
                                        <th>Customer</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Order Status</th>
                                        <th>Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($order = $orders->fetch_assoc()):
                                    $sc   = $STATUS_CONFIG[$order['status']]  ?? $STATUS_CONFIG['pending'];
                                    $pc   = $PAYMENT_CONFIG[$order['payment_status']] ?? $PAYMENT_CONFIG['pending'];
                                    $cust = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
                                    if (!$cust) $cust = $order['shipping_name'] ?? 'Guest';
                                    $pm_icon = match($order['payment_method']) {
                                        'upi'    => 'fa-mobile-alt',
                                        'card'   => 'fa-credit-card',
                                        'online' => 'fa-globe',
                                        default  => 'fa-money-bill-wave',
                                    };
                                ?>
                                <tr class="order-row"
                                    onclick="window.location='view-order-detail.php?id=<?= $order['id'] ?>'"
                                    style="cursor:pointer;">
                                    <td onclick="event.stopPropagation()">
                                        <div class="fw-bold" style="font-size:13px;">
                                            #<?= htmlspecialchars($order['order_number']) ?>
                                        </div>
                                        <small class="text-muted">ID: <?= $order['id'] ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:13px;">
                                            <?= htmlspecialchars($cust) ?>
                                        </div>
                                        <?php if ($order['shipping_phone']): ?>
                                            <small class="text-muted">
                                                <i class="fas fa-phone" style="font-size:10px;"></i>
                                                <?= htmlspecialchars($order['shipping_phone']) ?>
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($order['shipping_city']): ?>
                                            <small class="text-muted d-block">
                                                <i class="fas fa-map-marker-alt" style="font-size:10px;"></i>
                                                <?= htmlspecialchars($order['shipping_city']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$order['item_count'] ?> item<?= $order['item_count'] != 1 ? 's' : '' ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold">
                                        ₹<?= number_format((float)$order['total'], 2) ?>
                                        <?php if ((float)$order['discount'] > 0): ?>
                                            <div class="text-success" style="font-size:11px;">
                                                -₹<?= number_format((float)$order['discount'],2) ?> off
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <div class="d-flex flex-column gap-1">
                                            <span class="badge <?= $pc['class'] ?>" style="font-size:11px;">
                                                <?= $pc['label'] ?>
                                            </span>
                                            <small class="text-muted">
                                                <i class="fas <?= $pm_icon ?> payment-method-icon"></i>
                                                <?= strtoupper($order['payment_method']) ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td onclick="event.stopPropagation()">
                                        <!-- Inline status update -->
                                        <form action="" method="POST" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                                <span class="status-dot" style="background:<?= $sc['color'] ?>;"></span>
                                                <select name="status" class="status-select"
                                                        style="color:<?= $sc['color'] ?>; background:transparent;"
                                                        onchange="this.form.submit()">
                                                    <?php foreach ($STATUS_CONFIG as $key => $cfg): ?>
                                                        <option value="<?= $key ?>"
                                                                <?= $order['status'] === $key ? 'selected' : '' ?>>
                                                            <?= $cfg['label'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </span>
                                            <button type="submit" name="update_status" class="btn btn-sm btn-outline-secondary action-btn" title="Save status">
                                                <i class="fas fa-check" style="font-size:10px;"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="font-size:12px;">
                                            <?= date('d M Y', strtotime($order['created_at'])) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= date('h:i A', strtotime($order['created_at'])) ?>
                                        </small>
                                    </td>
                                    <td class="text-center" onclick="event.stopPropagation()">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="view-order-detail.php?id=<?= $order['id'] ?>"
                                               class="btn btn-sm btn-outline-primary action-btn" title="View detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="view-order-detail.php?id=<?= $order['id'] ?>&print=1"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary action-btn" title="Print invoice">
                                                <i class="fas fa-print"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <div class="d-flex justify-content-between align-items-center p-3 border-top flex-wrap gap-2">
                            <small class="text-muted">
                                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total_orders) ?> of <?= $total_orders ?>
                            </small>
                            <nav>
                                <ul class="pagination mb-0 pagination-sm">
                                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>&date=<?= urlencode($filter_date) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $start = max(1, $page-2);
                                    $end   = min($total_pages, $page+2);
                                    if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    for ($i = $start; $i <= $end; $i++):
                                    ?>
                                        <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>&date=<?= urlencode($filter_date) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($end < $total_pages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    ?>
                                    <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                        <a class="page-link" href="?page=<?= $page+1 ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>&date=<?= urlencode($filter_date) ?>">
                                            <i class="fas fa-chevron-right"></i>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>
    
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

</body>
</html>