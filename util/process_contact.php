<?php
// util/process_contact.php

require_once "contact_config.php";
include_once "../config/connect.php";
include_once "function.php";
define('SITE_URL', $site);

// Set timezone
date_default_timezone_set('UTC');

// Initialize response
$response = ['success' => false, 'message' => '', 'errors' => []];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_POST['submit'])) {
    header("Location: " . SITE_URL . "contact.php");
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    logError("CSRF token validation failed");
    $_SESSION['contact_error'] = "Security validation failed. Please try again.";
    header("Location: " . SITE_URL . "contact.php");
    exit();
}

// Sanitize and validate input data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$message = trim($_POST['message'] ?? '');

// Basic validation
$errors = [];

if (empty($name)) {
    $errors[] = "Name is required.";
} elseif (strlen($name) < 2 || strlen($name) > 100) {
    $errors[] = "Name must be between 2 and 100 characters.";
}

if (empty($email)) {
    $errors[] = "Email is required.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
} elseif (strlen($email) > 255) {
    $errors[] = "Email is too long.";
}

if (empty($phone)) {
    $errors[] = "Phone number is required.";
} elseif (!preg_match('/^[0-9+\-\s()]{10,20}$/', $phone)) {
    $errors[] = "Please enter a valid phone number.";
}

if (empty($message)) {
    $errors[] = "Message is required.";
} elseif (strlen($message) < 10) {
    $errors[] = "Message must be at least 10 characters.";
} elseif (strlen($message) > 5000) {
    $errors[] = "Message is too long (max 5000 characters).";
}

// Check for spam (honeypot could be added)
if (!empty($_POST['website']) || !empty($_POST['url'])) {
    logError("Spam detected - honeypot field filled", ['ip' => $_SERVER['REMOTE_ADDR']]);
    $_SESSION['contact_success'] = "Your message has been sent successfully!"; // Fake success for spam
    header("Location: " . SITE_URL . "contact.php?success=1");
    exit();
}

// If errors exist, store and redirect
if (!empty($errors)) {
    $_SESSION['contact_errors'] = $errors;
    $_SESSION['contact_form_data'] = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'subject' => $subject,
        'message' => $message
    ];
    header("Location: " . SITE_URL . "contact.php");
    exit();
}

// Check rate limit
if (!checkRateLimit($email, $conn)) {
    logError("Rate limit exceeded", ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
    $_SESSION['contact_error'] = "You have submitted too many inquiries. Please try again later.";
    header("Location: " . SITE_URL . "contact.php");
    exit();
}

// Prepare data for database
$clean_name = mysqli_real_escape_string($conn, $name);
$clean_email = mysqli_real_escape_string($conn, $email);
$clean_phone = mysqli_real_escape_string($conn, $phone);
$clean_subject = mysqli_real_escape_string($conn, $subject);
$clean_message = mysqli_real_escape_string($conn, $message);
$created_at = date('Y-m-d H:i:s');
$ip_address = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
$status = 'unread';

// Insert into database
$sql = "INSERT INTO inquiries (name, email, phone, subject, message, ip_address, user_agent, created_at, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    logError("Database prepare failed", ['error' => mysqli_error($conn)]);
    $_SESSION['contact_error'] = "System error. Please try again later.";
    header("Location: " . SITE_URL . "contact.php");
    exit();
}

mysqli_stmt_bind_param(
    $stmt,
    "sssssssss",
    $clean_name,
    $clean_email,
    $clean_phone,
    $clean_subject,
    $clean_message,
    $ip_address,
    $user_agent,
    $created_at,
    $status
);

if (mysqli_stmt_execute($stmt)) {
    // Get contact settings
    $contact = contact_us();
    $email_logo = get_email_logo();

    // Send email notification
    $email_sent = sendInquiryEmailSMTP(
        $name,
        $email,
        $phone,
        $subject,
        $message,
        $contact,
        $email_logo,
        SITE_URL
    );

    if (!$email_sent) {
        logError("Email notification failed", ['email' => $email]);
    }

    // Clear session data
    unset($_SESSION['csrf_token']);
    unset($_SESSION['contact_form_data']);

    // Set success message
    $_SESSION['contact_success'] = "Thank you for contacting us! We'll get back to you soon.";

    // Close statement and connection
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Redirect with success
    header("Location: " . SITE_URL . "contact.php?success=1");
    exit();

} else {
    logError("Database insert failed", [
        'error' => mysqli_error($conn),
        'data' => ['email' => $email]
    ]);

    $_SESSION['contact_error'] = "Failed to save your inquiry. Please try again.";
    $_SESSION['contact_form_data'] = $_POST;

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    header("Location: " . SITE_URL . "contact.php");
    exit();
}