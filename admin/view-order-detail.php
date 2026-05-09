<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: view-orders.php"); exit(); }

// ── STATUS CONFIG ─────────────────────────────────────────────
$STATUS_CONFIG = [
    'pending'       => ['label'=>'Pending',      'color'=>'#f39c12','bg'=>'#fff3cd','icon'=>'fa-clock'],
    'confirmed'     => ['label'=>'Confirmed',     'color'=>'#3498db','bg'=>'#d1ecf1','icon'=>'fa-check-circle'],
    'processing'    => ['label'=>'Processing',    'color'=>'#9b59b6','bg'=>'#e8d5f5','icon'=>'fa-cog'],
    'stitching'     => ['label'=>'Stitching',     'color'=>'#e91e8c','bg'=>'#fce4f2','icon'=>'fa-cut'],
    'quality_check' => ['label'=>'Quality Check', 'color'=>'#1abc9c','bg'=>'#d4efea','icon'=>'fa-search'],
    'shipped'       => ['label'=>'Shipped',       'color'=>'#2980b9','bg'=>'#d6eaf8','icon'=>'fa-truck'],
    'delivered'     => ['label'=>'Delivered',     'color'=>'#27ae60','bg'=>'#d5f5e3','icon'=>'fa-box'],
    'cancelled'     => ['label'=>'Cancelled',     'color'=>'#e74c3c','bg'=>'#fadbd8','icon'=>'fa-times-circle'],
    'refunded'      => ['label'=>'Refunded',      'color'=>'#7f8c8d','bg'=>'#eaecee','icon'=>'fa-undo'],
];
$STATUS_PIPELINE = ['pending','confirmed','processing','stitching','quality_check','shipped','delivered'];

// ── HANDLE ACTIONS ────────────────────────────────────────────
if (isset($_POST['update_status'])) {
    $st = $_POST['status'];
    if (array_key_exists($st, $STATUS_CONFIG)) {
        $s = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
        $s->bind_param("si", $st, $id); $s->execute(); $s->close();
        $_SESSION['success'] = "Status updated to " . $STATUS_CONFIG[$st]['label'] . "!";
    }
    header("Location: view-order-detail.php?id=$id"); exit();
}

if (isset($_POST['update_payment'])) {
    $ps = $_POST['payment_status'];
    $s = $conn->prepare("UPDATE orders SET payment_status=? WHERE id=?");
    $s->bind_param("si", $ps, $id); $s->execute(); $s->close();
    $_SESSION['success'] = "Payment status updated!";
    header("Location: view-order-detail.php?id=$id"); exit();
}

if (isset($_POST['save_notes'])) {
    $notes = trim($_POST['notes']);
    $s = $conn->prepare("UPDATE orders SET notes=? WHERE id=?");
    $s->bind_param("si", $notes, $id); $s->execute(); $s->close();
    $_SESSION['success'] = "Notes saved!";
    header("Location: view-order-detail.php?id=$id"); exit();
}

if (isset($_POST['add_item'])) {
    $product_id   = (int)$_POST['product_id'];
    $product_name = trim($_POST['product_name']);
    $size         = trim($_POST['size']);
    $qty          = max(1, (int)$_POST['quantity']);
    $unit_price   = (float)$_POST['unit_price'];
    $total_price  = $qty * $unit_price;

    // Get product image
    $img = '';
    if ($product_id) {
        $pi = $conn->prepare("SELECT pro_img FROM products WHERE id=?");
        $pi->bind_param("i", $product_id); $pi->execute();
        $pr = $pi->get_result()->fetch_assoc(); $pi->close();
        $img = $pr ? explode(',', $pr['pro_img'])[0] : '';
    }

    $s = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, product_name, product_img, size, quantity, unit_price, total_price)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $s->bind_param("iisssidd", $id, $product_id, $product_name, $img, $size, $qty, $unit_price, $total_price);
    $s->execute(); $s->close();

    // Recalculate order total
    $conn->query("UPDATE orders SET subtotal=(SELECT IFNULL(SUM(total_price),0) FROM order_items WHERE order_id=$id),
                  total=subtotal+shipping_charge+tax-discount WHERE id=$id");
    $_SESSION['success'] = "Item added!";
    header("Location: view-order-detail.php?id=$id"); exit();
}

