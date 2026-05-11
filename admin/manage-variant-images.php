<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$upload_dir = "uploads/variant-images/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// ── HELPER: upload image ──────────────────────────────────────
function uploadVariantImg($file_key, &$err)
{
    if (empty($_FILES[$file_key]['name'])) return '';
    $ext     = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg'];
    if (!in_array($ext, $allowed)) {
        $err = "Only JPG, PNG, WEBP, SVG allowed.";
        return false;
    }
    if ($_FILES[$file_key]['size'] > 3000000) {
        $err = "Image too large. Max 3MB.";
        return false;
    }
    $fname = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$file_key]['tmp_name'], "uploads/variant-images/$fname")) {
        $err = "Upload failed.";
        return false;
    }
    return $fname;
}

// ── DELETE variant image ──────────────────────────────────────
if (isset($_GET['del'])) {
    $id  = (int)$_GET['del'];
    $pid = (int)$_GET['pid'];
    $row = $conn->prepare("SELECT image_path FROM product_variant_images WHERE id=? AND product_id=?");
    $row->bind_param("ii", $id, $pid);
    $row->execute();
    $data = $row->get_result()->fetch_assoc();
    $row->close();
    if ($data && $data['image_path'] && file_exists("uploads/variant-images/" . $data['image_path'])) {
        unlink("uploads/variant-images/" . $data['image_path']);
    }
    $del = $conn->prepare("DELETE FROM product_variant_images WHERE id=? AND product_id=?");
    $del->bind_param("ii", $id, $pid);
    $del->execute();
    $del->close();
    $_SESSION['success'] = "Image removed.";
    header("Location: manage-variant-images.php?product_id=$pid");
    exit();
}

// ── SAVE: colour variant image ────────────────────────────────
if (isset($_POST['save_color_img'])) {
    $pid      = (int)$_POST['product_id'];
    $color_id = (int)$_POST['color_id'];
    $err = '';
    $fname = uploadVariantImg('color_img', $err);
    if ($fname === false) {
        $_SESSION['error'] = $err;
    } elseif ($fname) {
        // Replace existing if any for same product+color
        $chk = $conn->prepare("SELECT id, image_path FROM product_variant_images WHERE product_id=? AND color_id=? AND customization_option_id IS NULL LIMIT 1");
        $chk->bind_param("ii", $pid, $color_id);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($existing) {
            if ($existing['image_path'] && file_exists("uploads/variant-images/" . $existing['image_path']))
                unlink("uploads/variant-images/" . $existing['image_path']);
            $up = $conn->prepare("UPDATE product_variant_images SET image_path=? WHERE id=?");
            $up->bind_param("si", $fname, $existing['id']);
            $up->execute();
            $up->close();
        } else {
            $ins = $conn->prepare("INSERT INTO product_variant_images (product_id, color_id, image_path, display_order) VALUES (?,?,?,0)");
            $ins->bind_param("iis", $pid, $color_id, $fname);
            $ins->execute();
            $ins->close();
        }
        $_SESSION['success'] = "Colour image saved!";
    } else {
        $_SESSION['error'] = "Please choose an image file.";
    }
    header("Location: manage-variant-images.php?product_id=$pid&tab=colors");
    exit();
}

// ── SAVE: option variant image ────────────────────────────────
if (isset($_POST['save_option_img'])) {
    $pid    = (int)$_POST['product_id'];
    $opt_id = (int)$_POST['option_id'];
    $err    = '';
    $fname  = uploadVariantImg('option_img', $err);
    if ($fname === false) {
        $_SESSION['error'] = $err;
    } elseif ($fname) {
        $chk = $conn->prepare("SELECT id, image_path FROM product_variant_images WHERE product_id=? AND customization_option_id=? AND color_id IS NULL LIMIT 1");
        $chk->bind_param("ii", $pid, $opt_id);
        $chk->execute();
        $existing = $chk->get_result()->fetch_assoc();
        $chk->close();
        if ($existing) {
            if ($existing['image_path'] && file_exists("uploads/variant-images/" . $existing['image_path']))
                unlink("uploads/variant-images/" . $existing['image_path']);
            $up = $conn->prepare("UPDATE product_variant_images SET image_path=? WHERE id=?");
            $up->bind_param("si", $fname, $existing['id']);
            $up->execute();
            $up->close();
        } else {
            $ins = $conn->prepare("INSERT INTO product_variant_images (product_id, customization_option_id, image_path, display_order) VALUES (?,?,?,0)");
            $ins->bind_param("iis", $pid, $opt_id, $fname);
            $ins->execute();
            $ins->close();
        }
        $_SESSION['success'] = "Option image saved!";
    } else {
        $_SESSION['error'] = "Please choose an image file.";
    }
    header("Location: manage-variant-images.php?product_id=$pid&tab=options");
    exit();
}

