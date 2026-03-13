<?php
include 'db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

// Get heatmap data
$heatmap_query = "SELECT 
    c.course_name,
    fq.question,
    AVG(fr.rating) as avg_rating,
    COUNT(fr.response_id) as response_count
FROM courses c
CROSS JOIN feedback_questions fq
LEFT JOIN feedback_responses fr ON c.course_id = fr.course_id AND fq.question_id = fr.question_id
GROUP BY c.course_id, fq.question_id
ORDER BY c.course_name, fq.question_id";

$result = mysqli_query($conn, $heatmap_query);
$heatmap_data = [];
$courses = [];
$questions = [];

while($row = mysqli_fetch_assoc($result)) {
    if (!in_array($row['course_name'], $courses)) {
        $courses[] = $row['course_name'];
    }
    if (!in_array($row['question'], $questions)) {
        $questions[] = $row['question'];
    }
    
    $heatmap_data[] = [
        'course' => $row['course_name'],
        'question' => $row['question'],
        'rating' => $row['avg_rating'] ? round($row['avg_rating'], 2) : 0,
        'count' => $row['response_count']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interactive Feedback Heatmap</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 100%);
            min-height: 100vh;
            color: white;
            padding: 2rem;
        }
        
        .container { max-width: 1600px; margin: 0 auto; }
        
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
        
        .heatmap-container {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 2rem;
            overflow-x: auto;
            margin-bottom: 2rem;
        }
        
        .heatmap-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }
        
        .heatmap-table th {
            background: rgba(255,255,255,0.2);
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255,255,255,0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .heatmap-table th.course-header {
            text-align: left;
            min-width: 200px;
        }
        
        .heatmap-table td {
            padding: 0;
            border: 1px solid rgba(255,255,255,0.1);
            text-align: center;
            position: relative;
        }
        
        .heatmap-table td.course-name {
            padding: 1rem;
            font-weight: 600;
            background: rgba(255,255,255,0.05);
            text-align: left;
        }
        
        .heatmap-cell {
            width: 100%;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .heatmap-cell:hover {
            transform: scale(1.1);
            z-index: 5;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        
        /* Color scale for ratings */
        .rating-0 { background: #374151; color: #9ca3af; }
        .rating-1 { background: #dc2626; color: white; }
        .rating-2 { background: #ea580c; color: white; }
        .rating-3 { background: #d97706; color: white; }
        .rating-4 { background: #65a30d; color: white; }
        .rating-5 { background: #16a34a; color: white; }
        
        .legend {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }
        
        .tooltip {
            position: absolute;
            background: rgba(0,0,0,0.9);
            color: white;
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            pointer-events: none;
            z-index: 1000;
            opacity: 0;
            transition: opacity 0.3s ease;
            max-width: 200px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(255,255,255,0.1);
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="nav-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="header">
            <h1><i class="fas fa-fire"></i> Interactive Feedback Heatmap</h1>
            <p>Visual representation of course performance across all evaluation criteria</p>
        </div>
        
        <?php
        // Calculate statistics
        $total_responses = array_sum(array_column($heatmap_data, 'count'));
        $avg_overall = $total_responses > 0 ? array_sum(array_map(function($item) { 
            return $item['rating'] * $item['count']; 
        }, $heatmap_data)) / $total_responses : 0;
        
        $high_performers = count(array_filter($heatmap_data, function($item) { 
            return $item['rating'] >= 4.0 && $item['count'] > 0; 
        }));
        
        $needs_attention = count(array_filter($heatmap_data, function($item) { 
            return $item['rating'] < 3.0 && $item['count'] > 0; 
        }));
        ?>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?php echo count($courses); ?></div>
                <div class="stat-label">Total Courses</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo count($questions); ?></div>
                <div class="stat-label">Evaluation Criteria</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo number_format($avg_overall, 2); ?></div>
                <div class="stat-label">Overall Average</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $high_performers; ?></div>
                <div class="stat-label">High Performers</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?php echo $needs_attention; ?></div>
                <div class="stat-label">Needs Attention</div>
            </div>
        </div>
        
        <div class="heatmap-container">
            <table class="heatmap-table">
                <thead>
                    <tr>
                        <th class="course-header">Course</th>
                        <?php foreach($questions as $question): ?>
                            <th title="<?php echo htmlspecialchars($question); ?>">
                                <?php echo htmlspecialchars(substr($question, 0, 30)) . (strlen($question) > 30 ? '...' : ''); ?>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($courses as $course): ?>
                        <tr>
                            <td class="course-name"><?php echo htmlspecialchars($course); ?></td>
                            <?php foreach($questions as $question): ?>
                                <?php
                                $cell_data = null;
                                foreach($heatmap_data as $data) {
                                    if ($data['course'] === $course && $data['question'] === $question) {
                                        $cell_data = $data;
                                        break;
                                    }
                                }
                                
                                $rating = $cell_data ? $cell_data['rating'] : 0;
                                $count = $cell_data ? $cell_data['count'] : 0;
                                $rating_class = 'rating-' . round($rating);
                                ?>
                                <td>
                                    <div class="heatmap-cell <?php echo $rating_class; ?>" 
                                         data-course="<?php echo htmlspecialchars($course); ?>"
                                         data-question="<?php echo htmlspecialchars($question); ?>"
                                         data-rating="<?php echo $rating; ?>"
                                         data-count="<?php echo $count; ?>">
                                        <?php echo $rating > 0 ? number_format($rating, 1) : '-'; ?>
                                    </div>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="legend">
            <span style="font-weight: 600; margin-right: 1rem;">Rating Scale:</span>
            <div class="legend-item">
                <div class="legend-color rating-0"></div>
                <span>No Data</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rating-1"></div>
                <span>1.0 - Poor</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rating-2"></div>
                <span>2.0 - Fair</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rating-3"></div>
                <span>3.0 - Average</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rating-4"></div>
                <span>4.0 - Good</span>
            </div>
            <div class="legend-item">
                <div class="legend-color rating-5"></div>
                <span>5.0 - Excellent</span>
            </div>
        </div>
    </div>
    
    <div class="tooltip" id="tooltip"></div>
    
    <script>
        // Tooltip functionality
        const tooltip = document.getElementById('tooltip');
        const heatmapCells = document.querySelectorAll('.heatmap-cell');
        
        heatmapCells.forEach(cell => {
            cell.addEventListener('mouseenter', function(e) {
                const course = this.dataset.course;
                const question = this.dataset.question;
                const rating = this.dataset.rating;
                const count = this.dataset.count;
                
                tooltip.innerHTML = `
                    <strong>${course}</strong><br>
                    ${question}<br>
                    <strong>Rating:</strong> ${rating > 0 ? rating + '/5.0' : 'No data'}<br>
                    <strong>Responses:</strong> ${count}
                `;
                
                tooltip.style.opacity = '1';
            });
            
            cell.addEventListener('mousemove', function(e) {
                tooltip.style.left = e.pageX + 10 + 'px';
                tooltip.style.top = e.pageY + 10 + 'px';
            });
            
            cell.addEventListener('mouseleave', function() {
                tooltip.style.opacity = '0';
            });
        });
    </script>
</body>
</html>