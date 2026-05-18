<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

// Redirect if no visitor cookie
if (empty($_COOKIE['visitor_id'])) {
    header("Location: " . BASE_URL . "cart.php");
    exit;
}
$visitor_id = $_COOKIE['visitor_id'];

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.id AS cart_id, c.product_id, c.quantity, c.size, c.unit_price, c.total_price,
           c.fabric_id, c.color_id, c.customization_json, c.product_type,
           p.pro_name, p.pro_img,
           fo.name AS fabric_name,
           co.name AS color_name
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN fabric_options fo ON c.fabric_id = fo.id
    LEFT JOIN color_options co ON c.color_id = co.id
    WHERE c.visitor_id = ?
");
$stmt->bind_param("s", $visitor_id);
$stmt->execute();
$res = $stmt->get_result();
$cart_items = [];
$subtotal = 0.0;
while ($row = $res->fetch_assoc()) {
    $row['first_img'] = !empty($row['pro_img']) ? explode(',', $row['pro_img'])[0] : '';
    $cart_items[] = $row;
    $subtotal += (float)$row['total_price'];
}
$stmt->close();

if (empty($cart_items)) {
    header("Location: " . BASE_URL . "cart.php");
    exit;
}

$shipping = 0.00;
$total    = $subtotal + $shipping;

