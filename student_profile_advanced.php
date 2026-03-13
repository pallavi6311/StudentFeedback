<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'student') {
    header("Location: login_student.php");
    exit();
}

$student_id = (int)$_SESSION['user_id'];

// Get student info
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $student_id");
$user = mysqli_fetch_assoc($user_query) ?: [];

// Calculate gamification metrics
$feedback_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(DISTINCT course_id) as count FROM feedback_responses WHERE student_id = $student_id"))['count'];
$total_courses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM courses WHERE department = '" . mysqli_real_escape_string($conn, $user['department']) . "'"))['count'];

// Calculate level and XP
$xp = $feedback_count * 100; // 100 XP per feedback
$level = floor($xp / 500) + 1; // Level up every 500 XP
$xp_for_next_level = ($level * 500) - $xp;

// Calculate badges
$badges = [];
if ($feedback_count >= 1) $badges[] = ['name' => 'First Feedback', 'icon' => 'fas fa-star', 'color' => '#f59e0b'];
if ($feedback_count >= 5) $badges[] = ['name' => 'Feedback Warrior', 'icon' => 'fas fa-shield-alt', 'color' => '#3b82f6'];
if ($feedback_count >= 10) $badges[] = ['name' => 'Feedback Master', 'icon' => 'fas fa-crown', 'color' => '#8b5cf6'];
if ($feedback_count == $total_courses) $badges[] = ['name' => 'Completionist', 'icon' => 'fas fa-trophy', 'color' => '#10b981'];

// Get feedback history with detailed stats
$history_query = "SELECT fr.*, c.course_name, c.instructor as teacher, fr.created_at as timestamp 
                  FROM feedback_responses fr 
                  JOIN courses c ON fr.course_id = c.id 
                  WHERE fr.student_id = $student_id 
                  ORDER BY fr.created_at DESC";
$history_result = mysqli_query($conn, $history_query);

$feedback_history = [];
$monthly_activity = [];
while($row = mysqli_fetch_assoc($history_result)) {
    $feedback_history[] = $row;
    $month = date('Y-m', strtotime($row['timestamp']));
    $monthly_activity[$month] = ($monthly_activity[$month] ?? 0) + 1;
}

