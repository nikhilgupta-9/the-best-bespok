<?php
// delete_inquiry.php (Simplified version)
session_start();
include_once "db-conn.php";

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "No inquiry ID specified.";
    header('Location: inquiries.php');
    exit();
}

$inquiry_id = intval($_GET['id']);

// Optional: Check for confirmation
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] == 'true';


// Get inquiry details for logging
$stmt = $conn->prepare("SELECT * FROM inquiries WHERE id = ?");
$stmt->bind_param("i", $inquiry_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Inquiry not found.";
    header('Location: inquiries.php');
    exit();
}

$inquiry = $result->fetch_assoc();
$stmt->close();

// Log the deletion
$admin_id = $_SESSION['admin_id'] ?? 0;
$log_stmt = $conn->prepare("INSERT INTO deletion_logs (admin_id, inquiry_id, inquiry_data, deleted_at) VALUES (?, ?, ?, NOW())");
$inquiry_data = json_encode([
    'name' => $inquiry['name'],
    'email' => $inquiry['email'],
    'subject' => $inquiry['subject'],
    'message' => $inquiry['message'],
    'created_at' => $inquiry['created_at']
]);
$log_stmt->bind_param("iis", $admin_id, $inquiry_id, $inquiry_data);
$log_stmt->execute();
$log_stmt->close();

// Delete the inquiry
$stmt = $conn->prepare("DELETE FROM inquiries WHERE id = ?");
$stmt->bind_param("i", $inquiry_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Inquiry #" . $inquiry_id . " has been deleted successfully.";
} else {
    $_SESSION['error'] = "Failed to delete inquiry. Error: " . $stmt->error;
}

$stmt->close();

// Redirect back to inquiries page
header('Location: new-leads.php');
exit();
?>