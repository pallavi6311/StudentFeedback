<?php
include 'db.php';

// Force ALL passwords to be simple text "12345"
$sql = "UPDATE users SET password = '12345'";

if(mysqli_query($conn, $sql)) {
    echo "<h1>FIXED!</h1>";
    echo "<p>Passwords have been changed from encrypted codes back to simple: <strong>12345</strong></p>";
    echo "<h3>Now Login with:</h3>";
    echo "<ul>";
    echo "<li>Username: <strong>admin</strong></li>";
    echo "<li>Password: <strong>12345</strong></li>";
    echo "</ul>";
} else {
    echo "Error: " . mysqli_error($conn);
}
?>