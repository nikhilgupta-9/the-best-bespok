<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── CREATE TABLE IF NOT EXISTS ────────────────────────────
$conn->query("
    CREATE TABLE IF NOT EXISTS `product_fabric_map` (
        `id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL,
        `fabric_id` INT(11) NOT NULL,
        `is_default` TINYINT(1) DEFAULT 0 COMMENT 'Default fabric for this product',
        `sort_order` INT(11) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_product_fabric` (`product_id`, `fabric_id`),
        KEY `idx_product` (`product_id`),
        KEY `idx_fabric` (`fabric_id`),
        CONSTRAINT `fk_pfm_product` FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_pfm_fabric` FOREIGN KEY (`fabric_id`) REFERENCES `fabric_options`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

$selected_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;

// ── ASSIGN FABRIC TO PRODUCT ────────────────────────────────
if (isset($_POST['assign_fabric'])) {
    $product_id = (int)$_POST['product_id'];
    $fabric_id = (int)$_POST['fabric_id'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    
    // Get next sort order
    $max = $conn->query("SELECT MAX(sort_order) FROM product_fabric_map WHERE product_id=$product_id")->fetch_row()[0];
    $sort_order = (int)$max + 1;
    
    // If setting as default, remove default from other fabrics for this product
    if ($is_default) {
        $conn->query("UPDATE product_fabric_map SET is_default=0 WHERE product_id=$product_id");
    }
    
    $stmt = $conn->prepare(
        "INSERT INTO product_fabric_map (product_id, fabric_id, is_default, sort_order) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt->bind_param("iiii", $product_id, $fabric_id, $is_default, $sort_order);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Fabric assigned successfully!";
    } else {
        $_SESSION['error'] = "Error: " . $conn->error . " (fabric may already be assigned)";
    }
    $stmt->close();
    header("Location: manage-product-fabrics.php?product_id=$product_id");
    exit();
}

// ── UPDATE FABRIC ASSIGNMENT ────────────────────────────────
if (isset($_POST['update_assignment'])) {
    $id = (int)$_POST['id'];
    $product_id = (int)$_POST['product_id'];
    $is_default = isset($_POST['is_default']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];
    
    // If setting as default, remove default from other fabrics for this product
    if ($is_default) {
        $conn->query("UPDATE product_fabric_map SET is_default=0 WHERE product_id=$product_id AND id != $id");
    }
    
    $stmt = $conn->prepare(
        "UPDATE product_fabric_map SET is_default=?, sort_order=? WHERE id=? AND product_id=?"
    );
    $stmt->bind_param("iiii", $is_default, $sort_order, $id, $product_id);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute()
        ? "Assignment updated!" : "Error: " . $conn->error;
    $stmt->close();
    header("Location: manage-product-fabrics.php?product_id=$product_id");
    exit();
}

// ── REMOVE FABRIC ASSIGNMENT ────────────────────────────────
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $pid = (int)$_GET['pid'];
    $del = $conn->prepare("DELETE FROM product_fabric_map WHERE id=?");
    $del->bind_param("i", $id);
    $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
        ? "Fabric removed from product." : "Delete failed.";
    $del->close();
    header("Location: manage-product-fabrics.php?product_id=$pid");
    exit();
}

// ── SET DEFAULT FABRIC ──────────────────────────────────────
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    $pid = (int)$_GET['pid'];
    
    // Remove default from all fabrics for this product
    $conn->query("UPDATE product_fabric_map SET is_default=0 WHERE product_id=$pid");
    // Set this one as default
    $conn->query("UPDATE product_fabric_map SET is_default=1 WHERE id=$id");
    
    $_SESSION['success'] = "Default fabric updated!";
    header("Location: manage-product-fabrics.php?product_id=$pid");
    exit();
}

// ── UPDATE SORT ORDER (bulk) ────────────────────────────────
if (isset($_POST['update_sort_order'])) {
    $product_id = (int)$_POST['product_id'];
    $orders = $_POST['sort_order'] ?? [];
    
    foreach ($orders as $id => $order) {
        $order = (int)$order;
        $conn->query("UPDATE product_fabric_map SET sort_order=$order WHERE id=$id AND product_id=$product_id");
    }
    $_SESSION['success'] = "Sort order updated!";
    header("Location: manage-product-fabrics.php?product_id=$product_id");
    exit();
}

// ── FETCH ALL PRODUCTS ──────────────────────────────────────
$products_res = $conn->query(
    "SELECT p.id, p.pro_name, p.product_type, p.pro_img, p.status,
            COUNT(pfm.id) AS fabric_count
     FROM products p
     LEFT JOIN product_fabric_map pfm ON p.id = pfm.product_id
     GROUP BY p.id
     ORDER BY p.pro_name ASC"
);
$all_products = [];
while ($p = $products_res->fetch_assoc()) $all_products[] = $p;

// ── FETCH ALL AVAILABLE FABRICS ─────────────────────────────
$fabrics_res = $conn->query(
    "SELECT id, name, material, swatch_color, image, price_modifier 
     FROM fabric_options 
     WHERE is_available = 1 
     ORDER BY display_order ASC, name ASC"
);
$all_fabrics = [];
while ($f = $fabrics_res->fetch_assoc()) $all_fabrics[] = $f;

// ── FETCH SELECTED PRODUCT AND ITS FABRICS ──────────────────
$selected_product = null;
$assigned_fabrics = [];

if ($selected_product_id) {
    $sp = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $sp->bind_param("i", $selected_product_id);
    $sp->execute();
    $selected_product = $sp->get_result()->fetch_assoc();
    $sp->close();

    if ($selected_product) {
        $sr = $conn->prepare(
            "SELECT pfm.*, f.name, f.material, f.swatch_color, f.image, f.price_modifier 
             FROM product_fabric_map pfm
             JOIN fabric_options f ON pfm.fabric_id = f.id
             WHERE pfm.product_id = ? 
             ORDER BY pfm.sort_order ASC, pfm.id ASC"
        );
        $sr->bind_param("i", $selected_product_id);
        $sr->execute();
        $res = $sr->get_result();
        while ($a = $res->fetch_assoc()) $assigned_fabrics[] = $a;
        $sr->close();
    }
}

// Get IDs of already assigned fabrics
$assigned_fabric_ids = array_column($assigned_fabrics, 'fabric_id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Product Fabrics | Admin Panel</title>
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
        .product-card:hover { border-color: #6c63ff; box-shadow: 0 3px 12px rgba(108,99,255,0.12); }
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

        /* ── Fabric table ──────────────────────── */
        .fabric-row { transition: background 0.12s; }
        .fabric-row:hover { background: #f8f9fa; }
        .fabric-swatch {
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid #dee2e6; object-fit: cover;
        }
        .fabric-swatch-color {
            width: 36px; height: 36px; border-radius: 8px;
            border: 2px solid #dee2e6;
        }
        .default-badge {
            background: #ffd700;
            color: #856404;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 20px;
        }
        .action-btn {
            width: 30px; height: 30px; border-radius: 50%;
            display: inline-flex; align-items: center;
            justify-content: center; padding: 0; font-size: 12px;
        }
        .price-modifier {
            font-size: 12px;
            font-weight: 600;
        }
        .price-plus { color: #27ae60; }
        .price-minus { color: #e74c3c; }

        /* ── Empty state ─────────────────────── */
        .empty-state { padding: 40px 20px; text-align: center; color: #6c757d; }
        .empty-state i { font-size: 3rem; margin-bottom: 12px; opacity: 0.3; }

        /* ── Fabric card for assignment ──────── */
        .fabric-select-card {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 12px;
            cursor: pointer;
            transition: all 0.15s;
            background: #fff;
        }
        .fabric-select-card:hover { border-color: #6c63ff; background: #f8f9fa; }
        .fabric-select-card.selected { border-color: #2c3e50; background: #f0f7ff; }
        .fabric-select-card.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background: #f5f5f5;
        }
        .fabric-select-card.disabled:hover { border-color: #e9ecef; }
        
        /* ── Mini stats ─────────────────────── */
        .mini-stat {
            background: #f8f9fa; border-radius: 8px;
            padding: 10px 14px; text-align: center;
        }
        .mini-stat-val { font-size: 20px; font-weight: 700; line-height: 1; }
        .mini-stat-lbl { font-size: 11px; color: #6c757d; margin-top: 2px; }
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
                                    $isActive = $prod['id'] == $selected_product_id;
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
                                <a href="manage-product-fabrics.php?product_id=<?= $prod['id'] ?>"
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
                                                <?php if ($prod['fabric_count'] > 0): ?>
                                                    <span class="badge bg-light text-dark border" style="font-size:10px;">
                                                        <?= $prod['fabric_count'] ?> fabrics
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
                         RIGHT: Fabric manager
                    ══════════════════════════════════════ -->
                    <div class="col-lg-9">
                        <?php if (!$selected_product): ?>
                            <!-- No product selected -->
                            <div class="white_card">
                                <div class="empty-state">
                                    <i class="fas fa-tshirt d-block"></i>
                                    <h5 class="text-muted">Select a product</h5>
                                    <p class="text-muted small">Choose a product from the left panel to manage its fabric options.</p>
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
                                            </div>
                                        </div>
                                    </div>
                                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignFabricModal">
                                        <i class="fas fa-plus me-1"></i>Assign Fabric
                                    </button>
                                </div>

                                <!-- Mini stats -->
                                <?php if (!empty($assigned_fabrics)): ?>
                                <div class="row g-2 mt-2">
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val"><?= count($assigned_fabrics) ?></div>
                                            <div class="mini-stat-lbl">Assigned Fabrics</div>
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="mini-stat">
                                            <div class="mini-stat-val">
                                                <?php $default_count = count(array_filter($assigned_fabrics, fn($a)=>$a['is_default'] == 1)); ?>
                                                <?= $default_count ?>
                                            </div>
                                            <div class="mini-stat-lbl">Default Fabric</div>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- ── Assigned Fabrics Table ─────────────── -->
                        <div class="white_card mb_30">
                            <div class="card-header bg-white border-0 py-2">
                                <h5 class="fw-bold mb-0">
                                    <i class="fas fa-cut me-2"></i>Assigned Fabrics
                                </h5>
                            </div>
                            <div class="white_card_body p-0">
                                <?php if (empty($assigned_fabrics)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-cut d-block"></i>
                                        <h5 class="text-muted">No fabrics assigned</h5>
                                        <p class="text-muted small">Click "Assign Fabric" to add fabric options for this product.</p>
                                    </div>
                                <?php else: ?>
                                <form action="" method="POST">
                                    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th style="width:50px;">Swatch</th>
                                                    <th>Fabric Name</th>
                                                    <th>Material</th>
                                                    <th>Price Modifier</th>
                                                    <th style="width:80px;">Sort Order</th>
                                                    <th style="width:100px;">Default</th>
                                                    <th style="width:100px;">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($assigned_fabrics as $idx => $af): ?>
                                                <tr class="fabric-row <?= $af['is_default'] ? 'table-warning' : '' ?>">
                                                    <td>
                                                        <?php if (!empty($af['image']) && file_exists("uploads/fabrics/" . $af['image'])): ?>
                                                            <img src="uploads/fabrics/<?= htmlspecialchars($af['image']) ?>"
                                                                 class="fabric-swatch" alt="<?= htmlspecialchars($af['name']) ?>">
                                                        <?php elseif (!empty($af['swatch_color'])): ?>
                                                            <div class="fabric-swatch-color" style="background:<?= htmlspecialchars($af['swatch_color']) ?>"></div>
                                                        <?php else: ?>
                                                            <div class="fabric-swatch-color" style="background:#ccc"></div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="fw-semibold"><?= htmlspecialchars($af['name']) ?></div>
                                                        <?php if ($af['is_default']): ?>
                                                            <span class="default-badge"><i class="fas fa-star me-1"></i>Default</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="text-muted"><?= htmlspecialchars($af['material'] ?: '—') ?></td>
                                                    <td>
                                                        <?php $pm = (float)$af['price_modifier']; ?>
                                                        <?php if ($pm > 0): ?>
                                                            <span class="price-modifier price-plus">+₹<?= number_format($pm, 2) ?></span>
                                                        <?php elseif ($pm < 0): ?>
                                                            <span class="price-modifier price-minus">-₹<?= number_format(abs($pm), 2) ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">Base price</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="sort_order[<?= $af['id'] ?>]"
                                                               value="<?= $af['sort_order'] ?>"
                                                               class="form-control form-control-sm"
                                                               style="width:70px;" min="0">
                                                    </td>
                                                    <td>
                                                        <?php if ($af['is_default']): ?>
                                                            <span class="badge bg-success">Default</span>
                                                        <?php else: ?>
                                                            <a href="?set_default=<?= $af['id'] ?>&pid=<?= $selected_product_id ?>"
                                                               class="btn btn-sm btn-outline-secondary action-btn"
                                                               title="Set as Default"
                                                               onclick="return confirm('Set <?= htmlspecialchars($af['name']) ?> as default fabric for this product?')">
                                                                <i class="fas fa-star"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button class="btn btn-sm btn-outline-danger action-btn"
                                                                    title="Remove fabric"
                                                                    data-id="<?= $af['id'] ?>"
                                                                    data-name="<?= htmlspecialchars($af['name'], ENT_QUOTES) ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#removeModal">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                            <?php if (!empty($assigned_fabrics)): ?>
                                            <tfoot class="table-light">
                                                <tr>
                                                    <td colspan="4" class="text-end">
                                                        <button type="submit" name="update_sort_order" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-save me-1"></i>Update Sort Order
                                                        </button>
                                                    </td>
                                                    <td colspan="3"></td>
                                                </tr>
                                            </tfoot>
                                            <?php endif; ?>
                                        </table>
                                    </div>
                                </form>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- ══════════════════════════════════════════════════════
         ASSIGN FABRIC MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="assignFabricModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="product_id" value="<?= $selected_product_id ?>">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Assign Fabric to Product
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Select Fabric</label>
                                <div class="row g-2" id="fabricSelectList" style="max-height: 400px; overflow-y: auto;">
                                    <?php foreach ($all_fabrics as $fabric):
                                        $isAssigned = in_array($fabric['id'], $assigned_fabric_ids);
                                    ?>
                                    <div class="col-md-6 col-lg-4">
                                        <div class="fabric-select-card <?= $isAssigned ? 'disabled' : '' ?>"
                                             data-fabric-id="<?= $fabric['id'] ?>"
                                             onclick="<?= $isAssigned ? '' : 'selectFabric(this)' ?>">
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($fabric['image']) && file_exists("uploads/fabrics/" . $fabric['image'])): ?>
                                                    <img src="uploads/fabrics/<?= htmlspecialchars($fabric['image']) ?>"
                                                         style="width:40px;height:40px;object-fit:cover;border-radius:6px;">
                                                <?php elseif (!empty($fabric['swatch_color'])): ?>
                                                    <div style="width:40px;height:40px;border-radius:6px;background:<?= htmlspecialchars($fabric['swatch_color']) ?>;border:1px solid #dee2e6;"></div>
                                                <?php endif; ?>
                                                <div>
                                                    <div class="fw-semibold small"><?= htmlspecialchars($fabric['name']) ?></div>
                                                    <div class="text-muted small"><?= htmlspecialchars($fabric['material'] ?: '—') ?></div>
                                                    <?php if ($isAssigned): ?>
                                                        <span class="badge bg-secondary mt-1" style="font-size:9px;">Already Assigned</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_default" id="assignIsDefault">
                                    <label class="form-check-label fw-semibold" for="assignIsDefault">
                                        Set as Default Fabric
                                    </label>
                                    <small class="text-muted d-block">The default fabric will be pre-selected on the product page.</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <input type="hidden" name="fabric_id" id="selectedFabricId" required>
                                <div id="fabricSelectError" class="text-danger small" style="display:none;">Please select a fabric</div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="assign_fabric" class="btn btn-primary" onclick="return validateFabricSelection()">
                            <i class="fas fa-link me-1"></i>Assign Fabric
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         REMOVE FABRIC MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="removeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Remove Fabric?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-cut fa-xl text-danger"></i>
                    </div>
                    <h5>Remove <strong id="removeFabricName"></strong> from product?</h5>
                    <p class="text-muted small">This will unassign this fabric from the product.</p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmRemoveBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Remove
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── Product list search ──────────────────────────────────
    function filterProductList(q) {
        const query = q.toLowerCase().trim();
        document.querySelectorAll('#productList .product-card').forEach(card => {
            const name = card.getAttribute('data-name') || '';
            card.style.display = name.includes(query) ? '' : 'none';
        });
    }

    // ── Fabric selection for assignment ──────────────────────
    let selectedFabricCard = null;

    function selectFabric(card) {
        // Remove selection from previous
        if (selectedFabricCard) {
            selectedFabricCard.classList.remove('selected');
        }
        selectedFabricCard = card;
        selectedFabricCard.classList.add('selected');
        
        const fabricId = card.getAttribute('data-fabric-id');
        document.getElementById('selectedFabricId').value = fabricId;
        document.getElementById('fabricSelectError').style.display = 'none';
    }

    function validateFabricSelection() {
        if (!document.getElementById('selectedFabricId').value) {
            document.getElementById('fabricSelectError').style.display = 'block';
            return false;
        }
        return true;
    }

    // Reset selection when modal is opened
    document.getElementById('assignFabricModal').addEventListener('show.bs.modal', function() {
        if (selectedFabricCard) {
            selectedFabricCard.classList.remove('selected');
            selectedFabricCard = null;
        }
        document.getElementById('selectedFabricId').value = '';
        document.getElementById('fabricSelectError').style.display = 'none';
    });

    // ── Remove modal ─────────────────────────────────────────
    document.getElementById('removeModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        const name = btn.getAttribute('data-name');
        const id = btn.getAttribute('data-id');
        document.getElementById('removeFabricName').textContent = name;
        document.getElementById('confirmRemoveBtn').href = 
            'manage-product-fabrics.php?remove=' + id + '&pid=<?= $selected_product_id ?>';
    });

    // Tooltips
    document.querySelectorAll('[title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
</body>
</html>