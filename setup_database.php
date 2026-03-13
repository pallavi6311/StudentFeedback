<?php
// Database setup script
$servername = "localhost";
$username   = "root";
$password   = "";
$port       = 3306;

// Connect without database
$conn = mysqli_connect($servername, $username, $password, null, $port);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS students_feedback_db";
if (mysqli_query($conn, $sql)) {
    echo "✅ Database created successfully<br>";
} else {
    echo "❌ Error creating database: " . mysqli_error($conn) . "<br>";
}

// Select database
mysqli_select_db($conn, "students_feedback_db");

// Create ALL tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('student', 'admin') DEFAULT 'student',
        department VARCHAR(100),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        course_name VARCHAR(100) NOT NULL,
        course_code VARCHAR(20) UNIQUE NOT NULL,
        instructor VARCHAR(100),
        department VARCHAR(100),
        semester INT DEFAULT 1,
        session VARCHAR(50) DEFAULT 'Fall 2025',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        rating INT CHECK (rating BETWEEN 1 AND 5),
        comments TEXT,
        sentiment VARCHAR(20),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS feedback_questions (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        category VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS feedback_responses (
        response_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT,
        question_id INT,
        rating INT CHECK (rating BETWEEN 1 AND 5),
        text_response TEXT,
        sentiment VARCHAR(20),
        session VARCHAR(50),
        semester INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS student_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        level INT DEFAULT 1,
        xp INT DEFAULT 0,
        badges TEXT,
        streak_days INT DEFAULT 0,
        last_activity DATE,
        FOREIGN KEY (student_id) REFERENCES users(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS schedule (
        schedule_id INT AUTO_INCREMENT PRIMARY KEY,
        course_id INT,
        day_of_week VARCHAR(20),
        start_time TIME,
        end_time TIME,
        room VARCHAR(50),
        semester INT,
        session VARCHAR(50),
        FOREIGN KEY (course_id) REFERENCES courses(id)
    )"
];

foreach ($tables as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Table created successfully<br>";
    } else {
        echo "❌ Error creating table: " . mysqli_error($conn) . "<br>";
    }
}

// Add missing columns if they don't exist
$alter_queries = [
    "ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100)",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS course_id INT",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS department VARCHAR(100)",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS semester INT DEFAULT 1",
    "ALTER TABLE courses ADD COLUMN IF NOT EXISTS session VARCHAR(50) DEFAULT 'Fall 2025'"
];

foreach ($alter_queries as $sql) {
    mysqli_query($conn, $sql); // Ignore errors if column exists
}

// Insert demo data with password "12345"
$hashed_password = password_hash('12345', PASSWORD_DEFAULT);

$demo_queries = [
    "INSERT IGNORE INTO users (name, email, password, role, department) VALUES 
        ('Admin User', 'admin@example.com', '$hashed_password', 'admin', 'Administration'),
        ('John Student', 'student@example.com', '$hashed_password', 'student', 'Computer Science')",
    
    "INSERT IGNORE INTO courses (course_id, course_name, course_code, instructor, department, semester, session) VALUES 
        (1, 'Introduction to Programming', 'CS101', 'Dr. Smith', 'Computer Science', 1, 'Fall 2025'),
        (2, 'Data Structures', 'CS201', 'Prof. Johnson', 'Computer Science', 2, 'Fall 2025'),
        (3, 'Web Development', 'CS301', 'Dr. Williams', 'Computer Science', 3, 'Fall 2025')",
    
    "INSERT IGNORE INTO feedback_questions (question, category) VALUES 
        ('How would you rate the course content?', 'Content'),
        ('How effective was the instructor?', 'Teaching'),
        ('Were the assignments helpful?', 'Assignments')"
];

foreach ($demo_queries as $sql) {
    if (mysqli_query($conn, $sql)) {
        echo "✅ Demo data inserted<br>";
    } else {
        echo "⚠️ Demo data: " . mysqli_error($conn) . "<br>";
    }
}

echo "<br><h2>🎉 Setup Complete!</h2>";
echo "<p>You can now <a href='index.php'>go to your application</a></p>";
echo "<p><strong>Demo credentials (Password: 12345):</strong><br>";
echo "Admin: admin@example.com / 12345<br>";
echo "Student: student@example.com / 12345</p>";
echo "<p><a href='register_student.php'>Register New Student</a></p>";

mysqli_close($conn);
?>
