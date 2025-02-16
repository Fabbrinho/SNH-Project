<?php
require_once 'config.php';
require_once 'csrf.php';
require 'send_email.php';

use ZxcvbnPhp\Zxcvbn;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

function setErrorMessage($message, $type = "error") {
    $_SESSION['error_message'] = $message;
    $_SESSION['type'] = $type;
    $_SESSION['source'] = "SETTINGS";
}

$log = new Logger('user_settings');
$logFile = __DIR__ . '/logs/novelist-app.log';
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

session_start();
$inactive = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    $log->warning('Session expired due to inactivity.', ['session_id' => session_id(), 'username' => $_SESSION['username']]);
    setErrorMessage("Session expired.");
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

if (!isset($_SESSION['user_id'])) {
    $log->warning('Unauthenticated user tried to access settings.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    setErrorMessage("You must log in to access the dashboard.");
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('CSRF token error', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Something went wrong");
    }
    
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        setErrorMessage("All fields are required!");
    }

    if ($new_password !== $confirm_password) {
        setErrorMessage("Passwords do not match.");
    }

    $stmt = $conn->prepare("SELECT password_hash FROM Users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($password_hash);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_password, $password_hash)) {
        setErrorMessage("Incorrect current password.");
    }
    
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($new_password);
    if ($strength['score'] < 2) {
        setErrorMessage("Password is too weak. Please choose a stronger password.");
    }

    $new_password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("UPDATE Users SET password_hash = ?, password_changed_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $new_password_hash, $user_id);

    if ($stmt->execute()) {
        setErrorMessage("Password updated successfully!", "success");
        $log->info('User changed password successfully: ' . $user_id);
    } else {
        setErrorMessage("Error: " . $stmt->error);
        $log->error('Password update failed: ' . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    
    <!-- Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Zxcvbn Password Strength Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>

    <style>
        /* Style for password strength message */
        #password-strength {
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
</head>
<body class="grey lighten-4">
    <nav class="blue">
        <div class="nav-wrapper">
            <a href="dashboard.php" class="brand-logo center">Settings</a>
            <ul id="nav-mobile" class="right">
               <li><a href="dashboard.php"><i class="material-icons left">person</i>Profile</a></li>
               <li><a href="logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h4 class="center">Change Password</h4>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="card-panel <?php echo $_SESSION['type'] === 'success' ? 'green' : 'red'; ?> lighten-3 center">
                <?php echo htmlspecialchars($_SESSION['error_message']); ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card-panel">
            <form action="settings.php" method="POST">
                <div class="input-field">
                    <input id="current_password" name="current_password" type="password" required>
                    <label for="current_password">Current Password</label>
                </div>

                <div class="input-field">
                    <input id="new_password" name="new_password" type="password" required oninput="checkPasswordStrength()">
                    <label for="new_password">New Password</label>
                    <p id="password-strength" class="red-text"></p>
                </div>

                <div class="input-field">
                    <input id="confirm_password" name="confirm_password" type="password" required>
                    <label for="confirm_password">Confirm New Password</label>
                </div>

                <input type="hidden" name="token_csrf" value="<?php echo getToken(); ?>">

                <div class="center">
                    <button type="submit" class="btn blue">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <footer class="page-footer blue">
        <div class="container">
            <p class="center white-text">&copy; 2025 Novelists</p>
        </div>
    </footer>

    <!-- Materialize JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>

    <script>
        function checkPasswordStrength() {
            let password = document.getElementById('new_password').value;
            let strengthText = document.getElementById('password-strength');

            if (!password) {
                strengthText.textContent = '';
                return;
            }

            let result = zxcvbn(password);
            let score = result.score;
            let messages = [
                "Too weak!",
                "Weak",
                "Moderate",
                "Strong",
                "Very strong!"
            ];
            let colors = ["red", "orange", "yellow", "blue", "green"];

            strengthText.textContent = messages[score];
            strengthText.className = colors[score] + "-text";
        }
    </script>
</body>
</html>
