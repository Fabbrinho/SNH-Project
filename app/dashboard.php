<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not authenticated
    header('Location: login.php');
    exit();
}

$host = 'mysql-container';
$username = 'a';
$password = 'a';
$dbname = 'novelists_db';

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die('Connection failed: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_premium = $_SESSION['is_premium'] ? 'Yes' : 'No';

// Fetch user's novels
$stmt = $conn->prepare('SELECT title, type, created_at FROM Novels WHERE author_id = ?');
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
    <title>User Dashboard</title>
    <!-- Include Materialize CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="grey lighten-4">

    <nav class="blue">
        <div class="nav-wrapper">
            <a href="#" class="brand-logo center">Dashboard</a>
            <ul id="nav-mobile" class="right">
                <li><a href="logout.php"><i class="material-icons left">exit_to_app</i>Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h3 class="center">Welcome, <?php echo htmlspecialchars($username); ?>!</h3>
            <p class="center">Premium User: <strong><?php echo $is_premium; ?></strong></p>
        </div>

        <div class="section">
            <h5>Your Novels:</h5>
            <?php if (empty($novels)): ?>
                <div class="card-panel yellow lighten-4">You haven’t uploaded any novels yet.</div>
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
        </div>
    </div>

    <footer class="page-footer blue">
        <div class="container">
            <p class="center white-text">&copy; 2025 Novelists</p>
        </div>
    </footer>

    <!-- Include Materialize JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/js/materialize.min.js"></script>
</body>
</html>

