<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

// API endpoint for real-time data
if (isset($_GET['api']) && $_GET['api'] === 'realtime') {
    header('Content-Type: application/json');
    
    // Get real-time statistics
    $stats = [];
    
    // Total feedback count
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses");
    $stats['total_feedback'] = mysqli_fetch_assoc($result)['count'];
    
    // Today's feedback
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses WHERE DATE(timestamp) = CURDATE()");
    $stats['today_feedback'] = mysqli_fetch_assoc($result)['count'];
    
    // Average rating
    $result = mysqli_query($conn, "SELECT AVG(rating) as avg FROM feedback_responses WHERE rating > 0");
    $stats['avg_rating'] = round(mysqli_fetch_assoc($result)['avg'], 2);
    
    // Recent activity (last 10 feedback)
    $result = mysqli_query($conn, "SELECT fr.*, c.course_name, u.name as full_name 
                                   FROM feedback_responses fr 
                                   JOIN courses c ON fr.course_id = c.id 
                                   JOIN users u ON fr.student_id = u.id 
                                   ORDER BY fr.created_at DESC LIMIT 10");
    $recent_activity = [];
    while($row = mysqli_fetch_assoc($result)) {
        $recent_activity[] = [
            'student' => $row['full_name'],
            'course' => $row['course_name'],
            'rating' => $row['rating'],
            'time' => date('H:i', strtotime($row['timestamp']))
        ];
    }
    $stats['recent_activity'] = $recent_activity;
    
    // Hourly feedback data for the last 24 hours
    $hourly_data = [];
    for($i = 23; $i >= 0; $i--) {
        $hour = date('Y-m-d H:00:00', strtotime("-$i hours"));
        $next_hour = date('Y-m-d H:00:00', strtotime("-" . ($i-1) . " hours"));
        
        $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM feedback_responses 
                                       WHERE timestamp >= '$hour' AND timestamp < '$next_hour'");
        $count = mysqli_fetch_assoc($result)['count'];
        
        $hourly_data[] = [
            'hour' => date('H:i', strtotime($hour)),
            'count' => (int)$count
        ];
    }
    $stats['hourly_data'] = $hourly_data;
    
    echo json_encode($stats);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Real-time Analytics Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --dark: #1e293b;
            --light: #f8fafc;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        
        .header { 
            text-align: center; 
            margin-bottom: 3rem; 
            background: rgba(255,255,255,0.1);
            padding: 2rem;
            border-radius: 20px;
            backdrop-filter: blur(10px);
        }
        .header h1 { 
            font-size: 2.5rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .header p { font-size: 1.1rem; opacity: 0.9; }
        
        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--success);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-top: 1rem;
        }
        
        .pulse {
            width: 8px;
            height: 8px;
            background: white;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 1; }
            70% { transform: scale(1); opacity: 0.7; }
            100% { transform: scale(0.95); opacity: 1; }
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 3rem; 
        }
        
        .stat-card { 
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 2rem; 
            text-align: center;
            border: 1px solid rgba(255,255,255,0.2);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover { transform: translateY(-5px); }
        
        .stat-icon { 
            font-size: 2.5rem; 
            margin-bottom: 1rem; 
            opacity: 0.8;
        }
        
        .stat-number { 
            font-size: 2.5rem; 
            font-weight: 700; 
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .stat-label { 
            font-size: 0.9rem; 
            opacity: 0.8; 
            text-transform: uppercase; 
            letter-spacing: 1px;
        }
        
        .chart-container { 
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 2rem; 
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .chart-title { 
            font-size: 1.3rem; 
            font-weight: 600; 
            margin-bottom: 1.5rem;
            text-align: center;
        }
        
        .activity-feed { 
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .activity-item { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 1rem 0; 
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .activity-item:last-child { border-bottom: none; }
        
        .activity-info { flex-grow: 1; }
        .activity-student { font-weight: 600; margin-bottom: 0.25rem; }
        .activity-course { font-size: 0.9rem; opacity: 0.8; }
        .activity-rating { 
            font-weight: 700; 
            font-size: 1.1rem;
            margin-right: 1rem;
        }
        .activity-time { 
            font-size: 0.8rem; 
            opacity: 0.7;
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
            transition: background 0.3s ease;
        }
        .nav-back:hover { 
            background: rgba(255,255,255,0.2);
            text-decoration: none;
            color: white;
        }
        
        .grid-2 { 
            display: grid; 
            grid-template-columns: 2fr 1fr; 
            gap: 2rem; 
        }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> Real-time Analytics</h1>
            <p>Live feedback monitoring and insights</p>
            <div class="live-indicator">
                <div class="pulse"></div>
                LIVE
            </div>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-comments"></i></div>
                <div class="stat-number" id="totalFeedback">0</div>
                <div class="stat-label">Total Feedback</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-day"></i></div>
                <div class="stat-number" id="todayFeedback">0</div>
                <div class="stat-label">Today's Feedback</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-star"></i></div>
                <div class="stat-number" id="avgRating">0.0</div>
                <div class="stat-label">Average Rating</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-number" id="lastUpdate">--:--</div>
                <div class="stat-label">Last Update</div>
            </div>
        </div>
        
        <div class="grid-2">
            <div class="chart-container">
                <div class="chart-title">24-Hour Feedback Activity</div>
                <canvas id="activityChart" style="max-height: 300px;"></canvas>
            </div>
            
            <div class="activity-feed">
                <div class="chart-title">Live Activity Feed</div>
                <div id="activityFeed">
                    <div style="text-align: center; opacity: 0.7; padding: 2rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2rem;"></i>
                        <p style="margin-top: 1rem;">Loading activity...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let activityChart;
        
        // Initialize chart
        function initChart() {
            const ctx = document.getElementById('activityChart').getContext('2d');
            activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Feedback Count',
                        data: [],
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
                            labels: {
                                color: 'white'
                            }
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
        }
        
        // Update dashboard with real-time data
        function updateDashboard() {
            fetch('?api=realtime')
                .then(response => response.json())
                .then(data => {
                    // Update stats
                    document.getElementById('totalFeedback').textContent = data.total_feedback;
                    document.getElementById('todayFeedback').textContent = data.today_feedback;
                    document.getElementById('avgRating').textContent = data.avg_rating;
                    document.getElementById('lastUpdate').textContent = new Date().toLocaleTimeString();
                    
                    // Update chart
                    if (activityChart && data.hourly_data) {
                        activityChart.data.labels = data.hourly_data.map(item => item.hour);
                        activityChart.data.datasets[0].data = data.hourly_data.map(item => item.count);
                        activityChart.update();
                    }
                    
                    // Update activity feed
                    const feedContainer = document.getElementById('activityFeed');
                    if (data.recent_activity && data.recent_activity.length > 0) {
                        feedContainer.innerHTML = data.recent_activity.map(activity => `
                            <div class="activity-item">
                                <div class="activity-info">
                                    <div class="activity-student">${activity.student}</div>
                                    <div class="activity-course">${activity.course}</div>
                                </div>
                                <div class="activity-rating">${activity.rating}/5</div>
                                <div class="activity-time">${activity.time}</div>
                            </div>
                        `).join('');
                    } else {
                        feedContainer.innerHTML = `
                            <div style="text-align: center; opacity: 0.7; padding: 2rem;">
                                <i class="fas fa-inbox" style="font-size: 2rem;"></i>
                                <p style="margin-top: 1rem;">No recent activity</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error fetching real-time data:', error);
                });
        }
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            initChart();
            updateDashboard();
            
            // Update every 5 seconds
            setInterval(updateDashboard, 5000);
        });
    </script>
</body>
</html>