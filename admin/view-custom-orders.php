<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── STATUS CONFIG ─────────────────────────────────────────────
$STATUS_CONFIG = [
    'draft'         => ['label'=>'Draft',         'color'=>'#95a5a6','bg'=>'#eaecee','icon'=>'fa-file'],
    'confirmed'     => ['label'=>'Confirmed',      'color'=>'#3498db','bg'=>'#d1ecf1','icon'=>'fa-check-circle'],
    'in_progress'   => ['label'=>'In Progress',    'color'=>'#9b59b6','bg'=>'#e8d5f5','icon'=>'fa-cog'],
    'stitching'     => ['label'=>'Stitching',      'color'=>'#e91e8c','bg'=>'#fce4f2','icon'=>'fa-cut'],
    'quality_check' => ['label'=>'Quality Check',  'color'=>'#1abc9c','bg'=>'#d4efea','icon'=>'fa-search'],
    'ready'         => ['label'=>'Ready',          'color'=>'#f39c12','bg'=>'#fff3cd','icon'=>'fa-box-open'],
    'delivered'     => ['label'=>'Delivered',      'color'=>'#27ae60','bg'=>'#d5f5e3','icon'=>'fa-box'],
    'cancelled'     => ['label'=>'Cancelled',      'color'=>'#e74c3c','bg'=>'#fadbd8','icon'=>'fa-times-circle'],
];

// ── QUICK STATUS UPDATE ───────────────────────────────────────
if (isset($_POST['update_status'])) {
    $oid = (int)$_POST['order_id'];
    $st  = $_POST['status'];
    if (array_key_exists($st, $STATUS_CONFIG)) {
        $s = $conn->prepare("UPDATE custom_orders SET status=? WHERE id=?");
        $s->bind_param("si", $st, $oid);
        $s->execute(); $s->close();
        $_SESSION['success'] = "Status updated to " . $STATUS_CONFIG[$st]['label'] . "!";
    }
    header("Location: view-custom-orders.php");
    exit();
}

// ── FILTERS ──────────────────────────────────────────────────
$filter_status  = trim($_GET['status']  ?? '');
$filter_search  = trim($_GET['search']  ?? '');
$filter_date    = trim($_GET['date']    ?? '');
$page           = max(1, (int)($_GET['page'] ?? 1));
$perPage        = 15;
$offset         = ($page - 1) * $perPage;

$where  = [];
$params = [];
$types  = '';

if ($filter_status) {
    $where[]  = "co.status = ?";
    $params[] = $filter_status;
    $types   .= 's';
}
if ($filter_date) {
    $where[]  = "DATE(co.created_at) = ?";
    $params[] = $filter_date;
    $types   .= 's';
}
if ($filter_search) {
    $like     = "%$filter_search%";
    $where[]  = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR p.pro_name LIKE ? OR co.id LIKE ?)";
    $params   = array_merge($params, [$like,$like,$like,$like,$like]);
    $types   .= 'sssss';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";

// Count
$cnt_sql = "SELECT COUNT(*) FROM custom_orders co
            LEFT JOIN users u ON co.user_id = u.id
            LEFT JOIN products p ON co.product_id = p.id
            $where_sql";
if ($params) {
    $cs = $conn->prepare($cnt_sql);
    $cs->bind_param($types, ...$params);
    $cs->execute();
    $total = $cs->get_result()->fetch_row()[0];
    $cs->close();
} else {
    $total = $conn->query($cnt_sql)->fetch_row()[0];
}
$total_pages = (int)ceil($total / $perPage);

// Fetch
$sql = "SELECT co.*,
               u.first_name, u.last_name, u.email AS user_email, u.mobile,
               p.pro_name, p.pro_img,
               f.name AS fabric_name, f.swatch_color AS fabric_swatch,
               oc.name AS outer_color, oc.hex_code AS outer_hex,
               lc.name AS lining_color, lc.hex_code AS lining_hex,
               bc.name AS button_color, bc.hex_code AS button_hex
        FROM custom_orders co
        LEFT JOIN users u          ON co.user_id        = u.id
        LEFT JOIN products p       ON co.product_id     = p.id
        LEFT JOIN fabric_options f ON co.fabric_id      = f.id
        LEFT JOIN color_options oc ON co.outer_color_id = oc.id
        LEFT JOIN color_options lc ON co.lining_color_id= lc.id
        LEFT JOIN color_options bc ON co.button_color_id= bc.id
        $where_sql
        ORDER BY co.created_at DESC
        LIMIT ? OFFSET ?";

