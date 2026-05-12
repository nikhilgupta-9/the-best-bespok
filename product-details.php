<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Get product slug from URL
$slug = isset($_GET['alias']) ? mysqli_real_escape_string($conn, $_GET['alias']) : '';

if (empty($slug)) {
    header("Location: " . BASE_URL);
    exit();
}

// Fetch product details
$product_query = "SELECT p.*, c.name as category_name, sc.name as sub_category_name 
                  FROM products p 
                  LEFT JOIN categories c ON p.category_id = c.id 
                  LEFT JOIN sub_categories sc ON p.sub_category_id = sc.id 
                  WHERE p.slug_url = '$slug' AND p.status = 1";
$product_result = mysqli_query($conn, $product_query);
$product = mysqli_fetch_assoc($product_result);

if (!$product) {
    header("Location: " . BASE_URL);
    exit();
}

// Fetch product images
$images = !empty($product['pro_img']) ? explode(',', $product['pro_img']) : [];
$main_image = !empty($images) ? trim($images[0]) : 'default-product.jpg';

// Fetch product sizes
$sizes_query = "SELECT * FROM product_sizes WHERE product_id = " . $product['id'] . " AND is_available = 1 ORDER BY sort_order ASC";
$sizes_result = mysqli_query($conn, $sizes_query);
$sizes = [];
while ($size = mysqli_fetch_assoc($sizes_result)) {
    $sizes[] = $size;
}

// Fetch product fabrics
$fabrics_query = "SELECT pf.*, f.name, f.material, f.price_modifier, f.image, f.swatch_color 
                  FROM product_fabric_map pf 
                  JOIN fabric_options f ON pf.fabric_id = f.id 
                  WHERE pf.product_id = " . $product['id'] . " AND f.is_available = 1 
                  ORDER BY pf.sort_order ASC";
$fabrics_result = mysqli_query($conn, $fabrics_query);
$fabrics = [];
while ($fabric = mysqli_fetch_assoc($fabrics_result)) {
    $fabrics[] = $fabric;
}

// Fetch product colors
$colors_query = "SELECT pc.*, c.name, c.hex_code, c.color_family 
                 FROM product_color_map pc 
                 JOIN color_options c ON pc.color_id = c.id 
                 WHERE pc.product_id = " . $product['id'] . " AND c.is_available = 1 
                 ORDER BY c.display_order ASC";
$colors_result = mysqli_query($conn, $colors_query);
$colors = [];
while ($color = mysqli_fetch_assoc($colors_result)) {
    $colors[] = $color;
}

// Fetch customization options available for this product
$customization_query = "SELECT co.*, pcm.id as map_id 
                        FROM customization_options co 
                        JOIN product_customization_map pcm ON co.id = pcm.customization_option_id 
                        WHERE pcm.product_id = " . $product['id'] . " AND co.is_available = 1 
                        ORDER BY co.display_order ASC";
$customization_result = mysqli_query($conn, $customization_query);
$customization_options = [];
while ($opt = mysqli_fetch_assoc($customization_result)) {
    $group = $opt['group_name'];
    if (!isset($customization_options[$group])) {
        $customization_options[$group] = [];
    }
    $customization_options[$group][] = $opt;
}

// Get default fabric
$default_fabric = null;
foreach ($fabrics as $f) {
    if ($f['is_default'] == 1) {
        $default_fabric = $f;
        break;
    }
}
if (!$default_fabric && !empty($fabrics)) {
    $default_fabric = $fabrics[0];
}

// Calculate prices
$base_price = $product['selling_price'];
$fabric_modifier = $default_fabric ? $default_fabric['price_modifier'] : 0;
$display_price = $base_price + $fabric_modifier;
$discount = 0;
if ($product['mrp'] > 0 && $product['selling_price'] > 0) {
    $discount = round((($product['mrp'] - $product['selling_price']) / $product['mrp']) * 100);
}

// Fetch related products
$related_query = "SELECT * FROM products WHERE category_id = " . $product['category_id'] . " 
                  AND id != " . $product['id'] . " AND status = 1 
                  ORDER BY RAND() LIMIT 8";
$related_result = mysqli_query($conn, $related_query);
$related_products = [];
while ($rel = mysqli_fetch_assoc($related_result)) {
    $related_products[] = $rel;
}

// Fetch product reviews
$reviews_query = "SELECT * FROM product_reviews WHERE product_id = " . $product['id'] . " ORDER BY created_at DESC LIMIT 10";
$reviews_result = mysqli_query($conn, $reviews_query);

