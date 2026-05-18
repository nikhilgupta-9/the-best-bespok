<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

$visitor_id = $_COOKIE['visitor_id'] ?? session_id();
$action     = $_POST['action'] ?? '';
$cart_id    = isset($_POST['cart_id']) ? (int)$_POST['cart_id'] : 0;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid cart item']);
    exit;
}

// Security: only touch rows belonging to this visitor
$own = $conn->prepare("SELECT id, quantity, unit_price FROM cart WHERE id=? AND visitor_id=? LIMIT 1");
$own->bind_param("is", $cart_id, $visitor_id);
$own->execute();
$row = $own->get_result()->fetch_assoc();
$own->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    exit;
}

if ($action === 'remove') {
    $del = $conn->prepare("DELETE FROM cart WHERE id=? AND visitor_id=?");
    $del->bind_param("is", $cart_id, $visitor_id);
    $del->execute();
    $del->close();
} elseif ($action === 'update') {
    $qty = max(1, (int)($_POST['quantity'] ?? 1));
    $new_total = $row['unit_price'] * $qty;
    $up = $conn->prepare("UPDATE cart SET quantity=?, total_price=?, updated_at=NOW() WHERE id=?");
    $up->bind_param("idi", $qty, $new_total, $cart_id);
    $up->execute();
    $up->close();
}

// Recalculate totals for this visitor
$ts = $conn->prepare("SELECT COALESCE(SUM(quantity),0) as cnt, COALESCE(SUM(total_price),0) as subtotal FROM cart WHERE visitor_id=?");
$ts->bind_param("s", $visitor_id);
$ts->execute();
$totals = $ts->get_result()->fetch_assoc();
$ts->close();

echo json_encode([
    'success'    => true,
    'cart_count' => (int)$totals['cnt'],
    'subtotal'   => number_format((float)$totals['subtotal'], 2)
]);
