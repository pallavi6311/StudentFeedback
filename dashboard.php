<?php
include 'db.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
}
?>

<h2>Welcome Student</h2>

<a href="feedback.php">Give Feedback</a><br><br>
<a href="login.php">Logout</a>