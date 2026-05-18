<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$razorpay_order_id   = $_POST['razorpay_order_id']   ?? '';
$razorpay_payment_id = $_POST['razorpay_payment_id'] ?? '';
$razorpay_signature  = $_POST['razorpay_signature']  ?? '';

// Verify signature
$expected = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, RZP_KEY_SECRET);
if (!hash_equals($expected, $razorpay_signature)) {
    echo json_encode(['success' => false, 'message' => 'Payment verification failed. Invalid signature.']);
    exit;
}

// Visitor / user
if (empty($_COOKIE['visitor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please refresh.']);
    exit;
}
$visitor_id = $_COOKIE['visitor_id'];
$user_id    = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// Customer fields
$name    = trim($_POST['name']    ?? '');
$email   = trim($_POST['email']   ?? '');
$phone   = trim($_POST['phone']   ?? '');
$address = trim($_POST['address'] ?? '');
$city    = trim($_POST['city']    ?? '');
$state   = trim($_POST['state']   ?? '');
$pincode = trim($_POST['pincode'] ?? '');
$country = trim($_POST['country'] ?? 'India');
$notes   = trim($_POST['notes']   ?? '');

// Fetch cart items
$cart_stmt = $conn->prepare("
    SELECT c.id as cart_id, c.product_id, c.quantity, c.size, c.unit_price, c.total_price,
           c.fabric_id, c.color_id, c.customization_json, c.product_type,
           p.pro_name,
           fo.name AS fabric_name,
           co.name AS color_name
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN fabric_options fo ON c.fabric_id = fo.id
    LEFT JOIN color_options co ON c.color_id = co.id
    WHERE c.visitor_id = ?
");
$cart_stmt->bind_param("s", $visitor_id);
$cart_stmt->execute();
$cart_res = $cart_stmt->get_result();
$cart_items = [];
$total = 0.0;
while ($row = $cart_res->fetch_assoc()) {
    $cart_items[] = $row;
    $total += (float)$row['total_price'];
}
$cart_stmt->close();

if (empty($cart_items)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty.']);
    exit;
}

// Create tables if needed
$conn->query("CREATE TABLE IF NOT EXISTS `orders` (
    `id`                   INT AUTO_INCREMENT PRIMARY KEY,
    `order_number`         VARCHAR(50) UNIQUE NOT NULL,
    `visitor_id`           VARCHAR(64),
    `user_id`              INT DEFAULT NULL,
    `razorpay_order_id`    VARCHAR(100),
    `razorpay_payment_id`  VARCHAR(100),
    `razorpay_signature`   VARCHAR(255),
    `customer_name`        VARCHAR(100),
    `customer_email`       VARCHAR(150),
    `customer_phone`       VARCHAR(20),
    `address`              TEXT,
    `city`                 VARCHAR(100),
    `state`                VARCHAR(100),
    `pincode`              VARCHAR(20),
    `country`              VARCHAR(100) DEFAULT 'India',
    `subtotal`             DECIMAL(10,2),
    `shipping`             DECIMAL(10,2) DEFAULT 0.00,
    `total`                DECIMAL(10,2),
    `payment_status`       ENUM('pending','paid','failed') DEFAULT 'paid',
    `order_status`         ENUM('pending','processing','shipped','delivered','cancelled') DEFAULT 'pending',
    `notes`                TEXT,
    `created_at`           TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$conn->query("CREATE TABLE IF NOT EXISTS `order_items` (
    `id`                INT AUTO_INCREMENT PRIMARY KEY,
    `order_id`          INT NOT NULL,
    `product_id`        INT,
    `product_name`      VARCHAR(255),
    `quantity`          INT DEFAULT 1,
    `size`              VARCHAR(50),
    `fabric_id`         INT DEFAULT NULL,
    `color_id`          INT DEFAULT NULL,
    `customization_json` TEXT,
    `product_type`      VARCHAR(50),
    `unit_price`        DECIMAL(10,2),
    `total_price`       DECIMAL(10,2),
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Generate unique order number
$order_number = 'TBB-' . strtoupper(substr(md5(uniqid()), 0, 8));

// Insert order
$ins = $conn->prepare("INSERT INTO orders
    (order_number, visitor_id, user_id, razorpay_order_id, razorpay_payment_id, razorpay_signature,
     customer_name, customer_email, customer_phone, address, city, state, pincode, country,
     subtotal, shipping, total, payment_status, order_status, notes)
    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'paid','pending',?)");

$shipping = 0.00;
$ins->bind_param(
    "ssisssssssssssddds",
    $order_number, $visitor_id, $user_id,
    $razorpay_order_id, $razorpay_payment_id, $razorpay_signature,
    $name, $email, $phone, $address, $city, $state, $pincode, $country,
    $total, $shipping, $total, $notes
);
$ins->execute();
$order_id = $conn->insert_id;
$ins->close();

// Insert order items
$item_stmt = $conn->prepare("INSERT INTO order_items
    (order_id, product_id, product_name, quantity, size, fabric_id, color_id, customization_json, product_type, unit_price, total_price)
    VALUES (?,?,?,?,?,?,?,?,?,?,?)");

foreach ($cart_items as $item) {
    $item_stmt->bind_param(
        "iisisiiisdd",
        $order_id,
        $item['product_id'],
        $item['pro_name'],
        $item['quantity'],
        $item['size'],
        $item['fabric_id'],
        $item['color_id'],
        $item['customization_json'],
        $item['product_type'],
        $item['unit_price'],
        $item['total_price']
    );
    $item_stmt->execute();
}
$item_stmt->close();

// Clear cart
$del = $conn->prepare("DELETE FROM cart WHERE visitor_id = ?");
$del->bind_param("s", $visitor_id);
$del->execute();
$del->close();

$_SESSION['order_success'] = [
    'order_number'  => $order_number,
    'payment_id'    => $razorpay_payment_id,
    'total'         => $total,
    'customer_name' => $name,
];

echo json_encode([
    'success'      => true,
    'order_number' => $order_number,
    'redirect'     => BASE_URL . 'order-success.php'
]);
