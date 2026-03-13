-- COMPLETE FRESH DATABASE SETUP
-- Copy this ENTIRE code into phpMyAdmin SQL tab and click "Go"

-- Drop existing database and recreate
DROP DATABASE IF EXISTS students_feedback_db;
CREATE DATABASE students_feedback_db;
USE students_feedback_db;

-- Create users table with all columns
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'admin') DEFAULT 'student',
    department VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create courses table with all columns
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    course_name VARCHAR(100) NOT NULL,
    course_code VARCHAR(20) UNIQUE NOT NULL,
    instructor VARCHAR(100),
    department VARCHAR(100),
    semester INT DEFAULT 1,
    session VARCHAR(50) DEFAULT 'Fall 2025',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    course_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comments TEXT,
    sentiment VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create feedback_questions table
CREATE TABLE feedback_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create feedback_responses table
CREATE TABLE feedback_responses (
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
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES feedback_questions(question_id) ON DELETE CASCADE
);

-- Create student_progress table
CREATE TABLE student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    badges TEXT,
    streak_days INT DEFAULT 0,
    last_activity DATE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Create schedule table
CREATE TABLE schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    day_of_week VARCHAR(20),
    start_time TIME,
    end_time TIME,
    room VARCHAR(50),
    semester INT,
    session VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
);

-- Create feedback reminders log table
CREATE TABLE feedback_reminders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_email VARCHAR(100) NOT NULL,
    courses_pending INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin user (password: 12345)
INSERT INTO users (name, email, password, role, department) VALUES 
('Admin User', 'admin@example.com', '$2y$10$vI8aWBnW3fID.ZQ4/zo1G.q1lRps.9cGLcZEiGDMVr5yUP1KUOYTa', 'admin', 'Administration');

-- Insert demo courses
INSERT INTO courses (course_name, course_code, instructor, department, semester, session) VALUES 
('Introduction to Programming', 'CS101', 'Dr. Smith', 'Computer Science', 1, 'Fall 2025'),
('Data Structures', 'CS201', 'Prof. Johnson', 'Computer Science', 2, 'Fall 2025'),
('Web Development', 'CS301', 'Dr. Williams', 'Computer Science', 3, 'Fall 2025'),
('Database Systems', 'CS202', 'Dr. Brown', 'Computer Science', 2, 'Fall 2025'),
('Software Engineering', 'CS401', 'Prof. Davis', 'Computer Science', 4, 'Fall 2025');

-- Update course_id to match id
UPDATE courses SET course_id = id;

-- Insert feedback questions
INSERT INTO feedback_questions (question, category) VALUES 
('How would you rate the course content?', 'Content'),
('How effective was the instructor?', 'Teaching'),
('Were the assignments helpful?', 'Assignments'),
('How was the course organization?', 'Organization'),
('Would you recommend this course?', 'Overall');

-- Insert demo schedules
INSERT INTO schedule (course_id, day_of_week, start_time, end_time, room, semester, session) VALUES 
(1, 'Monday', '09:00:00', '10:30:00', 'Room 101', 1, 'Fall 2025'),
(1, 'Wednesday', '09:00:00', '10:30:00', 'Room 101', 1, 'Fall 2025'),
(2, 'Tuesday', '11:00:00', '12:30:00', 'Room 202', 2, 'Fall 2025'),
(2, 'Thursday', '11:00:00', '12:30:00', 'Room 202', 2, 'Fall 2025'),
(3, 'Monday', '14:00:00', '15:30:00', 'Lab 301', 3, 'Fall 2025'),
(3, 'Friday', '14:00:00', '15:30:00', 'Lab 301', 3, 'Fall 2025');
