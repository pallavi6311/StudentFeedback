<?php
session_start();
include 'db.php';

$success_message = "";
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $department = mysqli_real_escape_string($conn, $_POST['department']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($department)) {
        $error_message = "All fields are required!";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match!";
    } elseif (strlen($password) < 5) {
        $error_message = "Password must be at least 5 characters!";
    } else {
        // Check if email already exists
        $check_sql = "SELECT * FROM users WHERE email = '$email'";
        $check_result = mysqli_query($conn, $check_sql);
        
        if (mysqli_num_rows($check_result) > 0) {
            $error_message = "Email already registered!";
        } else {
            // Hash password and insert
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_sql = "INSERT INTO users (name, email, password, role, department) VALUES ('$name', '$email', '$hashed_password', 'student', '$department')";
            
            if (mysqli_query($conn, $insert_sql)) {
                $success_message = "Registration successful! You can now login.";
            } else {
                $error_message = "Registration failed: " . mysqli_error($conn);
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
    <title>Student Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: 1px solid rgba(255, 255, 255, 0.4);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --primary-blue: #2563eb;
            --text-dark: #1e293b;
            --text-light: #64748b;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(to bottom, #e0f7fa 0%, #b3e5fc 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .glass-card {
            width: 100%; max-width: 450px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: var(--glass-border);
            border-radius: 24px;
            box-shadow: var(--glass-shadow);
            padding: 40px 30px;
            text-align: center;
        }

        .logo-container {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 20px rgba(16, 185, 129, 0.3);
        }

        .logo-icon { width: 45px; height: 45px; fill: white; }

        .card-title {
            color: var(--primary-blue);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .card-subtitle {
            color: var(--text-light);
            font-size: 0.9rem;
            margin-bottom: 30px;
        }

        .form-group {
            width: 100%;
            margin-bottom: 20px;
            text-align: left;
        }

        .form-label {
            display: block;
            color: var(--text-dark);
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 8px;
            margin-left: 5px;
        }

        .input-wrapper {
            width: 100%;
            background: rgba(255, 255, 255, 0.6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            border: 1px solid transparent;
            transition: all 0.3s ease;
        }

        .input-wrapper:focus-within {
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1);
            border-color: rgba(37, 99, 235, 0.3);
        }

        .field-icon {
            width: 20px;
            height: 20px;
            margin-left: 15px;
            fill: var(--text-dark);
            opacity: 0.6;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            border: none;
            background: transparent;
            font-size: 0.95rem;
            color: var(--text-dark);
            outline: none;
        }

        .btn-submit {
            width: 100%;
            padding: 14px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
            transition: all 0.2s;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #059669;
            transform: translateY(-2px);
        }

        .login-link {
            margin-top: 25px;
            font-size: 0.9rem;
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
        }

        .login-link:hover {
            text-decoration: underline;
        }

        .success-msg {
            background-color: rgba(209, 250, 229, 0.9);
            color: #059669;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            border: 1px solid #6ee7b7;
        }

        .error-msg {
            background-color: rgba(254, 202, 202, 0.9);
            color: #dc2626;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>
    <form class="glass-card" method="POST">
        <div class="logo-container">
            <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
            </svg>
        </div>

        <h2 class="card-title">Student Registration</h2>
        <p class="card-subtitle">Create your account</p>

        <?php if(!empty($success_message)): ?>
            <div class="success-msg"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <?php if(!empty($error_message)): ?>
            <div class="error-msg"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label class="form-label">Full Name</label>
            <div class="input-wrapper">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                </svg>
                <input type="text" class="form-input" name="name" placeholder="Enter your full name" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Email Address</label>
            <div class="input-wrapper">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
                <input type="email" class="form-input" name="email" placeholder="your.email@example.com" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Department</label>
            <div class="input-wrapper">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/>
                </svg>
                <select class="form-input" name="department" required style="cursor: pointer;">
                    <option value="">Select Department</option>
                    <option value="Computer Science">Computer Science</option>
                    <option value="Information Technology">Information Technology</option>
                    <option value="Software Engineering">Software Engineering</option>
                    <option value="Business Administration">Business Administration</option>
                    <option value="Engineering">Engineering</option>
                    <option value="Mathematics">Mathematics</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Password</label>
            <div class="input-wrapper">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
                <input type="password" class="form-input" name="password" placeholder="Minimum 5 characters" required>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <div class="input-wrapper">
                <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/>
                </svg>
                <input type="password" class="form-input" name="confirm_password" placeholder="Re-enter password" required>
            </div>
        </div>

        <button type="submit" class="btn-submit">Register</button>

        <a href="login_student.php" class="login-link">Already have an account? Login here</a>
    </form>
</body>
</html>
