<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";
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
    <title>Ethics - Fashion Shop HTML Template</title>
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
</head>
<style>
    .home1-banner-section {
        position: relative;
        width: 100%;
        height: 100vh;
        min-height: 600px;
        overflow: hidden;
    }

    .banner-wrapper.video-bg {
        position: relative;
        width: 100%;
        height: 100%;
        overflow: hidden;
    }

    .banner-video {
        position: absolute;
        top: 50%;
        left: 50%;
        min-width: 100%;
        min-height: 100%;
        width: auto;
        height: auto;
        transform: translateX(-50%) translateY(-50%);
        object-fit: cover;
        z-index: 1;
    }

    .video-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(180deg, #00000014, rgba(0, 0, 0, 0.45));
        z-index: 2;
    }

    .banner-wrapper .container {
        position: relative;
        z-index: 3;
        height: 100%;
    }

    .banner-wrapper .row {
        height: 100%;
    }

    .banner-content {
        color: white;
        position: relative;
        z-index: 3;
    }

    .banner-content span {
        font-size: 18px;
        letter-spacing: 3px;
        display: block;
        margin-bottom: 20px;
    }

    .banner-content h1 {
        font-size: 64px;
        font-weight: 700;
        margin-bottom: 20px;
    }

    .banner-content p {
        font-size: 18px;
        max-width: 600px;
        margin: 0 auto 30px;
    }

    .banner-button {
        display: flex;
        gap: 20px;
        justify-content: center;
        flex-wrap: wrap;
    }



    .women-shop-btn {
        background-color: #c6a43f;
        border-color: #c6a43f;
    }

    .women-shop-btn:hover {
        background-color: transparent;
        color: white;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .banner-content h1 {
            font-size: 36px;
        }

        .banner-content span {
            font-size: 14px;
        }

        .banner-content p {
            font-size: 14px;
            padding: 0 20px;
        }

    }
</style>

