<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── AUTO-CREATE product_sizes table if not exists ────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `product_sizes` (
        `id`           INT(11)      NOT NULL AUTO_INCREMENT,
        `product_id`   INT(10)      NOT NULL,
        `size_label`   VARCHAR(10)  NOT NULL COMMENT 'e.g. XS, S, M, L, XL, 2XL, 3XL',
        `chest_min`    DECIMAL(4,1) DEFAULT NULL COMMENT 'inches',
        `chest_max`    DECIMAL(4,1) DEFAULT NULL,
        `waist_min`    DECIMAL(4,1) DEFAULT NULL,
        `waist_max`    DECIMAL(4,1) DEFAULT NULL,
        `hip_min`      DECIMAL(4,1) DEFAULT NULL,
        `hip_max`      DECIMAL(4,1) DEFAULT NULL,
        `shoulder`     DECIMAL(4,1) DEFAULT NULL COMMENT 'Shoulder width in inches',
        `price_modifier` DECIMAL(10,2) DEFAULT 0.00 COMMENT 'Extra charge for this size',
        `stock`        INT(11)      DEFAULT 0,
        `is_available` TINYINT(1)   DEFAULT 1,
        `sort_order`   INT(11)      DEFAULT 0,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_product_size` (`product_id`, `size_label`),
        KEY `idx_product` (`product_id`),
        CONSTRAINT `fk_ps_product`
            FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Standard size defaults for auto-fill
$SIZE_DEFAULTS = [
    'XS'  => ['chest_min'=>32,'chest_max'=>34,'waist_min'=>26,'waist_max'=>28,'hip_min'=>34,'hip_max'=>36,'shoulder'=>16.5,'sort_order'=>1],
    'S'   => ['chest_min'=>34,'chest_max'=>36,'waist_min'=>28,'waist_max'=>30,'hip_min'=>36,'hip_max'=>38,'shoulder'=>17.0,'sort_order'=>2],
    'M'   => ['chest_min'=>36,'chest_max'=>38,'waist_min'=>30,'waist_max'=>32,'hip_min'=>38,'hip_max'=>40,'shoulder'=>17.5,'sort_order'=>3],
    'L'   => ['chest_min'=>38,'chest_max'=>40,'waist_min'=>32,'waist_max'=>34,'hip_min'=>40,'hip_max'=>42,'shoulder'=>18.0,'sort_order'=>4],
    'XL'  => ['chest_min'=>40,'chest_max'=>42,'waist_min'=>34,'waist_max'=>36,'hip_min'=>42,'hip_max'=>44,'shoulder'=>18.5,'sort_order'=>5],
    '2XL' => ['chest_min'=>42,'chest_max'=>44,'waist_min'=>36,'waist_max'=>38,'hip_min'=>44,'hip_max'=>46,'shoulder'=>19.0,'sort_order'=>6],
    '3XL' => ['chest_min'=>44,'chest_max'=>46,'waist_min'=>38,'waist_max'=>40,'hip_min'=>46,'hip_max'=>48,'shoulder'=>19.5,'sort_order'=>7],
];

$selected_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// ── AUTO-SEED: bulk add standard sizes for a product ─────────
if (isset($_POST['auto_seed'])) {
    $pid        = (int)$_POST['product_id'];
    $sizes      = $_POST['seed_sizes'] ?? [];
    $added      = 0;

    $ins = $conn->prepare(
        "INSERT IGNORE INTO product_sizes
            (product_id, size_label, chest_min, chest_max, waist_min, waist_max, hip_min, hip_max, shoulder, price_modifier, stock, is_available, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    foreach ($sizes as $sz) {
        if (!isset($SIZE_DEFAULTS[$sz])) continue;
        $d    = $SIZE_DEFAULTS[$sz];
        $pm   = 0.00;
        $stk  = 0;
        $avail= 1;
        $ins->bind_param(
            "issdddddddiii",
            $pid, $sz,
            $d['chest_min'], $d['chest_max'],
            $d['waist_min'], $d['waist_max'],
            $d['hip_min'],   $d['hip_max'],
            $d['shoulder'],  $pm, $stk, $avail, $d['sort_order']
        );
        if ($ins->execute()) $added++;
    }
    $ins->close();
    $_SESSION['success'] = "$added size(s) added successfully!";
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── ADD SINGLE SIZE ──────────────────────────────────────────
if (isset($_POST['add_size'])) {
    $pid        = (int)$_POST['product_id'];
    $size_label = strtoupper(trim($_POST['size_label']));
    $chest_min  = $_POST['chest_min']  !== '' ? (float)$_POST['chest_min']  : null;
    $chest_max  = $_POST['chest_max']  !== '' ? (float)$_POST['chest_max']  : null;
    $waist_min  = $_POST['waist_min']  !== '' ? (float)$_POST['waist_min']  : null;
    $waist_max  = $_POST['waist_max']  !== '' ? (float)$_POST['waist_max']  : null;
    $hip_min    = $_POST['hip_min']    !== '' ? (float)$_POST['hip_min']    : null;
    $hip_max    = $_POST['hip_max']    !== '' ? (float)$_POST['hip_max']    : null;
    $shoulder   = $_POST['shoulder']   !== '' ? (float)$_POST['shoulder']   : null;
    $price_mod  = (float)($_POST['price_modifier'] ?? 0);
    $stock      = (int)($_POST['stock'] ?? 0);
    $is_avail   = isset($_POST['is_available']) ? 1 : 0;

    // Sort order = max + 1
    $max = $conn->query("SELECT MAX(sort_order) FROM product_sizes WHERE product_id=$pid")->fetch_row()[0];
    $sort_order = (int)$max + 1;

    $s = $conn->prepare(
        "INSERT INTO product_sizes
            (product_id, size_label, chest_min, chest_max, waist_min, waist_max, hip_min, hip_max, shoulder, price_modifier, stock, is_available, sort_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $s->bind_param("issdddddddiii", $pid, $size_label, $chest_min, $chest_max, $waist_min, $waist_max, $hip_min, $hip_max, $shoulder, $price_mod, $stock, $is_avail, $sort_order);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Size \"$size_label\" added!" : "Error: " . $conn->error . " (size may already exist)";
    $s->close();
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── EDIT SIZE ────────────────────────────────────────────────
if (isset($_POST['edit_size'])) {
    $id         = (int)$_POST['id'];
    $pid        = (int)$_POST['product_id'];
    $size_label = strtoupper(trim($_POST['size_label']));
    $chest_min  = $_POST['chest_min']  !== '' ? (float)$_POST['chest_min']  : null;
    $chest_max  = $_POST['chest_max']  !== '' ? (float)$_POST['chest_max']  : null;
    $waist_min  = $_POST['waist_min']  !== '' ? (float)$_POST['waist_min']  : null;
    $waist_max  = $_POST['waist_max']  !== '' ? (float)$_POST['waist_max']  : null;
    $hip_min    = $_POST['hip_min']    !== '' ? (float)$_POST['hip_min']    : null;
    $hip_max    = $_POST['hip_max']    !== '' ? (float)$_POST['hip_max']    : null;
    $shoulder   = $_POST['shoulder']   !== '' ? (float)$_POST['shoulder']   : null;
    $price_mod  = (float)($_POST['price_modifier'] ?? 0);
    $stock      = (int)($_POST['stock'] ?? 0);
    $is_avail   = isset($_POST['is_available']) ? 1 : 0;

    $s = $conn->prepare(
        "UPDATE product_sizes SET
            size_label=?, chest_min=?, chest_max=?, waist_min=?, waist_max=?,
            hip_min=?, hip_max=?, shoulder=?, price_modifier=?, stock=?, is_available=?
         WHERE id=? AND product_id=?"
    );
    $s->bind_param("sddddddddiiii", $size_label, $chest_min, $chest_max, $waist_min, $waist_max, $hip_min, $hip_max, $shoulder, $price_mod, $stock, $is_avail, $id, $pid);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Size updated!" : "Error: " . $conn->error;
    $s->close();
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── STOCK QUICK UPDATE (inline) ──────────────────────────────
if (isset($_POST['update_stock'])) {
    $id    = (int)$_POST['id'];
    $pid   = (int)$_POST['product_id'];
    $stock = (int)$_POST['stock'];
    $s = $conn->prepare("UPDATE product_sizes SET stock=? WHERE id=? AND product_id=?");
    $s->bind_param("iii", $stock, $id, $pid);
    $s->execute(); $s->close();
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── TOGGLE available ─────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $pid = (int)$_GET['pid'];
    $new = (int)$_GET['val'] == 1 ? 0 : 1;
    $s   = $conn->prepare("UPDATE product_sizes SET is_available=? WHERE id=?");
    $s->bind_param("ii", $new, $id); $s->execute(); $s->close();
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── DELETE SIZE ──────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $pid = (int)$_GET['pid'];
    $del = $conn->prepare("DELETE FROM product_sizes WHERE id=?");
    $del->bind_param("i", $id);
    $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
        ? "Size removed." : "Delete failed.";
    $del->close();
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── DELETE ALL SIZES FOR PRODUCT ─────────────────────────────
if (isset($_GET['delete_all'])) {
    $pid = (int)$_GET['pid'];
    $conn->query("DELETE FROM product_sizes WHERE product_id=$pid");
    $_SESSION['success'] = "All sizes cleared for this product.";
    header("Location: manage-sizes.php?product_id=$pid");
    exit();
}

// ── FETCH ALL PRODUCTS ────────────────────────────────────────
$products_res = $conn->query(
    "SELECT p.id, p.pro_name, p.product_type, p.pro_img, p.status,
            COUNT(ps.id) AS size_count,
            SUM(ps.stock) AS total_stock
     FROM products p
     LEFT JOIN product_sizes ps ON p.id = ps.product_id
     GROUP BY p.id
     ORDER BY p.pro_name ASC"
);
$all_products = [];
while ($p = $products_res->fetch_assoc()) $all_products[] = $p;

// ── FETCH SIZES FOR SELECTED PRODUCT ────────────────────────
$selected_product = null;
$sizes            = [];

if ($selected_product_id) {
    $sp = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $sp->bind_param("i", $selected_product_id);
    $sp->execute();
    $selected_product = $sp->get_result()->fetch_assoc();
    $sp->close();

    if ($selected_product) {
        $sr = $conn->prepare(
            "SELECT * FROM product_sizes WHERE product_id=? ORDER BY sort_order ASC, id ASC"
        );
        $sr->bind_param("i", $selected_product_id);
        $sr->execute();
        $res = $sr->get_result();
        while ($s = $res->fetch_assoc()) $sizes[] = $s;
        $sr->close();
    }
}

// Which standard sizes already exist for this product?
$existing_labels = array_column($sizes, 'size_label');
$total_size_stock = array_sum(array_column($sizes, 'stock'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Manage Sizes | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        /* ── Product selector cards ──────────── */
        .product-card {
            background: #fff;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: border-color 0.18s, box-shadow 0.18s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .product-card:hover  { border-color: #6c63ff; box-shadow: 0 3px 12px rgba(108,99,255,0.12); }
        .product-card.active { border-color: #2c3e50; box-shadow: 0 3px 12px rgba(44,62,80,0.15); background: #f8fafc; }
        .product-thumb {
            width: 48px; height: 48px; object-fit: cover;
            border-radius: 6px; border: 1px solid #eee; flex-shrink: 0;
        }
        .product-thumb-placeholder {
            width: 48px; height: 48px; border-radius: 6px;
            background: #f0f0f0; display: flex; align-items: center;
            justify-content: center; color: #ccc; flex-shrink: 0;
        }
        .type-badge { font-size: 10px; padding: 2px 7px; border-radius: 20px; }

        /* ── Size table ──────────────────────── */
        .size-row { transition: background 0.12s; }
        .size-row:hover { background: #f8f9fa; }
        .size-label-cell {
            font-size: 16px; font-weight: 700;
            color: #2c3e50; text-align: center;
        }
        .measurement-cell { font-size: 12px; color: #495057; }
        .stock-input {
            width: 75px; text-align: center;
            border: 1px solid #dee2e6; border-radius: 6px;
            padding: 4px 6px; font-size: 13px;
        }
        .stock-input:focus { outline: none; border-color: #6c63ff; }
        .stock-low  { color: #dc3545; font-weight: 600; }
        .stock-ok   { color: #198754; }
        .action-btn { width: 30px; height: 30px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; padding: 0; font-size: 12px; }

        /* ── Auto-seed panel ─────────────────── */
        .seed-panel {
            background: linear-gradient(135deg, #667eea11, #764ba211);
            border: 1.5px dashed #9b89fc;
            border-radius: 12px;
            padding: 20px;
        }
        .seed-size-btn {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            cursor: pointer;
            font-size: 13px;
            background: #fff;
            transition: all 0.15s;
            user-select: none;
        }
        .seed-size-btn:hover { border-color: #2c3e50; background: #f8f9fa; }
        .seed-size-btn.selected { background: #2c3e50; color: #fff; border-color: #2c3e50; }
        .seed-size-btn.already-exists {
            background: #d4edda; border-color: #28a745; color: #155724;
            opacity: 0.6; cursor: not-allowed;
        }

        /* ── Stat mini cards ─────────────────── */
        .mini-stat {
            background: #f8f9fa; border-radius: 8px;
            padding: 10px 14px; text-align: center;
        }
        .mini-stat-val { font-size: 20px; font-weight: 700; line-height: 1; }
        .mini-stat-lbl { font-size: 11px; color: #6c757d; margin-top: 2px; }

        /* ── Size chart help ─────────────────── */
        .chart-row td { font-size: 12px; padding: 5px 8px; }
        .chart-row:nth-child(even) { background: #f8f9fa; }

        /* ── Empty state ─────────────────────── */
        .empty-state { padding: 40px 20px; text-align: center; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; opacity: 0.3; }

        /* ── Measurement input group ─────────── */
        .meas-group { display: flex; gap: 4px; align-items: center; }
        .meas-group input { width: 60px; font-size: 12px; padding: 4px 6px; }
        .meas-sep { font-size: 11px; color: #aaa; }
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

                <!-- Alerts -->
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

                <div class="row g-3">

                    <!-- ══════════════════════════════════════
                         LEFT: Product selector
                    ══════════════════════════════════════ -->
                    <div class="col-lg-3">
                        <div class="white_card mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <h5 class="fw-bold mb-1">Select Product</h5>
                                <p class="text-muted small mb-0">
                                    <?= count($all_products) ?> products total
                                </p>
                                <input type="text" class="form-control form-control-sm mt-2"
                                       id="productSearch" placeholder="Search products..."
                                       oninput="filterProductList(this.value)">
                            </div>
                            <div class="white_card_body p-2" id="productList"
                                 style="max-height: calc(100vh - 260px); overflow-y: auto;">
                                <?php foreach ($all_products as $prod):
                                    $isActive  = $prod['id'] == $selected_product_id;
                                    $typeColor = match($prod['product_type']) {
                                        'ready_made'    => 'bg-info text-dark',
                                        'made_to_order' => 'bg-warning text-dark',
                                        default         => 'bg-primary text-white',
                                    };
                                    $typeLabel = match($prod['product_type']) {
                                        'ready_made'    => 'Ready Made',
                                        'made_to_order' => 'MTO',
                                        default         => 'Both',
                                    };
                                ?>
                                <a href="manage-sizes.php?product_id=<?= $prod['id'] ?>"
                                   class="product-card mb-2 <?= $isActive ? 'active' : '' ?>"
                                   data-name="<?= htmlspecialchars(strtolower($prod['pro_name'])) ?>">
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if ($prod['pro_img'] && file_exists("assets/img/uploads/" . explode(',', $prod['pro_img'])[0])): ?>
                                            <img src="assets/img/uploads/<?= htmlspecialchars(explode(',', $prod['pro_img'])[0]) ?>"
                                                 class="product-thumb" alt="">
                                        <?php else: ?>
                                            <div class="product-thumb-placeholder">
                                                <i class="fas fa-tshirt"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div style="min-width:0;">
                                            <div class="fw-semibold text-truncate" style="font-size:13px;max-width:160px;">
                                                <?= htmlspecialchars($prod['pro_name']) ?>
                                            </div>
                                            <div class="d-flex align-items-center gap-1 mt-1 flex-wrap">
                                                <span class="badge type-badge <?= $typeColor ?>"><?= $typeLabel ?></span>
                                                <?php if ($prod['size_count'] > 0): ?>
                                                    <span class="badge bg-light text-dark border" style="font-size:10px;">
                                                        <?= $prod['size_count'] ?> sizes
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!$prod['status']): ?>
                                                    <span class="badge bg-secondary" style="font-size:10px;">Inactive</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ══════════════════════════════════════
                         RIGHT: Size manager
                    ══════════════════════════════════════ -->
                    <div class="col-lg-9">
                        <?php if (!$selected_product): ?>
                            <!-- No product selected -->
                            <div class="white_card">
                                <div class="empty-state">
                                    <i class="fas fa-ruler-combined d-block"></i>
                                    <h5 class="text-muted">Select a product</h5>
                                    <p class="text-muted small">Choose a product from the left panel to manage its size variants.</p>
                                </div>
                            </div>

                        <?php else: ?>

                        <!-- Product header -->
                        <div class="white_card mb-3">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <?php if ($selected_product['pro_img'] && file_exists("assets/img/uploads/" . explode(',', $selected_product['pro_img'])[0])): ?>
                                            <img src="assets/img/uploads/<?= htmlspecialchars(explode(',', $selected_product['pro_img'])[0]) ?>"
                                                 style="width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #eee;">
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="fw-bold mb-1"><?= htmlspecialchars($selected_product['pro_name']) ?></h3>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <?php
                                                $typeColor = match($selected_product['product_type']) {
                                                    'ready_made'    => 'bg-info text-dark',
                                                    'made_to_order' => 'bg-warning text-dark',
                                                    default         => 'bg-primary text-white',
                                                };
                                                $typeLabel = match($selected_product['product_type']) {
                                                    'ready_made'    => 'Ready Made',
                                                    'made_to_order' => 'Made to Order',
                                                    default         => 'Both',
                                                };
                                                ?>
                                                <span class="badge <?= $typeColor ?>"><?= $typeLabel ?></span>
                                                <?php if ($selected_product['product_type'] === 'made_to_order'): ?>
                                                    <span class="badge bg-light text-muted border" style="font-size:11px;">
                                                        <i class="fas fa-info-circle me-1"></i>
                                                        Made-to-order products use measurements, not fixed sizes
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <?php if (!empty($sizes)): ?>
                                            <button class="btn btn-outline-danger btn-sm"
                                                    onclick="if(confirm('Clear ALL sizes for this product?')) location.href='?delete_all&pid=<?= $selected_product_id ?>'">
                                                <i class="fas fa-trash me-1"></i>Clear All
                                            </button>
                                        <?php endif; ?>
                                        <button class="btn btn-primary btn-sm"
                                                data-bs-toggle="modal" data-bs-target="#addSizeModal">
                                            <i class="fas fa-plus me-1"></i>Add Size
                                        </button>
                                    </div>
                                </div>

                                <!-- Mini stats -->
                                <?php if (!empty($sizes)): ?>
                                <div class="row g-2 mt-2">
                                    <div class="col-3 col-sm-2">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val"><?= count($sizes) ?></div>
                                            <div class="mini-stat-lbl">Sizes</div>
                                        </div>
                                    </div>
                                    <div class="col-3 col-sm-2">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val"><?= count(array_filter($sizes, fn($s)=>$s['is_available'])) ?></div>
                                            <div class="mini-stat-lbl">Active</div>
                                        </div>
                                    </div>
                                    <div class="col-3 col-sm-2">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val <?= $total_size_stock < 5 ? 'text-danger' : 'text-success' ?>">
                                                <?= $total_size_stock ?>
                                            </div>
                                            <div class="mini-stat-lbl">Total Stock</div>
                                        </div>
                                    </div>
                                    <div class="col-3 col-sm-2">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val text-warning">
                                                <?= count(array_filter($sizes, fn($s)=>$s['stock']==0 && $s['is_available'])) ?>
                                            </div>
                                            <div class="mini-stat-lbl">Out of Stock</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ── Auto-seed panel ─────────────────── -->
                        <?php $missing_sizes = array_diff(array_keys($SIZE_DEFAULTS), $existing_labels); ?>
                        <?php if (!empty($missing_sizes)): ?>
                        <div class="seed-panel mb-3">
                            <form action="" method="POST">
                                <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <i class="fas fa-magic me-2 text-primary"></i>Quick Add Standard Sizes
                                        </h6>
                                        <small class="text-muted">
                                            Measurements are pre-filled with standard Indian garment sizing (in inches).
                                            You can edit individual measurements after adding.
                                        </small>
                                    </div>
                                    <button type="submit" name="auto_seed" class="btn btn-primary btn-sm" id="seedBtn" disabled>
                                        <i class="fas fa-plus me-1"></i>Add Selected Sizes
                                    </button>
                                </div>
                                <div class="d-flex flex-wrap gap-2" id="seedSizeButtons">
                                    <?php foreach ($SIZE_DEFAULTS as $sz => $def): ?>
                                        <?php $exists = in_array($sz, $existing_labels); ?>
                                        <label class="seed-size-btn <?= $exists ? 'already-exists' : '' ?>"
                                               data-size="<?= $sz ?>">
                                            <input type="checkbox" name="seed_sizes[]" value="<?= $sz ?>"
                                                   style="display:none;" <?= $exists ? 'disabled' : '' ?>
                                                   onchange="updateSeedBtn()">
                                            <?= $sz ?>
                                            <?php if ($exists): ?>
                                                <i class="fas fa-check ms-1" style="font-size:10px;"></i>
                                            <?php endif; ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>

                        <!-- ── Sizes table ─────────────────────── -->
                        <div class="white_card mb_30">
                            <div class="card-header bg-white border-0 py-2 d-flex justify-content-between align-items-center">
                                <h5 class="fw-bold mb-0">Size Variants</h5>
                                <button class="btn btn-sm btn-outline-secondary" type="button"
                                        data-bs-toggle="collapse" data-bs-target="#sizeChartHelp">
                                    <i class="fas fa-ruler me-1"></i>View Size Chart Guide
                                </button>
                            </div>

                            <!-- Size chart reference -->
                            <div class="collapse" id="sizeChartHelp">
                                <div class="p-3 bg-light border-bottom">
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Standard Indian garment sizes (chest/waist/hip in <strong>inches</strong>, shoulder in inches):
                                    </p>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0" style="font-size:12px;">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>Size</th>
                                                    <th>Chest (in)</th>
                                                    <th>Waist (in)</th>
                                                    <th>Hip (in)</th>
                                                    <th>Shoulder (in)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($SIZE_DEFAULTS as $sz => $d): ?>
                                                <tr class="chart-row">
                                                    <td class="fw-bold"><?= $sz ?></td>
                                                    <td><?= $d['chest_min'] ?>–<?= $d['chest_max'] ?></td>
                                                    <td><?= $d['waist_min'] ?>–<?= $d['waist_max'] ?></td>
                                                    <td><?= $d['hip_min'] ?>–<?= $d['hip_max'] ?></td>
                                                    <td><?= $d['shoulder'] ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body p-0">
                                <?php if (empty($sizes)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-ruler-combined d-block"></i>
                                        <h5 class="text-muted">No sizes added yet</h5>
                                        <p class="text-muted small">Use Quick Add above to add standard sizes, or click "Add Size" for a custom size.</p>
                                    </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="table-dark">
                                            <tr>
                                                <th class="text-center" style="width:70px;">Size</th>
                                                <th>Chest (in)</th>
                                                <th>Waist (in)</th>
                                                <th>Hip (in)</th>
                                                <th>Shoulder (in)</th>
                                                <th>Price +</th>
                                                <th>Stock</th>
                                                <th class="text-center">Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($sizes as $sz):
                                            $stockClass = $sz['stock'] == 0 ? 'stock-low' : ($sz['stock'] < 5 ? 'text-warning fw-semibold' : 'stock-ok');
                                            $pm = (float)$sz['price_modifier'];
                                        ?>
                                            <tr class="size-row <?= !$sz['is_available'] ? 'table-secondary' : '' ?>">
                                                <td class="size-label-cell"><?= htmlspecialchars($sz['size_label']) ?></td>
                                                <td class="measurement-cell">
                                                    <?= $sz['chest_min'] ? $sz['chest_min'].'–'.$sz['chest_max'] : '—' ?>
                                                </td>
                                                <td class="measurement-cell">
                                                    <?= $sz['waist_min'] ? $sz['waist_min'].'–'.$sz['waist_max'] : '—' ?>
                                                </td>
                                                <td class="measurement-cell">
                                                    <?= $sz['hip_min'] ? $sz['hip_min'].'–'.$sz['hip_max'] : '—' ?>
                                                </td>
                                                <td class="measurement-cell">
                                                    <?= $sz['shoulder'] ?? '—' ?>
                                                </td>
                                                <td>
                                                    <?php if ($pm > 0): ?>
                                                        <span class="text-success fw-semibold">+₹<?= number_format($pm,2) ?></span>
                                                    <?php elseif ($pm < 0): ?>
                                                        <span class="text-danger fw-semibold">-₹<?= number_format(abs($pm),2) ?></span>
                                                    <?php else: ?>
                                                        <span class="text-muted">—</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Inline stock edit -->
                                                <td>
                                                    <form action="" method="POST" class="d-flex align-items-center gap-1">
                                                        <input type="hidden" name="id" value="<?= $sz['id'] ?>">
                                                        <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                                                        <input type="number" name="stock" value="<?= (int)$sz['stock'] ?>"
                                                               min="0" class="stock-input <?= $stockClass ?>"
                                                               title="Edit stock directly">
                                                        <button type="submit" name="update_stock"
                                                                class="btn btn-sm btn-outline-secondary action-btn"
                                                                title="Save stock">
                                                            <i class="fas fa-check" style="font-size:10px;"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                                <td class="text-center">
                                                    <a href="?toggle=<?= $sz['id'] ?>&pid=<?= $selected_product_id ?>&val=<?= $sz['is_available'] ?>"
                                                       class="badge text-decoration-none <?= $sz['is_available'] ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $sz['is_available'] ? 'Active' : 'Inactive' ?>
                                                    </a>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <button class="btn btn-sm btn-outline-primary action-btn"
                                                                title="Edit size"
                                                                onclick="openEditModal(
                                                                    <?= $sz['id'] ?>,
                                                                    '<?= addslashes($sz['size_label']) ?>',
                                                                    '<?= $sz['chest_min'] ?>','<?= $sz['chest_max'] ?>',
                                                                    '<?= $sz['waist_min'] ?>','<?= $sz['waist_max'] ?>',
                                                                    '<?= $sz['hip_min'] ?>','<?= $sz['hip_max'] ?>',
                                                                    '<?= $sz['shoulder'] ?>',
                                                                    <?= $sz['price_modifier'] ?>,
                                                                    <?= (int)$sz['stock'] ?>,
                                                                    <?= (int)$sz['is_available'] ?>
                                                                )">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger action-btn"
                                                                title="Delete size"
                                                                data-id="<?= $sz['id'] ?>"
                                                                data-label="<?= htmlspecialchars($sz['size_label'], ENT_QUOTES) ?>"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                        <tfoot class="table-light">
                                            <tr>
                                                <td colspan="6" class="text-end fw-semibold text-muted" style="font-size:12px;">Total Stock</td>
                                                <td class="fw-bold <?= $total_size_stock < 10 ? 'text-danger' : 'text-success' ?>">
                                                    <?= $total_size_stock ?>
                                                </td>
                                                <td colspan="2"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php endif; ?>
                    </div><!-- col -->
                </div><!-- row -->
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>


    <!-- ══════════════════════════════════════════════════════
         ADD SIZE MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="addSizeModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Add Size Variant
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Size Label <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="size_label"
                                       placeholder="e.g. XS, S, M, L, XL, 2XL, 38, 40" required
                                       list="sizeSuggestions" id="addSizeLabel"
                                       oninput="autoFillMeasurements(this.value)">
                                <datalist id="sizeSuggestions">
                                    <?php foreach (array_keys($SIZE_DEFAULTS) as $sz): ?>
                                        <option value="<?= $sz ?>">
                                    <?php endforeach; ?>
                                </datalist>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier"
                                       value="0" step="0.01" placeholder="0 = no extra charge">
                                <small class="text-muted">Extra charge for this size</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock" value="0" min="0">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Measurements <small class="text-muted fw-normal">(all in inches — optional but recommended)</small></label>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small">Chest (min–max)</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="chest_min" id="add_chest_min" step="0.5" placeholder="36">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="chest_max" id="add_chest_max" step="0.5" placeholder="38">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Waist (min–max)</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="waist_min" id="add_waist_min" step="0.5" placeholder="30">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="waist_max" id="add_waist_max" step="0.5" placeholder="32">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Hip (min–max)</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="hip_min" id="add_hip_min" step="0.5" placeholder="38">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="hip_max" id="add_hip_max" step="0.5" placeholder="40">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Shoulder (in)</label>
                                        <input type="number" class="form-control" name="shoulder" id="add_shoulder" step="0.5" placeholder="17.5">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="addIsAvail" checked>
                                    <label class="form-check-label fw-semibold" for="addIsAvail">Active / Available</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_size" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Size
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         EDIT SIZE MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editSizeModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2 text-warning"></i>Edit Size — <span id="editSizeTitleLabel"></span>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Size Label <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="size_label" id="edit_size_label" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier" id="edit_price_modifier" step="0.01">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Stock Quantity</label>
                                <input type="number" class="form-control" name="stock" id="edit_stock" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Measurements <small class="text-muted fw-normal">(inches)</small></label>
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label class="form-label small">Chest</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="chest_min" id="edit_chest_min" step="0.5">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="chest_max" id="edit_chest_max" step="0.5">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Waist</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="waist_min" id="edit_waist_min" step="0.5">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="waist_max" id="edit_waist_max" step="0.5">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Hip</label>
                                        <div class="meas-group">
                                            <input type="number" class="form-control" name="hip_min" id="edit_hip_min" step="0.5">
                                            <span class="meas-sep">–</span>
                                            <input type="number" class="form-control" name="hip_max" id="edit_hip_max" step="0.5">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">Shoulder</label>
                                        <input type="number" class="form-control" name="shoulder" id="edit_shoulder" step="0.5">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="edit_is_avail">
                                    <label class="form-check-label fw-semibold" for="edit_is_avail">Active / Available</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_size" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Update Size
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         DELETE MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Delete Size?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-trash-alt fa-xl text-danger"></i>
                    </div>
                    <h5>Remove size <strong id="deleteSizeLabel"></strong>?</h5>
                    <p class="text-muted small">This cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Standard sizes data for auto-fill
    const SIZE_DEFAULTS = <?= json_encode($SIZE_DEFAULTS) ?>;

    // ── Auto-fill measurements when typing a known size ──────
    function autoFillMeasurements(val) {
        const sz = val.toUpperCase().trim();
        if (!SIZE_DEFAULTS[sz]) return;
        const d = SIZE_DEFAULTS[sz];
        document.getElementById('add_chest_min').value = d.chest_min;
        document.getElementById('add_chest_max').value = d.chest_max;
        document.getElementById('add_waist_min').value = d.waist_min;
        document.getElementById('add_waist_max').value = d.waist_max;
        document.getElementById('add_hip_min').value   = d.hip_min;
        document.getElementById('add_hip_max').value   = d.hip_max;
        document.getElementById('add_shoulder').value  = d.shoulder;
    }

    // ── Open edit modal ──────────────────────────────────────
    function openEditModal(id, label, chestMin, chestMax, waistMin, waistMax, hipMin, hipMax, shoulder, pricemod, stock, isAvail) {
        document.getElementById('edit_id').value             = id;
        document.getElementById('edit_size_label').value     = label;
        document.getElementById('edit_chest_min').value      = chestMin || '';
        document.getElementById('edit_chest_max').value      = chestMax || '';
        document.getElementById('edit_waist_min').value      = waistMin || '';
        document.getElementById('edit_waist_max').value      = waistMax || '';
        document.getElementById('edit_hip_min').value        = hipMin || '';
        document.getElementById('edit_hip_max').value        = hipMax || '';
        document.getElementById('edit_shoulder').value       = shoulder || '';
        document.getElementById('edit_price_modifier').value = pricemod;
        document.getElementById('edit_stock').value          = stock;
        document.getElementById('edit_is_avail').checked     = isAvail == 1;
        document.getElementById('editSizeTitleLabel').textContent = label;
        new bootstrap.Modal(document.getElementById('editSizeModal')).show();
    }

    // ── Quick-add seed buttons ───────────────────────────────
    document.querySelectorAll('.seed-size-btn:not(.already-exists)').forEach(btn => {
        btn.addEventListener('click', function() {
            const chk = this.querySelector('input[type=checkbox]');
            chk.checked = !chk.checked;
            this.classList.toggle('selected', chk.checked);
            updateSeedBtn();
        });
    });

    function updateSeedBtn() {
        const anyChecked = document.querySelectorAll('#seedSizeButtons input:checked').length > 0;
        const btn = document.getElementById('seedBtn');
        if (btn) btn.disabled = !anyChecked;
    }

    // ── Delete modal ─────────────────────────────────────────
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('deleteSizeLabel').textContent = btn.getAttribute('data-label');
        document.getElementById('confirmDeleteBtn').href =
            'manage-sizes.php?delete=' + btn.getAttribute('data-id') + '&pid=<?= $selected_product_id ?>';
    });

    // ── Product list search ──────────────────────────────────
    function filterProductList(q) {
        const query = q.toLowerCase().trim();
        document.querySelectorAll('#productList .product-card').forEach(card => {
            const name = card.getAttribute('data-name') || '';
            card.style.display = name.includes(query) ? '' : 'none';
        });
    }
    </script>
</body>
</html>