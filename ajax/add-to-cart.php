<?php
session_start();
include "db-conn.php";

// User logged in check
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?redirect=" . urlencode($_SERVER['HTTP_REFERER']));
    exit();
}

$user_id          = (int)$_SESSION['user_id'];
$product_id       = (int)$_POST['product_id'];
$fabric_id        = (int)($_POST['fabric_id'] ?? 0) ?: null;
$color_id         = (int)($_POST['color_id']  ?? 0) ?: null;
$total_price      = (float)$_POST['total_price'];
$base_price       = (float)$_POST['base_price'];
$custom_json      = $_POST['customization_json'] ?? '{}';
$qty              = 1;

// Validate JSON
$decoded = json_decode($custom_json, true);
if (!$decoded) $custom_json = '{}';

// Calculate costs
$fabric_cost       = 0;
$customization_cost = 0;
if ($fabric_id) {
    $fr = $conn->prepare("SELECT price_modifier FROM fabric_options WHERE id=?");
    $fr->bind_param("i", $fabric_id);
    $fr->execute();
    $fabric_cost = (float)($fr->get_result()->fetch_assoc()['price_modifier'] ?? 0);
    $fr->close();
}

// Insert into custom_orders
$s = $conn->prepare(
    "INSERT INTO custom_orders
        (user_id, product_id, fabric_id, outer_color_id, customization_json,
         base_price, fabric_cost, customization_cost, total_price, quantity, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'confirmed')"
);
$s->bind_param("iiiiisdddi",
    $user_id, $product_id, $fabric_id, $color_id, $custom_json,
    $base_price, $fabric_cost, $customization_cost, $total_price, $qty
);

if ($s->execute()) {
    $_SESSION['success'] = "Custom suit added! Our tailor will contact you for measurements.";
    header("Location: cart.php");
} else {
    $_SESSION['error'] = "Something went wrong. Please try again.";
    header("Location: " . $_SERVER['HTTP_REFERER']);
}
$s->close();