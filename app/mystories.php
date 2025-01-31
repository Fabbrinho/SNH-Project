<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Se è stata inviata una richiesta di cancellazione
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delete_id = intval($_POST['delete_id']);

    // Recupera il percorso del file per eliminarlo se necessario
    $stmt = $conn->prepare('SELECT file_path FROM Novels WHERE id = ? AND author_id = ?');
    $stmt->bind_param('ii', $delete_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    // Elimina il record dal database
    $stmt = $conn->prepare('DELETE FROM Novels WHERE id = ? AND author_id = ?');
    $stmt->bind_param('ii', $delete_id, $user_id);

    if ($stmt->execute()) {
        // Se il file_path non è vuoto, elimina il file dal server
        if (!empty($file_path) && file_exists($file_path)) {
            unlink($file_path); // Elimina il file dal filesystem
        }
        $_SESSION['success_message'] = 'Novel deleted successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to delete the novel.';
    }
    $stmt->close();

    // Ricarica la pagina per aggiornare l'elenco
    header('Location: mystories.php');
    exit();
}

// Recupera tutte le novels dell'utente
$stmt = $conn->prepare('SELECT id, title, type, content, file_path, created_at FROM Novels WHERE author_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$novels = [];

while ($row = $result->fetch_assoc()) {
    $novels[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Stories</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="grey lighten-4">

    <nav class="blue">
        <div class="nav-wrapper">
            <a href="#" class="brand-logo center">My Stories</a>
            <ul id="nav-mobile" class="right">
                <li><a href="dashboard.php"><i class="material-icons left">arrow_back</i> Back</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h3 class="center">Your Published Stories</h3>
            
            <!-- Messaggi di successo o errore -->
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="card-panel green lighten-4"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="card-panel red lighten-4"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <?php if (empty($novels)): ?>
                <div class="card-panel yellow lighten-4">You haven’t published any stories yet.</div>
            <?php else: ?>
                <ul class="collection">
                    <?php foreach ($novels as $novel): ?>
                        <li class="collection-item">
                            <span class="title"><strong><?php echo htmlspecialchars($novel['title']); ?></strong></span>
                            <p>Type: <?php echo htmlspecialchars($novel['type']); ?><br>
                               Created on: <?php echo htmlspecialchars($novel['created_at']); ?>
                            </p>
                            <?php if ($novel['type'] === 'short'): ?>
                                <p><em>Content:</em> <?php echo nl2br(htmlspecialchars(substr($novel['content'], 0, 200))); ?>...</p>
                            <?php elseif ($novel['file_path']): ?>
                                <p><a href="<?php echo htmlspecialchars($novel['file_path']); ?>" class="btn blue">Download PDF</a></p>
                            <?php endif; ?>
                            <!-- Pulsante per eliminare la novel -->
                            <form action="mystories.php" method="POST" style="display: inline;">
                                <input type="hidden" name="delete_id" value="<?php echo $novel['id']; ?>">
                                <button type="submit" class="btn red">
                                    <i class="material-icons left">delete</i>Delete
                                </button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <footer class="page-footer blue">
        <div class="container">
            <p class="center white-text">&copy; 2025 Novelists</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>
