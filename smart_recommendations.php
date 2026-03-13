<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Smart Recommendation Engine
class RecommendationEngine {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    // Analyze course performance and generate recommendations
    public function generateCourseRecommendations() {
        $recommendations = [];
        
        // Get course performance data
        $query = "SELECT c.id as course_id, c.course_name, c.instructor as teacher, 
                         AVG(fr.rating) as avg_rating,
                         COUNT(fr.response_id) as response_count,
                         COUNT(DISTINCT fr.student_id) as unique_students
                  FROM courses c
                  LEFT JOIN feedback_responses fr ON c.course_id = fr.course_id
                  GROUP BY c.course_id
                  HAVING response_count > 0
                  ORDER BY avg_rating ASC";
        
        $result = mysqli_query($this->conn, $query);
        
        while($row = mysqli_fetch_assoc($result)) {
            $avg_rating = (float)$row['avg_rating'];
            $response_count = (int)$row['response_count'];
            
            if ($avg_rating < 3.0) {
                $recommendations[] = [
                    'type' => 'critical',
                    'priority' => 'high',
                    'course' => $row['course_name'],
                    'teacher' => $row['teacher'],
                    'issue' => 'Low satisfaction rating',
                    'rating' => $avg_rating,
                    'action' => 'Immediate intervention required',
                    'suggestions' => [
                        'Schedule meeting with instructor',
                        'Review course curriculum',
                        'Implement student support measures',
                        'Consider additional training for instructor'
                    ]
                ];
            } elseif ($avg_rating < 3.5) {
                $recommendations[] = [
                    'type' => 'warning',
                    'priority' => 'medium',
                    'course' => $row['course_name'],
                    'teacher' => $row['teacher'],
                    'issue' => 'Below average performance',
                    'rating' => $avg_rating,
                    'action' => 'Monitor and improve',
                    'suggestions' => [
                        'Gather detailed feedback from students',
                        'Provide additional resources',
                        'Consider peer mentoring',
                        'Review teaching methodologies'
                    ]
                ];
            } elseif ($avg_rating >= 4.5) {
                $recommendations[] = [
                    'type' => 'success',
                    'priority' => 'low',
                    'course' => $row['course_name'],
                    'teacher' => $row['teacher'],
                    'issue' => 'Excellent performance',
                    'rating' => $avg_rating,
                    'action' => 'Recognize and replicate',
                    'suggestions' => [
                        'Recognize instructor excellence',
                        'Document best practices',
                        'Share methods with other instructors',
                        'Consider as model course'
                    ]
                ];
            }
        }
        
        return $recommendations;
    }
    
    // Analyze student engagement patterns
    public function analyzeStudentEngagement() {
        $engagement_data = [];
        
        // Get student participation rates
        $query = "SELECT u.department, 
                         COUNT(DISTINCT u.id) as total_students,
                         COUNT(DISTINCT fr.student_id) as active_students,
                         (COUNT(DISTINCT fr.student_id) / COUNT(DISTINCT u.id)) * 100 as participation_rate
                  FROM users u
                  LEFT JOIN feedback_responses fr ON u.id = fr.student_id
                  WHERE u.role = 'student'
                  GROUP BY u.department";
        
        $result = mysqli_query($this->conn, $query);
        
        while($row = mysqli_fetch_assoc($result)) {
            $participation_rate = (float)$row['participation_rate'];
            
            $engagement_data[] = [
                'department' => $row['department'],
                'total_students' => $row['total_students'],
                'active_students' => $row['active_students'],
                'participation_rate' => $participation_rate,
                'status' => $participation_rate >= 80 ? 'excellent' : 
                           ($participation_rate >= 60 ? 'good' : 
                           ($participation_rate >= 40 ? 'fair' : 'poor'))
            ];
        }
        
        return $engagement_data;
    }
    
