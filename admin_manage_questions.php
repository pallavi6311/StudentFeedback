<?php
session_start();
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') != 'admin') {
    header("Location: login_admin.php");
    exit();
}

$success_message = "";
$error_message = "";

// Add new question
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_question'])) {
    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    
    if (!empty($question)) {
        $insert_sql = "INSERT INTO feedback_questions (question, category) VALUES ('$question', '$category')";
        if (mysqli_query($conn, $insert_sql)) {
            $success_message = "Question added successfully!";
        } else {
            $error_message = "Error adding question: " . mysqli_error($conn);
        }
    }
}

// Delete question
if (isset($_GET['delete'])) {
    $question_id = (int)$_GET['delete'];
    $delete_sql = "DELETE FROM feedback_questions WHERE question_id = $question_id";
    if (mysqli_query($conn, $delete_sql)) {
        $success_message = "Question deleted successfully!";
    } else {
        $error_message = "Error deleting question: " . mysqli_error($conn);
    }
}

// Get all questions
$questions_query = "SELECT * FROM feedback_questions ORDER BY question_id DESC";
$questions_result = mysqli_query($conn, $questions_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback Questions</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .form-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #333;
        }
        input[type="text"], select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
        }
        textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-2px);
        }
        .btn-danger {
            background: #dc2626;
            color: white;
            padding: 8px 16px;
            font-size: 12px;
        }
        .btn-danger:hover {
            background: #b91c1c;
        }
        .questions-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .questions-table th {
            background: #667eea;
            color: white;
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        .questions-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
        }
        .questions-table tr:hover {
            background: #f8f9fa;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-content { background: #dbeafe; color: #1e40af; }
        .badge-teaching { background: #dcfce7; color: #166534; }
        .badge-assignments { background: #fef3c7; color: #92400e; }
        .badge-organization { background: #fce7f3; color: #9f1239; }
        .badge-overall { background: #e0e7ff; color: #3730a3; }
        .success-msg {
            background: #dcfce7;
            color: #166534;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #86efac;
        }
        .error-msg {
            background: #fee2e2;
            color: #dc2626;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_admin.php" class="back-link">← Back to Dashboard</a>
        
        <h1>📝 Manage Feedback Questions</h1>
        <p class="subtitle">Create and manage custom feedback questions for students</p>

        <?php if(!empty($success_message)): ?>
            <div class="success-msg">✅ <?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_message)): ?>
            <div class="error-msg">❌ <?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-section">
            <h2 style="margin-bottom: 20px;">Add New Question</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Question Text</label>
                    <textarea name="question" placeholder="Enter your feedback question..." required></textarea>
                </div>
                
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="Content">Content</option>
                        <option value="Teaching">Teaching</option>
                        <option value="Assignments">Assignments</option>
                        <option value="Organization">Organization</option>
                        <option value="Overall">Overall</option>
                    </select>
                </div>
                
                <button type="submit" name="add_question" class="btn btn-primary">Add Question</button>
            </form>
        </div>

        <h2>Existing Questions</h2>
        <table class="questions-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Category</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = mysqli_fetch_assoc($questions_result)): ?>
                <tr>
                    <td><?php echo $row['question_id']; ?></td>
                    <td><?php echo htmlspecialchars($row['question']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo strtolower($row['category']); ?>">
                            <?php echo $row['category']; ?>
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                    <td>
                        <a href="?delete=<?php echo $row['question_id']; ?>" 
                           class="btn btn-danger" 
                           onclick="return confirm('Are you sure you want to delete this question?')">
                            Delete
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
