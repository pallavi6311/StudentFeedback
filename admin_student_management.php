<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

$success_message = "";
$error_message = "";

// Send email to specific student
if (isset($_POST['send_email'])) {
    $student_id = (int)$_POST['student_id'];
    
    // Get student info
    $student_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $student_id");
    $student = mysqli_fetch_assoc($student_query);
    
    // Get pending courses
    $pending_query = "
        SELECT c.course_name, c.course_code 
        FROM courses c
        WHERE c.department = '" . mysqli_real_escape_string($conn, $student['department']) . "'
        AND NOT EXISTS (
            SELECT 1 FROM feedback_responses fr 
            WHERE fr.student_id = $student_id AND fr.course_id = c.id
        )
    ";
    $pending_result = mysqli_query($conn, $pending_query);
    
    $course_list = "";
    while($course = mysqli_fetch_assoc($pending_result)) {
        $course_list .= "- {$course['course_name']} ({$course['course_code']})\n";
    }
    
    // Send email
    $to = $student['email'];
    $subject = "⚠️ URGENT: Submit Your Course Feedback";
    $message = "Dear {$student['name']},\n\n";
    $message .= "You have NOT submitted feedback for the following courses:\n\n";
    $message .= $course_list;
    $message .= "\nPlease login and submit your feedback immediately:\n";
    $message .= "http://localhost:8000/login_student.php\n\n";
    $message .= "This is MANDATORY.\n\nThank you,\nAdmin";
    
    // Log email (for testing)
    $log_content = "\n=== EMAIL SENT ===\n";
    $log_content .= "To: $to\nSubject: $subject\nMessage:\n$message\n";
    $log_content .= "Time: " . date('Y-m-d H:i:s') . "\n";
    file_put_contents("email_logs.txt", $log_content, FILE_APPEND);
    
    $success_message = "Email sent to {$student['name']}!";
}

// Get all students with feedback count
$students_query = "
    SELECT u.id, u.name, u.email, u.department,
           COUNT(fr.response_id) as feedback_count
    FROM users u
    LEFT JOIN feedback_responses fr ON u.id = fr.student_id
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY feedback_count ASC, u.name ASC
";
$students_result = mysqli_query($conn, $students_query);

// Get stats
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_feedback = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses"))['count'];
$avg_rating = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM feedback_responses"))['avg'] ?? 0;
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: linear-gradient(180deg, #4e54c8 0%, #8f94fb 100%);
            padding: 20px;
            color: white;
        }
        .sidebar h2 {
            margin-bottom: 30px;
            font-size: 1.5rem;
        }
        .sidebar a {
            display: block;
            color: white;
            text-decoration: none;
            padding: 12px 15px;
            margin-bottom: 5px;
            border-radius: 8px;
            transition: background 0.3s;
        }
        .sidebar a:hover {
            background: rgba(255,255,255,0.2);
        }
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        .header {
            background: white;
            padding: 20px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .header h1 {
            color: #333;
            font-size: 1.8rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #4e54c8;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .card h2 {
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e0e0e0;
        }
        td {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .btn {
            padding: 8px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        .btn-email {
            background: #4e54c8;
            color: white;
        }
        .btn-email:hover {
            background: #3d43a8;
            transform: translateY(-2px);
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        .badge-danger {
            background: #fee2e2;
            color: #dc2626;
        }
        .badge-warning {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-success {
            background: #dcfce7;
            color: #166534;
        }
        .success-msg {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #86efac;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h2>🎓 Admin</h2>
        <a href="dashboard_admin.php">📊 Dashboard</a>
        <a href="admin_student_management.php">👥 Students</a>
        <a href="admin_manage_questions.php">📝 Manage Questions</a>
        <a href="send_feedback_reminders.php">📧 Send Reminders</a>
        <a href="logout.php">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Student Management</h1>
            <p style="color: #666;">Welcome back, Administrator</p>
        </div>

        <?php if(!empty($success_message)): ?>
            <div class="success-msg">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-number"><?php echo $total_students; ?></div>
                <div class="stat-label">TOTAL STUDENTS</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💬</div>
                <div class="stat-number"><?php echo $total_feedback; ?></div>
                <div class="stat-label">TOTAL FEEDBACK</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⭐</div>
                <div class="stat-number"><?php echo number_format($avg_rating, 1); ?>/5</div>
                <div class="stat-label">AVG RATING</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-number"><?php echo $total_courses; ?></div>
                <div class="stat-label">ACTIVE COURSES</div>
            </div>
        </div>

        <div class="card">
            <h2>Registered Students</h2>
            <table>
                <thead>
                    <tr>
                        <th>NAME</th>
                        <th>EMAIL</th>
                        <th>DEPARTMENT</th>
                        <th>FEEDBACK GIVEN</th>
                        <th>ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($student = mysqli_fetch_assoc($students_result)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                        <td><?php echo htmlspecialchars($student['department']); ?></td>
                        <td>
                            <?php if($student['feedback_count'] == 0): ?>
                                <span class="badge badge-danger">0 - No Feedback</span>
                            <?php elseif($student['feedback_count'] < 3): ?>
                                <span class="badge badge-warning"><?php echo $student['feedback_count']; ?> - Incomplete</span>
                            <?php else: ?>
                                <span class="badge badge-success"><?php echo $student['feedback_count']; ?> - Complete</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                <button type="submit" name="send_email" class="btn btn-email">
                                    📧 Email
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
