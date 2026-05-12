<?php
// ================================================================
// FILE 1: login-handler.php
// Handles the login form POST from login-popup.php
// ================================================================
session_start();
include_once "config/connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ".BASE_URL.""); exit();
}

$email    = trim($_POST['email']    ?? '');
$password = trim($_POST['password'] ?? '');
$redirect = trim($_POST['redirect'] ?? '');
$remember = isset($_POST['remember']);

// Validate
if (!$email || !$password) {
    $_SESSION['login_error'] = "Please enter your email and password.";
    header("Location: " . ($redirect ?: 'index.php') . "#user-login"); exit();
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE email=? AND status=1 LIMIT 1");
$stmt->bind_param("s", $email); $stmt->execute();
$user = $stmt->get_result()->fetch_assoc(); $stmt->close();

if (!$user || !password_verify($password, $user['password'])) {
    $_SESSION['login_error'] = "Invalid email or password. Please try again.";
    header("Location: " . ($redirect ?: 'index.php') . "#user-login"); exit();
}

// Login success — set session
$_SESSION['user_id']     = $user['id'];
$_SESSION['user_email']  = $user['email'];
$_SESSION['user_name']   = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: ($user['name'] ?? 'User');

// Remember me cookie (30 days)
if ($remember) {
    $token = bin2hex(random_bytes(32));
    setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/');
    $upd = $conn->prepare("UPDATE users SET remember_token=? WHERE id=?");
    $upd->bind_param("si", $token, $user['id']); $upd->execute(); $upd->close();
}

// Redirect after login
$go = $_SESSION['login_redirect'] ?? $redirect ?: 'my-account.php';
unset($_SESSION['login_redirect']);

// Always go to my-account if they clicked My Account
if (str_contains($go, 'my-account') || str_contains($go, 'open_login')) {
    $go = 'my-account.php';
}

header("Location: $go"); exit();