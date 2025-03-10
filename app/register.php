<?php
require 'send_email.php';
require_once 'config.php';
require_once 'csrf.php';

session_start();

use Dotenv\Dotenv;
use ZxcvbnPhp\Zxcvbn;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function setErrorMessage($message, $type = "error") {
    $_SESSION['error_message'] = $message;
    $_SESSION['type'] = $type;
    $_SESSION['source'] = "REGISTER";
    header('Location: index.php'); 
    exit();
}

// Create a logger instance
$log = new Logger('user_registration');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        $log->warning('csrf token error', ['ip' => $_SERVER['REMOTE_ADDR']]);
        die("Something went wrong");
    }    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Validate input fields
    if (empty($username) || empty($email) || empty($password) || empty($recaptcha_response)) {
        setErrorMessage("All fields are required!");;
        $log->warning('Registration attempt with empty fields.');
        exit();
    }
    
    // Validate username format
    if (preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        // Sanitize by encoding special characters
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    } else {
        // Handle invalid username
        setErrorMessage("Invalid username format! The username must contain only letters, numbers, and underscores, and be 3 to 20 characters long.");
        $log->warning('Invalid username format: ' . $username);
        exit();
    }

    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setErrorMessage("Invalid email format!");
        exit();
    }
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);

    // Check if the username or email already exists
    $stmt = $conn->prepare('SELECT id FROM Users WHERE username = ? OR email = ?');
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        setErrorMessage("Registration failed. Please try again.");
        $log->warning('Username or email already exists: ' . $username . ' / ' . $email);
        exit();
    }
    $stmt->close();

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
        setErrorMessage("reCAPTCHA verification failed! Please try again.");
        $log->warning('reCAPTCHA verification failed.');
        exit();
    }

    // Check password strength
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($password);
    if ($strength['score'] < 2) {
        setErrorMessage("Password is too weak. Please choose a stronger password.");
        $log->warning('Weak password chosen.');
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(16));

    // Insert user into the database
    $stmt = $conn->prepare('INSERT INTO Users (username, email, password_hash, token, is_verified, password_changed_at) VALUES (?, ?, ?, ?, 0, NOW())');
    $stmt->bind_param('ssss', $username, $email, $password_hash, $token);

    if ($stmt->execute()) {
        // Send verification email
        $verificationLink = "https://localhost:8080/verify.php?email=$email&token=$token";
        $subject = "Verify Your Email Address";
        $body = "<p>Hi $username,</p>
                 <p>Click the link below to verify your email address:</p>
                 <a href='$verificationLink'>$verificationLink</a>";

        if (sendEmail($email, $subject, $body, $log)) {
            setErrorMessage("Registration successful! Please check your email to verify your account.", "success");
            $log->info('User registered successfully: ' . $username . ' / ' . $email);
        } else {
            setErrorMessage("Error: Unable to send verification email.");
            $log->error('Failed to send verification email to: ' . $email);
        }
    } else {
        setErrorMessage("Error: " . $stmt->error);
        $log->error('Database insertion failed: ' . $stmt->error);
    }

    $stmt->close();
} else{
    $log->warning('Invalid request.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    header("Location: index.php");
}

$conn->close();
?>
