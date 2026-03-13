<?php
// Session start (safe check)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db.php';

// Security Check
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') != 'student') {
    header("Location: login_student.php");
    exit;
}

$student_id = (int)$_SESSION['user_id'];
$current_semester = isset($_GET['semester']) ? urldecode($_GET['semester']) : 'Fall 2025';

// Get Course ID
$course_id = (int)($_GET['course_id'] ?? $_POST['course_id'] ?? 0);
$selected_course = null;

// Fetch Course Details Directly
if ($course_id > 0) {
    $course_sql = "SELECT course_id, course_name, COALESCE(teacher, 'TBD') as teacher FROM courses WHERE course_id = ?";
    $stmt = mysqli_prepare($conn, $course_sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $course_id);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($row = mysqli_fetch_assoc($res)) {
            $selected_course = $row;
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch questions
$questions = [];
$error = ""; 
if (isset($conn) && $conn) {
    $stmt = mysqli_prepare($conn, "SELECT question_id, question, category as question_type FROM feedback_questions WHERE question IS NOT NULL AND question != '' ORDER BY question_id LIMIT 12");
    if ($stmt) {
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty(trim($row['question']))) {
                $questions[] = [
                    'question_id' => (int)$row['question_id'],
                    'question' => trim($row['question']),
                    'type' => $row['question_type'] ?? 'rating'
                ];
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Handle Submit
if (isset($_POST['submit_feedback']) && $course_id > 0 && $selected_course && !empty($questions)) {
    // Check for duplicate submission
    $dup_stmt = mysqli_prepare($conn, "SELECT 1 FROM feedback_responses WHERE student_id = ? AND course_id = ? LIMIT 1");
    if ($dup_stmt) {
        mysqli_stmt_bind_param($dup_stmt, "ii", $student_id, $course_id);
        mysqli_stmt_execute($dup_stmt);
        $already = mysqli_num_rows(mysqli_stmt_get_result($dup_stmt)) > 0;
        mysqli_stmt_close($dup_stmt);

        if ($already) {
            $error = "<p style='color:#f59e0b; text-align:center;'>⚠️ Already submitted for this course.</p>";
        } else {
            $success = true;
            
            foreach ($questions as $q) {
                $response = trim($_POST["q_{$q['question_id']}"] ?? '');
                
                // Validate Rating
                if ($q['type'] === 'rating' && !in_array($response, ['1','2','3','4','5'])) {
                    $success = false;
                    $error = "<p style='color:#ef4444; text-align:center;'>Invalid rating for Q" . $q['question_id'] . ".</p>";
                    break;
                }
                
                // Prepare variables
                $rating_val = 0;       
                $comment_val = "";     

                if ($q['type'] === 'rating') {
                    $rating_val = (int)$response;
                } else {
                    $comment_val = $response;
                }
                
                // Insert
                $sql = "INSERT INTO feedback_responses (student_id, course_id, question_id, semester, rating, comment) VALUES (?, ?, ?, ?, ?, ?)";
                $ins_stmt = mysqli_prepare($conn, $sql);
                
                if ($ins_stmt) {
                    mysqli_stmt_bind_param($ins_stmt, "iiisis", $student_id, $course_id, $q['question_id'], $current_semester, $rating_val, $comment_val);
                    if (!mysqli_stmt_execute($ins_stmt)) {
                        $success = false;
                    }
                    mysqli_stmt_close($ins_stmt);
                } else {
                     $success = false; 
                }
            }
            
            if ($success) {
                $error = "<div style='text-align:center; padding:20px; background:#f0fdf4; border-radius:12px; margin-bottom:20px;'>
                            <i class='fas fa-check-circle' style='font-size:2rem; color:#16a34a; margin-bottom:10px;'></i>
                            <p style='color:#166534; font-weight:600; margin:0;'>Feedback Submitted Successfully!</p>
                            <p style='color:#15803d; margin-top:5px;'>Thank you for evaluating " . htmlspecialchars($selected_course['course_name']) . ".</p>
                            <a href='dashboard_student.php' style='display:inline-block; margin-top:15px; text-decoration:none; background:#16a34a; color:white; padding:8px 20px; border-radius:6px; font-weight:600;'>Return to Dashboard</a>
                          </div>";
                $course_id = 0; // Hide form after success
                $selected_course = null;
            } else {
                $error = "<p style='color:#ef4444; text-align:center;'>❌ Submit failed. Database error.</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Feedback</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); font-family: 'Poppins', sans-serif; margin:0; min-height: 100vh; }
        .nav { background: rgba(0,0,0,0.2); padding: 15px; text-align: center; backdrop-filter: blur(5px); }
        .nav a { color: white; margin: 0 15px; text-decoration: none; font-weight: bold; font-size: 1.1rem; transition: opacity 0.2s; }
        .nav a:hover { opacity: 0.8; }
        
        .form-container { max-width: 900px; margin: 2rem auto; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.25); background: #fff; overflow: hidden; }
        
        .form-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 2.5rem 2rem; text-align: center; position: relative; }
        .form-header h1 { margin: 0; font-size: 2rem; letter-spacing: -0.5px; }
        
        .form-body { padding: 2.5rem; }
        
        /* --- NEW TABLE STYLES --- */
        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            background-color: #fff;
        }

        .evaluation-table th {
            background-color: #f8fafc;
            color: #4f46e5; /* Match theme blue/purple */
            font-weight: 700;
            padding: 18px 10px;
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
            font-size: 0.95rem;
            vertical-align: bottom;
        }

        .evaluation-table th small {
            display: block;
            color: #64748b;
            font-weight: 400;
            margin-top: 4px;
            font-size: 0.8rem;
        }

        .evaluation-table th:first-child {
            text-align: left;
            padding-left: 20px;
            width: 45%;
        }

        .evaluation-table td {
            padding: 15px 10px;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
            vertical-align: middle;
        }
        
        .evaluation-table td:first-child {
            padding-left: 20px;
            font-weight: 500;
        }

        /* Zebra Striping */
        .evaluation-table tr:nth-child(even) {
            background-color: rgba(241, 245, 249, 0.6); 
        }
        .evaluation-table tr:hover {
            background-color: rgba(99, 102, 241, 0.05); /* Very light purple on hover */
        }

        /* Radio Button Styling */
        .radio-cell { text-align: center; }
        
        input[type="radio"] {
            accent-color: #4f46e5; /* Theme Color */
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Comment Section */
        .comment-group {
            margin-bottom: 25px;
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        .comment-label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
            color: #4f46e5;
        }
        .form-input { 
            width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 8px; 
            font-size: 1rem; box-sizing: border-box; font-family: inherit;
        }
        .form-input:focus { border-color: #6366f1; outline: none; box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1); }
        
        .form-button { width: 100%; padding: 18px; background: #4f46e5; color: white; border: none; border-radius: 12px; font-size: 1.1rem; cursor: pointer; font-weight: 600; transition: all 0.2s; margin-top: 20px; box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2); }
        .form-button:hover { background: #4338ca; transform: translateY(-1px); box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3); }

        .loading { text-align: center; color: #64748b; padding: 40px; font-size: 1.1rem; }
    </style>
</head>
<body>
    <div class="nav">
        <a href="dashboard_student.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1><i class="fas fa-pen-nib"></i> Course Feedback</h1>
            <p style="margin-top: 10px; opacity: 0.9; font-size: 1rem;">
                Semester: <strong><?php echo htmlspecialchars($current_semester); ?></strong>
            </p>
        </div>
        
        <div class="form-body">
            <?php if ($error): ?>
                <div style="margin-bottom: 25px;"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" id="feedbackForm">
                <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">

                <?php if ($course_id > 0 && $selected_course): ?>
                    
                    <!-- Course Info Header -->
                    <div style="text-align: center; margin-bottom: 30px;">
                        <h2 style="color: #1e293b; margin: 0 0 8px 0; font-size: 1.8rem;">
                            <?php echo htmlspecialchars($selected_course['course_name']); ?>
                        </h2>
                        <span style="display:inline-block; background: #e0e7ff; color: #3730a3; padding: 6px 16px; border-radius: 20px; font-size: 0.95rem; font-weight: 600;">
                            <?php echo htmlspecialchars($selected_course['teacher']); ?>
                        </span>
                    </div>

                    <?php if (!empty($questions)): 
                        // Separate questions into two arrays: Rating vs Text
                        $ratingQs = [];
                        $textQs = [];
                        foreach($questions as $q) {
                            if($q['type'] === 'rating') $ratingQs[] = $q;
                            else $textQs[] = $q;
                        }
                    ?>
                        
                        <!-- 1. RATING TABLE -->
                        <?php if(count($ratingQs) > 0): ?>
                        <p style="margin-bottom: 15px; color: #64748b; font-size: 0.95rem;">Please rate the following aspects of the course:</p>
                        
                        <table class="evaluation-table">
                            <thead>
                                <tr>
                                    <th>Evaluation Criteria</th>
                                    <th>1<br><small>Poor</small></th>
                                    <th>2<br><small>Fair</small></th>
                                    <th>3<br><small>Avg</small></th>
                                    <th>4<br><small>Good</small></th>
                                    <th>5<br><small>Excellent</small></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($ratingQs as $q): 
                                    $savedVal = $_POST["q_{$q['question_id']}"] ?? ''; 
                                ?>
                                <tr>
                                    <td>Q<?php echo (int)$q['question_id']; ?>. <?php echo htmlspecialchars($q['question']); ?></td>
                                    
                                    <!-- Radio Buttons 1-5 -->
                                    <td class="radio-cell"><input type="radio" name="q_<?php echo $q['question_id']; ?>" value="1" <?php if($savedVal=='1') echo 'checked'; ?> required></td>
                                    <td class="radio-cell"><input type="radio" name="q_<?php echo $q['question_id']; ?>" value="2" <?php if($savedVal=='2') echo 'checked'; ?>></td>
                                    <td class="radio-cell"><input type="radio" name="q_<?php echo $q['question_id']; ?>" value="3" <?php if($savedVal=='3') echo 'checked'; ?>></td>
                                    <td class="radio-cell"><input type="radio" name="q_<?php echo $q['question_id']; ?>" value="4" <?php if($savedVal=='4') echo 'checked'; ?>></td>
                                    <td class="radio-cell"><input type="radio" name="q_<?php echo $q['question_id']; ?>" value="5" <?php if($savedVal=='5') echo 'checked'; ?>></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>

                        <!-- 2. TEXT COMMENTS -->
                        <?php if(count($textQs) > 0): ?>
                            <?php foreach ($textQs as $q): ?>
                                <div class="comment-group">
                                    <label class="comment-label">
                                        Q<?php echo (int)$q['question_id']; ?>. <?php echo htmlspecialchars($q['question']); ?>
                                    </label>
                                    <textarea name="q_<?php echo $q['question_id']; ?>" class="form-input" rows="3"
                                              placeholder="Share your thoughts here..."><?php echo htmlspecialchars($_POST["q_{$q['question_id']}"] ?? ''); ?></textarea>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <button type="submit" name="submit_feedback" class="form-button">
                            Submit Evaluation <i class="fas fa-arrow-right" style="margin-left: 8px;"></i>
                        </button>

                    <?php else: ?>
                        <div class="loading">No questions found in database.</div>
                    <?php endif; ?>

                <?php else: ?>
                    <div class="loading">
                        <i class="fas fa-search" style="font-size: 2rem; margin-bottom: 15px; color: #cbd5e1; display:block;"></i>
                        No course selected.<br>
                        <a href="dashboard_student.php" style="color: #4f46e5; font-weight:600; text-decoration:none; margin-top:10px; display:inline-block;">Back to Dashboard</a>
                    </div>
                <?php endif; ?>
            </form>
            
            <?php if($course_id > 0): ?>
            <p style="text-align: center; margin-top: 30px;">
                <a href="dashboard_student.php" style="color: #94a3b8; text-decoration: none; font-weight: 500; font-size: 0.9rem;">
                    <i class="fas fa-chevron-left"></i> Cancel & Back to Dashboard
                </a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>