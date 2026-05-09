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
    <title>Checkout | The Best</title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
</head>

<body>

    <!-- product view modal  -->
   <?php
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- hearder section ends here -->
    <!-- breadcrumb section strats here -->
    <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(assets/image/background/gallery.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1>Checkout</h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><span>/</span> Checkout</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- breadcrumb section ends here -->
    <!-- checkout section strats here -->
    <div class="checkout-section mb-100">
        <div class="container">
            <div class="row g-lg-4 gy-5">
                <div class="col-xl-7 col-lg-8">
                    <div class="enquery-section">
                        <div class="checkout-form-title">
                            <h4>Billing Information</h4>
                        </div>
                        <div class="enquery-form-wrapper style-3">
                            <form>
                                <div class="row">
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>full name</label>
                                            <input type="text" placeholder="Mr. Daniel">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>phone number</label>
                                            <input type="text" placeholder="(212)+ 455 645 678">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>email address</label>
                                            <input type="email" placeholder="info@example.com">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>your location <span>*</span></label>
                                            <input type="email" placeholder="Type Location">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>street address<span>*</span></label>
                                            <input type="text" placeholder="Street Address">
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-30">
                                        <div class="form-inner">
                                            <label>postal code <span>*</span></label>
                                            <input type="email" placeholder="Postal Code">
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-15">
                                        <div class="form-inner">
                                            <label>short notes<span>*</span></label>
                                            <textarea placeholder="Your Text Here"></textarea>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-25">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="contactCheck">
                                            <label class="form-check-label" for="contactCheck">
                                                Please save <span> My Name, Email Address</span> for the next
                                                time I comment.
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-xl-5 col-lg-4">
                    <div class="checkout-form-wrapper">
                        <div class="checkout-form-title">
                            <h4>Order Summary</h4>
                        </div>
                        <div class="order-sum-area">
                            <form>
                                <div class="cart-menu">
                                    <div class="cart-body">
                                        <ul>
                                            <li class="single-item">
                                                <div class="item-area">
                                                    <div class="main-item">
                                                        <div class="item-img">
                                                           <a href="product-details.html"> <img src="assets/image/inner-page/sb-product1.png" alt=""></a>
                                                           <div class="close-btn">
                                                                <i class="bi bi-x"></i>
                                                           </div>
                                                        </div>
                                                        <div class="content-and-quantity">
                                                            <div class="content">
                                                                <h6><a href="product-details.html">Ultimate Comfort &
                                                                        Trendy Design</a></h6>
                                                                <span>2 x $190.00</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="quantity-area">
                                                        <div class="quantity">
                                                            <a class="quantity__minus"><span><i
                                                                        class="bi bi-dash"></i></span></a>
                                                            <input name="quantity" type="text" class="quantity__input"
                                                                value="01">
                                                            <a class="quantity__plus"><span><i
                                                                        class="bi bi-plus"></i></span></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                            <li class="single-item">
                                                <div class="item-area">
                                                    <div class="main-item">
                                                        <div class="item-img">
                                                            <a href="product-details.html"><img src="assets/image/inner-page/sb-product2.png" alt=""></a>
                                                            <div class="close-btn">
                                                                <i class="bi bi-x"></i>
                                                           </div>
                                                        </div>
                                                        <div class="content-and-quantity">
                                                            <div class="content">
                                                                <h6><a href="product-details.html">Classic Navy Slim Fit Blazer</a></h6>
                                                                <span>2 x $190</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="quantity-area">
                                                        <div class="quantity">
                                                            <a class="quantity__minus"><span><i
                                                                        class="bi bi-dash"></i></span></a>
                                                            <input name="quantity" type="text" class="quantity__input"
                                                                value="01">
                                                            <a class="quantity__plus"><span><i
                                                                        class="bi bi-plus"></i></span></a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </li>
                                        </ul>
                                    </div>
                                    <div class="cart-footer">
                                        <div class="pricing-area mb-40">
                                            <ul>
                                                <li>
                                                    <strong>Sub Total</strong>
                                                    <strong class="price">$348.00</strong>
                                                </li>
                                                <li>
                                                    <strong>Shipping</strong>
                                                    <div class="order-info">
                                                        <p>Shipping Free*</p>
                                                        <span> Pickup fee $10.00</span>
                                                    </div>
                                                </li>
                                                <li>
                                                    <strong>Total</strong>
                                                    <strong class=" price">$214.00</strong>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="payment-method-area">
                                            <h6>Select Payment Method</h6>
                                            <ul class="payment-list">
                                                <li class="cash-delivery active">
                                                    <div class="payment-check">
                                                        <h6>Cash On Delivery</h6>
                                                    </div>
                                                    <div class="checked">
                                                    </div>
                                                </li>
                                                <li class="stripe">
                                                    <h6>Direct bank transfer</h6>
                                                    <div class="checked">
                                                    </div>
                                                </li>
                                                <li class="other-transfer">
                                                    <div class="payment-check paypal">
                                                        <h6>Online Payment</h6>
                                                    </div>
                                                    <div class="checked">
                                                    </div>
                                                </li>
                                            </ul>
                                            <div class="payment-option">
                                                <ul>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/visa-card.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/amex-card.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/discover-card.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/master-card.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/stripe-card.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/paypal.svg" alt="">
                                                    </li>
                                                    <li>
                                                        <img src="assets/image/inner-page/icon/apple-card.svg" alt="">
                                                    </li>
                                                </ul>
                                            </div>
                                            <div class="choose-payment-method pt-25" id="strip-payment" style="display: none;">
                                                <div class="row g-4">
                                                    <div class="col-md-12">
                                                        <div class="form-inner">
                                                            <label>Card Number</label>
                                                            <input type="text" placeholder="1234 1234 1234 1234">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-inner">
                                                            <label>Expiry</label>
                                                            <input type="text" placeholder="MM/YY">
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="form-inner">
                                                            <label>CVC</label>
                                                            <input type="text" placeholder="CVC">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="submit" class="primary-btn">
                                            Place Your Order
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- checkout section ends here -->
    <!-- footer section strats here -->
     <?php include_once "includes/footer.php"; ?>
    <!-- footer section end here -->


    <!-- Jquery js link -->
    <script data-cfasync="false" src="../../../cdn-cgi/scripts/5c5dd728/cloudflare-static/email-decode.min.js"></script><script src="assets/js/jquery-3.7.1.min.js"></script>
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