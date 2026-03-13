<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Tools</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: white;
            text-align: center;
            margin-bottom: 40px;
            font-size: 2.5rem;
        }
        .tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        .tool-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            transition: transform 0.3s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .tool-card:hover {
            transform: translateY(-5px);
        }
        .tool-icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }
        .tool-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }
        .tool-desc {
            color: #666;
            font-size: 0.9rem;
        }
        .back-link {
            display: inline-block;
            color: white;
            text-decoration: none;
            margin-bottom: 30px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>
        <h1>🛠️ Admin Tools</h1>
        
        <div class="tools-grid">
            <a href="admin_manage_questions.php" class="tool-card">
                <div class="tool-icon">📝</div>
                <div class="tool-title">Manage Questions</div>
                <div class="tool-desc">Create and edit feedback questions</div>
            </a>
            
            <a href="send_feedback_reminders.php" class="tool-card">
                <div class="tool-icon">📧</div>
                <div class="tool-title">Send Reminders</div>
                <div class="tool-desc">Email students with pending feedback</div>
            </a>
            
            <a href="verify_setup.php" class="tool-card">
                <div class="tool-icon">🔍</div>
                <div class="tool-title">Verify Database</div>
                <div class="tool-desc">Check database setup and integrity</div>
            </a>
        </div>
    </div>
</body>
</html>
