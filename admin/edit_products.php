<?php
ob_start();
session_start();
include "db-conn.php";
include "functions.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── VALIDATE PRODUCT ID ───────────────────────────────────────
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$product_id) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: view-products.php");
    exit();
}

// ── FETCH PRODUCT ─────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM products WHERE id=? LIMIT 1");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    $_SESSION['error'] = "Product not found.";
    header("Location: view-products.php");
    exit();
}

// ── FETCH CATEGORIES ──────────────────────────────────────────
$categories = $conn->query("SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC");

// ── FETCH SUBCATEGORIES FOR CURRENT CATEGORY ──────────────────
$subcategories = [];
if ($product['category_id']) {
    $ss = $conn->prepare("SELECT id, name FROM sub_categories WHERE category_id=? ORDER BY name ASC");
    $ss->bind_param("i", $product['category_id']);
    $ss->execute();
    $subres = $ss->get_result();
    while ($s = $subres->fetch_assoc()) $subcategories[] = $s;
    $ss->close();
}

// ── FETCH ALL FABRICS ─────────────────────────────────────────
$fabrics_res = $conn->query("SELECT id, name, swatch_color, material FROM fabric_options WHERE is_available=1 ORDER BY display_order ASC");
$fabrics_arr = [];
while ($f = $fabrics_res->fetch_assoc()) $fabrics_arr[] = $f;

// ── FETCH ASSIGNED FABRICS FOR THIS PRODUCT ───────────────────
$assigned_fabrics = [];
$afr = $conn->prepare("SELECT fabric_id FROM product_fabric_map WHERE product_id=?");
$afr->bind_param("i", $product_id);
$afr->execute();
$afrres = $afr->get_result();
while ($af = $afrres->fetch_assoc()) $assigned_fabrics[] = $af['fabric_id'];
$afr->close();

// ── FETCH ALL COLORS ──────────────────────────────────────────
$colors_res = $conn->query("SELECT id, name, hex_code, color_family FROM color_options WHERE is_available=1 ORDER BY display_order ASC");
$colors_grouped = [];
while ($c = $colors_res->fetch_assoc()) {
    $colors_grouped[$c['color_family'] ?: 'Other'][] = $c;
}

// ── FETCH ASSIGNED COLORS ─────────────────────────────────────
$assigned_colors = [];
$acr = $conn->prepare("SELECT color_id FROM product_color_map WHERE product_id=?");
$acr->bind_param("i", $product_id);
$acr->execute();
$acrres = $acr->get_result();
while ($ac = $acrres->fetch_assoc()) $assigned_colors[] = $ac['color_id'];
$acr->close();

// ── FETCH EXISTING EXTRA IMAGES ───────────────────────────────
$extra_images = [];
$eir = $conn->prepare("SELECT id, image_path FROM product_images WHERE product_id=? ORDER BY id ASC");
$eir->bind_param("i", $product_id);
$eir->execute();
$eirres = $eir->get_result();
while ($ei = $eirres->fetch_assoc()) $extra_images[] = $ei;
$eir->close();

// ── BRANDS ────────────────────────────────────────────────────
$brands = $conn->query("SELECT brand_name FROM brands ORDER BY brand_name ASC");

