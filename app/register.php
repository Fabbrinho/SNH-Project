<?php
// Include database connection
$host = 'mysql-container';
$username = 'a'; // Or the username you configured
$password = 'a'; // Or your root password
$dbname = 'novelists_db';

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Input validation
    if (empty($username) || empty($email) || empty($password)) {
        die('All fields are required!');
    }

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Prepare and execute query
    $stmt = $conn->prepare('INSERT INTO Users (username, email, password_hash) VALUES (?, ?, ?)');
    $stmt->bind_param('sss', $username, $email, $password_hash);

    if ($stmt->execute()) {
        echo 'Registration successful!';
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
