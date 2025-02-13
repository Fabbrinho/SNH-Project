<?php
require_once 'vendor/autoload.php';
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('config');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

$host = getenv('DB_HOST');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$recap_v3_sec = getenv('RECAPTCHA_V3_SECRETKEY');
$recap_v3_site = getenv('RECAPTCHA_V3_SITEKEY');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4"); // Set charset for compatibility with special characters
    $log->info('Database connection established.', [
        'host' => $host,
        'dbname' => $dbname
    ]);
} catch (Exception $e) {
    $log->error('Database connection failed: ' . $e->getMessage());
    die('Database connection failed: ' . $e->getMessage());
}
?>