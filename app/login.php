<?php
require_once 'config.php';
require_once 'csrf.php';
require 'vendor/autoload.php';
use Dotenv\Dotenv;

session_start();
$inactive = 300; // 5 minutes session timeout

function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
    $_SESSION['source'] = "LOGIN";
    header('Location: index.php'); // Reindirizza l'utente alla pagina di login
    exit();
}

// function showMessage($message, $type = "error") {
//     $color = $type === "success" ? "#28a745" : "#dc3545"; // Green for success, red for error
//     echo "<div style='padding: 10px; margin: 10px 0; border-radius: 5px; background: $color; color: white; text-align: center; font-weight: bold;'>
//             $message
//           </div>";
// }

// Check if session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    session_unset();
    session_destroy();
    // showMessage("Session expired. Please log in again.");
    setErrorMessage("Session expired. Please log in again.");
    exit();
}

$_SESSION['timeout'] = time(); // Update session timeout

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
    die("Something went wrong");
}


// Validate required fields
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');
$recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

if (empty($email) || empty($password) || empty($recaptcha_response)) {
    // showMessage("All fields are required!");
    setErrorMessage("Session expired. Please log in again.");
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // showMessage("Invalid email format!");
    setErrorMessage("Invalid email format!");
    exit();
}
$email = filter_var($email, FILTER_SANITIZE_EMAIL);


// TODO: uncomment this part later on
// Validate password length
// if (strlen($password) <= 8) {
//     showMessage("Password must be at least 8 characters long!");
//     exit();
// }

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
    // showMessage("reCAPTCHA verification failed! Please try again.");
    setErrorMessage("reCAPTCHA verification failed! Please try again.");
    exit();
}

// Prepare SQL query
$stmt = $conn->prepare('SELECT id, username, password_hash, is_premium, role, trials, unlocking_date FROM Users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $db_username, $password_hash, $is_premium, $role, $trials, $unlocking_date);
    $stmt->fetch();

    // Controlla se l'account è bloccato
    if (($trials % 3) === 0 && $unlocking_date!== NULL) {
        $current_date = new DateTime();
        $unlock_date = new DateTime($unlocking_date);
        
        if ($current_date < $unlock_date) {
            // showMessage("Account is locked until " . $unlock_date->format('Y-m-d H:i:s'));
            $_SESSION['unlock_date'] = strtotime($unlock_date->format('Y-m-d H:i:s'));
            setErrorMessage("Too many failed attempts. Try again in ");
            exit();
        }
    }

    if (!is_string($password_hash) || empty($password_hash)) {
        die('Authentication error.');
    }

    if (password_verify($password, $password_hash)) {
        session_regenerate_id(true); // Prevent session fixation
        newToken();
        // Store user info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['is_premium'] = $is_premium;
        $_SESSION['role'] = $role;
        
        $update_stmt = $conn->prepare('UPDATE Users SET trials = 0, unlocking_date = NULL WHERE id = ?');
        $update_stmt->bind_param('i', $user_id);
        $update_stmt->execute();
        
        header('Location: home.php');
        exit();
    } else {
        $trials ++;
        if(($trials % 3)==0){
            $lock_duration = min(5 * pow(2, $trials), 86400);
            $new_unlocking_date = (new DateTime())->modify("+$lock_duration seconds")->format('Y-m-d H:i:s');

            $update_stmt = $conn->prepare('UPDATE Users SET trials = ?, unlocking_date = ? WHERE id = ?');
            $update_stmt->bind_param('isi', $trials, $new_unlocking_date,$user_id);
            $update_stmt->execute();
        }else{
            $update_stmt = $conn->prepare('UPDATE Users SET trials = ?, unlocking_date = NULL WHERE id = ?');
            $update_stmt->bind_param('ii', $trials ,$user_id);
            $update_stmt->execute();
        }
        // showMessage("Invalid username or password!");
        setErrorMessage("Invalid username or password!");
        exit();
    }
} else {
    // showMessage("Invalid credentials!");
    setErrorMessage("Invalid username or password!");
    exit();
}

$stmt->close();
$conn->close();
?>
