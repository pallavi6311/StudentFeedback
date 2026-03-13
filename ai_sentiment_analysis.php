<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Simple sentiment analysis function (can be enhanced with external APIs)
function analyzeSentiment($text) {
    $positive_words = ['excellent', 'great', 'good', 'amazing', 'wonderful', 'fantastic', 'love', 'best', 'perfect', 'outstanding'];
    $negative_words = ['bad', 'terrible', 'awful', 'hate', 'worst', 'horrible', 'poor', 'disappointing', 'boring', 'useless'];
    
    $text = strtolower($text);
    $positive_count = 0;
    $negative_count = 0;
    
    foreach($positive_words as $word) {
        $positive_count += substr_count($text, $word);
    }
    
    foreach($negative_words as $word) {
        $negative_count += substr_count($text, $word);
    }
    
    if ($positive_count > $negative_count) return 'positive';
    if ($negative_count > $positive_count) return 'negative';
    return 'neutral';
}

// Fetch feedback with sentiment analysis
$feedback_query = "SELECT fr.*, c.course_name, u.name as full_name, fq.question 
                   FROM feedback_responses fr 
                   JOIN courses c ON fr.course_id = c.id 
                   JOIN users u ON fr.student_id = u.id 
                   JOIN feedback_questions fq ON fr.question_id = fq.question_id 
                   WHERE fr.text_response IS NOT NULL AND fr.text_response != '' 
                   ORDER BY fr.created_at DESC";
$feedback_result = mysqli_query($conn, $feedback_query);

$sentiment_data = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
$course_sentiments = [];
$feedbacks = [];

while($row = mysqli_fetch_assoc($feedback_result)) {
    $sentiment = analyzeSentiment($row['text_response']);
    $row['sentiment'] = $sentiment;
    $sentiment_data[$sentiment]++;
    
    if (!isset($course_sentiments[$row['course_name']])) {
        $course_sentiments[$row['course_name']] = ['positive' => 0, 'negative' => 0, 'neutral' => 0];
    }
    $course_sentiments[$row['course_name']][$sentiment]++;
    
    $feedbacks[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Sentiment Analysis - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #f8fafc;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: var(--bg); color: #1e293b; }
        
        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .header { text-align: center; margin-bottom: 3rem; }
        .header h1 { font-size: 2.5rem; font-weight: 700; color: var(--primary); margin-bottom: 0.5rem; }
        .header p { color: #64748b; font-size: 1.1rem; }
        
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-bottom: 3rem; }
        .card { background: white; border-radius: 16px; padding: 2rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        
        .sentiment-overview { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .sentiment-card { text-align: center; padding: 1.5rem; border-radius: 12px; }
        .sentiment-positive { background: linear-gradient(135deg, #d1fae5, #a7f3d0); color: #065f46; }
        .sentiment-negative { background: linear-gradient(135deg, #fee2e2, #fecaca); color: #991b1b; }
        .sentiment-neutral { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #3730a3; }
        
        .sentiment-number { font-size: 2rem; font-weight: 700; margin-bottom: 0.5rem; }
        .sentiment-label { font-size: 0.9rem; font-weight: 500; text-transform: uppercase; }
        
        .feedback-item { background: white; border-radius: 12px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid; }
        .feedback-positive { border-left-color: var(--success); }
        .feedback-negative { border-left-color: var(--danger); }
        .feedback-neutral { border-left-color: #94a3b8; }
        
        .feedback-header { display: flex; justify-content: between; align-items: center; margin-bottom: 1rem; }
        .student-info { font-weight: 600; color: #374151; }
        .course-info { color: #6b7280; font-size: 0.9rem; }
        .sentiment-badge { padding: 0.25rem 0.75rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .badge-positive { background: #d1fae5; color: #065f46; }
        .badge-negative { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #e0e7ff; color: #3730a3; }
        
        .nav-back { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--primary); text-decoration: none; font-weight: 500; margin-bottom: 2rem; }
        .nav-back:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-brain"></i> AI Sentiment Analysis</h1>
            <p>Advanced feedback analysis using artificial intelligence</p>
        </div>
        
        <div class="grid">
            <!-- Overall Sentiment Overview -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; color: #374151;"><i class="fas fa-chart-pie"></i> Overall Sentiment</h3>
                <div class="sentiment-overview">
                    <div class="sentiment-card sentiment-positive">
                        <div class="sentiment-number"><?php echo $sentiment_data['positive']; ?></div>
                        <div class="sentiment-label">Positive</div>
                    </div>
                    <div class="sentiment-card sentiment-negative">
                        <div class="sentiment-number"><?php echo $sentiment_data['negative']; ?></div>
                        <div class="sentiment-label">Negative</div>
                    </div>
                    <div class="sentiment-card sentiment-neutral">
                        <div class="sentiment-number"><?php echo $sentiment_data['neutral']; ?></div>
                        <div class="sentiment-label">Neutral</div>
                    </div>
                </div>
            </div>
            
            <!-- Sentiment Trend Chart -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; color: #374151;"><i class="fas fa-chart-line"></i> Sentiment Distribution</h3>
                <canvas id="sentimentChart" style="max-height: 300px;"></canvas>
            </div>
            
            <!-- Course-wise Sentiment -->
            <div class="card">
                <h3 style="margin-bottom: 1.5rem; color: #374151;"><i class="fas fa-book"></i> Course Sentiment Breakdown</h3>
                <div style="max-height: 300px; overflow-y: auto;">
                    <?php foreach($course_sentiments as $course => $sentiments): ?>
                        <div style="margin-bottom: 1rem; padding: 1rem; background: #f8fafc; border-radius: 8px;">
                            <div style="font-weight: 600; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($course); ?></div>
                            <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                                <span style="color: var(--success);">✓ <?php echo $sentiments['positive']; ?></span>
                                <span style="color: var(--danger);">✗ <?php echo $sentiments['negative']; ?></span>
                                <span style="color: #6b7280;">◯ <?php echo $sentiments['neutral']; ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Recent Feedback with Sentiment -->
        <div class="card">
            <h3 style="margin-bottom: 1.5rem; color: #374151;"><i class="fas fa-comments"></i> Recent Feedback Analysis</h3>
            <div style="max-height: 600px; overflow-y: auto;">
                <?php foreach(array_slice($feedbacks, 0, 10) as $feedback): ?>
                    <div class="feedback-item feedback-<?php echo $feedback['sentiment']; ?>">
                        <div class="feedback-header">
                            <div>
                                <div class="student-info"><?php echo htmlspecialchars($feedback['full_name']); ?></div>
                                <div class="course-info"><?php echo htmlspecialchars($feedback['course_name']); ?></div>
                            </div>
                            <span class="sentiment-badge badge-<?php echo $feedback['sentiment']; ?>">
                                <?php echo ucfirst($feedback['sentiment']); ?>
                            </span>
                        </div>
                        <p style="color: #4b5563; line-height: 1.6;"><?php echo htmlspecialchars($feedback['text_response']); ?></p>
                        <div style="margin-top: 1rem; font-size: 0.8rem; color: #9ca3af;">
                            <?php echo date('M j, Y g:i A', strtotime($feedback['created_at'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Sentiment Distribution Chart
        const ctx = document.getElementById('sentimentChart').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Positive', 'Negative', 'Neutral'],
                datasets: [{
                    data: [<?php echo $sentiment_data['positive']; ?>, <?php echo $sentiment_data['negative']; ?>, <?php echo $sentiment_data['neutral']; ?>],
                    backgroundColor: ['#10b981', '#ef4444', '#6366f1'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>