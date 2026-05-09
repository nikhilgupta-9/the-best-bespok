<div class="product-card-section mb-100">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 mb-50">
                <div class="section-title">
                    <h3>Related products </h3>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12 position-relative">
                <div class="swiper home1-product-swiper">
                    <div class="swiper-wrapper">
                        <?php
                        $related_pro = select_query('products', 'where status=1 order by rand() limit 8');
                        foreach ($related_pro as $rel_pro) {
                            $images = explode(",", $rel_pro['pro_img']);
                            $first_img = $images[0];
                        ?>
                            <div class="swiper-slide">
                                <div class="product-card">
                                    <div class="product-card-img">
                                        <a href="<?= BASE_URL ?>product-details/<?= $rel_pro['slug_url'] ?>">
                                            <img src="<?= BASE_URL ?>admin/assets/img/uploads/<?= $first_img ?>" alt="<?= $rel_pro['pro_name'] ?>">
                                        </a>
                                        <div class="batch">
                                            <span class="new">23% off</span>
                                            <span>Hot deal</span>
                                        </div>
                                        <div class="overlay">
                                            <div class="cart-area">
                                                <a class="add-cart-btn" href="cart.php"><i class="bi bi-bag-check"></i>
                                                    Add To Cart</a>
                                            </div>
                                        </div>
                                        <div class="view-and-favorite-area">
                                            <ul>
                                                <li>
                                                    <a href="whislist.html">
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
                                        <h6><a class="hover-underline" href="<?= BASE_URL ?>product-details/<?= $rel_pro['slug_url'] ?>"><?= $rel_pro['pro_name'] ?></a></h6>
                                        <p class="price"><del>$<?= number_format($rel_pro['mrp'], 2) ?></del> $<?= number_format($rel_pro['selling_price'], 2) ?> </p>
                                        <ul class="color-tag">
                                            <li class="medium-brown"><span>Brown</span></li>
                                            <li class="dark-lavender"><span> Lavender</span></li>
                                            <li class="amber-yellow"><span> Yellow</span></li>
                                            <li class="plus-icon">
                                                <a href="<?= BASE_URL ?>product-details/<?= $rel_pro['slug_url'] ?>">
                                                    <svg width="8" height="8" viewBox="0 0 8 8" fill="none"
                                                        xmlns="http://www.w3.org/2000/svg">
                                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                                            d="M3.5 4.5V8H4.5V4.5H8V3.5H4.5V0H3.5V3.5H0V4.5H3.5Z"
                                                            fill="black" />
                                                    </svg>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php
                        }
                        ?>


                    </div>
                </div>
            </div>
        </div>
    </div>
</div>