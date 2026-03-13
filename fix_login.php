<?php
include 'db.php';

// Set passwords to PLAIN TEXT '12345' (No encryption)
$sql = "UPDATE users SET password = '12345'";

if(mysqli_query($conn, $sql)) {
    echo "<h1>Login Fixed!</h1>";
    echo "<p>All passwords are now exactly: <strong>12345</strong></p>";
    echo "<h3>Admin Login Details:</h3>";
    echo "<ul>";
    echo "<li>Username: <strong>admin.abdullah</strong></li>";
    echo "<li>Password: <strong>12345</strong></li>";
    echo "</ul>";
    echo "<p><em>Note: Do not use just 'admin', use the full username 'admin.abdullah'</em></p>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>