<?php
// Check for pending mandatory feedback
// Include this at the top of dashboard_student.php

if (!isset($_SESSION['user_id'])) {
    return;
}

$student_id = (int)$_SESSION['user_id'];

// Get user department
$user_query = mysqli_query($conn, "SELECT department FROM users WHERE id = $student_id");
$user = mysqli_fetch_assoc($user_query);
$dept = mysqli_real_escape_string($conn, $user['department'] ?? '');

// Get pending courses (courses without feedback)
$pending_query = "
    SELECT c.id, c.course_name, c.course_code, c.semester
    FROM courses c
    WHERE c.department = '$dept'
    AND NOT EXISTS (
        SELECT 1 FROM feedback_responses fr 
        WHERE fr.student_id = $student_id 
        AND fr.course_id = c.id
    )
    ORDER BY c.semester, c.course_name
";

$pending_result = mysqli_query($conn, $pending_query);
$pending_courses = [];
while ($row = mysqli_fetch_assoc($pending_result)) {
    $pending_courses[] = $row;
}

$pending_count = count($pending_courses);

// Store in session for easy access
$_SESSION['pending_feedback_count'] = $pending_count;
$_SESSION['pending_courses'] = $pending_courses;

return $pending_count;
?>
