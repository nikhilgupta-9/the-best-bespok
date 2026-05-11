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

function deleteOptImg($f)
{
    if ($f && file_exists("uploads/customization/$f")) unlink("uploads/customization/$f");
}

function uploadOptImg($key, &$err)
{
    if (empty($_FILES[$key]['name'])) return '';
    $ext = strtolower(pathinfo($_FILES[$key]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'])) {
        $err = "Invalid type. Use JPG,PNG,WEBP,SVG.";
        return false;
    }
    if ($_FILES[$key]['size'] > 3000000) {
        $err = "Max 3MB.";
        return false;
    }
    $f = time() . '_' . uniqid() . '.' . $ext;
    if (!move_uploaded_file($_FILES[$key]['tmp_name'], "uploads/customization/$f")) {
        $err = "Upload failed.";
        return false;
    }
    return $f;
}

// Auto-sync configurator_steps when group changes
function syncStep($conn, $grp, $action = 'ensure')
{
    if ($action === 'ensure') {
        $c = $conn->prepare("SELECT id FROM configurator_steps WHERE group_name=? LIMIT 1");
        $c->bind_param("s", $grp);
        $c->execute();
        $exists = $c->get_result()->fetch_assoc();
        $c->close();
        if (!$exists) {
            $m = $conn->query("SELECT IFNULL(MAX(step_order),0)+1 FROM configurator_steps")->fetch_row()[0];
            $i = $conn->prepare("INSERT INTO configurator_steps (group_name,step_label,step_order,applies_to,is_required,is_visible) VALUES (?,?,?,'all',0,1)");
            $i->bind_param("ssi", $grp, $grp, $m);
            $i->execute();
            $i->close();
        }
    } elseif ($action === 'remove') {
        $c = $conn->prepare("SELECT COUNT(*) FROM customization_options WHERE group_name=?");
        $c->bind_param("s", $grp);
        $c->execute();
        $n = $c->get_result()->fetch_row()[0];
        $c->close();
        if ($n == 0) {
            $d = $conn->prepare("DELETE FROM configurator_steps WHERE group_name=?");
            $d->bind_param("s", $grp);
            $d->execute();
            $d->close();
        }
    }
}

// AJAX reorder
if (isset($_POST['action']) && $_POST['action'] === 'reorder') {
    header('Content-Type: application/json');
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (is_array($order)) {
        $s = $conn->prepare("UPDATE customization_options SET display_order=? WHERE id=?");
        foreach ($order as $pos => $id) {
            $p = $pos + 1;
            $oid = (int)$id;
            $s->bind_param("ii", $p, $oid);
            $s->execute();
        }
        $s->close();
        echo json_encode(['success' => true]);
    } else echo json_encode(['success' => false]);
    exit();
}

// Toggle
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $new = (int)$_GET['status'] == 1 ? 0 : 1;
    $s = $conn->prepare("UPDATE customization_options SET is_available=? WHERE id=?");
    $s->bind_param("ii", $new, $id);
    $s->execute();
    $s->close();
    header("Location: customization-options.php" . (isset($_GET['group']) ? "?group=" . urlencode($_GET['group']) : ''));
    exit();
}

// Set default
if (isset($_GET['set_default'])) {
    $id = (int)$_GET['set_default'];
    $q = $conn->prepare("SELECT group_name FROM customization_options WHERE id=? LIMIT 1");
    $q->bind_param("i", $id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    if ($row) {
        $g = $row['group_name'];
        $c1 = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=?");
        $c1->bind_param("s", $g);
        $c1->execute();
        $c1->close();
        $c2 = $conn->prepare("UPDATE customization_options SET is_default=1 WHERE id=?");
        $c2->bind_param("i", $id);
        $c2->execute();
        $c2->close();
        $_SESSION['success'] = "Default updated.";
        header("Location: customization-options.php?group=" . urlencode($g));
        exit();
    }
}

// DELETE single option
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $r = $conn->prepare("SELECT image,group_name FROM customization_options WHERE id=? LIMIT 1");
    $r->bind_param("i", $id);
    $r->execute();
    $data = $r->get_result()->fetch_assoc();
    $r->close();
    $c = $conn->prepare("SELECT COUNT(*) FROM product_customization_map WHERE customization_option_id=?");
    $c->bind_param("i", $id);
    $c->execute();
    $used = $c->get_result()->fetch_row()[0];
    $c->close();
    if ($used > 0) {
        $_SESSION['error'] = "Cannot delete — used in $used product(s).";
    } else {
        if (!empty($data['image'])) deleteOptImg($data['image']);
        $d = $conn->prepare("DELETE FROM customization_options WHERE id=?");
        $d->bind_param("i", $id);
        if ($d->execute()) {
            $_SESSION['success'] = "Option deleted.";
            if ($data) syncStep($conn, $data['group_name'], 'remove');
        } else $_SESSION['error'] = "Delete failed.";
        $d->close();
    }
    header("Location: customization-options.php" . ($data ? "?group=" . urlencode($data['group_name']) : ''));
    exit();
}

