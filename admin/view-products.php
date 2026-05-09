<?php
ob_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "db-conn.php";

// Auth check
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// ── Pagination & Search ────────────────────────────────────────────────────
$search  = isset($_GET['search']) ? trim($_GET['search']) : '';
$page    = isset($_GET['page'])   ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// ── FIX 1: Raw string interpolation replaced with prepared statements ──────
// FIX 2: JOIN categories so we show category NAME not raw category_id int  ─
// FIX 3: countSql searched `category_id LIKE` (wrong) now searches pro_name─
// FIX 4: Removed `pro_id` reference — schema uses `id` as primary key      ─
if (!empty($search)) {
    $like = "%" . $search . "%";

    $countStmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM products
         WHERE pro_name LIKE ? OR id LIKE ?"
    );
    $countStmt->bind_param("ss", $like, $like);
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $stmt = $conn->prepare(
        "SELECT p.id, p.pro_name, p.pro_img, p.mrp, p.selling_price,
                p.status, p.product_type, p.is_customizable,
                c.name AS category_name,
                sc.name AS sub_category_name
         FROM products p
         LEFT JOIN categories c  ON p.category_id    = c.id
         LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
         WHERE p.pro_name LIKE ? OR p.id LIKE ?
         ORDER BY p.id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ssii", $like, $like, $perPage, $offset);
} else {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM products");
    $countStmt->execute();
    $totalRows = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();

    $stmt = $conn->prepare(
        "SELECT p.id, p.pro_name, p.pro_img, p.mrp, p.selling_price,
                p.status, p.product_type, p.is_customizable,
                c.name AS category_name,
                sc.name AS sub_category_name
         FROM products p
         LEFT JOIN categories c  ON p.category_id    = c.id
         LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id
         ORDER BY p.id DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $perPage, $offset);
}

