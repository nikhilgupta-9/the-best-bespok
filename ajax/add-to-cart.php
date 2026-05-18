<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

// Ensure visitor_id cookie exists (90-day guest tracking)
if (empty($_COOKIE['visitor_id'])) {
    $visitor_id = session_id() ?: bin2hex(random_bytes(16));
    setcookie('visitor_id', $visitor_id, time() + (86400 * 90), '/');
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}

$user_id    = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$quantity   = isset($_POST['quantity'])   ? max(1, (int)$_POST['quantity']) : 1;
$fabric_id  = !empty($_POST['fabric_id'])  ? (int)$_POST['fabric_id']  : null;
$color_id   = !empty($_POST['color_id'])   ? (int)$_POST['color_id']   : null;
$size_label = isset($_POST['size'])        ? trim($_POST['size'])       : '';
$size_id    = !empty($_POST['size_id'])    ? (int)$_POST['size_id']    : null;
$cjson      = !empty($_POST['customization_json']) ? $_POST['customization_json'] : '{}';
if (!json_decode($cjson)) $cjson = '{}';

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Get product
$ps = $conn->prepare("SELECT id, pro_name, selling_price, base_price, product_type FROM products WHERE id=? AND status=1 LIMIT 1");
$ps->bind_param("i", $product_id);
$ps->execute();
$product = $ps->get_result()->fetch_assoc();
$ps->close();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Resolve size label from size_id if only id was sent
if ($size_id && !$size_label) {
    $sr = $conn->prepare("SELECT size_label FROM product_sizes WHERE id=? LIMIT 1");
    $sr->bind_param("i", $size_id);
    $sr->execute();
    $szrow = $sr->get_result()->fetch_assoc();
    $sr->close();
    if ($szrow) $size_label = $szrow['size_label'];
}

// Price: use posted total_price, fallback to selling_price
$unit_price = !empty($_POST['total_price']) ? (float)$_POST['total_price'] : (float)$product['selling_price'];
if ($unit_price <= 0) $unit_price = (float)$product['selling_price'];
$total_price = $unit_price * $quantity;
$prod_type   = $product['product_type'];

// Check if same product already in cart for this visitor
$chk = $conn->prepare("SELECT id, quantity FROM cart WHERE visitor_id=? AND product_id=? LIMIT 1");
$chk->bind_param("si", $visitor_id, $product_id);
$chk->execute();
$existing = $chk->get_result()->fetch_assoc();
$chk->close();

if ($existing) {
    $new_qty   = $existing['quantity'] + $quantity;
    $new_total = $unit_price * $new_qty;
    $up = $conn->prepare("UPDATE cart SET quantity=?, total_price=?, updated_at=NOW() WHERE id=?");
    $up->bind_param("idi", $new_qty, $new_total, $existing['id']);
    $up->execute();
    $up->close();
} else {
    $ins = $conn->prepare(
        "INSERT INTO cart (visitor_id, user_id, product_id, quantity, size, fabric_id, color_id,
         customization_json, product_type, unit_price, total_price, updated_at)
         VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())"
    );
    $ins->bind_param(
        "siiisiissdd",
        $visitor_id, $user_id, $product_id, $quantity, $size_label,
        $fabric_id, $color_id, $cjson, $prod_type, $unit_price, $total_price
    );
    $ins->execute();
    $ins->close();
}

// Return updated cart count
$cc = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cnt FROM cart WHERE visitor_id=?");
$cc->bind_param("s", $visitor_id);
$cc->execute();
$cart_count = (int)$cc->get_result()->fetch_assoc()['cnt'];
$cc->close();

echo json_encode([
    'success'    => true,
    'message'    => htmlspecialchars($product['pro_name']) . ' added to cart!',
    'cart_count' => $cart_count
]);
