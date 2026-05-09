<?php
include "db-conn.php";
// Delete brand
if (isset($_GET['deleteId'])) {
    $delete_id = intval($_GET['deleteId']);
    $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: view_brands.php");
    exit;
}

// Fetch all brands
$result = $conn->query("SELECT * FROM brands ORDER BY created_at DESC");

?>

<!DOCTYPE html>
<html lang="zxx">

<head>

    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Sales</title>
    <link rel="icon" href="assets/img/logo.png" type="image/png">

    <?php include "links.php"; ?>
</head>

<body class="crm_body_bg">

    <?php include "header.php"; ?>
    <section class="main_content dashboard_part large_header_bg">

        <div class="container-fluid g-0">
            <div class="row">
                <div class="col-lg-12 p-0">
                    <?php include "top_nav.php"; ?>
                </div>
            </div>
        </div>

        <div class="main_content_iner">
            <div class="container-fluid p-0">
                <div class="row justify-content-center">
                    <div class="col-12">
                        <div class="white_card card_height_100 mb_30">
                            <div class="card-header bg-white border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h2 class="mb-1 fw-bold"><i class="fas fa-trademark me-2"></i>Manage Brands</h2>
                                        <p class="text-muted mb-0 small">Manage your product brands - add, edit, and delete brand information</p>
                                    </div>
                                    <div>
                                        <a href="our-best-brand.php" class="btn btn-primary btn-sm">
                                            <i class="fas fa-plus me-2"></i>Add New Brand
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Search Filter -->
                            <div class="card-header bg-light border-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="searchInput" placeholder="Search brands..." onkeyup="searchBrands()">
                                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <div class="btn-group" role="group">
                                            <input type="radio" class="btn-check" name="statusFilter" id="all" autocomplete="off" checked>
                                            <label class="btn btn-outline-secondary" for="all">All</label>

                                            <input type="radio" class="btn-check" name="statusFilter" id="active" autocomplete="off">
                                            <label class="btn btn-outline-success" for="active">Active</label>

                                            <input type="radio" class="btn-check" name="statusFilter" id="inactive" autocomplete="off">
                                            <label class="btn btn-outline-danger" for="inactive">Inactive</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-hover lms_table_active" id="brandsTable">
                                        <thead>
                                            <tr class="bg-light">
                                                <th scope="col" width="5%">#</th>
                                                <th scope="col" width="15%">Brand Name</th>
                                                <th scope="col" width="20%">Logo</th>
                                                <th scope="col" width="15%">Slug</th>
                                                <th scope="col" width="10%">Status</th>
                                                <th scope="col" width="15%">Created Date</th>
                                                <th scope="col" width="20%" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            $counter = 1;
                                            while ($brand = $result->fetch_assoc()):
                                            ?>
                                                <tr data-brand-name="<?= strtolower(htmlspecialchars($brand['brand_name'])); ?>" data-status="active">
                                                    <td><?= $counter++; ?></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($brand['brand_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <?php if (!empty($brand['logo_path']) && file_exists($brand['logo_path'])): ?>
                                                            <img src="<?= htmlspecialchars($brand['logo_path']); ?>"
                                                                alt="<?= htmlspecialchars($brand['brand_name']); ?>"
                                                                class="img-thumbnail"
                                                                style="max-width: 60px; max-height: 60px; object-fit: cover; cursor: pointer;"
                                                                data-bs-toggle="modal"
                                                                data-bs-target="#logoModal"
                                                                data-logo-src="<?= htmlspecialchars($brand['logo_path']); ?>"
                                                                data-brand-name="<?= htmlspecialchars($brand['brand_name']); ?>">
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">No Logo</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <code><?= htmlspecialchars($brand['slug'] ?? $brand['brand_name']); ?></code>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-success">Active</span>
                                                    </td>
                                                    <td>
                                                        <small><?= date('d M Y', strtotime($brand['created_at'] ?? 'now')); ?></small>
                                                    </td>
                                                    <td class="text-center">
                                                        <div class="btn-group" role="group">
                                                            <a href="edit-brand.php?id=<?= $brand['id']; ?>" class="btn btn-sm btn-warning" data-bs-toggle="tooltip" title="Edit Brand">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $brand['id']; ?>" data-bs-toggle="tooltip" title="Delete Brand">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </div>

                                                        <!-- Delete Confirmation Modal -->
                                                        <div class="modal fade" id="deleteModal<?= $brand['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $brand['id']; ?>" aria-hidden="true">
                                                            <div class="modal-dialog modal-dialog-centered">
                                                                <div class="modal-content">
                                                                    <div class="modal-header bg-danger text-white">
                                                                        <h5 class="modal-title" id="deleteModalLabel<?= $brand['id']; ?>">
                                                                            <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                                                                        </h5>
                                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body text-center py-4">
                                                                        <i class="fas fa-question-circle fa-4x text-warning mb-3"></i>
                                                                        <p class="mb-0 fs-5">Are you sure you want to delete the brand</p>
                                                                        <p class="mb-0 fw-bold text-danger mt-2">"<?= htmlspecialchars($brand['brand_name']); ?>"</p>
                                                                        <p class="text-muted small mt-3">This action cannot be undone!</p>
                                                                    </div>
                                                                    <div class="modal-footer justify-content-center">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                                            <i class="fas fa-times me-1"></i>Cancel
                                                                        </button>
                                                                        <a href="?delete_id=<?= $brand['id']; ?>" class="btn btn-danger">
                                                                            <i class="fas fa-trash-alt me-1"></i>Yes, Delete
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <!-- End Modal -->
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>

                                            <?php if (isset($brand) && $brand === null): ?>
                                                <tr>
                                                    <td colspan="7" class="text-center py-5">
                                                        <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                                        <p class="text-muted mb-0">No brands found</p>
                                                        <a href="our-best-brand.php" class="btn btn-primary btn-sm mt-3">
                                                            <i class="fas fa-plus me-1"></i>Add Your First Brand
                                                        </a>
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

        <!-- Logo Preview Modal -->
        <div class="modal fade" id="logoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-image me-2"></i>Brand Logo Preview</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-4">
                        <img src="" id="modalLogo" alt="Brand Logo" class="img-fluid" style="max-height: 300px; object-fit: contain;">
                        <p class="mt-3 fw-bold" id="modalBrandName"></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <a href="#" id="downloadLogoBtn" class="btn btn-primary" download>
                            <i class="fas fa-download me-1"></i>Download Logo
                        </a>
                    </div>
                </div>
            </div>
        </div>



        <script>
            // Search functionality
            function searchBrands() {
                let input = document.getElementById('searchInput');
                let filter = input.value.toLowerCase();
                let table = document.getElementById('brandsTable');
                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) {
                    let tdBrand = tr[i].getElementsByTagName('td')[1];
                    if (tdBrand) {
                        let brandName = tdBrand.textContent || tdBrand.innerText;
                        if (brandName.toLowerCase().indexOf(filter) > -1) {
                            tr[i].style.display = '';
                        } else {
                            tr[i].style.display = 'none';
                        }
                    }
                }
            }

            // Status filter functionality
            document.addEventListener('DOMContentLoaded', function() {
                const statusRadios = document.querySelectorAll('input[name="statusFilter"]');
                statusRadios.forEach(radio => {
                    radio.addEventListener('change', function() {
                        const selectedStatus = this.id;
                        filterByStatus(selectedStatus);
                    });
                });
            });

            function filterByStatus(status) {
                let table = document.getElementById('brandsTable');
                let tr = table.getElementsByTagName('tr');

                for (let i = 1; i < tr.length; i++) {
                    let statusCell = tr[i].getElementsByTagName('td')[4];
                    if (statusCell) {
                        let statusText = statusCell.textContent || statusCell.innerText;
                        if (status === 'all') {
                            tr[i].style.display = '';
                        } else if (status === 'active' && statusText.includes('Active')) {
                            tr[i].style.display = '';
                        } else if (status === 'inactive' && statusText.includes('Inactive')) {
                            tr[i].style.display = '';
                        } else {
                            tr[i].style.display = 'none';
                        }
                    }
                }
            }

            // Logo Modal Preview
            const logoModal = document.getElementById('logoModal');
            if (logoModal) {
                logoModal.addEventListener('show.bs.modal', function(event) {
                    const button = event.relatedTarget;
                    const logoSrc = button.getAttribute('data-logo-src');
                    const brandName = button.getAttribute('data-brand-name');

                    const modalImage = document.getElementById('modalLogo');
                    const modalBrandName = document.getElementById('modalBrandName');
                    const downloadBtn = document.getElementById('downloadLogoBtn');

                    modalImage.src = logoSrc;
                    modalBrandName.textContent = brandName;
                    downloadBtn.href = logoSrc;
                    downloadBtn.setAttribute('download', brandName + '_logo.png');
                });
            }

            // Tooltip initialization
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Enter key search
            document.getElementById('searchInput').addEventListener('keyup', function(event) {
                if (event.key === 'Enter') {
                    searchBrands();
                }
            });
        </script>

        <?php include "footer.php"; ?>