<?php
include_once __DIR__ . "/../config/connect.php";
include_once __DIR__ . "/../util/function.php";

$contact = contact_us();
$logo = get_header_logo();
$footer_logo = get_footer_logo();
$gallery = get_gallery();
$about = fetch_about();
$banner = fetch_banner();
$testimonial = testimonial();
$products = get_all_product();
$product_home = get_home_product();
$trending_pro = get_trending_product();
$brand = get_best_brand();
?> 
 <header class="header-area header1">
     <div class="header-logo">
         <!-- <a href="index.php"><h1 class="text-light">The Best</h1></a> -->
         <a href="<?= BASE_URL ?>">
             <img alt="image" class="img-fluid" src="<?= BASE_URL ?>assets/image/logo/logo2.png" style="max-width: 22%;">
         </a>
     </div>
     <div class="main-menu">
         <div class="mobile-menu-logo">
             <a href="<?= BASE_URL ?>">
                 <img alt="image" class="img-fluid" src="<?= BASE_URL ?>assets/image/logo/logo2.png">
             </a>
         </div>
         <ul class="menu-list">
             <li class="menu-item-has-children active">
                 <a href="<?= BASE_URL ?>">HOME </a><i class="bi bi-plus dropdown-icon"></i>

             </li>
             <li><a href="<?= BASE_URL ?>about.php">About</a></li>

             <!-- <li class="menu-item-has-children position-inherit">
                    <a href="#" class="drop-down"> Shop</a><i class="bi bi-plus dropdown-icon"></i>
                    <div class="sub-menu mega-menu">
                        <div class="container">
                            <div class="row g-xl-4 g-0">
                                <div class="col-xl-3">
                                    <div class="megamenu-items">
                                        <h6>Shop Layout</h6>
                                        <ul class="menu-list">
                                            <li><a href="shop-slider.html">shop slider</a></li>
                                            <li><a href="1columns-left.html">1 Columns left sidebar</a></li>
                                            <li><a href="1columns-right.html">1 Columns right sidebar</a></li>
                                            <li><a href="2columns-left.html">2 Columns left sidebar</a></li>
                                            <li><a href="2columns-right.html">2 Columns right sidebar</a></li>
                                            <li><a href="3columns-page.html">3 Columns page nosidebar</a></li>
                                            <li><a href="3columns-left.html">3 Columns left sidebar </a></li>
                                            <li><a href="3columns-right.html">3 Columns right sidebar </a></li>
                                            <li><a href="4columns-page.html">4 Columns page</a></li>
                                            <li><a href="category-top.html">Category Top</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="megamenu-items">
                                        <h6>Filter Layout</h6>
                                        <ul class="menu-list">
                                            <li><a href="3columns-left.html">Left sidebar</a></li>
                                                        <li><a href="3columns-right.html">Right sidebar</a></li>
                                                        <li><a href="category-top.html">Filter Hidden</a></li>
                                                        <li><a href="top-filter-bar.html">Filter Top</a></li>
                                        </ul>
                                    </div>
                                    <div class="megamenu-items">
                                        <h6>Woo Pages</h6>
                                        <ul class="menu-list">
                                            <li><a href="cart-page.html">cart page</a></li>
                                            <li><a href="checkout-page.html">checkout page</a></li>
                                            <li><a href="whislist.html">whistlist</a></li>
                                            <li><a href="my-account.html">My Account</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="megamenu-items">
                                        <h6>Product details</h6>
                                        <ul class="menu-list">
                                            <li><a href="product-details.html">Product Default</a></li>
                                            <li><a href="product-details.html">Product Thumbnail Left</a>
                                            </li>
                                            <li><a href="product-details2.html">Product Thumbnail
                                                    Right</a></li>
                                            <li><a href="product-details3.html">Product Slider</a></li>
                                            <li><a href="product-details4.html">Offer Countdown</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <div class="col-xl-3 dd--none">
                                    <div class="megamenu-items">
                                        <h6>Best selling products</h6>
                                        <div class="swiper menu-product-slider">
                                            <div class="swiper-wrapper">
                                                <div class="swiper-slide">
                                                    <div class="product-card">
                                                        <div class="product-card-img">
                                                            <a href="product-details.html">
                                                                <img src="assets/image/home1/product-image4.jpg" alt="">
                                                            </a>
                                                            <div class="overlay">
                                                                <div class="cart-area">
                                                                    <a class="add-cart-btn" href="cart-page.html"><i
                                                                            class="bi bi-bag-check"></i>
                                                                        Add To Cart</a>
                                                                </div>
                                                            </div>
                                                            <div class="view-and-favorite-area">
                                                                <ul>
                                                                    <li>
                                                                        <a href="whislist.html">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="18" height="18"
                                                                                viewBox="0 0 18 18">
                                                                                <g clip-path="url(#clip0_168_378)">
                                                                                    <path
                                                                                        d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                                                    </path>
                                                                                </g>
                                                                            </svg>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a data-bs-toggle="modal"
                                                                            data-bs-target="#product-view">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="22" height="22"
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
                                                            <h6><a class="hover-underline" href="product-details.html">Trendy & Comfortable Outerwear</a></h6>
                                                            <p class="price"><del>$345.00</del> $300.00 </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="swiper-slide">
                                                    <div class="product-card">
                                                        <div class="product-card-img">
                                                            <a href="product-details.html">
                                                                <img src="assets/image/home1/product-image3.jpg" alt="">
                                                            </a>
                                                            <div class="overlay">
                                                                <div class="cart-area">
                                                                    <a class="add-cart-btn" href="cart-page.html"><i
                                                                            class="bi bi-bag-check"></i>
                                                                        Add To Cart</a>
                                                                </div>
                                                            </div>
                                                            <div class="view-and-favorite-area">
                                                                <ul>
                                                                    <li>
                                                                        <a href="whislist.html">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="18" height="18"
                                                                                viewBox="0 0 18 18">
                                                                                <g clip-path="url(#clip0_168_378)">
                                                                                    <path
                                                                                        d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                                                    </path>
                                                                                </g>
                                                                            </svg>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a data-bs-toggle="modal"
                                                                            data-bs-target="#product-view">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="22" height="22"
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
                                                            <h6><a class="hover-underline" href="product-details.html">Trendy Oversized Shirt</a></h6>
                                                            <p class="price">$220.00 </p>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="swiper-slide">
                                                    <div class="product-card">
                                                        <div class="product-card-img">
                                                            <a href="product-details.html">
                                                                <img src="assets/image/home1/product-image2.jpg" alt="">
                                                            </a>
                                                            <div class="overlay">
                                                                <div class="cart-area">
                                                                    <a class="add-cart-btn" href="cart-page.html"><i
                                                                            class="bi bi-bag-check"></i>
                                                                        Add To Cart</a>
                                                                </div>
                                                            </div>
                                                            <div class="view-and-favorite-area">
                                                                <ul>
                                                                    <li>
                                                                        <a href="whislist.html">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="18" height="18"
                                                                                viewBox="0 0 18 18">
                                                                                <g clip-path="url(#clip0_168_378)">
                                                                                    <path
                                                                                        d="M16.528 2.20919C16.0674 1.71411 15.5099 1.31906 14.8902 1.04859C14.2704 0.778112 13.6017 0.637996 12.9255 0.636946C12.2487 0.637725 11.5794 0.777639 10.959 1.048C10.3386 1.31835 9.78042 1.71338 9.31911 2.20854L9.00132 2.54436L8.68352 2.20854C6.83326 0.217151 3.71893 0.102789 1.72758 1.95306C1.63932 2.03507 1.5541 2.12029 1.47209 2.20854C-0.490696 4.32565 -0.490696 7.59753 1.47209 9.71463L8.5343 17.1622C8.77862 17.4201 9.18579 17.4312 9.44373 17.1868C9.45217 17.1788 9.46039 17.1706 9.46838 17.1622L16.528 9.71463C18.4907 7.59776 18.4907 4.32606 16.528 2.20919ZM15.5971 8.82879H15.5965L9.00132 15.7849L2.40553 8.82879C0.90608 7.21113 0.90608 4.7114 2.40553 3.09374C3.76722 1.61789 6.06755 1.52535 7.5434 2.88703C7.61505 2.95314 7.68401 3.0221 7.75012 3.09374L8.5343 3.92104C8.79272 4.17781 9.20995 4.17781 9.46838 3.92104L10.2526 3.09438C11.6142 1.61853 13.9146 1.52599 15.3904 2.88767C15.4621 2.95378 15.531 3.02274 15.5971 3.09438C17.1096 4.71461 17.1207 7.2189 15.5971 8.82879Z">
                                                                                    </path>
                                                                                </g>
                                                                            </svg>
                                                                        </a>
                                                                    </li>
                                                                    <li>
                                                                        <a data-bs-toggle="modal"
                                                                            data-bs-target="#product-view">
                                                                            <svg xmlns="http://www.w3.org/2000/svg"
                                                                                width="22" height="22"
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
                                                            <h6><a class="hover-underline" href="product-details.html">Classic Slim Fit Blazer</a></h6>
                                                            <p class="price"><del>$355.00</del> $280.00 </p>
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
                </li> -->
             <!-- <li class="menu-item-has-children">
                    <a href="#" class="drop-down"> BLOG</a><i class="bi bi-plus dropdown-icon"></i>
                    <ul class="sub-menu">
                        <li><a href="blog-grid.html">Blog Grid</a></li>
                        <li><a href="blog-standard-left.html">Blog Standard</a></li>
                        <li><a href="blog-details.html">Blog Details</a></li>
                    </ul>
                </li> -->
             <!-- <li class="menu-item-has-children">
                    <a href="#" class="drop-down"> PAGES</a><i class="bi bi-plus dropdown-icon"></i>
                    <ul class="sub-menu">   
                        <li><a href="about-us.html">About Us</a></li>
                        <li>
                            <a href="categories.html">Category</a>
                            <i class="d-xl-flex d-none bi bi-chevron-right dropdown-icon"></i>
                            <i class="d-xl-none d-flex bi bi-plus dropdown-icon"></i>
                            <ul class="sub-menu">
                                <li><a href="categories.html">Category Style 01</a></li>
                                <li><a href="categories2.html">Category Style 02</a></li>
                            </ul>
                        </li>
                        <li><a href="faq.html">FAQ's</a></li>
                        <li><a href="error.html">Error</a></li>
                    </ul>
                </li> -->
             <li><a href="<?= BASE_URL ?>products.php">Spring summer collection</a></li>
             <li><a href="<?= BASE_URL ?>products.php">winter collection</a></li>
             <li><a href="<?= BASE_URL ?>contact.php">Contact</a></li>
         </ul>
         <div class="d-xl-none d-block">
             <div class="mobile-search-area mb-30">
                 <form action="<?= BASE_URL ?>search.php?text=<?= base64_encode('text') ?>">
                     <div class="form-inner">
                         <input type="text" placeholder="Enter your keywords">
                         <button type="submit" class="primary-btn">Search Now</button>
                     </div>
                 </form>
             </div>
         </div>
     </div>
     <div class="nav-right">
         <ul>
             <li>
                 <div class="search-bar d-xl-flex d-none">
                     <div class="search-btn">
                         <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                             <path
                                 d="M8.20349 1.44849C6.41514 1.45148 4.70089 2.16323 3.43633 3.42779C2.17178 4.69235 1.46003 6.40659 1.45703 8.19494C1.45853 9.9848 2.16943 11.7011 3.43399 12.9678C4.69855 14.2345 6.41364 14.9482 8.20349 14.9527C9.79089 14.9527 11.2536 14.3943 12.4101 13.4702L16.0578 17.1182C16.2002 17.2505 16.3882 17.3225 16.5825 17.3191C16.7768 17.3157 16.9622 17.2372 17.0998 17.0999C17.2374 16.9627 17.3165 16.7775 17.3204 16.5832C17.3243 16.3889 17.2528 16.2007 17.1208 16.058L13.4731 12.4072C14.4325 11.214 14.9556 9.72887 14.9556 8.19778C14.9556 4.47872 11.9225 1.44849 8.20349 1.44849ZM8.20349 2.95085C11.1118 2.95085 13.4533 5.28943 13.4533 8.19494C13.4533 11.1005 11.1118 13.4532 8.20349 13.4532C5.29514 13.4532 2.95656 11.109 2.95656 8.20061C2.95656 5.29227 5.29514 2.95085 8.20349 2.95085Z" />
                         </svg>
                     </div>
                     <div class="search-input">
                         <div class="serch-close"></div>
                         <form>
                             <div class="search-group">
                                 <div class="form-inner2">
                                     <input type="text" placeholder="Enter your keywords">
                                     <button type="submit"><i class="bi bi-search"></i></button>
                                 </div>
                             </div>
                             <div class="quick-search">
                                 <ul>
                                     <li>Quick Search :</li>
                                     <li><a href="services-01.html">Classic Haircut,</a></li>
                                     <li><a href="services-01.html">Coloring Services,</a></li>
                                     <li><a href="services-01.html">Hair Extensions,</a></li>
                                     <li><a href="services-01.html">Specialty Services,</a></li>
                                     <li><a href="services-01.html">Haircuts and Styling,</a></li>
                                     <li><a href="services-01.html">Men's Grooming,</a></li>
                                     <li><a href="services-01.html">Makeover Package,</a></li>
                                 </ul>
                             </div>
                         </form>
                     </div>
                 </div>
             </li>
             <li>
                 <div class="user">
                     <a href="#" data-bs-toggle="modal" data-bs-target="#user-login">
                         <svg width="18" height="18" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                             <path
                                 d="M11.7135 8.34627C12.8653 7.50628 13.6153 6.14686 13.6153 4.61538C13.6153 2.07046 11.5448 0 8.99989 0C6.45497 0 4.38451 2.07046 4.38451 4.61538C4.38451 6.14686 5.1345 7.50628 6.28629 8.34627C3.42316 9.44191 1.38452 12.2179 1.38452 15.4615C1.38452 16.8613 2.52327 18 3.92298 18H14.0768C15.4765 18 16.6153 16.8613 16.6153 15.4615C16.6153 12.2179 14.5766 9.44191 11.7135 8.34627ZM5.76914 4.61538C5.76914 2.83395 7.21845 1.38463 8.99989 1.38463C10.7813 1.38463 12.2306 2.83395 12.2306 4.61538C12.2306 6.39682 10.7813 7.84617 8.99989 7.84617C7.21845 7.84617 5.76914 6.39682 5.76914 4.61538ZM14.0768 16.6154H3.92298C3.28676 16.6154 2.76915 16.0978 2.76915 15.4615C2.76915 12.0258 5.56421 9.23073 8.99993 9.23073C12.4356 9.23073 15.2307 12.0258 15.2307 15.4615C15.2307 16.0978 14.7131 16.6154 14.0768 16.6154Z" />
                         </svg>
                     </a>
                 </div>
             </li>
             <li>
                 <div class="cart-area">
                     <a href="whislist.php">
                         <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                             <path
                                 d="M9.00035 16.3798C8.75818 16.3804 8.51829 16.333 8.29455 16.2403C8.07081 16.1477 7.86764 16.0116 7.69679 15.84L1.73357 9.87658C0.848151 8.99116 0.360352 7.81396 0.360352 6.56152V6.48826C0.360352 5.23582 0.848151 4.05844 1.73357 3.1732C2.61899 2.28796 3.79655 1.7998 5.04845 1.7998H5.12225C6.37415 1.7998 7.55171 2.2876 8.43713 3.17302L9.00035 3.73624L9.56357 3.17302C10.449 2.2876 11.6266 1.7998 12.8785 1.7998H12.9523C14.2042 1.7998 15.3817 2.2876 16.2671 3.17302C17.1526 4.05844 17.6404 5.23564 17.6404 6.48808V6.56134C17.6404 7.81378 17.1526 8.99116 16.2671 9.8764L10.3039 15.8398C10.1331 16.0115 9.92994 16.1476 9.70619 16.2403C9.48244 16.333 9.24254 16.3804 9.00035 16.3798ZM8.46035 15.0762C8.74979 15.3651 9.25145 15.3644 9.54035 15.0761L15.5036 9.1132C15.8396 8.77883 16.106 8.38115 16.2874 7.94317C16.4688 7.50518 16.5616 7.03558 16.5604 6.56152V6.48826C16.5604 5.52436 16.1849 4.61824 15.5036 3.93676C14.8223 3.25528 13.9158 2.8798 12.9523 2.8798H12.8785C12.4044 2.87847 11.9349 2.97119 11.4969 3.15259C11.059 3.33398 10.6614 3.60046 10.3271 3.93658L9.38213 4.88158C9.33201 4.93175 9.2725 4.97155 9.20699 4.99871C9.14148 5.02586 9.07126 5.03984 9.00035 5.03984C8.92944 5.03984 8.85922 5.02586 8.79371 4.99871C8.7282 4.97155 8.66869 4.93175 8.61857 4.88158L7.67357 3.93658C7.33933 3.60046 6.94173 3.33398 6.50379 3.15259C6.06585 2.97119 5.59627 2.87847 5.12225 2.8798H5.04845C4.08491 2.8798 3.17843 3.25492 2.49713 3.93658C1.81583 4.61824 1.44035 5.52418 1.44035 6.48808V6.56134C1.43908 7.03538 1.53183 7.50497 1.71322 7.94292C1.89462 8.38089 2.16106 8.77853 2.49713 9.11284L8.46035 15.0762Z"
                                 fill="white" />
                         </svg>
                     </a>
                     <span>0</span>
                 </div>
             </li>
             <li>
                 <div class="cart-area">
                     <a href="cart.php">
                         <svg width="18" height="18" viewBox="0 0 18 18" fill="none"
                             xmlns="http://www.w3.org/2000/svg">
                             <path
                                 d="M3.375 4.78125H14.625M6.1875 4.78125V3.51562C6.1875 1.96232 7.44669 0.703125 9 0.703125C10.5533 0.703125 11.8125 1.96232 11.8125 3.51562V4.78125M11.8125 7.59375C11.8125 9.14706 10.5533 10.4062 9 10.4062C7.44669 10.4062 6.1875 9.14706 6.1875 7.59375"
                                 stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                 stroke-linejoin="round" />
                             <path
                                 d="M14.625 4.78125L16.0201 15.7131C16.0275 15.772 16.0313 15.8313 16.0312 15.8906C16.0312 16.6673 15.4016 17.2969 14.625 17.2969H3.375C2.59836 17.2969 1.96875 16.6673 1.96875 15.8906C1.96875 15.8305 1.97251 15.7712 1.97986 15.7131L3.375 4.78125"
                                 stroke="white" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                                 stroke-linejoin="round" />
                         </svg>
                     </a>
                     <span>0</span>
                 </div>
             </li>
         </ul>
         <div class="sidebar-button mobile-menu-btn ">
             <span></span>
         </div>
     </div>
 </header>