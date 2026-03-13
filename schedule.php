<?php
// schedule.php
include 'db.php'; // Include DB connection
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. GET USER & SEMESTER INFO
if (!isset($_SESSION['user_id'])) { header("Location: login_student.php"); exit(); }
$student_id = (int)$_SESSION['user_id'];

// Get the semester from session (set in dashboard), default to 1
$selected_semester = isset($_SESSION['selected_semester']) ? (int)$_SESSION['selected_semester'] : 1;

// Get Department
$user_q = mysqli_query($conn, "SELECT department FROM users WHERE id = $student_id");
$user_r = mysqli_fetch_assoc($user_q);
$dept = mysqli_real_escape_string($conn, $user_r['department'] ?? '');

// 2. FETCH REAL COURSES FOR THIS SEMESTER
$course_query = "SELECT * FROM courses WHERE semester = $selected_semester AND department = '$dept'";
$course_result = mysqli_query($conn, $course_query);

$my_courses = [];
while($row = mysqli_fetch_assoc($course_result)) {
    $my_courses[] = $row;
}

// Helper function to get a course for a specific slot
function getCourseForSlot($courses, $index) {
    if (isset($courses[$index])) {
        // Cycle colors based on index to make it look nice
        $colors = [
            ['bg' => '#eff6ff', 'border' => '#2563eb'], // Blue
            ['bg' => '#fdf2f8', 'border' => '#ec4899'], // Pink
            ['bg' => '#f5f3ff', 'border' => '#8b5cf6'], // Purple
            ['bg' => '#ecfdf5', 'border' => '#10b981'], // Green
        ];
        $theme = $colors[$index % 4];
        
        return '
        <div class="class-block" style="background: '.$theme['bg'].'; border-left: 4px solid '.$theme['border'].';">
            <span class="class-name">'.htmlspecialchars($courses[$index]['course_name']).'</span>
            <span class="class-time">'.htmlspecialchars($courses[$index]['teacher']).'</span>
        </div>';
    }
    return ''; // Return empty if no course exists for this slot
}

// --- DATE SIMULATION LOGIC ---
$simulated_today_str = "2025-12-22"; 
$simulated_today = strtotime($simulated_today_str);
$feedback_deadline = strtotime("2025-12-25");
$days_left = floor(($feedback_deadline - $simulated_today) / (60 * 60 * 24));

