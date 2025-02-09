<?php
require 'vendor/autoload.php';

use ZxcvbnPhp\Zxcvbn;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('check_password');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

$zxcvbn = new Zxcvbn();

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($data['password']) || !is_string($data['password'])) {
    $log->warning('Invalid input', ['input' => $data]);
    die(json_encode(['error' => 'Invalid input']));
}
$password = trim($data['password']);

$strength = $zxcvbn->passwordStrength($password);
$log->info('Password strength checked', ['password' => $password, 'strength' => $strength]);
echo json_encode($strength);
?>