<?php
session_start();
// --- PHP LOGIC (Kept from your original Admin Login) ---
include 'db.php';

$error_message = "";

// Check if the form was submitted (checking for the 'login' button name)
if (isset($_POST['login'])) {
    
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = $_POST['password']; // Note: In production, consider hashing passwords

    // Query to check for ADMIN user
    $sql = "SELECT * FROM users WHERE email='$username' AND role='admin'";
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        // Success! Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['email'];
        $_SESSION['user_type'] = 'admin'; // Ensure role is set
        
        header("Location: dashboard_admin.php");
        exit();
    } else {
        // Failed login
        $error_message = "Invalid Admin Credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS STYLES (Copied from Student Login) --- */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: 1px solid rgba(255, 255, 255, 0.4);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --primary-blue: #2563eb;
            --admin-red: #ef4444; /* Added for Admin theme */
            --text-dark: #1e293b;
            --text-light: #64748b;
            --mountain-back: #a5d6f5;
            --mountain-mid: #6ab7e9;
            --mountain-front: #2c7da0;
            --tree-color: #0d1b2a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
            /* Slightly darker sky for Admin to differentiate */
            background: linear-gradient(to bottom, #dbeafe 0%, #93c5fd 100%);
            position: relative;
        }

        /* Background Elements */
        .scene-layer { position: absolute; bottom: 0; left: 0; width: 100%; pointer-events: none; z-index: 0; }
        .clouds { top: 0; height: 100%; z-index: 1; pointer-events: none; }
        .cloud { position: absolute; background: rgba(255, 255, 255, 0.8); border-radius: 50%; filter: blur(20px); animation: floatCloud 25s infinite alternate ease-in-out; }
        .cloud:nth-child(1) { width: 300px; height: 300px; top: -100px; left: 10%; }
        .cloud:nth-child(2) { width: 400px; height: 400px; top: -150px; right: 20%; animation-duration: 30s; }
        @keyframes floatCloud { from { transform: translateX(-30px); } to { transform: translateX(30px); } }

        .mountains-svg { width: 100%; height: auto; display: block; position: absolute; bottom: 0; }
        .layer-back { z-index: 2; fill: var(--mountain-back); transform: scale(1.2); transform-origin: bottom; }
        .layer-mid { z-index: 3; fill: var(--mountain-mid); transform: scale(1.1); transform-origin: bottom; }
        .layer-front { z-index: 4; fill: var(--mountain-front); }

        .trees-layer { z-index: 5; bottom: -5px; display: flex; justify-content: space-between; align-items: flex-end; width: 100%; }
        .tree-svg { fill: var(--tree-color); height: auto; }
        .tree-left { width: 25%; max-width: 300px; opacity: 0.9; }
        .tree-right { width: 30%; max-width: 400px; opacity: 0.9; }

        .birds { position: absolute; top: 20%; width: 100%; z-index: 2; pointer-events: none; }
        .bird { position: absolute; width: 20px; height: 6px; background: transparent; border-radius: 50%; box-shadow: 3px 3px 0 0 #333; animation: fly 20s linear infinite; }
        .bird::after { content: ''; position: absolute; left: 10px; width: 20px; height: 6px; border-radius: 50%; box-shadow: -3px 3px 0 0 #333; }
        .bird:nth-child(1) { top: 10%; left: -10%; }
        @keyframes fly { 0% { left: -10%; transform: translateY(0) scale(1); } 50% { transform: translateY(-30px) scale(1); } 100% { left: 110%; transform: translateY(-50px) scale(1); } }

        /* Login Card */
        .main-container {
            position: absolute; top: 0; left: 0; width: 100%; height: 100%;
            display: flex; justify-content: center; align-items: center;
            z-index: 50; padding: 20px;
        }

        .glass-card {
            width: 100%; max-width: 380px;
            background: var(--glass-bg);
            backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
            border: var(--glass-border); border-radius: 24px;
            box-shadow: var(--glass-shadow); padding: 40px 30px;
            text-align: center; display: flex; flex-direction: column; align-items: center;
        }

        .logo-container {
            width: 80px; height: 80px;
            /* Changed to Red Gradient for Admin */
            background: linear-gradient(135deg, #f87171, #dc2626);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px; box-shadow: 0 10px 20px rgba(220, 38, 38, 0.3);
        }
        .logo-icon { width: 40px; height: 40px; fill: white; }

        .card-title { color: var(--text-dark); font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
        .card-subtitle { color: var(--text-light); font-size: 0.9rem; margin-bottom: 30px; font-weight: 400; }

        .form-group { width: 100%; margin-bottom: 20px; }
        .input-wrapper {
            width: 100%; background: rgba(255, 255, 255, 0.6);
            border-radius: 12px; display: flex; align-items: center;
            border: 1px solid transparent; transition: all 0.3s ease;
        }
        .input-wrapper:focus-within { background: rgba(255, 255, 255, 0.9); box-shadow: 0 4px 15px rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.3); }
        .field-icon { width: 20px; height: 20px; margin-left: 15px; fill: var(--text-dark); opacity: 0.6; }
        .form-input { width: 100%; padding: 15px; border: none; background: transparent; font-size: 0.95rem; color: var(--text-dark); outline: none; }

        .btn-submit {
            width: 100%; padding: 14px; 
            /* Changed to Red/Dark Blue for Admin */
            background: #1e40af; 
            color: white; border: none; border-radius: 50px; font-size: 1rem;
            font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3); transition: all 0.2s; margin-top: 10px;
        }
        .btn-submit:hover { background: #1e3a8a; transform: translateY(-2px); }
        .btn-submit svg { width: 20px; height: 20px; fill: currentColor; }

        .student-link {
            margin-top: 25px; font-size: 0.9rem; color: var(--primary-blue);
            text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 20px; transition: background 0.2s;
        }
        .student-link:hover { background: rgba(37, 99, 235, 0.1); }
        .student-link svg { width: 16px; height: 16px; fill: currentColor; }
        
        .error-msg {
            background-color: rgba(254, 202, 202, 0.9); color: #dc2626; padding: 10px; border-radius: 8px;
            margin-bottom: 15px; width: 100%; font-size: 0.9rem; border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>

    <!-- SCENIC BACKGROUND (Same as Student) -->
    <div class="clouds"><div class="cloud"></div><div class="cloud"></div></div>
    <div class="birds"><div class="bird"></div></div>
    <svg class="scene-layer mountains-svg layer-back" viewBox="0 0 1440 320" preserveAspectRatio="none"><path d="M0,224L48,213.3C96,203,192,181,288,181.3C384,181,480,203,576,224C672,245,768,267,864,261.3C960,256,1056,224,1152,197.3C1248,171,1344,149,1392,138.7L1440,128L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
    <svg class="scene-layer mountains-svg layer-mid" viewBox="0 0 1440 320" preserveAspectRatio="none"><path d="M0,256L48,261.3C96,267,192,277,288,266.7C384,256,480,224,576,218.7C672,213,768,235,864,245.3C960,256,1056,256,1152,245.3C1248,235,1344,213,1392,202.7L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
    <svg class="scene-layer mountains-svg layer-front" viewBox="0 0 1440 320" preserveAspectRatio="none"><path d="M0,288L48,272C96,256,192,224,288,213.3C384,203,480,213,576,229.3C672,245,768,267,864,250.7C960,235,1056,181,1152,165.3C1248,149,1344,171,1392,181.3L1440,192L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>
    <div class="scene-layer trees-layer">
        <svg class="tree-svg tree-left" viewBox="0 0 100 150" preserveAspectRatio="none"><path d="M50,0 L65,30 L55,30 L75,60 L60,60 L90,100 L70,100 L100,150 L0,150 L30,100 L10,100 L40,60 L25,60 L45,30 L35,30 Z" /></svg>
        <svg class="tree-svg tree-right" viewBox="0 0 200 150" preserveAspectRatio="none"><path d="M50,20 L70,50 L60,50 L80,90 L20,90 L40,50 L30,50 Z M150,0 L170,40 L160,40 L190,80 L170,80 L200,150 L100,150 L130,80 L110,80 L140,40 L130,40 Z" /></svg>
    </div>

    <!-- LOGIN FORM -->
    <main class="main-container">
        <!-- The form submits to itself -->
        <form class="glass-card" action="" method="POST">
            
            <div class="logo-container">
                <!-- Shield Icon for Admin -->
                <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                </svg>
            </div>

            <h2 class="card-title">Admin Portal</h2>
            <p class="card-subtitle">Manage Feedback Reports</p>

            <!-- Show PHP Error Message Here -->
            <?php if(!empty($error_message)): ?>
                <div class="error-msg"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="form-group">
                <div class="input-wrapper">
                    <!-- User Icon -->
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <input type="text" class="form-input" name="username" placeholder="Admin Email" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <!-- Lock Icon -->
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <input type="password" class="form-input" name="password" placeholder="Admin Password" required>
                </div>
            </div>

            <!-- Added name='login' to trigger PHP -->
            <button type="submit" name="login" class="btn-submit">
                Admin Login
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
            </button>

            <!-- Link to Admin Registration -->
            <a href="register_admin.php" class="student-link" style="margin-top: 15px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Register New Admin
            </a>

            <!-- Link to Student Login -->
            <a href="login_student.php" class="student-link">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82zM12 3L1 9l11 6 9-4.91V17h2V9L12 3z"/></svg>
                Student Login
            </a>

        </form>
    </main>
</body>
</html>