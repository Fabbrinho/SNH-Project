<?php
$host = 'mysql'; // This should be the name of your MySQL service from Docker Compose
$username = 'a'; // Your MySQL user
$password = 'a'; // Your MySQL password
$dbname = 'novelists_db'; // Your MySQL database name

$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if (isset($_GET['email']) && isset($_GET['token'])) {
    $email = $_GET['email'];
    $token = $_GET['token'];

    // Prepare and execute the query
    $stmt = $conn->prepare('SELECT id FROM Users WHERE email = ? AND token = ? AND is_verified = 0');
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // User found, update is_verified to true and clear the token
        $stmt->close();
        $stmt = $conn->prepare('UPDATE Users SET is_verified = 1, token = NULL WHERE email = ?');
        $stmt->bind_param('s', $email);
        if ($stmt->execute()) {
            echo 'Your email has been verified successfully. You can now log in.';
        } else {
            echo 'Error: ' . $stmt->error;
        }
    } else {
        echo 'This verification link is invalid or has already been used.';
    }

    $stmt->close();
} else {
    echo 'Invalid request.';
}

$conn->close();
?>
