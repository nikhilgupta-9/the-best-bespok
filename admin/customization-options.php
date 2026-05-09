<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$upload_dir = "uploads/customization/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

// ── HELPER: delete image file ────────────────────────────────
function deleteOptionImage($filename) {
    $path = "uploads/customization/" . $filename;
    if ($filename && file_exists($path)) unlink($path);
}

// ── HELPER: handle image upload ──────────────────────────────
function uploadOptionImage($file_key, &$error) {
    if (empty($_FILES[$file_key]['name'])) return '';
    $ext     = strtolower(pathinfo($_FILES[$file_key]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','gif','webp','svg'];
    if (!in_array($ext, $allowed)) { $error = "Invalid image type. Use JPG, PNG, WEBP or SVG."; return false; }
    if ($_FILES[$file_key]['size'] > 2000000) { $error = "Image too large. Max 2MB."; return false; }
    $filename = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$file_key]['tmp_name'], "uploads/customization/" . $filename)) {
        $error = "Failed to upload image."; return false;
    }
    return $filename;
}

// ── TOGGLE available ─────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = (int)$_GET['toggle'];
    $new = (int)$_GET['status'] == 1 ? 0 : 1;
    $s   = $conn->prepare("UPDATE customization_options SET is_available=? WHERE id=?");
    $s->bind_param("ii", $new, $id);
    $s->execute(); $s->close();
    header("Location: customization-options.php" . (isset($_GET['group']) ? "?group=".urlencode($_GET['group']) : ''));
    exit();
}

// ── SET DEFAULT ──────────────────────────────────────────────
// Only one option per group can be default
if (isset($_GET['set_default'])) {
    $id   = (int)$_GET['set_default'];
    $group = $conn->query("SELECT group_name FROM customization_options WHERE id=$id")->fetch_assoc()['group_name'];
    $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=?")->bind_param("s",$group);
    $stmt = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=?");
    $stmt->bind_param("s", $group); $stmt->execute(); $stmt->close();
    $stmt2 = $conn->prepare("UPDATE customization_options SET is_default=1 WHERE id=?");
    $stmt2->bind_param("i", $id); $stmt2->execute(); $stmt2->close();
    $_SESSION['success'] = "Default option updated.";
    header("Location: customization-options.php" . (isset($_GET['group']) ? "?group=".urlencode($group) : ''));
    exit();
}

// ── DELETE ───────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->prepare("SELECT image, group_name FROM customization_options WHERE id=?");
    $row->bind_param("i", $id); $row->execute();
    $data = $row->get_result()->fetch_assoc(); $row->close();

    // Block delete if option is used in product_customization_map
    $chk = $conn->prepare("SELECT COUNT(*) FROM product_customization_map WHERE customization_option_id=?");
    $chk->bind_param("i", $id); $chk->execute();
    $used = $chk->get_result()->fetch_row()[0]; $chk->close();

    if ($used > 0) {
        $_SESSION['error'] = "Cannot delete — this option is assigned to $used product(s). Remove it from those products first.";
    } else {
        if ($data['image']) deleteOptionImage($data['image']);
        $del = $conn->prepare("DELETE FROM customization_options WHERE id=?");
        $del->bind_param("i", $id);
        $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
            ? "Option deleted." : "Delete failed: " . $conn->error;
        $del->close();
    }
    header("Location: customization-options.php" . ($data ? "?group=".urlencode($data['group_name']) : ''));
    exit();
}

