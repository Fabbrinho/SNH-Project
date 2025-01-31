<?php
require 'send_email.php';
require 'vendor/autoload.php'; // Include Composer's autoloader

use ZxcvbnPhp\Zxcvbn;

$host = 'mysql'; // This should be the name of your MySQL service from Docker Compose
$username = 'a'; // Your MySQL user
$password = 'a'; // Your MySQL password
$dbname = 'novelists_db'; // Your MySQL database name

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($email) || empty($password)) {
        die('All fields are required!');
    }

    // Check password strength using zxcvbn-php
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($password);

    // Define a minimum strength threshold (e.g., score >= 2)
    if ($strength['score'] < 2) {
        die('Password is too weak. Please choose a stronger password.');
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Generate a unique token
    $token = bin2hex(random_bytes(16));

    // Insert user into the database with the token and is_verified set to false
    $stmt = $conn->prepare('INSERT INTO Users (username, email, password_hash, token, is_verified) VALUES (?, ?, ?, ?, 0)');
    $stmt->bind_param('ssss', $username, $email, $password_hash, $token);
    if ($stmt->execute()) {
        // Send verification email
        $verificationLink = "http://localhost:8080/verify.php?email=$email&token=$token";

        $subject = "Verify Your Email Address";
        $body = "<p>Hi $username,</p>
                 <p>Click the link below to verify your email address:</p>
                 <a href='$verificationLink'>$verificationLink</a>";

        if (sendEmail($email, $subject, $body)) {
            echo "Registration successful! Please check your email to verify your account.";
        } else {
            echo "Error: Unable to send verification email.";
        }
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>