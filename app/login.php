<?php
require_once 'config.php';
require_once 'csrf.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('user_login');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

session_start();
$inactive = 300; // 5 minutes session timeout

function showMessage($message, $type = "error") {
    $color = $type === "success" ? "#28a745" : "#dc3545"; // Green for success, red for error
    echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background: $color; color: white; text-align: center; font-weight: bold;'>
            $message
          </div>";
}

// Check if session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    session_unset();
    session_destroy();
    showMessage("Session expired. Please log in again.");
    $log->warning('Session expired due to inactivity.', ['session_id' => session_id()]);
    exit();
}

$_SESSION['timeout'] = time(); // Update session timeout

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    $log->warning('Login attempt using unsupported HTTP method.', ['method' => $_SERVER['REQUEST_METHOD'], 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
    die("Error, invalid csrf token"); ### DA CAMBIARE PERCHè SPECIFICO
    exit();
}


// Validate required fields
$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($username) || empty($password) || empty($recaptcha_response)) {
    showMessage("All fields are required!");
    $log->warning('Login attempt with missing fields.', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

// Validate username format
if (preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
    // Sanitize by encoding special characters
    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
} else {
    showMessage("Invalid username format!");
    $log->warning('Invalid username format.', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

// Verify reCAPTCHA
$recaptcha_secret = $_ENV['RECAPTCHA_V2_SECRETKEY'];
$recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';

$ch = curl_init($recaptcha_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, [
    'secret' => $recaptcha_secret,
    'response' => $recaptcha_response
]);
$recaptcha_verify = curl_exec($ch);
curl_close($ch);

$recaptcha_data = json_decode($recaptcha_verify, true);

if (!$recaptcha_data || !$recaptcha_data['success']) {
    showMessage("reCAPTCHA verification failed! Please try again.");
    $log->warning('reCAPTCHA verification failed.', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

// Prepare SQL query
$stmt = $conn->prepare('SELECT id, username, password_hash, is_premium, role, is_verified FROM Users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $db_username, $password_hash, $is_premium, $role, $is_verified);
    $stmt->fetch();

    if (!is_string($password_hash) || empty($password_hash)) {
        $log->error('Invalid password hash retrieved from database.', ['username' => $db_username]);
        die('Authentication error.');
        exit();
    }

    if (password_verify($password, $password_hash)) {
        if (!$is_verified) {
            showMessage("Please verify your email address to activate your account.");
            $log->warning('Login attempt with unverified email.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
            exit();
        }
        session_regenerate_id(true); // Prevent session fixation
        newToken();
        // Store user info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['is_premium'] = $is_premium;
        $_SESSION['role'] = $role;
        $log->info('User logged in successfully.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        header('Location: home.php');
        exit();
    } else {
        $log->warning('Failed login attempt due to incorrect password.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        // Se un attaccante prova diversi username e riceve sempre lo stesso messaggio, può indovinare username validi. ora si crea invece ambiguità
        showMessage("Invalid username or password!");
        exit();
    }
} else {
    showMessage("Invalid credentials!");
    $log->warning('Failed login attempt with non-existent username.', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

$stmt->close();
$conn->close();
?>
