<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header("Location: view-custom-orders.php"); exit(); }

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
$STATUS_PIPELINE = ['draft','confirmed','in_progress','stitching','quality_check','ready','delivered'];

$MEASUREMENTS = [
    'Jacket'  => [
        'chest'        => 'Chest (cm)',
        'waist'        => 'Waist (cm)',
        'hips'         => 'Hips (cm)',
        'shoulder'     => 'Shoulder (cm)',
        'sleeve_length'=> 'Sleeve Length (cm)',
        'jacket_length'=> 'Jacket Length (cm)',
        'neck'         => 'Neck (cm)',
        'half_chest'   => 'Half Chest (cm)',
    ],
    'Trouser' => [
        'trouser_waist' => 'Trouser Waist (cm)',
        'inseam'        => 'Inseam (cm)',
        'outseam'       => 'Outseam (cm)',
        'thigh'         => 'Thigh (cm)',
        'trouser_length'=> 'Trouser Length (cm)',
    ],
    'Body'    => [
        'height' => 'Height (cm)',
        'weight' => 'Weight (kg)',
    ],
];

// ── ACTIONS ───────────────────────────────────────────────────
if (isset($_POST['update_status'])) {
    $st = $_POST['status'];
    if (array_key_exists($st, $STATUS_CONFIG)) {
        $s = $conn->prepare("UPDATE custom_orders SET status=? WHERE id=?");
        $s->bind_param("si", $st, $id); $s->execute(); $s->close();
        $_SESSION['success'] = "Status updated to " . $STATUS_CONFIG[$st]['label'] . "!";
    }
    header("Location: view-custom-order-detail.php?id=$id"); exit();
}

if (isset($_POST['update_delivery'])) {
    $date = $_POST['expected_delivery'];
    $s = $conn->prepare("UPDATE custom_orders SET expected_delivery=? WHERE id=?");
    $s->bind_param("si", $date, $id); $s->execute(); $s->close();
    $_SESSION['success'] = "Delivery date updated!";
    header("Location: view-custom-order-detail.php?id=$id"); exit();
}

if (isset($_POST['save_notes'])) {
    $note = trim($_POST['special_note']);
    $s = $conn->prepare("UPDATE custom_orders SET special_note=? WHERE id=?");
    $s->bind_param("si", $note, $id); $s->execute(); $s->close();
    $_SESSION['success'] = "Notes saved!";
    header("Location: view-custom-order-detail.php?id=$id"); exit();
}

if (isset($_POST['update_measurements'])) {
    $mid = (int)$_POST['measurement_id'];
    $fields = ['chest','waist','hips','shoulder','sleeve_length','jacket_length','neck','half_chest',
               'trouser_waist','inseam','outseam','thigh','trouser_length','shirt_length','height','weight','notes'];
    $sets = []; $vals = []; $types = '';
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            $sets[]  = "$f=?";
            $vals[]  = $_POST[$f] !== '' ? (float)$_POST[$f] : null;
            $types  .= 'd';
        }
    }
    if ($mid && $sets) {
        $vals[]  = $mid;
        $types  .= 'i';
        $sql = "UPDATE customer_measurements SET ".implode(',',$sets)." WHERE id=?";
        $s = $conn->prepare($sql);
        $s->bind_param($types, ...$vals);
        $s->execute(); $s->close();
        $_SESSION['success'] = "Measurements updated!";
    }
    header("Location: view-custom-order-detail.php?id=$id"); exit();
}

// ── FETCH ORDER ───────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT co.*,
           u.first_name, u.last_name, u.email AS user_email, u.mobile,
           p.pro_name, p.pro_img, p.product_type,
           f.name AS fabric_name, f.material AS fabric_material,
           f.swatch_color AS fabric_swatch, f.image AS fabric_image,
           f.price_modifier AS fabric_price,
           oc.name AS outer_color,  oc.hex_code AS outer_hex,
           lc.name AS lining_color, lc.hex_code AS lining_hex,
           bc.name AS button_color, bc.hex_code AS button_hex
    FROM custom_orders co
    LEFT JOIN users u            ON co.user_id         = u.id
    LEFT JOIN products p         ON co.product_id      = p.id
    LEFT JOIN fabric_options f   ON co.fabric_id       = f.id
    LEFT JOIN color_options oc   ON co.outer_color_id  = oc.id
    LEFT JOIN color_options lc   ON co.lining_color_id = lc.id
    LEFT JOIN color_options bc   ON co.button_color_id = bc.id
    WHERE co.id = ? LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { $_SESSION['error'] = "Order not found."; header("Location: view-custom-orders.php"); exit(); }