<body data-logged-in="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>">

    <!-- on page load modal -->
    <div class="page-load-modal">
        <div class="modal show" id="myModal" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="popup-wrapper">
                            <div class="modal-clode-btn" data-bs-dismiss="modal"></div>
                            <div class="popup-img">
                                <img src="assets/image/gallery/img1.png" alt="">
                            </div>
                            <div class="popup-content">
                                <h2>Sale Up To 50%</h2>
                                <p>Subscribe up to receive all the latest news updates & store discount</p>
                                <form>
                                    <div class="from-inner">
                                        <input type="email" placeholder="Email Address">
                                        <button type="submit" class="from-arrow">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="11" viewBox="0 0 18 11">
                                                <path
                                                    d="M16.4587 5.76798H1.12214C0.796856 5.76798 0.533569 5.50447 0.533569 5.17891C0.533569 4.85335 0.796856 4.58984 1.12214 4.58984H16.4587C16.784 4.58984 17.0473 4.85335 17.0473 5.17891C17.0473 5.50447 16.784 5.76798 16.4587 5.76798Z" />
                                                <path
                                                    d="M12.1134 10.3617C11.9395 10.3617 11.7677 10.2852 11.6515 10.1383C11.4499 9.88302 11.493 9.51269 11.7481 9.31084L13.5789 7.86173L16.5198 5.23489L11.6621 1.03484C11.4161 0.82199 11.389 0.450092 11.6013 0.203862C11.814 -0.0423681 12.1864 -0.069072 12.4316 0.142992L17.7958 4.78131C17.9241 4.89205 17.9983 5.05267 17.9999 5.22271C18.001 5.39236 17.9296 5.55416 17.8033 5.66687L14.3354 8.76301L12.4783 10.2349C12.37 10.3205 12.2409 10.3617 12.1134 10.3617Z" />
                                            </svg>
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
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- hearder section ends here -->
    <!-- Banner section with video starts here -->
    <div class="home1-banner-section">
        <div class="banner-wrapper video-bg">
            <video class="banner-video" autoplay loop muted playsinline>
                <source src="<?= BASE_URL ?>assets/image/video/home-v1.mp4" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <div class="video-overlay"></div>
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 d-flex align-items-center justify-content-center">
                        <div class="banner-content text-center">
                            <span>THE BEST – Custom Tailoring</span>
                            <h1>Crafted Just For You</h1>
                            <p>Experience the art of bespoke tailoring with perfectly fitted suits designed to match your personality and style.</p>
                            <div class="banner-button">
                                <a class="primary-btn" href="<?= BASE_URL ?>suit-configurator.php">CUSTOM SUITS</a>
                                <a class="primary-btn women-shop-btn" href="<?= BASE_URL ?>contact.php">BOOK APPOINTMENT</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- Banner footer -->
    <div class="banner-footer mb-100">
        <div class="container-fluid">
            <div class="banner-footer-wrapper">
                <div class="row g-lg-4 gy-5">
                    <!-- Your existing footer items here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Best selling product section strats here -->
    <div class="home1-product-section mb-100 ">
        <div class="container">
            <div class="row wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 mb-50">
                    <div class="section-title text-center">
                        <h3>Best Selling Fashion</h3>
                        <p>Discover our best selling fashion essentials, curated just for you! Elevate your wardrobe
                            with our must-have pieces </p>
                    </div>
                </div>
            </div>
            <div class="row wow animate fadeInUp" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 position-relative">
                    <div class="swiper home1-product-swiper">
                        <div class="swiper-wrapper">

                            <?php
                            foreach($product_home as $home_pro){
                                $images = explode(",",$home_pro['pro_img']);
                                $first_img = trim($images[0]);
                                $hp_discount = 0;
                                if (!empty($home_pro['mrp']) && $home_pro['mrp'] > $home_pro['selling_price']) {
                                    $hp_discount = round((($home_pro['mrp'] - $home_pro['selling_price']) / $home_pro['mrp']) * 100);
                                }
                            ?>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="<?= BASE_URL ?>product-details/<?= $home_pro['slug_url'] ?>">
                                            <img src="<?= BASE_URL ?>admin/assets/img/uploads/<?= $first_img ?>" alt="<?= htmlspecialchars($home_pro['pro_name']) ?>"
                                                 onerror="this.src='<?= BASE_URL ?>assets/image/placeholder.jpg'">
                                        </a>
                                        <div class="batch">
                                            <?php if ($hp_discount > 0): ?><span class="new"><?= $hp_discount ?>% off</span><?php endif; ?>
                                            <?php if (!empty($home_pro['new_arrival'])): ?><span>New</span><?php endif; ?>
                                        </div>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <?php if (!empty($home_pro['is_customizable'])): ?>
                                                    <a class="add-cart-btn" href="<?= BASE_URL ?>suit-configurator.php?id=<?= base64_encode($home_pro['id']) ?>">
                                                        <i class="bi bi-palette"></i> Customise
                                                    </a>
                                                <?php else: ?>
                                                    <a class="add-cart-btn" href="<?= BASE_URL ?>product-details/<?= htmlspecialchars($home_pro['slug_url']) ?>">
                                                        <i class="bi bi-eye"></i> View Details
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="javascript:void(0)"
                                                       class="wishlist-btn"
                                                       data-pid="<?= $home_pro['id'] ?>"
                                                       onclick="toggleWishlist(<?= $home_pro['id'] ?>, this)"
                                                       title="Add to Wishlist">
                                                        <i class="bi bi-heart" style="font-size:16px"></i>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a href="<?= BASE_URL ?>product-details/<?= $home_pro['slug_url'] ?>">
                                                        <i class="bi bi-eye text-dark" style="font-size:16px"></i>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <!-- <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div> -->
                                        <h6>
                                            <a class="hover-underline" href="<?= BASE_URL ?>product-details/<?= $home_pro['slug_url'] ?>"><?= $home_pro['pro_name'] ?></a>
                                        </h6>
                                        <p class="price"><del>$<?= number_format($home_pro['mrp'], 2) ?></del> $<?= number_format($home_pro['selling_price'], 2) ?> </p>

                                    </div>
                                </div>
                            </div>
                            <?php } ?>

                           
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center pt-40">
                    <div class="slider-btn-wrap">
                        <div class="slider-btn product-slider-prev">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 13C11 10.5 6 8 3 7C6 6 10.5 4.5 12 1" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="fractional-pagination"></div>
                        <div class="slider-btn product-slider-next">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1C2 3.5 7 6 10 7C7 8 2.5 9.5 1 13" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Best selling product section ends here -->
    <!-- Categori section strats here -->
    <div class="categori-section mb-100">
        <div class="container">
            <div class="row wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 d-flex align-items-center mb-60">
                    <div class="section-title text-center">
                        <h3>Our Tailoring Categories</h3>
                    </div>
                    <div class="view-all-button">
                        <a href="<?= BASE_URL ?>category.php">View All</a>
                    </div>
                </div>
            </div>

            <div class="row g-4 row-cols-xxl-6 row-cols-xl-5 row-cols-lg-4 row-cols-md-3 row-cols-sm-2 row-cols-2">

                <!-- Coat -->
                 <?php
                 $category = select_query('categories', 'where status=1 order by id desc limit 6 ');
                 while($row = mysqli_fetch_assoc($category)){
                 ?>
                <div class="col wow animate fadeInDown" data-wow-delay="200ms">
                    <div class="categori-content text-center">
                        <a href="<?= BASE_URL ?>category/<?= $row['slug_url'] ?>">
                            <img src="<?= BASE_URL ?>admin/uploads/category/<?= $row['image'] ?>" alt="Coat">
                        </a>
                        <h6><a href="<?= BASE_URL ?>category/<?= $row['slug_url'] ?>"><?= $row['name'] ?></a></h6>
                    </div>
                </div>
                <?php } ?>

               
            </div>
        </div>
    </div>
    <!-- Categori section ends here -->

    <!-- sell banner section strats here -->
    <div class="sell-banner-section mb-120"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.45), rgba(0, 0, 0, 0.45)), url(assets/image/banner/paralex-1.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="sell-banner-content text-center">
                        <p>Precision Tailoring for Modern Gentlemen</p>
                        <h2>Experience the Art of Bespoke Suits Crafted to <span>Perfection</span></h2>
                        <a class="primary-btn2" href="3columns-left.html">
                            BOOK YOUR APPOINTMENT
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- offer section ends here -->
    <!-- banner category section strats here -->
    <div class="home1-product-section3 mb-100">
        <div class="container-fluid">
            <div class="row gy-5">

                <!-- LEFT CONTENT -->
                <div class="col-lg-4 d-flex align-items-center wow animate fadeInLeft" data-wow-delay="200ms" data-wow-duration="1500ms">
                    <div class="home1-product-section3-content">
                        <h3>Bespoke Tailoring Experience</h3>
                        <p>Discover premium custom suits crafted with precision, elegance, and your unique style in mind.</p>
                        <a class="primary-btn" href="categories.html">EXPLORE SERVICES</a>
                    </div>
                </div>

                <!-- RIGHT SLIDER -->
                <div class="col-lg-8">
                    <div class="swiper home1-product3-swipe">
                        <div class="swiper-wrapper">

                            <!-- Custom Suits -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="categories.html">
                                        <img src="assets/image/gallery/1.png" alt="Custom Suits">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Custom Suits</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Blazers -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="3columns-left.html">
                                        <img src="assets/image/gallery/2.png" alt="Blazers">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Blazers</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Shirts -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="3columns-left.html">
                                        <img src="assets/image/gallery/3.png" alt="Shirts">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Premium Shirts</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Wedding Suits -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="3columns-left.html">
                                        <img src="assets/image/gallery/4.png" alt="Wedding Suits">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Wedding Suits</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Trousers -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="3columns-left.html">
                                        <img src="assets/image/gallery/5.png" alt="Trousers">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Custom Trousers</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Accessories -->
                            <div class="swiper-slide">
                                <div class="product-section3-card">
                                    <a href="3columns-left.html">
                                        <img src="assets/image/gallery/6.png" alt="Accessories">
                                    </a>
                                    <div class="overly text-center">
                                        <div class="overly-content">
                                            <h4><a href="3columns-left.html">Accessories</a></h4>
                                            <a href="3columns-left.html" class="view-all-btn">View Collection</a>
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
    <!-- banner category section ends here -->
    <!--  collection section strats here -->
    <div class="collection-section mb-100">
        <div class="container">
            <div class="row gy-4">

                <!-- LEFT BLOCK -->
                <div class="col-lg-6 wow animate fadeInLeft" data-wow-delay="200ms" data-wow-duration="1500ms">
                    <div class="fashion-area">
                        <img src="assets/image/about/about1.png" alt="">
                        <div class="fashion-area-content">
                            <h3>Bespoke Suit Collection</h3>
                            <a href="3columns-left.html">Explore Custom Designs</a>
                        </div>
                    </div>
                </div>

                <!-- RIGHT BLOCK -->
                <div class="col-lg-6 wow animate fadeInRight" data-wow-delay="200ms" data-wow-duration="1500ms">
                    <div class="jwelry-area">
                        <img src="assets/image/about/about-3.png" alt="">
                        <div class="jwelry-area-content">
                            <h3>Party & Occasion Wear</h3>
                            <a href="3columns-left.html" class="primary-btn">BOOK YOUR LOOK</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!--  collection section ends here -->
    <!-- partner section strats here -->
    <div class="partner-logo-section mb-120 wow animate fadeInUp" data-wow-delay="200ms" data-wow-duration="1500ms">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="logo-wrap">
                        <div class="logo-title">
                            <h5>We have Many Brands & 20+ Trusted Partner</h5>
                        </div>
                        <div class="logo-area mb-60">
                            <div class="marquee_text1">
                                <?php
                                foreach($brand as $brands){
                                ?>
                                <a href="#"><img src="<?= BASE_URL ?>admin/<?= $brands['logo_path'] ?>" alt=""></a>
                                <?php } ?>
                                
                                <a href="#"><img src="assets/image/partners/P-2.png" alt=""></a>
                            </div>
                        </div>
                        <div class="logo-area">
                            <div class="marquee_text2">
                                <a href="#"><img src="assets/image/partners/P-5.png" alt=""></a>
                                 <?php
                                foreach($brand as $brands){
                                ?>
                                <a href="#"><img src="<?= BASE_URL ?>admin/<?= $brands['logo_path'] ?>" alt=""></a>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- partner section ends here -->
    <!-- Best selling product section strats here -->
    <div class="home1-product-section mb-100">
        <div class="container">
            <div class="row wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 mb-50">
                    <div class="section-title text-center">
                        <h3> New arrival Fashion</h3>
                        <p> Our new arrivals are perfect for updating your wardrobe. Shop now and be the first to flaunt
                            the latest fashion! </p>
                    </div>
                </div>
            </div>
            <div class="row wow animate fadeInUp" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 position-relative">
                    <div class="swiper home1-product-swiper2">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/2.png" alt="">
                                        </a>
                                        <div class="batch">
                                            <span class="new">23% off</span>
                                            <span>Hot deal</span>
                                        </div>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Trendy & Comfortable Outerwear</a></h6>
                                        <p class="price"><del>$345.00</del> $300.00 </p>

                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/4.png" alt="">
                                        </a>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Classic Navy Slim Fit Blazer</a></h6>
                                        <p class="price"> $300.00 </p>

                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/6.png" alt="">
                                        </a>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Trendy & Comfortable Outerwear</a></h6>
                                        <p class="price"><del>$345.00</del> $300.00 </p>

                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/8.png" alt="">
                                        </a>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Trendy & Comfortable Outerwear</a></h6>
                                        <p class="price"><del>$345.00</del> $300.00 </p>

                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/10.png" alt="">
                                        </a>
                                        <div class="batch">
                                            <span class="new">23% off</span>
                                            <span>Hot deal</span>
                                        </div>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Trendy & Comfortable Outerwear</a></h6>
                                        <p class="price"><del>$345.00</del> $300.00 </p>

                                    </div>
                                </div>
                            </div>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="product-details.php">
                                            <img src="assets/image/products/1.png" alt="">
                                        </a>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn style-2" href="<?= BASE_URL ?>products.php"><i class="bi bi-eye"></i> View Details</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="<?= BASE_URL ?>whislist.php">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                                            viewBox="0 0 18 18">
                                                            <path
                                                                d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                                <li>
                                                    <a data-bs-toggle="modal" data-bs-target="#product-view">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22"
                                                            viewBox="0 0 22 22">
                                                            <path
                                                                d="M21.8601 10.5721C21.6636 10.3032 16.9807 3.98901 10.9999 3.98901C5.019 3.98901 0.335925 10.3032 0.139601 10.5718C0.0488852 10.6961 0 10.846 0 10.9999C0 11.1537 0.0488852 11.3036 0.139601 11.4279C0.335925 11.6967 5.019 18.011 10.9999 18.011C16.9807 18.011 21.6636 11.6967 21.8601 11.4281C21.951 11.3039 21.9999 11.154 21.9999 11.0001C21.9999 10.8462 21.951 10.6963 21.8601 10.5721ZM10.9999 16.5604C6.59432 16.5604 2.77866 12.3696 1.64914 10.9995C2.77719 9.62823 6.58487 5.43955 10.9999 5.43955C15.4052 5.43955 19.2206 9.62969 20.3506 11.0005C19.2225 12.3717 15.4149 16.5604 10.9999 16.5604Z">
                                                            </path>
                                                            <path
                                                                d="M10.9999 6.64832C8.60039 6.64832 6.64819 8.60051 6.64819 11C6.64819 13.3994 8.60039 15.3516 10.9999 15.3516C13.3993 15.3516 15.3515 13.3994 15.3515 11C15.3515 8.60051 13.3993 6.64832 10.9999 6.64832ZM10.9999 13.9011C9.40013 13.9011 8.09878 12.5997 8.09878 11C8.09878 9.40029 9.40017 8.0989 10.9999 8.0989C12.5995 8.0989 13.9009 9.40029 13.9009 11C13.9009 12.5997 12.5996 13.9011 10.9999 13.9011Z">
                                                            </path>
                                                        </svg>
                                                    </a>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="product-card-content">
                                        <div class="rating">
                                            <ul>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                                <li><i class="bi bi-star-fill"></i></li>
                                            </ul>
                                        </div>
                                        <h6><a class="hover-underline" href="product-details.php">Classic Navy Slim Fit Blazer</a></h6>
                                        <p class="price"> $300.00 </p>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center pt-40">
                    <div class="slider-btn-wrap2">
                        <div class="slider-btn product-slider-prev">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 13C11 10.5 6 8 3 7C6 6 10.5 4.5 12 1" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="fractional-pagination2"></div>
                        <div class="slider-btn product-slider-next">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1C2 3.5 7 6 10 7C7 8 2.5 9.5 1 13" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Best selling product section ends here -->
    <!-- tesimonial section strats here -->
    <div class="testimonial-section mb-100">
        <div class="container-fluid">
            <div class="row wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">
                <div class="col-lg-12 mb-50">
                    <div class="section-title text-center">
                        <span>Client Experiences</span>
                        <h3>What Our Clients Say</h3>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="swiper testimonial-swiper-slide">
                        <div class="swiper-wrapper">
                            <?php
                            foreach ($testimonial as $test) {
                            ?>
                                <div class="swiper-slide">
                                    <div class="testimonial-content">
                                        <div class="rating">
                                            <ul>
                                                <?php
                                                $rating = (int)($test['rating'] ?? 00);
                                                $maxstar = 5;
                                                for ($i = 1; $i <= $maxstar; $i++) {
                                                    if ($i <= $rating) {
                                                ?>
                                                        <li><i class="bi bi-star-fill"></i></li>
                                                <?php
                                                    } else {
                                                        echo '<li><i class="bi bi-star"></i></li>';
                                                    }
                                                }
                                                ?>

                                            </ul>
                                        </div>
                                        <p><?= $test['testimonial_text'] ?></p>
                                        <div class="author-area">
                                            <h5><?= $test['client_name'] ?></h5>
                                            <span><?= $test['client_title'] ?></span>
                                        </div>
                                    </div>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center pt-50">
                    <div class="slider-btn-wrap3">
                        <div class="slider-btn testimonial-slider-prev">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 13C11 10.5 6 8 3 7C6 6 10.5 4.5 12 1" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                        <div class="fractional-pagination3"></div>
                        <div class="slider-btn testimonial-slider-next">
                            <svg width="13" height="14" viewBox="0 0 13 14" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M1 1C2 3.5 7 6 10 7C7 8 2.5 9.5 1 13" stroke-width="1.5"
                                    stroke-linecap="round" />
                            </svg>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- tesimonial section ends here -->
    <!-- Newsletter section strats here -->
    <div class="newsletter-section mb-100">
        <div class="container">
            <div class="row g-0 p-0 wow animate fadeInDown" data-wow-delay="200ms" data-wow-duration="1500ms">

                <div class="col-lg-4">
                    <div class="newsletter-image">
                        <img src="assets/image/news-letter/2.png" alt="">
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="nwesletter-card">
                        <div class="newsletter-content text-center">
                            <span>Exclusive Access</span>
                            <h3>Elevate Your Style with Bespoke Updates</h3>
                            <p>Subscribe for tailoring tips, premium fabrics, and personalized style inspiration.</p>
                            <button class="primary-btn2">GET STARTED</button>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="newsletter-image">
                        <img src="assets/image/news-letter/1.png" alt="">
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- Newsletter section ends here -->
    <!-- Galleary section strats here -->
    <div class="gallery-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="sub-title mb-30">
                        <h6>
                            <svg width="21" height="13" viewBox="0 0 21 13" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M9.75024 10.9373C8.83968 11.6054 7.71592 12 6.5 12C3.46243 12 1 9.53757 1 6.5C1 3.46243 3.46243 1 6.5 1C7.71592 1 8.83968 1.39457 9.75024 2.06265C8.66454 3.2243 8 4.78454 8 6.5C8 8.21546 8.66454 9.7757 9.75024 10.9373ZM10.5 11.6238C9.39703 12.4861 8.00853 13 6.5 13C2.91015 13 0 10.0899 0 6.5C0 2.91015 2.91015 0 6.5 0C8.00853 0 9.39703 0.513889 10.5 1.37616C11.603 0.513889 12.9915 0 14.5 0C18.0899 0 21 2.91015 21 6.5C21 10.0899 18.0899 13 14.5 13C12.9915 13 11.603 12.4861 10.5 11.6238ZM11.2498 2.06265C12.1603 1.39457 13.2841 1 14.5 1C17.5376 1 20 3.46243 20 6.5C20 9.53757 17.5376 12 14.5 12C13.2841 12 12.1603 11.6054 11.2498 10.9373C12.3355 9.7757 13 8.21546 13 6.5C13 4.78454 12.3355 3.2243 11.2498 2.06265ZM10.5 2.72506C11.4299 3.71007 12 5.03846 12 6.5C12 7.96154 11.4299 9.28993 10.5 10.2749C9.57008 9.28993 9 7.96154 9 6.5C9 5.03846 9.57008 3.71007 10.5 2.72506Z"
                                    fill="#1E1E1E" />
                            </svg>
                            Instagram feed
                            <svg class="vector2" width="122" height="6" viewBox="0 0 122 6"
                                xmlns="http://www.w3.org/2000/svg">
                                <path d="M122 3L117 0.113249V5.88675L122 3ZM0 3.5H117.5V2.5H0V3.5Z" />
                            </svg>
                        </h6>
                    </div>
                </div>
            </div>
        </div>
        <div class="swiper gallery-slider">
            <div class="swiper-wrapper">
                <?php
                foreach ($gallery as $gal) {
                ?>
                    <div class="swiper-slide">
                        <div class="single-gallery-img">
                            <a href="https://www.instagram.com/" class="view-btn">
                                <svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg">
                                    <g>
                                        <path
                                            d="M9.625 0H4.375C1.95913 0 0 1.95913 0 4.375V9.625C0 12.0409 1.95913 14 4.375 14H9.625C12.0409 14 14 12.0409 14 9.625V4.375C14 1.95913 12.0409 0 9.625 0ZM12.6875 9.625C12.6875 11.3138 11.3138 12.6875 9.625 12.6875H4.375C2.68625 12.6875 1.3125 11.3138 1.3125 9.625V4.375C1.3125 2.68625 2.68625 1.3125 4.375 1.3125H9.625C11.3138 1.3125 12.6875 2.68625 12.6875 4.375V9.625Z" />
                                        <path
                                            d="M7 3.5C5.06712 3.5 3.5 5.06712 3.5 7C3.5 8.93288 5.06712 10.5 7 10.5C8.93288 10.5 10.5 8.93288 10.5 7C10.5 5.06712 8.93288 3.5 7 3.5ZM7 9.1875C5.79425 9.1875 4.8125 8.20575 4.8125 7C4.8125 5.79338 5.79425 4.8125 7 4.8125C8.20575 4.8125 9.1875 5.79338 9.1875 7C9.1875 8.20575 8.20575 9.1875 7 9.1875Z" />
                                        <path
                                            d="M10.7623 3.70423C11.0198 3.70423 11.2286 3.49543 11.2286 3.23786C11.2286 2.98029 11.0198 2.77148 10.7623 2.77148C10.5047 2.77148 10.2959 2.98029 10.2959 3.23786C10.2959 3.49543 10.5047 3.70423 10.7623 3.70423Z" />
                                    </g>
                                </svg>
                            </a>
                            <img src="<?= $gal ?>" alt="">
                        </div>
                    </div>
                <?php } ?>

            </div>
        </div>
    </div>
    <!-- Galleary section ends here -->
    <!-- footer section strats here -->
    <?php include_once "includes/footer.php"; ?>
    <!-- footer section end here -->


    <!-- Simple JavaScript to ensure video plays (handles browser autoplay restrictions) -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const video = document.querySelector('.banner-video');

            if (video) {
                // Ensure video plays
                video.play().catch(function(error) {
                    console.log("Autoplay prevented. Adding user interaction fallback.");
                    // Add click anywhere to play video
                    document.body.addEventListener('click', function playVideo() {
                        video.play();
                        document.body.removeEventListener('click', playVideo);
                    });
                });

                // Ensure video loops properly
                video.addEventListener('ended', function() {
                    video.play();
                });
            }
        });
    </script>

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
        $(".marquee_text1").marquee({
            direction: "left",
            duration: 25000,
            gap: 50,
            delayBeforeStart: 0,
            duplicated: true,
            startVisible: true,
        });
        $(".marquee_text2").marquee({
            direction: "right",
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