<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

$visitor_id = $_COOKIE['visitor_id'] ?? session_id();

$cc = $conn->prepare("SELECT COALESCE(SUM(quantity), 0) as cnt FROM cart WHERE visitor_id=?");
$cc->bind_param("s", $visitor_id);
$cc->execute();
$count = (int)$cc->get_result()->fetch_assoc()['cnt'];
$cc->close();

echo json_encode(['count' => $count]);
