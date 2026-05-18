<?php
session_start();
header('Content-Type: application/json');
include_once dirname(__DIR__) . '/config/connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$amount = isset($_POST['amount']) ? round((float)$_POST['amount'] * 100) : 0; // paise
if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid amount']);
    exit;
}

$receipt = 'order_' . time() . '_' . rand(1000, 9999);

$payload = json_encode([
    'amount'   => $amount,
    'currency' => 'INR',
    'receipt'  => $receipt,
    'payment_capture' => 1
]);

$ch = curl_init('https://api.razorpay.com/v1/orders');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_USERPWD        => RZP_KEY_ID . ':' . RZP_KEY_SECRET,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($http_code === 200 && !empty($data['id'])) {
    echo json_encode([
        'success'  => true,
        'order_id' => $data['id'],
        'amount'   => $data['amount'],
        'currency' => $data['currency'],
        'key'      => RZP_KEY_ID
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => $data['error']['description'] ?? 'Could not create payment order. Check Razorpay keys.'
    ]);
}