// Pre-fill from session if logged in
$pre_name  = '';
$pre_email = '';
$pre_phone = '';
if (!empty($_SESSION['user_id'])) {
    $us = $conn->prepare("SELECT name, email, phone FROM users WHERE id = ? LIMIT 1");
    if ($us) {
        $us->bind_param("i", $_SESSION['user_id']);
        $us->execute();
        $ur = $us->get_result()->fetch_assoc();
        $us->close();
        if ($ur) {
            $pre_name  = htmlspecialchars($ur['name']  ?? '');
            $pre_email = htmlspecialchars($ur['email'] ?? '');
            $pre_phone = htmlspecialchars($ur['phone'] ?? '');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | The Best Bespok</title>
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
    <style>
        .checkout-wrap {
            padding: 60px 0 80px;
        }

        .section-title-co {
            font-size: 20px;
            font-weight: 800;
            color: #1d2d44;
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 2px solid #c6a43f;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .billing-card,
        .summary-card {
            background: #fff;
            border-radius: 14px;
            padding: 32px;
            box-shadow: 0 4px 28px rgba(0, 0, 0, .07);
        }

        .form-label-co {
            font-size: 13px;
            font-weight: 700;
            color: #555;
            margin-bottom: 6px;
            display: block;
        }

        .form-control-co {
            width: 100%;
            padding: 11px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            color: #222;
            transition: border-color .2s;
            outline: none;
        }

        .form-control-co:focus {
            border-color: #c6a43f;
        }

        textarea.form-control-co {
            resize: vertical;
            min-height: 90px;
        }

        .summary-item {
            display: flex;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
            align-items: flex-start;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-item img {
            width: 64px;
            height: 72px;
            object-fit: cover;
            border-radius: 8px;
            background: #f5f5f5;
        }

        .summary-item-name {
            font-size: 14px;
            font-weight: 700;
            color: #1d2d44;
            margin-bottom: 4px;
        }

        .summary-item-meta {
            font-size: 12px;
            color: #888;
        }

        .summary-item-price {
            margin-left: auto;
            font-weight: 800;
            color: #c6a43f;
            font-size: 15px;
            white-space: nowrap;
        }

        .pricing-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            font-size: 14px;
            color: #555;
        }

        .pricing-row.total {
            font-size: 17px;
            font-weight: 800;
            color: #1d2d44;
            border-top: 2px solid #eee;
            padding-top: 14px;
            margin-top: 4px;
        }

        /* .rzp-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1d2d44, #c6a43f);
            color: #fff;
            font-size: 16px;
            font-weight: 800;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: opacity .2s;
            margin-top: 24px;
        } */

        .rzp-btn:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        .tag-chip {
            display: inline-block;
            background: #f0f0f0;
            border-radius: 20px;
            padding: 2px 10px;
            font-size: 11px;
            color: #555;
            margin: 2px 2px 0 0;
        }

        .secure-note {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 10px;
        }

        .sticky-summary {
            position: sticky;
            top: 90px;
        }
    </style>
</head>

<body>
    <?php
    include_once "login-popup.php";
    include_once "includes/header.php";
    include_once "includes/mobile-bottom-nav.php";
    ?>

    <!-- breadcrumb -->
    <div class="breadcrumb-section mb-0"
        style="background-image: linear-gradient(180deg, rgba(0,0,0,.4), rgba(0,0,0,.4)), url(assets/image/background/gallery.jpg);">
        <div class="container">
            <div class="row">
                <div class="col-12 d-flex justify-content-center">
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

    <div class="checkout-wrap">
        <div class="container">
            <div class="row g-4">

                <!-- LEFT: Billing Form -->
                <div class="col-lg-7">
                    <div class="billing-card">
                        <div class="section-title-co"><i class="bi bi-person-lines-fill"></i> Billing Information</div>
                        <form id="checkoutForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label-co">Full Name <span style="color:red">*</span></label>
                                    <input type="text" id="co_name" name="name" class="form-control-co" placeholder="John Doe" value="<?= $pre_name ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-co">Phone Number <span style="color:red">*</span></label>
                                    <input type="tel" id="co_phone" name="phone" class="form-control-co" placeholder="+91 98765 43210" value="<?= $pre_phone ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-co">Email Address <span style="color:red">*</span></label>
                                    <input type="email" id="co_email" name="email" class="form-control-co" placeholder="you@example.com" value="<?= $pre_email ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label-co">Country <span style="color:red">*</span></label>
                                    <input type="text" id="co_country" name="country" class="form-control-co" placeholder="India" value="India" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-co">Street Address <span style="color:red">*</span></label>
                                    <input type="text" id="co_address" name="address" class="form-control-co" placeholder="House No., Street, Area" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-co">City <span style="color:red">*</span></label>
                                    <input type="text" id="co_city" name="city" class="form-control-co" placeholder="Mumbai" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-co">State <span style="color:red">*</span></label>
                                    <input type="text" id="co_state" name="state" class="form-control-co" placeholder="Maharashtra" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label-co">Pincode <span style="color:red">*</span></label>
                                    <input type="text" id="co_pincode" name="pincode" class="form-control-co" placeholder="400001" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label-co">Order Notes (optional)</label>
                                    <textarea id="co_notes" name="notes" class="form-control-co" placeholder="Any special instructions for your bespoke order..."></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- RIGHT: Order Summary -->
                <div class="col-lg-5">
                    <div class="sticky-summary">
                        <div class="summary-card">
                            <div class="section-title-co"><i class="bi bi-bag-check"></i> Order Summary</div>

                            <div class="summary-items-list">
                                <?php foreach ($cart_items as $item):
                                    $imgSrc = !empty($item['first_img'])
                                        ? BASE_URL . 'admin/assets/img/uploads/' . $item['first_img']
                                        : BASE_URL . 'assets/image/placeholder.jpg';
                                ?>
                                    <div class="summary-item">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['pro_name']) ?>">
                                        <div style="flex:1; min-width:0;">
                                            <div class="summary-item-name"><?= htmlspecialchars($item['pro_name']) ?></div>
                                            <div class="summary-item-meta">Qty: <?= (int)$item['quantity'] ?></div>
                                            <?php if (!empty($item['size'])): ?>
                                                <span class="tag-chip">Size: <?= htmlspecialchars($item['size']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['fabric_name'])): ?>
                                                <span class="tag-chip"><i class="bi bi-scissors"></i> <?= htmlspecialchars($item['fabric_name']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($item['color_name'])): ?>
                                                <span class="tag-chip"><i class="bi bi-palette"></i> <?= htmlspecialchars($item['color_name']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="summary-item-price">₹<?= number_format((float)$item['total_price'], 2) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <div style="margin-top: 16px;">
                                <div class="pricing-row">
                                    <span>Subtotal</span>
                                    <span>₹<?= number_format($subtotal, 2) ?></span>
                                </div>
                                <div class="pricing-row">
                                    <span>Shipping</span>
                                    <span style="color:#27ae60; font-weight:700;"><?= $shipping > 0 ? '₹' . number_format($shipping, 2) : 'FREE' ?></span>
                                </div>
                                <div class="pricing-row total">
                                    <span>Total</span>
                                    <span>₹<?= number_format($total, 2) ?></span>
                                </div>
                            </div>

                            <button class="rzp-btn primary-btn" id="payBtn" type="button">
                                <i class="bi bi-lock-fill"></i>
                                Pay ₹<?= number_format($total, 2) ?> Securely
                            </button>
                            <p class="secure-note"><i class="bi bi-shield-check"></i> 256-bit SSL encrypted &bull; Powered by Razorpay</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include_once "includes/footer.php"; ?>

    <script src="assets/js/jquery-3.7.1.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>

    <script>
        (function() {
            const BASE = '<?= BASE_URL ?>';
            const TOTAL_INR = <?= $total ?>;

            function getFormData() {
                return {
                    name: document.getElementById('co_name').value.trim(),
                    email: document.getElementById('co_email').value.trim(),
                    phone: document.getElementById('co_phone').value.trim(),
                    country: document.getElementById('co_country').value.trim(),
                    address: document.getElementById('co_address').value.trim(),
                    city: document.getElementById('co_city').value.trim(),
                    state: document.getElementById('co_state').value.trim(),
                    pincode: document.getElementById('co_pincode').value.trim(),
                    notes: document.getElementById('co_notes').value.trim(),
                };
            }

            function validate(d) {
                const required = ['name', 'email', 'phone', 'country', 'address', 'city', 'state', 'pincode'];
                for (const k of required) {
                    if (!d[k]) {
                        alert('Please fill in: ' + k.charAt(0).toUpperCase() + k.slice(1));
                        document.getElementById('co_' + k).focus();
                        return false;
                    }
                }
                if (!/^\S+@\S+\.\S+$/.test(d.email)) {
                    alert('Please enter a valid email address.');
                    document.getElementById('co_email').focus();
                    return false;
                }
                return true;
            }

            document.getElementById('payBtn').addEventListener('click', async function() {
                const formData = getFormData();
                if (!validate(formData)) return;

                this.disabled = true;
                this.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating order...';

                try {
                    // Step 1: Create Razorpay order
                    const orderRes = await fetch(BASE + 'ajax/create-razorpay-order.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: 'amount=' + encodeURIComponent(TOTAL_INR)
                    });
                    const orderData = await orderRes.json();

                    if (!orderData.success) {
                        alert('Payment error: ' + (orderData.message || 'Could not initiate payment.'));
                        resetBtn();
                        return;
                    }

                    // Step 2: Open Razorpay modal
                    const options = {
                        key: orderData.key,
                        amount: orderData.amount,
                        currency: orderData.currency,
                        name: 'The Best Bespok',
                        description: 'Bespoke Order Payment',
                        order_id: orderData.order_id,
                        prefill: {
                            name: formData.name,
                            email: formData.email,
                            contact: formData.phone
                        },
                        theme: {
                            color: '#c6a43f'
                        },
                        handler: async function(response) {
                            // Step 3: Verify + save order
                            const params = new URLSearchParams({
                                razorpay_order_id: response.razorpay_order_id,
                                razorpay_payment_id: response.razorpay_payment_id,
                                razorpay_signature: response.razorpay_signature,
                                ...formData
                            });

                            const verRes = await fetch(BASE + 'ajax/verify-payment.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: params.toString()
                            });
                            const verData = await verRes.json();

                            if (verData.success) {
                                window.location.href = verData.redirect;
                            } else {
                                alert('Order save failed: ' + (verData.message || 'Please contact support.'));
                                resetBtn();
                            }
                        },
                        modal: {
                            ondismiss: function() {
                                resetBtn();
                            }
                        }
                    };

                    const rzp = new Razorpay(options);
                    rzp.on('payment.failed', function(resp) {
                        alert('Payment failed: ' + resp.error.description);
                        resetBtn();
                    });
                    rzp.open();

                } catch (err) {
                    console.error(err);
                    alert('Something went wrong. Please try again.');
                    resetBtn();
                }
            });

            function resetBtn() {
                const btn = document.getElementById('payBtn');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-lock-fill"></i> Pay ₹<?= number_format($total, 2) ?> Securely';
            }
        })();
    </script>
</body>

</html>