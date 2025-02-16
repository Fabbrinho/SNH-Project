<?php
require_once 'config.php';
require_once 'csrf.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;

// Create a logger instance
$log = new Logger('user_dashboard');
// Define the log file path
$logFile = __DIR__ . '/logs/novelist-app.log';
// Add a handler to write logs to the specified file
$log->pushHandler(new StreamHandler($logFile, Level::Debug));

session_start();
$inactive = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    $log->warning('Session expired due to inactivity.', ['session_id' => session_id(), 'username' => $_SESSION['username']]);
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    $log->warning('Unauthenticated user tried to access the dashboard.', ['ip' => $_SERVER['REMOTE_ADDR']]);
    $_SESSION['error_message'] = "You must log in to access the dashboard.";
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8');
$is_premium = $_SESSION['is_premium'];

// Fetch user's novels
$stmt = $conn->prepare('SELECT title, type, created_at FROM Novels WHERE author_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

$novels = [];
while ($row = $result->fetch_assoc()) {
    $novels[] = $row;
}
$log->info('User novels fetched', ['user_id' => $user_id, 'novels_count' => count($novels)]);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <!-- Include Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .container {
            max-width: 800px;
        }
        .center-card {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>
<body class="grey lighten-4">

    <nav class="blue">
        <div class="nav-wrapper">
            <a href="#" class="brand-logo center">Dashboard</a>
            <ul id="nav-mobile" class="right">
                <li><a href="home.php"><i class="material-icons left">go_to_home</i>Home</a></li>
                <li><a href="settings.php"><i class="material-icons left">settings</i>Settings</a></li>
                <li><a href="logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="admin.php"><i class="material-icons left">admin_panel_settings</i>Admin Panel</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h3 class="center">Welcome, <?php echo htmlspecialchars($username); ?>!</h3>
            <p class="center">Premium User: <strong><?php echo $is_premium; ?></strong></p>
        </div>

        <div class="section">
            <h5 class="center">Your Novels</h5>
            <?php if (empty($novels)): ?>
                <div class="card-panel yellow lighten-4 center">You haven’t uploaded any novels yet.</div>
            <?php else: ?>
                <ul class="collection">
                    <?php foreach ($novels as $novel): ?>
                        <li class="collection-item avatar">
                            <i class="material-icons circle blue">book</i>
                            <span class="title"><?php echo htmlspecialchars($novel['title']); ?></span>
                            <p>Type: <?php echo htmlspecialchars($novel['type']); ?><br>
                               Created on: <?php echo htmlspecialchars($novel['created_at']); ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="section center">
               <a href="mystories.php" class="btn green">
                  <i class="material-icons left">library_books</i> View My Stories
               </a>
            </div>
        </div>

        <!-- Centered Novel Upload Section -->
        <div class="section">
            <h5 class="center">Upload a Novel</h5>
            <div class="card-panel center-card">
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="input-field col s12">
                            <input id="title" name="title" type="text" required>
                            <label for="title">Title</label>
                        </div>

                        <div class="input-field col s12">
                            <select id="type-select" name="type" required>
                                <option value="" disabled selected>Choose type</option>
                                <option value="short">Short Story</option>
                                <option value="full">Full-Length Novel</option>
                            </select>
                            <label for="type-select">Type</label>
                        </div>

                        <div class="input-field col s12" id="content-container">
                            <textarea id="content" name="content" class="materialize-textarea"></textarea>
                            <label for="content">Content (for short stories)</label>
                        </div>

                        <div class="file-field input-field col s12" id="file-upload-container" style="display: none;">
                            <div class="btn blue">
                                <span>Upload PDF</span>
                                <input type="file" name="file">
                            </div>
                            <div class="file-path-wrapper">
                                <input class="file-path validate" type="text" placeholder="Upload a PDF (for full-length novels)">
                            </div>
                        </div>
                        <?php if ($is_premium): ?>
                        <div class="col s12 center">
                            <label>
                                <input type="checkbox" name="is_premium" class="filled-in">
                                <span>Premium Novel</span>
                            </label>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col s12 center">
                            <input type="hidden" name="token_csrf" value= "<?php echo getToken();?>">
                            <button type="submit" class="btn blue" style="margin-top: 20px;">Upload</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <footer class="page-footer blue">
        <div class="container">
            <p class="center white-text">&copy; 2025 Novelists</p>
        </div>
    </footer>

    <!-- Include Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var elems = document.querySelectorAll("select");
            M.FormSelect.init(elems);

            // Toggle between textarea and file upload based on type selection
            const typeSelect = document.getElementById("type-select");
            const contentContainer = document.getElementById("content-container");
            const fileUploadContainer = document.getElementById("file-upload-container");

            typeSelect.addEventListener("change", function() {
                if (typeSelect.value === "short") {
                    contentContainer.style.display = "block";
                    fileUploadContainer.style.display = "none";
                } else if (typeSelect.value === "full") {
                    contentContainer.style.display = "none";
                    fileUploadContainer.style.display = "block";
                }
            });
        });
    </script>
</body>
</html>
