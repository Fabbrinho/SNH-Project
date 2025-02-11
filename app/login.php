<?php
require_once 'config.php';
require_once 'csrf.php';

use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('user_login');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

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
    $log->warning('Session expired due to inactivity.', ['session_id' => session_id()]);
    exit();
}

$_SESSION['timeout'] = time(); // Update session timeout

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    $log->warning('Login attempt using unsupported HTTP method.', ['method' => $_SERVER['REQUEST_METHOD'], 'ip' => $_SERVER['REMOTE_ADDR']]);
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
    setErrorMessage("All fields are required!");
    $log->warning('Login attempt with missing fields.', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // showMessage("Invalid email format!");
    setErrorMessage("Invalid email format!");
    $log->warning('Login attempt with invalid email format.', ['email' => $email, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

$email = filter_var($email, FILTER_SANITIZE_EMAIL);

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
    $log->warning('reCAPTCHA verification failed.', ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

// Prepare SQL query
$stmt = $conn->prepare('SELECT id, username, password_hash, is_premium, role, is_verified, trials, unlocking_date, password_changed_at FROM Users WHERE email = ?');
$stmt->bind_param('s', $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->bind_result($user_id, $db_username, $password_hash, $is_premium, $role, $is_verified, $trials, $unlocking_date,$password_changed_at);
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
        $log->error('Invalid password hash retrieved from database.', ['username' => $db_username]);
        die('Authentication error.');
        exit();
    }

    
    // // Definiamo la durata massima della password in minuti (1 minuto per il test)
    // $max_password_age_minutes = 1;

    // // Convertiamo la data di cambio password in un oggetto DateTime
    // $password_last_changed = new DateTime($password_changed_at);
    // $current_date = new DateTime();

    // // Calcoliamo la differenza in minuti
    // $interval = $current_date->getTimestamp() - $password_last_changed->getTimestamp();
    // $interval_in_minutes = $interval / 60; // Converte i secondi in minuti

    // // Se la password è più vecchia del limite massimo, reindirizza al cambio password
    // if ($interval_in_minutes > $max_password_age_minutes) {
    //     $_SESSION['force_password_reset'] = true; // Indica che il reset è obbligatorio
    //     header('Location: force_password_change.php'); // Nuova pagina per il cambio password
    //     exit();
    // }



    if (password_verify($password, $password_hash)) {
        if (!$is_verified) {
            setErrorMessage("Please verify your email address to activate your account.");
            $log->warning('Login attempt with unverified email.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
            exit();
        }
        session_regenerate_id(true); // Prevent session fixation
        newToken();
        // Store user info in session
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $db_username;
        $_SESSION['is_premium'] = $is_premium;
        $_SESSION['role'] = $role;

            // // Definiamo la durata massima della password (es. 90 giorni)
        $max_password_age = 90; // giorni

        $password_last_changed = new DateTime($password_changed_at);
        $current_date = new DateTime();
        $interval = $password_last_changed->diff($current_date);

        // Se la password è più vecchia di 90 giorni, reindirizza al cambio password
        if ($interval->days > $max_password_age) {
            $_SESSION['force_password_reset'] = true; // Indica che il reset è obbligatorio
            header('Location: force_password_change.php'); // Nuova pagina per il cambio password
            exit();
        }

        $update_stmt = $conn->prepare('UPDATE Users SET trials = 0, unlocking_date = NULL WHERE id = ?');
        $update_stmt->bind_param('i', $user_id);
        $update_stmt->execute();
        $log->info('User logged in successfully.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        
        header('Location: home.php');
        exit();
    } else {
        $trials ++;
        if(($trials % 3)==0){
            $log->info('Login Locking due to retry limit reach.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $lock_duration = min(5 * pow(2, $trials), 86400);
            $new_unlocking_date = (new DateTime())->modify("+$lock_duration seconds")->format('Y-m-d H:i:s');

            $update_stmt = $conn->prepare('UPDATE Users SET trials = ?, unlocking_date = ? WHERE id = ?');
            $update_stmt->bind_param('isi', $trials, $new_unlocking_date,$user_id);
            $update_stmt->execute();
        }else{
            $log->info('Increasing login retry counter.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
            $update_stmt = $conn->prepare('UPDATE Users SET trials = ?, unlocking_date = NULL WHERE id = ?');
            $update_stmt->bind_param('ii', $trials ,$user_id);
            $update_stmt->execute();
        }
        // showMessage("Invalid username or password!");
        setErrorMessage("Invalid email or password!");
        $log->warning('Failed login attempt due to incorrect password.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
        exit();
    }
    
} else {
    // showMessage("Invalid credentials!");
    setErrorMessage("Invalid username or password!");
    $log->warning('Failed login attempt with non-existent username.', ['username' => $db_username, 'ip' => $_SERVER['REMOTE_ADDR']]);
    exit();
}

$stmt->close();
$conn->close();
?>
