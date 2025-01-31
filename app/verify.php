<?php
require_once 'config.php';

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
            $message = 'Your email has been verified successfully. You can now log in.';
        } else {
            $message = 'Error: ' . $stmt->error;
        }
    } else {
        $message = 'This verification link is invalid or has already been used.';
    }

    $stmt->close();
} else {
    $message = 'Invalid request.';
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 400px;
            margin: 50px auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        p {
            text-align: center;
            color: #555;
            margin: 20px 0;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            border: none;
            border-radius: 4px;
            color: #fff;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Email Verification</h2>
        <p><?php echo $message; ?></p>
        <form action="home.php">
            <button type="submit">Go to Home</button>
        </form>
    </div>
</body>
</html>
