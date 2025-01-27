<?php
session_start(); // Start the session

// Include database connection
$host = 'mysql-container';
$username = 'a';
$password = 'a';
$dbname = 'novelists_db';

$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

// Handle form data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        die('All fields are required!');
    }

    $stmt = $conn->prepare('SELECT id, username, password_hash, is_premium, role FROM Users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $db_username, $password_hash, $is_premium, $role);
        $stmt->fetch();

        if (password_verify($password, $password_hash)) {
            // Store user info in session
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $db_username;
            $_SESSION['is_premium'] = $is_premium;
            $_SESSION['role'] = $role;

            header('Location: dashboard.php');
            exit();
        } else {
            echo 'Invalid username or password!';
        }
    } else {
        echo 'Invalid username or password!';
    }

    $stmt->close();
}

$conn->close();
?>
