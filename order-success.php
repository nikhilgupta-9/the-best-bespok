<?php
session_start();
include_once "config/connect.php";
include_once "util/function.php";

$order = $_SESSION['order_success'] ?? null;
if (!$order) {
    header("Location: " . BASE_URL);
    exit;
}
unset($_SESSION['order_success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed | The Best Bespok</title>
    <link rel="stylesheet" href="assets/css/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/boxicons.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/image/thumbnail.svg" type="image/gif" sizes="20x20">
    <style>
        .success-section { min-height: 80vh; display: flex; align-items: center; padding: 80px 0; }
        .success-card { background: #fff; border-radius: 16px; padding: 56px 48px; text-align: center; box-shadow: 0 8px 48px rgba(0,0,0,.08); max-width: 560px; margin: 0 auto; }
        .success-icon { width: 90px; height: 90px; background: linear-gradient(135deg, #27ae60, #2ecc71); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 28px; }
        .success-icon i { font-size: 44px; color: #fff; }
        .success-card h2 { font-size: 28px; font-weight: 800; color: #1d2d44; margin-bottom: 8px; }
        .success-card p { color: #666; font-size: 15px; margin-bottom: 0; }
        .order-meta { background: #f8f9fb; border-radius: 10px; padding: 20px 24px; margin: 28px 0; text-align: left; }
        .order-meta .meta-row { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid #eee; }
        .order-meta .meta-row:last-child { border-bottom: none; }
        .order-meta .meta-label { font-size: 13px; color: #888; font-weight: 600; }
        .order-meta .meta-value { font-size: 14px; color: #1d2d44; font-weight: 700; }
        .action-btns { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 8px; }
        .confetti-bar { height: 6px; background: linear-gradient(90deg, #c6a43f, #1d2d44, #c6a43f, #27ae60); border-radius: 99px; margin-bottom: 32px; }
    </style>
</head>
<body>
<?php include_once "includes/header.php"; include_once "includes/mobile-bottom-nav.php"; ?>

<section class="success-section">
    <div class="container">
        <div class="success-card">
            <div class="confetti-bar"></div>
            <div class="success-icon">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2>Order Confirmed!</h2>
            <p>Thank you, <strong><?= htmlspecialchars($order['customer_name']) ?></strong>!<br>
               Your bespoke order has been placed successfully.</p>

            <div class="order-meta">
                <div class="meta-row">
                    <span class="meta-label">Order Number</span>
                    <span class="meta-value"><?= htmlspecialchars($order['order_number']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Payment ID</span>
                    <span class="meta-value" style="font-size:12px;"><?= htmlspecialchars($order['payment_id']) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Amount Paid</span>
                    <span class="meta-value" style="color:#c6a43f;">₹<?= number_format($order['total'], 2) ?></span>
                </div>
                <div class="meta-row">
                    <span class="meta-label">Status</span>
                    <span class="meta-value" style="color:#27ae60;"><i class="bi bi-patch-check-fill"></i> Payment Successful</span>
                </div>
            </div>

            <p style="font-size:13px;color:#999;margin-bottom:24px;">
                <i class="bi bi-envelope"></i> A confirmation will be sent to your email shortly.<br>
                Our team will begin crafting your order within 24 hours.
            </p>

            <div class="action-btns">
                <a href="<?= BASE_URL ?>" class="primary-btn">
                    <i class="bi bi-house"></i> Back to Home
                </a>
                <a href="<?= BASE_URL ?>products.php" class="primary-btn2">
                    <i class="bi bi-bag"></i> Continue Shopping
                </a>
            </div>
        </div>
    </div>
</section>

<?php include_once "includes/footer.php"; ?>
<script src="assets/js/jquery-3.7.1.min.js"></script>
<script src="assets/js/bootstrap.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
