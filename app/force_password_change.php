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
$log = new Logger('force_password_change');
$logFile = __DIR__ . '/logs/novelist-app.log';
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

if (!isset($_SESSION['user_id']) || !$_SESSION['force_password_reset']) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('CSRF token verification failed.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Something went wrong");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $new_password = trim($_POST['new_password']);

    if (empty($new_password)) {
        $log->warning('Password change attempt with empty password.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Password is required!');
        exit();
    }

    // Check password strength
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($new_password);

    if ($strength['score'] < 2) {
        $log->warning('Weak password provided.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die('Password is too weak. Please choose a stronger password.');
        exit();
    }

    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE Users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?');
    $stmt->bind_param('si', $hashed_password, $user_id);

    if ($stmt->execute()) {
        unset($_SESSION['force_password_reset']);
        $log->info('Password successfully updated.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background:rgb(107, 197, 128); color: white; text-align: center; font-weight: bold;'>
                Password successfully updated! <a href='login.php'>Login</a>
              </div>";
    } else {
        $log->error('Error updating password.', ['ip' => $_SERVER['REMOTE_ADDR']]);
        echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background:rgb(221, 84, 98); color: white; text-align: center; font-weight: bold;'>
                Error updating password. Please try again.
              </div>";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password</title>
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
        #password-strength {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Change Your Password</h2>
        <p>Your password has expired. Please set a new one to continue.</p>
        <form action="force_password_change.php" method="POST">
            <input type="hidden" name="token_csrf" value="<?php echo getToken(); ?>">
            <input type="password" name="new_password" id="new_password" required placeholder="Enter new password" oninput="checkPasswordStrength(this.value)">
            <div id="password-strength"></div>
            <button type="submit">Update Password</button>
        </form>
    </div>

    <script>
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById("password-strength");
            if (password.length === 0) {
                strengthText.innerHTML = "";
                return;
            }

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