// Calculate average rating
$avg_rating_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM product_reviews WHERE product_id = " . $product['id'];
$avg_result = mysqli_query($conn, $avg_rating_query);
$rating_data = mysqli_fetch_assoc($avg_result);
$avg_rating = round($rating_data['avg_rating'] ?? 0, 1);
$total_reviews = $rating_data['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['meta_title'] ?: $product['pro_name']) ?> | The Best</title>
    <meta name="description" content="<?= htmlspecialchars($product['meta_desc'] ?: strip_tags($product['short_desc'])) ?>">
    <meta name="keywords" content="<?= htmlspecialchars($product['meta_key']) ?>">
    <!-- Swiper css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/swiper-bundle.min.css">
    <!-- Fancybox css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/jquery.fancybox.min.css">
    <!-- Animation css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/animate.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/nice-select.css">
    <!-- bootstrap css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/bootstrap.min.css">
    <!-- Boxicon css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/boxicons.min.css">
    <!-- My css link -->
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="icon" href="<?= BASE_URL ?>assets/image/thumbnail.svg" type="image/gif" sizes="20x20">

    <style>
        .fabric-option,
        .color-option,
        .size-option {
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .fabric-option.selected,
        .color-option.selected,
        .size-option.selected {
            border: 2px solid #000 !important;
            transform: scale(1.02);
        }

        .fabric-price {
            font-size: 12px;
            font-weight: 600;
        }

        .price-plus {
            color: #27ae60;
        }

        .price-minus {
            color: #e74c3c;
        }

        .price-update {
            transition: all 0.3s ease;
        }

        .quantity__input {
            width: 60px;
            text-align: center;
        }
    </style>
</head>

<body data-logged-in="<?= !empty($_SESSION['user_id']) ? '1' : '0' ?>">

    <!-- product view modal -->
    <div class="modal product-view-modal" id="product-view">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="close-btn" data-bs-dismiss="modal"></div>
                    <div class="shop-details-top-section">
                        <div class="row g-4" id="quickViewContent">
                            <!-- Quick view content loaded via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- hearder section starts here -->
    <?php
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- breadcrumb section starts here -->
    <div class="breadcrumb-section mb-100"
        style="background-image: linear-gradient(180deg, rgba(0, 0, 0, 0.35), rgba(0, 0, 0, 0.35)), url(<?= BASE_URL ?>assets/image/background/gallery.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 d-flex justify-content-center">
                    <div class="banner-content style-2 text-center">
                        <h1><?= htmlspecialchars($product['pro_name']) ?></h1>
                        <ul class="breadcrumb-list">
                            <li><a href="<?= BASE_URL ?>">Home</a></li>
                            <li><a href="<?= BASE_URL ?>shop">Shop</a></li>
                            <li><span>/</span> <?= htmlspecialchars($product['pro_name']) ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Start Shop Details top section -->
    <div class="shop-details-top-section mb-70">
        <div class="container-xl container-fluid-lg container">
            <div class="row gy-5">
                <div class="col-lg-6">
                    <div class="shop-details-img">
                        <div class="tab-content" id="v-pills-tabContent2">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $idx => $img):
                                    $img = trim($img);
                                    if (empty($img)) continue;
                                ?>
                                    <div class="tab-pane fade <?= $idx == 0 ? 'show active' : '' ?>" id="v-pills2-img<?= $idx + 1 ?>" role="tabpanel">
                                        <div class="shop-details-tab-img product-img--main" data-scale="1.4"
                                            data-image="<?= BASE_URL ?>admin/assets/img/uploads/<?= htmlspecialchars($img) ?>">
                                            <img src="<?= BASE_URL ?>admin/assets/img/uploads/<?= htmlspecialchars($img) ?>"
                                                alt="<?= htmlspecialchars($product['pro_name']) ?>"
                                                onerror="this.src='<?= BASE_URL ?>assets/image/placeholder.jpg'">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="tab-pane fade show active" id="v-pills2-img1" role="tabpanel">
                                    <div class="shop-details-tab-img product-img--main">
                                        <img src="<?= BASE_URL ?>assets/image/placeholder.jpg" alt="No image available">
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="nav nav-pills" id="v-pills2-tab" role="tablist" aria-orientation="vertical">
                            <?php if (!empty($images)): ?>
                                <?php foreach ($images as $idx => $img):
                                    $img = trim($img);
                                    if (empty($img)) continue;
                                ?>
                                    <button class="nav-link <?= $idx == 0 ? 'active' : '' ?>" id="v-pills2-img<?= $idx + 1 ?>-tab"
                                        data-bs-toggle="pill" data-bs-target="#v-pills2-img<?= $idx + 1 ?>"
                                        type="button" role="tab" aria-selected="<?= $idx == 0 ? 'true' : 'false' ?>">
                                        <img src="<?= BASE_URL ?>admin/assets/img/uploads/<?= htmlspecialchars($img) ?>"
                                            alt="<?= htmlspecialchars($product['pro_name']) ?>"
                                            onerror="this.src='<?= BASE_URL ?>assets/image/placeholder.jpg'">
                                    </button>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="shop-details-content">
                        <h3><?= htmlspecialchars($product['pro_name']) ?></h3>
                        <div class="rating-review">
                            <div class="rating">
                                <div class="star">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi bi-star<?= $i <= $avg_rating ? '-fill' : ($i <= $avg_rating + 0.5 ? '-half' : '') ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p><a href="#reviews">(<?= $total_reviews ?> REVIEWS)</a></p>
                            </div>
                        </div>
                        <div class="price-area">
                            <p class="price">
                                <?php if ($product['mrp'] > $product['selling_price']): ?>
                                    <del>₹<?= number_format($product['mrp'], 2) ?></del>
                                <?php endif; ?>
                                <span id="displayPrice">₹<?= number_format($display_price, 2) ?></span>
                                <?php if ($discount > 0): ?>
                                    <span class="discount-badge">(<?= $discount ?>% OFF)</span>
                                <?php endif; ?>
                            </p>
                            <?php if ($fabric_modifier != 0): ?>
                                <small class="text-muted" id="fabricModifierNote">
                                    <?php if ($fabric_modifier > 0): ?>
                                        +₹<?= number_format($fabric_modifier, 2) ?> for selected fabric
                                    <?php elseif ($fabric_modifier < 0): ?>
                                        -₹<?= number_format(abs($fabric_modifier), 2) ?> for selected fabric
                                    <?php endif; ?>
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="quantity-color-area">
                            <!-- Fabric Selection -->
                            <?php if (!empty($fabrics)): ?>
                                <div class="quantity-color mb-3">
                                    <h6 class="widget-title">Select Fabric</h6>
                                    <div class="d-flex flex-wrap gap-2" id="fabricList">
                                        <?php foreach ($fabrics as $fabric): ?>
                                            <div class="fabric-option border rounded p-2 text-center <?= $default_fabric && $default_fabric['id'] == $fabric['id'] ? 'selected border-dark' : 'border-secondary' ?>"
                                                style="width: 100px; cursor: pointer;"
                                                data-fabric-id="<?= $fabric['fabric_id'] ?>"
                                                data-price-modifier="<?= $fabric['price_modifier'] ?>"
                                                data-name="<?= htmlspecialchars($fabric['name']) ?>"
                                                onclick="selectFabric(this, <?= $fabric['price_modifier'] ?>, <?= $base_price ?>)">
                                                <?php if (!empty($fabric['image']) && file_exists("admin/uploads/fabrics/" . $fabric['image'])): ?>
                                                    <img src="<?= BASE_URL ?>admin/uploads/fabrics/<?= htmlspecialchars($fabric['image']) ?>"
                                                        style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;"
                                                        alt="<?= htmlspecialchars($fabric['name']) ?>">
                                                <?php elseif (!empty($fabric['swatch_color'])): ?>
                                                    <div style="width: 60px; height: 60px; background: <?= htmlspecialchars($fabric['swatch_color']) ?>; border-radius: 6px; margin: 0 auto;"></div>
                                                <?php else: ?>
                                                    <div style="width: 60px; height: 60px; background: #ccc; border-radius: 6px; margin: 0 auto;"></div>
                                                <?php endif; ?>
                                                <div class="small mt-1"><?= htmlspecialchars($fabric['name']) ?></div>
                                                <div class="fabric-price <?= $fabric['price_modifier'] > 0 ? 'price-plus' : ($fabric['price_modifier'] < 0 ? 'price-minus' : '') ?>">
                                                    <?php if ($fabric['price_modifier'] > 0): ?>
                                                        +₹<?= number_format($fabric['price_modifier'], 2) ?>
                                                    <?php elseif ($fabric['price_modifier'] < 0): ?>
                                                        -₹<?= number_format(abs($fabric['price_modifier']), 2) ?>
                                                    <?php else: ?>
                                                        Base
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Color Selection -->
                            <?php if (!empty($colors)): ?>
                                <div class="quantity-color mb-3">
                                    <h6 class="widget-title">Color</h6>
                                    <ul class="color-list d-flex gap-2" id="colorList">
                                        <?php foreach ($colors as $color):

                                            $id   = isset($color['color_id']) ? (int)$color['color_id'] : 0;
                                            $name = isset($color['name']) ? htmlspecialchars($color['name']) : '';
                                            $hex  = isset($color['hex_code']) ? htmlspecialchars($color['hex_code']) : '#ccc';
                                            $isDefault = !empty($color['is_default']) ? 'selected' : '';
                                        ?>
                                            <li class="color-option select-wrap <?= $isDefault ?>"
                                                style="list-style: none;"
                                                data-color-id="<?= $id ?>"
                                                data-color-name="<?= $name ?>"
                                                data-hex="<?= $hex ?>"
                                                onclick="selectColor(this, '<?= $hex ?>')">

                                                <span
                                                    style="background: <?= $hex ?>; 
                                                    width: 30px; 
                                                    height: 30px; 
                                                    display: inline-block; 
                                                    border-radius: 50%; 
                                                    border: 2px solid #ddd;"
                                                    title="<?= $name ?>">
                                                </span>

                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <!-- Size Selection -->
                            <?php if (!empty($sizes) && $product['product_type'] != 'made_to_order'): ?>
                                <div class="quantity-color mb-3">
                                    <h6 class="widget-title">Size</h6>
                                    <div class="size-list">
                                        <ul class="d-flex flex-wrap gap-2" id="sizeList">
                                            <?php foreach ($sizes as $size):
                                                $price  = isset($size['price_modifier']) ? (float)$size['price_modifier'] : 0;
                                                $stock  = isset($size['stock']) ? (int)$size['stock'] : 0;
                                                $id     = isset($size['id']) ? (int)$size['id'] : 0;
                                                $label  = isset($size['size_label']) ? htmlspecialchars($size['size_label']) : '';
                                            ?>
                                                <li class="size-option select-wrap <?= !empty($size['is_default']) ? 'selected' : '' ?>"
                                                    data-size-id="<?= $id ?>"
                                                    data-size-label="<?= $label ?>"
                                                    data-price-modifier="<?= $price ?>"
                                                    data-stock="<?= $stock ?>"
                                                    onclick="selectSize(this, <?= $price ?>, <?= $stock ?>)">
                                                    <?= $label ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                    <div id="stockStatus" class="mt-2">
                                        <?php if (!empty($sizes) && $sizes[0]['stock'] > 0): ?>
                                            <small class="text-success">In Stock</small>
                                        <?php else: ?>
                                            <small class="text-danger">Out of Stock</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <!-- Quantity -->
                            <div class="quantity-color">
                                <div class="quantity-area">
                                    <div class="quantity">
                                        <a class="quantity__minus"><span><i class="bi bi-dash"></i></span></a>
                                        <input name="quantity" type="text" class="quantity__input" value="1" id="quantityInput">
                                        <a class="quantity__plus"><span><i class="bi bi-plus"></i></span></a>
                                    </div>
                                </div>
                                <h6 class="widget-title">Availability: <span id="availabilityStatus">
                                        <?php
                                        if ($product['product_type'] == 'made_to_order') {
                                            echo 'Made to Order';
                                        } elseif (!empty($sizes) && $sizes[0]['stock'] > 0) {
                                            echo 'In stock';
                                        } else {
                                            echo 'Out of stock';
                                        }
                                        ?>
                                    </span></h6>
                            </div>
                        </div>

                        <div class="shop-details-btn">
                            <button class="primary-btn" onclick="addToCart()">
                                <i class="bi bi-bag-check me-2"></i>ADD TO CART
                            </button>
                            <button class="primary-btn2" onclick="buyNow()">BUY NOW</button>

                            <?php if ($product['is_customizable'] == 1): ?>
                            <a class="primary-btn2" href="<?= BASE_URL ?>suit-configurator.php?id=<?= base64_encode($product['id']) ?>">Customization</a>
                            <?php endif; ?>

                        </div>

                        <ul class="product-shipping-delivers">
                            <li class="product-shipping">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M17.3967 3.99324L17.3979 3.21924C17.3874 2.95284 17.4375 2.60244 17.3193 2.34594C17.2395 2.07024 16.9521 1.84674 16.7685 1.64604L16.2171 1.10304L15.6534 0.551642C15.2238 0.151742..." />
                                </svg>
                                Fast Delivery In 24 hours max
                            </li>
                            <li class="product-delivers">
                                <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M5.13666 9.70208C5.26098 9.70208..." />
                                </svg>
                                Safe Payment
                            </li>
                        </ul>

                        <div class="compare-wishlist-area">
                            <ul>
                                <li>
                                    <a href="javascript:void(0)" onclick="addToWishlist(<?= $product['id'] ?>)">
                                        <span>
                                            <svg width="10" height="10" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M8.4692 1.95762C7.54537 1.2193 6.18482 1.36068 5.32818 2.17755L5.00904 2.49173L4.6731 2.17755C3.83325 1.36068 2.4559 1.2193 1.53207 1.95762C0.473867 2.80591 0.423476 4.31397 1.3641 5.22509L4.62271 8.36688C4.82427 8.57109 5.177 8.57109 5.37857 8.36688L8.63717 5.22509C9.5778 4.31397 9.52741 2.80591 8.4692 1.95762Z" stroke="#595959" />
                                            </svg>
                                        </span>
                                        Add to wishlist
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="product-info">
                            <ul class="product-info-list">
                                <li><span>Sku:</span> <?= htmlspecialchars($product['id']) ?>-<?= strtoupper(substr($product['pro_name'], 0, 5)) ?></li>
                                <li><span>Category:</span>
                                    <a href="<?= BASE_URL ?>shop?category=<?= $product['category_id'] ?>">
                                        <?= htmlspecialchars($product['category_name'] ?: 'Uncategorized') ?>
                                    </a>
                                </li>
                                <?php if ($product['brand_name']): ?>
                                    <li><span>Brand:</span> <a href="#"><?= htmlspecialchars($product['brand_name']) ?></a></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Product details page starts -->
    <div class="product-details-page mb-100">
        <div class="container">
            <div class="row">
                <div class="product-description-and-review-area">
                    <div class="row">
                        <div class="col-lg-12">
                            <div class="nav nav2 nav-pills" id="v-pills-tab2" role="tablist" aria-orientation="vertical">
                                <button class="nav-link active" id="description-tab" data-bs-toggle="pill"
                                    data-bs-target="#description" type="button" role="tab">Description</button>
                                <?php if (!empty($sizes)): ?>
                                    <button class="nav-link" id="size-tab" data-bs-toggle="pill"
                                        data-bs-target="#size" type="button" role="tab">Size Chart</button>
                                <?php endif; ?>
                                <button class="nav-link" id="review-tab" data-bs-toggle="pill"
                                    data-bs-target="#review" type="button" role="tab">Reviews (<?= $total_reviews ?>)</button>
                            </div>

                            <div class="tab-content tab-content2" id="v-pills-tabContent3">
                                <div class="tab-pane fade active show" id="description" role="tabpanel">
                                    <div class="description">
                                        <?= $product['description'] ? htmlspecialchars_decode($product['description']) : '<p>No description available for this product.</p>' ?>
                                    </div>
                                </div>

                                <?php if (!empty($sizes)): ?>
                                    <div class="tab-pane fade" id="size" role="tabpanel">
                                        <div class="addithonal-information">
                                            <div class="table-responsive">
                                                <table class="cart-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Size</th>
                                                            <th>Chest (in)</th>
                                                            <th>Waist (in)</th>
                                                            <th>Hip (in)</th>
                                                            <th>Shoulder (in)</th>
                                                            <th>Price +</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($sizes as $size): ?>
                                                            <tr>
                                                                <td data-label="Size"><strong><?= htmlspecialchars($size['size_label']) ?></strong></td>
                                                                <td data-label="Chest"><?= $size['chest_min'] ? $size['chest_min'] . '–' . $size['chest_max'] : '—' ?></td>
                                                                <td data-label="Waist"><?= $size['waist_min'] ? $size['waist_min'] . '–' . $size['waist_max'] : '—' ?></td>
                                                                <td data-label="Hip"><?= $size['hip_min'] ? $size['hip_min'] . '–' . $size['hip_max'] : '—' ?></td>
                                                                <td data-label="Shoulder"><?= $size['shoulder'] ?: '—' ?></td>
                                                                <td data-label="Price">
                                                                    <?php if ($size['price_modifier'] > 0): ?>
                                                                        <span class="text-success">+₹<?= number_format($size['price_modifier'], 2) ?></span>
                                                                    <?php elseif ($size['price_modifier'] < 0): ?>
                                                                        <span class="text-danger">-₹<?= number_format(abs($size['price_modifier']), 2) ?></span>
                                                                    <?php else: ?>
                                                                        <span>—</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="tab-pane fade" id="review" role="tabpanel">
                                    <div class="reviews-area">
                                        <div class="row g-lg-4 gy-5">
                                            <div class="col-lg-7">
                                                <div class="comment-and-form-area two">
                                                    <div class="comment-area">
                                                        <div class="comment-title">
                                                            <h4>Review (<?= $total_reviews ?>)</h4>
                                                        </div>
                                                        <ul class="comment">
                                                            <?php if (mysqli_num_rows($reviews_result) > 0): ?>
                                                                <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                                                                    <li>
                                                                        <div class="single-comment-area">
                                                                            <div class="author-img">
                                                                                <img src="<?= BASE_URL ?>assets/image/inner-page/comment-author-image.png" alt="">
                                                                            </div>
                                                                            <div class="comment-content">
                                                                                <div class="author-name-deg">
                                                                                    <h6><?= htmlspecialchars($review['reviewer_name']) ?>,</h6>
                                                                                    <span><?= date('d M, Y', strtotime($review['created_at'])) ?></span>
                                                                                </div>
                                                                                <div class="star mb-2">
                                                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                                                        <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?>" style="color: #ffc107; font-size: 12px;"></i>
                                                                                    <?php endfor; ?>
                                                                                </div>
                                                                                <p><?= htmlspecialchars($review['review_message']) ?></p>
                                                                            </div>
                                                                        </div>
                                                                    </li>
                                                                <?php endwhile; ?>
                                                            <?php else: ?>
                                                                <li>
                                                                    <div class="single-comment-area">
                                                                        <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                                                                    </div>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="review-form">
                                                    <div class="number-of-review">
                                                        <h4>Write A Review</h4>
                                                    </div>
                                                    <form id="reviewForm">
                                                        <div class="row">
                                                            <div class="col-lg-12">
                                                                <div class="form-inner3 mb-40">
                                                                    <div class="review-rate-area">
                                                                        <p>Your Rating</p>
                                                                        <div class="rate">
                                                                            <input type="radio" id="star5" name="rating" value="5">
                                                                            <label for="star5" title="5 stars">5 stars</label>
                                                                            <input type="radio" id="star4" name="rating" value="4">
                                                                            <label for="star4" title="4 stars">4 stars</label>
                                                                            <input type="radio" id="star3" name="rating" value="3">
                                                                            <label for="star3" title="3 stars">3 stars</label>
                                                                            <input type="radio" id="star2" name="rating" value="2">
                                                                            <label for="star2" title="2 stars">2 stars</label>
                                                                            <input type="radio" id="star1" name="rating" value="1">
                                                                            <label for="star1" title="1 star">1 star</label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-12">
                                                                <div class="form-inner mb-20">
                                                                    <input type="text" name="reviewer_name" placeholder="Name*" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-12">
                                                                <div class="form-inner mb-20">
                                                                    <input type="email" name="reviewer_email" placeholder="Email*" required>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-12">
                                                                <div class="form-inner mb-50">
                                                                    <textarea name="review_message" placeholder="Your review..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="col-lg-12">
                                                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                                                <button type="submit" class="primary-btn">Submit Review</button>
                                                            </div>
                                                        </div>
                                                    </form>
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
    </div>

    <!-- Related Products Section -->
    <?php if (!empty($related_products)): ?>
        <div class="product-card-section mb-100">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12 mb-50">
                        <div class="section-title">
                            <h3>Related Products</h3>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-lg-12 position-relative">
                        <div class="swiper home1-product-swiper">
                            <div class="swiper-wrapper">
                                <?php foreach ($related_products as $rel_pro):
                                    $rel_images = !empty($rel_pro['pro_img']) ? explode(",", $rel_pro['pro_img']) : [];
                                    $rel_first_img = !empty($rel_images) ? trim($rel_images[0]) : 'default-product.jpg';
                                    $rel_discount = 0;
                                    if ($rel_pro['mrp'] > 0 && $rel_pro['selling_price'] > 0) {
                                        $rel_discount = round((($rel_pro['mrp'] - $rel_pro['selling_price']) / $rel_pro['mrp']) * 100);
                                    }
                                ?>
                                    <div class="swiper-slide">
                                        <div class="product-card">
                                            <div class="product-card-img">
                                                <a href="<?= BASE_URL ?>product-details/<?= htmlspecialchars($rel_pro['slug_url']) ?>">
                                                    <img src="<?= BASE_URL ?>admin/assets/img/uploads/<?= htmlspecialchars($rel_first_img) ?>"
                                                        alt="<?= htmlspecialchars($rel_pro['pro_name']) ?>"
                                                        onerror="this.src='<?= BASE_URL ?>assets/image/placeholder.jpg'">
                                                </a>
                                                <div class="batch">
                                                    <?php if ($rel_discount > 0): ?>
                                                        <span class="new"><?= $rel_discount ?>% off</span>
                                                    <?php endif; ?>
                                                    <?php if ($rel_pro['new_arrival'] == 1): ?>
                                                        <span>New</span>
                                                    <?php endif; ?>
                                                    <?php if ($rel_pro['trending'] == 1): ?>
                                                        <span>Trending</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="overlay">
                                                    <div class="cart-area">
                                                        <a class="add-cart-btn" href="javascript:void(0)" onclick="addToCartDirect(<?= $rel_pro['id'] ?>)">
                                                            <i class="bi bi-bag-check"></i> Add To Cart
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="product-card-content">
                                                <div class="rating">
                                                    <ul>
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <li><i class="bi bi-star<?= $i <= 4 ? '-fill' : '' ?>"></i></li>
                                                        <?php endfor; ?>
                                                    </ul>
                                                </div>
                                                <h6>
                                                    <a href="<?= BASE_URL ?>product-details/<?= htmlspecialchars($rel_pro['slug_url']) ?>">
                                                        <?= htmlspecialchars($rel_pro['pro_name']) ?>
                                                    </a>
                                                </h6>
                                                <p class="price">
                                                    <?php if ($rel_pro['mrp'] > $rel_pro['selling_price']): ?>
                                                        <del>₹<?= number_format($rel_pro['mrp'], 2) ?></del>
                                                    <?php endif; ?>
                                                    <span class="current-price">₹<?= number_format($rel_pro['selling_price'], 2) ?></span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- footer section starts here -->
    <?php include_once "includes/footer.php"; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/jquery-ui.js"></script>
    <script src="<?= BASE_URL ?>assets/js/waypoints.js"></script>
    <script src="<?= BASE_URL ?>assets/js/jquery.counterup.js"></script>
    <script src="<?= BASE_URL ?>assets/js/jquery.marquee.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/popper.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/swiper-bundle.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/slick.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/jquery.fancybox.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/jquery.nice-select.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/wow.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/bootstrap.min.js"></script>
    <script src="<?= BASE_URL ?>assets/js/main.js"></script>

    <script>
        // Product data
        const basePrice = <?= $base_price ?>;
        let selectedFabricModifier = <?= $fabric_modifier ?>;
        let selectedSizeModifier = 0;
        let selectedSizeId = null;
        let selectedFabricId = <?= $default_fabric ? $default_fabric['fabric_id'] : 0 ?>;
        let selectedColorId = null;
        let currentStock = <?= !empty($sizes) ? $sizes[0]['stock'] : 0 ?>;

        // Update price display
        function updatePrice() {
            let totalPrice = basePrice + selectedFabricModifier + selectedSizeModifier;
            $('#displayPrice').text('₹' + totalPrice.toFixed(2));
            if (selectedFabricModifier !== 0) {
                let modifierText = selectedFabricModifier > 0 ?
                    '+₹' + selectedFabricModifier.toFixed(2) + ' for selected fabric' :
                    '-₹' + Math.abs(selectedFabricModifier).toFixed(2) + ' for selected fabric';
                $('#fabricModifierNote').text(modifierText).show();
            } else {
                $('#fabricModifierNote').hide();
            }
        }

        // Select fabric
        function selectFabric(element, modifier, basePriceVal) {
            $('.fabric-option').removeClass('selected border-dark').addClass('border-secondary');
            $(element).addClass('selected border-dark').removeClass('border-secondary');
            selectedFabricModifier = parseFloat(modifier);
            selectedFabricId = $(element).data('fabric-id');
            updatePrice();
        }

        // Select size
        function selectSize(element, modifier, stock) {
            $('.size-option').removeClass('selected');
            $(element).addClass('selected');
            selectedSizeModifier = parseFloat(modifier);
            selectedSizeId = $(element).data('size-id');
            currentStock = parseInt(stock);

            if (stock > 0) {
                $('#stockStatus').html('<small class="text-success">In Stock (' + stock + ' available)</small>');
                $('#availabilityStatus').text('In stock');
            } else {
                $('#stockStatus').html('<small class="text-danger">Out of Stock</small>');
                $('#availabilityStatus').text('Out of stock');
            }
            updatePrice();
        }

        // Select color
        function selectColor(element, hex) {
            $('.color-option').removeClass('selected');
            $(element).addClass('selected');
            selectedColorId = $(element).data('color-id');
        }

        // Add to cart
        function addToCart() {
            let quantity = parseInt($('#quantityInput').val()) || 1;
            let productId = <?= $product['id'] ?>;

            $.ajax({
                url: '<?= BASE_URL ?>ajax/add-to-cart.php',
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    fabric_id: selectedFabricId,
                    size_id: selectedSizeId,
                    color_id: selectedColorId
                },
                success: function(response) {
                    let res = JSON.parse(response);
                    if (res.success) {
                        alert('Product added to cart!');
                        updateCartCount();
                    } else {
                        alert(res.message || 'Error adding to cart');
                    }
                },
                error: function() {
                    alert('Error adding to cart');
                }
            });
        }

        // Buy now
        function buyNow() {
            let quantity = parseInt($('#quantityInput').val()) || 1;
            let productId = <?= $product['id'] ?>;

            $.ajax({
                url: '<?= BASE_URL ?>ajax/add-to-cart.php',
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    fabric_id: selectedFabricId,
                    size_id: selectedSizeId,
                    color_id: selectedColorId,
                    buy_now: true
                },
                success: function(response) {
                    window.location.href = '<?= BASE_URL ?>checkout.php';
                },
                error: function() {
                    alert('Error processing request');
                }
            });
        }

        // Add to wishlist
        function addToWishlist(productId) {
            $.ajax({
                url: '<?= BASE_URL ?>ajax/add-to-wishlist.php',
                type: 'POST',
                data: {
                    product_id: productId
                },
                success: function(response) {
                    alert('Added to wishlist!');
                },
                error: function() {
                    alert('Please login to add to wishlist');
                }
            });
        }

        function addToCartDirect(productId) {
            $.ajax({
                url: '<?= BASE_URL ?>ajax/add-to-cart.php',
                type: 'POST',
                data: {
                    product_id: productId,
                    quantity: 1
                },
                success: function(response) {
                    alert('Product added to cart!');
                    updateCartCount();
                }
            });
        }

        function updateCartCount() {
            $.ajax({
                url: '<?= BASE_URL ?>ajax/cart-count.php',
                type: 'GET',
                success: function(count) {
                    $('.cart-count').text(count);
                }
            });
        }

        // Quantity handlers
        $('.quantity__plus').click(function() {
            let val = parseInt($('#quantityInput').val());
            $('#quantityInput').val(val + 1);
        });

        $('.quantity__minus').click(function() {
            let val = parseInt($('#quantityInput').val());
            if (val > 1) $('#quantityInput').val(val - 1);
        });

        // Review form submission
        $('#reviewForm').submit(function(e) {
            e.preventDefault();
            $.ajax({
                url: '<?= BASE_URL ?>ajax/submit-review.php',
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    let res = JSON.parse(response);
                    if (res.success) {
                        alert('Thank you for your review!');
                        location.reload();
                    } else {
                        alert(res.message || 'Error submitting review');
                    }
                }
            });
        });

        // Quick view
        function quickView(productId) {
            $.ajax({
                url: '<?= BASE_URL ?>ajax/quick-view.php',
                type: 'POST',
                data: {
                    product_id: productId
                },
                success: function(response) {
                    $('#quickViewContent').html(response);
                }
            });
        }

        // Initialize swiper
        var swiper = new Swiper('.home1-product-swiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 3000,
            },
            breakpoints: {
                576: {
                    slidesPerView: 2
                },
                768: {
                    slidesPerView: 3
                },
                992: {
                    slidesPerView: 4
                },
                1200: {
                    slidesPerView: 4
                }
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    </script>
</body>

</html>