<?php
session_start();
require_once 'config.php'; // Connessione al database

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    header("Location: home.php");
    exit();
}
$novel_id = intval($_GET['id']);

// Query per recuperare la novel
$sql = "SELECT title, type, content, file_path FROM Novels WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $novel_id);
$stmt->execute();
$result = $stmt->get_result();
$novel = $result->fetch_assoc();

if (!$novel) {
    header("Location: home.php");
    exit();
}

// Se la novel è di tipo 'full', procedi con il download
if ($novel['type'] == 'full') {
    $file_path = $novel['file_path'];

    // Verifica che il file esista e sia leggibile
    if (file_exists($file_path) && is_readable($file_path)) {
        $filename = str_replace(" ", "_", $novel['title']) . ".pdf"; // Nome del file

        // Imposta gli header per il download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream'); // Forza il download
        header('Content-Disposition: attachment; filename="' . $filename . '"'); // Nome del file da scaricare
        header('Content-Length: ' . filesize($file_path)); // Dimensione del file

        $upload_dir = __DIR__ . '/uploads/';
        $real_path = realpath($file_path);
        if ($real_path === false || strpos($real_path, realpath($upload_dir)) !== 0) {
            die('Invalid file request.');
        }
        

        // Legge il file e lo invia al browser
        readfile($file_path);

        exit(); // Fermare l'esecuzione del codice per evitare output aggiuntivo
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