$stmt->execute();
$result     = $stmt->get_result();
$totalPages = (int)ceil($totalRows / $perPage);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Product Management | Admin Panel</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">
    <?php include "links.php"; ?>
    <style>
        .search-box {
            position: relative;
            max-width: 400px;
        }

        .search-box input {
            padding-left: 40px;
            border-radius: 20px;
        }

        .search-box .search-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none; /* FIX 5: was clickable but querySelector broke when .search-box i missing */
        }

        .product-img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 6px;
            border: 1px solid #eee;
        }

        .action-btn {
            width: 34px;
            height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            padding: 0;
        }

        .table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            white-space: nowrap;
        }

        .badge-customizable {
            font-size: 11px;
            padding: 3px 8px;
        }

        .pagination .page-item.active .page-link {
            background-color: #2c3e50;
            border-color: #2c3e50;
        }

        /* Delete modal */
        .delete-icon-wrap {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: #fde8e8;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
    </style>
</head>

<body class="crm_body_bg">
    <?php include "header.php"; ?>

    <section class="main_content dashboard_part">
        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-3">

                <!-- Session alerts -->
                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (!empty($_SESSION['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card mb_30">

                            <!-- Header -->
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                    <div>
                                        <h2 class="mb-0 fw-bold">Product Management</h2>
                                        <p class="text-muted mb-0 small">
                                            <?= $totalRows ?> product<?= $totalRows != 1 ? 's' : '' ?> total
                                        </p>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                        <!-- FIX 6: Search was a <form> with no search icon wrapper —
                                             moved icon inside proper search-box div -->
                                        <form method="GET" class="search-box mb-0">
                                            <i class="fas fa-search search-icon"></i>
                                            <input type="text" class="form-control" name="search"
                                                placeholder="Search products..."
                                                value="<?= htmlspecialchars($search) ?>">
                                        </form>
                                        <a href="add-products.php" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add Product
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-bordered align-middle text-center">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ID</th>
                                                <th>Image</th>
                                                <th class="text-start">Product</th>
                                                <th>Category</th>
                                                <th>Sub-Category</th>
                                                <th>Type</th>
                                                <th>MRP</th>
                                                <th>Sale Price</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        <?php
                                        if ($result && $result->num_rows > 0):
                                            $sno = $offset + 1;
                                            while ($row = $result->fetch_assoc()):

                                                // FIX 7: pro_img is single filename now (not comma-separated)
                                                // but guard against old comma-separated data just in case
                                                $imgFile    = explode(',', $row['pro_img'] ?? '')[0] ;
                                                $imgSrc     = 'assets/img/uploads/' . htmlspecialchars($imgFile) ;
                                                $imgAlt     = htmlspecialchars($row['pro_name']);

                                                $statusBadge = $row['status'] == '1'
                                                    ? '<span class="badge bg-success">Active</span>'
                                                    : '<span class="badge bg-secondary">Inactive</span>';

                                                // Product type badge
                                                $typeMap = [
                                                    'ready_made'    => ['Ready Made',    'bg-info text-dark'],
                                                    'made_to_order' => ['Made to Order', 'bg-warning text-dark'],
                                                    'both'          => ['Both',           'bg-primary'],
                                                ];
                                                [$typeLabel, $typeCls] = $typeMap[$row['product_type']] ?? ['—', 'bg-light text-muted'];

                                                // Customizable flag
                                                $customBadge = $row['is_customizable']
                                                    ? ' <span class="badge bg-success bg-opacity-10 text-success badge-customizable">
                                                            <i class="fas fa-paint-brush me-1"></i>Custom
                                                        </span>'
                                                    : '';
                                        ?>
                                            <tr>
                                                <td class="text-muted"><?= $sno++ ?></td>

                                                <!-- FIX 8: was $row['pro_id'] — column is now `id` -->
                                                <td class="fw-bold text-muted">#<?= (int)$row['id'] ?></td>

                                                <!-- Image -->
                                                <td>
                                                    <?php
                                                    if($imgSrc){ 
                                                    ?>
                                                    <img src="<?= $imgSrc?? '' ?>"
                                                         alt="<?= $imgAlt ?>"
                                                         class="product-img"
                                                         >
                                                    <?php }else{
                                                        echo ' <i class="fas fa-images product-img"></i>';
                                                    } ?>
                                                </td>

                                                <!-- Product name + customizable badge -->
                                                <td class="text-start">
                                                    <div class="fw-semibold"><?= $imgAlt ?></div>
                                                    <?= $customBadge ?>
                                                </td>

                                                <!-- FIX 9: was $row['pro_cate'] (raw int) — now shows category NAME via JOIN -->
                                                <td>
                                                    <?= $row['category_name']
                                                        ? htmlspecialchars($row['category_name'])
                                                        : '<span class="text-muted">—</span>' ?>
                                                </td>

                                                <td>
                                                    <?= $row['sub_category_name']
                                                        ? htmlspecialchars($row['sub_category_name'])
                                                        : '<span class="text-muted">—</span>' ?>
                                                </td>

                                                <!-- Product type -->
                                                <td>
                                                    <span class="badge <?= $typeCls ?>"><?= $typeLabel ?></span>
                                                </td>

                                                <td><del class="text-muted">₹<?= number_format((float)$row['mrp'], 2) ?></del></td>
                                                <td class="fw-semibold text-success">₹<?= number_format((float)$row['selling_price'], 2) ?></td>
                                                <td><?= $statusBadge ?></td>

                                                <td>
                                                    <div class="d-flex justify-content-center gap-1">
                                                        <!-- Manage images -->
                                                        <a href="multiple_img.php?id=<?= (int)$row['id'] ?>"
                                                           class="btn btn-outline-secondary action-btn"
                                                           data-bs-toggle="tooltip" title="Manage Images">
                                                            <i class="fas fa-images"></i>
                                                        </a>
                                                        <!-- Edit -->
                                                        <!-- FIX 10: was ?edit_product_details=pro_id; now uses id -->
                                                        <a href="edit_products.php?id=<?= (int)$row['id'] ?>"
                                                           class="btn btn-outline-primary action-btn"
                                                           data-bs-toggle="tooltip" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <!-- Delete — triggers modal -->
                                                        <!-- FIX 11: was href to delete page directly (no confirm modal).
                                                             Now uses data attributes + modal for safe confirmation. -->
                                                        <button type="button"
                                                                class="btn btn-outline-danger action-btn"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#deleteModal"
                                                                data-id="<?= (int)$row['id'] ?>"
                                                                data-name="<?= htmlspecialchars($row['pro_name'], ENT_QUOTES) ?>"
                                                                title="Delete">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php
                                            endwhile;
                                        else:
                                        ?>
                                            <tr>
                                                <td colspan="11" class="text-center text-muted py-5">
                                                    <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                                    <?= !empty($search) ? 'No products matched "' . htmlspecialchars($search) . '"' : 'No products found.' ?>
                                                    <?php if (!empty($search)): ?>
                                                        <br><a href="view-products.php" class="btn btn-sm btn-outline-secondary mt-2">Clear Search</a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-2">
                                        <div class="text-muted small">
                                            Showing <?= $offset + 1 ?> – <?= min($offset + $perPage, $totalRows) ?>
                                            of <?= $totalRows ?> entries
                                        </div>
                                        <nav>
                                            <ul class="pagination mb-0">
                                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">
                                                        <i class="fas fa-chevron-left"></i>
                                                    </a>
                                                </li>
                                                <?php
                                                // Show at most 5 page links around current page
                                                $start = max(1, $page - 2);
                                                $end   = min($totalPages, $page + 2);
                                                if ($start > 1) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                                for ($i = $start; $i <= $end; $i++):
                                                ?>
                                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                                        <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
                                                    </li>
                                                <?php
                                                endfor;
                                                if ($end < $totalPages) echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                                                ?>
                                                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                                                    <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </nav>
                                    </div>
                                <?php endif; ?>

                            </div><!-- white_card_body -->
                        </div><!-- white_card -->
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include "footer.php"; ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center pb-0">
                    <div class="delete-icon-wrap">
                        <i class="fas fa-trash-alt fa-xl text-danger"></i>
                    </div>
                    <h5 class="mb-1">Delete Product?</h5>
                    <p class="text-muted">
                        Are you sure you want to delete
                        <strong id="deleteProductName"></strong>?
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
        // ── Delete modal ───────────────────────────────────────────────────
        document.getElementById('deleteModal').addEventListener('show.bs.modal', function (e) {
            const btn  = e.relatedTarget;
            const id   = btn.getAttribute('data-id');
            const name = btn.getAttribute('data-name');
            document.getElementById('deleteProductName').textContent = name;
            document.getElementById('confirmDeleteBtn').href = 'product_delete.php?id=' + id;
        });

        // ── Tooltips ───────────────────────────────────────────────────────
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
            new bootstrap.Tooltip(el);
        });

        // ── FIX 12: search icon click was `querySelector('.search-box i')`
        //    which throws if the element didn't exist. Now safely guarded. ─
        const searchIcon = document.querySelector('.search-icon');
        if (searchIcon) {
            searchIcon.addEventListener('click', function () {
                this.closest('.search-box').querySelector('input').focus();
            });
        }
    </script>
</body>
</html>