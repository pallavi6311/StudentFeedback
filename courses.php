<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'student') {
    header("Location: login_student.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $student_id");
$user = mysqli_fetch_assoc($user_query) ?: [];
$dept = mysqli_real_escape_string($conn, $user['department'] ?? '');

// --- FIX START: Check Session if URL param is missing ---
if (isset($_GET['semester'])) {
    // If user manually switched via dropdown on this page
    $selected_semester = (int)$_GET['semester'];
    $_SESSION['selected_semester'] = $selected_semester; 
} elseif (isset($_SESSION['selected_semester'])) {
    // If user came from Dashboard
    $selected_semester = (int)$_SESSION['selected_semester'];
} else {
    // Fallback
    $selected_semester = 1; 
}
// --- FIX END ---

// Fetch Courses
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM feedback_responses fr WHERE fr.course_id = c.id AND fr.student_id = $student_id) as is_submitted
          FROM courses c 
          WHERE c.semester = $selected_semester AND c.department = '$dept'";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Courses - Coligo</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* --- REUSING DASHBOARD STYLES --- */
        :root {
            --main-bg: #f0f2f5;
            --sidebar-gradient: linear-gradient(180deg, #2563eb 0%, #1e40af 100%);
            --accent-color: #3b82f6;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--main-bg); margin: 0; display: flex; height: 100vh; overflow: hidden; }
        
        /* Sidebar */
        .sidebar { width: 270px; background-image: var(--sidebar-gradient); color: white; display: flex; flex-direction: column; padding: 25px; flex-shrink: 0; }
        .brand { font-size: 1.6rem; font-weight: 700; margin-bottom: 50px; display: flex; align-items: center; gap: 12px; }
        .menu { list-style: none; padding: 0; margin: 0; }
        .menu li { margin-bottom: 12px; }
        .menu a { text-decoration: none; color: rgba(255,255,255,0.7); font-weight: 500; display: flex; align-items: center; gap: 15px; padding: 14px 18px; border-radius: 14px; transition: all 0.3s ease; }
        .menu a:hover { background: rgba(255, 255, 255, 0.15); color: white; }
        .menu a.active { background: white; color: #2563eb; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

        /* Content */
        .main-content { flex-grow: 1; overflow-y: auto; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
        .page-title { font-size: 1.8rem; font-weight: 700; color: #1e293b; margin: 0; }
        
        /* Course Grid */
        .course-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; }
        .course-card { background: white; border-radius: 20px; padding: 25px; box-shadow: var(--card-shadow); transition: transform 0.2s; position: relative; overflow: hidden; border: 1px solid #e2e8f0; }
        .course-card:hover { transform: translateY(-5px); }
        
        .status-badge { position: absolute; top: 20px; right: 20px; padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .status-pending { background: #fee2e2; color: #991b1b; } /* Red */
        .status-done { background: #dcfce7; color: #166534; } /* Green */

        .c-icon { width: 50px; height: 50px; background: #eff6ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #2563eb; font-size: 1.5rem; margin-bottom: 15px; }
        .c-title { font-size: 1.1rem; font-weight: 700; color: #1e293b; margin: 0 0 5px 0; }
        .c-teacher { color: #64748b; font-size: 0.9rem; margin-bottom: 20px; display: block; }
        
        .btn-course { display: block; width: 100%; padding: 12px; border-radius: 10px; text-align: center; text-decoration: none; font-weight: 600; transition: 0.2s; }
        .btn-eval { background: #2563eb; color: white; }
        .btn-eval:hover { background: #1d4ed8; }
        .btn-view { background: #f1f5f9; color: #475569; }
        .btn-view:hover { background: #e2e8f0; }
    </style>
</head>
<body>
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="brand"><i class="fas fa-graduation-cap"></i> Coligo-LMS</div>
        <ul class="menu">
            <li><a href="dashboard_student.php"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="courses.php" class="active"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="feedback.php"><i class="fas fa-edit"></i> Feedback</a></li>
            <li style="margin-top: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- MAIN -->
    <main class="main-content">
        <div class="header">
            <div>
                <h1 class="page-title">My Courses</h1>
                <p style="color:#64748b; margin:5px 0 0;">Semester <?php echo $selected_semester; ?> • <?php echo $dept; ?></p>
            </div>
            
            <!-- Simple Semester Switcher -->
            <form method="GET">
                <select name="semester" onchange="this.form.submit()" style="padding: 10px; border-radius: 10px; border: 1px solid #cbd5e1;">
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($i == $selected_semester) ? 'selected' : ''; ?>>Semester <?php echo $i; ?></option>
                    <?php endfor; ?>
                </select>
            </form>
        </div>

        <div class="course-grid">
            <?php 
            if (mysqli_num_rows($result) > 0) {
                while($row = mysqli_fetch_assoc($result)): 
                    $is_done = ($row['is_submitted'] > 0);
            ?>
                <div class="course-card">
                    <?php if($is_done): ?>
                        <span class="status-badge status-done">Evaluated</span>
                    <?php else: ?>
                        <span class="status-badge status-pending">Feedback Pending</span>
                    <?php endif; ?>
                    
                    <div class="c-icon"><i class="fas fa-book-open"></i></div>
                    <h3 class="c-title"><?php echo htmlspecialchars($row['course_name']); ?></h3>
                    <span class="c-teacher"><i class="fas fa-chalkboard-teacher"></i> <?php echo htmlspecialchars($row['teacher']); ?></span>
                    
                    <div style="font-size: 0.85rem; color: #94a3b8; margin-bottom: 20px;">
                        Credit Hours: <strong><?php echo $row['credit_hours']; ?></strong>
                    </div>

                    <?php if(!$is_done): ?>
                        <a href="feedback.php?course_id=<?php echo $row['course_id']; ?>" class="btn-course btn-eval">
                            Evaluate Course
                        </a>
                    <?php else: ?>
                        <button class="btn-course btn-view" disabled>Feedback Submitted</button>
                    <?php endif; ?>
                </div>
            <?php 
                endwhile; 
            } else {
                echo "<p style='color:#64748b;'>No courses found for this semester.</p>";
            }
            ?>
        </div>
    </main>
</body>
</html>