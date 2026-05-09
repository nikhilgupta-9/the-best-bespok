<?php
ob_start();
session_start();
include "db-conn.php";

// FIX 1: Auth check — protect this page
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// FIX 2: Load parent categories for the "Parent Category" dropdown (new schema)
$parent_categories = [];
$res = $conn->query("SELECT id, name FROM categories WHERE status = 1 AND parent_id IS NULL ORDER BY name ASC");
if ($res) {
    while ($r = $res->fetch_assoc()) {
        $parent_categories[] = $r;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title>Add Category | Admin Panel</title>
    <link rel="icon" href="img/logo.png" type="image/png">
    <?php include "links.php"; ?>

    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4cc9f0;
            --dark-color: #1a1a2e;
            --light-color: #f8f9fa;
            --success-color: #4bb543;
        }

        .category-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .section-title {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.75rem;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        .form-label {
            font-weight: 500;
            color: var(--dark-color);
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 201, 240, 0.25);
        }

        .btn-submit {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: #fff;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background: linear-gradient(to right, var(--secondary-color), var(--primary-color));
            color: #fff;
            transform: translateY(-2px);
        }

        /* ── FIX 3: File upload area ──────────────────────────────────────
           Original had file-upload-input CSS (position:absolute) but the
           actual <input> used class="form-control d-none" — so d-none made
           it display:none, which means clicking the <label> did NOT open
           the file picker (labels cannot activate display:none inputs).
           Fix: hide the input with opacity:0 + position:absolute so it is
           still "visible" to the browser's label activation mechanism,
           while appearing invisible to the user.
        ─────────────────────────────────────────────────────────────────── */
        .file-upload-wrapper {
            position: relative;
        }

        .file-upload-label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 1.5rem 1rem;
            border: 2px dashed #ddd;
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            min-height: 110px;
        }

        .file-upload-label:hover,
        .file-upload-label.drag-over {
            border-color: var(--primary-color);
            background-color: rgba(67, 97, 238, 0.04);
        }

        .file-upload-label i {
            font-size: 1.8rem;
            color: var(--primary-color);
        }

        /* FIX: opacity:0 + absolute instead of display:none */
        .file-upload-input {
            position: absolute;
            inset: 0;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
            z-index: 2;
        }

        .image-preview-container {
            display: none;
            margin-top: 1rem;
            text-align: center;
        }

        .image-preview {
            max-width: 200px;
            max-height: 150px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border: 1px solid #eee;
            object-fit: cover;
        }

        /* Required star */
        .required-star {
            color: #e74c3c;
            margin-left: 2px;
        }

        /* Alert inside card */
        .alert ul {
            padding-left: 1.2rem;
            margin: 0;
        }
    </style>
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
            <div class="container-fluid p-0 sm_padding_15px">
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="white_card card_height_100 mb_30">

                            <!-- Header -->
                            <div class="white_card_header">
                                <div class="box_header m-0">
                                    <div class="main-title">
                                        <h2 class="m-0">Add New Category</h2>
                                    </div>
                                    <div class="add_button ms-2">
                                        <a href="view-categories.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-list me-1"></i> View Categories
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- FIX 4: Error messages from session (set by functions.php after redirect) -->
                            <?php if (!empty($_SESSION['errors'])): ?>
                                <div class="alert alert-danger mx-4 mt-3">
                                    <strong><i class="fas fa-exclamation-circle me-1"></i> Please fix the following:</strong>
                                    <ul class="mt-2">
                                        <?php foreach ($_SESSION['errors'] as $error): ?>
                                            <li><?= htmlspecialchars($error) ?></li>
                                        <?php endforeach;
                                        unset($_SESSION['errors']); ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['success'])): ?>
                                <div class="alert alert-success mx-4 mt-3">
                                    <i class="fas fa-check-circle me-1"></i>
                                    <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
                                </div>
                            <?php endif; ?>

                            <div class="white_card_body">
                                <div class="category-card">
                                    <h3 class="section-title">Category Details</h3>

                                    <!-- FIX 5: action posts to index.php (self) — NOT functions.php directly.
                                         functions.php is included at the top of every admin page via include,
                                         so the POST handler in functions.php runs when THIS page loads.
                                         Alternatively, post to add-categories.php itself (action="").
                                         This is the standard pattern used by your other pages.          -->
                                    <form id="categoryForm" action="" method="post" enctype="multipart/form-data" novalidate>

                                        <!-- Row 1: Category Name + Parent Category -->
                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="cate_name">
                                                    Category Name <span class="required-star">*</span>
                                                </label>
                                                <input type="text" class="form-control" name="cate_name" id="cate_name"
                                                    placeholder="e.g. Suits, Blazers, Sherwanis"
                                                    value="<?= htmlspecialchars($_SESSION['form_data']['cate_name'] ?? '') ?>"
                                                    required />
                                            </div>

                                            <!-- FIX 6: NEW — Parent Category dropdown (was missing entirely)
                                                 Allows creating sub-categories directly from this form.
                                                 Leave blank = top-level category. -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="parent_id">
                                                    Parent Category
                                                    <small class="text-muted fw-normal">(leave blank for top-level)</small>
                                                </label>
                                                <select class="form-select" name="parent_id" id="parent_id">
                                                    <option value="">— Top Level Category —</option>
                                                    <?php foreach ($parent_categories as $pc): ?>
                                                        <option value="<?= (int)$pc['id'] ?>"
                                                            <?= (isset($_SESSION['form_data']['parent_id']) && $_SESSION['form_data']['parent_id'] == $pc['id']) ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($pc['name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Row 2: Category Type + Status -->
                                        <div class="row mb-4">
                                            <!-- FIX 7: NEW — Type field (new schema column) -->
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="type">
                                                    Category Type <span class="required-star">*</span>
                                                </label>
                                                <select class="form-select" name="type" id="type" required>
                                                    <option value="general"
                                                        <?= (($_SESSION['form_data']['type'] ?? '') == 'general') ? 'selected' : '' ?>>
                                                        General
                                                    </option>
                                                    <option value="suit_style"
                                                        <?= (($_SESSION['form_data']['type'] ?? '') == 'suit_style') ? 'selected' : '' ?>>
                                                        Suit Style (e.g. 2-Piece, 3-Piece)
                                                    </option>
                                                    <option value="occasion"
                                                        <?= (($_SESSION['form_data']['type'] ?? '') == 'occasion') ? 'selected' : '' ?>>
                                                        Occasion (e.g. Wedding, Corporate)
                                                    </option>
                                                    <option value="fabric_type"
                                                        <?= (($_SESSION['form_data']['type'] ?? '') == 'fabric_type') ? 'selected' : '' ?>>
                                                        Fabric Type
                                                    </option>
                                                </select>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="status">Status</label>
                                                <select id="status" name="status" class="form-select" required>
                                                    <option value="1" <?= (($_SESSION['form_data']['status'] ?? '1') == '1') ? 'selected' : '' ?>>
                                                        Active
                                                    </option>
                                                    <option value="0" <?= (($_SESSION['form_data']['status'] ?? '') == '0') ? 'selected' : '' ?>>
                                                        Inactive
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Row 3: Meta Title + Meta Keywords -->
                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_title">Meta Title</label>
                                                <input type="text" class="form-control" name="meta_title" id="meta_title"
                                                    placeholder="Enter meta title for SEO"
                                                    value="<?= htmlspecialchars($_SESSION['form_data']['meta_title'] ?? '') ?>" />
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_key">Meta Keywords</label>
                                                <input type="text" class="form-control" name="meta_key" id="meta_key"
                                                    placeholder="Comma separated: suits, blazers, tailor"
                                                    value="<?= htmlspecialchars($_SESSION['form_data']['meta_key'] ?? '') ?>" />
                                                <small class="text-muted">Example: suits, wedding collection, tailor</small>
                                            </div>
                                        </div>

                                        <!-- Row 4: Meta Description + Image Upload -->
                                        <div class="row mb-4">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label" for="meta_desc">Meta Description</label>
                                                <textarea class="form-control" name="meta_desc" id="meta_desc" rows="3"
                                                    placeholder="Brief description for SEO (150-160 chars recommended)"><?= htmlspecialchars($_SESSION['form_data']['meta_desc'] ?? '') ?></textarea>
                                                <small class="text-muted">
                                                    <span id="metaCharCount">0</span> / 160 characters
                                                </small>
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">
                                                    Category Image <span class="required-star">*</span>
                                                </label>
                                                <!-- FIX 3 applied: input uses class="file-upload-input" NOT d-none -->
                                                <div class="file-upload-wrapper">
                                                    <label for="imageUpload" class="file-upload-label" id="uploadLabel">
                                                        <i class="fas fa-cloud-upload-alt"></i>
                                                        <span class="fw-semibold">Click to upload or drag & drop</span>
                                                        <small class="text-muted">PNG, JPG, GIF, WEBP — max 5MB</small>
                                                    </label>
                                                    <input type="file"
                                                           class="file-upload-input"
                                                           name="imageUpload"
                                                           id="imageUpload"
                                                           accept="image/*"
                                                           onchange="previewImage(this)" />
                                                </div>
                                                <div class="image-preview-container" id="imagePreviewContainer">
                                                    <img id="imagePreview" class="image-preview" alt="Preview" />
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-sm btn-danger" onclick="removeImage()">
                                                            <i class="fas fa-trash me-1"></i> Remove
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Actions -->
                                        <div class="d-flex justify-content-end mt-4 gap-2">
                                            <button type="reset" class="btn btn-outline-secondary" onclick="removeImage()">
                                                <i class="fas fa-undo me-1"></i> Reset
                                            </button>
                                            <button type="submit" class="btn btn-submit" name="add-categories">
                                                <i class="fas fa-save me-1"></i> Save Category
                                            </button>
                                        </div>

                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include "footer.php"; ?>
    </section>

    <?php
    // FIX 8: Clear form_data from session AFTER rendering so repopulate script below can use it
    $form_data_js = json_encode($_SESSION['form_data'] ?? []);
    unset($_SESSION['form_data']);
    ?>

    <script>
        // ── FIX 9: Repopulate selects on validation error ──────────────────
        // Original JS repopulate only worked for text inputs (element.value).
        // Selects are now handled server-side with PHP selected= checks above,
        // which is more reliable. This JS block is kept only for any dynamic
        // fields added later.
        (function () {
            const formData = <?= $form_data_js ?>;
            if (Object.keys(formData).length === 0) return;
            document.addEventListener('DOMContentLoaded', function () {
                for (const key in formData) {
                    const el = document.querySelector(`[name="${key}"]`);
                    if (el && el.tagName !== 'SELECT') {   // selects handled server-side
                        el.value = formData[key];
                    }
                }
            });
        })();

        // ── Image preview ──────────────────────────────────────────────────
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('imagePreview').src = e.target.result;
                    document.getElementById('imagePreviewContainer').style.display = 'block';
                    document.getElementById('uploadLabel').style.display = 'none';
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeImage() {
            document.getElementById('imageUpload').value = '';
            document.getElementById('imagePreviewContainer').style.display = 'none';
            document.getElementById('uploadLabel').style.display = 'flex';
        }

        // ── Meta description character counter ─────────────────────────────
        const metaDesc    = document.getElementById('meta_desc');
        const metaCounter = document.getElementById('metaCharCount');
        function updateCount() {
            const len = metaDesc.value.length;
            metaCounter.textContent = len;
            metaCounter.style.color = len > 160 ? '#e74c3c' : '#6c757d';
        }
        metaDesc.addEventListener('input', updateCount);
        updateCount(); // init on load

        // ── Slug preview (auto-generate from category name) ────────────────
        document.getElementById('cate_name').addEventListener('input', function () {
            const slug = this.value.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .trim()
                .replace(/\s+/g, '-');
            const preview = document.getElementById('slugPreview');
            if (preview) preview.textContent = slug || '—';
        });

        // ── Drag and drop ──────────────────────────────────────────────────
        const uploadLabel = document.getElementById('uploadLabel');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(ev => {
            uploadLabel.addEventListener(ev, e => { e.preventDefault(); e.stopPropagation(); }, false);
        });

        ['dragenter', 'dragover'].forEach(ev => {
            uploadLabel.addEventListener(ev, () => uploadLabel.classList.add('drag-over'));
        });
        ['dragleave', 'drop'].forEach(ev => {
            uploadLabel.addEventListener(ev, () => uploadLabel.classList.remove('drag-over'));
        });

        uploadLabel.addEventListener('drop', function (e) {
            const files = e.dataTransfer.files;
            const input = document.getElementById('imageUpload');
            if (files.length) {
                // FIX 10: DataTransfer is the correct way to assign files to input
                const dt = new DataTransfer();
                dt.items.add(files[0]);
                input.files = dt.files;
                previewImage(input);
            }
        });

        // ── Client-side form validation ────────────────────────────────────
        document.getElementById('categoryForm').addEventListener('submit', function (e) {
            const name  = document.getElementById('cate_name').value.trim();
            const image = document.getElementById('imageUpload').files.length;

            if (!name) {
                e.preventDefault();
                alert('Please enter a category name.');
                document.getElementById('cate_name').focus();
                return;
            }
            if (!image) {
                e.preventDefault();
                alert('Please upload a category image.');
                return;
            }
        });
    </script>

</body>
</html>