import smtplib
from email.mime.text import MIMEText
from email.mime.multipart import MIMEMultipart
import mysql.connector
from datetime import datetime

# Email Configuration
SENDER_EMAIL = "xig1pallavi.p@gmail.com"
SENDER_PASSWORD = "bicvogzjtwhfqzul"  # Your app password
SENDER_NAME = "Student Feedback System"

# Database Configuration
DB_CONFIG = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'students_feedback_db'
}

def send_email(to_email, to_name, courses):
    """Send email to student with pending feedback"""
    
    # Create message
    msg = MIMEMultipart()
    msg['From'] = f"{SENDER_NAME} <{SENDER_EMAIL}>"
    msg['To'] = to_email
    msg['Subject'] = "⚠️ URGENT: Pending Course Feedback Required"
    
    # Email body
    course_list = "\n".join([f"- {c['course_name']} ({c['course_code']}) - Semester {c['semester']}" 
                             for c in courses])
    
    body = f"""Dear {to_name},

This is a MANDATORY reminder that you have NOT submitted feedback for the following courses:

{course_list}

⚠️ IMPORTANT: Submitting feedback is COMPULSORY for all students.
Please login immediately and complete your feedback:
http://localhost:8000/login_student.php

Failure to submit feedback may affect your academic records.

Thank you,
Student Feedback System
"""
    
    msg.attach(MIMEText(body, 'plain'))
    
    try:
        # Connect to Gmail SMTP
        server = smtplib.SMTP('smtp.gmail.com', 587)
        server.starttls()
        server.login(SENDER_EMAIL, SENDER_PASSWORD)
        
        # Send email
        server.send_message(msg)
        server.quit()
        
        print(f"✅ Email sent successfully to: {to_email}")
        return True
        
    except Exception as e:
        print(f"❌ Failed to send email to {to_email}: {str(e)}")
        return False

def get_students_with_pending_feedback():
    """Get all students who haven't submitted feedback"""
    
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)
        
        # Get students with pending courses
        query = """
            SELECT DISTINCT 
                u.id, u.name, u.email, u.department
            FROM users u
            CROSS JOIN courses c
            WHERE u.role = 'student' 
                AND u.department = c.department
                AND NOT EXISTS (
                    SELECT 1 FROM feedback_responses fr 
                    WHERE fr.student_id = u.id 
                    AND fr.course_id = c.id
                )
            ORDER BY u.email
        """
        
        cursor.execute(query)
        students = cursor.fetchall()
        
        # Get pending courses for each student
        student_data = {}
        for student in students:
            if student['email'] not in student_data:
                student_data[student['email']] = {
                    'name': student['name'],
                    'email': student['email'],
                    'courses': []
                }
            
            # Get pending courses
            course_query = """
                SELECT c.course_name, c.course_code, c.semester
                FROM courses c
                WHERE c.department = %s
                AND NOT EXISTS (
                    SELECT 1 FROM feedback_responses fr 
                    WHERE fr.student_id = %s AND fr.course_id = c.id
                )
            """
            cursor.execute(course_query, (student['department'], student['id']))
            courses = cursor.fetchall()
            student_data[student['email']]['courses'] = courses
        
        cursor.close()
        conn.close()
        
        return list(student_data.values())
        
    except Exception as e:
        print(f"❌ Database error: {str(e)}")
        return []

def main():
    """Main function to send reminder emails"""
    
    print("=" * 60)
    print("📧 Student Feedback Reminder System")
    print("=" * 60)
    print(f"Sender: {SENDER_EMAIL}")
    print(f"Time: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}")
    print("=" * 60)
    
    # Get students with pending feedback
    students = get_students_with_pending_feedback()
    
    if not students:
        print("\n✅ No students with pending feedback!")
        return
    
    print(f"\nFound {len(students)} student(s) with pending feedback\n")
    
    # Send emails
    sent_count = 0
    failed_count = 0
    
    for student in students:
        if len(student['courses']) > 0:
            print(f"\nSending to: {student['name']} ({student['email']})")
            print(f"Pending courses: {len(student['courses'])}")
            
            if send_email(student['email'], student['name'], student['courses']):
                sent_count += 1
            else:
                failed_count += 1
    
    # Summary
    print("\n" + "=" * 60)
    print(f"✅ Successfully sent: {sent_count}")
    print(f"❌ Failed: {failed_count}")
    print("=" * 60)

if __name__ == "__main__":
    main()
