<?php
// register-handler.php
session_start();
include_once "../config/connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ".BASE_URL.""); exit();
}

$name     = trim($_POST['name']             ?? '');
$email    = trim($_POST['email']            ?? '');
$password = trim($_POST['password']         ?? '');
$confirm  = trim($_POST['confirm_password'] ?? '');
$redirect = trim($_POST['redirect']         ?? '');

// Basic validation
if (!$name || !$email || !$password) {
    $_SESSION['register_error'] = "All fields are required.";
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Invalid email address.";
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}
if (strlen($password) < 6) {
    $_SESSION['register_error'] = "Password must be at least 6 characters.";
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}
if ($password !== $confirm) {
    $_SESSION['register_error'] = "Passwords do not match.";
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}

// Check if email exists
$chk = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
$chk->bind_param("s", $email); $chk->execute();
if ($chk->get_result()->fetch_assoc()) {
    $_SESSION['register_error'] = "This email is already registered. Please log in.";
    $chk->close();
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}
$chk->close();

// Split name into first/last
$name_parts = explode(' ', $name, 2);
$first_name = $name_parts[0];
$last_name  = $name_parts[1] ?? '';

$hashed = password_hash($password, PASSWORD_DEFAULT);

$ins = $conn->prepare(
    "INSERT INTO users (first_name, last_name, email, password, status, created_at) VALUES (?, ?, ?, ?, 1, NOW())"
);
$ins->bind_param("ssss", $first_name, $last_name, $email, $hashed);

if ($ins->execute()) {
    // Auto login after registration
    $new_id = $ins->insert_id;
    $_SESSION['user_id']    = $new_id;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name']  = $name;
    $ins->close();
    header("Location: ".BASE_URL."my-account.php"); exit();
} else {
    $_SESSION['register_error'] = "Registration failed. Please try again.";
    $ins->close();
    header("Location: " . ($redirect ?:  BASE_URL) . "#user-login"); exit();
}