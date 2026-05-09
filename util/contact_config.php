<?php
// util/contact_config.php
session_start();
include_once "../config/connect.php";
$contact = contact_us();

// Rate limiting configuration
define('MAX_SUBMISSIONS_PER_HOUR', 5);
define('RATE_LIMIT_WINDOW', 3600); // 1 hour in seconds

// Email configuration
define('SMTP_HOST', 'smtp.hostinger.com'); // Change to your SMTP server
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'no-reply@web2techsolutions.com'); // Change to your email
define('SMTP_PASSWORD', '8@MGB+XSAsx'); // Use app password, not regular password
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', $contact['company_name']);

// Generate CSRF token
function generateCSRFToken()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Rate limiting
function checkRateLimit($email, $conn)
{
    $window_start = date('Y-m-d H:i:s', time() - RATE_LIMIT_WINDOW);

    $sql = "SELECT COUNT(*) as count FROM inquiries 
            WHERE email = ? AND created_at > ?";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $email, $window_start);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    return $row['count'] < MAX_SUBMISSIONS_PER_HOUR;
}

// Log errors
function logError($message, $data = [])
{
    $log_file = __DIR__ . '/../logs/contact_errors.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }

    $log_entry = date('Y-m-d H:i:s') . " - ERROR: " . $message . "\n";
    if (!empty($data)) {
        $log_entry .= "Data: " . json_encode($data) . "\n";
    }
    $log_entry .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
    $log_entry .= "----------------------------------------\n";

    error_log($log_entry, 3, $log_file);
}

// Send email using PHPMailer (more reliable than mail())
function sendInquiryEmailSMTP($name, $email, $phone, $subject, $message, $contact, $email_logo, $site)
{
    // You'll need to install PHPMailer via Composer
    // require_once __DIR__ . '/../vendor/autoload.php';

    // For now, using mail() with better headers
    $to = $contact['contact_email'] ?? SMTP_FROM_EMAIL;
    $email_subject = "New Contact Form Inquiry: " . ($subject ?: "No Subject");

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $email_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>New Contact Form Inquiry</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; background: #f4f4f4; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
            .header { background: #2ecc71; color: white; text-align: center; padding: 20px; }
            .header img { max-width: 200px; background: white; padding: 10px; border-radius: 5px; }
            .content { padding: 30px; }
            .field { margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
            .label { font-weight: bold; color: #2c3e50; font-size: 14px; text-transform: uppercase; }
            .value { margin-top: 5px; color: #34495e; font-size: 16px; }
            .footer { background: #f8f9fa; text-align: center; padding: 15px; color: #7f8c8d; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <img src='{$site}{$email_logo}' alt='Logo' style='max-width:200px;'>
                <h2>New Contact Form Submission</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <div class='label'>Name:</div>
                    <div class='value'>" . htmlspecialchars($name) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Email:</div>
                    <div class='value'>" . htmlspecialchars($email) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Phone:</div>
                    <div class='value'>" . htmlspecialchars($phone) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Subject:</div>
                    <div class='value'>" . htmlspecialchars(!empty($subject) ? $subject : 'No Subject') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Message:</div>
                    <div class='value'>" . nl2br(htmlspecialchars($message)) . "</div>
                </div>
                <div class='field'>
                    <div class='label'>Submitted:</div>
                    <div class='value'>" . date('F j, Y, g:i a') . "</div>
                </div>
                <div class='field'>
                    <div class='label'>IP Address:</div>
                    <div class='value'>" . $_SERVER['REMOTE_ADDR'] . "</div>
                </div>
            </div>
            <div class='footer'>
                <p>This message was sent from your website contact form.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    return mail($to, $email_subject, $email_body, $headers);
}