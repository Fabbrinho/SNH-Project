<?php
session_start();
require 'send_email.php';

$host = 'mysql';
$username = 'a';
$password = 'a';
$dbname = 'novelists_db';

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    
    if (empty($email)) {
        die('Email is required!');
    }

    // Check if user exists
    $stmt = $conn->prepare('SELECT id FROM Users WHERE email = ?');
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        echo 'If this email exists, a reset link will be sent.';
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

        // Send email with reset link
        $resetLink = "https://localhost:8080/reset_password.php?token=$token&email=$email";
        $subject = "Password Reset Request";
        $body = "<p>Click the link below to reset your password:</p>
                 <a href='$resetLink'>$resetLink</a>";

        if (sendEmail($email, $subject, $body)) {
            echo "A reset link has been sent to the given email.";
        } else {
            echo "Error: Unable to send reset email.";
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
