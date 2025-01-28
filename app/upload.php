<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$host = 'mysql-container';
$username = 'a';
$password = 'a';
$dbname = 'novelists_db';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $type = trim($_POST['type']);
    $content = trim($_POST['content']);
    $is_premium = isset($_POST['is_premium']) ? 1 : 0;
    $author_id = $_SESSION['user_id'];

    if (empty($title) || empty($type)) {
        die('Title and type are required!');
    }

    $file_path = null;
    if ($type === 'full' && isset($_FILES['file'])) {
        $upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_name = basename($_FILES['file']['name']);
        $file_path = $upload_dir . uniqid() . '_' . $file_name;
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            die('File upload failed!');
        }
    }

    $stmt = $conn->prepare('INSERT INTO Novels (author_id, title, type, content, file_path, is_premium) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssi', $author_id, $title, $type, $content, $file_path, $is_premium);

    if ($stmt->execute()) {
        header('Location: dashboard.php');
        exit();
    } else {
        echo 'Error: ' . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>