<?php
// Database Verification Script
include 'db.php';

echo "<h2>🔍 Database Verification</h2>";

// Check tables exist
$tables = ['users', 'courses', 'feedback', 'feedback_questions', 'feedback_responses', 'student_progress', 'schedule', 'feedback_reminders'];

echo "<h3>Tables Check:</h3>";
foreach ($tables as $table) {
    $result = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
    if (mysqli_num_rows($result) > 0) {
        echo "✅ $table exists<br>";
    } else {
        echo "❌ $table MISSING<br>";
    }
}

// Check required columns
echo "<h3>Column Check:</h3>";

$column_checks = [
    'users' => ['id', 'name', 'email', 'password', 'role', 'department'],
    'courses' => ['id', 'course_name', 'course_code', 'instructor', 'department', 'semester', 'session'],
    'feedback_questions' => ['question_id', 'question', 'category'],
    'feedback_responses' => ['response_id', 'student_id', 'course_id', 'question_id', 'rating', 'text_response', 'sentiment', 'session', 'semester']
];

foreach ($column_checks as $table => $columns) {
    $result = mysqli_query($conn, "DESCRIBE $table");
    $existing_columns = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $existing_columns[] = $row['Field'];
    }
    
    echo "<strong>$table:</strong><br>";
    foreach ($columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "✅ $col<br>";
        } else {
            echo "❌ $col MISSING<br>";
        }
    }
    echo "<br>";
}

// Check data
echo "<h3>Data Check:</h3>";
$user_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users"))['cnt'];
$course_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM courses"))['cnt'];
$question_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as cnt FROM feedback_questions"))['cnt'];

echo "Users: $user_count<br>";
echo "Courses: $course_count<br>";
echo "Questions: $question_count<br>";

if ($user_count > 0 && $course_count > 0 && $question_count > 0) {
    echo "<br><h2 style='color: green;'>✅ Database is ready!</h2>";
    echo "<p><a href='register_student.php'>Register as Student</a> | <a href='login_admin.php'>Admin Login</a></p>";
} else {
    echo "<br><h2 style='color: red;'>❌ Database needs setup!</h2>";
    echo "<p>Run fresh_setup.sql in phpMyAdmin</p>";
}
?>
