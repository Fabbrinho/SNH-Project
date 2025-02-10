<?php
require_once 'config.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('verify');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

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
            $log->info('Email verified successfully.', ['email' => $email]);
            $message = 'Your email has been verified successfully. You can now log in.';
        } else {
            $log->error('An error occurred while verifying email.', ['email' => $email]);
            $message = 'An error occurred while verifying your email. Please try again later.';
            error_log("Email verification error: " . $stmt->error);

        }
    } else {
        $log->warning('Invalid verification link.', ['email' => $email]);
        $message = 'The verification link is invalid.';
    }

    $stmt->close();
} else {
    $log->warning('Invalid request.', ['ip' => $_SERVER['REMOTE_ADDR']]);
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
        <!-- Aggiungo controllo per XSS anche se non contiene input dell'utente 
         -->
        <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
        <form action="index.php">
            <button type="submit">Go to Login Page</button>
        </form>
    </div>
</body>
</html>
