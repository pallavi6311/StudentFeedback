<?php
include 'db.php';

echo "<h1>Database Connection Check</h1>";

// 1. Show which database is connected
$result = mysqli_query($conn, "SELECT DATABASE()");
$row = mysqli_fetch_row($result);
echo "<h3>Connected Database Name: <span style='color:red'>" . $row[0] . "</span></h3>";

// 2. Compare with what you see in PhpMyAdmin
echo "<p><em>(In your screenshot, you are looking at 'students_feedback_db'. Does the name above match?)</em></p>";

// 3. Show what PHP actually sees in the 'users' table
echo "<h3>Users found by PHP in this database:</h3>";
$q = "SELECT username, password, user_type FROM users WHERE user_type='admin'";
$res = mysqli_query($conn, $q);

if (mysqli_num_rows($res) > 0) {
    echo "<table border='1' cellpadding='5'><tr><th>Username</th><th>Password</th></tr>";
    while ($user = mysqli_fetch_assoc($res)) {
        echo "<tr>";
        echo "<td>" . $user['username'] . "</td>";
        echo "<td>" . $user['password'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<h2 style='color:red'>PHP SEES 0 ADMINS!</h2>";
    echo "<p>This proves your website is looking at an empty database.</p>";
}
?>