<?php
// Automated Feedback Reminder System
// Run this script daily via cron job or task scheduler

include 'db.php';

// Get all students who haven't submitted feedback for their courses
$query = "
    SELECT DISTINCT 
        u.id, u.name, u.email, u.department,
        c.course_name, c.course_code, c.semester
    FROM users u
    CROSS JOIN courses c
    WHERE u.role = 'student' 
        AND u.department = c.department
        AND NOT EXISTS (
            SELECT 1 FROM feedback_responses fr 
            WHERE fr.student_id = u.id 
            AND fr.course_id = c.id
            AND fr.session = 'Fall 2025'
        )
    ORDER BY u.email, c.semester
";

$result = mysqli_query($conn, $query);

$reminders_sent = 0;
$current_email = '';
$pending_courses = [];

while ($row = mysqli_fetch_assoc($result)) {
    if ($current_email != $row['email']) {
        // Send email for previous student
        if ($current_email != '' && count($pending_courses) > 0) {
            send_reminder_email($current_email, $pending_courses[0]['name'], $pending_courses);
            $reminders_sent++;
        }
        
        // Reset for new student
        $current_email = $row['email'];
        $pending_courses = [];
    }
    
    $pending_courses[] = $row;
}

// Send last email
if ($current_email != '' && count($pending_courses) > 0) {
    send_reminder_email($current_email, $pending_courses[0]['name'], $pending_courses);
    $reminders_sent++;
}

echo "✅ Sent $reminders_sent reminder emails\n";

function send_reminder_email($email, $name, $courses) {
    global $conn;
    
    $subject = "⚠️ URGENT: Pending Course Feedback Required";
    
    $course_list = "";
    foreach ($courses as $course) {
        $course_list .= "- {$course['course_name']} ({$course['course_code']}) - Semester {$course['semester']}\n";
    }
    
    $message = "Dear $name,\n\n";
    $message .= "This is a MANDATORY reminder that you have NOT submitted feedback for the following courses:\n\n";
    $message .= $course_list;
    $message .= "\n⚠️ IMPORTANT: Submitting feedback is COMPULSORY for all students.\n";
    $message .= "Please login immediately and complete your feedback:\n";
    $message .= "http://localhost:8000/login_student.php\n\n";
    $message .= "Failure to submit feedback may affect your academic records.\n\n";
    $message .= "Thank you,\n";
    $message .= "Student Feedback System";
    
    // Email headers
    $headers = "From: Student Feedback System <noreply@studentfeedback.edu>\r\n";
    $headers .= "Reply-To: admin@studentfeedback.edu\r\n";
    $headers .= "X-Priority: 1\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Try to send email
    $email_sent = mail($email, $subject, $message, $headers);
    
    // Always log to file for tracking
    $log_file = "email_logs.txt";
    $log_content = "\n\n=== EMAIL " . ($email_sent ? "SENT" : "FAILED") . " ===\n";
    $log_content .= "To: $email\n";
    $log_content .= "Subject: $subject\n";
    $log_content .= "Message:\n$message\n";
    $log_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
    $log_content .= "Status: " . ($email_sent ? "SUCCESS" : "FAILED - Check PHP mail configuration") . "\n";
    file_put_contents($log_file, $log_content, FILE_APPEND);
    
    // Log the reminder in database
    $email_safe = mysqli_real_escape_string($conn, $email);
    $log_query = "INSERT INTO feedback_reminders (student_email, courses_pending, sent_at) 
                  VALUES ('$email_safe', " . count($courses) . ", NOW())";
    mysqli_query($conn, $log_query);
    
    if ($email_sent) {
        echo "✅ Email sent successfully to: $email (" . count($courses) . " pending courses)<br>";
    } else {
        echo "⚠️ Email logged for: $email (Check email_logs.txt and configure PHP mail)<br>";
    }
}
?>
