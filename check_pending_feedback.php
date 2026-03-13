<?php
// Include this at the top of dashboard_student.php to show pending feedback alerts
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    exit();
}

$student_id = (int)$_SESSION['user_id'];

// Get user department
$user_query = mysqli_query($conn, "SELECT department FROM users WHERE id = $student_id");
$user = mysqli_fetch_assoc($user_query);
$dept = $user['department'] ?? '';

// Count pending feedback
$pending_query = "
    SELECT COUNT(*) as pending_count
    FROM courses c
    WHERE c.department = '" . mysqli_real_escape_string($conn, $dept) . "'
    AND NOT EXISTS (
        SELECT 1 FROM feedback_responses fr 
        WHERE fr.student_id = $student_id 
        AND fr.course_id = c.id
    )
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_row = mysqli_fetch_assoc($pending_result);
$pending_count = $pending_row['pending_count'] ?? 0;

// Return the count
return $pending_count;
?>
