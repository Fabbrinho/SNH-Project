<?php
session_start();
require 'send_email.php';
require_once 'csrf.php';
require_once 'config.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('forgot_password');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

function showMessage($message, $type = "error") {
    $color = $type === "success" ? "rgb(107, 197, 128)" : "rgb(221, 84, 98)"; // Green for success, red for error
    echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background: $color; color: white; text-align: center; font-weight: bold;'>
            $message
          </div>";
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('csrf token error', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Something went wrong");
    }    
    $email = trim($_POST['email']);

    if (empty($email)) {
        die('Email is required!');
    }

    // Validate and sanitize email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Invalid email format!');
    }
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
  
    // Check if user exists
    $stmt = $conn->prepare('SELECT id FROM Users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();


    if ($stmt->num_rows === 0) {
        $log->warning('Password reset attempt for never registered email.', ['email' => $email]);
        showMessage("Invalid parameter passed. Please try again.");
    } else {
        $stmt->bind_result($user_id);
        $stmt->fetch();

        // Generate token
        $token = bin2hex(random_bytes(16));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour')); // Token valid for 1 hour

        // Store token
        $stmt = $conn->prepare('UPDATE Users SET reset_token = ?, reset_expires = ? WHERE id = ?');
        $stmt->bind_param('ssi', $token, $expires, $user_id);
        $stmt->execute();


        // Secure reset link
        $resetLink = "https://localhost:8080/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

        $subject = "Password Reset Request";
        $body = "<p>Click the link below to reset your password:</p>
                 <a href='$resetLink'>$resetLink</a>";

        if (sendEmail($email, $subject, $body, $log)) {
            showMessage("A reset link has been sent to the given email.", "success");
            $log->info('Password reset link sent.', ['email' => $email]);
        } else {
            showMessage("Error: Unable to send reset email.");
            $log->error('Failed to send password reset email.', ['email' => $email]);
        }
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Forgot Password</title>
    <!-- Include Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h2>Forgot Password</h2>
        <form action="forgot_password.php" method="POST">
            <div class="input-field">
                <input type="hidden" name="token_csrf" value= "<?php echo getToken();?>">
                <input type="email" name="email" id="email" required>
                <label for="email">Enter your email</label>
            </div>
            <button type="submit" class="btn waves-effect waves-light">Send Reset Link</button>
        </form>
    </div>

    <!-- Include Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
