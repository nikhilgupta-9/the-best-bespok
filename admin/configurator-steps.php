<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── AUTO-CREATE configurator_steps table if not exists ───────
$conn->query("
    CREATE TABLE IF NOT EXISTS `configurator_steps` (
        `id`          INT(11)      NOT NULL AUTO_INCREMENT,
        `group_name`  VARCHAR(100) NOT NULL COMMENT 'Matches customization_options.group_name',
        `step_label`  VARCHAR(150) NOT NULL COMMENT 'Customer-facing label shown in configurator',
        `step_order`  INT(11)      NOT NULL DEFAULT 0,
        `applies_to`  SET('jacket','trouser','waistcoat','all') DEFAULT 'all',
        `is_required` TINYINT(1)   NOT NULL DEFAULT 1,
        `is_visible`  TINYINT(1)   NOT NULL DEFAULT 1,
        `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at`  DATETIME     DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_group` (`group_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// ── AUTO-SEED: if table empty, insert from customization_options groups ───
$count = $conn->query("SELECT COUNT(*) FROM configurator_steps")->fetch_row()[0];
if ($count == 0) {
    // Pull all distinct groups from customization_options
    $groups = $conn->query(
        "SELECT DISTINCT group_name FROM customization_options ORDER BY MIN(display_order) ASC, group_name ASC"
    );
    // Default labels and applies_to per known group
    $defaults = [
        'Lapel Style'    => ['Choose your lapel style',        'jacket',    1, 1],
        'Button Count'   => ['Select button style',            'jacket',    1, 1],
        'Pocket Style'   => ['Choose pocket style',            'jacket',    1, 1],
        'Vent Style'     => ['Select back vent style',         'jacket',    1, 1],
        'Chest Pocket'   => ['Chest pocket preference',        'jacket',    1, 1],
        'Lining'         => ['Select jacket lining',           'jacket',    1, 1],
        'Sleeve Buttons' => ['Sleeve button detail',           'jacket',    1, 1],
        'Pick Stitch'    => ['Pick stitch detail',             'jacket',    0, 1],
        'Waistcoat'      => ['Add a waistcoat?',               'waistcoat', 0, 1],
        'Trouser Fit'    => ['Choose trouser fit',             'trouser',   1, 1],
        'Trouser Style'  => ['Trouser pleat style',            'trouser',   1, 1],
        'Side Pocket'    => ['Side pocket style',              'trouser',   1, 1],
        'Back Pocket'    => ['Back pocket preference',         'trouser',   0, 1],
        'Trouser Bottom' => ['Trouser bottom & cuff style',    'trouser',   1, 1],
        'Trouser Lining' => ['Trouser lining',                 'trouser',   1, 1],
        'Monogram'       => ['Add monogram / embroidery',      'jacket',    0, 1],
    ];
    $order = 1;
    $ins   = $conn->prepare(
        "INSERT IGNORE INTO configurator_steps (group_name, step_label, step_order, applies_to, is_required, is_visible)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    while ($g = $groups->fetch_assoc()) {
        $gn  = $g['group_name'];
        $def = $defaults[$gn] ?? [$gn, 'all', 1, 1];
        $ins->bind_param("sssiii", $gn, $def[0], $order, $def[1], $def[2], $def[3]);
        $ins->execute();
        $order++;
    }
    $ins->close();
    $_SESSION['success'] = "Configurator steps auto-created from your customization option groups!";
}

// ── AJAX: save drag-drop order ───────────────────────────────
if (isset($_POST['action']) && $_POST['action'] === 'reorder') {
    header('Content-Type: application/json');
    $order = json_decode($_POST['order'] ?? '[]', true);
    if (is_array($order)) {
        $stmt = $conn->prepare("UPDATE configurator_steps SET step_order=? WHERE id=?");
        foreach ($order as $position => $id) {
            $pos = $position + 1;
            $sid = (int)$id;
            $stmt->bind_param("ii", $pos, $sid);
            $stmt->execute();
        }
        $stmt->close();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid order data']);
    }
    exit();
}

// ── TOGGLE visible ───────────────────────────────────────────
if (isset($_GET['toggle_visible'])) {
    $id  = (int)$_GET['toggle_visible'];
    $new = (int)$_GET['val'] == 1 ? 0 : 1;
    $s   = $conn->prepare("UPDATE configurator_steps SET is_visible=? WHERE id=?");
    $s->bind_param("ii", $new, $id);
    $s->execute(); $s->close();
    header("Location: configurator-steps.php");
    exit();
}

// ── TOGGLE required ──────────────────────────────────────────
if (isset($_GET['toggle_required'])) {
    $id  = (int)$_GET['toggle_required'];
    $new = (int)$_GET['val'] == 1 ? 0 : 1;
    $s   = $conn->prepare("UPDATE configurator_steps SET is_required=? WHERE id=?");
    $s->bind_param("ii", $new, $id);
    $s->execute(); $s->close();
    header("Location: configurator-steps.php");
    exit();
}

// ── ADD NEW STEP ─────────────────────────────────────────────
if (isset($_POST['add_step'])) {
    $group_name = trim($_POST['group_name']);
    $step_label = trim($_POST['step_label']);
    $applies_to = trim($_POST['applies_to']);
    $is_required= isset($_POST['is_required']) ? 1 : 0;
    $is_visible = isset($_POST['is_visible'])  ? 1 : 0;

    // Next order number
    $max = $conn->query("SELECT MAX(step_order) FROM configurator_steps")->fetch_row()[0];
    $next_order = (int)$max + 1;

    $s = $conn->prepare(
        "INSERT INTO configurator_steps (group_name, step_label, step_order, applies_to, is_required, is_visible)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $s->bind_param("sssiii", $group_name, $step_label, $next_order, $applies_to, $is_required, $is_visible);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Step \"$step_label\" added!" : "Error: " . $conn->error;
    $s->close();
    header("Location: configurator-steps.php");
    exit();
}

// ── EDIT SAVE ────────────────────────────────────────────────
if (isset($_POST['edit_step'])) {
    $id         = (int)$_POST['id'];
    $group_name = trim($_POST['group_name']);
    $step_label = trim($_POST['step_label']);
    $applies_to = trim($_POST['applies_to']);
    $is_required= isset($_POST['is_required']) ? 1 : 0;
    $is_visible = isset($_POST['is_visible'])  ? 1 : 0;

    $s = $conn->prepare(
        "UPDATE configurator_steps SET group_name=?, step_label=?, applies_to=?, is_required=?, is_visible=? WHERE id=?"
    );
    $s->bind_param("sssiii", $group_name, $step_label, $applies_to, $is_required, $is_visible, $id);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Step updated!" : "Error: " . $conn->error;
    $s->close();
    header("Location: configurator-steps.php");
    exit();
}

// ── DELETE STEP ──────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $del = $conn->prepare("DELETE FROM configurator_steps WHERE id=?");
    $del->bind_param("i", $id);
    $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
        ? "Step removed." : "Delete failed.";
    $del->close();
    // Reorder remaining steps to close gaps
    $conn->query("SET @r=0; UPDATE configurator_steps SET step_order=(@r:=@r+1) ORDER BY step_order ASC");
    header("Location: configurator-steps.php");
    exit();
}

// ── FETCH ALL STEPS ──────────────────────────────────────────
$steps = $conn->query(
    "SELECT cs.*,
            (SELECT COUNT(*) FROM customization_options co WHERE co.group_name = cs.group_name) AS option_count,
            (SELECT COUNT(*) FROM customization_options co WHERE co.group_name = cs.group_name AND co.is_available=1) AS active_options
     FROM configurator_steps cs
     ORDER BY cs.step_order ASC"
);
$all_steps = [];
while ($s = $steps->fetch_assoc()) $all_steps[] = $s;

// Groups available in customization_options (for add/edit dropdowns)
$opt_groups = $conn->query(
    "SELECT DISTINCT group_name FROM customization_options ORDER BY group_name ASC"
);
$available_groups = [];
while ($g = $opt_groups->fetch_assoc()) $available_groups[] = $g['group_name'];

// Steps summary counts
$total   = count($all_steps);
$visible = count(array_filter($all_steps, fn($s) => $s['is_visible']));
$jacket  = count(array_filter($all_steps, fn($s) => str_contains($s['applies_to'], 'jacket')));
$trouser = count(array_filter($all_steps, fn($s) => str_contains($s['applies_to'], 'trouser')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Configurator Steps | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <!-- SortableJS for drag-drop reorder -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <style>
        /* ── Stats bar ───────────────────────────── */
        .stat-card {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .stat-icon {
            width: 44px; height: 44px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; flex-shrink: 0;
        }
        .stat-val { font-size: 22px; font-weight: 700; line-height: 1; }
        .stat-lbl { font-size: 12px; color: #6c757d; margin-top: 2px; }

        /* ── Step row (drag handle) ──────────────── */
        .step-row {
            background: #fff;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 0;
            overflow: hidden;
            transition: box-shadow 0.18s, border-color 0.18s;
            cursor: default;
        }
        .step-row:hover { box-shadow: 0 3px 14px rgba(0,0,0,0.08); border-color: #ced4da; }
        .step-row.sortable-ghost { opacity: 0.4; border: 2px dashed #6c63ff; }
        .step-row.sortable-chosen { box-shadow: 0 6px 20px rgba(0,0,0,0.15); border-color: #6c63ff; z-index: 100; }
        .step-row.inactive-step { background: #f8f9fa; opacity: 0.65; }

        .drag-handle {
            width: 44px;
            display: flex; align-items: center; justify-content: center;
            color: #adb5bd; font-size: 16px;
            cursor: grab; align-self: stretch;
            border-right: 1px solid #f0f0f0;
            flex-shrink: 0;
        }
        .drag-handle:active { cursor: grabbing; }

        .step-number {
            width: 36px; height: 36px;
            border-radius: 50%;
            background: #2c3e50;
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-size: 13px; font-weight: 700;
            flex-shrink: 0; margin: 0 14px;
        }
        .step-number.inactive { background: #adb5bd; }

        .step-body { flex: 1; padding: 12px 4px; min-width: 0; }
        .step-label { font-size: 14px; font-weight: 600; color: #2c3e50; margin: 0 0 3px; }
        .step-group { font-size: 12px; color: #6c757d; margin: 0; }
        .step-meta  { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 5px; }

        .tag {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; padding: 2px 8px; border-radius: 20px;
            font-weight: 500;
        }
        .tag-jacket    { background: #e3f2fd; color: #0d6efd; }
        .tag-trouser   { background: #e8f5e9; color: #198754; }
        .tag-waistcoat { background: #fff3e0; color: #fd7e14; }
        .tag-all       { background: #f3e5f5; color: #6f42c1; }
        .tag-required  { background: #fde8e8; color: #dc3545; }
        .tag-optional  { background: #f0f0f0; color: #6c757d; }
        .tag-options   { background: #e9ecef; color: #495057; }

        .step-actions { padding: 0 14px; display: flex; gap: 6px; flex-shrink: 0; }
        .action-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; padding: 0; font-size: 13px;
        }

        /* ── Applies-to selector ────────────────── */
        .applies-btn-group .btn { border-radius: 6px !important; font-size: 12px; }
        .applies-btn-group .btn.active {
            background: #2c3e50; border-color: #2c3e50; color: #fff;
        }

        /* ── Preview panel ──────────────────────── */
        .preview-panel {
            background: #1a1a2e;
            border-radius: 12px;
            padding: 20px;
            color: #fff;
            height: 100%;
        }
        .preview-step {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 10px; border-radius: 8px;
            margin-bottom: 6px;
            transition: background 0.15s;
        }
        .preview-step.active-preview { background: rgba(255,255,255,0.12); }
        .preview-step-num {
            width: 24px; height: 24px; border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 600; flex-shrink: 0;
        }
        .preview-step-num.done { background: #27ae60; }
        .preview-step-label { font-size: 12px; flex: 1; }
        .preview-step-badge { font-size: 10px; opacity: 0.6; }
        .preview-title { font-size: 13px; font-weight: 500; opacity: 0.5; text-transform: uppercase;
                         letter-spacing: 0.06em; margin-bottom: 12px; }

        /* ── Save order button ──────────────────── */
        #saveOrderBtn {
            display: none;
            position: fixed;
            bottom: 24px; right: 24px;
            z-index: 999;
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

                <!-- ── Stats ─────────────────────────────────── -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:#e8f4fd;">
                                <i class="fas fa-list-ol" style="color:#0d6efd;"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $total ?></div>
                                <div class="stat-lbl">Total Steps</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:#e8f5e9;">
                                <i class="fas fa-eye" style="color:#198754;"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $visible ?></div>
                                <div class="stat-lbl">Visible to Customer</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:#e3f2fd;">
                                <i class="fas fa-tshirt" style="color:#0d6efd;"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $jacket ?></div>
                                <div class="stat-lbl">Jacket Steps</div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-icon" style="background:#fff3e0;">
                                <i class="fas fa-ruler-vertical" style="color:#fd7e14;"></i>
                            </div>
                            <div>
                                <div class="stat-val"><?= $trouser ?></div>
                                <div class="stat-lbl">Trouser Steps</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3">

                    <!-- ── LEFT: Step list ────────────────────── -->
                    <div class="col-lg-8">
                        <div class="white_card mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0 fw-bold">Configurator Steps</h2>
                                        <p class="text-muted small mb-0">
                                            <i class="fas fa-grip-vertical me-1"></i>
                                            Drag rows to reorder — this controls the step sequence in the frontend configurator
                                        </p>
                                    </div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                                        <i class="fas fa-plus me-2"></i>Add Step
                                    </button>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <?php if (empty($all_steps)): ?>
                                    <div class="text-center py-5 text-muted">
                                        <i class="fas fa-list-ol fa-3x mb-3 d-block"></i>
                                        <h5>No steps yet</h5>
                                        <p>Add steps to define the order of the suit configurator.</p>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStepModal">
                                            Add First Step
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div id="sortableSteps">
                                    <?php foreach ($all_steps as $step):
                                        $applies = $step['applies_to'];
                                        $tagClass = match(true) {
                                            str_contains($applies, 'jacket')    && !str_contains($applies, 'trouser') => 'tag-jacket',
                                            str_contains($applies, 'trouser')   && !str_contains($applies, 'jacket')  => 'tag-trouser',
                                            str_contains($applies, 'waistcoat') && !str_contains($applies, 'jacket')  => 'tag-waistcoat',
                                            default => 'tag-all'
                                        };
                                        $tagIcon = match(true) {
                                            str_contains($applies, 'jacket')    && !str_contains($applies, 'trouser') => 'fa-tshirt',
                                            str_contains($applies, 'trouser')   && !str_contains($applies, 'jacket')  => 'fa-ruler-vertical',
                                            str_contains($applies, 'waistcoat') && !str_contains($applies, 'jacket')  => 'fa-vest',
                                            default => 'fa-layer-group'
                                        };
                                    ?>
                                        <div class="step-row <?= !$step['is_visible'] ? 'inactive-step' : '' ?>"
                                             data-id="<?= $step['id'] ?>">

                                            <!-- Drag handle -->
                                            <div class="drag-handle" title="Drag to reorder">
                                                <i class="fas fa-grip-vertical"></i>
                                            </div>

                                            <!-- Step number -->
                                            <div class="step-number <?= !$step['is_visible'] ? 'inactive' : '' ?>">
                                                <?= (int)$step['step_order'] ?>
                                            </div>

                                            <!-- Step content -->
                                            <div class="step-body">
                                                <div class="step-label">
                                                    <?= htmlspecialchars($step['step_label']) ?>
                                                    <?php if (!$step['is_visible']): ?>
                                                        <span class="badge bg-secondary ms-1" style="font-size:10px;">Hidden</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="step-group">
                                                    <i class="fas fa-link me-1" style="font-size:10px;"></i>
                                                    <?= htmlspecialchars($step['group_name']) ?>
                                                </div>
                                                <div class="step-meta">
                                                    <!-- Applies to -->
                                                    <span class="tag <?= $tagClass ?>">
                                                        <i class="fas <?= $tagIcon ?>"></i>
                                                        <?= htmlspecialchars(ucfirst($applies)) ?>
                                                    </span>
                                                    <!-- Required / Optional -->
                                                    <span class="tag <?= $step['is_required'] ? 'tag-required' : 'tag-optional' ?>">
                                                        <?= $step['is_required'] ? '* Required' : 'Optional' ?>
                                                    </span>
                                                    <!-- Options count -->
                                                    <span class="tag tag-options">
                                                        <i class="fas fa-sliders-h"></i>
                                                        <?= (int)$step['active_options'] ?>/<?= (int)$step['option_count'] ?> options
                                                    </span>
                                                    <?php if ($step['option_count'] == 0): ?>
                                                        <span class="tag" style="background:#fff3cd;color:#856404;">
                                                            <i class="fas fa-exclamation-triangle"></i> No options linked
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="step-actions">
                                                <!-- Toggle visible -->
                                                <a href="?toggle_visible=<?= $step['id'] ?>&val=<?= $step['is_visible'] ?>"
                                                   class="btn btn-sm action-btn <?= $step['is_visible'] ? 'btn-outline-success' : 'btn-outline-secondary' ?>"
                                                   title="<?= $step['is_visible'] ? 'Hide from configurator' : 'Show in configurator' ?>">
                                                    <i class="fas <?= $step['is_visible'] ? 'fa-eye' : 'fa-eye-slash' ?>"></i>
                                                </a>
                                                <!-- Toggle required -->
                                                <a href="?toggle_required=<?= $step['id'] ?>&val=<?= $step['is_required'] ?>"
                                                   class="btn btn-sm action-btn <?= $step['is_required'] ? 'btn-outline-danger' : 'btn-outline-secondary' ?>"
                                                   title="<?= $step['is_required'] ? 'Mark as Optional' : 'Mark as Required' ?>">
                                                    <i class="fas <?= $step['is_required'] ? 'fa-asterisk' : 'fa-minus' ?>"></i>
                                                </a>
                                                <!-- Edit -->
                                                <button class="btn btn-sm btn-outline-primary action-btn"
                                                        title="Edit"
                                                        onclick="openEditModal(
                                                            <?= $step['id'] ?>,
                                                            '<?= addslashes($step['group_name']) ?>',
                                                            '<?= addslashes($step['step_label']) ?>',
                                                            '<?= addslashes($step['applies_to']) ?>',
                                                            <?= (int)$step['is_required'] ?>,
                                                            <?= (int)$step['is_visible'] ?>
                                                        )">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <!-- Delete -->
                                                <button class="btn btn-sm btn-outline-danger action-btn"
                                                        title="Remove step"
                                                        data-id="<?= $step['id'] ?>"
                                                        data-name="<?= htmlspecialchars($step['step_label'], ENT_QUOTES) ?>"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#deleteModal">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    </div><!-- #sortableSteps -->

                                    <div class="mt-3 d-flex align-items-center gap-2 text-muted" style="font-size:12px;">
                                        <i class="fas fa-info-circle"></i>
                                        Drag the <i class="fas fa-grip-vertical mx-1"></i> handle to reorder steps.
                                        Changes are saved automatically when you drop.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── RIGHT: Live preview ────────────────── -->
                    <div class="col-lg-4">
                        <div style="position: sticky; top: 20px;">
                            <div class="preview-panel">
                                <div class="preview-title">
                                    <i class="fas fa-mobile-alt me-2"></i>Customer View Preview
                                </div>
                                <?php
                                $visible_steps = array_filter($all_steps, fn($s) => $s['is_visible']);
                                $idx = 0;
                                foreach ($visible_steps as $step):
                                    $idx++;
                                    $isFirst = $idx === 1;
                                ?>
                                <div class="preview-step <?= $isFirst ? 'active-preview' : '' ?>">
                                    <div class="preview-step-num <?= $isFirst ? 'done' : '' ?>">
                                        <?php if ($isFirst): ?>
                                            <i class="fas fa-check" style="font-size:9px;"></i>
                                        <?php else: ?>
                                            <?= $idx ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="preview-step-label"><?= htmlspecialchars($step['step_label']) ?></div>
                                    <div class="preview-step-badge">
                                        <?php if (!$step['is_required']): ?>
                                            <span style="background:rgba(255,255,255,0.1);padding:1px 5px;border-radius:10px;font-size:9px;">opt</span>
                                        <?php endif; ?>
                                        <?php if ($step['option_count'] == 0): ?>
                                            <span style="background:#e74c3c;padding:1px 5px;border-radius:10px;font-size:9px;">!</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>

                                <?php if (empty($visible_steps)): ?>
                                    <div style="text-align:center;opacity:0.4;padding:20px 0;font-size:13px;">
                                        <i class="fas fa-eye-slash fa-2x mb-2 d-block"></i>
                                        No visible steps
                                    </div>
                                <?php endif; ?>

                                <!-- Legend -->
                                <div style="margin-top:16px;padding-top:12px;border-top:1px solid rgba(255,255,255,0.1);font-size:11px;opacity:0.6;">
                                    <div class="d-flex gap-3 flex-wrap">
                                        <span><span style="background:#27ae60;width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:4px;"></span>Active step</span>
                                        <span><span style="background:rgba(255,255,255,0.2);width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:4px;"></span>Upcoming</span>
                                        <span><span style="background:#e74c3c;padding:0 4px;border-radius:3px;font-size:9px;margin-right:4px;">!</span>No options</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick actions -->
                            <div class="white_card mt-3 p-3">
                                <h6 class="fw-semibold mb-3">Quick Actions</h6>
                                <div class="d-grid gap-2">
                                    <a href="customization-options.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-sliders-h me-2"></i>Manage Customization Options
                                    </a>
                                    <button class="btn btn-outline-secondary btn-sm"
                                            onclick="if(confirm('Reset step order to 1,2,3...? This will renumber all steps.')) location.href='?reset_order=1'">
                                        <i class="fas fa-sort-numeric-up me-2"></i>Renumber Steps (1,2,3...)
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div><!-- row -->
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>

    <!-- ── Floating save order button (shown after drag) ────── -->
    <button id="saveOrderBtn" class="btn btn-success btn-lg shadow-lg" onclick="saveOrder()">
        <i class="fas fa-save me-2"></i>Save New Order
    </button>

    <!-- ══════════════════════════════════════════════════════
         ADD STEP MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="addStepModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle me-2 text-primary"></i>Add Configurator Step
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Option Group <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" name="group_name" required>
                                    <option value="">— Select a group —</option>
                                    <?php foreach ($available_groups as $grp): ?>
                                        <option value="<?= htmlspecialchars($grp) ?>">
                                            <?= htmlspecialchars($grp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Links this step to options from <a href="customization-options.php">Customization Options</a></small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">
                                    Step Label (Customer Facing) <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" name="step_label"
                                       placeholder="e.g. Choose your lapel style" required>
                                <small class="text-muted">This is what the customer sees in the configurator</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Applies To</label>
                                <div class="d-flex gap-2 flex-wrap applies-btn-group" id="addAppliesToBtns">
                                    <?php foreach (['jacket','trouser','waistcoat','all'] as $part): ?>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm <?= $part === 'jacket' ? 'active' : '' ?>"
                                                onclick="setAppliesTo(this, 'addAppliesToInput', '<?= $part ?>')">
                                            <?= ucfirst($part) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="applies_to" id="addAppliesToInput" value="jacket">
                            </div>

                            <div class="col-12">
                                <div class="d-flex gap-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_required"
                                               id="addIsRequired" checked>
                                        <label class="form-check-label fw-semibold" for="addIsRequired">
                                            <span class="text-danger">*</span> Required Step
                                        </label>
                                        <div><small class="text-muted">Customer must select before proceeding</small></div>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_visible"
                                               id="addIsVisible" checked>
                                        <label class="form-check-label fw-semibold" for="addIsVisible">
                                            Visible
                                        </label>
                                        <div><small class="text-muted">Show in frontend configurator</small></div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_step" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i>Add Step
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════
         EDIT STEP MODAL
    ══════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editStepModal" tabindex="-1" aria-hidden="true" style="background: #14141491;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-edit me-2 text-warning"></i>Edit Step
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <div class="col-12">
                                <label class="form-label fw-semibold">Option Group <span class="text-danger">*</span></label>
                                <select class="form-select" name="group_name" id="edit_group_name" required>
                                    <?php foreach ($available_groups as $grp): ?>
                                        <option value="<?= htmlspecialchars($grp) ?>">
                                            <?= htmlspecialchars($grp) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Step Label <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="step_label"
                                       id="edit_step_label" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Applies To</label>
                                <div class="d-flex gap-2 flex-wrap applies-btn-group" id="editAppliesToBtns">
                                    <?php foreach (['jacket','trouser','waistcoat','all'] as $part): ?>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                onclick="setAppliesTo(this, 'editAppliesToInput', '<?= $part ?>')">
                                            <?= ucfirst($part) ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" name="applies_to" id="editAppliesToInput" value="jacket">
                            </div>

                            <div class="col-12">
                                <div class="d-flex gap-4">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_required" id="edit_is_required">
                                        <label class="form-check-label fw-semibold" for="edit_is_required">Required Step</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="is_visible" id="edit_is_visible">
                                        <label class="form-check-label fw-semibold" for="edit_is_visible">Visible</label>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_step" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i>Update Step
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
                    <h5 class="modal-title">Remove Step</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:60px;height:60px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-trash-alt fa-xl text-danger"></i>
                    </div>
                    <h5>Remove this step?</h5>
                    <p class="text-muted">
                        <strong id="deleteStepName"></strong><br>
                        <small class="text-danger">
                            This only removes the step from the configurator — it does NOT delete the customization options linked to it.
                        </small>
                    </p>
                </div>
                <div class="modal-footer justify-content-center border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Remove Step
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    // ── Drag & drop reorder ──────────────────────────────────
    let orderChanged = false;

    const sortable = Sortable.create(document.getElementById('sortableSteps'), {
        handle: '.drag-handle',
        animation: 180,
        easing: 'cubic-bezier(0.25, 1, 0.5, 1)',
        ghostClass: 'sortable-ghost',
        chosenClass: 'sortable-chosen',
        onEnd: function() {
            orderChanged = true;
            document.getElementById('saveOrderBtn').style.display = 'block';
            // Update visible step numbers
            document.querySelectorAll('.step-number').forEach((el, i) => {
                el.textContent = i + 1;
            });
        }
    });

    function saveOrder() {
        const items   = document.querySelectorAll('#sortableSteps .step-row');
        const ordered = Array.from(items).map(el => el.getAttribute('data-id'));

        fetch('configurator-steps.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=reorder&order=' + encodeURIComponent(JSON.stringify(ordered))
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('saveOrderBtn').style.display = 'none';
                orderChanged = false;
                showToast('success', 'Step order saved!');
            } else {
                showToast('danger', 'Failed to save order.');
            }
        })
        .catch(() => showToast('danger', 'Network error.'));
    }

    // Warn if leaving page with unsaved order
    window.addEventListener('beforeunload', e => {
        if (orderChanged) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ── Applies-to button group ───────────────────────────────
    function setAppliesTo(btn, inputId, value) {
        btn.closest('.applies-btn-group').querySelectorAll('.btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById(inputId).value = value;
    }

    // ── Open edit modal ───────────────────────────────────────
    function openEditModal(id, groupName, stepLabel, appliesTo, isRequired, isVisible) {
        document.getElementById('edit_id').value         = id;
        document.getElementById('edit_step_label').value = stepLabel;
        document.getElementById('edit_is_required').checked = isRequired == 1;
        document.getElementById('edit_is_visible').checked  = isVisible == 1;

        // Set group
        const groupSel = document.getElementById('edit_group_name');
        for (let o of groupSel.options) {
            if (o.value === groupName) { o.selected = true; break; }
        }

        // Set applies_to button
        document.getElementById('editAppliesToInput').value = appliesTo;
        document.querySelectorAll('#editAppliesToBtns .btn').forEach(btn => {
            btn.classList.toggle('active', btn.textContent.trim().toLowerCase() === appliesTo);
        });

        new bootstrap.Modal(document.getElementById('editStepModal')).show();
    }

    // ── Delete modal ──────────────────────────────────────────
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
        const btn = e.relatedTarget;
        document.getElementById('deleteStepName').textContent = btn.getAttribute('data-name');
        document.getElementById('confirmDeleteBtn').href = 'configurator-steps.php?delete=' + btn.getAttribute('data-id');
    });

    // ── Toast helper ──────────────────────────────────────────
    function showToast(type, msg) {
        const t = document.createElement('div');
        t.className = `alert alert-${type} alert-dismissible fade show position-fixed shadow`;
        t.style.cssText = 'top:20px;right:20px;z-index:9999;min-width:260px;';
        t.innerHTML = `<i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${msg}
                       <button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
        document.body.appendChild(t);
        setTimeout(() => t.remove(), 3500);
    }
    </script>
</body>
</html>