// ── ADD ──────────────────────────────────────────────────────
if (isset($_POST['add_option'])) {
    $group_name    = trim($_POST['group_name']);
    $new_group     = trim($_POST['new_group_name'] ?? '');
    if ($group_name === '__new__' && $new_group) $group_name = $new_group;

    $option_name   = trim($_POST['option_name']);
    $description   = trim($_POST['description']);
    $price_modifier= (float)$_POST['price_modifier'];
    $is_default    = isset($_POST['is_default']) ? 1 : 0;
    $is_available  = isset($_POST['is_available']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    $err = '';
    $image = uploadOptionImage('image', $err);

    if ($image === false) {
        $_SESSION['error'] = $err;
        header("Location: customization-options.php");
        exit();
    }

    // If set as default, clear other defaults in this group first
    if ($is_default) {
        $clr = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=?");
        $clr->bind_param("s", $group_name); $clr->execute(); $clr->close();
    }

    $s = $conn->prepare(
        "INSERT INTO customization_options (group_name, option_name, description, image, price_modifier, is_default, is_available, display_order)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $s->bind_param("ssssdiii", $group_name, $option_name, $description, $image, $price_modifier, $is_default, $is_available, $display_order);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Option \"$option_name\" added to \"$group_name\"!" : "Error: " . $conn->error;
    $s->close();
    header("Location: customization-options.php?group=" . urlencode($group_name));
    exit();
}

// ── EDIT SAVE ────────────────────────────────────────────────
if (isset($_POST['edit_option'])) {
    $id            = (int)$_POST['id'];
    $group_name    = trim($_POST['group_name']);
    $option_name   = trim($_POST['option_name']);
    $description   = trim($_POST['description']);
    $price_modifier= (float)$_POST['price_modifier'];
    $is_default    = isset($_POST['is_default']) ? 1 : 0;
    $is_available  = isset($_POST['is_available']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];
    $existing_img  = trim($_POST['existing_image']);
    $remove_img    = isset($_POST['remove_image']);
    $err = '';

    $image = $existing_img;
    if ($remove_img) { deleteOptionImage($existing_img); $image = ''; }
    $new_img = uploadOptionImage('image', $err);
    if ($new_img === false) { $_SESSION['error'] = $err; header("Location: customization-options.php"); exit(); }
    if ($new_img) { if ($existing_img) deleteOptionImage($existing_img); $image = $new_img; }

    if ($is_default) {
        $clr = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=? AND id != ?");
        $clr->bind_param("si", $group_name, $id); $clr->execute(); $clr->close();
    }

    $s = $conn->prepare(
        "UPDATE customization_options SET group_name=?, option_name=?, description=?, image=?, price_modifier=?, is_default=?, is_available=?, display_order=? WHERE id=?"
    );
    $s->bind_param("ssssdiiii", $group_name, $option_name, $description, $image, $price_modifier, $is_default, $is_available, $display_order, $id);
    $s->bind_param("ssssdiiii", $group_name, $option_name, $description, $image, $price_modifier, $is_default, $is_available, $display_order, $id);
    $s->close();

    // Clean single statement
    $stmt = $conn->prepare(
        "UPDATE customization_options SET group_name=?, option_name=?, description=?, image=?, price_modifier=?, is_default=?, is_available=?, display_order=? WHERE id=?"
    );
    $stmt->bind_param("ssssdiiii", $group_name, $option_name, $description, $image, $price_modifier, $is_default, $is_available, $display_order, $id);
    // fix bind: price_modifier=d, rest ints, id=i
    $stmt->close();
    $st = $conn->prepare(
        "UPDATE customization_options SET group_name=?, option_name=?, description=?, image=?, price_modifier=?, is_default=?, is_available=?, display_order=? WHERE id=?"
    );
    $st->bind_param("ssssdiiii", $group_name, $option_name, $description, $image, $price_modifier, $is_default, $is_available, $display_order, $id);
    $_SESSION[$st->execute() ? 'success' : 'error'] = $st->execute()
        ? "Option updated!" : "Error: " . $conn->error;
    $st->close();
    header("Location: customization-options.php?group=" . urlencode($group_name));
    exit();
}

// ── FETCH DATA ────────────────────────────────────────────────
$active_group = trim($_GET['group'] ?? '');

// All distinct groups with counts
$groups_res = $conn->query(
    "SELECT group_name,
            COUNT(*) as total,
            SUM(is_available) as active_count
     FROM customization_options
     GROUP BY group_name
     ORDER BY MIN(display_order) ASC, group_name ASC"
);
$all_groups = [];
while ($g = $groups_res->fetch_assoc()) $all_groups[] = $g;

// Options for active group (or all if no group selected)
if ($active_group) {
    $stmt = $conn->prepare(
        "SELECT * FROM customization_options WHERE group_name=? ORDER BY display_order ASC, id ASC"
    );
    $stmt->bind_param("s", $active_group);
    $stmt->execute();
    $options_res = $stmt->get_result();
    $stmt->close();
} else {
    $options_res = $conn->query(
        "SELECT * FROM customization_options ORDER BY group_name ASC, display_order ASC"
    );
}

// Usage count per option
$usage_map = [];
$uq = $conn->query("SELECT customization_option_id, COUNT(*) as cnt FROM product_customization_map GROUP BY customization_option_id");
while ($u = $uq->fetch_assoc()) $usage_map[$u['customization_option_id']] = $u['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Customization Options | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        /* ── Group tabs ──────────────────────── */
        .group-tabs { display: flex; flex-wrap: wrap; gap: 8px; padding: 16px 0 0; }
        .group-tab {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 30px; border: 1.5px solid #dee2e6;
            background: #fff; cursor: pointer; text-decoration: none;
            color: #495057; font-size: 13px; font-weight: 500;
            transition: all 0.18s;
        }
        .group-tab:hover { border-color: #6c63ff; color: #6c63ff; background: #f5f4ff; }
        .group-tab.active { background: #2c3e50; border-color: #2c3e50; color: #fff; }
        .group-tab .cnt {
            font-size: 11px; padding: 1px 7px; border-radius: 20px;
            background: rgba(255,255,255,0.2);
        }
        .group-tab:not(.active) .cnt { background: #f0f0f0; color: #666; }

        /* ── Option cards ─────────────────────── */
        .option-card {
            background: #fff; border: 1px solid #e9ecef;
            border-radius: 10px; padding: 0;
            transition: box-shadow 0.18s, transform 0.18s;
            overflow: hidden;
        }
        .option-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.09); transform: translateY(-2px); }
        .option-card.inactive { opacity: 0.55; }
        .option-img {
            width: 100%; height: 120px; object-fit: cover;
            background: #f8f9fa; border-bottom: 1px solid #f0f0f0;
        }
        .option-img-placeholder {
            width: 100%; height: 120px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex; align-items: center; justify-content: center;
            border-bottom: 1px solid #f0f0f0;
            color: #adb5bd; font-size: 2rem;
        }
        .option-body { padding: 12px 14px; }
        .option-name { font-size: 14px; font-weight: 600; margin: 0 0 4px; color: #2c3e50; }
        .option-group-label {
            font-size: 10px; text-transform: uppercase; letter-spacing: 0.06em;
            color: #6c757d; margin-bottom: 6px;
        }
        .option-price { font-size: 13px; font-weight: 600; }
        .price-plus  { color: #27ae60; }
        .price-minus { color: #e74c3c; }
        .price-zero  { color: #6c757d; }
        .option-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding: 8px 14px; border-top: 1px solid #f0f0f0;
            background: #fafafa;
        }

        /* ── Table row ────────────────────────── */
        .action-btn {
            width: 30px; height: 30px; border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            padding: 0;
        }
        .badge-default { font-size: 10px; }
        .opt-img-thumb {
            width: 40px; height: 40px; object-fit: cover;
            border-radius: 6px; border: 1px solid #eee;
        }

        /* ── Section header ───────────────────── */
        .group-section-header {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 0 6px;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 14px;
        }
        .group-section-title { font-size: 16px; font-weight: 600; color: #2c3e50; margin: 0; }

        /* ── View toggle ──────────────────────── */
        .view-toggle .btn.active { background: #2c3e50; color: #fff; border-color: #2c3e50; }

        /* ── Image upload preview ─────────────── */
        .img-upload-preview {
            width: 80px; height: 80px; object-fit: cover;
            border-radius: 8px; border: 1px solid #dee2e6;
        }
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

                <div class="white_card mb_30">

                    <!-- ── Page header ────────────────────────────── -->
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <h2 class="mb-0 fw-bold">Customization Options</h2>
                                <p class="text-muted small mb-0">
                                    <?= count($all_groups) ?> option groups ·
                                    <?= array_sum(array_column($all_groups, 'total')) ?> total options
                                </p>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <!-- View toggle -->
                                <div class="btn-group view-toggle">
                                    <button class="btn btn-sm btn-outline-secondary active" id="btnCardView" onclick="switchView('card')">
                                        <i class="fas fa-th-large"></i> Cards
                                    </button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnTableView" onclick="switchView('table')">
                                        <i class="fas fa-list"></i> Table
                                    </button>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOptionModal">
                                    <i class="fas fa-plus me-2"></i>Add Option
                                </button>
                            </div>
                        </div>

                        <!-- ── Group tabs ──────────────────────────── -->
                        <div class="group-tabs">
                            <a href="customization-options.php"
                               class="group-tab <?= !$active_group ? 'active' : '' ?>">
                                <i class="fas fa-layer-group"></i> All Groups
                                <span class="cnt"><?= array_sum(array_column($all_groups, 'total')) ?></span>
                            </a>
                            <?php foreach ($all_groups as $g): ?>
                                <a href="?group=<?= urlencode($g['group_name']) ?>"
                                   class="group-tab <?= $active_group === $g['group_name'] ? 'active' : '' ?>">
                                    <?= htmlspecialchars($g['group_name']) ?>
                                    <span class="cnt"><?= $g['total'] ?></span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="white_card_body">

                        <?php
                        // Collect options into array grouped
                        $options_all = [];
                        while ($o = $options_res->fetch_assoc()) {
                            $options_all[$o['group_name']][] = $o;
                        }

                        if (empty($options_all)):
                        ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-sliders-h fa-3x mb-3 d-block"></i>
                                <h5>No options found</h5>
                                <p>Start building your suit configurator by adding customization options.</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addOptionModal">
                                    <i class="fas fa-plus me-2"></i>Add First Option
                                </button>
                            </div>
                        <?php else: ?>

                        <!-- ══════════════════════════════════════════
                             CARD VIEW
                        ══════════════════════════════════════════ -->
                        <div id="cardView">
                        <?php foreach ($options_all as $group_name => $options): ?>
                            <div class="group-section-header">
                                <h5 class="group-section-title">
                                    <i class="fas fa-tag me-2 text-muted" style="font-size:13px;"></i>
                                    <?= htmlspecialchars($group_name) ?>
                                    <span class="badge bg-secondary ms-2" style="font-size:12px;"><?= count($options) ?></span>
                                </h5>
                                <button class="btn btn-sm btn-outline-primary"
                                        onclick="openAddModal('<?= addslashes($group_name) ?>')">
                                    <i class="fas fa-plus me-1"></i> Add to this group
                                </button>
                            </div>

                            <div class="row g-3 mb-4">
                            <?php foreach ($options as $opt):
                                $pm       = (float)$opt['price_modifier'];
                                $pm_class = $pm > 0 ? 'price-plus' : ($pm < 0 ? 'price-minus' : 'price-zero');
                                $pm_text  = $pm > 0 ? '+₹'.number_format($pm,2) : ($pm < 0 ? '-₹'.number_format(abs($pm),2) : 'Included');
                                $used     = $usage_map[$opt['id']] ?? 0;
                            ?>
                                <div class="col-6 col-sm-4 col-md-3 col-xl-2">
                                    <div class="option-card <?= !$opt['is_available'] ? 'inactive' : '' ?>">

                                        <!-- Image or placeholder -->
                                        <?php if (!empty($opt['image']) && file_exists("uploads/customization/".$opt['image'])): ?>
                                            <img src="uploads/customization/<?= htmlspecialchars($opt['image']) ?>"
                                                 class="option-img" alt="<?= htmlspecialchars($opt['option_name']) ?>">
                                        <?php else: ?>
                                            <div class="option-img-placeholder">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        <?php endif; ?>

                                        <div class="option-body">
                                            <?php if (!$active_group): ?>
                                                <div class="option-group-label"><?= htmlspecialchars($group_name) ?></div>
                                            <?php endif; ?>
                                            <div class="option-name"><?= htmlspecialchars($opt['option_name']) ?></div>
                                            <div class="d-flex align-items-center justify-content-between mt-1">
                                                <span class="option-price <?= $pm_class ?>"><?= $pm_text ?></span>
                                                <?php if ($opt['is_default']): ?>
                                                    <span class="badge bg-warning text-dark badge-default">Default</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="option-footer">
                                            <div class="d-flex gap-1">
                                                <!-- Toggle active -->
                                                <a href="?toggle=<?= $opt['id'] ?>&status=<?= $opt['is_available'] ?>&group=<?= urlencode($group_name) ?>"
                                                   class="btn btn-sm <?= $opt['is_available'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> action-btn"
                                                   title="<?= $opt['is_available'] ? 'Deactivate' : 'Activate' ?>">
                                                    <i class="fas fa-power-off" style="font-size:10px;"></i>
                                                </a>
                                                <!-- Edit -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-primary action-btn"
                                                        title="Edit"
                                                        onclick="openEditModal(
                                                            <?= $opt['id'] ?>,
                                                            '<?= addslashes($opt['group_name']) ?>',
                                                            '<?= addslashes($opt['option_name']) ?>',
                                                            '<?= addslashes($opt['description'] ?? '') ?>',
                                                            <?= $opt['price_modifier'] ?>,
                                                            <?= $opt['is_default'] ?>,
                                                            <?= $opt['is_available'] ?>,
                                                            <?= $opt['display_order'] ?>,
                                                            '<?= addslashes($opt['image'] ?? '') ?>'
                                                        )">
                                                    <i class="fas fa-edit" style="font-size:10px;"></i>
                                                </button>
                                                <!-- Delete -->
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-danger action-btn"
                                                        title="<?= $used > 0 ? "Used in $used products" : 'Delete' ?>"
                                                        <?= $used > 0 ? 'disabled' : '' ?>
                                                        data-id="<?= $opt['id'] ?>"
                                                        data-name="<?= htmlspecialchars($opt['option_name'], ENT_QUOTES) ?>"
                                                        <?= $used == 0 ? 'data-bs-toggle="modal" data-bs-target="#deleteModal"' : '' ?>>
                                                    <i class="fas fa-trash" style="font-size:10px;"></i>
                                                </button>
                                            </div>
                                            <!-- Set as default -->
                                            <?php if (!$opt['is_default']): ?>
                                                <a href="?set_default=<?= $opt['id'] ?>&group=<?= urlencode($group_name) ?>"
                                                   class="text-muted" style="font-size:10px;" title="Set as default">
                                                    Set default
                                                </a>
                                            <?php else: ?>
                                                <span style="font-size:10px;color:#e67e22;"><i class="fas fa-star"></i> Default</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>

                        <!-- ══════════════════════════════════════════
                             TABLE VIEW
                        ══════════════════════════════════════════ -->
                        <div id="tableView" style="display:none;">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle" id="optionsTable">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>#</th>
                                            <th>Preview</th>
                                            <th>Group</th>
                                            <th>Option Name</th>
                                            <th>Price Modifier</th>
                                            <th>Order</th>
                                            <th>Default</th>
                                            <th>Products</th>
                                            <th>Status</th>
                                            <th class="text-center">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php
                                    $sno = 1;
                                    foreach ($options_all as $group_name => $options):
                                        foreach ($options as $opt):
                                            $pm       = (float)$opt['price_modifier'];
                                            $pm_class = $pm > 0 ? 'text-success' : ($pm < 0 ? 'text-danger' : 'text-muted');
                                            $pm_text  = $pm > 0 ? '+₹'.number_format($pm,2) : ($pm < 0 ? '-₹'.number_format(abs($pm),2) : 'Included');
                                            $used     = $usage_map[$opt['id']] ?? 0;
                                    ?>
                                        <tr class="<?= !$opt['is_available'] ? 'table-secondary' : '' ?>">
                                            <td class="text-muted"><?= $sno++ ?></td>
                                            <td>
                                                <?php if (!empty($opt['image']) && file_exists("uploads/customization/".$opt['image'])): ?>
                                                    <img src="uploads/customization/<?= htmlspecialchars($opt['image']) ?>"
                                                         class="opt-img-thumb" alt="">
                                                <?php else: ?>
                                                    <div style="width:40px;height:40px;border-radius:6px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#ccc;">
                                                        <i class="fas fa-image"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?group=<?= urlencode($opt['group_name']) ?>"
                                                   class="badge bg-light text-dark border text-decoration-none" style="font-size:12px;">
                                                    <?= htmlspecialchars($opt['group_name']) ?>
                                                </a>
                                            </td>
                                            <td class="fw-semibold"><?= htmlspecialchars($opt['option_name']) ?></td>
                                            <td class="<?= $pm_class ?> fw-semibold"><?= $pm_text ?></td>
                                            <td class="text-muted"><?= (int)$opt['display_order'] ?></td>
                                            <td>
                                                <?php if ($opt['is_default']): ?>
                                                    <span class="badge bg-warning text-dark">
                                                        <i class="fas fa-star me-1"></i>Default
                                                    </span>
                                                <?php else: ?>
                                                    <a href="?set_default=<?= $opt['id'] ?>&group=<?= urlencode($active_group) ?>"
                                                       class="text-muted" style="font-size:11px;" title="Set as default">
                                                        Set
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($used > 0): ?>
                                                    <span class="badge bg-info text-dark"><?= $used ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted" style="font-size:11px;">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="?toggle=<?= $opt['id'] ?>&status=<?= $opt['is_available'] ?>&group=<?= urlencode($active_group) ?>"
                                                   class="badge text-decoration-none <?= $opt['is_available'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $opt['is_available'] ? 'Active' : 'Inactive' ?>
                                                </a>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <button class="btn btn-sm btn-outline-primary action-btn" title="Edit"
                                                            onclick="openEditModal(
                                                                <?= $opt['id'] ?>,
                                                                '<?= addslashes($opt['group_name']) ?>',
                                                                '<?= addslashes($opt['option_name']) ?>',
                                                                '<?= addslashes($opt['description'] ?? '') ?>',
                                                                <?= $opt['price_modifier'] ?>,
                                                                <?= $opt['is_default'] ?>,
                                                                <?= $opt['is_available'] ?>,
                                                                <?= $opt['display_order'] ?>,
                                                                '<?= addslashes($opt['image'] ?? '') ?>'
                                                            )">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger action-btn"
                                                            title="<?= $used > 0 ? "Used in $used products" : 'Delete' ?>"
                                                            <?= $used > 0 ? 'disabled' : '' ?>
                                                            data-id="<?= $opt['id'] ?>"
                                                            data-name="<?= htmlspecialchars($opt['option_name'], ENT_QUOTES) ?>"
                                                            <?= $used == 0 ? 'data-bs-toggle="modal" data-bs-target="#deleteModal"' : '' ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
                                        endforeach;
                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php endif; ?>
                    </div><!-- white_card_body -->
                </div><!-- white_card -->
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- ══════════════════════════════════════════════════════
         ADD OPTION MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="addOptionModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Add Customization Option
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Group selection -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Option Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_name" id="addGroupSelect"
                                        onchange="toggleNewGroup(this.value,'addNewGroupWrap')" required>
                                    <option value="">— Select Group —</option>
                                    <?php foreach ($all_groups as $g): ?>
                                        <option value="<?= htmlspecialchars($g['group_name']) ?>">
                                            <?= htmlspecialchars($g['group_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                    <option value="__new__">➕ Create New Group</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="addNewGroupWrap" style="display:none;">
                                <label class="form-label fw-semibold">New Group Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="new_group_name"
                                       placeholder="e.g. Chest Pocket, Pick Stitch">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Option Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="option_name"
                                       placeholder="e.g. Notch Lapel, Peak Lapel" required>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier"
                                       value="0" step="0.01"
                                       placeholder="0 = included, +500 = extra">
                                <small class="text-muted">Positive = extra charge</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order"
                                       value="0" min="0">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" name="description" rows="2"
                                          placeholder="Short description shown to customer..."></textarea>
                            </div>

                            <!-- Image upload -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Preview Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*,.svg"
                                       onchange="previewOptImg(this,'addImgPreview')">
                                <small class="text-muted">
                                    JPG, PNG, WEBP, SVG — max 2MB.<br>
                                    <strong>Tip:</strong> Use a line-drawing/diagram that clearly shows this style option.
                                    Recommended: 300×200px, transparent background PNG.
                                </small>
                                <div id="addImgPreview" class="mt-2"></div>
                            </div>

                            <!-- Flags -->
                            <div class="col-12">
                                <div class="d-flex gap-4 flex-wrap">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_default" id="addIsDefault">
                                        <label class="form-check-label fw-semibold" for="addIsDefault">
                                            <i class="fas fa-star text-warning me-1"></i>Set as Default
                                        </label>
                                        <div><small class="text-muted">Pre-selected in configurator</small></div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_available" id="addIsAvailable" checked>
                                        <label class="form-check-label fw-semibold" for="addIsAvailable">Active / Available</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_option" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Option
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         EDIT OPTION MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editOptionModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="existing_image" id="edit_existing_image">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2 text-warning"></i>Edit Customization Option
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Option Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_name" id="edit_group_name" required>
                                    <?php foreach ($all_groups as $g): ?>
                                        <option value="<?= htmlspecialchars($g['group_name']) ?>">
                                            <?= htmlspecialchars($g['group_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Option Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="option_name" id="edit_option_name" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier"
                                       id="edit_price_modifier" step="0.01">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order"
                                       id="edit_display_order" min="0">
                            </div>

                            <div class="col-md-4 d-flex align-items-end pb-1">
                                <div>
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="is_default" id="edit_is_default">
                                        <label class="form-check-label" for="edit_is_default">
                                            <i class="fas fa-star text-warning me-1"></i>Set as Default
                                        </label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_available" id="edit_is_available">
                                        <label class="form-check-label" for="edit_is_available">Active</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            </div>

                            <!-- Current image + replace -->
                            <div class="col-12">
                                <label class="form-label fw-semibold">Preview Image</label>
                                <div id="editCurrentImgWrap" class="mb-2" style="display:none;">
                                    <img id="editCurrentImg" src="" class="img-upload-preview" alt="Current">
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_image" id="editRemoveImg"
                                               onchange="document.getElementById('editCurrentImgWrap').style.opacity = this.checked ? '0.3' : '1'">
                                        <label class="form-check-label text-danger" for="editRemoveImg">Remove current image</label>
                                    </div>
                                </div>
                                <input type="file" class="form-control" name="image" accept="image/*,.svg"
                                       onchange="previewOptImg(this,'editImgPreview')">
                                <small class="text-muted">Leave blank to keep current image. JPG, PNG, WEBP, SVG — max 2MB.</small>
                                <div id="editImgPreview" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_option" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Update Option
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
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:60px;height:60px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-trash-alt fa-xl text-danger"></i>
                    </div>
                    <h5>Delete Option?</h5>
                    <p class="text-muted">
                        Are you sure you want to delete <strong id="deleteOptionName"></strong>?
                        <br><small class="text-danger">This cannot be undone.</small>
                    </p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i> Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── View toggle ──────────────────────────────────────────
    function switchView(view) {
        const isCard = view === 'card';
        document.getElementById('cardView').style.display  = isCard ? '' : 'none';
        document.getElementById('tableView').style.display = isCard ? 'none' : '';
        document.getElementById('btnCardView').classList.toggle('active', isCard);
        document.getElementById('btnTableView').classList.toggle('active', !isCard);
        localStorage.setItem('custOptView', view);
    }
    document.addEventListener('DOMContentLoaded', () => {
        const saved = localStorage.getItem('custOptView');
        if (saved === 'table') switchView('table');
    });

    // ── Show/hide new group name input ───────────────────────
    function toggleNewGroup(val, wrapId) {
        document.getElementById(wrapId).style.display = val === '__new__' ? '' : 'none';
    }

    // ── Open add modal pre-filled with group ─────────────────
    function openAddModal(groupName) {
        const sel = document.getElementById('addGroupSelect');
        for (let o of sel.options) {
            if (o.value === groupName) { o.selected = true; break; }
        }
        new bootstrap.Modal(document.getElementById('addOptionModal')).show();
    }

    // ── Open edit modal ──────────────────────────────────────
    function openEditModal(id, group, optName, desc, price, isDef, isAvail, order, image) {
        document.getElementById('edit_id').value             = id;
        document.getElementById('edit_option_name').value   = optName;
        document.getElementById('edit_description').value   = desc;
        document.getElementById('edit_price_modifier').value = price;
        document.getElementById('edit_display_order').value  = order;
        document.getElementById('edit_is_default').checked   = isDef == 1;
        document.getElementById('edit_is_available').checked = isAvail == 1;
        document.getElementById('edit_existing_image').value = image;
        document.getElementById('editRemoveImg').checked     = false;

        // Set group select
        const sel = document.getElementById('edit_group_name');
        for (let o of sel.options) {
            if (o.value === group) { o.selected = true; break; }
        }

        // Show current image
        const wrap = document.getElementById('editCurrentImgWrap');
        const img  = document.getElementById('editCurrentImg');
        if (image) {
            img.src = 'uploads/customization/' + image;
            wrap.style.display = '';
            wrap.style.opacity = '1';
        } else {
            wrap.style.display = 'none';
        }
        document.getElementById('editImgPreview').innerHTML = '';

        new bootstrap.Modal(document.getElementById('editOptionModal')).show();
    }

    // ── Image preview ────────────────────────────────────────
    function previewOptImg(input, targetId) {
        const target = document.getElementById(targetId);
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = e => {
                target.innerHTML = `<img src="${e.target.result}" class="img-upload-preview" alt="Preview">
                                    <small class="text-muted ms-2">New image</small>`;
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // ── Delete modal ─────────────────────────────────────────
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('deleteOptionName').textContent = btn.getAttribute('data-name');
        document.getElementById('confirmDeleteBtn').href =
            'customization-options.php?delete=' + btn.getAttribute('data-id') +
            '<?= $active_group ? "&group=".urlencode($active_group) : "" ?>';
    });
    </script>
</body>
</html>