<?php ob_start();
session_start();
include "config/connect.php";
$user_id = $_SESSION['user_id'] ?? null;
$visitor_id = $_COOKIE['visitor_id'] ?? session_id();
$product_id = 0;

// Step 1: Get from URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $decoded = base64_decode($_GET['id'], true);

    if ($decoded !== false && is_numeric($decoded)) {
        $product_id = (int)$decoded;
    }
}

// Step 2: fallback
if (!$product_id) {
    $r = $conn->query("SELECT id FROM products WHERE is_customizable=1 AND status=1 LIMIT 1");

    if ($r && $r->num_rows) {
        $product_id = (int)$r->fetch_assoc()['id'];
    }
}
// Step 3: final fallback
if (!$product_id) {
    header("Location: index.php");
    exit();
}
$ps = $conn->prepare("SELECT * FROM products WHERE id=? AND status=1 LIMIT 1");
$ps->bind_param("i", $product_id);
$ps->execute();
$product = $ps->get_result()->fetch_assoc();
$ps->close();
if (!$product) {
    header("Location: index.php");
    exit();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $fabric_id = !empty($_POST['fabric_id']) ? (int)$_POST['fabric_id'] : null;
    $color_id = !empty($_POST['color_id']) ? (int)$_POST['color_id'] : null;
    $size = trim($_POST['size'] ?? '');
    $total_price = (float)($_POST['total_price'] ?? 0);
    $unit_price = (float)($_POST['unit_price'] ?? $total_price);
    $cjson = $_POST['customization_json'] ?? '{}';
    if (!json_decode($cjson)) $cjson = '{}';
    $prod_type = $product['product_type'];
    $chk = $conn->prepare("SELECT id FROM cart WHERE visitor_id=? AND product_id=? LIMIT 1");
    $chk->bind_param("si", $visitor_id, $product_id);
    $chk->execute();
    $existing = $chk->get_result()->fetch_assoc();
    $chk->close();
    if ($existing) {
        $up = $conn->prepare("UPDATE cart SET quantity=quantity+1,customization_json=?,updated_at=NOW() WHERE id=?");
        $up->bind_param("si", $cjson, $existing['id']);
        $up->execute();
        $up->close();
    } else {
        $ins = $conn->prepare("INSERT INTO cart (visitor_id,user_id,product_id,quantity,size,fabric_id,color_id,customization_json,product_type,unit_price,total_price,updated_at) VALUES (?,?,?,1,?,?,?,?,?,?,?,NOW())");
        $ins->bind_param("siisissdd", $visitor_id, $user_id, $product_id, $size, $fabric_id, $color_id, $cjson, $unit_price, $total_price);
        $ins->execute();
        $ins->close();
    }
    if (in_array($prod_type, ['made_to_order', 'both'])) {
        $base = (float)$product['base_price'] ?: (float)$product['selling_price'];
        $fpm = 0;
        if ($fabric_id) {
            $fr = $conn->prepare("SELECT price_modifier FROM fabric_options WHERE id=?");
            $fr->bind_param("i", $fabric_id);
            $fr->execute();
            $fpm = (float)($fr->get_result()->fetch_assoc()['price_modifier'] ?? 0);
            $fr->close();
        }
        $cust_cost = max(0, $total_price - $base - $fpm);
        $stmt = $conn->prepare("INSERT INTO custom_orders (user_id,product_id,fabric_id,outer_color_id,customization_json,base_price,fabric_cost,customization_cost,total_price,quantity,status) VALUES (?,?,?,?,?,?,?,?,?,1,'confirmed')");
        $stmt->bind_param("iiiissddd", $user_id, $product_id, $fabric_id, $color_id, $cjson, $base, $fpm, $cust_cost, $total_price);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['cart_success'] = "Your custom suit has been added to cart!";
    header("Location: cart.php");
    exit();
}

// configurator steps 
$steps_res = $conn->query("SELECT * FROM configurator_steps WHERE is_visible=1 ORDER BY step_order ASC");
$steps = [];
while ($s = $steps_res->fetch_assoc()) $steps[] = $s;

// customization options 
$opt_res = $conn->query("SELECT * FROM customization_options WHERE is_available=1 ORDER BY group_name,display_order ASC");
$opts_by_group = [];
while ($o = $opt_res->fetch_assoc()) $opts_by_group[$o['group_name']][] = $o;


//  echo $product_id ;
// fabric 
$fabrics = [];
$fr = $conn->prepare("SELECT f.* FROM fabric_options f INNER JOIN product_fabric_map pfm ON pfm.fabric_id=f.id WHERE pfm.product_id=? AND f.is_available=1 ORDER BY f.display_order ASC");
$fr->bind_param("i", $product_id);
$fr->execute();
$frr = $fr->get_result();
while ($f = $frr->fetch_assoc()) $fabrics[] = $f;
$fr->close();
if (empty($fabrics)) {
    $fall = $conn->query("SELECT * FROM fabric_options WHERE is_available=1 ORDER BY display_order ASC");
    while ($f = $fall->fetch_assoc()) $fabrics[] = $f;
}

// color 
$colors = [];
$cr = $conn->prepare("SELECT c.* FROM color_options c INNER JOIN product_color_map pcm ON pcm.color_id=c.id WHERE pcm.product_id=? AND c.is_available=1 ORDER BY c.display_order ASC");
$cr->bind_param("i", $product_id);
$cr->execute();
$crr = $cr->get_result();
while ($c = $crr->fetch_assoc()) $colors[] = $c;
$cr->close();
if (empty($colors)) {
    $call = $conn->query("SELECT * FROM color_options WHERE is_available=1 ORDER BY display_order ASC");
    while ($c = $call->fetch_assoc()) $colors[] = $c;
}

// size 
$sizes = [];
if (in_array($product['product_type'], ['ready_made', 'both'])) {
    $szr = $conn->prepare("SELECT * FROM product_sizes WHERE product_id=? AND is_available=1 ORDER BY sort_order ASC");
    $szr->bind_param("i", $product_id);
    $szr->execute();
    $szrr = $szr->get_result();
    while ($sz = $szrr->fetch_assoc()) $sizes[] = $sz;
    $szr->close();
}
$main_img = '';
$all_imgs = [];
if ($product['pro_img']) {
    $parts = array_filter(array_map('trim', explode(',', $product['pro_img'])));
    if ($parts) {
        $main_img = $parts[0];
        $all_imgs = array_values($parts);
    }
}
$pir = $conn->prepare("SELECT image_path FROM product_images WHERE product_id=? ORDER BY id ASC");
$pir->bind_param("i", $product_id);
$pir->execute();
$pirr = $pir->get_result();
while ($pi = $pirr->fetch_assoc()) $all_imgs[] = $pi['image_path'];
$pir->close();
$all_imgs = array_values(array_unique(array_filter($all_imgs)));
$variant_map = [];
$vir = $conn->prepare("SELECT color_id,customization_option_id,image_path FROM product_variant_images WHERE product_id=?");
$vir->bind_param("i", $product_id);
$vir->execute();
$virr = $vir->get_result();
while ($v = $virr->fetch_assoc()) {
    if ($v['color_id']) $variant_map['color_' . $v['color_id']] = $v['image_path'];
    if ($v['customization_option_id']) $variant_map['opt_' . $v['customization_option_id']] = $v['image_path'];
}
$vir->close();
$base_price = (float)$product['base_price'] ?: (float)$product['selling_price'];
$mrp = (float)$product['mrp'];
$toggle_groups = ['Waistcoat', 'Chest Pocket', 'Pick Stitch', 'Back Pocket'];
$dropdown_groups = ['Lining Color'];
$colByFam = [];
foreach ($colors as $c) $colByFam[$c['color_family'] ?: 'Colours'][] = $c;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0">
    <title>Design Your Suit — <?= htmlspecialchars($product['pro_name']) ?></title>
    <link rel="icon" href="assets/image/thumbnail.svg">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }

        body {
            background: #f7f7f5
        }

        .cfg-wrap {
            display: grid;
            grid-template-columns: 190px 1fr 1fr;
            min-height: 100vh
        }

        @media(max-width:991px) {
            .cfg-wrap {
                grid-template-columns: 1fr
            }

            .cfg-right {
                position: static !important
            }
        }

        .cfg-top {
            grid-column: 1/-1;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: #fff;
            border-bottom: 1px solid #e5e5e5;
            font-size: 12px;
            color: #888
        }

        .cfg-top a {
            color: #888;
            text-decoration: none
        }

        .cfg-top a:hover {
            color: #333
        }

        .cfg-title {
            font-size: 13px;
            font-weight: 600;
            color: #222
        }

        /* LEFT NAV */
        .cfg-left {
            background: #fff;
            border-right: 1px solid #e5e5e5;
            overflow-y: auto
        }

        .cfg-nav-item {
            display: block;
            padding: 13px 18px;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            cursor: pointer;
            border-left: 3px solid transparent;
            border-bottom: 1px solid #f0f0f0;
            transition: all .15s;
            text-align: left;
            background: transparent;
            width: 100%;
            position: relative
        }

        .cfg-nav-item:hover {
            background: #f8f8f8;
            color: #222
        }

        .cfg-nav-item.active {
            background: #1d2d44;
            color: #fff;
            border-left-color: #1d2d44;
            font-weight: 600
        }

        .cfg-nav-item .nav-sub {
            display: block;
            font-size: 10px;
            font-weight: 400;
            opacity: .6;
            margin-top: 1px
        }

        .cfg-nav-item.done::after {
            content: '✓';
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #27ae60
        }

        .cfg-nav-item.active.done::after {
            color: #a0e8b0
        }

        /* CENTER */
        .cfg-center {
            background: #fff;
            border-right: 1px solid #e5e5e5;
            overflow-y: auto;
            padding: 16px
        }

        .cfg-panel {
            display: none
        }

        .cfg-panel.active {
            display: block;
            animation: panIn .2s ease
        }

        @keyframes panIn {
            from {
                opacity: 0;
                transform: translateY(4px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .cfg-ph {
            font-size: 14px;
            font-weight: 600;
            color: #1d2d44;
            margin-bottom: 4px
        }

        .cfg-ps {
            font-size: 11px;
            color: #999;
            margin-bottom: 14px
        }

        .opt-tag {
            color: #c9a84c;
            font-weight: 600
        }

        /* Option tiles — exactly like screenshot */
        .opt-tiles {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px
        }

        .opt-tile {
            border: 1.5px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all .15s;
            overflow: hidden;
            position: relative;
            background: #fafafa
        }

        .opt-tile:hover {
            border-color: #1d2d44
        }

        .opt-tile.selected {
            border-color: #1d2d44;
            background: #f0f4ff
        }

        .opt-tile.selected::after {
            content: '✔';
            position: absolute;
            top: 5px;
            right: 7px;
            font-size: 13px;
            color: #27ae60;
            font-weight: 700
        }

        .opt-img {
            width: 100%;
            aspect-ratio: 1;
            object-fit: contain;
            padding: 8px;
            display: block;
            background: #f5f5f3
        }

        .opt-noimg {
            width: 100%;
            aspect-ratio: 1;
            background: #f0f0ee;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .opt-label {
            background: #1d2d44;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            padding: 5px 4px;
            line-height: 1.3
        }

        .opt-price {
            font-size: 9px;
            text-align: center;
            padding: 2px 4px 4px;
            color: #c9a84c;
            font-weight: 600
        }

        /* Fabric */
        .fab-tiles {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px
        }

        .fab-tile {
            border: 1.5px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            transition: all .15s;
            overflow: hidden;
            background: #fafafa
        }

        .fab-tile:hover {
            border-color: #1d2d44
        }

        .fab-tile.selected {
            border-color: #1d2d44;
            box-shadow: 0 0 0 2px rgba(29, 45, 68, .15)
        }

        .fab-sw-img {
            width: 100%;
            height: 100px;
            object-fit: cover;
            display: block
        }

        .fab-sw-col {
            width: 100%;
            height: 60px;
            display: block
        }

        .fab-label {
            background: #1d2d44;
            color: #fff;
            font-size: 11px;
            font-weight: 500;
            text-align: center;
            padding: 5px 4px
        }

        .fab-price {
            font-size: 9px;
            color: #c9a84c;
            text-align: center;
            padding: 2px 4px 4px
        }

        /* Colors */
        .col-tiles {
            display: flex;
            flex-wrap: wrap;
            gap: 10px
        }

        .col-tile {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            cursor: pointer
        }

        .col-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: 2px solid transparent;
            box-shadow: 0 1px 5px rgba(0, 0, 0, .2);
            transition: all .15s
        }

        .col-tile.selected .col-circle {
            border-color: #fff;
            box-shadow: 0 0 0 3px #1d2d44;
            transform: scale(1.1)
        }

        .col-lbl {
            font-size: 9px;
            color: #888;
            max-width: 44px;
            text-align: center;
            line-height: 1.2
        }

        /* Sizes */
        .sz-tiles {
            display: flex;
            flex-wrap: wrap;
            gap: 8px
        }

        .sz-tile {
            min-width: 50px;
            padding: 9px 12px;
            border: 1.5px solid #ddd;
            border-radius: 5px;
            font-size: 13px;
            font-weight: 600;
            color: #444;
            text-align: center;
            cursor: pointer;
            background: #fafafa;
            transition: all .15s
        }

        .sz-tile:hover {
            border-color: #1d2d44
        }

        .sz-tile.selected {
            border-color: #1d2d44;
            background: #f0f4ff;
            color: #1d2d44
        }

        .sz-tile.oos {
            opacity: .4;
            cursor: not-allowed;
            text-decoration: line-through
        }

        /* Toggle */
        .tog-tiles {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            max-width: 240px
        }

        .tog-tile {
            padding: 12px;
            text-align: center;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            background: #fafafa;
            transition: all .15s
        }

        .tog-tile:hover {
            border-color: #1d2d44
        }

        .tog-tile.selected {
            border-color: #1d2d44;
            background: #f0f4ff;
            color: #1d2d44;
            font-weight: 700
        }

        .tog-tile.sel-yes {
            border-color: #27ae60 !important;
            background: #f0faf5 !important;
            color: #27ae60 !important
        }

        /* Dropdown */
        .cfg-sel {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #ddd;
            border-radius: 6px;
            font-size: 13px;
            color: #333;
            background: #fafafa;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23888' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center
        }

        .cfg-sel:focus {
            outline: none;
            border-color: #1d2d44
        }

        /* RIGHT */
        .cfg-right {
            position: sticky;
            top: 0;
            height: 100vh;
            display: flex;
            flex-direction: column;
            background: #fafafa;
            overflow: hidden
        }

        .part-tabs {
            display: flex;
            gap: 8px;
            padding: 12px 16px;
            border-bottom: 1px solid #eee;
            background: #fff;
            flex-shrink: 0
        }

        .part-tab {
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: 1.5px solid #ddd;
            background: #fff;
            color: #666;
            transition: all .15s;
            letter-spacing: .03em
        }

        .part-tab.active {
            background: #1d2d44;
            border-color: #1d2d44;
            color: #fff
        }

        .preview-wrap {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            overflow: hidden;
            position: relative
        }

        .preview-img {
            max-height: 100%;
            max-width: 100%;
            object-fit: contain;
            transition: opacity .28s, transform .28s
        }

        .preview-img.sw {
            opacity: 0;
            transform: scale(.97)
        }

        .no-img {
            text-align: center;
            color: #ccc
        }

        .col-badge {
            position: absolute;
            top: 14px;
            left: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            background: rgba(255, 255, 255, .92);
            border: 1px solid #e5e5e5;
            border-radius: 20px;
            padding: 4px 10px 4px 6px;
            font-size: 11px;
            color: #444;
            opacity: 0;
            transition: opacity .3s;
            box-shadow: 0 1px 6px rgba(0, 0, 0, .08)
        }

        .col-badge.show {
            opacity: 1
        }

        .col-badge-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 1px solid rgba(0, 0, 0, .08)
        }

        .sel-sum {
            padding: 8px 16px;
            border-bottom: 1px solid #eee;
            min-height: 36px;
            background: #fff;
            flex-shrink: 0;
            overflow-x: auto;
            white-space: nowrap
        }

        .sel-chip {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            background: #f0f4ff;
            border: 1px solid #d0d8ee;
            border-radius: 20px;
            padding: 2px 8px;
            font-size: 10px;
            color: #555;
            margin: 0 2px
        }

        .sel-chip strong {
            color: #1d2d44
        }

        .cfg-price-cart {
            background: #fff;
            border-top: 1px solid #eee;
            padding: 14px 16px;
            flex-shrink: 0
        }

        .total-lbl {
            font-size: 12px;
            color: #888;
            margin-bottom: 2px
        }

        .total-val {
            font-size: 26px;
            font-weight: 800;
            color: #1d2d44;
            letter-spacing: -.02em;
            margin-bottom: 10px
        }

        .total-val .cur {
            font-size: 18px;
            font-weight: 600
        }

        .total-val .saving {
            font-size: 13px;
            font-weight: 500;
            color: #27ae60;
            margin-left: 8px;
            vertical-align: middle
        }

        .btn-cart {
            width: 100%;
            padding: 12px;
            background: #1d2d44;
            color: #fff;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            letter-spacing: .04em;
            transition: background .15s
        }

        .btn-cart:hover {
            background: #111c2e
        }

        .btn-cart:disabled {
            opacity: .6;
            cursor: not-allowed
        }

        .pb-row {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: #999;
            padding: 2px 0
        }

        .pb-row.extra {
            color: #e74c3c
        }

        .pb-row.gold {
            color: #c9a84c
        }

        .sz-chart-link {
            font-size: 11px;
            color: #1d2d44;
            text-decoration: underline;
            cursor: pointer;
            display: inline-block;
            margin-top: 8px
        }
    </style>
</head>

<body>

    <div class="cfg-wrap">

        <!-- TOP -->
        <div class="cfg-top">
            <div>
                <span class="cfg-title">Select all options and add to cart</span>
                <span style="margin:0 8px;color:#ddd">|</span>
                <a href="index.php">Home</a> <span style="margin:0 4px;color:#bbb">›</span>
                <a href="product-details.php?id=<?= $product_id ?>"><?= htmlspecialchars($product['pro_name']) ?></a>
            </div>
            <a href="javascript:history.go(-1)" style="color:#555;font-size:12px;text-decoration:none">‹ Return to previous page</a>
        </div>

        <!-- LEFT NAV -->
        <div class="cfg-left">
            <button class="cfg-nav-item active" data-nav="fabric" onclick="switchPanel('fabric',this)">Fabric</button>
            <button class="cfg-nav-item" data-nav="color" onclick="switchPanel('color',this)">Colour</button>
            <?php if (!empty($sizes)): ?><button class="cfg-nav-item" data-nav="size" onclick="switchPanel('size',this)">Size</button><?php endif; ?>
            <?php foreach ($steps as $step):
                $applies = $step['applies_to'];
                $sub = match (true) {
                    str_contains($applies, 'jacket') && !str_contains($applies, 'trouser') => 'Jacket',
                    str_contains($applies, 'trouser') && !str_contains($applies, 'jacket') => 'Trouser',
                    str_contains($applies, 'waistcoat') && !str_contains($applies, 'jacket') => 'Waistcoat',
                    default => ''
                };
            ?>
                <button class="cfg-nav-item" data-nav="step<?= $step['id'] ?>" onclick="switchPanel('step<?= $step['id'] ?>',this)">
                    <?= htmlspecialchars($step['group_name']) ?>
                    <?php if ($sub): ?><span class="nav-sub"><?= $sub ?></span><?php endif; ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- CENTER -->
        <div class="cfg-center">

            <!-- Fabric -->
            <div class="cfg-panel active" id="panel_fabric">
                <div class="cfg-ph">Choose Your Fabric</div>
                <div class="cfg-ps">Select the fabric for your suit.</div>
                <div class="fab-tiles">
                    <?php foreach ($fabrics as $f): $pm = (float)$f['price_modifier'];
                        $pml = $pm > 0 ? '+₹' . number_format($pm, 0) : ($pm < 0 ? '-₹' . number_format(abs($pm), 0) : ''); ?>
                        <div class="fab-tile" data-fab-id="<?= $f['id'] ?>" data-fab-name="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>" data-fab-price="<?= $pm ?>" onclick="selFab(this)">
                            <?php if (!empty($f['image']) && file_exists("admin/uploads/fabrics/" . $f['image'])): ?><img src="admin/uploads/fabrics/<?= htmlspecialchars($f['image']) ?>" class="fab-sw-img" alt="">
                            <?php else: ?><div class="fab-sw-col" style="background:<?= htmlspecialchars($f['swatch_color'] ?? '#888') ?>"></div><?php endif; ?>
                            <div class="fab-label"><?= htmlspecialchars($f['name']) ?><?php if ($f['material']): ?><br><span style="font-weight:400;opacity:.7;font-size:9px"><?= htmlspecialchars($f['material']) ?></span><?php endif; ?></div>
                            <?php if ($pml): ?><div class="fab-price"><?= $pml ?></div><?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($fabrics)): ?><div style="grid-column:1/-1;text-align:center;padding:20px;color:#bbb;font-size:12px">No fabrics configured.<br><a href="admin/manage-fabric.php" style="color:#1d2d44">Add in admin</a></div><?php endif; ?>
                </div>
            </div>

            <!-- Colour -->
            <div class="cfg-panel" id="panel_color">
                <div class="cfg-ph">Choose Your Colour</div>
                <div class="cfg-ps">Select the main fabric colour.</div>
                <?php foreach ($colByFam as $fam => $cols): ?>
                    <div style="font-size:10px;font-weight:600;color:#aaa;text-transform:uppercase;letter-spacing:.07em;margin:10px 0 6px"><?= htmlspecialchars($fam) ?></div>
                    <div class="col-tiles" style="margin-bottom:12px">
                        <?php foreach ($cols as $c): ?>
                            <div class="col-tile" data-col-id="<?= $c['id'] ?>" data-col-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>" data-col-hex="<?= htmlspecialchars($c['hex_code']) ?>" onclick="selColor(this)" title="<?= htmlspecialchars($c['name']) ?>">
                                <div class="col-circle" style="background:<?= htmlspecialchars($c['hex_code']) ?>"></div>
                                <div class="col-lbl"><?= htmlspecialchars($c['name']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Size -->
            <?php if (!empty($sizes)): ?>
                <div class="cfg-panel" id="panel_size">
                    <div class="cfg-ph">Select Your Size</div>
                    <div class="cfg-ps">Measurements in inches.</div>
                    <div class="sz-tiles">
                        <?php foreach ($sizes as $sz): ?>
                            <div class="sz-tile <?= $sz['stock'] == 0 ? 'oos' : '' ?>" data-sz="<?= htmlspecialchars($sz['size_label'], ENT_QUOTES) ?>" data-sz-price="<?= $sz['price_modifier'] ?>" <?= $sz['stock'] > 0 ? 'onclick="selSize(this)"' : '' ?>>
                                <?= htmlspecialchars($sz['size_label']) ?>
                                <?php if ((float)$sz['price_modifier'] > 0): ?><div style="font-size:9px;color:#c9a84c">+₹<?= number_format((float)$sz['price_modifier'], 0) ?></div><?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <a class="sz-chart-link" data-bs-toggle="modal" data-bs-target="#szModal">View size chart</a>
                </div>
            <?php endif; ?>

            <!-- Dynamic steps -->
            <?php foreach ($steps as $step):
                $grpOpts = $opts_by_group[$step['group_name']] ?? [];
                $isToggle = in_array($step['group_name'], $toggle_groups);
                $isDrop = in_array($step['group_name'], $dropdown_groups);
            ?>
                <div class="cfg-panel" id="panel_step<?= $step['id'] ?>">
                    <div class="cfg-ph"><?= htmlspecialchars($step['step_label']) ?></div>
                    <div class="cfg-ps">
                        <?= !$step['is_required'] ? '<span class="opt-tag">Optional — </span>' : '' ?>
                        <?= $step['group_name'] === 'Waistcoat' ? 'Adding waistcoat adds 20% to jacket price.' : 'Select your preferred ' . strtolower($step['group_name']) . '.' ?>
                    </div>
                    <?php if (empty($grpOpts)): ?>
                        <div style="text-align:center;padding:20px;color:#bbb;font-size:12px">No options.<br><a href="admin/customization-options.php" style="color:#1d2d44">Add in admin</a></div>
                    <?php elseif ($isDrop): ?>
                        <select class="cfg-sel" data-group="<?= htmlspecialchars($step['group_name'], ENT_QUOTES) ?>" onchange="selDrop(this)">
                            <option value="">— Choose <?= htmlspecialchars($step['group_name']) ?> —</option>
                            <?php foreach ($grpOpts as $o): ?><option value="<?= htmlspecialchars($o['option_name'], ENT_QUOTES) ?>" data-price="<?= $o['price_modifier'] ?>"><?= htmlspecialchars($o['option_name']) ?><?= (float)$o['price_modifier'] > 0 ? ' (+₹' . number_format((float)$o['price_modifier'], 0) . ')' : '' ?></option><?php endforeach; ?>
                        </select>
                    <?php elseif ($isToggle): ?>
                        <div class="tog-tiles">
                            <?php foreach ($grpOpts as $o): $isY = str_contains(strtolower($o['option_name']), 'add') || str_contains(strtolower($o['option_name']), 'with') || str_contains(strtolower($o['option_name']), 'yes'); ?>
                                <div class="tog-tile <?= $o['is_default'] ? 'selected' : '' ?>" data-group="<?= htmlspecialchars($step['group_name'], ENT_QUOTES) ?>" data-option="<?= htmlspecialchars($o['option_name'], ENT_QUOTES) ?>" data-price="<?= $o['price_modifier'] ?>" data-yes="<?= $isY ? '1' : '0' ?>" onclick="selToggle(this,'<?= htmlspecialchars($step['group_name'], ENT_QUOTES) ?>')">
                                    <?= htmlspecialchars($o['option_name']) ?>
                                    <?php if ((float)$o['price_modifier'] > 0): ?><div style="font-size:10px;color:#c9a84c;margin-top:3px">+₹<?= number_format((float)$o['price_modifier'], 0) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="opt-tiles">
                            <?php foreach ($grpOpts as $o): $pm = (float)$o['price_modifier'];
                                $vk = 'opt_' . $o['id'];
                                $imgSrc = '';
                                if (!empty($variant_map[$vk]) && file_exists("admin/uploads/variant-images/" . $variant_map[$vk])) $imgSrc = "admin/uploads/variant-images/" . $variant_map[$vk];
                                elseif (!empty($o['image']) && file_exists("admin/uploads/customization/" . $o['image'])) $imgSrc = "admin/uploads/customization/" . $o['image'];
                            ?>
                                <div class="opt-tile <?= $o['is_default'] ? 'selected' : '' ?>" data-group="<?= htmlspecialchars($step['group_name'], ENT_QUOTES) ?>" data-option="<?= htmlspecialchars($o['option_name'], ENT_QUOTES) ?>" data-price="<?= $pm ?>" data-opt-id="<?= $o['id'] ?>" onclick="selOpt(this,'<?= htmlspecialchars($step['group_name'], ENT_QUOTES) ?>')">
                                    <?php if ($imgSrc): ?><img class="opt-img" src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($o['option_name']) ?>">
                                    <?php else: ?>
                                        <div class="opt-noimg">
                                            <svg width="50" height="50" viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg" opacity=".25">
                                                <rect x="8" y="4" width="36" height="44" rx="4" stroke="#888" stroke-width="1.5" fill="none" />
                                                <path d="M18 4L26 14L34 4" stroke="#888" stroke-width="1.5" fill="none" />
                                                <line x1="26" y1="14" x2="26" y2="30" stroke="#888" stroke-width="1" stroke-dasharray="2 2" />
                                                <rect x="12" y="20" width="10" height="6" rx="1" stroke="#888" stroke-width="1" fill="none" />
                                                <rect x="22" y="38" width="8" height="6" rx="1" stroke="#888" stroke-width="1" fill="none" />
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                    <div class="opt-label"><?= htmlspecialchars($o['option_name']) ?></div>
                                    <?php if ($pm > 0): ?><div class="opt-price">+₹<?= number_format($pm, 0) ?></div><?php elseif ($pm < 0): ?><div class="opt-price" style="color:#27ae60">-₹<?= number_format(abs($pm), 0) ?></div><?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        </div><!-- /cfg-center -->

        <!-- RIGHT -->
        <div class="cfg-right">
            <div class="part-tabs">
                <button class="part-tab active" onclick="switchPart('jacket',this)">JACKET</button>
                <button class="part-tab" onclick="switchPart('waistcoat',this)">WAISTCOAT</button>
                <button class="part-tab" onclick="switchPart('pant',this)">PANT</button>
            </div>
            <div class="preview-wrap">
                <div class="col-badge" id="colBadge">
                    <div class="col-badge-dot" id="colBadgeDot"></div><span id="colBadgeTxt"></span>
                </div>
                <?php if ($main_img && file_exists("assets/img/uploads/$main_img")): ?>
                    <img id="previewImg" src="assets/img/uploads/<?= htmlspecialchars($main_img) ?>" class="preview-img" alt="<?= htmlspecialchars($product['pro_name']) ?>">
                <?php else: ?><div class="no-img"><i class="bi bi-suit-spade-fill" style="font-size:4rem;display:block;margin-bottom:10px"></i>
                        <p style="font-size:12px">Upload product images in admin</p>
                    </div><?php endif; ?>
            </div>
            <div class="sel-sum">
                <div id="selChips"><span style="font-size:10px;color:#bbb">Make your selections</span></div>
            </div>
            <div class="cfg-price-cart">
                <div class="total-lbl">Total:</div>
                <div class="total-val"><span class="cur">₹</span><span id="totalVal"><?= number_format($base_price, 0) ?></span><?php if ($mrp > $base_price): ?><span class="saving">Save <?= round((($mrp - $base_price) / $mrp) * 100) ?>%</span><?php endif; ?></div>
                <div style="margin-bottom:10px">
                    <div class="pb-row"><span>Base price</span><span id="pbBase">₹<?= number_format($base_price, 0) ?></span></div>
                    <div class="pb-row gold" id="pbFabRow" style="display:none"><span>Fabric</span><span id="pbFab"></span></div>
                    <div class="pb-row extra" id="pbWRow" style="display:none"><span>Waistcoat (+20%)</span><span id="pbW"></span></div>
                    <div class="pb-row gold" id="pbCRow" style="display:none"><span>Customizations</span><span id="pbC"></span></div>
                </div>
                <button class="btn-cart" id="btnCart" onclick="addToCart()"><i class="bi bi-cart"></i> ADD TO CART</button>
            </div>
        </div>

    </div><!-- /cfg-wrap -->

    <div class="modal fade" id="szModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Size Chart (inches)</h5><button class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm table-bordered" style="font-size:12px">
                        <thead class="table-dark">
                            <tr>
                                <th>Size</th>
                                <th>Chest</th>
                                <th>Waist</th>
                                <th>Hip</th>
                                <th>Shoulder</th>
                            </tr>
                        </thead>
                        <tbody><?php foreach ($sizes as $sz): ?><tr>
                                    <td><strong><?= htmlspecialchars($sz['size_label']) ?></strong></td>
                                    <td><?= $sz['chest_min'] ?>–<?= $sz['chest_max'] ?></td>
                                    <td><?= $sz['waist_min'] ?>–<?= $sz['waist_max'] ?></td>
                                    <td><?= $sz['hip_min'] ?>–<?= $sz['hip_max'] ?></td>
                                    <td><?= $sz['shoulder'] ?></td>
                                </tr><?php endforeach; ?></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <form id="cfgForm" method="POST" action="" style="display:none">
        <input type="hidden" name="add_to_cart" value="1">
        <input type="hidden" name="fabric_id" id="hFab">
        <input type="hidden" name="color_id" id="hCol">
        <input type="hidden" name="size" id="hSz">
        <input type="hidden" name="unit_price" id="hUnit">
        <input type="hidden" name="total_price" id="hTot">
        <input type="hidden" name="customization_json" id="hJson">
    </form>

    <?php 
    // include_once "includes/footer.php"; 
    ?>
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        const BASE = <?= $base_price ?>;
        const VMAP = <?= json_encode($variant_map) ?>;
        const PROD_TYPE = '<?= $product['product_type'] ?>';
        const IMGS = <?= json_encode(array_map(fn($i) => "assets/img/uploads/$i", $all_imgs)) ?>;
        let sels = {},
            fabId = null,
            colId = null,
            selSz = '';
        document.querySelectorAll('.opt-tile.selected').forEach(t => {
            if (t.dataset.group && t.dataset.option) sels[t.dataset.group] = {
                option: t.dataset.option,
                price: parseFloat(t.dataset.price) || 0
            }
        });
        document.querySelectorAll('.tog-tile.selected').forEach(t => {
            if (t.dataset.group && t.dataset.option) sels[t.dataset.group] = {
                option: t.dataset.option,
                price: parseFloat(t.dataset.price) || 0
            }
        });
        updateAll();

        function switchPanel(key, btn) {
            document.querySelectorAll('.cfg-panel').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.cfg-nav-item').forEach(b => b.classList.remove('active'));
            const p = document.getElementById('panel_' + key);
            if (p) p.classList.add('active');
            if (btn) btn.classList.add('active')
        }

        function switchPart(part, btn) {
            document.querySelectorAll('.part-tab').forEach(b => b.classList.remove('active'));
            if (btn) btn.classList.add('active');
            if (part === 'jacket' && IMGS[0]) swImg(IMGS[0]);
            if (part === 'waistcoat' && IMGS[1]) swImg(IMGS[1]);
            if (part === 'pant' && IMGS[2]) swImg(IMGS[2]);
        }

        function selFab(t) {
            document.querySelectorAll('.fab-tile').forEach(x => x.classList.remove('selected'));
            t.classList.add('selected');
            fabId = t.dataset.fabId;
            document.getElementById('hFab').value = fabId;
            markDone('fabric');
            updateAll()
        }

        function selColor(t) {
            document.querySelectorAll('.col-tile').forEach(x => x.classList.remove('selected'));
            t.classList.add('selected');
            colId = t.dataset.colId;
            document.getElementById('hCol').value = colId;
            document.getElementById('colBadgeDot').style.background = t.dataset.colHex;
            document.getElementById('colBadgeTxt').textContent = t.dataset.colName;
            document.getElementById('colBadge').classList.add('show');
            const vk = 'color_' + colId;
            if (VMAP[vk]) swImg('uploads/variant-images/' + VMAP[vk]);
            markDone('color');
            updateAll()
        }

        function selSize(b) {
            if (b.classList.contains('oos')) return;
            document.querySelectorAll('.sz-tile').forEach(x => x.classList.remove('selected'));
            b.classList.add('selected');
            selSz = b.dataset.sz;
            document.getElementById('hSz').value = selSz;
            markDone('size');
            updateAll()
        }

        function selOpt(t, group) {
            document.querySelectorAll(`.opt-tile[data-group="${CSS.escape(group)}"]`).forEach(x => x.classList.remove('selected'));
            t.classList.add('selected');
            sels[group] = {
                option: t.dataset.option,
                price: parseFloat(t.dataset.price) || 0
            };
            const vk = 'opt_' + t.dataset.optId;
            if (VMAP[vk]) swImg('uploads/variant-images/' + VMAP[vk]);
            else if (t.querySelector('img')) swImg(t.querySelector('img').src);
            updateAll()
        }

        function selToggle(b, group) {
            document.querySelectorAll(`.tog-tile[data-group="${CSS.escape(group)}"]`).forEach(x => {
                x.classList.remove('selected', 'sel-yes')
            });
            b.classList.add('selected');
            if (b.dataset.yes === '1') b.classList.add('sel-yes');
            sels[group] = {
                option: b.dataset.option,
                price: parseFloat(b.dataset.price) || 0
            };
            updateAll()
        }

        function selDrop(s) {
            const g = s.dataset.group,
                o = s.value,
                p = parseFloat(s.options[s.selectedIndex].dataset.price) || 0;
            if (o) sels[g] = {
                option: o,
                price: p
            };
            else delete sels[g];
            updateAll()
        }

        function markDone(key) {
            const btn = document.querySelector(`.cfg-nav-item[data-nav="${key}"]`);
            if (btn) btn.classList.add('done')
        }

        function updateAll() {
            let total = BASE,
                jacket = BASE,
                waist = 0,
                customCost = 0;
            const ft = document.querySelector('.fab-tile.selected');
            if (ft) {
                const fp = parseFloat(ft.dataset.fabPrice) || 0;
                total += fp;
                jacket += fp;
            }
            const sz = document.querySelector('.sz-tile.selected');
            if (sz) {
                const sp = parseFloat(sz.dataset.szPrice) || 0;
                total += sp;
                jacket += sp;
            }
            Object.values(sels).forEach(s => {
                if (s.price) {
                    total += s.price;
                    customCost += s.price;
                    jacket += s.price;
                }
            });
            const wv = sels['Waistcoat'];
            if (wv && wv.option && !wv.option.toLowerCase().includes('no')) {
                waist = Math.round(jacket * 0.20);
                total += waist;
            }
            document.getElementById('totalVal').textContent = Math.round(total).toLocaleString('en-IN');
            document.getElementById('pbBase').textContent = '₹' + Math.round(BASE).toLocaleString('en-IN');
            if (ft && parseFloat(ft.dataset.fabPrice)) {
                document.getElementById('pbFabRow').style.display = 'flex';
                document.getElementById('pbFab').textContent = '+₹' + Math.round(parseFloat(ft.dataset.fabPrice)).toLocaleString('en-IN');
            } else {
                document.getElementById('pbFabRow').style.display = 'none';
            }
            if (waist) {
                document.getElementById('pbWRow').style.display = 'flex';
                document.getElementById('pbW').textContent = '+₹' + Math.round(waist).toLocaleString('en-IN');
            } else {
                document.getElementById('pbWRow').style.display = 'none';
            }
            if (customCost > 0) {
                document.getElementById('pbCRow').style.display = 'flex';
                document.getElementById('pbC').textContent = '+₹' + Math.round(customCost).toLocaleString('en-IN');
            } else {
                document.getElementById('pbCRow').style.display = 'none';
            }
            document.getElementById('hUnit').value = Math.round(total);
            document.getElementById('hTot').value = Math.round(total);
            renderChips();
        }

        function renderChips() {
            const w = document.getElementById('selChips');
            w.innerHTML = '';
            const ft = document.querySelector('.fab-tile.selected');
            if (ft) chip(w, 'Fabric', ft.dataset.fabName);
            const ct = document.querySelector('.col-tile.selected');
            if (ct) chip(w, 'Colour', ct.dataset.colName);
            const sz = document.querySelector('.sz-tile.selected');
            if (sz) chip(w, 'Size', sz.dataset.sz);
            Object.entries(sels).forEach(([g, v]) => {
                if (v.option) chip(w, g, v.option)
            });
            if (!w.children.length) w.innerHTML = '<span style="font-size:10px;color:#bbb">Make your selections above</span>';
        }

        function chip(w, l, v) {
            const el = document.createElement('span');
            el.className = 'sel-chip';
            el.innerHTML = l + ': <strong>' + v + '</strong>';
            w.appendChild(el);
        }

        function swImg(src) {
            const img = document.getElementById('previewImg');
            if (!img) return;
            img.classList.add('sw');
            setTimeout(() => {
                img.src = src;
                img.classList.remove('sw');
            }, 270);
        }

        function addToCart() {
            if (!fabId) {
                switchPanel('fabric', document.querySelector('[data-nav="fabric"]'));
                alert('Please select a fabric first.');
                return;
            }
            if (!colId) {
                switchPanel('color', document.querySelector('[data-nav="color"]'));
                alert('Please select a colour first.');
                return;
            }
            if (PROD_TYPE === 'ready_made' && !selSz) {
                alert('Please select a size.');
                return;
            }
            const j = {};
            Object.entries(sels).forEach(([g, v]) => {
                if (v.option) j[g] = v.option;
            });
            document.getElementById('hJson').value = JSON.stringify(j);
            const btn = document.getElementById('btnCart');
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> ADDING...';
            btn.disabled = true;
            document.getElementById('cfgForm').submit();
        }
    </script>
</body>

</html>