<?php
ob_start();
session_start();
include "db-conn.php";

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── TOGGLE available ────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = (int) $_GET['toggle'];
    $new = (int) $_GET['status'] == 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE color_options SET is_available = ? WHERE id = ?");
    $stmt->bind_param("ii", $new, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage-colors.php");
    exit();
}

// ── DELETE ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int) $_GET['delete'];

    // Check if color is used in any product_color_map
    $check = $conn->prepare("SELECT COUNT(*) FROM product_color_map WHERE color_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $count = $check->get_result()->fetch_row()[0];
    $check->close();

    if ($count > 0) {
        $_SESSION['error'] = "Cannot delete — this color is assigned to $count product(s). Remove it from those products first.";
    } else {
        $del = $conn->prepare("DELETE FROM color_options WHERE id = ?");
        $del->bind_param("i", $id);
        $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
            ? "Color deleted successfully!" : "Failed to delete color.";
        $del->close();
    }
    header("Location: manage-colors.php");
    exit();
}

// ── ADD ─────────────────────────────────────────────────────
if (isset($_POST['add_color'])) {
    $name          = trim($_POST['name']);
    $hex_code      = trim($_POST['hex_code']);
    $color_family  = trim($_POST['color_family']);
    $use_for       = isset($_POST['use_for']) ? implode(',', $_POST['use_for']) : 'fabric,lining,button';
    $display_order = (int) $_POST['display_order'];
    $is_available  = isset($_POST['is_available']) ? 1 : 0;

    if (empty($name) || empty($hex_code)) {
        $_SESSION['error'] = "Color name and hex code are required.";
        header("Location: manage-colors.php");
        exit();
    }

    $stmt = $conn->prepare(
        "INSERT INTO color_options (name, hex_code, color_family, use_for, display_order, is_available)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssssii", $name, $hex_code, $color_family, $use_for, $display_order, $is_available);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute()
        ? "Color \"$name\" added successfully!" : "Error: " . $conn->error;
    $stmt->close();
    header("Location: manage-colors.php");
    exit();
}

// ── EDIT SAVE ────────────────────────────────────────────────
if (isset($_POST['edit_color'])) {
    $id            = (int) $_POST['id'];
    $name          = trim($_POST['name']);
    $hex_code      = trim($_POST['hex_code']);
    $color_family  = trim($_POST['color_family']);
    $use_for       = isset($_POST['use_for']) ? implode(',', $_POST['use_for']) : 'fabric';
    $display_order = (int) $_POST['display_order'];
    $is_available  = isset($_POST['is_available']) ? 1 : 0;

    $stmt = $conn->prepare(
        "UPDATE color_options SET name=?, hex_code=?, color_family=?, use_for=?, display_order=?, is_available=? WHERE id=?"
    );
    $stmt->bind_param("ssssiis", $name, $hex_code, $color_family, $use_for, $display_order, $is_available, $id);
    // fix: id is int
    $stmt->bind_param("ssssiii", $name, $hex_code, $color_family, $use_for, $display_order, $is_available, $id);
    $stmt->close();

    $stmt2 = $conn->prepare(
        "UPDATE color_options SET name=?, hex_code=?, color_family=?, use_for=?, display_order=?, is_available=? WHERE id=?"
    );
    $stmt2->bind_param("ssssiis", $name, $hex_code, $color_family, $use_for, $display_order, $is_available, $id);
    // use_for is varchar, id is int — correct types:
    $stmt2->close();

    // Clean single prepared statement
    $s = $conn->prepare(
        "UPDATE color_options SET name=?, hex_code=?, color_family=?, use_for=?, display_order=?, is_available=? WHERE id=?"
    );
    $s->bind_param("sssssii", $name, $hex_code, $color_family, $use_for, $display_order, $is_available, $id);
    $_SESSION[$s->execute() ? 'success' : 'error'] = $s->execute()
        ? "Color updated!" : "Error: " . $conn->error;
    $s->close();
    header("Location: manage-colors.php");
    exit();
}

