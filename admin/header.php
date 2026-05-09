<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get current page for active state highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<nav class="sidebar vertical-scroll dark_sidebar ps-container ps-theme-default ps-active-y">

    <div class="admin-profile text-center py-3">
        <a href="index.php">
            <img src="assets/img/logo_icon.jpg" alt="Admin Avatar" class="rounded-circle" width="60">
            <h6 class="mt-2 text-white">
                <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
            </h6>
        </a>
    </div>

    <div class="search-box px-3 mb-2">
        <input type="text" id="sidebarSearch" class="form-control" placeholder="Search...">
    </div>

    <ul id="sidebar_menu" class="sidebar-menu mt-3">

        <!-- Dashboard -->
        <li class="<?= $current_page === 'index.php' ? 'active' : '' ?>">
            <a href="index.php">
                <i class="fas fa-tachometer-alt" style="color:#3498db;"></i>
                <span>Dashboard</span>
            </a>
        </li>

        <!-- Home Content -->
        <li class="<?= in_array($current_page, ['home-items.php','add-banner.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-home" style="color:#e74c3c;"></i>
                <span>Home Content</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'home-items.php' ? 'active' : '' ?>">
                    <a href="home-items.php">Add Logo</a>
                </li>
                <li class="<?= $current_page === 'add-banner.php' ? 'active' : '' ?>">
                    <a href="add-banner.php">Add Banners</a>
                </li>
            </ul>
        </li>

        <!-- Categories -->
        <li class="<?= in_array($current_page, ['add-categories.php','view-categories.php','add-sub-category.php','view-sub-categories.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-layer-group" style="color:#2ecc71;"></i>
                <span>Categories</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'add-categories.php' ? 'active' : '' ?>">
                    <a href="add-categories.php">Add Category</a>
                </li>
                <li class="<?= $current_page === 'view-categories.php' ? 'active' : '' ?>">
                    <a href="view-categories.php">View Categories</a>
                </li>
                <li class="<?= $current_page === 'add-sub-category.php' ? 'active' : '' ?>">
                    <a href="add-sub-category.php">Add Sub Category</a>
                </li>
                <li class="<?= $current_page === 'view-sub-categories.php' ? 'active' : '' ?>">
                    <a href="view-sub-categories.php">View Sub Categories</a>
                </li>
            </ul>
        </li>

        <!-- Products -->
        <li class="<?= in_array($current_page, ['add-products.php','view-products.php','show-products.php','show-products-review.php','manage-fabric.php','add-fabric.php','edit-fabric.php','manage-colors.php','add-color.php','edit-color.php','manage-sizes.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-box-open" style="color:#f39c12;"></i>
                <span>Products</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'add-products.php' ? 'active' : '' ?>">
                    <a href="add-products.php">Add Product</a>
                </li>
                <li class="<?= in_array($current_page, ['view-products.php','show-products.php']) ? 'active' : '' ?>">
                    <a href="view-products.php">View Products</a>
                </li>
                <li class="<?= $current_page === 'show-products-review.php' ? 'active' : '' ?>">
                    <a href="show-products-review.php">Product Reviews</a>
                </li>

                <!-- FIX: Separator label for fabric/color/size -->
                <li class="sidebar-label">
                    <small style="color:#7bb3e5;padding:4px 16px;display:block;font-size:12px;text-transform:uppercase;letter-spacing:0.05em;">
                        Product Options
                    </small>
                </li>

                <li class="<?= in_array($current_page, ['manage-fabric.php','add-fabric.php','edit-fabric.php']) ? 'active' : '' ?>">
                    <a href="manage-fabric.php">
                        <i class="fas fa-scroll" style="color:#c19a6b;font-size:11px;"></i>
                        Fabric Options
                    </a>
                </li>
                <li class="<?= in_array($current_page, ['manage-colors.php','add-color.php','edit-color.php']) ? 'active' : '' ?>">
                    <a href="manage-colors.php">
                        <i class="fas fa-palette" style="color:#8e44ad;font-size:11px;"></i>
                        Color Options
                    </a>
                </li>
                <li class="<?= $current_page === 'manage-sizes.php' ? 'active' : '' ?>">
                    <a href="manage-sizes.php">
                        <i class="fas fa-ruler" style="color:#1abc9c;font-size:11px;"></i>
                        Size Variants
                    </a>
                </li>
            </ul>
        </li>

        <!-- ======================================
             NEW: Suit Configurator
        ======================================= -->
        <li class="<?= in_array($current_page, ['customization-options.php','add-customization-option.php','edit-customization-option.php','configurator-steps.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-magic" style="color:#e91e8c;"></i>
                <span>Suit Configurator</span>
            </a>
            <ul>
                <li class="<?= in_array($current_page, ['customization-options.php','add-customization-option.php','edit-customization-option.php']) ? 'active' : '' ?>">
                    <a href="customization-options.php">
                        <i class="fas fa-sliders-h" style="color:#e91e8c;font-size:11px;"></i>
                        Customization Options
                    </a>
                </li>
                <li class="<?= $current_page === 'configurator-steps.php' ? 'active' : '' ?>">
                    <a href="configurator-steps.php">
                        <i class="fas fa-list-ol" style="color:#ff6b6b;font-size:11px;"></i>
                        Configurator Steps
                    </a>
                </li>
            </ul>
        </li>

        <!-- ======================================
             NEW: Orders
        ======================================= -->
        <li class="<?= in_array($current_page, ['view-orders.php','view-order-detail.php','view-custom-orders.php','view-custom-order-detail.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-shopping-bag" style="color:#e67e22;"></i>
                <span>Orders</span>
                <?php
                // Show badge count of pending orders if orders table exists
                // Uncomment once orders table is created:
                // $pending = $conn->query("SELECT COUNT(*) FROM orders WHERE status='pending'")->fetch_row()[0];
                // if ($pending > 0) echo "<span class='badge bg-danger ms-1'>$pending</span>";
                ?>
            </a>
            <ul>
                <li class="<?= in_array($current_page, ['view-orders.php','view-order-detail.php']) ? 'active' : '' ?>">
                    <a href="view-orders.php">
                        <i class="fas fa-receipt" style="color:#e67e22;font-size:11px;"></i>
                        All Orders
                    </a>
                </li>
                <li class="<?= in_array($current_page, ['view-custom-orders.php','view-custom-order-detail.php']) ? 'active' : '' ?>">
                    <a href="view-custom-orders.php">
                        <i class="fas fa-cut" style="color:#e91e8c;font-size:11px;"></i>
                        Custom Suit Orders
                    </a>
                </li>
            </ul>
        </li>

        <!-- Blogs & News -->
        <li class="<?= in_array($current_page, ['add-blog.php','view-all-blog.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-blog" style="color:#9b59b6;"></i>
                <span>Blogs &amp; News</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'add-blog.php' ? 'active' : '' ?>">
                    <a href="add-blog.php">Add Blog</a>
                </li>
                <li class="<?= $current_page === 'view-all-blog.php' ? 'active' : '' ?>">
                    <a href="view-all-blog.php">View Blogs</a>
                </li>
            </ul>
        </li>

        <!-- Our Brands -->
        <li class="<?= in_array($current_page, ['our-best-brand.php','view_brands.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-award" style="color:#1abc9c;"></i>
                <span>Our Brand's</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'our-best-brand.php' ? 'active' : '' ?>">
                    <a href="our-best-brand.php">Add Brand</a>
                </li>
                <li class="<?= $current_page === 'view_brands.php' ? 'active' : '' ?>">
                    <a href="view_brands.php">View Brands</a>
                </li>
            </ul>
        </li>

        <!-- About Us -->
        <li class="<?= $current_page === 'about_us.php' ? 'active' : '' ?>">
            <a href="about_us.php">
                <i class="fas fa-info-circle" style="color:#d35400;"></i>
                <span>About Us</span>
            </a>
        </li>

        <!-- Contact Details -->
        <li class="<?= $current_page === 'add_contact.php' ? 'active' : '' ?>">
            <a href="add_contact.php">
                <i class="fa-regular fa-address-book" style="color:#e4d72b;"></i>
                <span>Contact Details</span>
            </a>
        </li>

        <!-- Inquiries -->
        <li class="<?= $current_page === 'new-leads.php' ? 'active' : '' ?>">
            <a href="new-leads.php">
                <i class="fa-solid fa-inbox" style="color:#3cc008;"></i>
                <span>Inquiries</span>
            </a>
        </li>

        <!-- Gallery -->
        <li class="<?= $current_page === 'add-gallery.php' ? 'active' : '' ?>">
            <a href="add-gallery.php">
                <i class="fas fa-images" style="color:#8e44ad;"></i>
                <span>Gallery</span>
            </a>
        </li>

        <!-- Testimonials -->
        <li class="<?= $current_page === 'view-testimonials.php' ? 'active' : '' ?>">
            <a href="view-testimonials.php">
                <i class="fas fa-quote-left" style="color:#16a085;"></i>
                <span>Testimonials</span>
            </a>
        </li>

        <!-- Users -->
        <li class="<?= in_array($current_page, ['all-admin.php','admin-create.php']) ? 'active' : '' ?>">
            <a class="has-arrow" href="#">
                <i class="fas fa-user-cog" style="color:#7f8c8d;"></i>
                <span>Users</span>
            </a>
            <ul>
                <li class="<?= $current_page === 'all-admin.php' ? 'active' : '' ?>">
                    <a href="all-admin.php">All Users</a>
                </li>
                <li class="<?= $current_page === 'admin-create.php' ? 'active' : '' ?>">
                    <a href="admin-create.php">Create Admin</a>
                </li>
            </ul>
        </li>

        <!-- Log Out -->
        <li>
            <a href="auth/logout.php">
                <i class="fas fa-sign-out-alt" style="color:#e74c3c;"></i>
                <span>Log Out</span>
            </a>
        </li>

    </ul>
</nav>

<script>
// Sidebar search — shows/hides top-level <li> based on text match
document.getElementById('sidebarSearch').addEventListener('input', function () {
    const filter = this.value.toLowerCase().trim();
    const items  = document.querySelectorAll('#sidebar_menu > li');

    items.forEach(li => {
        if (!filter) {
            li.style.display = '';
            return;
        }
        const text = li.textContent.toLowerCase();
        li.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Auto-expand the active parent menu on page load
document.addEventListener('DOMContentLoaded', function () {
    const activeItem = document.querySelector('#sidebar_menu li.active');
    if (activeItem) {
        const parentUl = activeItem.closest('ul:not(#sidebar_menu)');
        if (parentUl) {
            parentUl.style.display = 'block';
            const parentLi = parentUl.closest('li');
            if (parentLi) parentLi.classList.add('active');
        }
    }
});
</script>