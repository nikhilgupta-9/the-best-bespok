<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Ensure visitor_id cookie
if (empty($_COOKIE['visitor_id'])) {
    $visitor_id = session_id() ?: bin2hex(random_bytes(16));
    setcookie('visitor_id', $visitor_id, time() + (86400 * 90), '/');
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}
$user_id = $_SESSION['user_id'] ?? null;

// Show success message from suit-configurator redirect
$cart_success = $_SESSION['cart_success'] ?? null;
unset($_SESSION['cart_success']);

// Load cart items
$cart_items = [];
$subtotal = 0.0;

$stmt = $conn->prepare("
    SELECT c.id as cart_id, c.quantity, c.size, c.unit_price, c.total_price,
           c.customization_json, c.product_type, c.fabric_id, c.color_id,
           p.id as product_id, p.pro_name, p.pro_img, p.slug_url,
           f.name as fabric_name, cl.name as color_name
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN fabric_options f ON c.fabric_id = f.id
    LEFT JOIN color_options cl ON c.color_id = cl.id
    WHERE c.visitor_id = ?
    ORDER BY c.updated_at DESC
");
$stmt->bind_param("s", $visitor_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $cart_items[] = $row;
    $subtotal += (float)$row['total_price'];
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Swiper css link -->
    <link rel="stylesheet" href="assets/css/swiper-bundle.min.css">
    <!-- Fancybox css link -->
    <link rel="stylesheet" href="assets/css/jquery.fancybox.min.css">
    <!-- Animation css link -->
    <link rel="stylesheet" href="assets/css/animate.css">
    <link rel="stylesheet" href="assets/css/nice-select.css">
    <!-- bootstrap css link -->
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <!-- Boxicon css link -->
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <!-- My css link -->
    <link rel="stylesheet" href="assets/css/style.css">
    <title>The Best | Cart</title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
</head>

<body data-logged-in="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>">

    <!-- Back To Top -->
    <div class="progress-wrap">
        <svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
            <path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98" />
        </svg>
        <svg aria-hidden="true" class="arrow" width="16px" height="16px" viewBox="0 0 448 512" xmlns="http://www.w3.org/2000/svg">
            <path
                d="M34.9 289.5l-22.2-22.2c-9.4-9.4-9.4-24.6 0-33.9L207 39c9.4-9.4 24.6-9.4 33.9 0l194.3 194.3c9.4 9.4 9.4 24.6 0 33.9L413 289.4c-9.5 9.5-25 9.3-34.3-.4L264 168.6V456c0 13.3-10.7 24-24 24h-32c-13.3 0-24-10.7-24-24V168.6L69.2 289.1c-9.3 9.8-24.8 10-34.3.4z">
            </path>
        </svg>
    </div>


    <!-- hearder section strats here -->
    <?php
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>
    <!-- hearder section ends here -->
    <!-- breadcrumb section strats here -->
    <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(assets/image/background/contact_hero.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1>Cart</h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><span>/</span> Cart</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- breadcrumb section ends here -->
    <!-- Start Cart Page -->
    <div class="cart-page mb-100">
        <div class="container-lg container-fluid">

            <?php if ($cart_success): ?>
                <div class="alert alert-success mb-4" style="padding:12px 16px;background:#d4edda;border:1px solid #c3e6cb;border-radius:6px;color:#155724;">
                    <?= htmlspecialchars($cart_success) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($cart_items)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-bag-x" style="font-size:4rem;color:#ccc;display:block;margin-bottom:16px"></i>
                    <h4 style="color:#888;margin-bottom:12px">Your cart is empty</h4>
                    <a href="<?= BASE_URL ?>products.php" class="primary-btn">Continue Shopping</a>
                </div>
            <?php else: ?>

                <div class="row g-lg-4 gy-5">
                    <div class="col-xl-8 col-lg-7">
                        <div class="cart-shopping-wrapper">
                            <div class="cart-widget-title">
                                <h4>My Shopping</h4>
                            </div>
                            <table class="cart-table" id="cartTable">
                                <thead>
                                    <tr>
                                        <th>Product Info</th>
                                        <th>Price</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cart_items as $item):
                                        $images   = !empty($item['pro_img']) ? explode(',', $item['pro_img']) : [];
                                        $img_path = !empty($images) ? trim($images[0]) : '';
                                        $img_src  = $img_path
                                            ? BASE_URL . 'admin/assets/img/uploads/' . htmlspecialchars($img_path)
                                            : BASE_URL . 'assets/image/placeholder.jpg';
                                        $cdata = json_decode($item['customization_json'] ?? '{}', true) ?: [];
                                    ?>
                                        <tr id="cart-row-<?= $item['cart_id'] ?>">
                                            <td data-label="Product Info">
                                                <div class="product-info-wrapper">
                                                    <div class="product-info-img">
                                                        <img src="<?= $img_src ?>" alt="<?= htmlspecialchars($item['pro_name']) ?>"
                                                            onerror="this.src='<?= BASE_URL ?>assets/image/placeholder.jpg'">
                                                    </div>
                                                    <div class="product-info-content">
                                                        <h6>
                                                            <a href="<?= BASE_URL ?>product-details/<?= htmlspecialchars($item['slug_url']) ?>">
                                                                <?= htmlspecialchars($item['pro_name']) ?>
                                                            </a>
                                                        </h6>
                                                        <?php if ($item['size']): ?>
                                                            <p><span>Size: </span><?= htmlspecialchars($item['size']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($item['fabric_name']): ?>
                                                            <p><span>Fabric: </span><?= htmlspecialchars($item['fabric_name']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if ($item['color_name']): ?>
                                                            <p><span>Colour: </span><?= htmlspecialchars($item['color_name']) ?></p>
                                                        <?php endif; ?>
                                                        <?php if (!empty($cdata)): ?>
                                                            <p style="font-size:11px;color:#888">
                                                                <?= implode(', ', array_map(fn($k, $v) => "$k: $v", array_keys($cdata), $cdata)) ?>
                                                            </p>
                                                        <?php endif; ?>
                                                        <div class="quantity-area" style="margin-top:8px">
                                                            <div class="quantity">
                                                                <a class="quantity__minus" onclick="changeQty(<?= $item['cart_id'] ?>, -1)">
                                                                    <span><i class="bi bi-dash"></i></span>
                                                                </a>
                                                                <input type="text" class="quantity__input"
                                                                    id="qty-<?= $item['cart_id'] ?>"
                                                                    value="<?= $item['quantity'] ?>" readonly>
                                                                <a class="quantity__plus" onclick="changeQty(<?= $item['cart_id'] ?>, 1)">
                                                                    <span><i class="bi bi-plus"></i></span>
                                                                </a>
                                                            </div>
                                                        </div>
                                                        <ul style="margin-top:6px">
                                                            <li>
                                                                <a href="javascript:void(0)"
                                                                    style="color:#e74c3c;font-size:12px;cursor:pointer"
                                                                    onclick="removeItem(<?= $item['cart_id'] ?>)">
                                                                    <i class="bi bi-trash3"></i> Remove
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </td>
                                            <td data-label="Price">
                                                <span>₹<?= number_format((float)$item['unit_price'], 2) ?></span>
                                            </td>
                                            <td data-label="Total" id="row-total-<?= $item['cart_id'] ?>">
                                                ₹<?= number_format((float)$item['total_price'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <a href="<?= BASE_URL ?>products.php" class="details-button">
                                Continue Shopping
                                <svg width="10" height="10" viewBox="0 0 10 10" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M8.33624 2.84003L1.17627 10L0 8.82373L7.15914 1.66376H0.849347V0H10V9.15065H8.33624V2.84003Z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="col-xl-4 col-lg-5">
                        <div class="cart-order-sum-area">
                            <div class="cart-widget-title">
                                <h4>Order Summary</h4>
                            </div>
                            <div class="order-summary-wrap">
                                <ul class="order-summary-list">
                                    <li>
                                        <strong>Sub Total</strong>
                                        <span id="cartSubtotal">₹<?= number_format($subtotal, 2) ?></span>
                                    </li>
                                    <li>
                                        <strong>Shipping</strong>
                                        <div class="order-info">
                                            <p>Shipping Free*</p>
                                            <span>Pickup fee ₹200.00</span>
                                        </div>
                                    </li>
                                    <li>
                                        <div class="coupon-area">
                                            <strong>Coupon Code</strong>
                                            <form onsubmit="return false">
                                                <div class="form-inner">
                                                    <input type="text" placeholder="Your code">
                                                    <button type="submit" class="apply-btn">Apply</button>
                                                </div>
                                            </form>
                                        </div>
                                    </li>
                                    <li>
                                        <strong>Total</strong>
                                        <span id="cartTotal">₹<?= number_format($subtotal, 2) ?></span>
                                    </li>
                                </ul>
                                <a href="<?= BASE_URL ?>checkout.php" class="primary-btn mt-40">
                                    PROCEED TO CHECKOUT
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endif; ?>

        </div>
    </div>
    <!-- End Cart Page -->

    <script>
        var CART_BASE = '<?= BASE_URL ?>';

        function changeQty(cartId, delta) {
            var input = document.getElementById('qty-' + cartId);
            var newQty = Math.max(1, parseInt(input.value) + delta);
            input.value = newQty;
            updateCartItem(cartId, 'update', newQty);
        }

        function removeItem(cartId) {
            if (!confirm('Remove this item from cart?')) return;
            updateCartItem(cartId, 'remove', 0);
        }

        function updateCartItem(cartId, action, qty) {
            var form = new FormData();
            form.append('cart_id', cartId);
            form.append('action', action);
            form.append('quantity', qty);

            fetch(CART_BASE + 'ajax/update-cart.php', {
                    method: 'POST',
                    body: form
                })
                .then(function(r) {
                    return r.json();
                })
                .then(function(res) {
                    if (!res.success) return;
                    if (action === 'remove') {
                        var row = document.getElementById('cart-row-' + cartId);
                        if (row) row.remove();
                        // If no rows left, reload to show empty state
                        var rows = document.querySelectorAll('#cartTable tbody tr');
                        if (rows.length === 0) location.reload();
                    }
                    if (res.subtotal !== undefined) {
                        var el = document.getElementById('cartSubtotal');
                        var el2 = document.getElementById('cartTotal');
                        if (el) el.textContent = '₹' + res.subtotal;
                        if (el2) el2.textContent = '₹' + res.subtotal;
                    }
                    if (typeof updateCartCount === 'function') updateCartCount(res.cart_count);
                })
                .catch(function() {});
        }
    </script>
    <!-- footer section strats here -->
    <?php include_once "includes/footer.php"; ?>
    <!-- footer section end here -->


    <!-- Jquery js link -->
    <script data-cfasync="false" src="../../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script>
    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/jquery-ui.js"></script>
    <!-- Counterup js link -->
    <script src="assets/js/waypoints.js"></script>
    <script src="assets/js/jquery.counterup.js"></script>
    <script src="assets/js/jquery.counterup.min.js"></script>
    <!-- Marquee js link -->
    <script src="assets/js/jquery.marquee.min.js"></script>
    <!-- Popper js link -->
    <script src="assets/js/popper.min.js"></script>
    <!-- Swiper js link -->
    <script src="assets/js/swiper-bundle.min.js"></script>
    <!-- Fancybox js link -->
    <script src="assets/js/jquery.fancybox.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <!-- Wow js link -->
    <script src="assets/js/wow.min.js"></script>
    <!-- Bootstrap js link -->
    <script src="assets/js/bootstrap.min.js"></script>
    <!-- MAin js link -->
    <script src="assets/js/main.js"></script>

    <script>
        $(".marquee_text2").marquee({
            direction: "left",
            duration: 25000,
            gap: 50,
            delayBeforeStart: 0,
            duplicated: true,
            startVisible: true,
        });
    </script>
    <script defer src="https://static.cloudflareinsights.com/beacon.min.js/v8c78df7c7c0f484497ecbca7046644da1771523124516" integrity="sha512-8DS7rgIrAmghBFwoOTujcf6D9rXvH8xm8JQ1Ja01h9QX8EzXldiszufYa4IFfKdLUKTTrnSFXLDkUEOTrZQ8Qg==" data-cf-beacon='{"version":"2024.11.0","token":"70834e4b23964a2eaf7cf4ec0e5e9a84","r":1,"server_timing":{"name":{"cfCacheStatus":true,"cfEdge":true,"cfExtPri":true,"cfL4":true,"cfOrigin":true,"cfSpeedBrain":true},"location_startswith":null}}' crossorigin="anonymous"></script>
</body>

</html>