// Discount calc
$mrp  = (float)($product['mrp'] ?? 0);
$sell = (float)($product['selling_price'] ?? 0);
$disc_pct = ($mrp > 0 && $sell < $mrp) ? round((($mrp - $sell) / $mrp) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Edit: <?= htmlspecialchars($product['pro_name']) ?> | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
    <style>
        .form-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.06);
            margin-bottom: 24px;
            overflow: hidden;
            border: 1px solid #e9ecef;
        }

        .form-section-header {
            padding: 14px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
            font-size: 14px;
            color: #2c3e50;
        }

        .form-section-header .sec-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }

        .form-section-body {
            padding: 20px;
        }

        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 5px;
            font-size: 13px;
        }

        .req {
            color: #e74c3c;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 9px 12px;
            font-size: 13px;
            transition: border-color 0.2s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: #6c63ff;
            box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.12);
        }

        .input-group-text {
            border-radius: 8px 0 0 8px;
            background: #f8f9fa;
            border-color: #dee2e6;
            font-weight: 600;
        }

        /* Fabric/color */
        .swatch-checkbox-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .swatch-check-item {
            position: relative;
        }

        .swatch-check-item input[type=checkbox] {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }

        .swatch-check-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            padding: 8px 10px;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s;
            min-width: 75px;
            text-align: center;
            font-size: 11px;
            font-weight: 500;
            color: #495057;
            background: #fff;
        }

        .swatch-check-label:hover {
            border-color: #6c63ff;
            background: #f5f4ff;
        }

        .swatch-check-item input:checked+.swatch-check-label {
            border-color: #2c3e50;
            background: #f8fafc;
            box-shadow: 0 0 0 2px rgba(44, 62, 80, 0.15);
        }

        .swatch-circle {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .swatch-square {
            width: 32px;
            height: 20px;
            border-radius: 6px;
            border: 1px solid rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .family-label {
            font-size: 11px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin: 12px 0 6px;
        }

        /* Toggles */
        .toggle-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 12px;
        }

        .toggle-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 12px 14px;
        }

        .toggle-label {
            font-size: 13px;
            font-weight: 500;
            color: #2c3e50;
        }

        .toggle-sub {
            font-size: 11px;
            color: #6c757d;
        }

        /* Images */
        .img-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .img-item {
            position: relative;
            width: 100px;
            height: 100px;
            border-radius: 8px;
            overflow: hidden;
            border: 2px solid #dee2e6;
        }

        .img-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .img-item .img-remove {
            position: absolute;
            top: 4px;
            right: 4px;
            background: rgba(220, 53, 69, 0.9);
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 22px;
            height: 22px;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .img-item.main-img {
            border-color: #6c63ff;
        }

        .img-item.main-img::after {
            content: 'Main';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(108, 99, 255, 0.85);
            color: #fff;
            font-size: 10px;
            text-align: center;
            padding: 2px 0;
            font-weight: 600;
        }

        .img-item.new-img {
            border-color: #27ae60;
            border-style: dashed;
        }

        .img-item.new-img::after {
            content: 'New';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(39, 174, 96, 0.85);
            color: #fff;
            font-size: 10px;
            text-align: center;
            padding: 2px 0;
            font-weight: 600;
        }

        .img-drop-zone {
            border: 2px dashed #dee2e6;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: #fafafa;
        }

        .img-drop-zone:hover,
        .img-drop-zone.drag-over {
            border-color: #6c63ff;
            background: #f5f4ff;
        }

        /* Discount */
        .discount-preview {
            background: #d5f5e3;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #27ae60;
            font-weight: 600;
            display: none;
            margin-top: 8px;
        }

        /* Char counter */
        .char-counter {
            font-size: 11px;
        }

        .char-counter.warn {
            color: #f39c12;
        }

        .char-counter.over {
            color: #e74c3c;
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

        <div class="main_content_iner">
            <div class="container-fluid p-3">

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']);
                        unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']);
                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Page header -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="fw-bold mb-0">Edit Product</h2>
                        <p class="text-muted small mb-0">
                            Editing: <strong><?= htmlspecialchars($product['pro_name']) ?></strong>
                            · ID #<?= $product_id ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="view-products.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Products
                        </a>
                        <a href="manage-sizes.php?product_id=<?= $product_id ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-ruler me-1"></i>Manage Sizes
                        </a>
                    </div>
                </div>

                <form action="update-product.php" method="POST" enctype="multipart/form-data" id="productForm" novalidate>
                    <input type="hidden" name="id" value="<?= $product_id ?>">
                    <input type="hidden" name="slug_url" id="slug_url" value="<?= htmlspecialchars($product['slug_url']) ?>">
                    <input type="hidden" name="removed_images" id="removed_images" value="">
                    <input type="hidden" name="existing_images" id="existing_images" value="<?= htmlspecialchars($product['pro_img']) ?>">
                    <input type="hidden" name="removed_extra_images" id="removed_extra_images" value="">

                    <div class="row g-3">

                        <!-- ═══════════ LEFT ═══════════ -->
                        <div class="col-lg-8">

                            <!-- 1. Basic Info -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-info-circle"></i></div>
                                    Basic Information
                                </div>
                                <div class="form-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">Product Name <span class="req">*</span></label>
                                            <input type="text" class="form-control" name="pro_name" id="pro_name"
                                                value="<?= htmlspecialchars($product['pro_name']) ?>"
                                                required oninput="autoSlug(this.value)">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Brand Name</label>
                                            <input type="text" class="form-control" name="brand_name"
                                                value="<?= htmlspecialchars($product['brand_name'] ?? '') ?>"
                                                list="brandSuggestions">
                                            <datalist id="brandSuggestions">
                                                <?php if ($brands && $brands->num_rows > 0): $brands->data_seek(0);
                                                    while ($b = $brands->fetch_assoc()): ?>
                                                        <option value="<?= htmlspecialchars($b['brand_name']) ?>">
                                                    <?php endwhile;
                                                endif; ?>
                                            </datalist>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Slug URL</label>
                                            <div class="input-group">
                                                <span class="input-group-text text-muted" style="font-size:12px;">yourdomain.com/product/</span>
                                                <input type="text" class="form-control" id="slug_display"
                                                    value="<?= htmlspecialchars($product['slug_url']) ?>"
                                                    style="background:#f8f9fa;font-family:monospace;font-size:12px;" readonly>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Short Description</label>
                                            <textarea class="form-control" name="short_desc" id="short_desc" rows="2"><?= htmlspecialchars($product['short_desc'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Full Product Description</label>
                                            <textarea class="form-control" name="description" id="pro_desc" rows="6"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Category -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#e8f5e9;color:#198754;"><i class="fas fa-tags"></i></div>
                                    Category
                                </div>
                                <div class="form-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Main Category <span class="req">*</span></label>
                                            <select class="form-select" name="category_id" id="category_id"
                                                onchange="get_subcategory(this.value)" required>
                                                <option value="">— Select Category —</option>
                                                <?php if ($categories): $categories->data_seek(0);
                                                    while ($cat = $categories->fetch_assoc()): ?>
                                                        <option value="<?= $cat['id'] ?>"
                                                            <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars(ucwords($cat['name'])) ?>
                                                        </option>
                                                <?php endwhile;
                                                endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Sub Category</label>
                                            <select class="form-select" name="sub_category_id" id="sub_category_id">
                                                <option value="">— Select Sub Category —</option>
                                                <!-- FIX: column is `name` not `categories` -->
                                                <?php foreach ($subcategories as $sub): ?>
                                                    <option value="<?= $sub['id'] ?>"
                                                        <?= $product['sub_category_id'] == $sub['id'] ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars(ucwords($sub['name'])) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Pricing -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#fce4f2;color:#e91e8c;"><i class="fas fa-rupee-sign"></i></div>
                                    <!-- FIX: $ → ₹ -->
                                    Pricing <small class="fw-normal text-muted ms-2" style="font-size:12px;">All amounts in ₹ (Indian Rupees)</small>
                                </div>
                                <div class="form-section-body">
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">MRP <span class="req">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="mrp" id="mrp"
                                                    value="<?= htmlspecialchars($product['mrp'] ?? '') ?>"
                                                    step="0.01" min="0" required oninput="calcDiscount()">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Selling Price <span class="req">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="selling_price" id="selling_price"
                                                    value="<?= htmlspecialchars($product['selling_price'] ?? '') ?>"
                                                    step="0.01" min="0" required oninput="calcDiscount()">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Base Price <small class="text-muted fw-normal">(pre-customization)</small></label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="base_price"
                                                    value="<?= htmlspecialchars($product['base_price'] ?? '0') ?>"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Custom Surcharge</label>
                                            <div class="input-group">
                                                <span class="input-group-text">₹</span>
                                                <input type="number" class="form-control" name="custom_surcharge"
                                                    value="<?= htmlspecialchars($product['custom_surcharge'] ?? '0') ?>"
                                                    step="0.01" min="0">
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="discount-preview" id="discountPreview"
                                                style="<?= $disc_pct > 0 ? 'display:block;' : '' ?>">
                                                <i class="fas fa-tag me-1"></i>
                                                Customer saves ₹<?= number_format($mrp - $sell, 2) ?> (<?= $disc_pct ?>% off)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 4. Images -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#fff3e0;color:#fd7e14;"><i class="fas fa-images"></i></div>
                                    Product Images
                                </div>
                                <div class="form-section-body">
                                    <!-- Current main image -->
                                    <?php if (!empty($product['pro_img'])): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Current Main Image</label>
                                            <div class="img-grid" id="mainImgGrid">
                                                <?php
                                                $mainImg = explode(',', $product['pro_img'])[0];
                                                if ($mainImg):
                                                ?>
                                                    <div class="img-item main-img" id="mainImgItem" data-image="<?= htmlspecialchars($mainImg) ?>">
                                                        <img src="assets/img/uploads/<?= htmlspecialchars($mainImg) ?>" alt="">
                                                        <button type="button" class="img-remove" onclick="removeMainImg(this)">×</button>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Extra product images -->
                                    <?php if (!empty($extra_images)): ?>
                                        <div class="mb-3">
                                            <label class="form-label">Gallery Images</label>
                                            <div class="img-grid" id="extraImgGrid">
                                                <?php foreach ($extra_images as $ei): ?>
                                                    <div class="img-item" id="extraImg_<?= $ei['id'] ?>" data-imgid="<?= $ei['id'] ?>">
                                                        <img src="assets/img/uploads/<?= htmlspecialchars($ei['image_path']) ?>" alt="">
                                                        <button type="button" class="img-remove" onclick="removeExtraImg(this, <?= $ei['id'] ?>)">×</button>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Upload new images -->
                                    <label class="form-label">Add New Images</label>
                                    <div class="img-drop-zone" id="dropZone" onclick="document.getElementById('pro_img').click()">
                                        <i class="fas fa-cloud-upload-alt" style="font-size:1.8rem;color:#dee2e6;display:block;margin-bottom:8px;"></i>
                                        <div class="fw-semibold" style="font-size:13px;">Click to upload or drag & drop</div>
                                        <div class="text-muted small mt-1">JPG, PNG, WEBP — max 5MB each</div>
                                    </div>
                                    <input type="file" name="pro_img[]" id="pro_img" multiple
                                        accept="image/jpeg,image/png,image/webp,image/jpg"
                                        class="d-none" onchange="previewNewImages(this)">
                                    <div class="img-grid" id="newImgGrid"></div>
                                </div>
                            </div>

                            <!-- 5. Fabric Options -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#f3e5f5;color:#6f42c1;"><i class="fas fa-scroll"></i></div>
                                    Available Fabrics
                                    <small class="fw-normal text-muted ms-2" style="font-size:12px;">Tick fabrics customers can choose</small>
                                </div>
                                <div class="form-section-body">
                                    <?php if (empty($fabrics_arr)): ?>
                                        <p class="text-muted small mb-0">No fabrics added. <a href="manage-fabric.php">Add fabrics</a> first.</p>
                                    <?php else: ?>
                                        <div class="swatch-checkbox-grid">
                                            <?php foreach ($fabrics_arr as $f): ?>
                                                <div class="swatch-check-item">
                                                    <input type="checkbox" name="fabric_ids[]"
                                                        id="fab_<?= $f['id'] ?>"
                                                        value="<?= $f['id'] ?>"
                                                        <?= in_array($f['id'], $assigned_fabrics) ? 'checked' : '' ?>>
                                                    <label class="swatch-check-label" for="fab_<?= $f['id'] ?>">
                                                        <div class="swatch-square" style="background:<?= htmlspecialchars($f['swatch_color'] ?? '#ccc') ?>"></div>
                                                        <span><?= htmlspecialchars($f['name']) ?></span>
                                                        <?php if ($f['material']): ?>
                                                            <span style="color:#aaa;font-weight:400;"><?= htmlspecialchars($f['material']) ?></span>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 6. Color Options -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-palette"></i></div>
                                    Available Colors
                                    <small class="fw-normal text-muted ms-2" style="font-size:12px;">Tick colors customers can choose</small>
                                </div>
                                <div class="form-section-body">
                                    <?php if (empty($colors_grouped)): ?>
                                        <p class="text-muted small mb-0">No colors added. <a href="manage-colors.php">Add colors</a> first.</p>
                                    <?php else: ?>
                                        <?php foreach ($colors_grouped as $family => $cols): ?>
                                            <div class="family-label"><?= htmlspecialchars($family) ?></div>
                                            <div class="swatch-checkbox-grid mb-2">
                                                <?php foreach ($cols as $c): ?>
                                                    <div class="swatch-check-item">
                                                        <input type="checkbox" name="color_ids[]"
                                                            id="col_<?= $c['id'] ?>"
                                                            value="<?= $c['id'] ?>"
                                                            <?= in_array($c['id'], $assigned_colors) ? 'checked' : '' ?>>
                                                        <label class="swatch-check-label" for="col_<?= $c['id'] ?>">
                                                            <div class="swatch-circle" style="background:<?= htmlspecialchars($c['hex_code'] ?? '#ccc') ?>"></div>
                                                            <span><?= htmlspecialchars($c['name']) ?></span>
                                                        </label>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 7. SEO -->
                            <div class="form-section">
                                <div class="form-section-header">
                                    <div class="sec-icon" style="background:#e8f5e9;color:#27ae60;"><i class="fas fa-search"></i></div>
                                    SEO Settings
                                </div>
                                <div class="form-section-body">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Meta Title</label>
                                            <input type="text" class="form-control" name="meta_title" id="meta_title"
                                                value="<?= htmlspecialchars($product['meta_title'] ?? '') ?>"
                                                maxlength="70" oninput="updateCounter(this,'metaTitleCount',60)">
                                            <span class="char-counter" id="metaTitleCount"><?= strlen($product['meta_title'] ?? '') ?>/60</span>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta Keywords</label>
                                            <input type="text" class="form-control" name="meta_key"
                                                value="<?= htmlspecialchars($product['meta_key'] ?? '') ?>"
                                                placeholder="suit, tailor, slim fit">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Meta Description</label>
                                            <textarea class="form-control" name="meta_desc" id="meta_desc" rows="2"
                                                oninput="updateCounter(this,'metaDescCount',160)"><?= htmlspecialchars($product['meta_desc'] ?? '') ?></textarea>
                                            <span class="char-counter" id="metaDescCount"><?= strlen($product['meta_desc'] ?? '') ?>/160</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div><!-- /LEFT -->

                        <!-- ═══════════ RIGHT ═══════════ -->
                        <div class="col-lg-4">
                            <div style="position:sticky;top:20px;">

                                <!-- Status & Type -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="sec-icon" style="background:#fce4f2;color:#e91e8c;"><i class="fas fa-toggle-on"></i></div>
                                        Publish Settings
                                    </div>
                                    <div class="form-section-body">
                                        <div class="mb-3">
                                            <label class="form-label">Status <span class="req">*</span></label>
                                            <select class="form-select" name="status" required>
                                                <option value="1" <?= $product['status'] == 1 ? 'selected' : '' ?>>✅ Active</option>
                                                <option value="0" <?= $product['status'] == 0 ? 'selected' : '' ?>>⛔ Inactive</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Manufacture Type <span class="req">*</span></label>
                                            <!-- FIX: options match DB ENUM (ready_made/made_to_order/both) -->
                                            <select class="form-select" name="product_type" required>
                                                <option value="both" <?= $product['product_type'] === 'both' ? 'selected' : ''          ?>>Both (Ready Made + Custom)</option>
                                                <option value="ready_made" <?= $product['product_type'] === 'ready_made' ? 'selected' : ''    ?>>Ready Made (Fixed Sizes)</option>
                                                <option value="made_to_order" <?= $product['product_type'] === 'made_to_order' ? 'selected' : '' ?>>Made to Order (Custom Stitch)</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Suit Properties -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="sec-icon" style="background:#e3f2fd;color:#0d6efd;"><i class="fas fa-tshirt"></i></div>
                                        Suit Properties
                                    </div>
                                    <div class="form-section-body">
                                        <div class="mb-3">
                                            <label class="form-label">Fit Type</label>
                                            <!-- FIX: values match DB ENUM slim/regular/oversized/custom -->
                                            <select class="form-select" name="fit_type">
                                                <option value="regular" <?= $product['fit_type'] === 'regular' ? 'selected' : ''   ?>>Regular Fit</option>
                                                <option value="slim" <?= $product['fit_type'] === 'slim' ? 'selected' : ''      ?>>Slim Fit</option>
                                                <option value="oversized" <?= $product['fit_type'] === 'oversized' ? 'selected' : '' ?>>Oversized / Relaxed Fit</option>
                                                <option value="custom" <?= $product['fit_type'] === 'custom' ? 'selected' : ''    ?>>Custom (made-to-measure)</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="form-label">Stock Quantity</label>
                                            <input type="number" class="form-control" name="stock"
                                                value="<?= (int)$product['stock'] ?>" min="0">
                                            <small class="text-muted">0 for made-to-order products</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Highlights -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="sec-icon" style="background:#fff3e0;color:#f39c12;"><i class="fas fa-star"></i></div>
                                        Product Highlights
                                    </div>
                                    <div class="form-section-body">
                                        <div class="toggle-grid">
                                            <div class="toggle-item">
                                                <div>
                                                    <div class="toggle-label">New Arrival</div>
                                                    <div class="toggle-sub">Show "New" badge</div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="new_arrival" value="1"
                                                        <?= $product['new_arrival'] ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                            <div class="toggle-item">
                                                <div>
                                                    <div class="toggle-label">Trending</div>
                                                    <div class="toggle-sub">Show "Hot" badge</div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="trending" value="1"
                                                        <?= $product['trending'] ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                            <div class="toggle-item">
                                                <div>
                                                    <div class="toggle-label">Customizable</div>
                                                    <div class="toggle-sub">Show configurator</div>
                                                </div>
                                                <div class="form-check form-switch mb-0">
                                                    <input class="form-check-input" type="checkbox" name="is_customizable" value="1"
                                                        <?= $product['is_customizable'] ? 'checked' : '' ?>>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quick links -->
                                <div class="form-section">
                                    <div class="form-section-header">
                                        <div class="sec-icon" style="background:#e8f4fd;color:#0d6efd;"><i class="fas fa-external-link-alt"></i></div>
                                        Quick Links
                                    </div>
                                    <div class="form-section-body d-grid gap-2">
                                        <a href="manage-sizes.php?product_id=<?= $product_id ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-ruler me-1"></i>Manage Size Variants
                                        </a>
                                        <a href="multiple_img.php?id=<?= $product_id ?>" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-images me-1"></i>Manage Gallery Images
                                        </a>
                                        <a href="customization-options.php" class="btn btn-sm btn-outline-secondary">
                                            <i class="fas fa-sliders-h me-1"></i>Customization Options
                                        </a>
                                    </div>
                                </div>

                                <!-- Submit -->
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update-product" id="submitBtn" class="btn btn-warning btn-lg fw-semibold">
                                        <i class="fas fa-save me-2"></i>Update Product
                                    </button>
                                    <a href="view-products.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times me-1"></i>Cancel
                                    </a>
                                </div>

                            </div><!-- /sticky -->
                        </div><!-- /RIGHT -->

                    </div><!-- /row -->
                </form>

            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>

    <script>
        // CKEditor
        CKEDITOR.replace('pro_desc', {
            toolbar: 'Basic',
            height: 250
        });
        CKEDITOR.replace('short_desc', {
            toolbar: 'Basic',
            height: 120
        });

        // Auto slug (edit: keeps existing unless changed)
        function autoSlug(val) {
            const slug = val.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-');
            document.getElementById('slug_url').value = slug;
            document.getElementById('slug_display').value = slug;
        }

        // AJAX subcategory load
        function get_subcategory(cate_id) {
            const sel = document.getElementById('sub_category_id');
            const currentSub = '<?= $product['sub_category_id'] ?>';
            if (!cate_id) {
                sel.innerHTML = '<option value="">— Select Sub Category —</option>';
                return;
            }
            $.post('functions.php', {
                cate_id
            }, function(data) {
                sel.innerHTML = data;
                // Re-select if same category
                if (sel.querySelector(`option[value="${currentSub}"]`)) {
                    sel.value = currentSub;
                }
            });
        }

        // Discount preview
        function calcDiscount() {
            const mrp = parseFloat(document.getElementById('mrp').value) || 0;
            const sell = parseFloat(document.getElementById('selling_price').value) || 0;
            const el = document.getElementById('discountPreview');
            if (mrp > 0 && sell > 0 && sell < mrp) {
                const disc = mrp - sell;
                const pct = ((disc / mrp) * 100).toFixed(1);
                el.style.display = 'block';
                el.innerHTML = `<i class="fas fa-tag me-1"></i>Customer saves ₹${disc.toLocaleString('en-IN')} (${pct}% off)`;
            } else {
                el.style.display = 'none';
            }
        }
        calcDiscount(); // Init on load

        // Char counter
        function updateCounter(el, counterId, max) {
            const len = el.value.length;
            const span = document.getElementById(counterId);
            span.textContent = `${len}/${max}`;
            span.className = 'char-counter' + (len > max ? ' over' : len > max * 0.85 ? ' warn' : '');
        }

        // Remove main image
        let removedImages = [];

        function removeMainImg(btn) {
            const item = btn.closest('.img-item');
            const name = item.getAttribute('data-image');
            removedImages.push(name);
            document.getElementById('removed_images').value = removedImages.join(',');
            item.remove();
        }

        // Remove extra gallery image
        let removedExtraImages = [];

        function removeExtraImg(btn, imgId) {
            removedExtraImages.push(imgId);
            document.getElementById('removed_extra_images').value = removedExtraImages.join(',');
            btn.closest('.img-item').remove();
        }

        // Preview new images
        let newFiles = [];

        function previewNewImages(input) {
            const grid = document.getElementById('newImgGrid');
            newFiles = Array.from(input.files);
            grid.innerHTML = '';
            newFiles.forEach((file, i) => {
                const reader = new FileReader();
                reader.onload = e => {
                    const div = document.createElement('div');
                    div.className = 'img-item new-img';
                    div.dataset.index = i;
                    div.innerHTML = `<img src="${e.target.result}" alt=""><button type="button" class="img-remove" onclick="removeNewImg(${i})">×</button>`;
                    grid.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
        }

        function removeNewImg(idx) {
            newFiles.splice(idx, 1);
            const dt = new DataTransfer();
            newFiles.forEach(f => dt.items.add(f));
            document.getElementById('pro_img').files = dt.files;
            previewNewImages(document.getElementById('pro_img'));
        }

        // Drag & drop
        const dz = document.getElementById('dropZone');
        ['dragenter', 'dragover'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.add('drag-over');
        }));
        ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.remove('drag-over');
        }));
        dz.addEventListener('drop', e => {
            const dt2 = new DataTransfer();
            Array.from(e.dataTransfer.files).forEach(f => dt2.items.add(f));
            document.getElementById('pro_img').files = dt2.files;
            previewNewImages(document.getElementById('pro_img'));
        });

        // Form validation
        document.getElementById('productForm').addEventListener('submit', function(e) {
            const mrp = parseFloat(document.getElementById('mrp').value) || 0;
            const sell = parseFloat(document.getElementById('selling_price').value) || 0;
            if (sell > mrp && mrp > 0) {
                e.preventDefault();
                alert('Selling price cannot be higher than MRP (₹' + mrp + ')');
                return;
            }
            const btn = document.getElementById('submitBtn');
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';
            btn.disabled = true;
        });
    </script>
</body>

</html>