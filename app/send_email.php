<?php
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Function to send email
function sendEmail($toEmail, $subject, $body, $log) {
    // Email address validation -> FILTER_VALIDATE_EMAIL
// Has a valid email format (e.g., user@example.com)
// Does not contain prohibited characters (such as \n, \r, <script>, etc.)
// Has a domain and username compliant with RFC 5322
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $log->warning('Invalid email address.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Invalid email address.");
    }

    $subject = trim(str_replace(["\r", "\n"], '', $subject));
    $allowed_tags = '<p><br><strong><em><a>';
    $body = strip_tags($body, $allowed_tags);
    ##$body = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');

    $mail = new PHPMailer(true);

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASS'];
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        // Email content
        $mail->setFrom($_ENV['SMTP_USERNAME'], 'SNH-novelist');

        $mail->addAddress($toEmail);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;

        // Send email
        $mail->send();
        $log->info('Email sent successfully.', ['to' => $toEmail]);
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        $log->error('Email could not be sent.', ['error' => $mail->ErrorInfo]);
        return false;
    }
}
?>
