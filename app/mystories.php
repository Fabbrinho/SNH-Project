<?php
require_once 'config.php';
require_once 'csrf.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('user_mystories');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

session_start(); // Start session securely

if (!isset($_SESSION['user_id'])) {
    $log->warning('Unauthenticated user tried to access the dashboard.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    header('Location: login.php');
    exit();
}

$inactive = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    $log->warning('Session expired due to inactivity.', ['session_id' => session_id(), 'username' => $_SESSION['username']]);
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    if (!isset($_POST['token_csrf']) || !verifyToken($_POST['token_csrf'])) {
        die("Something went wrong");
    }    
    
    if (!ctype_digit($_POST['delete_id'])) {
        $log->warning('Invalid request to delete a novel.', ['user_id' => $user_id]);
        die('Invalid request');
    }

    $delete_id = intval($_POST['delete_id']);

    $stmt = $conn->prepare('SELECT file_path FROM Novels WHERE id = ? AND author_id = ?');
    $stmt->bind_param('ii', $delete_id, $user_id);
    $stmt->execute();
    $stmt->bind_result($file_path);
    $stmt->fetch();
    $stmt->close();

    $upload_dir = __DIR__ . '/uploads/';
    if (!empty($file_path)) {
        $real_path = realpath($file_path);
        if (strpos($real_path, realpath($upload_dir)) === 0 && file_exists($real_path)) {
            unlink($real_path);
        } else {
            $_SESSION['error_message'] = 'Invalid file deletion request.';
        }
    }

    $stmt = $conn->prepare('DELETE FROM Novels WHERE id = ? AND author_id = ?');
    $stmt->bind_param('ii', $delete_id, $user_id);

    if ($stmt->execute()) {
        $log->info('Novel deleted successfully.', ['user_id' => $user_id, 'novel_id' => $delete_id]);
        $_SESSION['success_message'] = 'Novel deleted successfully!';
    } else {
        $log->error('An error occurred while deleting the novel.', ['user_id' => $user_id, 'novel_id' => $delete_id]);
        $_SESSION['error_message'] = 'An error occurred. Please try again later.';
    }
    $stmt->close();

    header('Location: mystories.php');
    exit();
}


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
                                <p><a href="novels.php?id=<?php echo $novel['id']; ?>" class="btn blue">See full content</a></p>
                            <?php elseif ($novel['file_path']): ?>
                                <p><a href="novels.php?id=<?php echo $novel['id']; ?>" class="btn blue">Download PDF</a></p>
                            <?php endif; ?>
                        
                            <form action="mystories.php" method="POST" style="display: inline;">
                                <input type="hidden" name="token_csrf" value="<?php echo getToken();?>">
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
