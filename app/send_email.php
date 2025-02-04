<?php
// Include Composer's autoloader
require __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Function to send email
function sendEmail($toEmail, $subject, $body) {
    // Validazione indirizzo email -> FILTER_VALIDATE_EMAIL
    //Ha un formato valido di email (esempio: utente@example.com)
    //Non contiene caratteri proibiti (come \n, \r, <script> ecc.)
    //Ha un dominio e un nome utente conformi alla RFC 5322
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email address.");
    }

    // Sanitizzazione input per prevenire Email Injection
    $subject = trim(str_replace(["\r", "\n"], '', $subject));
    $body = strip_tags($body); // Rimuove tag HTML non necessari

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
        return true;
    } catch (Exception $e) {
        echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>
