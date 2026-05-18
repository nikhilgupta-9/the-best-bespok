<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$product_id = intval($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product']);
    exit;
}

// Ensure visitor_id cookie
if (empty($_COOKIE['visitor_id'])) {
    $visitor_id = session_id() ?: bin2hex(random_bytes(16));
    setcookie('visitor_id', $visitor_id, time() + (86400 * 90), '/');
} else {
    $visitor_id = $_COOKIE['visitor_id'];
}
$user_id = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;

// Create table if not exists
$conn->query("CREATE TABLE IF NOT EXISTS `wishlist` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` VARCHAR(64) NOT NULL,
    `user_id` INT DEFAULT NULL,
    `product_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY `uq_visitor_product` (`visitor_id`, `product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Check if already in wishlist
$check = $conn->prepare("SELECT id FROM wishlist WHERE visitor_id = ? AND product_id = ?");
$check->bind_param("si", $visitor_id, $product_id);
$check->execute();
$check->store_result();
$exists = $check->num_rows > 0;
$check->close();

if ($exists) {
    $del = $conn->prepare("DELETE FROM wishlist WHERE visitor_id = ? AND product_id = ?");
    $del->bind_param("si", $visitor_id, $product_id);
    $del->execute();
    $del->close();
    $added = false;
} else {
    $ins = $conn->prepare("INSERT INTO wishlist (visitor_id, user_id, product_id) VALUES (?, ?, ?)");
    $ins->bind_param("sii", $visitor_id, $user_id, $product_id);
    $ins->execute();
    $ins->close();
    $added = true;
}

// Get updated wishlist count
$cnt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE visitor_id = ?");
$cnt->bind_param("s", $visitor_id);
$cnt->execute();
$cnt->bind_result($wishlist_count);
$cnt->fetch();
$cnt->close();

echo json_encode([
    'success' => true,
    'added'   => $added,
    'count'   => intval($wishlist_count),
    'message' => $added ? 'Added to wishlist!' : 'Removed from wishlist'
]);
