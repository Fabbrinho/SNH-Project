<?php
require 'send_email.php';
require_once 'config.php';
require_once 'csrf.php';
require_once 'vendor/autoload.php';

session_start();
use Dotenv\Dotenv;
use ZxcvbnPhp\Zxcvbn;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
    $_SESSION['source'] = "REGISTER";
    header('Location: index.php'); // Reindirizza l'utente alla pagina di login
    exit();
}

// function showMessage($message, $type = "error") {
//     $color = $type === "success" ? "#28a745" : "#dc3545"; // Green for success, red for error
//     echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background: $color; color: white; text-align: center; font-weight: bold;'>
//             $message
//           </div>";
// }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        die("Something went wrong");
    }    
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    // Validate input fields
    if (empty($username) || empty($email) || empty($password) || empty($recaptcha_response)) {
        // showMessage("All fields are required!");
        setErrorMessage("All fields are required!");
        exit();
    }

    // Validate username format
    if (preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        // Sanitize by encoding special characters
        $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    } else {
        // Handle invalid username
        // showMessage("Invalid username format! The username must contain only letters, numbers, and underscores, and be 3 to 20 characters long.");
        setErrorMessage("Invalid username format! The username must contain only letters, numbers, and underscores, and be 3 to 20 characters long.");
        exit();
    }

    $username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // showMessage("Invalid email format!");
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
        // showMessage("Registration failed. Please try again.");
        setErrorMessage("Registration failed. Please try again.");
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
        //showMessage("reCAPTCHA verification failed! Please try again.");
        setErrorMessage("reCAPTCHA verification failed! Please try again.");
        exit();
    }

    // Check password strength
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($password);
    if ($strength['score'] < 2) {
        // showMessage("Password is too weak. Please choose a stronger password.");
        setErrorMessage("Password is too weak. Please choose a stronger password.");
        exit();
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(16));

    // Insert user into the database
    $stmt = $conn->prepare('INSERT INTO Users (username, email, password_hash, token, is_verified) VALUES (?, ?, ?, ?, 0)');
    $stmt->bind_param('ssss', $username, $email, $password_hash, $token);

    if ($stmt->execute()) {
        // Send verification email
        $verificationLink = "https://localhost:8080/verify.php?email=$email&token=$token";
        $subject = "Verify Your Email Address";
        $body = "<p>Hi $username,</p>
                 <p>Click the link below to verify your email address:</p>
                 <a href='$verificationLink'>$verificationLink</a>";

        if (sendEmail($email, $subject, $body)) {
            // showMessage("Registration successful! Please check your email to verify your account.", "success");
            setErrorMessage("Registration successful! Please check your email to verify your account.", "success");
        } else {
            // showMessage("Error: Unable to send verification email.");
            setErrorMessage("Error: Unable to send verification email.");
        }
    } else {
        // showMessage("Error: " . $stmt->error);
        setErrorMessage("Error: " . $stmt->error);
    }

    $stmt->close();
}

$conn->close();
?>
