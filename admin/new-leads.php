<?php
session_start();
include "functions.php";
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Customer Inquiries | Admin Dashboard</title>
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
                                        <h2 class="mb-1 fw-bold">Customer Inquiries</h2>
                                        <p class="text-muted mb-0 small">Manage customer queries and product inquiries
                                        </p>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm" id="markAllRead">
                                            <i class="fas fa-check-double me-2"></i>Mark All as Read
                                        </button>
                                        <a href="#" class="btn btn-outline-primary btn-sm" id="exportBtn">
                                            <i class="fas fa-download me-2"></i>Export CSV
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Search and Filter Section -->
                            <div class="card-header bg-light border-0 py-3">
                                <div class="row align-items-center">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="searchInquiries"
                                                placeholder="Search inquiries...">
                                            <button class="btn btn-outline-secondary" type="button">
                                                <i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="row g-2">
                                            <div class="col-md-4">
                                                <select class="form-select" id="statusFilter">
                                                    <option value="all">All Status</option>
                                                    <option value="unread">Unread</option>
                                                    <option value="read">Read</option>
                                                    <option value="replied">Replied</option>
                                                    <option value="pending">Pending</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="date" class="form-control" id="dateFilter">
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-secondary w-100" id="clearFilters">
                                                    <i class="fas fa-times me-2"></i>Clear Filters
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="white_card_body">
                                <div class="table-responsive">
                                    <table class="table table-hover lms_table_active" id="inquiriesTable">
                                        <thead>
                                            <tr class="bg-light">
                                                <th scope="col" width="5%">#</th>
                                                <th scope="col" width="15%">Customer Details</th>
                                                <th scope="col" width="15%">Contact Info</th>
                                                <th scope="col" width="20%">Subject / Product</th>
                                                <th scope="col" width="25%">Message</th>
                                                <th scope="col" width="10%">Status</th>
                                                <th scope="col" width="10%" class="text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php echo get_Inquiries(); ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"
            integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p"
            crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.min.js"
            integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKTRIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF"
            crossorigin="anonymous"></script>
        <?php include "footer.php"; ?>
    </section>

    <!-- Inquiry Details Modal -->
    <div class="modal fade" id="inquiryModal" tabindex="-1" aria-labelledby="inquiryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="inquiryModalLabel">Inquiry Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Customer Information</h6>
                            <div class="mb-3">
                                <strong>Name:</strong> <span id="modalCustomerName"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong> <span id="modalCustomerEmail"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Phone:</strong> <span id="modalCustomerPhone"></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Inquiry Details</h6>
                            <div class="mb-3">
                                <strong>Subject:</strong> <span id="modalSubject"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Date:</strong> <span id="modalDate"></span>
                            </div>
                            <div class="mb-3">
                                <strong>Status:</strong> <span id="modalStatus"></span>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6 class="text-muted mb-2">Message</h6>
                            <div class="card bg-light">
                                <div class="card-body">
                                    <p id="modalMessage" class="mb-0"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-warning" id="markAsReadBtn">
                        <i class="fas fa-check me-2"></i>Mark as Read
                    </button>
                    <a href="#" class="btn btn-primary" id="replyBtn">
                        <i class="fas fa-reply me-2"></i>Reply
                    </a>
                    <a href="#" class="btn btn-danger" id="deleteInquiryBtn">
                        <i class="fas fa-trash me-2"></i>Delete
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });

            // Search functionality
            const searchInput = document.getElementById('searchInquiries');
            if (searchInput) {
                searchInput.addEventListener('keyup', function () {
                    const searchValue = this.value.toLowerCase();
                    const rows = document.querySelectorAll('#inquiriesTable tbody tr');

                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(searchValue) ? '' : 'none';
                    });
                });
            }

            // Status filter
            const statusFilter = document.getElementById('statusFilter');
            if (statusFilter) {
                statusFilter.addEventListener('change', function () {
                    filterInquiries();
                });
            }

            // Date filter
            const dateFilter = document.getElementById('dateFilter');
            if (dateFilter) {
                dateFilter.addEventListener('change', function () {
                    filterInquiries();
                });
            }

            // Clear filters
            const clearFiltersBtn = document.getElementById('clearFilters');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', function () {
                    if (searchInput) searchInput.value = '';
                    if (statusFilter) statusFilter.value = 'all';
                    if (dateFilter) dateFilter.value = '';

                    const rows = document.querySelectorAll('#inquiriesTable tbody tr');
                    rows.forEach(row => {
                        row.style.display = '';
                    });
                });
            }

            // Mark all as read
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function () {
                    if (confirm('Mark all inquiries as read?')) {
                        // AJAX call to mark all as read
                        fetch('mark_all_read.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            }
                        })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    location.reload();
                                }
                            });
                    }
                });
            }

            // Export CSV
            const exportBtn = document.getElementById('exportBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    window.location.href = 'export_inquiries.php';
                });
            }

            // Inquiry modal handling
            const inquiryModal = document.getElementById('inquiryModal');
            if (inquiryModal) {
                inquiryModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const inquiryId = button.getAttribute('data-id');

                    // Fetch inquiry details via AJAX
                    fetch('get_inquiry_details.php?id=' + inquiryId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                document.getElementById('modalCustomerName').textContent = data.name;
                                document.getElementById('modalCustomerEmail').textContent = data.email;
                                document.getElementById('modalCustomerPhone').textContent = data.phone;
                                document.getElementById('modalSubject').textContent = data.subject;
                                document.getElementById('modalDate').textContent = data.created_at;
                                document.getElementById('modalMessage').textContent = data.message;

                                // Set status badge
                                const statusSpan = document.getElementById('modalStatus');
                                statusSpan.innerHTML = getStatusBadge(data.status);

                                // Update action buttons
                                document.getElementById('replyBtn').href = 'mailto:' + data.email + '?subject=Re: ' + encodeURIComponent(data.subject);
                                document.getElementById('deleteInquiryBtn').href = 'delete_inquiry.php?id=' + inquiryId;
                                document.getElementById('markAsReadBtn').onclick = function () {
                                    markInquiryAsRead(inquiryId);
                                };
                            }
                        });
                });
            }

            function filterInquiries() {
                const statusValue = statusFilter ? statusFilter.value : 'all';
                const dateValue = dateFilter ? dateFilter.value : '';
                const rows = document.querySelectorAll('#inquiriesTable tbody tr');

                rows.forEach(row => {
                    const rowStatus = row.getAttribute('data-status');
                    const rowDate = row.getAttribute('data-date');
                    let showRow = true;

                    if (statusValue !== 'all' && rowStatus !== statusValue) {
                        showRow = false;
                    }

                    if (dateValue && rowDate !== dateValue) {
                        showRow = false;
                    }

                    row.style.display = showRow ? '' : 'none';
                });
            }

            function getStatusBadge(status) {
                const badges = {
                    'unread': '<span class="badge bg-info bg-opacity-20 text-dark fw-semibold"><i class="fas fa-envelope me-1"></i>Unread</span>',
                    'read': '<span class="badge bg-info bg-opacity-20 text-info fw-semibold"><i class="fas fa-envelope-open me-1"></i>Read</span>',
                    'replied': '<span class="badge bg-success bg-opacity-20 text-success fw-semibold"><i class="fas fa-reply me-1"></i>Replied</span>',
                    'pending': '<span class="badge bg-secondary bg-opacity-20 text-secondary fw-semibold"><i class="fas fa-clock me-1"></i>Pending</span>'
                };
                return badges[status] || badges['unread'];
            }

            function markInquiryAsRead(inquiryId) {
                fetch('mark_inquiry_read.php?id=' + inquiryId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        }
                    });
            }
        });
    </script>
</body>

</html>