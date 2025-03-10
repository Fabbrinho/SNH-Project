<?php
session_start();
require_once 'config.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('user_novels');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

if (!isset($_SESSION['user_id'])) {
    $log->warning('Unauthenticated user tried to access the dashboard.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    // Redirect to login if not authenticated
    $_SESSION['error_message'] = "You must log in first.";
    header('Location: index.php');
    exit();
}

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $log->warning('Invalid novel ID in the URL.', ['user_id' => $_SESSION['user_id']]);
    header("Location: home.php");
    exit();
}
$novel_id = intval($_GET['id']);

$sql = "SELECT title, type, content, file_path FROM Novels WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $novel_id);
$stmt->execute();
$result = $stmt->get_result();
$novel = $result->fetch_assoc();

if (!$novel) {
    $log->warning('Novel not found.', ['user_id' => $_SESSION['user_id'], 'novel_id' => $novel_id]);
    header("Location: home.php");
    exit();
}

if ($novel['type'] == 'full') {
    $file_path = $novel['file_path'];

    if (file_exists($file_path) && is_readable($file_path)) {
        $filename = str_replace(" ", "_", $novel['title']) . ".pdf"; // Nome del file

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); // Forza il download
        header('Content-Disposition: attachment; filename="' . $filename . '"'); // Nome del file da scaricare
        header('Content-Length: ' . filesize($file_path)); // Dimensione del file

        $upload_dir = __DIR__ . '/uploads/';
        $real_path = realpath($file_path);
        if ($real_path === false || strpos($real_path, realpath($upload_dir)) !== 0) {
            $log->warning('Invalid file path', ['user_id' => $_SESSION['user_id'], 'novel_id' => $novel_id]);
            die('Invalid file request.');
        }
        
        $log->info('Novel downloaded', ['user_id' => $_SESSION['user_id'], 'novel_id' => $novel_id]);
        readfile($file_path);

        exit(); 
    } else {
        die('File non trovato o non leggibile!');
    }
}

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($novel['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css">
</head>
<body>
    <div class="container">
        <h3><?php echo htmlspecialchars($novel['title']); ?></h3>
        <pre><?php echo nl2br(htmlspecialchars($novel['content'])); ?></pre>
        <a href="home.php" class="btn blue">Torna alla Home</a>
        <a href="dashboard.php" class="btn blue">Torna alla Dashboard</a>
    </div>
</body>
</html>
