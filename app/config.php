<?php
$host = getenv('DB_HOST');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');
$dbname = getenv('DB_NAME');
$recap_v3_sec = getenv('RECAPTCHA_V3_SECRETKEY');
$recap_v3_site = getenv('RECAPTCHA_V3_SITEKEY');
$recap_v2_sec = getenv('RECAPTCHA_V2_SECRETKEY');
$recap_v2_site = getenv('RECAPTCHA_V2_SITEKEY');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4"); // Imposta il charset per la compatibilità con caratteri speciali
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>