// Alert Box Logic
$box_color = "linear-gradient(135deg, #f59e0b, #d97706)";
$msg_title = "Action Required";
if ($days_left < 0) {
    $days_text = "Closed"; $msg_text = "Submission Closed";
    $box_color = "linear-gradient(135deg, #ef4444, #b91c1c)";
} elseif ($days_left == 0) {
    $days_text = "Today"; $msg_text = "Closes Today";
    $box_color = "linear-gradient(135deg, #ef4444, #b91c1c)";
} else {
    $days_text = $days_left . " Days"; $msg_text = "Closes in <strong>$days_text</strong>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Schedule - Coligo</title>
    <!-- CSS Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        /* CSS STYLES - This makes it look good! */
        * { box-sizing: border-box; }
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background: #f0f2f5; 
            margin: 0; 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
            color: #334155;
        }

        /* Sidebar Styles */
        .sidebar { 
            width: 270px; 
            background: #1e3a8a; /* Fallback color */
            background-image: linear-gradient(180deg, #2563eb 0%, #1e40af 100%); 
            color: white; 
            display: flex; 
            flex-direction: column; 
            padding: 25px; 
            flex-shrink: 0; 
        }
        .brand { font-size: 1.6rem; font-weight: 700; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; }
        .menu { list-style: none; padding: 0; margin: 0; }
        .menu li { margin-bottom: 12px; }
        .menu a { text-decoration: none; color: rgba(255,255,255,0.7); font-weight: 500; display: flex; align-items: center; gap: 15px; padding: 14px 18px; border-radius: 14px; transition: all 0.3s ease; }
        .menu a:hover { background: rgba(255, 255, 255, 0.15); color: white; }
        .menu a.active { background: white; color: #2563eb; font-weight: 700; }

        /* Main Content Styles */
        .main-content { flex-grow: 1; overflow-y: auto; padding: 40px; }
        
        .layout-grid { display: grid; grid-template-columns: 2.5fr 1fr; gap: 30px; }
        .card { background: white; border-radius: 20px; padding: 25px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); margin-bottom: 25px; }
        .card-title { font-size: 1.2rem; font-weight: 700; color: #1e293b; margin: 0 0 20px 0; display: flex; justify-content: space-between; align-items: center; }
        
        /* Calendar Styles */
        .cal-table { width: 100%; border-collapse: collapse; }
        .cal-table th { text-align: left; padding: 15px; color: #94a3b8; font-size: 0.85rem; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
        .cal-table td { padding: 20px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: top; width: 30%; }
        
        /* Dynamic Class Block Styles */
        .class-block { padding: 10px 15px; border-radius: 6px; margin-bottom: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .class-name { font-weight: 600; color: #1e293b; display: block; font-size: 0.95rem; margin-bottom: 4px; }
        .class-time { font-size: 0.8rem; color: #64748b; font-weight: 500; }

        /* Alert Box Styles */
        .alert-box { background: <?php echo $box_color; ?>; color: white; padding: 20px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .deadline-item { display: flex; gap: 15px; margin-bottom: 20px; align-items: center; }
        .d-date { background: #fee2e2; color: #991b1b; padding: 8px 12px; border-radius: 10px; text-align: center; min-width: 60px; height: fit-content; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> Coligo-LMS</div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="schedule.php" class="active"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="feedback.php"><i class="fas fa-edit"></i> Feedback</a></li>
            <li style="margin-top: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <main class="main-content">
        <h1 style="margin: 0 0 5px 0; color: #1e293b;">Weekly Schedule</h1>
        <p style="margin: 0 0 30px 0; color: #64748b;">
            Semester <?php echo $selected_semester; ?> • Simulated Date: <strong><?php echo date("M j, Y", $simulated_today); ?></strong>
        </p>
        
        <div class="layout-grid">
            <!-- CALENDAR -->
            <div class="card">
                <div class="card-title">Weekly Classes</div>
                
                <?php if(empty($my_courses)): ?>
                    <div style="color:#64748b; text-align:center; padding:40px;">
                        <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px; opacity: 0.5;"></i>
                        <p>No courses found for Semester <?php echo $selected_semester; ?>.</p>
                    </div>
                <?php else: ?>
                    <table class="cal-table">
                        <thead>
                            <tr><th>Time</th><th>Monday</th><th>Wednesday</th><th>Friday</th></tr>
                        </thead>
                        <tbody>
                            <!-- ROW 1: 09:00 AM -->
                            <tr>
                                <td style="font-weight:600; color:#64748b;">09:00 AM</td>
                                <td><?php echo getCourseForSlot($my_courses, 0); ?></td>
                                <td><?php echo getCourseForSlot($my_courses, 1); ?></td>
                                <td><!-- Free Slot --></td>
                            </tr>
                            <!-- ROW 2: 11:00 AM -->
                            <tr>
                                <td style="font-weight:600; color:#64748b;">11:00 AM</td>
                                <td><!-- Free Slot --></td>
                                <td><?php echo getCourseForSlot($my_courses, 2); ?></td>
                                <td><?php echo getCourseForSlot($my_courses, 2); // Repeat course for demo ?></td>
                            </tr>
                            <!-- ROW 3: 02:00 PM -->
                            <tr>
                                <td style="font-weight:600; color:#64748b;">02:00 PM</td>
                                <td><?php echo getCourseForSlot($my_courses, 3); ?></td>
                                <td><!-- Free Slot --></td>
                                <td><?php echo getCourseForSlot($my_courses, 4); ?></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- DEADLINES -->
            <div>
                <div class="alert-box">
                    <h3 style="margin:0 0 5px 0;"><i class="fas fa-exclamation-triangle"></i> <?php echo $msg_title; ?></h3>
                    <p style="margin:0; opacity:0.9; font-size:0.9rem;"><?php echo $msg_text; ?></p>
                </div>

                <div class="card">
                    <div class="card-title">Important Dates</div>
                    <div class="deadline-item">
                        <div class="d-date">
                            <span style="font-size:1.2rem; display:block;">25</span>
                            <span style="font-size:0.7rem; text-transform:uppercase;">Dec</span>
                        </div>
                        <div>
                            <h4 style="margin:0; color:#1e293b;">Feedback Deadline</h4>
                            <p style="margin:5px 0 0; color:#64748b; font-size:0.85rem;">All subjects</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>