// ── FETCH MEASUREMENTS ────────────────────────────────────────
$meas = null;
if ($order['measurement_id']) {
    $ms = $conn->prepare("SELECT * FROM customer_measurements WHERE id=? LIMIT 1");
    $ms->bind_param("i", $order['measurement_id']);
    $ms->execute();
    $meas = $ms->get_result()->fetch_assoc();
    $ms->close();
}

// ── PARSE CUSTOMIZATION JSON ──────────────────────────────────
$custom_options = [];
if (!empty($order['customization_json'])) {
    $decoded = json_decode($order['customization_json'], true);
    if (is_array($decoded)) $custom_options = $decoded;
}

// ── FETCH LOGO ────────────────────────────────────────────────
$logo = $conn->query("SELECT logo_path FROM logos WHERE location='header' AND is_active=1 LIMIT 1")->fetch_assoc();

$sc           = $STATUS_CONFIG[$order['status']] ?? $STATUS_CONFIG['draft'];
$cust         = trim(($order['first_name'] ?? '') . ' ' . ($order['last_name'] ?? ''));
if (!$cust) $cust = 'Customer #' . ($order['user_id'] ?? 'Guest');
$pipeline_idx = array_search($order['status'], $STATUS_PIPELINE);
$is_print     = isset($_GET['print']);