// DELETE entire group
if (isset($_GET['delete_group'])) {
    $grp = trim($_GET['delete_group']);
    $c = $conn->prepare("SELECT COUNT(*) FROM product_customization_map pcm INNER JOIN customization_options co ON co.id=pcm.customization_option_id WHERE co.group_name=?");
    $c->bind_param("s", $grp);
    $c->execute();
    $used = $c->get_result()->fetch_row()[0];
    $c->close();
    if ($used > 0) {
        $_SESSION['error'] = "Cannot delete — $used option(s) assigned to products.";
    } else {
        $ir = $conn->prepare("SELECT image FROM customization_options WHERE group_name=? AND image IS NOT NULL AND image!=''");
        $ir->bind_param("s", $grp);
        $ir->execute();
        $ires = $ir->get_result();
        while ($img = $ires->fetch_assoc()) deleteOptImg($img['image']);
        $ir->close();
        $d = $conn->prepare("DELETE FROM customization_options WHERE group_name=?");
        $d->bind_param("s", $grp);
        if ($d->execute()) {
            $ds = $conn->prepare("DELETE FROM configurator_steps WHERE group_name=?");
            $ds->bind_param("s", $grp);
            $ds->execute();
            $ds->close();
            $_SESSION['success'] = "Group \"$grp\" deleted.";
        } else $_SESSION['error'] = "Delete failed.";
        $d->close();
    }
    header("Location: customization-options.php");
    exit();
}

// ADD option
if (isset($_POST['add_option'])) {
    $grp = trim($_POST['group_name']);
    $newgrp = trim($_POST['new_group_name'] ?? '');
    if ($grp === '__new__' && $newgrp !== '') $grp = $newgrp;
    if (!$grp) {
        $_SESSION['error'] = "Group required.";
        header("Location: customization-options.php");
        exit();
    }
    $name = trim($_POST['option_name']);
    $desc = trim($_POST['description'] ?? '');
    $pm = (float)($_POST['price_modifier'] ?? 0);
    $isDef = isset($_POST['is_default']) ? 1 : 0;
    $isAvail = isset($_POST['is_available']) ? 1 : 0;
    $mq = $conn->prepare("SELECT IFNULL(MAX(display_order),0)+1 FROM customization_options WHERE group_name=?");
    $mq->bind_param("s", $grp);
    $mq->execute();
    $ord = (int)$mq->get_result()->fetch_row()[0];
    $mq->close();
    $err = '';
    $img = uploadOptImg('image', $err);
    if ($img === false) {
        $_SESSION['error'] = $err;
        header("Location: customization-options.php");
        exit();
    }
    if ($isDef) {
        $c = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=?");
        $c->bind_param("s", $grp);
        $c->execute();
        $c->close();
    }
    $s = $conn->prepare("INSERT INTO customization_options (group_name,option_name,description,image,price_modifier,is_default,is_available,display_order) VALUES (?,?,?,?,?,?,?,?)");
    $s->bind_param("ssssdiii", $grp, $name, $desc, $img, $pm, $isDef, $isAvail, $ord);
    if ($s->execute()) {
        syncStep($conn, $grp, 'ensure');
        $_SESSION['success'] = "Option \"$name\" added!";
    } else $_SESSION['error'] = "Error: " . $conn->error;
    $s->close();
    header("Location: customization-options.php?group=" . urlencode($grp));
    exit();
}

// EDIT option
if (isset($_POST['edit_option'])) {
    $id = (int)$_POST['id'];
    $grp = trim($_POST['group_name']);
    $name = trim($_POST['option_name']);
    $desc = trim($_POST['description'] ?? '');
    $pm = (float)($_POST['price_modifier'] ?? 0);
    $isDef = isset($_POST['is_default']) ? 1 : 0;
    $isAvail = isset($_POST['is_available']) ? 1 : 0;
    $ord = (int)($_POST['display_order'] ?? 0);
    $exImg = trim($_POST['existing_image'] ?? '');
    $rmImg = isset($_POST['remove_image']);
    $img = $exImg;
    if ($rmImg) {
        deleteOptImg($exImg);
        $img = '';
    }
    $err = '';
    $newImg = uploadOptImg('image', $err);
    if ($newImg === false) {
        $_SESSION['error'] = $err;
        header("Location: customization-options.php");
        exit();
    }
    if ($newImg) {
        if ($exImg) deleteOptImg($exImg);
        $img = $newImg;
    }
    if ($isDef) {
        $c = $conn->prepare("UPDATE customization_options SET is_default=0 WHERE group_name=? AND id!=?");
        $c->bind_param("si", $grp, $id);
        $c->execute();
        $c->close();
    }
    $s = $conn->prepare("UPDATE customization_options SET group_name=?,option_name=?,description=?,image=?,price_modifier=?,is_default=?,is_available=?,display_order=? WHERE id=?");
    $s->bind_param("ssssdiiii", $grp, $name, $desc, $img, $pm, $isDef, $isAvail, $ord, $id);
    if ($s->execute()) {
        syncStep($conn, $grp, 'ensure');
        $_SESSION['success'] = "Option updated!";
    } else $_SESSION['error'] = "Error: " . $conn->error;
    $s->close();
    header("Location: customization-options.php?group=" . urlencode($grp));
    exit();
}

