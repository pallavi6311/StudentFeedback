-- Complete Database Fix Script
-- Copy this entire code into phpMyAdmin SQL tab and click "Go"

USE students_feedback_db;

-- Add missing columns to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS department VARCHAR(100);

-- Add missing columns to courses table  
ALTER TABLE courses ADD COLUMN IF NOT EXISTS course_id INT;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS department VARCHAR(100);
ALTER TABLE courses ADD COLUMN IF NOT EXISTS semester INT DEFAULT 1;
ALTER TABLE courses ADD COLUMN IF NOT EXISTS session VARCHAR(50) DEFAULT 'Fall 2025';

-- Create missing tables if they don't exist
CREATE TABLE IF NOT EXISTS feedback_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    question TEXT NOT NULL,
    category VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS feedback_responses (
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
);

CREATE TABLE IF NOT EXISTS student_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    level INT DEFAULT 1,
    xp INT DEFAULT 0,
    badges TEXT,
    streak_days INT DEFAULT 0,
    last_activity DATE,
    FOREIGN KEY (student_id) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS schedule (
    schedule_id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT,
    day_of_week VARCHAR(20),
    start_time TIME,
    end_time TIME,
    room VARCHAR(50),
    semester INT,
    session VARCHAR(50),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Update existing users with department
UPDATE users SET department = 'Computer Science' WHERE email = 'student@example.com';
UPDATE users SET department = 'Administration' WHERE email = 'admin@example.com';

-- Insert demo courses if not exist
INSERT IGNORE INTO courses (course_name, course_code, instructor, department, semester, session) VALUES 
('Introduction to Programming', 'CS101', 'Dr. Smith', 'Computer Science', 1, 'Fall 2025'),
('Data Structures', 'CS201', 'Prof. Johnson', 'Computer Science', 2, 'Fall 2025'),
('Web Development', 'CS301', 'Dr. Williams', 'Computer Science', 3, 'Fall 2025');

-- Update course_id in courses table
UPDATE courses SET course_id = id WHERE course_id IS NULL;

-- Insert demo feedback questions
INSERT IGNORE INTO feedback_questions (question, category) VALUES 
('How would you rate the course content?', 'Content'),
('How effective was the instructor?', 'Teaching'),
('Were the assignments helpful?', 'Assignments');
