<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

if (empty($_COOKIE['visitor_id'])) {
    echo json_encode(['count' => 0]);
    exit;
}
$visitor_id = $_COOKIE['visitor_id'];

// Table may not exist yet — handle gracefully
$result = $conn->query("SHOW TABLES LIKE 'wishlist'");
if (!$result || $result->num_rows === 0) {
    echo json_encode(['count' => 0]);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) FROM wishlist WHERE visitor_id = ?");
$stmt->bind_param("s", $visitor_id);
$stmt->execute();
$stmt->bind_result($count);
$stmt->fetch();
$stmt->close();

echo json_encode(['count' => intval($count)]);