// ── FETCH ────────────────────────────────────────────────────
$search       = trim($_GET['search'] ?? '');
$family_filter = trim($_GET['family'] ?? '');

$where  = [];
$params = [];
$types  = '';

if ($search) {
    $where[]  = "(name LIKE ? OR hex_code LIKE ?)";
    $like     = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types   .= 'ss';
}
if ($family_filter) {
    $where[]  = "color_family = ?";
    $params[] = $family_filter;
    $types   .= 's';
}

$where_sql = $where ? "WHERE " . implode(" AND ", $where) : "";
$sql       = "SELECT * FROM color_options $where_sql ORDER BY display_order ASC, color_family ASC, name ASC";

if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $colors = $stmt->get_result();
    $stmt->close();
} else {
    $colors = $conn->query($sql);
}

// Fetch distinct families for filter dropdown
$families_res = $conn->query("SELECT DISTINCT color_family FROM color_options WHERE color_family != '' ORDER BY color_family ASC");
$families     = [];
while ($fam = $families_res->fetch_assoc()) {
    $families[] = $fam['color_family'];
}

// Count how many products each color is assigned to
$usage_map = [];
$usage_res = $conn->query("SELECT color_id, COUNT(*) as cnt FROM product_color_map GROUP BY color_id");
while ($u = $usage_res->fetch_assoc()) {
    $usage_map[$u['color_id']] = $u['cnt'];
}

