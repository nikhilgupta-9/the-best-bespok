<?php
ob_start();
session_start();
include "db-conn.php";
include_once "auth/login-check.php";


// ── DELETE ──────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id   = (int) $_GET['delete'];
    $stmt = $conn->prepare("SELECT image FROM fabric_options WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row && !empty($row['image']) && file_exists("uploads/fabrics/" . $row['image'])) {
        unlink("uploads/fabrics/" . $row['image']);
    }

    $del = $conn->prepare("DELETE FROM fabric_options WHERE id = ?");
    $del->bind_param("i", $id);
    $_SESSION[$del->execute() ? 'success' : 'error'] = $del->execute()
        ? "Fabric deleted successfully!" : "Failed to delete fabric.";
    $del->close();
    header("Location: manage-fabric.php");
    exit();
}

// ── TOGGLE available ────────────────────────────────────────
if (isset($_GET['toggle'])) {
    $id  = (int) $_GET['toggle'];
    $cur = (int) $_GET['status'];
    $new = $cur == 1 ? 0 : 1;
    $stmt = $conn->prepare("UPDATE fabric_options SET is_available = ? WHERE id = ?");
    $stmt->bind_param("ii", $new, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: manage-fabric.php");
    exit();
}

// ── ADD ─────────────────────────────────────────────────────
if (isset($_POST['add_fabric'])) {
    $name           = trim($_POST['name']);
    $material       = trim($_POST['material']);
    $description    = trim($_POST['description']);
    $price_modifier = (float) $_POST['price_modifier'];
    $swatch_color   = trim($_POST['swatch_color']);
    $display_order  = (int) $_POST['display_order'];
    $is_available   = isset($_POST['is_available']) ? 1 : 0;
    $image          = '';

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = "uploads/fabrics/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext      = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5000000) {
            $image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        } else {
            $_SESSION['error'] = "Invalid image. Use JPG/PNG/WEBP under 5MB.";
            header("Location: manage-fabric.php");
            exit();
        }
    }

    $stmt = $conn->prepare(
        "INSERT INTO fabric_options (name, material, description, price_modifier, swatch_color, image, display_order, is_available)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("sssdssi i", $name, $material, $description, $price_modifier, $swatch_color, $image, $display_order, $is_available);
    $stmt->bind_param("sssdssii", $name, $material, $description, $price_modifier, $swatch_color, $image, $display_order, $is_available);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute()
        ? "Fabric added successfully!" : "Error: " . $conn->error;
    $stmt->close();
    header("Location: manage-fabric.php");
    exit();
}

// ── EDIT SAVE ────────────────────────────────────────────────
if (isset($_POST['edit_fabric'])) {
    $id             = (int) $_POST['id'];
    $name           = trim($_POST['name']);
    $material       = trim($_POST['material']);
    $description    = trim($_POST['description']);
    $price_modifier = (float) $_POST['price_modifier'];
    $swatch_color   = trim($_POST['swatch_color']);
    $display_order  = (int) $_POST['display_order'];
    $is_available   = isset($_POST['is_available']) ? 1 : 0;
    $existing_image = trim($_POST['existing_image']);

    $image = $existing_image;

    if (!empty($_FILES['image']['name'])) {
        $upload_dir = "uploads/fabrics/";
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed) && $_FILES['image']['size'] <= 5000000) {
            if ($existing_image && file_exists($upload_dir . $existing_image)) {
                unlink($upload_dir . $existing_image);
            }
            $image = time() . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
        }
    }

    $stmt = $conn->prepare(
        "UPDATE fabric_options SET name=?, material=?, description=?, price_modifier=?, swatch_color=?, image=?, display_order=?, is_available=? WHERE id=?"
    );
    $stmt->bind_param("sssdssiii", $name, $material, $description, $price_modifier, $swatch_color, $image, $display_order, $is_available, $id);
    $_SESSION[$stmt->execute() ? 'success' : 'error'] = $stmt->execute()
        ? "Fabric updated!" : "Error: " . $conn->error;
    $stmt->close();
    header("Location: manage-fabric.php");
    exit();
}

