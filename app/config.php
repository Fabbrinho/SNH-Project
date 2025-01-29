<?php
$host = 'mysql-container';
$username = 'a';
$password = 'a';
$dbname = 'novelists_db';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4"); // Imposta il charset per la compatibilità con caratteri speciali
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>