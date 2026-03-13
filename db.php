<?php
$servername = "localhost";
$username   = "root";
$password   = "";   // MUST be empty in XAMPP
$database   = "students_feedback_db";
$port       = 3306; // or 3307 if you changed it

$conn = mysqli_connect($servername, $username, $password, $database, $port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}
?>
