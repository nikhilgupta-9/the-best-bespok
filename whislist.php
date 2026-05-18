<?php
// Report all PHP errors
error_reporting(E_ALL);

// Force display of errors to the screen
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Resolve visitor_id
if (empty($_COOKIE['visitor_id'])) {
    $visitor_id = session_id() ?: bin2hex(random_bytes(16));
    setcookie('visitor_id', $visitor_id, time() + (86400 * 90), '/');
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}

// Load wishlist items (graceful: table may not exist yet)
$wishlist_items = [];
$table_check = $conn->query("SHOW TABLES LIKE 'wishlist'");
if ($table_check && $table_check->num_rows > 0) {
    $stmt = $conn->prepare("
        SELECT w.id AS wid, p.id AS pro_id, p.pro_name, p.mrp, p.selling_price, p.pro_img, p.slug_url
        FROM wishlist w
        JOIN products p ON w.product_id = p.id
        WHERE w.visitor_id = ?
        ORDER BY w.created_at DESC
    ");
    $stmt->bind_param("s", $visitor_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $wishlist_items[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <link rel="stylesheet" href="assets/css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <title>The Best | Wishlist</title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
</head>

<body data-logged-in="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>">

    <!-- product view modal  -->
    <div class="modal product-view-modal" id="product-view">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="close-btn" data-bs-dismiss="modal"></div>
                    <div class="shop-details-top-section">
                        <div class="row g-4">
                            <div class="col-lg-6">
                                <div class="shop-details-img">
                                    <div class="tab-content" id="v-pills-tabContent">
                                        <div class="tab-pane fade show active" id="v-pills-img1" role="tabpanel">
                                            <div class="shop-details-tab-img">
                                                <img src="assets/image/inner-page/shop-details-tab-img1.jpg" alt="">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Back To Top -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
        <svg aria-hidden="true" class="arrow" width="16px" height="16px" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
            <path d="M34.9 289.5l-22.2-22.2c-9.4-9.4-9.4-24.6 0-33.9L207 39c9.4-9.4 24.6-9.4 33.9 0l194.3 194.3c9.4 9.4 9.4 24.6 0 33.9L413 289.4c-9.5 9.5-25 9.3-34.3-.4L264 168.6V456c0 13.3-10.7 24-24 24h-32c-13.3 0-24-10.7-24-24V168.6L69.2 289.1c-9.3 9.8-24.8 10-34.3.4z"></path>
        </svg>
    </div>

    <div class="modal login-modal fade" id="user-login" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="home-tab" data-bs-toggle="tab" data-bs-target="#home"
                                type="button" role="tab" aria-controls="home" aria-selected="true">Log In</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile"
                                type="button" role="tab" aria-controls="profile" aria-selected="false">Registration</button>
                        </li>
                    </ul>
                </div>
                <div class="modal-body">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <div class="login-registration-form">
                                <div class="form-title"><h3>Log In</h3></div>
                                <form>
                                    <div class="form-inner mb-35"><input type="text" placeholder="User name or Email *"></div>
                                    <div class="form-inner">
                                        <input id="password" type="password" placeholder="Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword"></i>
                                    </div>
                                    <div class="form-remember-forget">
                                        <div class="remember">
                                            <input type="checkbox" class="custom-check-box" id="check1">
                                            <label for="check1">Remember me</label>
                                        </div>
                                        <a href="#" class="forget-pass hover-underline">Forget Password</a>
                                    </div>
                                    <button class="primary-btn" type="submit">Log In</button>
                                    <a href="#" class="member">Not a member yet?</a>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
                            <div class="login-registration-form">
                                <div class="form-title"><h3>Registration</h3></div>
                                <form>
                                    <div class="form-inner mb-25"><input type="text" placeholder="User Name *"></div>
                                    <div class="form-inner mb-25"><input type="email" placeholder="Email Here *"></div>
                                    <div class="form-inner mb-25">
                                        <input id="password2" type="password" placeholder="Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword2"></i>
                                    </div>
                                    <div class="form-inner mb-35">
                                        <input id="password3" type="password" placeholder="Confirm Password *">
                                        <i class="bi bi-eye-slash" id="togglePassword3"></i>
                                    </div>
                                    <button class="primary-btn" type="submit">Registration</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- header section -->
    <?php
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- breadcrumb section -->
    <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(assets/image/background/about.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1>Wishlist</h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><span>/</span> Wishlist</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- wishlist section -->
    <div class="wishlist-section mb-100">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <?php if (empty($wishlist_items)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-heart" style="font-size:64px;color:#ccc;"></i>
                            <h4 class="mt-3" style="color:#555;">Your wishlist is empty</h4>
                            <p style="color:#888;">Browse our collection and save your favourite items here.</p>
                            <a class="primary-btn mt-3 d-inline-block" href="<?= BASE_URL ?>products.php">Shop Now</a>
                        </div>
                    <?php else: ?>
                        <?php foreach ($wishlist_items as $item):
                            $raw_imgs = explode(',', $item['pro_img'] ?? '');
                            $first_img = trim($raw_imgs[0]);
                            $img_src = !empty($first_img)
                                ? BASE_URL . 'admin/assets/img/uploads/' . htmlspecialchars($first_img)
                                : BASE_URL . 'assets/image/product-not-found.gif';
                            $detail_url = BASE_URL . 'product-details/' . htmlspecialchars($item['slug_url']);
                        ?>
                        <div class="wishlist-wrapper mb-30" id="wrow-<?= intval($item['pro_id']) ?>">
                            <div class="row align-items-center">
                                <div class="col-lg-4">
                                    <div class="product-content-details style-2">
                                        <div class="procut-image">
                                            <a href="<?= $detail_url ?>">
                                                <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['pro_name']) ?>" style="max-height:120px;object-fit:contain;">
                                            </a>
                                        </div>
                                        <div class="product-details">
                                            <h6><a href="<?= $detail_url ?>"><?= htmlspecialchars($item['pro_name']) ?></a></h6>
                                            <?php if (!empty($item['mrp']) && $item['mrp'] > $item['selling_price']): ?>
                                                <p><del>$<?= number_format($item['mrp'], 2) ?></del> $<?= number_format($item['selling_price'], 2) ?></p>
                                            <?php else: ?>
                                                <p>$<?= number_format($item['selling_price'], 2) ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 d-flex align-items-center justify-content-center">
                                    <div class="product-content-details">
                                        <div class="stock">
                                            <h6>In Stock</h6>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4 d-flex align-items-center justify-content-center">
                                    <div class="product-content-details close-btn">
                                        <a class="primary-btn2" href="<?= $detail_url ?>">View Details</a>
                                        <div class="close-icon" onclick="removeFromWishlist(<?= intval($item['pro_id']) ?>, this)" style="cursor:pointer;" title="Remove from wishlist">
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M9 1L1 9" stroke="#222222" stroke-width="1.2" stroke-linecap="round" />
                                                <path d="M1 1L9 9" stroke="#222222" stroke-width="1.2" stroke-linecap="round" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <!-- wishlist section ends here -->

    <?php include_once "includes/footer.php"; ?>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/jquery-ui.js"></script>
    <script src="assets/js/waypoints.js"></script>
    <script src="assets/js/jquery.counterup.js"></script>
    <script src="assets/js/jquery.counterup.min.js"></script>
    <script src="assets/js/jquery.marquee.min.js"></script>
    <script src="assets/js/popper.min.js"></script>
    <script src="assets/js/swiper-bundle.min.js"></script>
    <script src="assets/js/jquery.fancybox.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        function removeFromWishlist(productId, el) {
            var row = document.getElementById('wrow-' + productId);
            toggleWishlist(productId, el);
            if (row) {
                row.style.transition = 'opacity .3s';
                row.style.opacity = '0';
                setTimeout(function () {
                    row.remove();
                    // Show empty state if no rows left
                    var remaining = document.querySelectorAll('.wishlist-wrapper');
                    if (remaining.length === 0) {
                        document.querySelector('.wishlist-section .col-lg-12').innerHTML =
                            '<div class="text-center py-5">' +
                            '<i class="bi bi-heart" style="font-size:64px;color:#ccc;"></i>' +
                            '<h4 class="mt-3" style="color:#555;">Your wishlist is empty</h4>' +
                            '<p style="color:#888;">Browse our collection and save your favourite items here.</p>' +
                            '<a class="primary-btn mt-3 d-inline-block" href="<?= BASE_URL ?>products.php">Shop Now</a>' +
                            '</div>';
                    }
                }, 300);
            }
        }

        $(".marquee_text2").marquee({
            direction: "left",
            duration: 25000,
            gap: 50,
            delayBeforeStart: 0,
            duplicated: true,
            startVisible: true,
        });
    </script>
</body>
</html>