// ── SAVE: customization_options.image (global option diagram) ─
if (isset($_POST['save_global_opt_img'])) {
    $opt_id = (int)$_POST['opt_id'];
    $err    = '';
    $fname  = uploadVariantImg('global_opt_img', $err);
    if ($fname === false) {
        $_SESSION['error'] = $err;
    } elseif ($fname) {
        // Delete old file
        $old = $conn->prepare("SELECT image FROM customization_options WHERE id=?");
        $old->bind_param("i", $opt_id);
        $old->execute();
        $oldrow = $old->get_result()->fetch_assoc();
        $old->close();
        if ($oldrow && $oldrow['image'] && file_exists("uploads/customization/" . $oldrow['image']))
            unlink("uploads/customization/" . $oldrow['image']);
        // Save to customization_options
        $up = $conn->prepare("UPDATE customization_options SET image=? WHERE id=?");
        $up->bind_param("si", $fname, $opt_id);
        $up->execute();
        $up->close();
        // Also copy to uploads/customization/
        copy("uploads/variant-images/$fname", "uploads/customization/$fname");
        $_SESSION['success'] = "Option diagram image saved!";
    } else {
        $_SESSION['error'] = "Please choose an image file.";
    }
    $pid = (int)$_POST['product_id'];
    header("Location: manage-variant-images.php?product_id=$pid&tab=options");
    exit();
}

// ── FETCH ─────────────────────────────────────────────────────
$product_id  = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$active_tab  = $_GET['tab'] ?? 'colors';

// All products
$products_res = $conn->query("SELECT id, pro_name, product_type, pro_img, is_customizable FROM products WHERE status=1 ORDER BY pro_name ASC");
$all_products = [];
while ($p = $products_res->fetch_assoc()) $all_products[] = $p;

$product = null;
$prod_colors  = [];
$prod_fabrics = [];
$prod_options = [];
$existing_color_imgs  = [];
$existing_option_imgs = [];

if ($product_id) {
    $ps = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
    $ps->bind_param("i", $product_id);
    $ps->execute();
    $product = $ps->get_result()->fetch_assoc();
    $ps->close();

    if ($product) {
        // Colors for this product
        $cr = $conn->prepare("SELECT c.* FROM color_options c INNER JOIN product_color_map pcm ON pcm.color_id=c.id WHERE pcm.product_id=? AND c.is_available=1 ORDER BY c.display_order ASC");
        $cr->bind_param("i", $product_id);
        $cr->execute();
        $crr = $cr->get_result();
        while ($c = $crr->fetch_assoc()) $prod_colors[] = $c;
        $cr->close();
        // Fallback: all colors
        if (empty($prod_colors)) {
            $call = $conn->query("SELECT * FROM color_options WHERE is_available=1 ORDER BY display_order ASC");
            while ($c = $call->fetch_assoc()) $prod_colors[] = $c;
        }

        // All customization options (grouped)
        $or = $conn->query("SELECT co.*, cs.applies_to FROM customization_options co LEFT JOIN configurator_steps cs ON cs.group_name=co.group_name WHERE co.is_available=1 ORDER BY cs.step_order ASC, co.display_order ASC");
        while ($o = $or->fetch_assoc()) $prod_options[$o['group_name']][] = $o;

        // Existing variant images for this product
        $vr = $conn->prepare("SELECT pvi.*, co.name AS color_name, co.hex_code, cop.option_name, cop.group_name FROM product_variant_images pvi LEFT JOIN color_options co ON co.id=pvi.color_id LEFT JOIN customization_options cop ON cop.id=pvi.customization_option_id WHERE pvi.product_id=? ORDER BY pvi.id ASC");
        $vr->bind_param("i", $product_id);
        $vr->execute();
        $vrr = $vr->get_result();
        while ($v = $vrr->fetch_assoc()) {
            if ($v['color_id'])                $existing_color_imgs[$v['color_id']]  = $v;
            if ($v['customization_option_id']) $existing_option_imgs[$v['customization_option_id']] = $v;
        }
        $vr->close();
    }
}