    // Generate predictive insights
    public function generatePredictiveInsights() {
        $insights = [];
        
        // Trend analysis - compare current month with previous month
        $current_month = date('Y-m');
        $previous_month = date('Y-m', strtotime('-1 month'));
        
        $current_feedback = mysqli_fetch_assoc(mysqli_query($this->conn, 
            "SELECT COUNT(*) as count, AVG(rating) as avg_rating 
             FROM feedback_responses 
             WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'"));
        
        $previous_feedback = mysqli_fetch_assoc(mysqli_query($this->conn, 
            "SELECT COUNT(*) as count, AVG(rating) as avg_rating 
             FROM feedback_responses 
             WHERE DATE_FORMAT(created_at, '%Y-%m') = '$previous_month'"));
        
        $feedback_trend = $current_feedback['count'] - $previous_feedback['count'];
        $rating_trend = $current_feedback['avg_rating'] - $previous_feedback['avg_rating'];
        
        $insights[] = [
            'type' => 'trend',
            'title' => 'Feedback Volume Trend',
            'current_value' => $current_feedback['count'],
            'previous_value' => $previous_feedback['count'],
            'change' => $feedback_trend,
            'change_percent' => $previous_feedback['count'] > 0 ? 
                              round(($feedback_trend / $previous_feedback['count']) * 100, 1) : 0,
            'prediction' => $feedback_trend > 0 ? 'Increasing engagement' : 'Decreasing engagement'
        ];
        
        $insights[] = [
            'type' => 'trend',
            'title' => 'Average Rating Trend',
            'current_value' => round($current_feedback['avg_rating'], 2),
            'previous_value' => round($previous_feedback['avg_rating'], 2),
            'change' => round($rating_trend, 2),
            'change_percent' => $previous_feedback['avg_rating'] > 0 ? 
                              round(($rating_trend / $previous_feedback['avg_rating']) * 100, 1) : 0,
            'prediction' => $rating_trend > 0 ? 'Improving satisfaction' : 'Declining satisfaction'
        ];
        
        return $insights;
    }
}

$engine = new RecommendationEngine($conn);
$course_recommendations = $engine->generateCourseRecommendations();
$engagement_data = $engine->analyzeStudentEngagement();
$predictive_insights = $engine->generatePredictiveInsights();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Recommendations - AI Insights</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --purple: #8b5cf6;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
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
            background: linear-gradient(135deg, #60a5fa, #a78bfa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
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
        
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }
        
        .insight-card {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .insight-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .insight-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        
        .insight-title {
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .trend-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .trend-change {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .trend-positive { color: var(--success); }
        .trend-negative { color: var(--danger); }
        .trend-neutral { color: #94a3b8; }
        
        .recommendations-section {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .recommendation-item {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        
        .rec-critical { border-left-color: var(--danger); }
        .rec-warning { border-left-color: var(--warning); }
        .rec-success { border-left-color: var(--success); }
        
        .rec-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .rec-course {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .rec-teacher {
            font-size: 0.9rem;
            opacity: 0.8;
        }
        
        .rec-priority {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .priority-high { background: var(--danger); }
        .priority-medium { background: var(--warning); }
        .priority-low { background: var(--success); }
        
        .rec-rating {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .rec-action {
            font-weight: 600;
            margin-bottom: 1rem;
            color: #e2e8f0;
        }
        
        .suggestions-list {
            list-style: none;
            padding: 0;
        }
        
        .suggestions-list li {
            padding: 0.5rem 0;
            padding-left: 1.5rem;
            position: relative;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .suggestions-list li::before {
            content: '→';
            position: absolute;
            left: 0;
            color: var(--primary);
            font-weight: bold;
        }
        
        .engagement-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .engagement-card {
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
        }
        
        .engagement-dept {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .engagement-rate {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .engagement-status {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-excellent { background: var(--success); }
        .status-good { background: var(--primary); }
        .status-fair { background: var(--warning); }
        .status-poor { background: var(--danger); }
        
        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--purple), var(--primary));
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 1rem;
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
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <div class="ai-badge">
                <div class="pulse"></div>
                <i class="fas fa-robot"></i>
                AI-Powered Insights
            </div>
            <h1><i class="fas fa-lightbulb"></i> Smart Recommendations</h1>
            <p>Intelligent analysis and actionable insights for educational improvement</p>
        </div>
        
        <!-- Predictive Insights -->
        <div class="insights-grid">
            <?php foreach($predictive_insights as $insight): ?>
                <div class="insight-card">
                    <div class="insight-header">
                        <div class="insight-icon" style="background: linear-gradient(135deg, var(--primary), var(--purple));">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="insight-title"><?php echo $insight['title']; ?></div>
                    </div>
                    
                    <div class="trend-value"><?php echo $insight['current_value']; ?></div>
                    
                    <div class="trend-change <?php echo $insight['change'] > 0 ? 'trend-positive' : ($insight['change'] < 0 ? 'trend-negative' : 'trend-neutral'); ?>">
                        <i class="fas fa-<?php echo $insight['change'] > 0 ? 'arrow-up' : ($insight['change'] < 0 ? 'arrow-down' : 'minus'); ?>"></i>
                        <?php echo abs($insight['change']); ?> (<?php echo $insight['change_percent']; ?>%)
                    </div>
                    
                    <div style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.9;">
                        <strong>Prediction:</strong> <?php echo $insight['prediction']; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Course Recommendations -->
        <div class="recommendations-section">
            <h2 class="section-title">
                <i class="fas fa-brain"></i>
                AI Course Recommendations
            </h2>
            
            <?php if (empty($course_recommendations)): ?>
                <div style="text-align: center; padding: 3rem; opacity: 0.7;">
                    <i class="fas fa-check-circle" style="font-size: 3rem; margin-bottom: 1rem; color: var(--success);"></i>
                    <p>All courses are performing well! No immediate actions required.</p>
                </div>
            <?php else: ?>
                <?php foreach($course_recommendations as $rec): ?>
                    <div class="recommendation-item rec-<?php echo $rec['type']; ?>">
                        <div class="rec-header">
                            <div>
                                <div class="rec-course"><?php echo htmlspecialchars($rec['course']); ?></div>
                                <div class="rec-teacher">Instructor: <?php echo htmlspecialchars($rec['teacher']); ?></div>
                            </div>
                            <div>
                                <div class="rec-priority priority-<?php echo $rec['priority']; ?>">
                                    <?php echo $rec['priority']; ?> Priority
                                </div>
                                <div class="rec-rating" style="text-align: right; margin-top: 0.5rem;">
                                    <?php echo number_format($rec['rating'], 1); ?>/5.0
                                </div>
                            </div>
                        </div>
                        
                        <div class="rec-action">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php echo $rec['issue']; ?> - <?php echo $rec['action']; ?>
                        </div>
                        
                        <ul class="suggestions-list">
                            <?php foreach($rec['suggestions'] as $suggestion): ?>
                                <li><?php echo $suggestion; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Student Engagement Analysis -->
        <div class="recommendations-section">
            <h2 class="section-title">
                <i class="fas fa-users"></i>
                Student Engagement Analysis
            </h2>
            
            <div class="engagement-grid">
                <?php foreach($engagement_data as $dept): ?>
                    <div class="engagement-card">
                        <div class="engagement-dept"><?php echo htmlspecialchars($dept['department']); ?></div>
                        <div class="engagement-rate"><?php echo number_format($dept['participation_rate'], 1); ?>%</div>
                        <div class="engagement-status status-<?php echo $dept['status']; ?>">
                            <?php echo ucfirst($dept['status']); ?>
                        </div>
                        <div style="margin-top: 1rem; font-size: 0.9rem; opacity: 0.8;">
                            <?php echo $dept['active_students']; ?> of <?php echo $dept['total_students']; ?> students active
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>