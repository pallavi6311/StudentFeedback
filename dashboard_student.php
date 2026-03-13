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

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// --- LOGIC FIX: Handle Session & Semester Selection ---
$selected_session = $_GET['session'] ?? ($_SESSION['selected_session'] ?? 'Fall 2025');
$selected_semester = (int)($_GET['semester'] ?? ($_SESSION['selected_semester'] ?? 1));

// Ensure semester is valid
if ($selected_semester < 1) $selected_semester = 1;

// SAVE TO SESSION (So Schedule and Courses pages know what you picked)
$_SESSION['selected_semester'] = $selected_semester;
$_SESSION['selected_session'] = $selected_session;

$session_sql = mysqli_real_escape_string($conn, $selected_session);

// --- RESTORED LOGIC: Calculate Stats (Fixes the Undefined Variable Error) ---
$total_submissions = 0;
$avg_rating = 0; // Initialize variable to prevent error

$stats_sql = mysqli_query($conn, "SELECT COUNT(DISTINCT course_id) as cnt, AVG(NULLIF(rating, 0)) as avg FROM feedback_responses WHERE student_id = $student_id");
if ($stats_sql && $row = mysqli_fetch_assoc($stats_sql)) {
    $total_submissions = (int)$row['cnt'];
    // Format rating to 1 decimal place if it exists, otherwise 0
    $avg_rating = $row['avg'] ? number_format((float)$row['avg'], 1) : 0;
}

// Fetch Courses for the specific semester
$query = "
    SELECT c.id as course_id, c.course_name, c.semester as semester_number, c.instructor as teacher, 3 as credit_hours,
           MAX(fr.response_id) as response_id, 
           ROUND(AVG(NULLIF(fr.rating, 0)), 1) as rating, 
           MAX(fr.created_at) as timestamp
    FROM courses c
    LEFT JOIN feedback_responses fr 
        ON c.id = fr.course_id AND fr.student_id = $student_id AND fr.session = '$session_sql'
    WHERE c.semester = $selected_semester AND c.department = '$dept'
    GROUP BY c.id
    ORDER BY c.course_name ASC
";
$result = mysqli_query($conn, $query);

$pending_courses = [];
$completed_courses = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['response_id']) {
        $completed_courses[] = $row;
    } else {
        $pending_courses[] = $row;
    }
}

