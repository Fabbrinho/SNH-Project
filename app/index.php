<?php
$servername = "mysql-container";
$username = "a";
$password = "a";
$dbname = "novelists_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully to the database.<br>";

// Example query
$sql = "SELECT * FROM Users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Users:";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . $row['username'] . " (" . $row['email'] . ")</li>";
    }
    echo "</ul>";
} else {
    echo "No users found.";
}

$conn->close();
?>
