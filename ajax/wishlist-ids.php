<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

if (empty($_COOKIE['visitor_id'])) {
    echo json_encode(['ids' => []]);
    exit;
}
$visitor_id = $_COOKIE['visitor_id'];

$result = $conn->query("SHOW TABLES LIKE 'wishlist'");
if (!$result || $result->num_rows === 0) {
    echo json_encode(['ids' => []]);
    exit;
}

$stmt = $conn->prepare("SELECT product_id FROM wishlist WHERE visitor_id = ?");
$stmt->bind_param("s", $visitor_id);
$stmt->execute();
$res = $stmt->get_result();
$ids = [];
while ($row = $res->fetch_assoc()) {
    $ids[] = intval($row['product_id']);
}
$stmt->close();

echo json_encode(['ids' => $ids]);