// FETCH
$active_group = trim($_GET['group'] ?? '');
$groups_res = $conn->query("SELECT group_name,COUNT(*) AS total,SUM(is_available) AS active_count,SUM(image IS NOT NULL AND image!='') AS with_image FROM customization_options GROUP BY group_name ORDER BY MIN(display_order) ASC,group_name ASC");
$all_groups = [];
while ($g = $groups_res->fetch_assoc()) $all_groups[] = $g;
if ($active_group) {
    $st = $conn->prepare("SELECT * FROM customization_options WHERE group_name=? ORDER BY display_order ASC,id ASC");
    $st->bind_param("s", $active_group);
    $st->execute();
    $opt_res = $st->get_result();
    $st->close();
} else {
    $opt_res = $conn->query("SELECT * FROM customization_options ORDER BY group_name ASC,display_order ASC");
}
$options_all = [];
while ($o = $opt_res->fetch_assoc()) $options_all[$o['group_name']][] = $o;
$usage_map = [];
$uq = $conn->query("SELECT customization_option_id,COUNT(*) AS cnt FROM product_customization_map GROUP BY customization_option_id");
if ($uq) while ($u = $uq->fetch_assoc()) $usage_map[$u['customization_option_id']] = $u['cnt'];
$total_opts = array_sum(array_column($all_groups, 'total'));
$total_imgs = array_sum(array_column($all_groups, 'with_image'));
$cfg_visible = $conn->query("SELECT COUNT(*) FROM configurator_steps WHERE is_visible=1")->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1,shrink-to-fit=no" />
    <title>Customization Options | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <style>
        .gtabs {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 14px 0 0
        }

        .gtab {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 30px;
            border: 1.5px solid #dee2e6;
            background: #fff;
            cursor: pointer;
            text-decoration: none;
            color: #495057;
            font-size: 12px;
            font-weight: 500;
            transition: all .17s
        }

        .gtab:hover {
            border-color: #6c63ff;
            color: #6c63ff;
            background: #f5f4ff
        }

        .gtab.active {
            background: #2c3e50;
            border-color: #2c3e50;
            color: #fff
        }

        .gtab .cnt {
            font-size: 10px;
            padding: 1px 6px;
            border-radius: 20px;
            background: rgba(255, 255, 255, .2)
        }

        .gtab:not(.active) .cnt {
            background: #f0f0f0;
            color: #666
        }

        .gtab.warn {
            border-color: #f39c12 !important
        }

        .gtab.warn:not(.active) {
            color: #f39c12
        }

        .opt-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            transition: box-shadow .18s, transform .18s;
            cursor: grab;
            position: relative
        }

        .opt-card:hover {
            box-shadow: 0 4px 18px rgba(0, 0, 0, .09);
            transform: translateY(-2px)
        }

        .opt-card.inactive {
            opacity: .55
        }

        .opt-card.sortable-ghost {
            opacity: .3;
            border: 2px dashed #6c63ff
        }

        .opt-card.sortable-chosen {
            box-shadow: 0 6px 20px rgba(0, 0, 0, .15);
            cursor: grabbing
        }

        .opt-img {
            width: 100%;
            height: 110px;
            object-fit: contain;
            padding: 6px;
            background: #f8f9fa;
            border-bottom: 1px solid #f0f0f0;
            display: block
        }

        .opt-noimg {
            width: 100%;
            height: 110px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            display: flex;
            align-items: center;
            justify-content: center;
            border-bottom: 1px solid #f0f0f0;
            color: #adb5bd;
            font-size: 2rem
        }

        .img-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 9px;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 600;
            color: #fff
        }

        .img-ok {
            background: rgba(39, 174, 96, .85)
        }

        .img-no {
            background: rgba(231, 76, 60, .85)
        }

        .opt-body {
            padding: 10px 12px
        }

        .opt-name {
            font-size: 13px;
            font-weight: 600;
            margin: 0 0 3px;
            color: #2c3e50
        }

        .opt-grp-lbl {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #6c757d;
            margin-bottom: 5px
        }

        .opt-price {
            font-size: 12px;
            font-weight: 600
        }

        .pp {
            color: #27ae60
        }

        .pm {
            color: #e74c3c
        }

        .pz {
            color: #6c757d
        }

        .opt-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 7px 12px;
            border-top: 1px solid #f0f0f0;
            background: #fafafa
        }

        .ab {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            font-size: 11px
        }

        .grp-hdr {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 0 6px;
            border-bottom: 2px solid #2c3e50;
            margin-bottom: 12px;
            flex-wrap: wrap;
            gap: 8px
        }

        .grp-ttl {
            font-size: 15px;
            font-weight: 600;
            color: #2c3e50;
            margin: 0
        }

        .mini-stat {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 10px 14px;
            text-align: center;
            border: 1px solid #e9ecef
        }

        .msv {
            font-size: 20px;
            font-weight: 700;
            color: #2c3e50
        }

        .msl {
            font-size: 11px;
            color: #6c757d;
            margin-top: 1px
        }

        .cov-bar {
            height: 3px;
            background: #e9ecef;
            border-radius: 2px;
            margin-top: 3px
        }

        .cov-fill {
            height: 100%;
            background: #27ae60;
            border-radius: 2px
        }

        .vt .btn.active {
            background: #2c3e50;
            color: #fff;
            border-color: #2c3e50
        }

        .opt-thumb {
            width: 38px;
            height: 38px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee
        }

        .drag-hint {
            font-size: 11px;
            color: #aaa;
            display: flex;
            align-items: center;
            gap: 4px;
            margin-top: -6px;
            margin-bottom: 8px
        }

        .img-up-prev {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #dee2e6
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
                    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']);
                                                                                                                            unset($_SESSION['success']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif;
                if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']);
                                                                                                                                unset($_SESSION['error']); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>

                <!-- Stats -->
                <div class="row g-3 mb-3">
                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <div class="msv"><?= count($all_groups) ?></div>
                            <div class="msl">Option Groups</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <div class="msv"><?= $total_opts ?></div>
                            <div class="msl">Total Options</div>
                        </div>
                    </div>
                    <?php
                    $percentage = ($total_opts > 0) ? round(($total_imgs / $total_opts) * 100) : 0;
                    $statusClass = ($total_imgs < $total_opts) ? 'text-warning' : 'text-success';
                    ?>

                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <div class="msv <?= $statusClass ?>">
                                <?= $total_imgs ?>/<?= $total_opts ?>
                            </div>
                            <div class="msl">Have Images</div>
                            <div class="cov-bar">
                                <div class="cov-fill" style="width:<?= $percentage ?>%"></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-6 col-md-3">
                        <div class="mini-stat">
                            <div class="msv text-primary"><?= $cfg_visible ?></div>
                            <div class="msl">
                                Active Configurator Steps ·
                                <a href="configurator-steps.php" style="font-size:10px">Manage</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="white_card mb_30">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                            <div>
                                <h2 class="mb-0 fw-bold">Customization Options</h2>
                                <p class="text-muted small mb-0"><?= count($all_groups) ?> groups · <?= $total_opts ?> options · Images: <?= $total_imgs ?>/<?= $total_opts ?> · <a href="configurator-steps.php" class="text-primary">Manage step order ↗</a></p>
                            </div>
                            <div class="d-flex gap-2 align-items-center flex-wrap">
                                <div class="btn-group vt">
                                    <button class="btn btn-sm btn-outline-secondary active" id="btnCard" onclick="switchView('card')"><i class="fas fa-th-large"></i> Cards</button>
                                    <button class="btn btn-sm btn-outline-secondary" id="btnTbl" onclick="switchView('table')"><i class="fas fa-list"></i> Table</button>
                                </div>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i> Add Option</button>
                            </div>
                        </div>
                        <!-- Group tabs -->
                        <div class="gtabs">
                            <a href="customization-options.php" class="gtab <?= !$active_group ? 'active' : '' ?>"><i class="fas fa-layer-group"></i> All <span class="cnt"><?= $total_opts ?></span></a>
                            <?php foreach ($all_groups as $g): $warn = (int)$g['with_image'] < (int)$g['total']; ?>
                                <a href="?group=<?= urlencode($g['group_name']) ?>" class="gtab <?= $active_group === $g['group_name'] ? 'active' : '' ?> <?= $warn && $active_group !== $g['group_name'] ? 'warn' : '' ?>" title="<?= $warn ? 'Missing images' : '' ?>">
                                    <?= htmlspecialchars($g['group_name']) ?>
                                    <span class="cnt"><?= $g['total'] ?></span>
                                    <?php if ($warn): ?><i class="fas fa-exclamation-triangle" style="font-size:9px"></i><?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="white_card_body">
                        <?php if (empty($options_all)): ?>
                            <div class="text-center py-5 text-muted"><i class="fas fa-sliders-h fa-3x mb-3 d-block opacity-25"></i>
                                <h5>No options found</h5><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fas fa-plus me-1"></i> Add First Option</button>
                            </div>
                        <?php else: ?>

                            <!-- CARD VIEW -->
                            <div id="cardView">
                                <?php foreach ($options_all as $grp_name => $opts):
                                    $grpImgCnt = count(array_filter($opts, fn($o) => !empty($o['image'])));
                                    $grpUsed = array_sum(array_map(fn($o) => $usage_map[$o['id']] ?? 0, $opts));
                                ?>
                                    <div class="grp-hdr">
                                        <div class="d-flex align-items-center gap-2 flex-wrap">
                                            <h5 class="grp-ttl"><i class="fas fa-tag me-1 text-muted" style="font-size:11px"></i><?= htmlspecialchars($grp_name) ?> <span class="badge bg-secondary ms-1" style="font-size:11px"><?= count($opts) ?></span></h5>
                                            <?php if ($grpImgCnt < count($opts)): ?><span class="badge bg-warning text-dark" style="font-size:10px"><i class="fas fa-image me-1"></i><?= count($opts) - $grpImgCnt ?> missing images</span><?php endif; ?>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button class="btn btn-sm btn-outline-primary" onclick="openAddModal('<?= addslashes($grp_name) ?>')"><i class="fas fa-plus me-1"></i> Add</button>
                                            <a href="manage-variant-images.php?product_id=1&tab=options" class="btn btn-sm btn-outline-secondary"><i class="fas fa-images me-1"></i> Images</a>
                                            <button class="btn btn-sm btn-outline-danger" <?= $grpUsed > 0 ? 'disabled title="Used in products"' : '' ?> <?= $grpUsed == 0 ? 'data-bs-toggle="modal" data-bs-target="#delGrpModal"' : '' ?> data-grp="<?= htmlspecialchars($grp_name, ENT_QUOTES) ?>"><i class="fas fa-trash me-1"></i> Delete Group</button>
                                        </div>
                                    </div>
                                    <?php if ($active_group): ?><div class="drag-hint"><i class="fas fa-grip-vertical"></i> Drag to reorder — saves automatically</div><?php endif; ?>
                                    <div class="row g-3 mb-4 sortable-group" data-group="<?= htmlspecialchars($grp_name, ENT_QUOTES) ?>">
                                        <?php foreach ($opts as $opt):
                                            $pm = (float)$opt['price_modifier'];
                                            $pmc = $pm > 0 ? 'pp' : ($pm < 0 ? 'pm' : 'pz');
                                            $pmt = $pm > 0 ? '+₹' . number_format($pm, 0) : ($pm < 0 ? '-₹' . number_format(abs($pm), 0) : 'Included');
                                            $used = $usage_map[$opt['id']] ?? 0;
                                            $hasImg = !empty($opt['image']) && file_exists("uploads/customization/" . $opt['image']);
                                        ?>
                                            <div class="col-6 col-sm-4 col-md-3 col-xl-2" data-id="<?= $opt['id'] ?>">
                                                <div class="opt-card <?= !$opt['is_available'] ? 'inactive' : '' ?>">
                                                    <span class="img-badge <?= $hasImg ? 'img-ok' : 'img-no' ?>"><?= $hasImg ? 'IMG ✓' : 'NO IMG' ?></span>
                                                    <?php if ($hasImg): ?><img src="uploads/customization/<?= htmlspecialchars($opt['image']) ?>" class="opt-img" alt="">
                                                    <?php else: ?><div class="opt-noimg"><i class="fas fa-image"></i></div><?php endif; ?>
                                                    <div class="opt-body">
                                                        <?php if (!$active_group): ?><div class="opt-grp-lbl"><?= htmlspecialchars($grp_name) ?></div><?php endif; ?>
                                                        <div class="opt-name"><?= htmlspecialchars($opt['option_name']) ?></div>
                                                        <div class="d-flex align-items-center justify-content-between mt-1">
                                                            <span class="opt-price <?= $pmc ?>"><?= $pmt ?></span>
                                                            <?php if ($opt['is_default']): ?><span class="badge bg-warning text-dark" style="font-size:9px">Default</span><?php endif; ?>
                                                        </div>
                                                        <?php if (!$opt['is_available']): ?><span class="badge bg-secondary mt-1" style="font-size:9px">Hidden</span><?php endif; ?>
                                                    </div>
                                                    <div class="opt-footer">
                                                        <div class="d-flex gap-1">
                                                            <a href="?toggle=<?= $opt['id'] ?>&status=<?= $opt['is_available'] ?>&group=<?= urlencode($grp_name) ?>" class="btn btn-sm <?= $opt['is_available'] ? 'btn-outline-success' : 'btn-outline-secondary' ?> ab" title="<?= $opt['is_available'] ? 'Deactivate' : 'Activate' ?>"><i class="fas fa-power-off" style="font-size:10px"></i></a>
                                                            <button type="button" class="btn btn-sm btn-outline-primary ab" onclick="openEditModal(<?= $opt['id'] ?>,'<?= addslashes($opt['group_name']) ?>','<?= addslashes($opt['option_name']) ?>','<?= addslashes($opt['description'] ?? '') ?>',<?= $opt['price_modifier'] ?>,<?= $opt['is_default'] ?>,<?= $opt['is_available'] ?>,<?= $opt['display_order'] ?>,'<?= addslashes($opt['image'] ?? '') ?>')"><i class="fas fa-edit" style="font-size:10px"></i></button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger ab" <?= $used > 0 ? 'disabled title="Used in ' . ($used) . ' product(s)"' : '' ?> data-id="<?= $opt['id'] ?>" data-name="<?= htmlspecialchars($opt['option_name'], ENT_QUOTES) ?>" data-group="<?= htmlspecialchars($grp_name, ENT_QUOTES) ?>" <?= $used == 0 ? 'data-bs-toggle="modal" data-bs-target="#delOptModal"' : '' ?>><i class="fas fa-trash" style="font-size:10px"></i></button>
                                                        </div>
                                                        <?php if (!$opt['is_default']): ?><a href="?set_default=<?= $opt['id'] ?>&group=<?= urlencode($grp_name) ?>" style="font-size:10px;color:#aaa">Default</a>
                                                        <?php else: ?><span style="font-size:10px;color:#e67e22"><i class="fas fa-star"></i></span><?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div><!-- sortable-group -->
                                <?php endforeach; ?>
                            </div><!-- cardView -->

                            <!-- TABLE VIEW -->
                            <div id="tableView" style="display:none">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Img</th>
                                                <th>Group</th>
                                                <th>Option Name</th>
                                                <th>Price</th>
                                                <th>Order</th>
                                                <th>Default</th>
                                                <th>Used</th>
                                                <th>Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $sno = 1;
                                            foreach ($options_all as $grp_name => $opts): foreach ($opts as $opt):
                                                    $pm = (float)$opt['price_modifier'];
                                                    $pmc = $pm > 0 ? 'text-success' : ($pm < 0 ? 'text-danger' : 'text-muted');
                                                    $pmt = $pm > 0 ? '+₹' . number_format($pm, 0) : ($pm < 0 ? '-₹' . number_format(abs($pm), 0) : '—');
                                                    $used = $usage_map[$opt['id']] ?? 0;
                                                    $hasImg = !empty($opt['image']) && file_exists("uploads/customization/" . $opt['image']);
                                            ?>
                                                    <tr class="<?= !$opt['is_available'] ? 'table-secondary' : '' ?>">
                                                        <td class="text-muted" style="font-size:11px"><?= $sno++ ?></td>
                                                        <td><?php if ($hasImg): ?><img src="uploads/customization/<?= htmlspecialchars($opt['image']) ?>" class="opt-thumb" alt="">
                                                            <?php else: ?><div style="width:38px;height:38px;border-radius:6px;background:#fde8e8;display:flex;align-items:center;justify-content:center;color:#e74c3c;font-size:10px;font-weight:700">✗</div><?php endif; ?></td>
                                                        <td><a href="?group=<?= urlencode($opt['group_name']) ?>" class="badge bg-light text-dark border text-decoration-none"><?= htmlspecialchars($opt['group_name']) ?></a></td>
                                                        <td class="fw-semibold" style="font-size:13px"><?= htmlspecialchars($opt['option_name']) ?></td>
                                                        <td class="<?= $pmc ?> fw-semibold"><?= $pmt ?></td>
                                                        <td class="text-muted"><?= $opt['display_order'] ?></td>
                                                        <td><?php if ($opt['is_default']): ?><span class="badge bg-warning text-dark"><i class="fas fa-star me-1"></i>Yes</span><?php else: ?><a href="?set_default=<?= $opt['id'] ?>&group=<?= urlencode($active_group) ?>" class="text-muted" style="font-size:11px">Set</a><?php endif; ?></td>
                                                        <td><?php if ($used > 0): ?><span class="badge bg-info text-dark"><?= $used ?></span><?php else: ?><span class="text-muted">—</span><?php endif; ?></td>
                                                        <td><a href="?toggle=<?= $opt['id'] ?>&status=<?= $opt['is_available'] ?>&group=<?= urlencode($active_group) ?>" class="badge text-decoration-none <?= $opt['is_available'] ? 'bg-success' : 'bg-secondary' ?>"><?= $opt['is_available'] ? 'Active' : 'Hidden' ?></a></td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <button class="btn btn-sm btn-outline-primary ab" onclick="openEditModal(<?= $opt['id'] ?>,'<?= addslashes($opt['group_name']) ?>','<?= addslashes($opt['option_name']) ?>','<?= addslashes($opt['description'] ?? '') ?>',<?= $opt['price_modifier'] ?>,<?= $opt['is_default'] ?>,<?= $opt['is_available'] ?>,<?= $opt['display_order'] ?>,'<?= addslashes($opt['image'] ?? '') ?>')"><i class="fas fa-edit"></i></button>
                                                                <button class="btn btn-sm btn-outline-danger ab" <?= $used > 0 ? 'disabled' : '' ?> data-id="<?= $opt['id'] ?>" data-name="<?= htmlspecialchars($opt['option_name'], ENT_QUOTES) ?>" data-group="<?= htmlspecialchars($grp_name, ENT_QUOTES) ?>" <?= $used == 0 ? 'data-bs-toggle="modal" data-bs-target="#delOptModal"' : '' ?>><i class="fas fa-trash"></i></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                            <?php endforeach;
                                            endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div><!-- tableView -->
                        <?php endif; ?>
                    </div><!-- white_card_body -->
                </div><!-- white_card -->
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>
    <!-- ADD MODAL -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true" style="background:#14141491">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add Option</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_name" id="aGrpSel" onchange="toggleNewGrp(this.value)" required>
                                    <option value="">— Select —</option>
                                    <?php foreach ($all_groups as $g): ?><option value="<?= htmlspecialchars($g['group_name']) ?>"><?= htmlspecialchars($g['group_name']) ?></option><?php endforeach; ?>
                                    <option value="__new__">➕ Create New Group</option>
                                </select>
                            </div>
                            <div class="col-md-6" id="newGrpW" style="display:none"><label class="form-label fw-semibold">New Group Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="new_group_name" placeholder="e.g. Back Button"></div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Option Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="option_name" placeholder="e.g. Notch Lapel" required></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Price Modifier (₹)</label><input type="number" class="form-control" name="price_modifier" value="0" step="0.01"><small class="text-muted">0 = included</small></div>
                            <div class="col-md-3"><label class="form-label fw-semibold">Display Order</label><input type="number" class="form-control" name="display_order" value="0" min="0"></div>
                            <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea class="form-control" name="description" rows="2" placeholder="Shown to customer on hover..."></textarea></div>
                            <div class="col-12"><label class="form-label fw-semibold">Option Image <span class="text-danger">*</span> <small class="text-muted fw-normal">— Line drawing/sketch, transparent PNG, 300×300px</small></label>
                                <input type="file" class="form-control" name="image" accept="image/*,.svg" onchange="prevImg(this,'aPrev')">
                                <div id="aPrev" class="mt-2"></div>
                            </div>
                            <div class="col-12">
                                <div class="d-flex gap-4 flex-wrap">
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_default" id="aDef"><label class="form-check-label fw-semibold" for="aDef"><i class="fas fa-star text-warning me-1"></i>Set as Default</label></div>
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" id="aAvail" checked><label class="form-check-label fw-semibold" for="aAvail">Active / Visible</label></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="add_option" class="btn btn-primary"><i class="fas fa-save me-1"></i> Save Option</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- EDIT MODAL -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true" style="background:#14141491">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="eId"><input type="hidden" name="existing_image" id="eExImg">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Edit Option</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label fw-semibold">Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_name" id="eGrp" required><?php foreach ($all_groups as $g): ?><option value="<?= htmlspecialchars($g['group_name']) ?>"><?= htmlspecialchars($g['group_name']) ?></option><?php endforeach; ?></select>
                            </div>
                            <div class="col-md-6"><label class="form-label fw-semibold">Option Name <span class="text-danger">*</span></label><input type="text" class="form-control" name="option_name" id="eName" required></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Price Modifier (₹)</label><input type="number" class="form-control" name="price_modifier" id="ePrice" step="0.01"></div>
                            <div class="col-md-4"><label class="form-label fw-semibold">Display Order</label><input type="number" class="form-control" name="display_order" id="eOrd" min="0"></div>
                            <div class="col-md-4 d-flex align-items-end pb-1">
                                <div>
                                    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="is_default" id="eDef"><label class="form-check-label" for="eDef"><i class="fas fa-star text-warning me-1"></i>Default</label></div>
                                    <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_available" id="eAvail"><label class="form-check-label" for="eAvail">Active</label></div>
                                </div>
                            </div>
                            <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea class="form-control" name="description" id="eDesc" rows="2"></textarea></div>
                            <div class="col-12"><label class="form-label fw-semibold">Option Image</label>
                                <div id="eCurW" class="mb-2" style="display:none"><img id="eCurImg" src="" class="img-up-prev" alt="">
                                    <div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="remove_image" id="eRmImg" onchange="document.getElementById('eCurW').style.opacity=this.checked?'.3':'1'"><label class="form-check-label text-danger" for="eRmImg">Remove current image</label></div>
                                </div>
                                <input type="file" class="form-control" name="image" accept="image/*,.svg" onchange="prevImg(this,'ePrev')">
                                <small class="text-muted">Leave blank to keep existing. PNG/SVG line drawing recommended.</small>
                                <div id="ePrev" class="mt-2"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="submit" name="edit_option" class="btn btn-warning"><i class="fas fa-save me-1"></i> Update Option</button></div>
                </form>
            </div>
        </div>
    </div>

    <!-- DELETE OPTION MODAL -->
    <div class="modal fade" id="delOptModal" tabindex="-1" aria-hidden="true" style="background:#14141491">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Delete Option?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem"><i class="fas fa-trash-alt fa-xl text-danger"></i></div>
                    <h5>Delete <strong id="dOptName"></strong>?</h5>
                    <p class="text-muted small">Image will also be removed. Cannot be undone.</p>
                </div>
                <div class="modal-footer justify-content-center border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a href="#" id="dOptBtn" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete</a></div>
            </div>
        </div>
    </div>

    <!-- DELETE GROUP MODAL -->
    <div class="modal fade" id="delGrpModal" tabindex="-1" aria-hidden="true" style="background:#14141491">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Delete Entire Group?</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem"><i class="fas fa-layer-group fa-xl text-danger"></i></div>
                    <h5>Delete group <strong id="dGrpName"></strong>?</h5>
                    <p class="text-muted small">All options + images inside will be deleted.<br><span class="text-danger fw-semibold">Also removes from configurator steps.</span></p>
                </div>
                <div class="modal-footer justify-content-center border-0"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><a href="#" id="dGrpBtn" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Delete Group</a></div>
            </div>
        </div>
    </div>

    <script>
        function switchView(v) {
            const c = v === 'card';
            document.getElementById('cardView').style.display = c ? '' : 'none';
            document.getElementById('tableView').style.display = c ? 'none' : '';
            document.getElementById('btnCard').classList.toggle('active', c);
            document.getElementById('btnTbl').classList.toggle('active', !c);
            localStorage.setItem('coView', v);
        }
        document.addEventListener('DOMContentLoaded', () => {
            if (localStorage.getItem('coView') === 'table') switchView('table');
            initSort();
        });

        function toggleNewGrp(v) {
            document.getElementById('newGrpW').style.display = v === '__new__' ? '' : 'none';
        }

        function openAddModal(g) {
            const s = document.getElementById('aGrpSel');
            for (let o of s.options) {
                if (o.value === g) {
                    o.selected = true;
                    break;
                }
            }
            new bootstrap.Modal(document.getElementById('addModal')).show();
        }

        function openEditModal(id, grp, name, desc, price, isDef, isAvail, order, img) {
            document.getElementById('eId').value = id;
            document.getElementById('eName').value = name;
            document.getElementById('eDesc').value = desc;
            document.getElementById('ePrice').value = price;
            document.getElementById('eOrd').value = order;
            document.getElementById('eDef').checked = isDef == 1;
            document.getElementById('eAvail').checked = isAvail == 1;
            document.getElementById('eExImg').value = img;
            document.getElementById('eRmImg').checked = false;
            const s = document.getElementById('eGrp');
            for (let o of s.options) {
                if (o.value === grp) {
                    o.selected = true;
                    break;
                }
            }
            const w = document.getElementById('eCurW'),
                im = document.getElementById('eCurImg');
            if (img) {
                im.src = 'uploads/customization/' + img;
                w.style.display = '';
                w.style.opacity = '1';
            } else w.style.display = 'none';
            document.getElementById('ePrev').innerHTML = '';
            new bootstrap.Modal(document.getElementById('editModal')).show();
        }

        function prevImg(input, tid) {
            const t = document.getElementById(tid);
            if (input.files && input.files[0]) {
                const r = new FileReader();
                r.onload = e => {
                    t.innerHTML = `<img src="${e.target.result}" class="img-up-prev" alt=""><small class="text-muted ms-2">New image</small>`;
                };
                r.readAsDataURL(input.files[0]);
            }
        }
        document.getElementById('delOptModal').addEventListener('show.bs.modal', function(e) {
            const b = e.relatedTarget;
            document.getElementById('dOptName').textContent = b.getAttribute('data-name');
            const g = b.getAttribute('data-group') || '';
            document.getElementById('dOptBtn').href = 'customization-options.php?delete=' + b.getAttribute('data-id') + (g ? '&group=' + encodeURIComponent(g) : '');
        });
        document.getElementById('delGrpModal').addEventListener('show.bs.modal', function(e) {
            const b = e.relatedTarget;
            const g = b.getAttribute('data-grp');
            document.getElementById('dGrpName').textContent = g;
            document.getElementById('dGrpBtn').href = 'customization-options.php?delete_group=' + encodeURIComponent(g);
        });
        let sTimer = null;

        function initSort() {
            document.querySelectorAll('.sortable-group').forEach(grid => {
                Sortable.create(grid, {
                    handle: '.opt-card',
                    animation: 180,
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    onEnd: function() {
                        const order = [...grid.querySelectorAll('[data-id]')].map(el => el.getAttribute('data-id'));
                        clearTimeout(sTimer);
                        sTimer = setTimeout(() => {
                            fetch('customization-options.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(order))
                            }).then(r => r.json()).then(d => {
                                if (d.success) showToast('success', 'Order saved');
                                else showToast('danger', 'Save failed');
                            });
                        }, 600);
                    }
                });
            });
        }

        function showToast(type, msg) {
            const t = document.createElement('div');
            t.className = `alert alert-${type} position-fixed shadow`;
            t.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:200px;font-size:13px;';
            t.innerHTML = msg;
            document.body.appendChild(t);
            setTimeout(() => t.remove(), 2500);
        }
    </script>
</body>

</html>