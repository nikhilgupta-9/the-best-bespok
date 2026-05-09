<?php
include_once "function.php";
$contact = contact_us();

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subscribe'])) {

    $email = trim($_POST['email']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address");
    }

    $to = $contact['contact_email'];   // 🔥 Change to your email
    $subject = "New Newsletter Subscription";

    $message = "
    New subscriber details:

    Email: $email
    ";

    $headers = "From: noreply@bharatfreshagro.com\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    if (mail($to, $subject, $message, $headers)) {
        echo "Subscription successful!";
    } else {
        echo "Mail sending failed.";
    }
}
?>