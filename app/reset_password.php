<?php
session_start();

require 'vendor/autoload.php';
require_once 'config.php';
require_once 'csrf.php';

use ZxcvbnPhp\Zxcvbn;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('reset_password');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('csrf token error', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Something went wrong");
    }    
    $email = trim($_POST['email']);
    $token = trim($_POST['token']);
    $new_password = trim($_POST['new_password']);

    if (empty($email) || empty($token) || empty($new_password)) {
        $log->warning('Reset password attempt with empty fields.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('All fields are required!');
    }

    // Validate token
    $stmt = $conn->prepare('SELECT id, reset_expires FROM Users WHERE email = ? AND reset_token = ?');
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $log->warning('Invalid or expired reset token.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Invalid or expired token.');
    }

    $stmt->bind_result($user_id, $reset_expires);
    $stmt->fetch();

    // Check if token expired
    if (strtotime($reset_expires) < time()) {
        $log->warning('Reset token expired.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Token expired. Please request a new password reset.');
    }

    // Check password strength using zxcvbn-php
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($new_password);

    // Define a minimum strength threshold (e.g., score >= 2)
    if ($strength['score'] < 2) {
        $log->warning('Weak password provided.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Password is too weak. Please choose a stronger password.');
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE Users SET password_hash = ?, password_changed_at = NOW(), reset_token = NULL, reset_expires = NULL WHERE id = ?');
    $stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        $log->info('Password reset successfully.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background:rgb(107, 197, 128); color: white; text-align: center; font-weight: bold;'>
                Password successfully updated! <a href='index.php'>Login</a>
            </div>";
    } else {
        $log->error('Error updating password.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background:rgb(221, 84, 98); color: white; text-align: center; font-weight: bold;'>
                Error updating password. Please try again.
            </div>";
        exit();
    }

    $stmt->close();
}
else {
    if(!isset($_GET['email']) || !isset($_GET['token']) || !getToken()){
        $log->warning('Invalid request.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        header("Location: index.php");
    }

    $email = trim($_GET['email']);
    $token = trim($_GET['token']);

    // Validate token
    $stmt = $conn->prepare('SELECT id, reset_expires FROM Users WHERE email = ? AND reset_token = ?');
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $log->warning('Invalid or expired reset token.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Invalid or expired token.');
    }

    $stmt->bind_result($user_id, $reset_expires);
    $stmt->fetch();

    // Check if token expired
    if (strtotime($reset_expires) < time()) {
        $log->warning('Reset token expired.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background:rgb(221, 84, 98); color: white; text-align: center; font-weight: bold;'>
                Token expired. Please request a new password reset.
            </div>";
        exit();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <!-- Include zxcvbn.js for client-side password strength feedback -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="password"]:focus {
            border-color: #28a745;
            outline: none;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .message {
            text-align: center;
            margin-top: 10px;
        }
        .message a {
            color: #007bff;
            text-decoration: none;
        }
        .message a:hover {
            text-decoration: underline;
        }
        #password-strength {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="token_csrf" value="<?php echo getToken(); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <input type="password" name="new_password" id="new_password" required placeholder="Enter new password" oninput="checkPasswordStrength(this.value)">
            <div id="password-strength"></div>
            <button type="submit">Reset Password</button>
        </form>
        <div class="message">
            <p>Remembered your password? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        // Function to check password strength using zxcvbn.js
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById("password-strength");
            if (password.length === 0) {
                strengthText.innerHTML = "";
                return;
            }

            // Use zxcvbn.js to evaluate password strength
            const result = zxcvbn(password);
            const strength = result.score;
            let message = "";
            let color = "red";

            switch (strength) {
                case 0:
                    message = "Very Weak";
                    break;
                case 1:
                    message = "Weak";
                    break;
                case 2:
                    message = "Moderate";
                    color = "orange";
                    break;
                case 3:
                    message = "Strong";
                    color = "green";
                    break;
                case 4:
                    message = "Very Strong";
                    color = "darkgreen";
                    break;
                default:
                    message = "Unknown";
            }

            strengthText.innerHTML = `<span style="color: ${color};">${message}</span>`;
        }
    </script>
</body>
</html>