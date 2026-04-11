<<<<<<< HEAD
<?php
echo "Welcome To PHP APP<br>";

// MySQL connection settings (Docker service name is correct)
$servername = "mysql";
$username = "root";
$password = "rootpassword";
$dbname = "app_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "Connected successfully to the database<br>";

// Query the database
$sql = "SELECT DATABASE()";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<br>Current database: " . $row["DATABASE()"];
    }
} else {
    echo "<br>No database selected.";
}

$conn->close();
?>
=======
<?php echo '🚀 StegaVault is working!'; ?>
>>>>>>> f02258f (Initial commit - PHP project setup on EC2)
