<?php
session_start();
// Include your database connection
// Note: We do NOT need session_start() here because your db.php already does it.
include 'db.php'; 

$error_message = "";

// Check if the form was submitted via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Get data from the form
    $username = $_POST['username']; 
    $password = $_POST['password']; 

    // 2. Protect against basic SQL injection
    $username = mysqli_real_escape_string($conn, $username);
    $password = mysqli_real_escape_string($conn, $password);

    // 3. The Query
    // We check the 'users' table.
    // We check email and verify password hash, AND ensure role is 'student'
    $sql = "SELECT * FROM users WHERE email = '$username' AND role = 'student'";
    
    // 4. Execute the query
    $result = mysqli_query($conn, $sql);

    // 5. Check if a user was found
    if (mysqli_num_rows($result) == 1) {
        // Fetch the user data to store in session
        $row = mysqli_fetch_assoc($result);

        // Verify password
        if (password_verify($password, $row['password'])) {
            // Success! Save user info to session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['email'];
            $_SESSION['full_name'] = $row['name'];
            $_SESSION['role'] = 'student';
            
            // Redirect to the student dashboard
            header("Location: dashboard_student.php"); 
            exit();
        } else {
            $error_message = "Invalid Email or Password";
        }
    } else {
        // Failed login
        $error_message = "Invalid Email or Password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* --- CSS STYLES --- */
        :root {
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: 1px solid rgba(255, 255, 255, 0.4);
            --glass-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --primary-blue: #2563eb;
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
            background: linear-gradient(to bottom, #e0f7fa 0%, #b3e5fc 100%);
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
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            margin-bottom: 20px; box-shadow: 0 10px 20px rgba(245, 158, 11, 0.3);
        }
        .logo-icon { width: 45px; height: 45px; fill: white; }

        .card-title { color: var(--primary-blue); font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
        .card-subtitle { color: var(--text-light); font-size: 0.9rem; margin-bottom: 30px; font-weight: 400; }

        .form-group { width: 100%; margin-bottom: 20px; }
        .input-wrapper {
            width: 100%; background: rgba(255, 255, 255, 0.6);
            border-radius: 12px; display: flex; align-items: center;
            border: 1px solid transparent; transition: all 0.3s ease;
        }
        .input-wrapper:focus-within { background: rgba(255, 255, 255, 0.9); box-shadow: 0 4px 15px rgba(37, 99, 235, 0.1); border-color: rgba(37, 99, 235, 0.3); }
        .field-icon { width: 20px; height: 20px; margin-left: 15px; fill: var(--text-dark); opacity: 0.6; }
        .form-input { width: 100%; padding: 15px; border: none; background: transparent; font-size: 0.95rem; color: var(--text-dark); outline: none; }

        .btn-submit {
            width: 100%; padding: 14px; background: var(--primary-blue);
            color: white; border: none; border-radius: 50px; font-size: 1rem;
            font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 10px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3); transition: all 0.2s; margin-top: 10px;
        }
        .btn-submit:hover { background: #1d4ed8; transform: translateY(-2px); }
        .btn-submit svg { width: 20px; height: 20px; fill: currentColor; }

        .admin-link {
            margin-top: 25px; font-size: 0.9rem; color: var(--primary-blue);
            text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 8px;
            padding: 8px 16px; border-radius: 20px; transition: background 0.2s;
        }
        .admin-link:hover { background: rgba(37, 99, 235, 0.1); }
        .admin-link svg { width: 16px; height: 16px; fill: currentColor; }
        
        .error-msg {
            background-color: rgba(254, 202, 202, 0.9); color: #dc2626; padding: 10px; border-radius: 8px;
            margin-bottom: 15px; width: 100%; font-size: 0.9rem; border: 1px solid #fca5a5;
        }
    </style>
</head>
<body>

    <!-- SCENIC BACKGROUND -->
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
        <form class="glass-card" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            
            <div class="logo-container">
                <svg class="logo-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M12 3L1 9l11 6 9-4.91V17h2V9L12 3zM5 13.18v4L12 21l7-3.82v-4L12 17l-7-3.82z"/>
                </svg>
            </div>

            <h2 class="card-title">Student Portal</h2>
            <p class="card-subtitle">University Feedback System</p>

            <!-- Show PHP Error Message Here -->
            <?php if(!empty($error_message)): ?>
                <div class="error-msg"><?php echo $error_message; ?></div>
            <?php endif; ?>

            <div class="form-group">
                <div class="input-wrapper">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                    <input type="text" class="form-input" name="username" placeholder="Enter Email (e.g. student@example.com)" required>
                </div>
            </div>

            <div class="form-group">
                <div class="input-wrapper">
                    <svg class="field-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zm-6 9c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2zm3.1-9H8.9V6c0-1.71 1.39-3.1 3.1-3.1 1.71 0 3.1 1.39 3.1 3.1v2z"/></svg>
                    <input type="password" class="form-input" name="password" placeholder="Enter Password" required>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                Login
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M10.09 15.59L11.5 17l5-5-5-5-1.41 1.41L12.67 11H3v2h9.67l-2.58 2.59zM19 3H5c-1.11 0-2 .9-2 2v4h2V5h14v14H5v-4H3v4c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/></svg>
            </button>

            <!-- Link to Registration -->
            <a href="register_student.php" class="admin-link" style="margin-top: 15px;">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M15 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm-9-2V7H4v3H1v2h3v3h2v-3h3v-2H6zm9 4c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Register New Account
            </a>

            <!-- Link to Admin Login -->
            <a href="login_admin.php" class="admin-link">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                Admin Login
            </a>

        </form>
    </main>
</body>
</html>