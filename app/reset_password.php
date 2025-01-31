<?php
session_start();
require 'vendor/autoload.php'; // Include Composer's autoloader

use ZxcvbnPhp\Zxcvbn;

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
    $token = trim($_POST['token']);
    $new_password = trim($_POST['new_password']);

    if (empty($email) || empty($token) || empty($new_password)) {
        die('All fields are required!');
    }

    // Validate token
    $stmt = $conn->prepare('SELECT id, reset_expires FROM Users WHERE email = ? AND reset_token = ?');
    $stmt->bind_param('ss', $email, $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        die('Invalid or expired token.');
    }

    $stmt->bind_result($user_id, $reset_expires);
    $stmt->fetch();

    // Check if token expired
    if (strtotime($reset_expires) < time()) {
        die('Token expired. Please request a new password reset.');
    }

    // Check password strength using zxcvbn-php
    $zxcvbn = new Zxcvbn();
    $strength = $zxcvbn->passwordStrength($new_password);

    // Define a minimum strength threshold (e.g., score >= 2)
    if ($strength['score'] < 2) {
        die('Password is too weak. Please choose a stronger password.');
    }

    // Update password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare('UPDATE Users SET password_hash = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?');
    $stmt->bind_param('si', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        echo "<p>Password successfully updated! <a href='index.php'>Login</a></p>";
    } else {
        echo "<p>Error updating password.</p>";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
    <!-- Include zxcvbn.js for client-side password strength feedback -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/zxcvbn/4.4.2/zxcvbn.js"></script>
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
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }
        input[type="password"]:focus {
            border-color: #28a745;
            outline: none;
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
        .message {
            text-align: center;
            margin-top: 10px;
        }
        .message a {
            color: #007bff;
            text-decoration: none;
        }
        .message a:hover {
            text-decoration: underline;
        }
        #password-strength {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <form action="reset_password.php" method="POST">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
            <input type="password" name="new_password" id="new_password" required placeholder="Enter new password" oninput="checkPasswordStrength(this.value)">
            <div id="password-strength"></div>
            <button type="submit">Reset Password</button>
        </form>
        <div class="message">
            <p>Remembered your password? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        // Function to check password strength using zxcvbn.js
        function checkPasswordStrength(password) {
            const strengthText = document.getElementById("password-strength");
            if (password.length === 0) {
                strengthText.innerHTML = "";
                return;
            }

            // Use zxcvbn.js to evaluate password strength
            const result = zxcvbn(password);
            const strength = result.score;
            let message = "";
            let color = "red";

            switch (strength) {
                case 0:
                    message = "Very Weak";
                    break;
                case 1:
                    message = "Weak";
                    break;
                case 2:
                    message = "Moderate";
                    color = "orange";
                    break;
                case 3:
                    message = "Strong";
                    color = "green";
                    break;
                case 4:
                    message = "Very Strong";
                    color = "darkgreen";
                    break;
                default:
                    message = "Unknown";
            }

            strengthText.innerHTML = `<span style="color: ${color};">${message}</span>`;
        }
    </script>
</body>
</html>