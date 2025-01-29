<?php
session_start();

// Verifica se l'utente è loggato e se è un admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
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

// Se viene inviata una richiesta POST per cambiare il privilegio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $new_status = isset($_POST['is_premium']) ? 1 : 0;

    $stmt = $conn->prepare('UPDATE Users SET is_premium = ? WHERE id = ?');
    $stmt->bind_param('ii', $new_status, $user_id);

    if ($stmt->execute()) {
        $_SESSION['success_message'] = 'User privilege updated successfully!';
    } else {
        $_SESSION['error_message'] = 'Failed to update user privilege!';
    }

    $stmt->close();
    header('Location: admin.php');
    exit();
}

// Recupera tutti gli utenti tranne gli admin
$stmt = $conn->prepare("SELECT id, username, email, is_premium FROM Users WHERE role = 'user'");
$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/materialize/1.0.0/css/materialize.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</head>
<body class="grey lighten-4">

    <nav class="blue">
        <div class="nav-wrapper">
            <a href="#" class="brand-logo center">Admin Panel</a>
            <ul id="nav-mobile" class="right">
                <li><a href="dashboard.php"><i class="material-icons left">arrow_back</i>Back</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="section">
            <h3 class="center">User Management</h3>
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="card-panel green lighten-4"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="card-panel red lighten-4"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
            <?php endif; ?>

            <ul class="collection">
                <?php foreach ($users as $user): ?>
                    <li class="collection-item">
                        <span class="title"><strong><?php echo htmlspecialchars($user['username']); ?></strong></span>
                        <p>Email: <?php echo htmlspecialchars($user['email']); ?><br>
                           Premium Status: <?php echo $user['is_premium'] ? 'Yes' : 'No'; ?>
                        </p>
                        <form action="admin.php" method="POST">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <label>
                                <input type="checkbox" name="is_premium" value="1" <?php echo $user['is_premium'] ? 'checked' : ''; ?> onchange="this.form.submit()">
                                <span>Make Premium</span>
                            </label>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
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