// Calculate streak
$streak = 0;
$current_date = new DateTime();
for($i = 0; $i < 30; $i++) {
    $check_date = $current_date->format('Y-m-d');
    $day_feedback = false;
    
    foreach($feedback_history as $feedback) {
        if (date('Y-m-d', strtotime($feedback['timestamp'])) === $check_date) {
            $day_feedback = true;
            break;
        }
    }
    
    if ($day_feedback) {
        $streak++;
    } else {
        break;
    }
    
    $current_date->modify('-1 day');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Advanced</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        
        .profile-header {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            padding: 3rem;
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .avatar {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, var(--primary), var(--purple));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: 700;
            margin: 0 auto 1.5rem;
            border: 4px solid rgba(255,255,255,0.3);
            position: relative;
            z-index: 2;
        }
        
        .profile-name {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 2;
        }
        
        .profile-department {
            font-size: 1.2rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .level-info {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            position: relative;
            z-index: 2;
        }
        
        .level-badge {
            background: linear-gradient(135deg, var(--warning), #ea580c);
            padding: 1rem 2rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.4);
        }
        
        .xp-info {
            text-align: center;
        }
        
        .xp-bar {
            width: 200px;
            height: 10px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            overflow: hidden;
            margin: 0.5rem 0;
        }
        
        .xp-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--success), #16a34a);
            border-radius: 5px;
            transition: width 0.5s ease;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-5px); }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            opacity: 0.8;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .badges-section {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .badges-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .badge-item {
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .badge-item:hover {
            transform: scale(1.05);
            background: rgba(255,255,255,0.2);
        }
        
        .badge-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .badge-name {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .activity-chart {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .nav-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 2rem;
            background: rgba(255,255,255,0.1);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
        }
        
        .progress-ring {
            width: 100px;
            height: 100px;
            margin: 0 auto;
        }
        
        .progress-ring-circle {
            stroke: rgba(255,255,255,0.2);
            stroke-width: 8;
            fill: transparent;
            r: 40;
            cx: 50;
            cy: 50;
        }
        
        .progress-ring-progress {
            stroke: var(--success);
            stroke-width: 8;
            stroke-linecap: round;
            fill: transparent;
            r: 40;
            cx: 50;
            cy: 50;
            stroke-dasharray: 251.2;
            stroke-dashoffset: 251.2;
            transform: rotate(-90deg);
            transform-origin: 50% 50%;
            transition: stroke-dashoffset 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_student.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="profile-header">
            <div class="avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <h1 class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></h1>
            <p class="profile-department"><?php echo htmlspecialchars($user['department']); ?></p>
            
            <div class="level-info">
                <div class="level-badge">
                    <i class="fas fa-medal"></i> Level <?php echo $level; ?>
                </div>
                <div class="xp-info">
                    <div><?php echo $xp; ?> XP</div>
                    <div class="xp-bar">
                        <div class="xp-fill" style="width: <?php echo (($xp % 500) / 500) * 100; ?>%;"></div>
                    </div>
                    <div style="font-size: 0.8rem; opacity: 0.8;"><?php echo $xp_for_next_level; ?> XP to next level</div>
                </div>
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments" style="color: var(--primary);"></i></div>
                <div class="stat-number"><?php echo $feedback_count; ?></div>
                <div class="stat-label">Feedback Given</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-fire" style="color: var(--danger);"></i></div>
                <div class="stat-number"><?php echo $streak; ?></div>
                <div class="stat-label">Day Streak</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-percentage" style="color: var(--success);"></i></div>
                <div class="stat-number"><?php echo $total_courses > 0 ? round(($feedback_count / $total_courses) * 100) : 0; ?>%</div>
                <div class="stat-label">Completion Rate</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-trophy" style="color: var(--warning);"></i></div>
                <div class="stat-number"><?php echo count($badges); ?></div>
                <div class="stat-label">Badges Earned</div>
            </div>
        </div>
        
        <?php if (!empty($badges)): ?>
        <div class="badges-section">
            <h2 class="section-title"><i class="fas fa-award"></i> Achievements</h2>
            <div class="badges-grid">
                <?php foreach($badges as $badge): ?>
                    <div class="badge-item">
                        <div class="badge-icon" style="color: <?php echo $badge['color']; ?>;">
                            <i class="<?php echo $badge['icon']; ?>"></i>
                        </div>
                        <div class="badge-name"><?php echo $badge['name']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="activity-chart">
            <h2 class="section-title"><i class="fas fa-chart-area"></i> Activity Overview</h2>
            <canvas id="activityChart" style="max-height: 300px;"></canvas>
        </div>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="stat-card">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-target"></i> Progress</h3>
                <svg class="progress-ring">
                    <circle class="progress-ring-circle"></circle>
                    <circle class="progress-ring-progress" style="stroke-dashoffset: <?php echo 251.2 - (($feedback_count / max($total_courses, 1)) * 251.2); ?>;"></circle>
                </svg>
                <p style="margin-top: 1rem;"><?php echo $feedback_count; ?> of <?php echo $total_courses; ?> courses completed</p>
            </div>
            
            <div class="stat-card">
                <h3 style="margin-bottom: 1rem;"><i class="fas fa-calendar-check"></i> Recent Activity</h3>
                <div style="max-height: 200px; overflow-y: auto;">
                    <?php foreach(array_slice($feedback_history, 0, 5) as $feedback): ?>
                        <div style="padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1);">
                            <div style="font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($feedback['course_name']); ?></div>
                            <div style="font-size: 0.8rem; opacity: 0.7;"><?php echo date('M j, Y', strtotime($feedback['timestamp'])); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Activity Chart
        const ctx = document.getElementById('activityChart').getContext('2d');
        
        // Prepare monthly data
        const monthlyData = <?php echo json_encode($monthly_activity); ?>;
        const months = [];
        const counts = [];
        
        // Get last 6 months
        for(let i = 5; i >= 0; i--) {
            const date = new Date();
            date.setMonth(date.getMonth() - i);
            const monthKey = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
            const monthLabel = date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
            
            months.push(monthLabel);
            counts.push(monthlyData[monthKey] || 0);
        }
        
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Feedback Given',
                    data: counts,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: { color: 'white' }
                    }
                },
                scales: {
                    x: {
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255,255,255,0.1)' }
                    },
                    y: {
                        ticks: { color: 'white' },
                        grid: { color: 'rgba(255,255,255,0.1)' },
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>