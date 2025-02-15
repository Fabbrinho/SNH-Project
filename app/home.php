<?php
require_once 'config.php'; // Connessione al database

session_start(); // Start session securely
$inactive = 300; // 5 minutes

// Check if the session has timed out
if (isset($_SESSION['timeout']) && (time() - $_SESSION['timeout'] > $inactive)) {
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session
    header("Location: index.php"); // Redirect to login page
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_premium = $_SESSION['is_premium'];

// Fetch user's novels
$stmt = $conn->prepare('SELECT n.id, n.title, n.type, n.created_at, n.is_premium, u.username
                    FROM Novels AS n 
                    JOIN Users AS u ON n.author_id = u.id
                    ORDER BY n.created_at DESC');
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
    <title>Homepage</title>
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
            <a href="#" class="brand-logo center">Homepage</a>
            <ul id="nav-mobile" class="right">
            <li><a href="dashboard.php"><i class="material-icons left">person</i>Profile</a></li>
            <li><a href="logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h3 class="center">Read the newest novels!</h3>
        </div>

        <div class="section">
            <?php if (empty($novels)): ?>
                <div class="card-panel yellow lighten-4 center">There are no uploaded novels yet</div>
            <?php else: ?>
                <ul class="collection">
                    <?php foreach ($novels as $novel): ?>
                        <a href="novels.php?id=<?php echo htmlspecialchars((string) $novel['id']); ?>"
                        <?php if ($novel['is_premium'] && !$is_premium) : ?>
                            class="disabled-link" 
                            onclick="alert('You are not a premium user!'); return false;" 
                            <?php endif; ?>>
                        <li  class="collection-item avatar">
                            <i class="material-icons circle blue">book</i>
                            <span class="title"><?php echo htmlspecialchars($novel['title']); ?></span>
                            <p>Type: <?php echo htmlspecialchars($novel['type']); ?><br>
                               Created on: <?php echo htmlspecialchars($novel['created_at']); ?><br>
                               By: <?php echo htmlspecialchars($novel['username']); ?>
                            </p>

                            <?php if ($novel['is_premium']) : ?>
                                <i class="material-icons secondary-content" title="Premium">star</i>
                            <?php endif; ?>

                        </li>
                            </a>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            
        

    <footer class="page-footer blue">
        <div class="container">
            <p class="center white-text">&copy; 2025 Novelists</p>
        </div>
    </footer>

    <!-- Include Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
    <script>
        
    </script>
</body>
</html>