// Delivery status
$delivery_overdue = false;
$delivery_soon    = false;
if ($order['expected_delivery'] && !in_array($order['status'],['delivered','cancelled'])) {
    $diff = (int)((strtotime($order['expected_delivery']) - strtotime('today')) / 86400);
    if ($diff < 0)       $delivery_overdue = true;
    elseif ($diff <= 3)  $delivery_soon    = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Custom Order #<?= $id ?> | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php if (!$is_print) include "links.php"; ?>
    <?php if ($is_print): ?>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <?php endif; ?>
    <style>
        /* Pipeline */
        .pipeline { display:flex; align-items:flex-start; overflow-x:auto; padding:10px 0; gap:0; }
        .pipeline-step { display:flex; flex-direction:column; align-items:center; min-width:90px; position:relative; }
        .pipeline-step:not(:last-child)::after { content:''; position:absolute; top:18px; left:calc(50% + 18px); width:calc(100% - 36px); height:3px; background:#dee2e6; z-index:0; }
        .pipeline-step.done::after   { background:#27ae60; }
        .pipeline-step.active::after { background:linear-gradient(90deg,#27ae60,#dee2e6); }
        .pipeline-dot { width:36px; height:36px; border-radius:50%; border:3px solid #dee2e6; background:#fff; display:flex; align-items:center; justify-content:center; font-size:13px; position:relative; z-index:1; color:#dee2e6; }
        .pipeline-step.done   .pipeline-dot { background:#27ae60; border-color:#27ae60; color:#fff; }
        .pipeline-step.active .pipeline-dot { background:#2c3e50; border-color:#2c3e50; color:#fff; box-shadow:0 0 0 4px rgba(44,62,80,0.15); }
        .pipeline-label { font-size:10px; color:#aaa; margin-top:5px; text-align:center; max-width:80px; line-height:1.3; }
        .pipeline-step.done   .pipeline-label { color:#27ae60; font-weight:600; }
        .pipeline-step.active .pipeline-label { color:#2c3e50; font-weight:600; }
        /* Cards */
        .detail-card { background:#fff; border:1px solid #e9ecef; border-radius:12px; overflow:hidden; margin-bottom:16px; }
        .detail-card-header { padding:14px 18px; background:#f8f9fa; border-bottom:1px solid #e9ecef; font-weight:600; font-size:14px; display:flex; justify-content:space-between; align-items:center; }
        .detail-card-body { padding:18px; }
        /* Customization option chips */
        .option-chip { display:inline-flex; align-items:center; gap:6px; background:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; padding:6px 10px; font-size:12px; }
        .option-chip-group { font-size:10px; color:#6c757d; display:block; }
        .option-chip-val { font-weight:600; color:#2c3e50; }
        /* Color swatch */
        .swatch-lg { width:32px; height:32px; border-radius:50%; border:2px solid #dee2e6; display:inline-block; flex-shrink:0; }
        /* Measurements grid */
        .meas-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:10px; }
        .meas-item { background:#f8f9fa; border-radius:8px; padding:10px 14px; }
        .meas-label { font-size:11px; color:#6c757d; }
        .meas-val   { font-size:16px; font-weight:700; color:#2c3e50; }
        .meas-val.empty { color:#dee2e6; }
        .meas-section-title { font-size:12px; font-weight:600; text-transform:uppercase; letter-spacing:0.05em; color:#6c757d; margin:12px 0 6px; }
        /* Fabric display */
        .fabric-swatch-lg { width:50px; height:50px; border-radius:8px; border:2px solid #dee2e6; flex-shrink:0; }
        /* Price breakdown */
        .price-row { display:flex; justify-content:space-between; padding:5px 0; font-size:13px; border-bottom:0.5px solid #f0f0f0; }
        .price-row:last-child { border:none; }
        .price-row.total { font-size:16px; font-weight:700; border-top:2px solid #2c3e50; margin-top:6px; padding-top:10px; }
        /* Status pill */
        .status-pill { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:600; padding:5px 12px; border-radius:20px; }
        .status-dot  { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
        /* Action btn */
        .action-btn  { width:30px; height:30px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; padding:0; }
        /* Print */
        @media print { .no-print { display:none !important; } body { background:#fff !important; } .detail-card { box-shadow:none !important; border:1px solid #ccc !important; } }
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

                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 no-print">
                    <a href="view-custom-orders.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Custom Orders
                    </a>
                    <div class="d-flex gap-2">
                        <a href="view-custom-order-detail.php?id=<?= $id ?>&print=1"
                           target="_blank" class="btn btn-sm btn-outline-dark">
                            <i class="fas fa-print me-1"></i>Print Order Sheet
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Print header -->
                <?php if ($is_print): ?>
                <div class="d-flex justify-content-between align-items-start mb-4 pb-3 border-bottom">
                    <div>
                        <?php if ($logo): ?>
                            <img src="<?= htmlspecialchars($logo['logo_path']) ?>" height="50" alt="Logo" class="mb-1">
                        <?php else: ?>
                            <h4 class="fw-bold">Your Tailor Shop</h4>
                        <?php endif; ?>
                        <div class="text-muted small">Custom Suit Order Sheet</div>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold mb-1">Custom Order #<?= $id ?></h5>
                        <div class="text-muted small"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
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

                    <!-- LEFT -->
                    <div class="col-lg-8">

                        <!-- Status pipeline (non-print) -->
                        <?php if (!$is_print): ?>
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <div>
                                    <span class="me-3">Custom Order <strong>#<?= $id ?></strong></span>
                                    <span class="text-muted" style="font-size:12px;">
                                        <?= date('d M Y, h:i A', strtotime($order['created_at'])) ?>
                                    </span>
                                </div>
                                <span class="status-pill" style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
                                    <span class="status-dot" style="background:<?= $sc['color'] ?>;"></span>
                                    <?= $sc['label'] ?>
                                </span>
                            </div>
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
                                <?php if (!in_array($order['status'],['delivered','cancelled'])): ?>
                                <form action="" method="POST" class="d-flex align-items-center gap-2 mt-3 no-print">
                                    <select name="status" class="form-select form-select-sm" style="max-width:200px;">
                                        <?php foreach ($STATUS_CONFIG as $key => $cfg): ?>
                                            <option value="<?= $key ?>" <?= $order['status']===$key?'selected':'' ?>>
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

                        <!-- Product + fabric + colors -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-tshirt me-2 text-muted"></i>Product & Material</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="row g-3">
                                    <!-- Product -->
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-start gap-3">
                                            <?php
                                            $img = $order['pro_img'] ? explode(',', $order['pro_img'])[0] : '';
                                            if ($img && file_exists("assets/img/uploads/$img")): ?>
                                                <img src="assets/img/uploads/<?= htmlspecialchars($img) ?>"
                                                     style="width:70px;height:70px;object-fit:cover;border-radius:10px;border:1px solid #eee;" alt="">
                                            <?php else: ?>
                                                <div style="width:70px;height:70px;border-radius:10px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#ccc;">
                                                    <i class="fas fa-tshirt fa-2x"></i>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold" style="font-size:15px;"><?= htmlspecialchars($order['pro_name'] ?? '—') ?></div>
                                                <div class="text-muted small">Qty: <?= (int)$order['quantity'] ?></div>
                                                <div class="text-muted small">Type: <?= ucfirst(str_replace('_',' ',$order['product_type'] ?? '')) ?></div>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Fabric -->
                                    <div class="col-md-6">
                                        <?php if ($order['fabric_name']): ?>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <?php if ($order['fabric_image'] && file_exists("uploads/fabrics/".$order['fabric_image'])): ?>
                                                <img src="uploads/fabrics/<?= htmlspecialchars($order['fabric_image']) ?>"
                                                     class="fabric-swatch-lg" alt="">
                                            <?php else: ?>
                                                <div class="fabric-swatch-lg" style="background:<?= htmlspecialchars($order['fabric_swatch'] ?? '#ccc') ?>;"></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-semibold"><?= htmlspecialchars($order['fabric_name']) ?></div>
                                                <div class="text-muted small"><?= htmlspecialchars($order['fabric_material'] ?? '') ?></div>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                            <span class="text-muted small">No fabric selected</span>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Colors -->
                                    <div class="col-12">
                                        <div class="d-flex flex-wrap gap-3">
                                            <?php
                                            $color_items = [
                                                ['label'=>'Outer Fabric', 'name'=>$order['outer_color'],  'hex'=>$order['outer_hex'],  'border'=>'solid'],
                                                ['label'=>'Lining',       'name'=>$order['lining_color'], 'hex'=>$order['lining_hex'], 'border'=>'dashed'],
                                                ['label'=>'Button',       'name'=>$order['button_color'], 'hex'=>$order['button_hex'], 'border'=>'solid'],
                                            ];
                                            foreach ($color_items as $ci):
                                                if (!$ci['name']) continue;
                                            ?>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="swatch-lg" style="background:<?= htmlspecialchars($ci['hex'] ?? '#ccc') ?>;border-style:<?= $ci['border'] ?>;" title="<?= htmlspecialchars($ci['name']) ?>"></span>
                                                <div>
                                                    <div style="font-size:10px;color:#6c757d;"><?= $ci['label'] ?></div>
                                                    <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($ci['name']) ?></div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customization options -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-sliders-h me-2 text-muted"></i>Style Customizations</span>
                                <span class="badge bg-secondary"><?= count($custom_options) ?> options</span>
                            </div>
                            <div class="detail-card-body">
                                <?php if (empty($custom_options)): ?>
                                    <p class="text-muted mb-0 small">No customization options recorded.</p>
                                <?php else: ?>
                                    <div style="display:flex;flex-wrap:wrap;gap:8px;">
                                        <?php foreach ($custom_options as $group => $value): ?>
                                            <div class="option-chip">
                                                <div>
                                                    <span class="option-chip-group"><?= htmlspecialchars($group) ?></span>
                                                    <span class="option-chip-val"><?= htmlspecialchars($value) ?></span>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($order['special_note'])): ?>
                                    <div class="mt-3 p-3" style="background:#fff3cd;border-radius:8px;border-left:3px solid #f39c12;">
                                        <div class="fw-semibold mb-1" style="font-size:12px;color:#856404;">
                                            <i class="fas fa-sticky-note me-1"></i>Customer Special Note
                                        </div>
                                        <p class="mb-0" style="font-size:13px;"><?= nl2br(htmlspecialchars($order['special_note'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Measurements -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-ruler me-2 text-muted"></i>Customer Measurements</span>
                                <?php if ($meas && !$is_print): ?>
                                    <button class="btn btn-sm btn-outline-primary no-print"
                                            data-bs-toggle="collapse" data-bs-target="#editMeasCollapse">
                                        <i class="fas fa-edit me-1"></i>Edit
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div class="detail-card-body">
                                <?php if (!$meas): ?>
                                    <div class="text-center py-3 text-muted">
                                        <i class="fas fa-ruler-combined fa-2x mb-2 d-block opacity-25"></i>
                                        <p class="mb-0 small">No measurements recorded for this order.</p>
                                        <?php if (!$is_print): ?>
                                        <p class="small text-muted mt-1">Ask customer to provide their measurements.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <!-- Profile name -->
                                    <?php if ($meas['profile_name']): ?>
                                        <div class="mb-3">
                                            <span class="badge bg-light text-dark border">
                                                <i class="fas fa-user me-1"></i><?= htmlspecialchars($meas['profile_name']) ?>
                                            </span>
                                            <?php if ($meas['notes']): ?>
                                                <span class="text-muted small ms-2"><?= htmlspecialchars($meas['notes']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Measurements by section -->
                                    <?php foreach ($MEASUREMENTS as $section => $fields): ?>
                                        <div class="meas-section-title"><?= $section ?></div>
                                        <div class="meas-grid mb-3">
                                            <?php foreach ($fields as $key => $label):
                                                $val = $meas[$key] ?? null;
                                            ?>
                                                <div class="meas-item">
                                                    <div class="meas-label"><?= $label ?></div>
                                                    <div class="meas-val <?= !$val ? 'empty' : '' ?>">
                                                        <?= $val ? number_format((float)$val, 1) : '—' ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>

                                    <!-- Edit form (collapsible) -->
                                    <?php if (!$is_print): ?>
                                    <div class="collapse mt-3 no-print" id="editMeasCollapse">
                                        <div class="border rounded p-3 bg-light">
                                            <h6 class="fw-semibold mb-3">Edit Measurements</h6>
                                            <form action="" method="POST">
                                                <input type="hidden" name="measurement_id" value="<?= $meas['id'] ?>">
                                                <?php foreach ($MEASUREMENTS as $section => $fields): ?>
                                                    <div class="meas-section-title"><?= $section ?></div>
                                                    <div class="row g-2 mb-3">
                                                        <?php foreach ($fields as $key => $label): ?>
                                                            <div class="col-6 col-md-3">
                                                                <label class="form-label" style="font-size:11px;"><?= $label ?></label>
                                                                <input type="number" class="form-control form-control-sm"
                                                                       name="<?= $key ?>"
                                                                       value="<?= $meas[$key] ?? '' ?>"
                                                                       step="0.1" placeholder="—">
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="mb-3">
                                                    <label class="form-label" style="font-size:11px;">Tailor Notes</label>
                                                    <textarea class="form-control form-control-sm" name="notes" rows="2"><?= htmlspecialchars($meas['notes'] ?? '') ?></textarea>
                                                </div>
                                                <button type="submit" name="update_measurements" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-save me-1"></i>Save Measurements
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Admin notes -->
                        <?php if (!$is_print): ?>
                        <div class="detail-card no-print">
                            <div class="detail-card-header">
                                <span><i class="fas fa-sticky-note me-2 text-muted"></i>Admin / Tailor Notes</span>
                            </div>
                            <div class="detail-card-body">
                                <form action="" method="POST">
                                    <textarea class="form-control" name="special_note" rows="3"
                                              placeholder="Internal notes for tailor, special instructions..."><?= htmlspecialchars($order['special_note'] ?? '') ?></textarea>
                                    <button type="submit" name="save_notes" class="btn btn-sm btn-outline-secondary mt-2">
                                        <i class="fas fa-save me-1"></i>Save Notes
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div><!-- LEFT col -->

                    <!-- RIGHT -->
                    <div class="col-lg-4">

                        <!-- Price breakdown -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-receipt me-2 text-muted"></i>Price Breakdown</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="price-row">
                                    <span class="text-muted">Base Price</span>
                                    <span>₹<?= number_format((float)$order['base_price'],2) ?></span>
                                </div>
                                <div class="price-row">
                                    <span class="text-muted">Fabric Cost</span>
                                    <span class="<?= (float)$order['fabric_cost']>0 ? 'text-danger' : '' ?>">
                                        <?= (float)$order['fabric_cost']>0 ? '+' : '' ?>₹<?= number_format((float)$order['fabric_cost'],2) ?>
                                    </span>
                                </div>
                                <div class="price-row">
                                    <span class="text-muted">Customization Cost</span>
                                    <span class="<?= (float)$order['customization_cost']>0 ? 'text-danger' : '' ?>">
                                        <?= (float)$order['customization_cost']>0 ? '+' : '' ?>₹<?= number_format((float)$order['customization_cost'],2) ?>
                                    </span>
                                </div>
                                <div class="price-row total">
                                    <span>Total (×<?= (int)$order['quantity'] ?>)</span>
                                    <span>₹<?= number_format((float)$order['total_price'],2) ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Customer info -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-user me-2 text-muted"></i>Customer</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="fw-semibold mb-1" style="font-size:15px;"><?= htmlspecialchars($cust) ?></div>
                                <?php if ($order['user_email']): ?>
                                    <div class="text-muted small mb-1">
                                        <i class="fas fa-envelope me-1"></i><?= htmlspecialchars($order['user_email']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['mobile']): ?>
                                    <div class="text-muted small">
                                        <i class="fas fa-phone me-1"></i><?= htmlspecialchars($order['mobile']) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($order['user_id']): ?>
                                    <div class="mt-2">
                                        <span class="badge bg-light text-muted border" style="font-size:10px;">
                                            User ID: <?= $order['user_id'] ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Delivery date -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-calendar-check me-2 text-muted"></i>Expected Delivery</span>
                            </div>
                            <div class="detail-card-body">
                                <?php if ($order['expected_delivery']): ?>
                                    <div class="fw-bold mb-1 <?= $delivery_overdue ? 'text-danger' : ($delivery_soon ? 'text-warning' : 'text-success') ?>"
                                         style="font-size:18px;">
                                        <?= date('d M Y', strtotime($order['expected_delivery'])) ?>
                                    </div>
                                    <?php if ($delivery_overdue): ?>
                                        <span class="badge bg-danger">Overdue</span>
                                    <?php elseif ($delivery_soon): ?>
                                        <span class="badge bg-warning text-dark">Due soon</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">On track</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small">Not set</span>
                                <?php endif; ?>

                                <?php if (!$is_print): ?>
                                <form action="" method="POST" class="mt-3 no-print">
                                    <input type="date" class="form-control form-control-sm mb-2"
                                           name="expected_delivery"
                                           value="<?= htmlspecialchars($order['expected_delivery'] ?? '') ?>">
                                    <button type="submit" name="update_delivery" class="btn btn-sm btn-outline-secondary w-100">
                                        <i class="fas fa-calendar me-1"></i>Set Delivery Date
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Order meta -->
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span><i class="fas fa-info-circle me-2 text-muted"></i>Order Info</span>
                            </div>
                            <div class="detail-card-body">
                                <table class="table table-sm mb-0" style="font-size:12px;">
                                    <tr><td class="text-muted">Order ID</td><td class="fw-semibold">#<?= $id ?></td></tr>
                                    <tr><td class="text-muted">Created</td><td><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td></tr>
                                    <?php if ($order['updated_at']): ?>
                                    <tr><td class="text-muted">Updated</td><td><?= date('d M Y, h:i A', strtotime($order['updated_at'])) ?></td></tr>
                                    <?php endif; ?>
                                    <tr><td class="text-muted">Quantity</td><td><?= (int)$order['quantity'] ?></td></tr>
                                    <tr><td class="text-muted">Measurements</td>
                                        <td><?= $meas ? '<span class="text-success">✓ Saved</span>' : '<span class="text-danger">✗ Missing</span>' ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <?php if (!$is_print): ?>
                        <div class="no-print">
                            <button onclick="window.print()" class="btn btn-outline-dark w-100">
                                <i class="fas fa-print me-2"></i>Print Order Sheet
                            </button>
                        </div>
                        <?php endif; ?>

                    </div><!-- RIGHT col -->
                </div><!-- row -->

                <!-- Print footer -->
                <?php if ($is_print): ?>
                <div class="border-top mt-4 pt-3 text-center text-muted" style="font-size:11px;">
                    This is a custom tailored order. Please verify all measurements before cutting fabric.<br>
                    Printed on <?= date('d M Y, h:i A') ?>
                </div>
                <?php endif; ?>

            </div>
        </div>

    <?php if (!$is_print) include "footer.php"; ?>
    <?php if (!$is_print): ?>
    </section>
    <?php endif; ?>

    <?php if ($is_print): ?>
    <script>window.onload = () => window.print();</script>
    <?php endif; ?>
</body>
</html>