// ── FETCH ALL ───────────────────────────────────────────────
$fabrics = $conn->query("SELECT * FROM fabric_options ORDER BY display_order ASC, id ASC");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Fabric Options | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .swatch {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            display: inline-block;
            vertical-align: middle;
        }

        .fabric-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .price-plus {
            color: #27ae60;
            font-weight: 600;
        }

        .price-minus {
            color: #e74c3c;
            font-weight: 600;
        }

        .price-zero {
            color: #6c757d;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
        }

        .modal-swatch-preview {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #dee2e6;
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
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

                <!-- Alerts -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($_SESSION['success']);
                                                                unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($_SESSION['error']);
                                                                        unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-12">
                        <div class="white_card mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-0 fw-bold">Fabric Options</h2>
                                        <p class="text-muted small mb-0">Manage fabrics available for suit customization</p>
                                    </div>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFabricModal">
                                        <i class="fas fa-plus me-2"></i>Add Fabric
                                    </button>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>#</th>
                                                <th>Swatch</th>
                                                <th>Fabric Name</th>
                                                <th>Material</th>
                                                <th>Price Modifier</th>
                                                <th>Order</th>
                                                <th>Status</th>
                                                <th class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $sno = 1;
                                            if ($fabrics && $fabrics->num_rows > 0):
                                                while ($f = $fabrics->fetch_assoc()):
                                                    $pm = (float)$f['price_modifier'];
                                                    $pm_class = $pm > 0 ? 'price-plus' : ($pm < 0 ? 'price-minus' : 'price-zero');
                                                    $pm_text  = $pm > 0 ? '+₹' . number_format($pm, 2) : ($pm < 0 ? '-₹' . number_format(abs($pm), 2) : 'Base price');
                                            ?>
                                                    <tr>
                                                        <td class="text-muted"><?= $sno++ ?></td>
                                                        <td>
                                                            <?php if (!empty($f['image']) && file_exists("uploads/fabrics/" . $f['image'])): ?>
                                                                <img src="uploads/fabrics/<?= htmlspecialchars($f['image']) ?>"
                                                                    class="fabric-img" alt="<?= htmlspecialchars($f['name']) ?>">
                                                            <?php elseif (!empty($f['swatch_color'])): ?>
                                                                <span class="swatch" style="background:<?= htmlspecialchars($f['swatch_color']) ?>"
                                                                    title="<?= htmlspecialchars($f['swatch_color']) ?>"></span>
                                                            <?php else: ?>
                                                                <span class="swatch" style="background:#ccc"></span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="fw-semibold"><?= htmlspecialchars($f['name']) ?></div>
                                                            <?php if (!empty($f['description'])): ?>
                                                                <small class="text-muted"><?= htmlspecialchars(substr($f['description'], 0, 60)) ?>...</small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= htmlspecialchars($f['material'] ?: '—') ?></td>
                                                        <td class="<?= $pm_class ?>"><?= $pm_text ?></td>
                                                        <td><?= (int)$f['display_order'] ?></td>
                                                        <td>
                                                            <a href="manage-fabric.php?toggle=<?= $f['id'] ?>&status=<?= $f['is_available'] ?>"
                                                                class="badge <?= $f['is_available'] ? 'bg-success' : 'bg-secondary' ?> text-decoration-none">
                                                                <?= $f['is_available'] ? 'Active' : 'Inactive' ?>
                                                            </a>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-flex justify-content-center gap-1">
                                                                <!-- Edit -->
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-primary action-btn"
                                                                    title="Edit"
                                                                    onclick='openEditModal(
                                                                        <?= (int)$f['id'] ?>,
                                                                        <?= json_encode($f['name']) ?>,
                                                                        <?= json_encode($f['material']) ?>,
                                                                        <?= json_encode($f['description']) ?>,
                                                                        <?= (float)$f['price_modifier'] ?>,
                                                                        <?= json_encode($f['swatch_color']) ?>,
                                                                        <?= (int)$f['display_order'] ?>,
                                                                        <?= (int)$f['is_available'] ?>,
                                                                        <?= json_encode($f['image']) ?>
                                                                    )'>
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <!-- Delete -->
                                                                <button type="button"
                                                                    class="btn btn-sm btn-outline-danger action-btn"
                                                                    title="Delete"
                                                                    data-id="<?= $f['id'] ?>"
                                                                    data-name="<?= htmlspecialchars($f['name'], ENT_QUOTES) ?>"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#deleteModal">
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
                                                    <td colspan="8" class="text-center py-5 text-muted">
                                                        <i class="fas fa-scroll fa-2x mb-2 d-block"></i>
                                                        No fabrics added yet.
                                                        <br><button class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#addFabricModal">Add First Fabric</button>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include "footer.php"; ?>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-kenU1KFdBIe4zVF0s0G1M5b4hcpxyD9F7jL+jjXkk+Q2h455rYXK/7HAuoJl+0I4" crossorigin="anonymous"></script>

    <!-- ── ADD MODAL ─────────────────────────────────────────── -->
    <div class="modal fade" id="addFabricModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-plus-circle me-2 text-primary"></i>Add New Fabric</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fabric Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" placeholder="e.g. Premium Pure Wool" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Material Type</label>
                                <input type="text" class="form-control" name="material" placeholder="e.g. Wool, Linen, Cotton">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier" value="0" step="0.01"
                                    placeholder="0 = base price, +500 = extra, -200 = discount">
                                <small class="text-muted">Positive = surcharge, Negative = discount, 0 = included</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Swatch Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color" name="swatch_color"
                                        value="#C4A882" id="addSwatchColor"
                                        oninput="document.getElementById('addSwatchPreview').style.background=this.value">
                                    <span id="addSwatchPreview" class="modal-swatch-preview" style="background:#C4A882"></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order" value="0" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" name="description" rows="2"
                                    placeholder="Brief description of this fabric..."></textarea>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Fabric Swatch Image</label>
                                <input type="file" class="form-control" name="image" accept="image/*"
                                    onchange="previewFabricImg(this, 'addImgPreview')">
                                <small class="text-muted">JPG, PNG, WEBP — max 5MB. Optional if swatch color is set.</small>
                                <div id="addImgPreview" class="mt-2"></div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end pb-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="addAvailable" checked>
                                    <label class="form-check-label fw-semibold" for="addAvailable">Active / Available</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_fabric" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Save Fabric
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- ── EDIT MODAL ────────────────────────────────────────── -->
    <div class="modal fade" id="editFabricModal" tabindex="-1" aria-hidden="true" style="background: #3d3d3d47;">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_id">
                    <input type="hidden" name="existing_image" id="edit_existing_image">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-edit me-2 text-warning"></i>Edit Fabric</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Fabric Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Material Type</label>
                                <input type="text" class="form-control" name="material" id="edit_material">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Price Modifier (₹)</label>
                                <input type="number" class="form-control" name="price_modifier" id="edit_price_modifier" step="0.01">
                                <small class="text-muted">Positive = surcharge, Negative = discount, 0 = included</small>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Swatch Color</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color" name="swatch_color"
                                        id="edit_swatch_color"
                                        oninput="document.getElementById('editSwatchPreview').style.background=this.value">
                                    <span id="editSwatchPreview" class="modal-swatch-preview"></span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-semibold">Display Order</label>
                                <input type="number" class="form-control" name="display_order" id="edit_display_order" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="2"></textarea>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-semibold">Replace Image (optional)</label>
                                <input type="file" class="form-control" name="image" accept="image/*"
                                    onchange="previewFabricImg(this, 'editImgPreview')">
                                <div id="editImgPreview" class="mt-2"></div>
                                <div id="editCurrentImg" class="mt-2"></div>
                            </div>
                            <div class="col-md-4 d-flex align-items-end pb-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_available" id="edit_available">
                                    <label class="form-check-label fw-semibold" for="edit_available">Active / Available</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="edit_fabric" class="btn btn-warning">
                            <i class="fas fa-save me-1"></i> Update Fabric
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
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3 d-block"></i>
                    <h5>Delete Fabric?</h5>
                    <p class="text-muted">Are you sure you want to delete <strong id="deleteFabricName"></strong>?<br>
                        <small class="text-danger">This cannot be undone and will remove the fabric from all products.</small>
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
        // Open edit modal and populate fields
        function openEditModal(id, name, material, description, price, swatchColor, order, available, image) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_material').value = material;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_price_modifier').value = price;
            document.getElementById('edit_swatch_color').value = swatchColor || '#C4A882';
            document.getElementById('editSwatchPreview').style.background = swatchColor || '#C4A882';
            document.getElementById('edit_display_order').value = order;
            document.getElementById('edit_available').checked = available == 1;
            document.getElementById('edit_existing_image').value = image;

            const currentImg = document.getElementById('editCurrentImg');
            if (image && image !== 'null' && image !== '') {
                currentImg.innerHTML = `<img src="uploads/fabrics/${image}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eee" alt="Current">
                                <small class="text-muted ms-2">Current image</small>`;
            } else {
                currentImg.innerHTML = '<small class="text-muted">No image uploaded</small>';
            }
            document.getElementById('editImgPreview').innerHTML = '';
            new bootstrap.Modal(document.getElementById('editFabricModal')).show();
        }

        // Image preview helper
        function previewFabricImg(input, targetId) {
            const target = document.getElementById(targetId);
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = e => {
                    target.innerHTML = `<img src="${e.target.result}" style="width:60px;height:60px;object-fit:cover;border-radius:6px;border:1px solid #eee">
                                    <small class="text-muted ms-2">New image preview</small>`;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Delete modal
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function(e) {
            const btn = e.relatedTarget;
            const id = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            document.getElementById('deleteFabricName').textContent = name;
            document.getElementById('confirmDeleteBtn').href = 'manage-fabric.php?delete=' + id;
        });

        // Tooltips
        document.querySelectorAll('[title]').forEach(el => new bootstrap.Tooltip(el));
    </script>
</body>

</html>