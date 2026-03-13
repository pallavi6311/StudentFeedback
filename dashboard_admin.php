<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// 1. Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

// 2. GET CURRENT PAGE
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// 3. GLOBAL STATS (Needed for sidebar/header)
$total_students = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE role='student'"))['count'];
$total_feedbacks = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses"))['count'];
$avg_rating_raw = mysqli_fetch_assoc(mysqli_query($conn, "SELECT AVG(rating) as avg FROM feedback_responses"))['avg'];
$avg_rating = number_format($avg_rating_raw, 1);
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses"))['count'];

// Calculate breakdown for charts (Satisfied vs Unsatisfied)
$positive_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses WHERE rating >= 4"))['count'];
$neutral_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses WHERE rating >= 3 AND rating < 4"))['count'];
$negative_fb = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses WHERE rating < 3"))['count'];

// Helper function for Stars
function renderStars($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $color = ($i <= $rating) ? '#f59e0b' : '#e5e7eb'; // Orange for filled, Gray for empty
        $html .= '<i class="fas fa-star" style="color: '.$color.'; font-size: 0.8rem; margin-right: 2px;"></i>';
    }
    return $html;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Professional</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-color: #2563eb; /* Royal Blue */
            --secondary-color: #1d4ed8;
            --bg-color: #f3f4f6; /* Light gray background */
            --card-bg: #ffffff;
            --text-dark: #1f2937;
            --text-light: #6b7280;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Poppins', sans-serif; }

        body { display: flex; background-color: var(--bg-color); color: var(--text-dark); min-height: 100vh; }

        /* --- SIDEBAR --- */
        .sidebar {
            width: 260px;
            background-color: var(--primary-color);
            color: white;
            min-height: 100vh;
            padding: 2rem;
            position: fixed;
            display: flex;
            flex-direction: column;
        }
        .sidebar h2 { font-size: 1.5rem; margin-bottom: 3rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }
        .menu-item {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 15px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 10px;
            transition: 0.3s;
        }
        .menu-item:hover, .menu-item.active { background-color: rgba(255,255,255,0.2); color: white; }
        .menu-item i { width: 20px; text-align: center; }

        /* --- MAIN CONTENT --- */
        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 2rem;
        }

        /* --- HEADER --- */
        .header {
            display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;
        }
        .header h1 { font-size: 1.8rem; font-weight: 600; }
        
        .search-box {
            background: white; padding: 8px 15px; border-radius: 20px; display: flex; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.05); margin-right: 20px;
        }
        .search-box input { border: none; outline: none; margin-left: 10px; color: var(--text-dark); }

        .user-profile { display: flex; align-items: center; gap: 10px; background: white; padding: 8px 15px; border-radius: 30px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

        /* --- STATS CARDS --- */
        .stats-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 2rem;
        }
        .card {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            text-align: center;
            border: 1px solid #e5e7eb;
            transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }
        
        .card-icon {
            width: 50px; height: 50px; margin: 0 auto 10px;
            background: #eff6ff; color: var(--primary-color);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            border: 2px solid var(--primary-color);
        }
        .card h3 { font-size: 2rem; font-weight: 700; color: var(--primary-color); margin-bottom: 5px; }
        .card p { font-size: 0.9rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500; }

        /* --- CONTENT CARDS --- */
        .content-card {
            background: var(--card-bg);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .section-title { font-size: 1.1rem; font-weight: 600; display: flex; align-items: center; gap: 10px; }

        /* --- TABLE DESIGN --- */
        .table-container { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; min-width: 800px; }
        th { text-align: left; padding: 15px; color: var(--text-light); font-size: 0.85rem; font-weight: 600; border-bottom: 2px solid #f3f4f6; text-transform: uppercase; letter-spacing: 0.5px; }
        td { padding: 15px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; font-size: 0.95rem; }
        
        /* Progress Bar Styling */
        .rating-container { display: flex; align-items: center; gap: 15px; }
        .progress-bg { flex-grow: 1; height: 8px; background: #e5e7eb; border-radius: 4px; overflow: hidden; max-width: 150px; }
        .progress-fill { height: 100%; border-radius: 4px; }
        .score-text { font-weight: 600; min-width: 40px; }
        
        /* Badges */
        .badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; display: inline-block;}
        .badge-gray { background: #f3f4f6; color: var(--text-dark); }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-yellow { background: #fef3c7; color: #92400e; }
        .badge-red { background: #fee2e2; color: #991b1b; }

        /* Buttons */
        .btn { padding: 8px 16px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.9rem; font-weight: 500; transition: 0.2s; text-decoration: none; display: inline-block; }
        .btn-primary { background: var(--primary-color); color: white; }
        .btn-outline { background: white; border: 1px solid #e5e7eb; color: var(--text-dark); }
        .btn:hover { opacity: 0.9; transform: translateY(-1px); }

        /* Charts Layout */
        .charts-row { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 2rem; }
        
        /* Student Initials */
        .avatar-initial { width: 35px; height: 35px; background: var(--primary-color); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 0.9rem; margin-right: 10px; }
        .student-flex { display: flex; align-items: center; }

    </style>
</head>
<body>

    <!-- SIDEBAR -->
    <div class="sidebar">
        <h2><i class="fas fa-shield-alt"></i> Admin</h2>
        
        <a href="?page=dashboard" class="menu-item <?php echo $page == 'dashboard' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="?page=reports" class="menu-item <?php echo $page == 'reports' ? 'active' : ''; ?>">
            <i class="fas fa-chart-pie"></i> Full Reports
        </a>
        <a href="?page=students" class="menu-item <?php echo $page == 'students' ? 'active' : ''; ?>">
            <i class="fas fa-users"></i> Students
        </a>
        <a href="?page=courses" class="menu-item <?php echo $page == 'courses' ? 'active' : ''; ?>">
            <i class="fas fa-book"></i> Courses
        </a>
        <a href="ai_sentiment_analysis.php" class="menu-item">
            <i class="fas fa-brain"></i> AI Sentiment
        </a>
        <a href="realtime_analytics.php" class="menu-item">
            <i class="fas fa-chart-line"></i> Live Analytics
        </a>
        <a href="feedback_heatmap.php" class="menu-item">
            <i class="fas fa-fire"></i> Heatmap
        </a>
        <a href="smart_recommendations.php" class="menu-item">
            <i class="fas fa-lightbulb"></i> AI Insights
        </a>
        <a href="admin_manage_questions.php" class="menu-item">
            <i class="fas fa-edit"></i> Manage Questions
        </a>
        <a href="admin_student_management.php" class="menu-item">
            <i class="fas fa-user-graduate"></i> Student Management
        </a>
        <a href="send_feedback_reminders.php" class="menu-item">
            <i class="fas fa-envelope"></i> Send Reminders
        </a>
        
        <a href="logout.php" class="menu-item" style="margin-top: auto;">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>

    <!-- MAIN CONTENT -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <div>
                <h1>
                    <?php 
                        if($page == 'dashboard') echo "Dashboard Overview";
                        elseif($page == 'reports') echo "Analytics Reports";
                        elseif($page == 'students') echo "Student Management";
                        elseif($page == 'courses') echo "Course Performance";
                    ?>
                </h1>
                <p style="color: var(--text-light);">Welcome back, Administrator</p>
            </div>
            
            <div style="display: flex; align-items: center;">
                <form method="GET" action="" class="search-box">
                    <input type="hidden" name="page" value="<?php echo $page; ?>">
                    <i class="fas fa-search" style="color: #9ca3af;"></i>
                    <input type="text" name="search" placeholder="Search..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </form>

                <div class="user-profile">
                    <div style="width: 35px; height: 35px; background: #dfe7ff; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary-color); font-weight: bold;">A</div>
                    <span style="font-size: 0.9rem; font-weight: 500;">Admin</span>
                </div>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats-grid">
            <div class="card">
                <div class="card-icon"><i class="fas fa-user-graduate"></i></div>
                <h3><?php echo $total_students; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-comment-dots"></i></div>
                <h3><?php echo $total_feedbacks; ?></h3>
                <p>Total Feedbacks</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-star"></i></div>
                <h3><?php echo $avg_rating; ?>/5</h3>
                <p>Avg Rating</p>
            </div>
            <div class="card">
                <div class="card-icon"><i class="fas fa-book-open"></i></div>
                <h3><?php echo $total_courses; ?></h3>
                <p>Active Courses</p>
            </div>
        </div>

        <!-- ================= PAGE CONTENT SWITCH ================= -->

        <?php if ($page == 'dashboard'): ?>
            <!-- DASHBOARD VIEW -->
            <div class="charts-row">
                <div class="content-card" style="margin-bottom: 0;">
                    <div class="section-header">
                        <span class="section-title"><i class="fas fa-chart-bar" style="color: var(--primary-color);"></i> Feedback Overview</span>
                    </div>
                    <div style="height: 250px;">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>
                <div class="content-card" style="margin-bottom: 0;">
                    <div class="section-header">
                        <span class="section-title"><i class="fas fa-smile" style="color: var(--warning);"></i> Satisfaction</span>
                    </div>
                    <div style="position: relative; height: 200px; display: flex; justify-content: center;">
                        <canvas id="donutChart"></canvas>
                        <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center;">
                            <span style="font-size: 1.5rem; font-weight: bold; display: block; color: var(--text-dark);"><?php echo $avg_rating; ?></span>
                            <span style="font-size: 0.8rem; color: #6b7280;">Average</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Feedback Table -->
            <div class="content-card">
                <div class="section-header">
                    <span class="section-title">Recent Feedback</span>
                    <a href="?page=reports" class="btn btn-primary">View All Reports</a>
                </div>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr><th>Student</th><th>Course</th><th>Question</th><th>Rating</th></tr>
                        </thead>
                        <tbody>
                            <?php
                            $search = $_GET['search'] ?? '';
                            $where = $search ? "WHERE c.course_name LIKE '%$search%' OR u.name LIKE '%$search%'" : '';
                            
                            $query = "SELECT fr.*, u.name as full_name, c.course_name, fq.question 
                                      FROM feedback_responses fr 
                                      JOIN users u ON fr.student_id = u.id 
                                      JOIN courses c ON fr.course_id = c.course_id 
                                      JOIN feedback_questions fq ON fr.question_id = fq.question_id 
                                      $where ORDER BY fr.response_id DESC LIMIT 5";
                            $feedbacks = mysqli_query($conn, $query);

                            if(mysqli_num_rows($feedbacks) > 0):
                                while($row = mysqli_fetch_assoc($feedbacks)):
                            ?>
                            <tr>
                                <td>
                                    <div class="student-flex">
                                        <div class="avatar-initial"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                                        <span style="font-weight: 500;"><?php echo htmlspecialchars($row['full_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                <td style="color: var(--text-light);"><?php echo htmlspecialchars(substr($row['question'],0,40)); ?>...</td>
                                <td><?php echo renderStars($row['rating']); ?></td>
                            </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="4" style="text-align: center;">No recent feedback found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'reports'): ?>
            <!-- REPORTS VIEW (THE REDESIGNED SECTION) -->
            <div class="content-card">
                <div class="section-header">
                    <div>
                        <span class="section-title">Detailed Feedback Analysis</span>
                        <p style="color: var(--text-light); font-size: 0.9rem; margin-top: 5px;">Performance breakdown by question</p>
                    </div>
                    <div>
                        <button onclick="window.print()" class="btn btn-outline"><i class="fas fa-print"></i> Print</button>
                    </div>
                </div>

                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 45%;">Question</th>
                                <th style="width: 15%;">Responses</th>
                                <th style="width: 40%;">Average Score</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $r_sql = "SELECT fq.question, AVG(fr.rating) as avg, COUNT(fr.response_id) as count 
                                      FROM feedback_questions fq 
                                      LEFT JOIN feedback_responses fr ON fq.question_id = fr.question_id 
                                      GROUP BY fq.question_id";
                            $r_res = mysqli_query($conn, $r_sql);
                            
                            while($row = mysqli_fetch_assoc($r_res)):
                                $score = number_format($row['avg'], 1);
                                $width = ($score / 5) * 100;
                                
                                // Dynamic Color Logic
                                if ($score >= 4.0) { $color = 'var(--success)'; } // Green
                                elseif ($score >= 3.0) { $color = 'var(--warning)'; } // Orange
                                else { $color = 'var(--danger)'; } // Red
                                
                                // Hide items with 0 responses if you want cleaner look, else keep them
                                // if ($row['count'] == 0) continue; 
                            ?>
                            <tr>
                                <td><?php echo $row['question']; ?></td>
                                <td><span class="badge badge-gray"><?php echo $row['count']; ?> responses</span></td>
                                <td>
                                    <div class="rating-container">
                                        <span class="score-text" style="color: <?php echo $color; ?>"><?php echo $score; ?></span>
                                        <div class="progress-bg">
                                            <div class="progress-fill" style="width: <?php echo $width; ?>%; background-color: <?php echo $color; ?>;"></div>
                                        </div>
                                        <span style="font-size: 0.75rem; color: #aaa;">/ 5.0</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'courses'): ?>
            <!-- COURSES VIEW -->
            <div class="content-card">
                <div class="section-header">
                    <span class="section-title">Course Performance</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Course Name</th><th>Total Feedback</th><th>Avg Rating</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php
                            $c_sql = "SELECT c.course_name, COUNT(fr.response_id) as count, AVG(fr.rating) as avg 
                                      FROM courses c 
                                      LEFT JOIN feedback_responses fr ON c.course_id = fr.course_id 
                                      GROUP BY c.course_id";
                            $c_res = mysqli_query($conn, $c_sql);
                            while($row = mysqli_fetch_assoc($c_res)):
                                $avg = number_format($row['avg'], 1);
                                
                                if($row['count'] == 0) {
                                    $status = '<span class="badge badge-gray">No Data</span>';
                                } elseif($avg >= 4) {
                                    $status = '<span class="badge badge-green">Excellent</span>';
                                } elseif($avg >= 3) {
                                    $status = '<span class="badge badge-yellow">Good</span>';
                                } else {
                                    $status = '<span class="badge badge-red">Critical</span>';
                                }
                            ?>
                            <tr>
                                <td style="font-weight: 500;"><?php echo $row['course_name']; ?></td>
                                <td><?php echo $row['count']; ?></td>
                                <td><?php echo ($row['count'] > 0) ? renderStars(round($row['avg'])) . " ($avg)" : '-'; ?></td>
                                <td><?php echo $status; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page == 'students'): ?>
            <!-- STUDENTS VIEW -->
            <div class="content-card">
                <div class="section-header">
                    <span class="section-title">Registered Students</span>
                </div>
                <div class="table-container">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Feedback Given</th><th>Action</th></tr></thead>
                        <tbody>
                            <?php
                            $s_sql = "SELECT u.id as user_id, u.name as full_name, u.email, COUNT(fr.response_id) as fb_count 
                                      FROM users u 
                                      LEFT JOIN feedback_responses fr ON u.id = fr.student_id 
                                      WHERE u.role='student' 
                                      GROUP BY u.id";
                            $s_res = mysqli_query($conn, $s_sql);
                            while($row = mysqli_fetch_assoc($s_res)):
                            ?>
                            <tr>
                                <td>
                                    <div class="student-flex">
                                        <div class="avatar-initial"><?php echo strtoupper(substr($row['full_name'], 0, 1)); ?></div>
                                        <span style="font-weight: 500;"><?php echo $row['full_name']; ?></span>
                                    </div>
                                </td>
                                <td style="color: var(--text-light);"><?php echo $row['email']; ?></td>
                                <td><span class="badge badge-gray"><?php echo $row['fb_count']; ?></span></td>
                                <td>
                                    <a href="mailto:<?php echo $row['email']; ?>" class="btn btn-outline" style="padding: 5px 10px; font-size: 0.8rem;">Email</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

    </div>

    <!-- Chart Logic -->
    <script>
        <?php if ($page == 'dashboard'): ?>
        // Bar Chart
        const ctxBar = document.getElementById('barChart').getContext('2d');
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Positive (4-5)', 'Neutral (3)', 'Negative (1-2)'],
                datasets: [{
                    label: 'Feedback Count',
                    data: [<?php echo $positive_fb; ?>, <?php echo $neutral_fb; ?>, <?php echo $negative_fb; ?>],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderRadius: 5,
                    barThickness: 40
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { y: { beginAtZero: true, grid: { borderDash: [2, 4] } }, x: { grid: { display: false } } },
                plugins: { legend: { display: false } }
            }
        });

        // Donut Chart
        const ctxDonut = document.getElementById('donutChart').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Neutral', 'Negative'],
                datasets: [{
                    data: [<?php echo $positive_fb; ?>, <?php echo $neutral_fb; ?>, <?php echo $negative_fb; ?>],
                    backgroundColor: ['#2563eb', '#f59e0b', '#ef4444'],
                    borderWidth: 0,
                    cutout: '75%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>