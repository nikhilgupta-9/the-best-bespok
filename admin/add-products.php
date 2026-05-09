<?php
ob_start();
session_start();
include "db-conn.php";
include "functions.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── FETCH DATA FOR DROPDOWNS ──────────────────────────────────
$categories   = $conn->query("SELECT id, name FROM categories WHERE status=1 ORDER BY name ASC");
$fabrics      = $conn->query("SELECT id, name, swatch_color, material FROM fabric_options WHERE is_available=1 ORDER BY display_order ASC");
$colors       = $conn->query("SELECT id, name, hex_code, color_family FROM color_options WHERE is_available=1 ORDER BY display_order ASC");
$brands       = $conn->query("SELECT id, brand_name FROM brands ORDER BY brand_name ASC");

// Group colors by family
$colors_grouped = [];
while ($c = $colors->fetch_assoc()) {
    $colors_grouped[$c['color_family'] ?: 'Other'][] = $c;
}

// Group fabrics into array
$fabrics_arr = [];
while ($f = $fabrics->fetch_assoc()) $fabrics_arr[] = $f;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Add New Product | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.ckeditor.com/4.21.0/standard/ckeditor.js"></script>
    <style>
        /* ── Section cards ─────────────────────── */
        .form-section {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
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
            width: 32px; height: 32px; border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }
        .form-section-body { padding: 20px; }

        /* ── Labels ────────────────────────────── */
        .form-label { font-weight: 500; color: #495057; margin-bottom: 5px; font-size: 13px; }
        .req { color: #e74c3c; }

        /* ── Input ─────────────────────────────── */
        .form-control, .form-select {
            border-radius: 8px; border: 1px solid #dee2e6;
            padding: 9px 12px; font-size: 13px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #6c63ff; box-shadow: 0 0 0 3px rgba(108,99,255,0.12);
        }
        .input-group-text { border-radius: 8px 0 0 8px; background: #f8f9fa; border-color: #dee2e6; font-weight: 600; }

        /* ── Fabric / color checkboxes ─────────── */
        .swatch-checkbox-grid { display: flex; flex-wrap: wrap; gap: 10px; }
        .swatch-check-item { position: relative; }
        .swatch-check-item input[type=checkbox] { position: absolute; opacity: 0; width: 0; height: 0; }
        .swatch-check-label {
            display: flex; flex-direction: column; align-items: center; gap: 5px;
            padding: 8px 10px; border: 2px solid #dee2e6; border-radius: 10px;
            cursor: pointer; transition: all 0.15s; min-width: 75px; text-align: center;
            font-size: 11px; font-weight: 500; color: #495057; background: #fff;
        }
        .swatch-check-label:hover { border-color: #6c63ff; background: #f5f4ff; }
        .swatch-check-item input:checked + .swatch-check-label {
            border-color: #2c3e50; background: #f8fafc;
            box-shadow: 0 0 0 2px rgba(44,62,80,0.15);
        }
        .swatch-circle { width: 32px; height: 32px; border-radius: 50%; border: 2px solid rgba(0,0,0,0.1); flex-shrink: 0; }
        .swatch-square { width: 32px; height: 20px; border-radius: 6px; border: 1px solid rgba(0,0,0,0.1); flex-shrink: 0; }
        .family-label { font-size: 11px; font-weight: 600; color: #6c757d; text-transform: uppercase; letter-spacing: 0.05em; margin: 12px 0 6px; }

        /* ── Toggle switches ───────────────────── */
        .toggle-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; }
        .toggle-item {
            display: flex; align-items: center; justify-content: space-between;
            background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 10px;
            padding: 12px 14px;
        }
        .toggle-label { font-size: 13px; font-weight: 500; color: #2c3e50; }
        .toggle-sub   { font-size: 11px; color: #6c757d; }

        /* ── Image upload ──────────────────────── */
        .img-drop-zone {
            border: 2px dashed #dee2e6; border-radius: 12px;
            padding: 30px; text-align: center; cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            background: #fafafa;
        }
        .img-drop-zone:hover, .img-drop-zone.drag-over {
            border-color: #6c63ff; background: #f5f4ff;
        }
        .img-drop-zone i { font-size: 2.5rem; color: #dee2e6; margin-bottom: 10px; }
        .img-preview-grid { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        .img-preview-item {
            position: relative; width: 100px; height: 100px;
            border-radius: 8px; overflow: hidden; border: 2px solid #dee2e6;
        }
        .img-preview-item img { width: 100%; height: 100%; object-fit: cover; }
        .img-preview-item .img-remove {
            position: absolute; top: 4px; right: 4px;
            background: rgba(220,53,69,0.9); color: #fff; border: none;
            border-radius: 50%; width: 22px; height: 22px;
            font-size: 13px; cursor: pointer; display: flex;
            align-items: center; justify-content: center; line-height: 1;
        }
        .img-preview-item:first-child { border-color: #6c63ff; }
        .img-preview-item:first-child::after {
            content: 'Main'; position: absolute; bottom: 0; left: 0; right: 0;
            background: rgba(108,99,255,0.85); color: #fff; font-size: 10px;
            text-align: center; padding: 2px 0; font-weight: 600;
        }

        /* ── Pricing live calc ─────────────────── */
        .discount-preview {
            background: #d5f5e3; border-radius: 8px; padding: 8px 12px;
            font-size: 12px; color: #27ae60; font-weight: 600;
            display: none; margin-top: 8px;
        }

        /* ── Sticky submit bar ─────────────────── */
        .submit-bar {
            position: sticky; bottom: 0; background: #fff;
            border-top: 1px solid #e9ecef; padding: 14px 20px;
            display: flex; justify-content: space-between;
            align-items: center; z-index: 100; margin: 0 -12px -12px;
            border-radius: 0 0 12px 12px;
        }

        /* ── SEO counter ───────────────────────── */
        .char-counter { font-size: 11px; }
        .char-counter.warn  { color: #f39c12; }
        .char-counter.over  { color: #e74c3c; }

        /* ── Required asterisk ─────────────────── */
        .req-note { font-size: 11px; color: #6c757d; }
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

                <!-- Page header -->
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div>
                        <h2 class="fw-bold mb-0">Add New Product</h2>
                        <p class="text-muted small mb-0">Fill in the details below to add a new suit or garment to your store</p>
                    </div>
                    <a href="view-products.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Products
                    </a>
                </div>

                <form id="productForm" action="" method="POST" enctype="multipart/form-data" novalidate>

                <div class="row g-3">

                    <!-- ═══════════ LEFT COLUMN ═══════════ -->
                    <div class="col-lg-8">

                        <!-- 1. Basic Info -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#e8f4fd;color:#0d6efd;">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                                Basic Information
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-8">
                                        <label class="form-label">Product Name <span class="req">*</span></label>
                                        <input type="text" class="form-control" name="pro_name" id="proName"
                                               placeholder="e.g. Classic Navy Slim Fit 2-Piece Suit" required
                                               oninput="autoSlug(this.value)">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Brand Name</label>
                                        <input type="text" class="form-control" name="brand_name"
                                               placeholder="e.g. RoyalWear"
                                               list="brandSuggestions">
                                        <datalist id="brandSuggestions">
                                            <?php if ($brands && $brands->num_rows > 0): $brands->data_seek(0); while ($b = $brands->fetch_assoc()): ?>
                                                <option value="<?= htmlspecialchars($b['brand_name']) ?>">
                                            <?php endwhile; endif; ?>
                                        </datalist>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Slug URL <small class="text-muted fw-normal">(auto-generated)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text text-muted" style="font-size:12px;">yourdomain.com/product/</span>
                                            <input type="text" class="form-control" name="slug_url" id="slug_url" readonly
                                                   style="background:#f8f9fa;font-family:monospace;font-size:12px;">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Short Description</label>
                                        <textarea class="form-control" name="short_desc" id="short_desc" rows="2"
                                                  placeholder="Brief description shown in product listing cards..."></textarea>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Full Product Description</label>
                                        <textarea class="form-control" name="description" id="pro_desc" rows="6"
                                                  placeholder="Detailed product description with features, materials, care instructions..."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Category -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#e8f5e9;color:#198754;">
                                    <i class="fas fa-tags"></i>
                                </div>
                                Category
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Main Category <span class="req">*</span></label>
                                        <select class="form-select" name="category_id" id="category_id"
                                                onchange="get_subcategory(this.value)" required>
                                            <option value="" disabled selected>— Select Category —</option>
                                            <?php if ($categories): $categories->data_seek(0); while ($cat = $categories->fetch_assoc()): ?>
                                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars(ucwords($cat['name'])) ?></option>
                                            <?php endwhile; endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Sub Category <small class="text-muted fw-normal">(optional)</small></label>
                                        <select class="form-select" name="sub_category_id" id="sub_category_id">
                                            <option value="">— Select Sub Category —</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Pricing -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#fce4f2;color:#e91e8c;">
                                    <i class="fas fa-rupee-sign"></i>
                                </div>
                                Pricing <small class="fw-normal text-muted ms-2" style="font-size:12px;">All amounts in ₹ (Indian Rupees)</small>
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label">MRP <span class="req">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="mrp" id="mrp"
                                                   placeholder="0.00" step="0.01" min="0" required
                                                   oninput="calcDiscount()">
                                        </div>
                                        <small class="text-muted">Maximum Retail Price</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Selling Price <span class="req">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="selling_price" id="selling_price"
                                                   placeholder="0.00" step="0.01" min="0" required
                                                   oninput="calcDiscount()">
                                        </div>
                                        <small class="text-muted">Actual selling price</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Base Price <small class="text-muted fw-normal">(before customization)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="base_price"
                                                   placeholder="0.00" step="0.01" min="0">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Custom Surcharge <small class="text-muted fw-normal">(added for customized orders)</small></label>
                                        <div class="input-group">
                                            <span class="input-group-text">₹</span>
                                            <input type="number" class="form-control" name="custom_surcharge"
                                                   placeholder="0.00" step="0.01" min="0" value="0">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="discount-preview" id="discountPreview"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Images -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#fff3e0;color:#fd7e14;">
                                    <i class="fas fa-images"></i>
                                </div>
                                Product Images <span class="req ms-1">*</span>
                            </div>
                            <div class="form-section-body">
                                <div class="img-drop-zone" id="dropZone" onclick="document.getElementById('pro_img').click()">
                                    <i class="fas fa-cloud-upload-alt d-block"></i>
                                    <div class="fw-semibold">Click to upload or drag & drop</div>
                                    <div class="text-muted small mt-1">JPG, PNG, WEBP — max 5MB each · First image = main image</div>
                                </div>
                                <input type="file" name="pro_img[]" id="pro_img" multiple
                                       accept="image/jpeg,image/png,image/webp,image/jpg"
                                       class="d-none" onchange="previewImages(this)">
                                <div class="img-preview-grid" id="imgPreviewGrid"></div>
                            </div>
                        </div>

                        <!-- 5. Fabric Options -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#f3e5f5;color:#6f42c1;">
                                    <i class="fas fa-scroll"></i>
                                </div>
                                Available Fabrics
                                <small class="fw-normal text-muted ms-2" style="font-size:12px;">Select which fabrics customers can choose for this product</small>
                            </div>
                            <div class="form-section-body">
                                <?php if (empty($fabrics_arr)): ?>
                                    <p class="text-muted small mb-0">No fabrics added yet. <a href="manage-fabric.php">Add fabrics</a> first.</p>
                                <?php else: ?>
                                    <div class="swatch-checkbox-grid">
                                        <?php foreach ($fabrics_arr as $f): ?>
                                            <div class="swatch-check-item">
                                                <input type="checkbox" name="fabric_ids[]"
                                                       id="fab_<?= $f['id'] ?>"
                                                       value="<?= $f['id'] ?>">
                                                <label class="swatch-check-label" for="fab_<?= $f['id'] ?>">
                                                    <div class="swatch-square"
                                                         style="background:<?= htmlspecialchars($f['swatch_color'] ?? '#ccc') ?>"></div>
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
                                <div class="sec-icon" style="background:#e8f4fd;color:#0d6efd;">
                                    <i class="fas fa-palette"></i>
                                </div>
                                Available Colors
                                <small class="fw-normal text-muted ms-2" style="font-size:12px;">Select which colors customers can choose for this product</small>
                            </div>
                            <div class="form-section-body">
                                <?php if (empty($colors_grouped)): ?>
                                    <p class="text-muted small mb-0">No colors added yet. <a href="manage-colors.php">Add colors</a> first.</p>
                                <?php else: ?>
                                    <?php foreach ($colors_grouped as $family => $cols): ?>
                                        <div class="family-label"><?= htmlspecialchars($family) ?></div>
                                        <div class="swatch-checkbox-grid mb-2">
                                            <?php foreach ($cols as $c): ?>
                                                <div class="swatch-check-item">
                                                    <input type="checkbox" name="color_ids[]"
                                                           id="col_<?= $c['id'] ?>"
                                                           value="<?= $c['id'] ?>">
                                                    <label class="swatch-check-label" for="col_<?= $c['id'] ?>">
                                                        <div class="swatch-circle"
                                                             style="background:<?= htmlspecialchars($c['hex_code'] ?? '#ccc') ?>"></div>
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
                                <div class="sec-icon" style="background:#e8f5e9;color:#27ae60;">
                                    <i class="fas fa-search"></i>
                                </div>
                                SEO Settings
                            </div>
                            <div class="form-section-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label">Meta Title <small class="text-muted fw-normal">(50–60 chars recommended)</small></label>
                                        <input type="text" class="form-control" name="meta_title" id="meta_title"
                                               placeholder="SEO title shown in Google results" maxlength="70"
                                               oninput="updateCounter(this,'metaTitleCount',60)">
                                        <span class="char-counter" id="metaTitleCount">0/60</span>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Meta Keywords</label>
                                        <input type="text" class="form-control" name="meta_key"
                                               placeholder="suit, wedding suit, slim fit suit, tailor">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Meta Description <small class="text-muted fw-normal">(150–160 chars)</small></label>
                                        <textarea class="form-control" name="meta_desc" id="meta_desc" rows="2"
                                                  placeholder="Brief description shown under title in search results..."
                                                  oninput="updateCounter(this,'metaDescCount',160)"></textarea>
                                        <span class="char-counter" id="metaDescCount">0/160</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div><!-- /LEFT -->

                    <!-- ═══════════ RIGHT COLUMN ═══════════ -->
                    <div class="col-lg-4">

                        <!-- Publish card (sticky) -->
                        <div style="position:sticky;top:20px;">

                        <!-- Status & Visibility -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#fce4f2;color:#e91e8c;">
                                    <i class="fas fa-toggle-on"></i>
                                </div>
                                Publish Settings
                            </div>
                            <div class="form-section-body">
                                <div class="mb-3">
                                    <label class="form-label">Status <span class="req">*</span></label>
                                    <select class="form-select" name="status" required>
                                        <option value="1" selected>✅ Active — Visible to customers</option>
                                        <option value="0">⛔ Inactive — Hidden from store</option>
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label">Manufacture Type <span class="req">*</span></label>
                                    <select class="form-select" name="product_type" required>
                                        <option value="both" selected>Both (Ready Made + Custom)</option>
                                        <option value="ready_made">Ready Made (Fixed Sizes)</option>
                                        <option value="made_to_order">Made to Order (Custom Stitch)</option>
                                    </select>
                                    <small class="text-muted">Controls whether size chart or custom measurements flow is shown</small>
                                </div>
                            </div>
                        </div>

                        <!-- Product Properties -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#e3f2fd;color:#0d6efd;">
                                    <i class="fas fa-tshirt"></i>
                                </div>
                                Suit Properties
                            </div>
                            <div class="form-section-body">
                                <div class="mb-3">
                                    <label class="form-label">Fit Type</label>
                                    <select class="form-select" name="fit_type">
                                        <!-- FIX: values match DB ENUM (slim/regular/oversized/custom) -->
                                        <option value="regular" selected>Regular Fit</option>
                                        <option value="slim">Slim Fit</option>
                                        <option value="oversized">Oversized / Relaxed Fit</option>
                                        <option value="custom">Custom (made-to-measure)</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Stock Quantity</label>
                                    <input type="number" class="form-control" name="stock"
                                           placeholder="0 for made-to-order" min="0" value="0">
                                    <small class="text-muted">Set 0 for made-to-order products</small>
                                </div>
                            </div>
                        </div>

                        <!-- Highlights (Toggle group) -->
                        <div class="form-section">
                            <div class="form-section-header">
                                <div class="sec-icon" style="background:#fff3e0;color:#f39c12;">
                                    <i class="fas fa-star"></i>
                                </div>
                                Product Highlights
                            </div>
                            <div class="form-section-body">
                                <div class="toggle-grid">
                                    <!-- New Arrival -->
                                    <div class="toggle-item">
                                        <div>
                                            <div class="toggle-label">New Arrival</div>
                                            <div class="toggle-sub">Show "New" badge</div>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="new_arrival" value="1" id="newArrival">
                                        </div>
                                    </div>
                                    <!-- Trending -->
                                    <div class="toggle-item">
                                        <div>
                                            <div class="toggle-label">Trending</div>
                                            <div class="toggle-sub">Show "Hot" badge</div>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="trending" value="1" id="trending">
                                        </div>
                                    </div>
                                    <!-- Customizable -->
                                    <div class="toggle-item">
                                        <div>
                                            <div class="toggle-label">Customizable</div>
                                            <div class="toggle-sub">Show configurator</div>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" name="is_customizable" value="1" id="isCustomizable" checked>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="d-grid gap-2">
                            <button type="submit" name="add-product" id="submitBtn" class="btn btn-primary btn-lg">
                                <i class="fas fa-plus-circle me-2"></i>Add Product
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
    // Init CKEditor
    CKEDITOR.replace('pro_desc', { toolbar: 'Basic', height: 250 });
    CKEDITOR.replace('short_desc', { toolbar: 'Basic', height: 120 });

    // Auto slug
    function autoSlug(val) {
        document.getElementById('slug_url').value = val.toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim().replace(/\s+/g, '-');
    }

    // AJAX subcategory
    function get_subcategory(cate_id) {
        const sel = document.getElementById('sub_category_id');
        if (!cate_id) { sel.innerHTML = '<option value="">— Select Sub Category —</option>'; return; }
        $.post('functions.php', { cate_id }, function(data) { sel.innerHTML = data; });
    }

    // Discount preview
    function calcDiscount() {
        const mrp  = parseFloat(document.getElementById('mrp').value)          || 0;
        const sell = parseFloat(document.getElementById('selling_price').value) || 0;
        const el   = document.getElementById('discountPreview');
        if (mrp > 0 && sell > 0 && sell < mrp) {
            const disc = mrp - sell;
            const pct  = ((disc / mrp) * 100).toFixed(1);
            el.style.display = 'block';
            el.innerHTML = `<i class="fas fa-tag me-1"></i>Customer saves ₹${disc.toLocaleString('en-IN')} (${pct}% off)`;
        } else {
            el.style.display = 'none';
        }
    }

    // Char counter
    function updateCounter(el, counterId, max) {
        const len = el.value.length;
        const span = document.getElementById(counterId);
        span.textContent = `${len}/${max}`;
        span.className = 'char-counter' + (len > max ? ' over' : len > max * 0.85 ? ' warn' : '');
    }

    // Image preview
    let selectedFiles = [];
    function previewImages(input) {
        const grid = document.getElementById('imgPreviewGrid');
        selectedFiles = Array.from(input.files);
        grid.innerHTML = '';
        selectedFiles.forEach((file, i) => {
            const reader = new FileReader();
            reader.onload = e => {
                const div = document.createElement('div');
                div.className = 'img-preview-item';
                div.dataset.index = i;
                div.innerHTML = `<img src="${e.target.result}" alt="">
                                 <button type="button" class="img-remove" onclick="removeImg(${i})">×</button>`;
                grid.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
    function removeImg(idx) {
        selectedFiles.splice(idx, 1);
        const dt = new DataTransfer();
        selectedFiles.forEach(f => dt.items.add(f));
        document.getElementById('pro_img').files = dt.files;
        previewImages(document.getElementById('pro_img'));
    }

    // Drag & drop
    const dz = document.getElementById('dropZone');
    ['dragenter','dragover'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.add('drag-over'); }));
    ['dragleave','drop'].forEach(ev => dz.addEventListener(ev, e => { e.preventDefault(); dz.classList.remove('drag-over'); }));
    dz.addEventListener('drop', e => {
        const input = document.getElementById('pro_img');
        const dt = new DataTransfer();
        Array.from(e.dataTransfer.files).forEach(f => dt.items.add(f));
        input.files = dt.files;
        previewImages(input);
    });

    // Form validation
    document.getElementById('productForm').addEventListener('submit', function(e) {
        const mrp  = parseFloat(document.getElementById('mrp').value)          || 0;
        const sell = parseFloat(document.getElementById('selling_price').value) || 0;
        const imgs = document.getElementById('pro_img').files.length;

        if (sell > mrp && mrp > 0) {
            e.preventDefault();
            alert('Selling price cannot be higher than MRP (₹' + mrp + ')');
            return;
        }
        if (imgs === 0) {
            e.preventDefault();
            alert('Please upload at least one product image.');
            return;
        }
        const btn = document.getElementById('submitBtn');
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding Product...';
        btn.disabled = true;
    });
    </script>
</body>
</html>