$total_courses_found = count($pending_courses) + count($completed_courses);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --main-bg: #f0f2f5;
            --sidebar-gradient: linear-gradient(180deg, #2563eb 0%, #1e40af 100%);
            --accent-color: #3b82f6;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            --c-blue: #2563eb; 
        }
        
        * { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--main-bg); margin: 0; color: #334155; display: flex; height: 100vh; overflow: hidden; }

        .sidebar { 
            width: 270px; 
            background: #111827;
            background-image: var(--sidebar-gradient);
            color: white; 
            display: flex; flex-direction: column; padding: 25px; flex-shrink: 0; 
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        
        .brand { 
            margin-bottom: 50px; 
            display: flex; 
            flex-direction: column; 
            gap: 8px; 
            background: rgba(255,255,255,0.1); 
            padding: 15px; 
            border-radius: 16px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .menu { list-style: none; padding: 0; margin: 0; flex-grow: 1; }
        .menu li { margin-bottom: 12px; }
        .menu a { text-decoration: none; color: rgba(255,255,255,0.7); font-weight: 500; display: flex; align-items: center; gap: 15px; padding: 14px 18px; border-radius: 14px; transition: all 0.3s ease; }
        .menu a:hover { background: rgba(255, 255, 255, 0.15); color: white; transform: translateX(5px); }
        .menu a.active { background: white; color: #2563eb; font-weight: 700; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }

        .main-content { flex-grow: 1; overflow-y: auto; padding: 40px; display: flex; flex-direction: column; }

        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .welcome h1 { font-size: 2rem; margin: 0; font-weight: 700; color: #1e293b; letter-spacing: -0.5px; }
        .welcome p { color: #64748b; margin: 5px 0 0; font-size: 1rem; }
        
        .filters { display: flex; gap: 12px; background: white; padding: 8px; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.5); }
        .form-select { padding: 10px 16px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit; color: #475569; outline: none; cursor: pointer; background-color: #f8fafc; }
        .btn-update { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; border: none; padding: 10px 24px; border-radius: 10px; cursor: pointer; font-weight: 600; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3); transition: transform 0.2s; }
        .btn-update:hover { transform: translateY(-2px); }

        .stats-row { display: grid; grid-template-columns: repeat(4, 1fr); gap: 25px; margin-bottom: 40px; }
        .stat-card { 
            background: white; border-radius: 24px; padding: 25px; text-align: center; 
            box-shadow: var(--card-shadow); display: flex; flex-direction: column; align-items: center; justify-content: center; 
            transition: all 0.3s ease; position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: var(--card-hover-shadow); }
        .circle-wrap { width: 90px; height: 90px; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 15px; border: 4px solid #f1f5f9; position: relative; }
        .circle-wrap::after { content: ''; position: absolute; inset: -4px; border-radius: 50%; border: 4px solid transparent; opacity: 0.3; }
        .stat-card .circle-wrap { border-color: var(--c-blue); color: var(--c-blue); box-shadow: 0 0 20px rgba(37, 99, 235, 0.15); }
        .stat-num { font-size: 1.8rem; font-weight: 700; }
        .stat-label { font-size: 0.9rem; color: #94a3b8; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }

        .content-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; }
        .section-card { background: white; border-radius: 24px; padding: 30px; box-shadow: var(--card-shadow); height: 100%; transition: transform 0.3s; }
        .section-card:hover { transform: translateY(-3px); }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 2px solid #f1f5f9; padding-bottom: 15px; }
        .section-title { font-size: 1.25rem; font-weight: 700; color: #1e293b; margin: 0; }
        
        .task-item { 
            display: flex; align-items: center; justify-content: space-between; 
            padding: 18px 0; border-bottom: 1px solid #f1f5f9; transition: background 0.2s; 
            border-radius: 12px; padding-left: 10px; padding-right: 10px; 
        }
        .task-item:hover { background: #f8fafc; }
        .task-item:last-child { border-bottom: none; }
        
        .task-icon { 
            width: 45px; height: 45px; flex-shrink: 0;
            background: linear-gradient(135deg, #eff6ff, #dbeafe); 
            color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; 
            margin-right: 18px; font-size: 1.1rem; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1);
        }
        
        .task-info { flex-grow: 1; margin-right: 30px; }
        .task-title { font-weight: 600; font-size: 1rem; color: #334155; display: block; margin-bottom: 4px; }
        
        .btn-action { 
            flex-shrink: 0; margin-left: auto;
            text-decoration: none; font-size: 0.85rem; font-weight: 600; color: white; 
            background: var(--accent-color); padding: 8px 16px; border-radius: 8px; 
            box-shadow: 0 4px 10px rgba(59, 130, 246, 0.3); transition: 0.2s; white-space: nowrap; 
        }
        .btn-action:hover { background: #2563eb; box-shadow: 0 6px 15px rgba(59, 130, 246, 0.4); }

        @media (max-width: 1000px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .content-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="brand">
            <div style="display:flex; justify-content:space-between; width:100%; align-items: flex-end;">
                <span style="font-size: 1.1rem; font-weight: 700;">Semester <?php echo $selected_semester; ?></span>
                <?php 
                    // Calculate progress percentage
                    $percent = ($total_courses_found > 0) ? round((count($completed_courses) / $total_courses_found) * 100) : 0;
                ?>
                <span style="font-size: 0.8rem; opacity: 0.9;"><?php echo $percent; ?>%</span>
            </div>
            <!-- Progress Bar Container -->
            <div style="width: 100%; height: 6px; background: rgba(0,0,0,0.2); border-radius: 10px; overflow: hidden;">
                <!-- Progress Fill -->
                <div style="width: <?php echo $percent; ?>%; height: 100%; background: white; border-radius: 10px;"></div>
            </div>
            <span style="font-size: 0.75rem; opacity: 0.7; margin-top: 2px;">Completed</span>
        </div>
        
        <ul class="menu">
            <li><a href="dashboard_student.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="schedule.php"><i class="fas fa-calendar-alt"></i> Schedule</a></li>
            <li><a href="courses.php"><i class="fas fa-book"></i> Courses</a></li>
            <li><a href="feedback.php"><i class="fas fa-edit"></i> Feedback</a></li>
            <li><a href="student_profile_advanced.php"><i class="fas fa-user-circle"></i> My Profile</a></li>
            <li style="margin-top: auto;"><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </nav>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        
        <div class="header">
            <div class="welcome">
                <h1>Welcome, <?php echo h($user['full_name'] ?? 'Student'); ?> 👋</h1>
                <p><?php echo h($user['department'] ?? 'Department'); ?></p>
            </div>
            
            <form method="GET" class="filters">
                <select name="session" class="form-select">
                    <?php 
                    $start_year = 2022; $end_year = 2026;
                    for ($y = $start_year; $y <= $end_year; $y++) {
                        foreach (['Spring', 'Fall'] as $term) {
                            $val = "$term $y";
                            $sel = ($selected_session === $val) ? 'selected' : '';
                            echo "<option value='$val' $sel>$val</option>";
                        }
                    }
                    ?>
                </select>
                <select name="semester" class="form-select">
                    <?php for($i=1; $i<=8; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($selected_semester == $i) ? 'selected' : ''; ?>>
                            Semester <?php echo $i; ?>
                        </option>
                    <?php endfor; ?>
                </select>
                <button type="submit" class="btn-update">View</button>
            </form>
        </div>

        <!-- STATS -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="circle-wrap">
                    <span class="stat-num"><?php echo $total_submissions; ?></span>
                </div>
                <span class="stat-label">Submissions</span>
            </div>
            <div class="stat-card">
                <div class="circle-wrap">
                    <!-- Fixed Variable Here -->
                    <span class="stat-num"><?php echo $avg_rating; ?></span>
                </div>
                <span class="stat-label">Avg Rating</span>
            </div>
            <div class="stat-card">
                <div class="circle-wrap">
                    <span class="stat-num"><?php echo count($pending_courses); ?></span>
                </div>
                <span class="stat-label">Pending</span>
            </div>
            <div class="stat-card">
                <div class="circle-wrap">
                    <span class="stat-num"><?php echo $selected_semester; ?></span>
                </div>
                <span class="stat-label">Semester</span>
            </div>
        </div>

        <div class="content-grid">
            
            <!-- PENDING LIST -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">Pending Feedback</h3>
                    <span style="font-size:0.9rem; color:#94a3b8; font-weight:500;">Semester <?php echo $selected_semester; ?></span>
                </div>

                <?php if (count($pending_courses) > 0): ?>
                    <ul class="task-list">
                        <?php foreach ($pending_courses as $course): ?>
                        <li class="task-item">
                            <div class="task-icon">
                                <i class="fas fa-clipboard-list"></i>
                            </div>
                            <div class="task-info">
                                <span class="task-title"><?php echo h($course['course_name']); ?></span>
                                <span class="task-sub">
                                    <?php echo h($course['teacher'] ?? 'TBD'); ?> • <?php echo h($course['credit_hours']); ?> Credits
                                </span>
                            </div>
                            <a href="feedback.php?semester=<?php echo urlencode($selected_session); ?>&course_id=<?php echo $course['course_id']; ?>" 
                               class="btn-action">
                                Give Feedback
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php elseif ($total_courses_found > 0): ?>
                    <div style="text-align:center; padding:40px;">
                        <i class="fas fa-check-circle" style="font-size:3rem; color:#10b981; margin-bottom:15px; display:block; opacity:0.8;"></i>
                        <p style="color:#10b981; font-weight:600; margin:0;">All caught up!</p>
                        <p style="color:#64748b; font-size:0.9rem; margin-top:5px;">You've submitted feedback for all courses this semester.</p>
                    </div>
                <?php else: ?>
                    <div style="text-align:center; padding:40px;">
                        <i class="fas fa-folder-open" style="font-size:3rem; color:#cbd5e1; margin-bottom:15px; display:block;"></i>
                        <p style="color:#64748b; font-weight:600; margin:0;">No courses found</p>
                        <p style="color:#94a3b8; font-size:0.9rem; margin-top:5px;">
                            There are no registered courses for <strong>Semester <?php echo $selected_semester; ?></strong>.
                        </p>
                    </div>
                <?php endif; ?>

            </div>

            <!-- HISTORY -->
            <div class="section-card">
                <div class="section-header">
                    <h3 class="section-title">History</h3>
                    <a href="#" style="font-size:0.85rem; color:var(--accent-color); text-decoration:none; font-weight:600;">View All</a>
                </div>

                <?php if (count($completed_courses) > 0): ?>
                    <?php foreach ($completed_courses as $course): ?>
                        <div class="task-item">
                            <i class="fas fa-check-circle" style="color:#94a3b8; font-size:1.3rem; margin-right:15px;"></i>
                            <div style="flex-grow:1;">
                                <span class="task-title"><?php echo h($course['course_name']); ?></span>
                                <span class="task-sub">Rated on <?php echo date('M j', strtotime($course['timestamp'])); ?></span>
                            </div>
                            <span style="font-weight:700; color:#94a3b8; font-size:1rem;"><?php echo h($course['rating']); ?>/5</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="font-size:0.9rem; color:#94a3b8; text-align:center;">No completed feedback yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </main>
</body>
</html>