if (isset($_GET['remove_item'])) {
    $item_id = (int)$_GET['remove_item'];
    $s = $conn->prepare("DELETE FROM order_items WHERE id=? AND order_id=?");
    $s->bind_param("ii", $item_id, $id); $s->execute(); $s->close();
    $conn->query("UPDATE orders SET subtotal=(SELECT IFNULL(SUM(total_price),0) FROM order_items WHERE order_id=$id),
                  total=subtotal+shipping_charge+tax-discount WHERE id=$id");
    $_SESSION['success'] = "Item removed!";
    header("Location: view-order-detail.php?id=$id"); exit();
}

// ── FETCH ORDER ───────────────────────────────────────────────
$stmt = $conn->prepare(
    "SELECT o.*, u.first_name, u.last_name, u.email AS user_email, u.mobile
     FROM orders o
     LEFT JOIN users u ON o.user_id = u.id
     WHERE o.id=? LIMIT 1"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { $_SESSION['error'] = "Order not found."; header("Location: view-orders.php"); exit(); }

// ── FETCH ITEMS ───────────────────────────────────────────────
$items_res = $conn->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY id ASC");
$items_res->bind_param("i", $id); $items_res->execute();
$items = $items_res->get_result()->fetchAll(MYSQLI_ASSOC);
$items_res->close();

// ── FETCH PRODUCTS for add-item dropdown ─────────────────────
$products = $conn->query("SELECT id, pro_name, selling_price FROM products WHERE status=1 ORDER BY pro_name ASC");

// ── FETCH LOGO ────────────────────────────────────────────────
$logo = $conn->query("SELECT logo_path FROM logos WHERE location='header' AND is_active=1 LIMIT 1")->fetch_assoc();

$sc   = $STATUS_CONFIG[$order['status']]   ?? $STATUS_CONFIG['pending'];
$cust = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
if (!$cust) $cust = $order['shipping_name'] ?? 'Guest';

// Current pipeline step index
$pipeline_idx = array_search($order['status'], $STATUS_PIPELINE);

$is_print = isset($_GET['print']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Order #<?= htmlspecialchars($order['order_number']) ?> | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php if (!$is_print) include "links.php"; ?>
    <?php if ($is_print): ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>
    <style>
        /* ── Pipeline timeline ───────────────────── */
        .pipeline { display:flex; align-items:flex-start; gap:0; overflow-x:auto; padding:10px 0; }
        .pipeline-step { display:flex; flex-direction:column; align-items:center; min-width:90px; position:relative; }
        .pipeline-step:not(:last-child)::after {
            content:''; position:absolute; top:18px; left:calc(50% + 18px);
            width:calc(100% - 36px); height:3px; background:#dee2e6; z-index:0;
        }
        .pipeline-step.done::after   { background:#27ae60; }
        .pipeline-step.active::after { background:linear-gradient(90deg,#27ae60,#dee2e6); }
        .pipeline-dot {
            width:36px; height:36px; border-radius:50%; border:3px solid #dee2e6;
            background:#fff; display:flex; align-items:center; justify-content:center;
            font-size:14px; position:relative; z-index:1; color:#dee2e6;
            transition:all 0.2s;
        }
        .pipeline-step.done   .pipeline-dot { background:#27ae60; border-color:#27ae60; color:#fff; }
        .pipeline-step.active .pipeline-dot { background:#2c3e50; border-color:#2c3e50; color:#fff; box-shadow:0 0 0 4px rgba(44,62,80,0.15); }
        .pipeline-label { font-size:10px; color:#aaa; margin-top:5px; text-align:center; max-width:80px; line-height:1.3; }
        .pipeline-step.done   .pipeline-label { color:#27ae60; font-weight:600; }
        .pipeline-step.active .pipeline-label { color:#2c3e50; font-weight:600; }

        /* ── Cards ───────────────────────────────── */
        .detail-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; margin-bottom:16px; }
        .detail-card-header { padding:14px 18px; background:#f8f9fa; border-bottom:1px solid #e9ecef; font-weight:600; font-size:14px; display:flex; justify-content:space-between; align-items:center; }
        .detail-card-body { padding:18px; }

        /* ── Address box ─────────────────────────── */
        .address-box { background:#f8f9fa; border-radius:8px; padding:14px; font-size:13px; line-height:1.7; }

        /* ── Item row ────────────────────────────── */
        .item-img { width:44px; height:44px; object-fit:cover; border-radius:6px; border:1px solid #eee; }
        .item-img-placeholder { width:44px; height:44px; border-radius:6px; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#ccc; flex-shrink:0; }

        /* ── Summary table ───────────────────────── */
        .summary-row { display:flex; justify-content:space-between; padding:6px 0; font-size:14px; }
        .summary-row.total { font-size:16px; font-weight:700; border-top:2px solid #2c3e50; margin-top:6px; padding-top:10px; }

        /* ── Action btn ──────────────────────────── */
        .action-btn { width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; font-size:12px; }

        /* ── Print styles ────────────────────────── */
        @media print {
            .no-print { display:none !important; }
            body { background:#fff !important; }
            .detail-card { box-shadow:none !important; border:1px solid #ccc !important; }
        }

        /* ── Status pill ─────────────────────────── */
        .status-pill { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; padding:5px 12px; border-radius:20px; }
        .status-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
    </style>
</head>
<body class="<?= $is_print ? '' : 'crm_body_bg' ?>">
    <?php if (!$is_print) include "header.php"; ?>

    <?php if (!$is_print): ?>
    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row"><div class="col-lg-12 p-0"><?php include "top_nav.php"; ?></div></div>
        </div>
    <?php endif; ?>

        <div class="<?= $is_print ? 'container py-4' : 'main_content_iner' ?>">
            <div class="<?= $is_print ? '' : 'container-fluid p-3' ?>">

                <?php if (!$is_print): ?>
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Back + action bar -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
                    <a href="view-orders.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Orders
                    </a>
                    <div class="d-flex gap-2">
                        <a href="view-order-detail.php?id=<?= $id ?>&print=1"
                           target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-print me-1"></i>Print Invoice
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ══════════════════════════════════════════
                     INVOICE HEADER (print visible)
                ══════════════════════════════════════════ -->
                <?php if ($is_print): ?>
                <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                    <div>
                        <?php if ($logo): ?>
                            <img src="<?= htmlspecialchars($logo['logo_path']) ?>" height="50" alt="Logo" class="mb-2">
                        <?php else: ?>
                            <h4 class="fw-bold">Your Tailor Shop</h4>
                        <?php endif; ?>
                        <div class="text-muted small">Tax Invoice</div>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold mb-1">Invoice #<?= htmlspecialchars($order['order_number']) ?></h5>
                        <div class="text-muted small">Date: <?= date('d M Y', strtotime($order['created_at'])) ?></div>
                        <div class="mt-2">
                            <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                <span class="status-dot" style="background:<?= $sc['color'] ?>;"></span>
                                <?= $sc['label'] ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row g-3">

                    <!-- ══════════════════════════════════════
                         LEFT COLUMN
                    ══════════════════════════════════════ -->
                    <div class="col-lg-8">

                        <!-- Order header card -->
                        <?php if (!$is_print): ?>
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <div>
                                    <span class="me-3">
                                        <i class="fas fa-hashtag text-muted"></i>
                                        <strong><?= htmlspecialchars($order['order_number']) ?></strong>
                                    </span>
                                    <span class="text-muted" style="font-size:12px;">
                                        <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                                <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                    <span class="status-dot" style="background:<?= $sc['color'] ?>;"></span>
                                    <?= $sc['label'] ?>
                                </span>
                            </div>
                            <!-- Status pipeline -->
                            <div class="detail-card-body">
                                <div class="pipeline">
                                    <?php foreach ($STATUS_PIPELINE as $si => $step):
                                        $cfg     = $STATUS_CONFIG[$step];
                                        $isDone  = $pipeline_idx !== false && $si < $pipeline_idx;
                                        $isActive= $pipeline_idx !== false && $si === $pipeline_idx;
                                        $cls     = $isDone ? 'done' : ($isActive ? 'active' : '');
                                    ?>
                                        <div class="pipeline-step <?= $cls ?>">
                                            <div class="pipeline-dot">
                                                <i class="fas <?= $isDone ? 'fa-check' : $cfg['icon'] ?>"></i>
                                            </div>
                                            <div class="pipeline-label"><?= $cfg['label'] ?></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <!-- Status update form -->
                                <?php if (!in_array($order['status'], ['delivered','cancelled','refunded'])): ?>
                                <form action="" method="POST" class="d-flex align-items-center gap-2 mt-3 no-print">
                                    <select name="status" class="form-select form-select-sm" style="max-width:200px;">
                                        <?php foreach ($STATUS_CONFIG as $key => $cfg): ?>
                                            <option value="<?= $key ?>" <?= $order['status']===$key ? 'selected' : '' ?>>
                                                <?= $cfg['label'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-sm btn-primary">
                                        <i class="fas fa-save me-1"></i>Update Status
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Order items -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-box me-2 text-muted"></i>Order Items (<?= count($items) ?>)</span>
                                <?php if (!$is_print): ?>
                                <button class="btn btn-sm btn-outline-primary no-print"
                                        data-bs-toggle="modal" data-bs-target="#addItemModal">
                                    <i class="fas fa-plus me-1"></i>Add Item
                                </button>
                                <?php endif; ?>
                            </div>
                            <div class="detail-card-body p-0">
                                <?php if (empty($items)): ?>
                                    <div class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2 d-block opacity-25"></i>
                                        No items added yet.
                                    </div>
                                <?php else: ?>
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th colspan="2">Product</th>
                                            <th class="text-center">Size</th>
                                            <th class="text-center">Qty</th>
                                            <th class="text-end">Unit Price</th>
                                            <th class="text-end">Total</th>
                                            <?php if (!$is_print): ?><th></th><?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($items as $item): ?>
                                        <tr>
                                            <td style="width:52px;">
                                                <?php $img = $item['product_img'] ? explode(',', $item['product_img'])[0] : ''; ?>
                                                <?php if ($img && file_exists("assets/img/uploads/$img")): ?>
                                                    <img src="assets/img/uploads/<?= htmlspecialchars($img) ?>" class="item-img" alt="">
                                                <?php else: ?>
                                                    <div class="item-img-placeholder"><i class="fas fa-tshirt"></i></div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="fw-semibold" style="font-size:13px;"><?= htmlspecialchars($item['product_name']) ?></div>
                                                <?php if ($item['product_id']): ?>
                                                    <small class="text-muted">ID: <?= $item['product_id'] ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?= $item['size'] ? '<span class="badge bg-light text-dark border">'.htmlspecialchars($item['size']).'</span>' : '<span class="text-muted">—</span>' ?>
                                            </td>
                                            <td class="text-center fw-semibold"><?= (int)$item['quantity'] ?></td>
                                            <td class="text-end">₹<?= number_format((float)$item['unit_price'], 2) ?></td>
                                            <td class="text-end fw-bold">₹<?= number_format((float)$item['total_price'], 2) ?></td>
                                            <?php if (!$is_print): ?>
                                            <td class="no-print">
                                                <a href="?id=<?= $id ?>&remove_item=<?= $item['id'] ?>"
                                                   class="btn btn-sm btn-outline-danger action-btn"
                                                   onclick="return confirm('Remove this item?')" title="Remove">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Notes (admin only) -->
                        <?php if (!$is_print): ?>
                        <div class="detail-card no-print">
                            <div class="detail-card-header">
                                <span><i class="fas fa-sticky-note me-2 text-muted"></i>Admin Notes</span>
                            </div>
                            <div class="detail-card-body">
                                <form action="" method="POST">
                                    <textarea class="form-control" name="notes" rows="3"
                                              placeholder="Internal notes about this order..."><?= htmlspecialchars($order['notes'] ?? '') ?></textarea>
                                    <button type="submit" name="save_notes" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="fas fa-save me-1"></i>Save Notes
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php elseif (!empty($order['notes'])): ?>
                        <div class="detail-card">
                            <div class="detail-card-header">Notes</div>
                            <div class="detail-card-body">
                                <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- ══════════════════════════════════════
                         RIGHT COLUMN
                    ══════════════════════════════════════ -->
                    <div class="col-lg-4">

                        <!-- Order summary -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-receipt me-2 text-muted"></i>Order Summary</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="summary-row">
                                    <span class="text-muted">Subtotal</span>
                                    <span>₹<?= number_format((float)$order['subtotal'], 2) ?></span>
                                </div>
                                <?php if ((float)$order['discount'] > 0): ?>
                                <div class="summary-row">
                                    <span class="text-success">Discount</span>
                                    <span class="text-success">-₹<?= number_format((float)$order['discount'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ((float)$order['tax'] > 0): ?>
                                <div class="summary-row">
                                    <span class="text-muted">Tax / GST</span>
                                    <span>₹<?= number_format((float)$order['tax'], 2) ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ((float)$order['shipping_charge'] > 0): ?>
                                <div class="summary-row">
                                    <span class="text-muted">Shipping</span>
                                    <span>₹<?= number_format((float)$order['shipping_charge'], 2) ?></span>
                                </div>
                                <?php else: ?>
                                <div class="summary-row">
                                    <span class="text-muted">Shipping</span>
                                    <span class="text-success">Free</span>
                                </div>
                                <?php endif; ?>
                                <div class="summary-row total">
                                    <span>Total</span>
                                    <span>₹<?= number_format((float)$order['total'], 2) ?></span>
                                </div>

                                <!-- Payment info -->
                                <div class="mt-3 pt-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-muted" style="font-size:12px;">Payment Method</span>
                                        <span class="fw-semibold" style="font-size:13px;">
                                            <?= strtoupper($order['payment_method']) ?>
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted" style="font-size:12px;">Payment Status</span>
                                        <?php
                                        $pbg = match($order['payment_status']) {
                                            'paid'     => '#d5f5e3', 'failed'  => '#fadbd8',
                                            'refunded' => '#eaecee', default   => '#fff3cd',
                                        };
                                        $pcolor = match($order['payment_status']) {
                                            'paid'     => '#27ae60', 'failed'  => '#e74c3c',
                                            'refunded' => '#7f8c8d', default   => '#f39c12',
                                        };
                                        ?>
                                        <span class="badge" style="background:<?= $pbg ?>;color:<?= $pcolor ?>;font-size:12px;">
                                            <?= ucfirst($order['payment_status']) ?>
                                        </span>
                                    </div>
                                    <?php if ($order['payment_id']): ?>
                                    <div class="mt-2" style="font-size:11px;color:#aaa;">
                                        Txn: <?= htmlspecialchars($order['payment_id']) ?>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Payment status update -->
                                    <?php if (!$is_print): ?>
                                    <form action="" method="POST" class="mt-3 no-print">
                                        <select name="payment_status" class="form-select form-select-sm mb-2">
                                            <?php foreach (['pending','paid','failed','refunded'] as $ps): ?>
                                                <option value="<?= $ps ?>" <?= $order['payment_status']===$ps ? 'selected' : '' ?>>
                                                    <?= ucfirst($ps) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" name="update_payment" class="btn btn-sm btn-outline-secondary w-100">
                                            <i class="fas fa-save me-1"></i>Update Payment Status
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Customer info -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-user me-2 text-muted"></i>Customer</span>
                                <?php if ($order['user_id'] && !$is_print): ?>
                                    <a href="all-admin.php" class="btn btn-sm btn-outline-secondary" style="font-size:11px;">View</a>
                                <?php endif; ?>
                            </div>
                            <div class="detail-card-body">
                                <div class="fw-semibold mb-1"><?= htmlspecialchars($cust) ?></div>
                                <?php if ($order['user_email']): ?>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($order['user_email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['mobile'] ?? false): ?>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($order['mobile']) ?>
                                    </div>
                                <?php elseif ($order['shipping_phone']): ?>
                                    <div class="text-muted" style="font-size:12px;">
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($order['shipping_phone']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Shipping address -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-map-marker-alt me-2 text-muted"></i>Shipping Address</span>
                            </div>
                            <div class="detail-card-body">
                                <?php if ($order['shipping_address'] || $order['shipping_city']): ?>
                                <div class="address-box">
                                    <strong><?= htmlspecialchars($order['shipping_name'] ?? $cust) ?></strong><br>
                                    <?php if ($order['shipping_address']): ?>
                                        <?= nl2br(htmlspecialchars($order['shipping_address'])) ?><br>
                                    <?php endif; ?>
                                    <?php if ($order['shipping_city']): ?>
                                        <?= htmlspecialchars($order['shipping_city']) ?>
                                        <?= $order['shipping_state'] ? ', '.htmlspecialchars($order['shipping_state']) : '' ?>
                                        <?= $order['shipping_pincode'] ? ' - '.htmlspecialchars($order['shipping_pincode']) : '' ?>
                                    <?php endif; ?>
                                </div>
                                <?php else: ?>
                                    <p class="text-muted mb-0 small">No shipping address provided.</p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Print button (non-print view) -->
                        <?php if (!$is_print): ?>
                        <div class="no-print">
                            <button onclick="window.print()" class="btn btn-outline-dark w-100">
                                <i class="fas fa-print me-2"></i>Print this Page
                            </button>
                        </div>
                        <?php endif; ?>

                    </div>
                </div><!-- row -->

                <!-- Print footer -->
                <?php if ($is_print): ?>
                <div class="border-top mt-4 pt-3 text-center text-muted" style="font-size:11px;">
                    Thank you for your order! For queries contact us at your store email/phone.<br>
                    Generated on <?= date('d M Y, h:i A') ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

    <?php if (!$is_print) include "footer.php"; ?>
    <?php if (!$is_print): ?>
    </section>
    <?php endif; ?>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- ══════════════════════════════════════════
         ADD ITEM MODAL
    ══════════════════════════════════════════ -->
    <?php if (!$is_print): ?>
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Item to Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Product</label>
                                <select class="form-select" name="product_id" id="itemProductSelect"
                                        onchange="fillProductPrice(this)">
                                    <option value="">— Custom item (fill manually) —</option>
                                    <?php
                                    $products->data_seek(0);
                                    while ($p = $products->fetch_assoc()):
                                    ?>
                                        <option value="<?= $p['id'] ?>"
                                                data-name="<?= htmlspecialchars($p['pro_name'], ENT_QUOTES) ?>"
                                                data-price="<?= $p['selling_price'] ?>">
                                            <?= htmlspecialchars($p['pro_name']) ?>
                                            (₹<?= number_format((float)$p['selling_price'],2) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Product Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="product_name" id="itemProductName" required>
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold">Size</label>
                                <input type="text" class="form-control" name="size" placeholder="M, L, XL...">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold">Qty</label>
                                <input type="number" class="form-control" name="quantity" value="1" min="1"
                                       onchange="calcItemTotal()">
                            </div>
                            <div class="col-4">
                                <label class="form-label fw-semibold">Unit Price (₹)</label>
                                <input type="number" class="form-control" name="unit_price" id="itemUnitPrice"
                                       step="0.01" placeholder="0.00" onchange="calcItemTotal()">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_item" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>Add Item
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function fillProductPrice(sel) {
        const opt   = sel.options[sel.selectedIndex];
        const name  = opt.getAttribute('data-name') || '';
        const price = opt.getAttribute('data-price') || '';
        if (name)  document.getElementById('itemProductName').value = name;
        if (price) document.getElementById('itemUnitPrice').value   = parseFloat(price).toFixed(2);
    }
    function calcItemTotal() {
        const qty   = parseFloat(document.querySelector('[name=quantity]').value) || 0;
        const price = parseFloat(document.getElementById('itemUnitPrice').value)  || 0;
        // optional: show live total somewhere
    }
    </script>

    <?php if ($is_print): ?>
    <script>window.onload = () => window.print();</script>
    <?php endif; ?>
    <?php endif; ?>

</body>
</html>