$fp = array_merge($params, [$perPage, $offset]);
$ft = $types . 'ii';
$st = $conn->prepare($sql);
$st->bind_param($ft, ...$fp);
$st->execute();
$orders = $st->get_result();
$st->close();

// ── STATS ─────────────────────────────────────────────────────
$stats = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='draft'         THEN 1 ELSE 0 END) AS draft,
        SUM(CASE WHEN status='confirmed'     THEN 1 ELSE 0 END) AS confirmed,
        SUM(CASE WHEN status='stitching'     THEN 1 ELSE 0 END) AS stitching,
        SUM(CASE WHEN status='quality_check' THEN 1 ELSE 0 END) AS quality_check,
        SUM(CASE WHEN status='ready'         THEN 1 ELSE 0 END) AS ready,
        SUM(CASE WHEN status='delivered'     THEN 1 ELSE 0 END) AS delivered,
        SUM(CASE WHEN status='cancelled'     THEN 1 ELSE 0 END) AS cancelled,
        SUM(total_price) AS revenue,
        SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS today
    FROM custom_orders
")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Custom Suit Orders | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .stat-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; padding:16px 20px; display:flex; align-items:center; gap:14px; }
        .stat-icon { width:46px; height:46px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:18px; flex-shrink:0; }
        .stat-val  { font-size:22px; font-weight:700; line-height:1; }
        .stat-lbl  { font-size:12px; color:#6c757d; margin-top:2px; }
        .status-pill { display:inline-flex; align-items:center; gap:5px; font-size:12px; font-weight:500; padding:4px 10px; border-radius:20px; }
        .status-dot  { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
        .filter-tab  { padding:6px 14px; border-radius:20px; font-size:12px; font-weight:500; border:1.5px solid #dee2e6; background:#fff; cursor:pointer; text-decoration:none; color:#495057; white-space:nowrap; transition:all 0.15s; }
        .filter-tab:hover  { border-color:#2c3e50; color:#2c3e50; }
        .filter-tab.active { background:#2c3e50; border-color:#2c3e50; color:#fff; }
        .swatch-row { display:flex; align-items:center; gap:4px; }
        .swatch-sm  { width:16px; height:16px; border-radius:50%; border:1.5px solid #dee2e6; display:inline-block; flex-shrink:0; }
        .prod-thumb { width:40px; height:40px; object-fit:cover; border-radius:6px; border:1px solid #eee; }
        .prod-thumb-ph { width:40px; height:40px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#ccc; }
        .order-row { transition:background 0.12s; cursor:pointer; }
        .order-row:hover { background:#f8f9fa; }
        .action-btn { width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; font-size:12px; }
        .delivery-date { font-size:11px; }
        .overdue { color:#dc3545; font-weight:600; }
        .upcoming { color:#f39c12; }
        .search-box { position:relative; }
        .search-box i { position:absolute; left:14px; top:50%; transform:translateY(-50%); color:#aaa; pointer-events:none; }
        .search-box input { padding-left:38px; border-radius:20px; }
        .empty-state { padding:50px 20px; text-align:center; color:#6c757d; }
        .status-select { border:none; background:transparent; font-size:12px; font-weight:500; cursor:pointer; padding:0; }
        .status-select:focus { outline:none; }
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

                <!-- Stats -->
                <div class="row g-3 mb-4">
                    <?php
                    $stat_items = [
                        ['icon'=>'fa-cut',         'color'=>'#e91e8c','bg'=>'#fce4f2','val'=>$stats['total'],        'lbl'=>'Total Custom Orders'],
                        ['icon'=>'fa-clock',        'color'=>'#3498db','bg'=>'#d1ecf1','val'=>$stats['confirmed'],    'lbl'=>'Confirmed'],
                        ['icon'=>'fa-scissors',     'color'=>'#9b59b6','bg'=>'#e8d5f5','val'=>$stats['stitching'],    'lbl'=>'In Stitching'],
                        ['icon'=>'fa-search',       'color'=>'#1abc9c','bg'=>'#d4efea','val'=>$stats['quality_check'],'lbl'=>'Quality Check'],
                        ['icon'=>'fa-box-open',     'color'=>'#f39c12','bg'=>'#fff3cd','val'=>$stats['ready'],        'lbl'=>'Ready for Pickup'],
                        ['icon'=>'fa-box',          'color'=>'#27ae60','bg'=>'#d5f5e3','val'=>$stats['delivered'],    'lbl'=>'Delivered'],
                        ['icon'=>'fa-rupee-sign',   'color'=>'#27ae60','bg'=>'#d5f5e3','val'=>'₹'.number_format((float)$stats['revenue'],0),'lbl'=>'Total Revenue'],
                        ['icon'=>'fa-calendar-day', 'color'=>'#9b59b6','bg'=>'#e8d5f5','val'=>$stats['today'],       'lbl'=>"Today's Orders"],
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
                    <!-- Header + filters -->
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                            <div>
                                <h2 class="fw-bold mb-0">Custom Suit Orders</h2>
                                <p class="text-muted small mb-0">
                                    <?= $total ?> order<?= $total != 1 ? 's' : '' ?>
                                    <?= $filter_status ? ' · ' . htmlspecialchars($filter_status) : '' ?>
                                </p>
                            </div>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <form method="GET" class="search-box mb-0">
                                    <i class="fas fa-search"></i>
                                    <input type="text" class="form-control form-control-sm" name="search"
                                           placeholder="Name, email, product..."
                                           value="<?= htmlspecialchars($filter_search) ?>"
                                           style="min-width:200px;">
                                    <?php if ($filter_status) echo "<input type='hidden' name='status' value='".htmlspecialchars($filter_status)."'>"; ?>
                                    <?php if ($filter_date)   echo "<input type='hidden' name='date'   value='".htmlspecialchars($filter_date)."'>"; ?>
                                </form>
                                <form method="GET">
                                    <input type="date" class="form-control form-control-sm" name="date"
                                           value="<?= htmlspecialchars($filter_date) ?>"
                                           onchange="this.form.submit()" style="width:150px;">
                                    <?php if ($filter_status) echo "<input type='hidden' name='status' value='".htmlspecialchars($filter_status)."'>"; ?>
                                    <?php if ($filter_search) echo "<input type='hidden' name='search' value='".htmlspecialchars($filter_search)."'>"; ?>
                                </form>
                                <?php if ($filter_status || $filter_search || $filter_date): ?>
                                    <a href="view-custom-orders.php" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Status tabs -->
                        <div class="d-flex flex-wrap gap-2">
                            <a href="view-custom-orders.php<?= $filter_search ? '?search='.urlencode($filter_search) : '' ?>"
                               class="filter-tab <?= !$filter_status ? 'active' : '' ?>">
                                All <span class="ms-1 badge <?= !$filter_status ? 'bg-light text-dark' : 'bg-secondary' ?>"><?= $stats['total'] ?></span>
                            </a>
                            <?php foreach ($STATUS_CONFIG as $key => $cfg):
                                $cnt = $stats[$key] ?? 0;
                            ?>
                                <a href="?status=<?= $key ?><?= $filter_search ? '&search='.urlencode($filter_search) : '' ?>"
                                   class="filter-tab <?= $filter_status === $key ? 'active' : '' ?>">
                                    <span class="status-dot" style="background:<?= $cfg['color'] ?>;"></span>
                                    <?= $cfg['label'] ?>
                                    <?php if ($cnt > 0): ?>
                                        <span class="ms-1 badge" style="background:<?= $cfg['color'] ?>20;color:<?= $cfg['color'] ?>;"><?= $cnt ?></span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="white_card_body p-0">
                        <?php if ($total == 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-cut fa-3x d-block mb-3 opacity-25"></i>
                                <h5 class="text-muted">No custom orders found</h5>
                                <?php if ($filter_status || $filter_search): ?>
                                    <a href="view-custom-orders.php" class="btn btn-sm btn-outline-secondary mt-2">Clear filters</a>
                                <?php else: ?>
                                    <p class="text-muted small">Custom suit orders placed by customers will appear here.</p>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>Product</th>
                                        <th>Customer</th>
                                        <th>Customization</th>
                                        <th>Total</th>
                                        <th>Delivery</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php while ($order = $orders->fetch_assoc()):
                                    $sc   = $STATUS_CONFIG[$order['status']] ?? $STATUS_CONFIG['draft'];
                                    $cust = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
                                    if (!$cust) $cust = 'Guest';

                                    // Delivery date check
                                    $delivery_html = '—';
                                    if ($order['expected_delivery']) {
                                        $ddate = strtotime($order['expected_delivery']);
                                        $today = strtotime('today');
                                        $diff  = (int)(($ddate - $today) / 86400);
                                        $dclass = '';
                                        if (!in_array($order['status'],['delivered','cancelled'])) {
                                            if ($diff < 0)      $dclass = 'overdue';
                                            elseif ($diff <= 3) $dclass = 'upcoming';
                                        }
                                        $delivery_html = "<span class='delivery-date $dclass'>".date('d M Y', $ddate);
                                        if ($dclass === 'overdue')   $delivery_html .= " <span class='badge bg-danger' style='font-size:9px;'>Overdue</span>";
                                        elseif ($dclass === 'upcoming') $delivery_html .= " <span class='badge bg-warning text-dark' style='font-size:9px;'>Soon</span>";
                                        $delivery_html .= "</span>";
                                    }

                                    // Parse customization JSON
                                    $custom_json = $order['customization_json'] ? json_decode($order['customization_json'], true) : [];
                                    $custom_count = is_array($custom_json) ? count($custom_json) : 0;
                                ?>
                                <tr class="order-row"
                                    onclick="window.location='view-custom-order-detail.php?id=<?= $order['id'] ?>'">
                                    <td onclick="event.stopPropagation()">
                                        <div class="fw-bold" style="font-size:13px;">#<?= $order['id'] ?></div>
                                        <small class="text-muted">Qty: <?= (int)$order['quantity'] ?></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <?php
                                            $img = $order['pro_img'] ? explode(',', $order['pro_img'])[0] : '';
                                            if ($img && file_exists("assets/img/uploads/$img")): ?>
                                                <img src="assets/img/uploads/<?= htmlspecialchars($img) ?>" class="prod-thumb" alt="">
                                            <?php else: ?>
                                                <div class="prod-thumb-ph"><i class="fas fa-tshirt"></i></div>
                                            <?php endif; ?>
                                            <div style="min-width:0;">
                                                <div class="fw-semibold text-truncate" style="font-size:13px;max-width:130px;">
                                                    <?= htmlspecialchars($order['pro_name'] ?? '—') ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold" style="font-size:13px;"><?= htmlspecialchars($cust) ?></div>
                                        <?php if ($order['user_email']): ?>
                                            <small class="text-muted d-block" style="font-size:11px;"><?= htmlspecialchars($order['user_email']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="swatch-row flex-wrap" style="gap:5px;">
                                            <?php if ($order['fabric_name']): ?>
                                                <span title="Fabric: <?= htmlspecialchars($order['fabric_name']) ?>">
                                                    <span class="swatch-sm" style="background:<?= htmlspecialchars($order['fabric_swatch'] ?? '#ccc') ?>;"></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($order['outer_color']): ?>
                                                <span title="Colour: <?= htmlspecialchars($order['outer_color']) ?>">
                                                    <span class="swatch-sm" style="background:<?= htmlspecialchars($order['outer_hex'] ?? '#ccc') ?>;"></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($order['lining_color']): ?>
                                                <span title="Lining: <?= htmlspecialchars($order['lining_color']) ?>">
                                                    <span class="swatch-sm" style="background:<?= htmlspecialchars($order['lining_hex'] ?? '#ccc') ?>;border-style:dashed;"></span>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($order['button_color']): ?>
                                                <span title="Button: <?= htmlspecialchars($order['button_color']) ?>">
                                                    <span class="swatch-sm" style="background:<?= htmlspecialchars($order['button_hex'] ?? '#ccc') ?>;border-radius:3px;"></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($custom_count > 0): ?>
                                            <small class="text-muted d-block mt-1" style="font-size:10px;">
                                                <i class="fas fa-sliders-h me-1"></i><?= $custom_count ?> customizations
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($order['measurement_id']): ?>
                                            <small class="text-success" style="font-size:10px;">
                                                <i class="fas fa-ruler me-1"></i>Measurements saved
                                            </small>
                                        <?php else: ?>
                                            <small class="text-warning" style="font-size:10px;">
                                                <i class="fas fa-exclamation-triangle me-1"></i>No measurements
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold">₹<?= number_format((float)$order['total_price'], 2) ?></div>
                                        <?php if ((float)$order['fabric_cost'] > 0 || (float)$order['customization_cost'] > 0): ?>
                                            <small class="text-muted" style="font-size:10px;">
                                                Base: ₹<?= number_format((float)$order['base_price'],0) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $delivery_html ?></td>
                                    <td onclick="event.stopPropagation()">
                                        <form action="" method="POST" class="d-flex align-items-center gap-1">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                                <span class="status-dot" style="background:<?= $sc['color'] ?>;"></span>
                                                <select name="status" class="status-select"
                                                        style="color:<?= $sc['color'] ?>; background:transparent;"
                                                        onchange="this.form.submit()">
                                                    <?php foreach ($STATUS_CONFIG as $key => $cfg): ?>
                                                        <option value="<?= $key ?>" <?= $order['status']===$key?'selected':'' ?>>
                                                            <?= $cfg['label'] ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </span>
                                            <button type="submit" name="update_status"
                                                    class="btn btn-sm btn-outline-secondary action-btn" title="Save">
                                                <i class="fas fa-check" style="font-size:10px;"></i>
                                            </button>
                                        </form>
                                    </td>
                                    <td>
                                        <div style="font-size:12px;"><?= date('d M Y', strtotime($order['created_at'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($order['created_at'])) ?></small>
                                    </td>
                                    <td class="text-center" onclick="event.stopPropagation()">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a href="view-custom-order-detail.php?id=<?= $order['id'] ?>"
                                               class="btn btn-sm btn-outline-primary action-btn" title="View detail">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="view-custom-order-detail.php?id=<?= $order['id'] ?>&print=1"
                                               target="_blank"
                                               class="btn btn-sm btn-outline-secondary action-btn" title="Print">
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
                                Showing <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> of <?= $total ?>
                            </small>
                            <nav>
                                <ul class="pagination mb-0 pagination-sm">
                                    <li class="page-item <?= $page<=1?'disabled':'' ?>">
                                        <a class="page-link" href="?page=<?= $page-1 ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>&date=<?= urlencode($filter_date) ?>">
                                            <i class="fas fa-chevron-left"></i>
                                        </a>
                                    </li>
                                    <?php
                                    $s = max(1,$page-2); $e = min($total_pages,$page+2);
                                    if ($s > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    for ($i=$s;$i<=$e;$i++):
                                    ?>
                                        <li class="page-item <?= $i==$page?'active':'' ?>">
                                            <a class="page-link" href="?page=<?= $i ?>&status=<?= urlencode($filter_status) ?>&search=<?= urlencode($filter_search) ?>&date=<?= urlencode($filter_date) ?>"><?= $i ?></a>
                                        </li>
                                    <?php endfor;
                                    if ($e < $total_pages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                    ?>
                                    <li class="page-item <?= $page>=$total_pages?'disabled':'' ?>">
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
</body>
</html>