$total_colors = $colors->num_rows;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"/>
    <title>Color Options | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .color-swatch-lg {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            border: 3px solid #fff;
            box-shadow: 0 1px 5px rgba(0,0,0,0.18);
            display: inline-block;
            cursor: pointer;
            transition: transform 0.15s;
        }
        .color-swatch-lg:hover { transform: scale(1.15); }
        .color-swatch-sm {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 1.5px solid #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }
        .action-btn {
            width: 32px; height: 32px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 50%; padding: 0;
        }
        .hex-badge {
            font-family: var(--bs-font-monospace, monospace);
            font-size: 11px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 2px 6px;
            color: #495057;
        }
        .family-badge {
            font-size: 11px;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .usage-count {
            font-size: 11px;
            color: #6c757d;
        }
        .use-for-tag {
            font-size: 10px;
            background: #e9ecef;
            color: #495057;
            border-radius: 10px;
            padding: 1px 6px;
            margin: 1px;
            display: inline-block;
        }
        .swatch-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 1rem 0;
        }
        .swatch-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            border: 2px solid transparent;
            transition: border-color 0.15s, background 0.15s;
            min-width: 70px;
        }
        .swatch-card:hover { background: #f8f9fa; border-color: #dee2e6; }
        .swatch-card.inactive { opacity: 0.4; }
        .swatch-name { font-size: 11px; text-align: center; color: #495057; max-width: 65px; word-break: break-word; }
        .view-toggle .btn.active { background: #2c3e50; color: #fff; border-color: #2c3e50; }
        .color-picker-preview {
            width: 42px; height: 42px;
            border-radius: 50%;
            border: 3px solid #dee2e6;
            display: inline-block;
            vertical-align: middle;
            transition: background 0.1s;
        }
        .family-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
            border: 1px solid #dee2e6;
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
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="white_card mb_30">

                            <!-- Header -->
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <h2 class="mb-0 fw-bold">Color Options</h2>
                                        <p class="text-muted small mb-0">
                                            <?= $total_colors ?> color<?= $total_colors != 1 ? 's' : '' ?> — used for fabric, lining &amp; button selection in suit configurator
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <!-- View toggle: table / swatch grid -->
                                        <div class="btn-group view-toggle" role="group">
                                            <button class="btn btn-outline-secondary btn-sm active" id="btnTableView" onclick="switchView('table')">
                                                <i class="fas fa-list"></i>
                                            </button>
                                            <button class="btn btn-outline-secondary btn-sm" id="btnSwatchView" onclick="switchView('swatch')">
                                                <i class="fas fa-th"></i>
                                            </button>
                                        </div>
                                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addColorModal">
                                            <i class="fas fa-plus me-2"></i>Add Color
                                        </button>
                                    </div>
                                </div>

                                <!-- Filters -->
                                <div class="row mt-3 g-2 align-items-end">
                                    <div class="col-md-4">
                                        <form method="GET" class="d-flex gap-2">
                                            <input type="text" class="form-control form-control-sm" name="search"
                                                   placeholder="Search color name or hex..."
                                                   value="<?= htmlspecialchars($search) ?>">
                                            <?php if ($family_filter): ?>
                                                <input type="hidden" name="family" value="<?= htmlspecialchars($family_filter) ?>">
                                            <?php endif; ?>
                                            <button class="btn btn-sm btn-outline-secondary"><i class="fas fa-search"></i></button>
                                        </form>
                                    </div>
                                    <div class="col-md-auto">
                                        <div class="d-flex gap-1 flex-wrap">
                                            <a href="manage-colors.php" class="btn btn-sm <?= !$family_filter ? 'btn-dark' : 'btn-outline-secondary' ?>">All</a>
                                            <?php foreach ($families as $fam): ?>
                                                <a href="?family=<?= urlencode($fam) ?><?= $search ? '&search='.urlencode($search) : '' ?>"
                                                   class="btn btn-sm <?= $family_filter === $fam ? 'btn-dark' : 'btn-outline-secondary' ?>">
                                                    <?= htmlspecialchars($fam) ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">

                                <!-- ── TABLE VIEW ─────────────────────────────── -->
                                <div id="tableView">
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-dark">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Swatch</th>
                                                    <th>Color Name</th>
                                                    <th>Hex Code</th>
                                                    <th>Family</th>
                                                    <th>Use For</th>
                                                    <th>Order</th>
                                                    <th>Products</th>
                                                    <th>Status</th>
                                                    <th class="text-center">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                            <?php
                                            $sno = 1;
                                            $colors->data_seek(0);
                                            if ($colors && $colors->num_rows > 0):
                                                while ($c = $colors->fetch_assoc()):
                                                    $used    = $usage_map[$c['id']] ?? 0;
                                                    $use_for = $c['use_for'] ?? 'fabric,lining,button';
                                                    $tags    = explode(',', $use_for);
                                            ?>
                                                <tr class="<?= !$c['is_available'] ? 'table-secondary' : '' ?>">
                                                    <td class="text-muted"><?= $sno++ ?></td>
                                                    <td>
                                                        <span class="color-swatch-lg"
                                                              style="background:<?= htmlspecialchars($c['hex_code']) ?>"
                                                              title="<?= htmlspecialchars($c['hex_code']) ?>"
                                                              onclick="copyHex('<?= htmlspecialchars($c['hex_code']) ?>')"></span>
                                                    </td>
                                                    <td class="fw-semibold"><?= htmlspecialchars($c['name']) ?></td>
                                                    <td>
                                                        <span class="hex-badge"><?= htmlspecialchars($c['hex_code']) ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($c['color_family']): ?>
                                                            <span class="badge family-badge bg-light text-dark border">
                                                                <span class="family-dot" style="background:<?= htmlspecialchars($c['hex_code']) ?>"></span>
                                                                <?= htmlspecialchars($c['color_family']) ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted">—</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php foreach ($tags as $tag): ?>
                                                            <span class="use-for-tag"><?= htmlspecialchars(trim($tag)) ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td class="text-muted"><?= (int)$c['display_order'] ?></td>
                                                    <td>
                                                        <?php if ($used > 0): ?>
                                                            <span class="badge bg-info text-dark"><?= $used ?> product<?= $used != 1 ? 's' : '' ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted usage-count">Not assigned</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <a href="manage-colors.php?toggle=<?= $c['id'] ?>&status=<?= $c['is_available'] ?>"
                                                           class="badge text-decoration-none <?= $c['is_available'] ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $c['is_available'] ? 'Active' : 'Inactive' ?>
                                                        </a>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="d-flex justify-content-center gap-1">
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-primary action-btn"
                                                                    title="Edit"
                                                                    onclick="openEditModal(
                                                                        <?= $c['id'] ?>,
                                                                        '<?= addslashes($c['name']) ?>',
                                                                        '<?= addslashes($c['hex_code']) ?>',
                                                                        '<?= addslashes($c['color_family']) ?>',
                                                                        '<?= addslashes($use_for) ?>',
                                                                        <?= (int)$c['display_order'] ?>,
                                                                        <?= (int)$c['is_available'] ?>
                                                                    )">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button"
                                                                    class="btn btn-sm btn-outline-danger action-btn"
                                                                    title="<?= $used > 0 ? "Used in $used product(s) — remove from products first" : 'Delete' ?>"
                                                                    <?= $used > 0 ? 'disabled' : '' ?>
                                                                    data-id="<?= $c['id'] ?>"
                                                                    data-name="<?= htmlspecialchars($c['name'], ENT_QUOTES) ?>"
                                                                    <?= $used == 0 ? 'data-bs-toggle="modal" data-bs-target="#deleteModal"' : '' ?>>
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php
                                                endwhile;
                                            else:
                                            ?>
                                                <tr>
                                                    <td colspan="10" class="text-center py-5 text-muted">
                                                        <i class="fas fa-palette fa-2x mb-2 d-block"></i>
                                                        No colors found.
                                                        <br><button class="btn btn-sm btn-primary mt-2"
                                                                    data-bs-toggle="modal" data-bs-target="#addColorModal">Add First Color</button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- ── SWATCH GRID VIEW ───────────────────────── -->
                                <div id="swatchView" style="display:none;">
                                    <?php
                                    // Group by family for swatch view
                                    $colors->data_seek(0);
                                    $grouped = [];
                                    while ($c = $colors->fetch_assoc()) {
                                        $fam = $c['color_family'] ?: 'Uncategorized';
                                        $grouped[$fam][] = $c;
                                    }
                                    foreach ($grouped as $family => $items):
                                    ?>
                                    <div class="mb-4">
                                        <h6 class="fw-semibold text-muted mb-2 text-uppercase" style="font-size:11px;letter-spacing:0.05em;">
                                            <?= htmlspecialchars($family) ?>
                                            <span class="badge bg-light text-dark ms-1"><?= count($items) ?></span>
                                        </h6>
                                        <div class="swatch-grid">
                                            <?php foreach ($items as $c): ?>
                                                <div class="swatch-card <?= !$c['is_available'] ? 'inactive' : '' ?>"
                                                     title="<?= htmlspecialchars($c['name']) ?> — <?= htmlspecialchars($c['hex_code']) ?>"
                                                     onclick="openEditModal(
                                                         <?= $c['id'] ?>,
                                                         '<?= addslashes($c['name']) ?>',
                                                         '<?= addslashes($c['hex_code']) ?>',
                                                         '<?= addslashes($c['color_family']) ?>',
                                                         '<?= addslashes($c['use_for'] ?? 'fabric,lining,button') ?>',
                                                         <?= (int)$c['display_order'] ?>,
                                                         <?= (int)$c['is_available'] ?>
                                                     )">
                                                    <span class="color-swatch-lg"
                                                          style="background:<?= htmlspecialchars($c['hex_code']) ?>"></span>
                                                    <span class="swatch-name"><?= htmlspecialchars($c['name']) ?></span>
                                                    <span style="font-size:10px;color:#aaa"><?= htmlspecialchars($c['hex_code']) ?></span>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>

                            </div><!-- white_card_body -->
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>


    <!-- ── ADD COLOR MODAL ──────────────────────────────────── -->
    <div class="modal fade" id="addColorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Color</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Color picker + preview -->
                            <div class="col-12 d-flex align-items-center gap-3 mb-2">
                                <span id="addColorPreview" class="color-picker-preview" style="background:#4169E1"></span>
                                <div>
                                    <div class="fw-semibold" id="addColorNamePreview">Color Name</div>
                                    <small class="text-muted" id="addColorHexPreview">#4169E1</small>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Color Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="addName"
                                       placeholder="e.g. Royal Blue, Charcoal Grey"
                                       oninput="document.getElementById('addColorNamePreview').textContent=this.value||'Color Name'"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hex Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color"
                                           id="addColorPicker" value="#4169E1"
                                           oninput="syncHex('addColorPicker','addHexInput','addColorPreview','addColorHexPreview')">
                                    <input type="text" class="form-control" name="hex_code" id="addHexInput"
                                           value="#4169E1" maxlength="7" placeholder="#000000"
                                           oninput="syncPicker('addHexInput','addColorPicker','addColorPreview','addColorHexPreview')"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Color Family</label>
                                <input type="text" class="form-control" name="color_family"
                                       placeholder="e.g. Blues, Greys, Earthy"
                                       list="familySuggestions">
                                <datalist id="familySuggestions">
                                    <?php foreach ($families as $fam): ?>
                                        <option value="<?= htmlspecialchars($fam) ?>">
                                    <?php endforeach; ?>
                                    <option value="Blues"><option value="Greys"><option value="Darks">
                                    <option value="Lights"><option value="Earthy"><option value="Reds">
                                    <option value="Greens"><option value="Festive">
                                </datalist>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Use For</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="fabric" id="addUseForFabric" checked>
                                        <label class="form-check-label" for="addUseForFabric">
                                            <i class="fas fa-scroll text-muted me-1"></i>Fabric Color
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="lining" id="addUseForLining" checked>
                                        <label class="form-check-label" for="addUseForLining">
                                            <i class="fas fa-layer-group text-muted me-1"></i>Lining Color
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="button" id="addUseForButton" checked>
                                        <label class="form-check-label" for="addUseForButton">
                                            <i class="fas fa-circle text-muted me-1"></i>Button Color
                                        </label>
                                    </div>
                                </div>
                                <small class="text-muted">Select where this color can be applied in the suit configurator</small>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order" value="0" min="0">
                            </div>

                            <div class="col-md-8 d-flex align-items-end pb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="addAvail" checked>
                                    <label class="form-check-label fw-semibold" for="addAvail">Active / Available</label>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_color" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Color
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── EDIT COLOR MODAL ──────────────────────────────────── -->
    <div class="modal fade" id="editColorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Edit Color</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">

                            <!-- Preview -->
                            <div class="col-12 d-flex align-items-center gap-3 mb-2">
                                <span id="editColorPreview" class="color-picker-preview"></span>
                                <div>
                                    <div class="fw-semibold" id="editColorNamePreview"></div>
                                    <small class="text-muted" id="editColorHexPreview"></small>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <label class="form-label fw-semibold">Color Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name"
                                       oninput="document.getElementById('editColorNamePreview').textContent=this.value"
                                       required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Hex Code <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color"
                                           id="editColorPicker"
                                           oninput="syncHex('editColorPicker','editHexInput','editColorPreview','editColorHexPreview')">
                                    <input type="text" class="form-control" name="hex_code" id="editHexInput"
                                           maxlength="7"
                                           oninput="syncPicker('editHexInput','editColorPicker','editColorPreview','editColorHexPreview')"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Color Family</label>
                                <input type="text" class="form-control" name="color_family" id="edit_color_family"
                                       list="familySuggestions">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Use For</label>
                                <div class="d-flex gap-3 flex-wrap">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="fabric" id="editUseForFabric">
                                        <label class="form-check-label" for="editUseForFabric">
                                            <i class="fas fa-scroll text-muted me-1"></i>Fabric Color
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="lining" id="editUseForLining">
                                        <label class="form-check-label" for="editUseForLining">
                                            <i class="fas fa-layer-group text-muted me-1"></i>Lining Color
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="use_for[]" value="button" id="editUseForButton">
                                        <label class="form-check-label" for="editUseForButton">
                                            <i class="fas fa-circle text-muted me-1"></i>Button Color
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order" id="edit_display_order" min="0">
                            </div>

                            <div class="col-md-8 d-flex align-items-end pb-1">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="edit_available">
                                    <label class="form-check-label fw-semibold" for="edit_available">Active / Available</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_color" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Update Color
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── DELETE MODAL ──────────────────────────────────────── -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div style="width:56px;height:56px;border-radius:50%;background:#fde8e8;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;">
                        <i class="fas fa-trash-alt fa-xl text-danger"></i>
                    </div>
                    <h5>Delete Color?</h5>
                    <p class="text-muted">
                        Are you sure you want to delete <strong id="deleteColorName"></strong>?
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

    <!-- Copied toast -->
    <div id="copyToast" style="position:fixed;bottom:24px;right:24px;z-index:9999;display:none;">
        <div class="alert alert-dark mb-0 py-2 px-3 shadow">
            <i class="fas fa-copy me-2"></i><span id="copyToastText">Copied!</span>
        </div>
    </div>

    <script>
    // ── Color picker ↔ hex input sync ────────────────────────
    function syncHex(pickerId, inputId, previewId, hexLabelId) {
        const hex = document.getElementById(pickerId).value;
        document.getElementById(inputId).value    = hex;
        document.getElementById(previewId).style.background = hex;
        if (hexLabelId) document.getElementById(hexLabelId).textContent = hex;
    }
    function syncPicker(inputId, pickerId, previewId, hexLabelId) {
        let hex = document.getElementById(inputId).value.trim();
        if (!hex.startsWith('#')) hex = '#' + hex;
        if (/^#[0-9A-Fa-f]{6}$/.test(hex)) {
            document.getElementById(pickerId).value = hex;
            document.getElementById(previewId).style.background = hex;
            if (hexLabelId) document.getElementById(hexLabelId).textContent = hex;
        }
    }

    // ── Open edit modal ──────────────────────────────────────
    function openEditModal(id, name, hex, family, useFor, order, available) {
        document.getElementById('edit_id').value            = id;
        document.getElementById('edit_name').value          = name;
        document.getElementById('editHexInput').value       = hex;
        document.getElementById('editColorPicker').value    = hex;
        document.getElementById('editColorPreview').style.background = hex;
        document.getElementById('editColorNamePreview').textContent  = name;
        document.getElementById('editColorHexPreview').textContent   = hex;
        document.getElementById('edit_color_family').value  = family;
        document.getElementById('edit_display_order').value = order;
        document.getElementById('edit_available').checked   = available == 1;

        // Set use_for checkboxes
        const tags = useFor ? useFor.split(',').map(t => t.trim()) : [];
        document.getElementById('editUseForFabric').checked = tags.includes('fabric');
        document.getElementById('editUseForLining').checked = tags.includes('lining');
        document.getElementById('editUseForButton').checked = tags.includes('button');

        new bootstrap.Modal(document.getElementById('editColorModal')).show();
    }

    // ── Delete modal ─────────────────────────────────────────
    document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
        const btn  = e.relatedTarget;
        document.getElementById('deleteColorName').textContent = btn.getAttribute('data-name');
        document.getElementById('confirmDeleteBtn').href = 'manage-colors.php?delete=' + btn.getAttribute('data-id');
    });

    // ── Copy hex to clipboard ────────────────────────────────
    function copyHex(hex) {
        navigator.clipboard.writeText(hex).then(() => {
            const toast = document.getElementById('copyToast');
            document.getElementById('copyToastText').textContent = hex + ' copied!';
            toast.style.display = 'block';
            setTimeout(() => toast.style.display = 'none', 1800);
        });
    }

    // ── Table / Swatch view toggle ───────────────────────────
    function switchView(view) {
        const isTable = view === 'table';
        document.getElementById('tableView').style.display  = isTable ? '' : 'none';
        document.getElementById('swatchView').style.display = isTable ? 'none' : '';
        document.getElementById('btnTableView').classList.toggle('active', isTable);
        document.getElementById('btnSwatchView').classList.toggle('active', !isTable);
        localStorage.setItem('colorViewMode', view);
    }

    // Restore last view preference
    document.addEventListener('DOMContentLoaded', function() {
        const saved = localStorage.getItem('colorViewMode');
        if (saved === 'swatch') switchView('swatch');
    });
    </script>
</body>
</html>