// Coverage stats
$total_colors  = count($prod_colors);
$covered_colors = count($existing_color_imgs);
$total_options = array_sum(array_map('count', $prod_options));
$covered_opts  = count($existing_option_imgs);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Variant Images | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        /* ── Page layout ───────────────────────── */
        .vi-grid {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 0;
            min-height: calc(100vh - 140px);
        }

        @media(max-width:991px) {
            .vi-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ── Product sidebar ─────────────────────*/
        .vi-sidebar {
            background: #fff;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
        }

        .vi-sidebar-head {
            padding: 16px;
            border-bottom: 1px solid #e9ecef;
        }

        .vi-prod-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-bottom: 0.5px solid #f0f0f0;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s;
        }

        .vi-prod-item:hover {
            background: #f8f9fa;
        }

        .vi-prod-item.active {
            background: #f0f4ff;
            border-right: 3px solid #2c3e50;
        }

        .vi-prod-thumb {
            width: 42px;
            height: 42px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #eee;
            flex-shrink: 0;
        }

        .vi-prod-thumb-ph {
            width: 42px;
            height: 42px;
            border-radius: 6px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            flex-shrink: 0;
        }

        .vi-prod-name {
            font-size: 12px;
            font-weight: 500;
            color: #2c3e50;
            line-height: 1.3;
        }

        .vi-prod-type {
            font-size: 10px;
            color: #aaa;
            margin-top: 2px;
        }

        .vi-prod-cov {
            font-size: 10px;
            color: #27ae60;
            margin-top: 2px;
        }

        /* ── Main content ─────────────────────── */
        .vi-main {
            background: #f8f9fa;
            overflow-y: auto;
        }

        .vi-main-inner {
            padding: 20px;
        }

        /* ── Tabs ─────────────────────────────── */
        .vi-tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 20px;
        }

        .vi-tab {
            padding: 10px 20px;
            font-size: 13px;
            font-weight: 500;
            color: #6c757d;
            border: none;
            background: transparent;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            margin-bottom: -2px;
            transition: all .15s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 7px;
        }

        .vi-tab:hover {
            color: #2c3e50;
        }

        .vi-tab.active {
            color: #2c3e50;
            border-bottom-color: #2c3e50;
        }

        .vi-tab .badge {
            font-size: 10px;
        }

        /* ── Coverage bar ─────────────────────── */
        .cov-bar {
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-top: 4px;
        }

        .cov-fill {
            height: 100%;
            background: #27ae60;
            transition: width .4s;
        }

        /* ── Info banner ──────────────────────── */
        .info-banner {
            background: linear-gradient(135deg, #e8f4fd, #d1ecf1);
            border: 1px solid #b8daff;
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .info-banner ol {
            margin: 8px 0 0 16px;
        }

        .info-banner li {
            margin-bottom: 4px;
        }

        /* ── Color card ───────────────────────── */
        .vi-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            overflow: hidden;
        }

        .vi-card-head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }

        .color-swatch {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            flex-shrink: 0;
        }

        .vi-card-body {
            padding: 14px 16px;
        }

        /* Current image */
        .current-img-wrap {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .current-img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #dee2e6;
        }

        .current-img.has-img {
            border-color: #27ae60;
        }

        .img-placeholder {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            background: #f0f0f0;
            border: 2px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ccc;
            font-size: 24px;
            flex-shrink: 0;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .dot-green {
            background: #27ae60;
        }

        .dot-grey {
            background: #dee2e6;
        }

        /* Upload form inline */
        .inline-upload {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .upload-input-wrap {
            position: relative;
            display: inline-block;
        }

        .custom-file-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            border: 1.5px solid #dee2e6;
            background: #f8f9fa;
            font-size: 12px;
            font-weight: 500;
            color: #495057;
            cursor: pointer;
            transition: all .15s;
        }

        .custom-file-btn:hover {
            border-color: #2c3e50;
            color: #2c3e50;
        }

        .custom-file-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            cursor: pointer;
        }

        .btn-save-img {
            padding: 7px 16px;
            border-radius: 6px;
            border: none;
            background: #2c3e50;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all .15s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-save-img:hover {
            background: #1a252f;
        }

        .btn-del-img {
            padding: 7px 10px;
            border-radius: 6px;
            border: none;
            background: #fde8e8;
            color: #e74c3c;
            font-size: 12px;
            cursor: pointer;
            transition: all .15s;
        }

        .btn-del-img:hover {
            background: #e74c3c;
            color: #fff;
        }

        .preview-new {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #27ae60;
            display: none;
        }

        .filename-lbl {
            font-size: 11px;
            color: #6c757d;
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        /* ── Option group ─────────────────────── */
        .opt-group-head {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 0;
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 10px;
        }

        .opt-applies-tag {
            font-size: 10px;
            padding: 2px 8px;
            border-radius: 20px;
            font-weight: 500;
        }

        .tag-jacket {
            background: #d1ecf1;
            color: #0c5460;
        }

        .tag-trouser {
            background: #d4edda;
            color: #155724;
        }

        .tag-waistcoat {
            background: #fff3cd;
            color: #856404;
        }

        .tag-all {
            background: #e2d9f3;
            color: #432874;
        }

        /* ── Option row with image ────────────── */
        .opt-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 14px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin-bottom: 8px;
            background: #fff;
            transition: border-color .15s;
        }

        .opt-row:hover {
            border-color: #ced4da;
        }

        .opt-row.has-img {
            border-left: 3px solid #27ae60;
        }

        .opt-row.no-img {
            border-left: 3px solid #dee2e6;
        }

        .opt-current-img {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            object-fit: cover;
            border: 1px solid #eee;
            flex-shrink: 0;
        }

        .opt-img-ph {
            width: 52px;
            height: 52px;
            border-radius: 6px;
            background: #f5f5f5;
            border: 1px dashed #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            color: #ddd;
            flex-shrink: 0;
        }

        .opt-info {
            flex: 1;
            min-width: 0;
        }

        .opt-name {
            font-size: 13px;
            font-weight: 500;
            color: #2c3e50;
        }

        .opt-price-tag {
            font-size: 11px;
            color: #27ae60;
        }

        .opt-img-status {
            font-size: 10px;
        }

        .opt-img-status.ok {
            color: #27ae60;
        }

        .opt-img-status.nok {
            color: #aaa;
        }

        /* ── Stat cards ───────────────────────── */
        .stat-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 14px;
            text-align: center;
        }

        .stat-val {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
        }

        .stat-lbl {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
        }

        /* ── No product selected ──────────────── */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #aaa;
        }

        .empty-state i {
            font-size: 4rem;
            opacity: .2;
            margin-bottom: 14px;
        }

        /* ── Tip box ──────────────────────────── */
        .tip-box {
            background: #fff8e1;
            border: 1px solid #ffe082;
            border-radius: 8px;
            padding: 12px 16px;
            font-size: 12px;
            color: #5d4037;
            margin-bottom: 16px;
        }

        .tip-box strong {
            color: #e65100;
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "header.php"; ?>

    <section class="main_content dashboard_part large_header_bg">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0"><?php include "top_nav.php"; ?></div>
            </div>
        </div>

        <div class="main_content_iner p-0">

            <?php if (!empty($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show m-3">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['success']);
                    unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (!empty($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show m-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= htmlspecialchars($_SESSION['error']);
                    unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="vi-grid">

                <!-- ══ SIDEBAR: Product list ═══════════════ -->
                <div class="vi-sidebar">
                    <div class="vi-sidebar-head">
                        <h5 class="fw-bold mb-1" style="font-size:14px;">Select Product</h5>
                        <p class="text-muted mb-0" style="font-size:11px;">Choose a product to manage its variant images</p>
                        <input type="text" class="form-control form-control-sm mt-2"
                            placeholder="Search products..."
                            oninput="filterProd(this.value)">
                    </div>
                    <div id="prodList">
                        <?php foreach ($all_products as $p):
                            $isActive = $p['id'] == $product_id;
                            $pimg = $p['pro_img'] ? explode(',', $p['pro_img'])[0] : '';
                            // Count how many variant images this product already has
                            $vcnt = $conn->query("SELECT COUNT(*) FROM product_variant_images WHERE product_id=" . (int)$p['id'])->fetch_row()[0];
                        ?>
                            <a href="manage-variant-images.php?product_id=<?= $p['id'] ?>"
                                class="vi-prod-item <?= $isActive ? 'active' : '' ?>"
                                data-name="<?= htmlspecialchars(strtolower($p['pro_name'])) ?>">
                                <?php if ($pimg && file_exists("assets/img/uploads/$pimg")): ?>
                                    <img src="assets/img/uploads/<?= htmlspecialchars($pimg) ?>" class="vi-prod-thumb" alt="">
                                <?php else: ?>
                                    <div class="vi-prod-thumb-ph"><i class="fas fa-tshirt"></i></div>
                                <?php endif; ?>
                                <div>
                                    <div class="vi-prod-name"><?= htmlspecialchars($p['pro_name']) ?></div>
                                    <div class="vi-prod-type"><?= ucfirst(str_replace('_', ' ', $p['product_type'])) ?></div>
                                    <?php if ($vcnt > 0): ?>
                                        <div class="vi-prod-cov"><i class="fas fa-check-circle me-1"></i><?= $vcnt ?> images set</div>
                                    <?php else: ?>
                                        <div class="vi-prod-type" style="color:#e74c3c;"><i class="fas fa-exclamation-circle me-1"></i>No images</div>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- ══ MAIN CONTENT ════════════════════════ -->
                <div class="vi-main">
                    <div class="vi-main-inner">

                        <?php if (!$product): ?>
                            <div class="empty-state">
                                <i class="fas fa-images d-block"></i>
                                <h5 class="text-muted">Select a product</h5>
                                <p class="text-muted small">Choose a product from the left to manage its configurator preview images.</p>
                            </div>

                        <?php else: ?>

                            <!-- Product header -->
                            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                                <div>
                                    <h3 class="fw-bold mb-1"><?= htmlspecialchars($product['pro_name']) ?></h3>
                                    <span class="badge bg-light text-dark border me-1"><?= ucfirst(str_replace('_', ' ', $product['product_type'])) ?></span>
                                    <span class="badge bg-light text-dark border">ID #<?= $product_id ?></span>
                                </div>
                                <a href="edit_products.php?id=<?= $product_id ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit me-1"></i>Edit Product
                                </a>
                            </div>

                            <!-- Stats -->
                            <div class="stat-row">
                                <div class="stat-card">
                                    <div class="stat-val"><?= $total_colors ?></div>
                                    <div class="stat-lbl">Total Colours</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-val" style="color:<?= $covered_colors > 0 ? '#27ae60' : '#aaa' ?>"><?= $covered_colors ?></div>
                                    <div class="stat-lbl">Colour Images Set</div>
                                    <div class="cov-bar">
                                        <div class="cov-fill" style="width:<?= $total_colors > 0 ? round(($covered_colors / $total_colors) * 100) : 0 ?>%"></div>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-val"><?= $total_options ?></div>
                                    <div class="stat-lbl">Total Options</div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-val" style="color:<?= $covered_opts > 0 ? '#27ae60' : '#aaa' ?>"><?= $covered_opts ?></div>
                                    <div class="stat-lbl">Option Images Set</div>
                                    <div class="cov-bar">
                                        <div class="cov-fill" style="width:<?= $total_options > 0 ? round(($covered_opts / $total_options) * 100) : 0 ?>%"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- How it works info -->
                            <div class="info-banner">
                                <strong><i class="fas fa-info-circle me-2"></i>How visual update works in the configurator:</strong>
                                <ol>
                                    <li><strong>Colour images</strong> — Upload a suit photo for each colour. When customer selects "Navy Blue", the Navy Blue photo will appear in the preview.</li>
                                    <li><strong>Option images</strong> — Upload a diagram/photo for each style option (Notch Lapel, Peak Lapel etc.). When customer selects it, that image shows as preview.</li>
                                    <li>If no specific image is uploaded — the default product photo stays visible (nothing breaks).</li>
                                </ol>
                            </div>

                            <!-- Tabs -->
                            <div class="vi-tabs">
                                <a href="?product_id=<?= $product_id ?>&tab=colors"
                                    class="vi-tab <?= $active_tab === 'colors' ? 'active' : '' ?>">
                                    <i class="fas fa-palette"></i> Colour Images
                                    <span class="badge <?= $covered_colors > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $covered_colors ?>/<?= $total_colors ?>
                                    </span>
                                </a>
                                <a href="?product_id=<?= $product_id ?>&tab=options"
                                    class="vi-tab <?= $active_tab === 'options' ? 'active' : '' ?>">
                                    <i class="fas fa-sliders-h"></i> Option Images
                                    <span class="badge <?= $covered_opts > 0 ? 'bg-success' : 'bg-secondary' ?>">
                                        <?= $covered_opts ?>/<?= $total_options ?>
                                    </span>
                                </a>
                            </div>

                            <?php if ($active_tab === 'colors'): ?>
                                <!-- ════════ COLOUR IMAGES TAB ════════ -->
                                <div class="tip-box">
                                    <strong>Tip:</strong> Upload the same suit photo in each available colour.
                                    Image should clearly show the fabric colour. Recommended size: <strong>600×800px</strong>, transparent or white background PNG.
                                </div>

                                <?php if (empty($prod_colors)): ?>
                                    <div class="alert alert-warning">
                                        No colours assigned to this product.
                                        <a href="edit_products.php?id=<?= $product_id ?>">Assign colours in Edit Product</a>.
                                    </div>
                                <?php endif; ?>

                                <?php foreach ($prod_colors as $col):
                                    $existing = $existing_color_imgs[$col['id']] ?? null;
                                    $imgPath  = $existing ? "uploads/variant-images/" . $existing['image_path'] : '';
                                    $imgExists = $imgPath && file_exists($imgPath);
                                ?>
                                    <div class="vi-card">
                                        <div class="vi-card-head">
                                            <div class="color-swatch" style="background:<?= htmlspecialchars($col['hex_code'] ?? '#ccc') ?>"></div>
                                            <div>
                                                <div class="fw-semibold" style="font-size:13px;"><?= htmlspecialchars($col['name']) ?></div>
                                                <div style="font-size:10px;color:#aaa;"><?= htmlspecialchars($col['hex_code']) ?> · <?= htmlspecialchars($col['color_family'] ?? '') ?></div>
                                            </div>
                                            <div class="ms-auto">
                                                <?php if ($imgExists): ?>
                                                    <span class="badge bg-success"><i class="fas fa-check me-1"></i>Image set</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No image</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="vi-card-body">
                                            <div class="current-img-wrap">
                                                <!-- Current image or placeholder -->
                                                <?php if ($imgExists): ?>
                                                    <img src="<?= htmlspecialchars($imgPath) ?>"
                                                        class="current-img has-img"
                                                        alt="<?= htmlspecialchars($col['name']) ?>"
                                                        onclick="openFullImg('<?= htmlspecialchars($imgPath) ?>')"
                                                        style="cursor:zoom-in;" title="Click to view full">
                                                <?php else: ?>
                                                    <div class="img-placeholder" title="No image uploaded">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Upload form -->
                                                <div>
                                                    <form action="" method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                                        <input type="hidden" name="color_id" value="<?= $col['id'] ?>">
                                                        <div class="inline-upload mb-2">
                                                            <div class="upload-input-wrap">
                                                                <label class="custom-file-btn" id="fabLbl_c<?= $col['id'] ?>">
                                                                    <i class="fas fa-cloud-upload-alt"></i>
                                                                    <span id="fabTxt_c<?= $col['id'] ?>">Choose Image</span>
                                                                    <input type="file" class="custom-file-input"
                                                                        name="color_img" accept="image/*"
                                                                        onchange="previewColorImg(this, <?= $col['id'] ?>)"
                                                                        required>
                                                                </label>
                                                            </div>
                                                            <img id="prevC<?= $col['id'] ?>" class="preview-new" alt="Preview">
                                                            <button type="submit" name="save_color_img" class="btn-save-img">
                                                                <i class="fas fa-save"></i>
                                                                <?= $imgExists ? 'Replace' : 'Upload' ?>
                                                            </button>
                                                            <?php if ($existing): ?>
                                                                <a href="?del=<?= $existing['id'] ?>&pid=<?= $product_id ?>"
                                                                    class="btn-del-img"
                                                                    onclick="return confirm('Remove this colour image?')"
                                                                    title="Remove">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            <?php endif; ?>
                                                        </div>
                                                        <small class="text-muted" style="font-size:10px;">
                                                            JPG, PNG, WEBP — max 3MB · Recommended: 600×800px
                                                        </small>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <!-- ════════ OPTION IMAGES TAB ════════ -->
                                <div class="tip-box">
                                    <strong>Tip:</strong> Upload a clear diagram or product photo for each style option.
                                    Use <strong>line drawings / sketches</strong> for Lapel/Pocket/Vent — they look cleaner in the configurator.
                                    Recommended: <strong>400×400px</strong>, white background PNG.
                                </div>

                                <?php if (empty($prod_options)): ?>
                                    <div class="alert alert-warning">No customization options configured. <a href="customization-options.php">Add options first</a>.</div>
                                <?php endif; ?>

                                <?php foreach ($prod_options as $group_name => $opts):
                                    // Get applies_to from first option
                                    $applies = $opts[0]['applies_to'] ?? 'all';
                                    $applyClass = match (true) {
                                        str_contains($applies, 'jacket')    && !str_contains($applies, 'trouser') => 'tag-jacket',
                                        str_contains($applies, 'trouser')   && !str_contains($applies, 'jacket')  => 'tag-trouser',
                                        str_contains($applies, 'waistcoat') && !str_contains($applies, 'jacket')  => 'tag-waistcoat',
                                        default => 'tag-all'
                                    };
                                    $applyLabel = match (true) {
                                        str_contains($applies, 'jacket')    && !str_contains($applies, 'trouser') => 'Jacket',
                                        str_contains($applies, 'trouser')   && !str_contains($applies, 'jacket')  => 'Trouser',
                                        str_contains($applies, 'waistcoat') && !str_contains($applies, 'jacket')  => 'Waistcoat',
                                        default => 'All'
                                    };
                                ?>
                                    <div class="opt-group-head">
                                        <i class="fas fa-tag text-muted" style="font-size:11px;"></i>
                                        <?= htmlspecialchars($group_name) ?>
                                        <span class="opt-applies-tag <?= $applyClass ?>"><?= $applyLabel ?></span>
                                        <span class="badge bg-secondary ms-auto" style="font-size:10px;"><?= count($opts) ?> options</span>
                                    </div>

                                    <?php foreach ($opts as $opt):
                                        // Check product_variant_images first (product-specific)
                                        $pvExisting = $existing_option_imgs[$opt['id']] ?? null;
                                        $pvPath     = $pvExisting ? "uploads/variant-images/" . $pvExisting['image_path'] : '';
                                        $pvExists   = $pvPath && file_exists($pvPath);

                                        // Check customization_options.image (global diagram)
                                        $globalImg  = $opt['image'] ?? '';
                                        $globalPath = $globalImg ? "uploads/customization/$globalImg" : '';
                                        $globalExists = $globalPath && file_exists($globalPath);

                                        $pm = (float)$opt['price_modifier'];
                                    ?>
                                        <div class="opt-row <?= ($pvExists || $globalExists) ? 'has-img' : 'no-img' ?>">
                                            <!-- Current image -->
                                            <?php if ($pvExists): ?>
                                                <img src="<?= htmlspecialchars($pvPath) ?>" class="opt-current-img"
                                                    alt="" onclick="openFullImg('<?= htmlspecialchars($pvPath) ?>')"
                                                    style="cursor:zoom-in;" title="Product-specific image (click to view)">
                                            <?php elseif ($globalExists): ?>
                                                <img src="<?= htmlspecialchars($globalPath) ?>" class="opt-current-img"
                                                    style="opacity:.7;cursor:zoom-in;"
                                                    onclick="openFullImg('<?= htmlspecialchars($globalPath) ?>')"
                                                    alt="" title="Global option diagram (click to view)">
                                            <?php else: ?>
                                                <div class="opt-img-ph"><i class="fas fa-image"></i></div>
                                            <?php endif; ?>

                                            <!-- Option info -->
                                            <div class="opt-info">
                                                <div class="opt-name"><?= htmlspecialchars($opt['option_name']) ?></div>
                                                <?php if ($pm != 0): ?>
                                                    <div class="opt-price-tag"><?= $pm > 0 ? '+' : '—' ?>₹<?= number_format(abs($pm), 0) ?></div>
                                                <?php endif; ?>
                                                <div class="opt-img-status <?= ($pvExists || $globalExists) ? 'ok' : 'nok' ?>">
                                                    <?php if ($pvExists): ?>
                                                        <i class="fas fa-check-circle me-1"></i>Product image set
                                                    <?php elseif ($globalExists): ?>
                                                        <i class="fas fa-circle me-1" style="color:#f39c12;"></i>Using global diagram
                                                    <?php else: ?>
                                                        <i class="fas fa-circle me-1"></i>No image — showing placeholder
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Upload: product-specific variant image -->
                                            <div>
                                                <form action="" method="POST" enctype="multipart/form-data" style="display:inline;">
                                                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                                    <input type="hidden" name="option_id" value="<?= $opt['id'] ?>">
                                                    <div class="inline-upload">
                                                        <div class="upload-input-wrap">
                                                            <label class="custom-file-btn" style="font-size:11px;padding:5px 10px;">
                                                                <i class="fas fa-upload"></i>
                                                                <span id="optTxt_<?= $opt['id'] ?>">Product photo</span>
                                                                <input type="file" class="custom-file-input"
                                                                    name="option_img" accept="image/*"
                                                                    onchange="previewOptImg(this, <?= $opt['id'] ?>)"
                                                                    required>
                                                            </label>
                                                        </div>
                                                        <img id="prevO<?= $opt['id'] ?>" class="preview-new" alt="">
                                                        <button type="submit" name="save_option_img" class="btn-save-img" style="font-size:11px;padding:5px 10px;">
                                                            <i class="fas fa-save"></i> <?= $pvExists ? 'Replace' : 'Upload' ?>
                                                        </button>
                                                        <?php if ($pvExisting): ?>
                                                            <a href="?del=<?= $pvExisting['id'] ?>&pid=<?= $product_id ?>&tab=options"
                                                                class="btn-del-img" style="font-size:11px;padding:5px 8px;"
                                                                onclick="return confirm('Remove this image?')" title="Remove">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </form>

                                                <!-- Global option diagram upload (saves to customization_options.image) -->
                                                <form action="" method="POST" enctype="multipart/form-data" style="display:inline;margin-top:4px;">
                                                    <input type="hidden" name="product_id" value="<?= $product_id ?>">
                                                    <input type="hidden" name="opt_id" value="<?= $opt['id'] ?>">
                                                    <div class="inline-upload mt-1">
                                                        <div class="upload-input-wrap">
                                                            <label class="custom-file-btn" style="font-size:11px;padding:5px 10px;border-color:#f39c12;color:#856404;">
                                                                <i class="fas fa-drafting-compass"></i>
                                                                <span>Global diagram</span>
                                                                <input type="file" class="custom-file-input"
                                                                    name="global_opt_img" accept="image/*,.svg"
                                                                    required>
                                                            </label>
                                                        </div>
                                                        <button type="submit" name="save_global_opt_img"
                                                            class="btn-save-img" style="font-size:11px;padding:5px 10px;background:#f39c12;">
                                                            <i class="fas fa-globe"></i> Set Global
                                                        </button>
                                                    </div>
                                                    <div style="font-size:10px;color:#888;margin-top:2px;">
                                                        Global diagram shows in configurator for ALL products using this option.
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <br>
                                <?php endforeach; ?>

                            <?php endif; ?>
                        <?php endif; ?>

                    </div>
                </div>
            </div><!-- vi-grid -->
        </div>

        <?php include "footer.php"; ?>
    </section>

    <!-- Full image preview overlay -->
    <div id="imgOverlay" onclick="closeFullImg()"
        style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;align-items:center;justify-content:center;cursor:zoom-out;">
        <img id="imgOverlayImg" style="max-height:90vh;max-width:90vw;border-radius:8px;" alt="">
    </div>

    <script>
        // ── Product search filter ─────────────────────────────
        function filterProd(q) {
            const qry = q.toLowerCase().trim();
            document.querySelectorAll('#prodList .vi-prod-item').forEach(el => {
                el.style.display = el.getAttribute('data-name').includes(qry) ? '' : 'none';
            });
        }

        // ── Colour image preview ──────────────────────────────
        function previewColorImg(input, colorId) {
            const txt = document.getElementById('fabTxt_c' + colorId);
            const prev = document.getElementById('prevC' + colorId);
            if (input.files && input.files[0]) {
                txt.textContent = input.files[0].name.substring(0, 16) + '...';
                const r = new FileReader();
                r.onload = e => {
                    prev.src = e.target.result;
                    prev.style.display = 'block';
                };
                r.readAsDataURL(input.files[0]);
            }
        }

        // ── Option image preview ──────────────────────────────
        function previewOptImg(input, optId) {
            const txt = document.getElementById('optTxt_' + optId);
            const prev = document.getElementById('prevO' + optId);
            if (input.files && input.files[0]) {
                if (txt) txt.textContent = input.files[0].name.substring(0, 14) + '...';
                const r = new FileReader();
                r.onload = e => {
                    if (prev) {
                        prev.src = e.target.result;
                        prev.style.display = 'block';
                    }
                };
                r.readAsDataURL(input.files[0]);
            }
        }

        // ── Full image overlay ────────────────────────────────
        function openFullImg(src) {
            const ov = document.getElementById('imgOverlay');
            document.getElementById('imgOverlayImg').src = src;
            ov.style.display = 'flex';
        }

        function closeFullImg() {
            document.getElementById('imgOverlay').style.display = 'none';
        }
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') closeFullImg();
        });
    </script>
</body>

</html>