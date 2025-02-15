<?php
   use Monolog\Logger;
   use Monolog\Handler\StreamHandler;
   use Monolog\Level;
   require_once 'vendor/autoload.php';

   // Create a logger instance
   $log = new Logger('user_logout');
   // Define the log file path
   $logFile = __DIR__ . '/logs/novelist-app.log';
   // Add a handler to write logs to the specified file
   $log->pushHandler(new StreamHandler($logFile, Level::Debug));

   session_start();
   $user = isset($_SESSION['username']) ? $_SESSION['username'] : 'unknown user';
   $log->info('User logged out.', ['username' => $user]);
   session_unset();
   session_destroy();
   header('Location: index.php');
   exit();
?>
