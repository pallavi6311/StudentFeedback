<?php
include 'db.php';

// The password everyone will get
$new_password = '12345';

// 1. Try modern Hashing (BCRYPT) - Most likely what your system uses
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Update ALL users to have this new hashed password
$sql = "UPDATE users SET password = '$hashed_password'";

if(mysqli_query($conn, $sql)) {
    echo "<h1>Success!</h1>";
    echo "<p>All passwords have been reset to: <strong>12345</strong></p>";
    echo "<p>Try logging in as <strong>admin.abdullah@coligo.edu.pk</strong> now.</p>";
    echo "<p>Or log in as <strong>ali.khan@student.edu.pk</strong></p>";
} else {
    echo "Error updating passwords: " . mysqli_error($conn);
}
?>