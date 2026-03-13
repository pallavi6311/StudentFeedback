# 📧 How to Send Real Emails to Gmail

## Option 1: Using PHPMailer (Recommended)

### Step 1: Download PHPMailer
1. Go to: https://github.com/PHPMailer/PHPMailer
2. Download ZIP and extract to your project folder
3. Or use Composer: `composer require phpmailer/phpmailer`

### Step 2: Get Gmail App Password
1. Go to your Google Account: https://myaccount.google.com/
2. Click "Security"
3. Enable "2-Step Verification" (if not enabled)
4. Search for "App passwords"
5. Select "Mail" and "Windows Computer"
6. Click "Generate"
7. Copy the 16-character password

### Step 3: Configure Email Settings
Edit `send_feedback_reminders.php` and replace the `send_reminder_email` function with:

```php
function send_reminder_email($email, $name, $courses) {
    global $conn;
    
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    require 'PHPMailer/Exception.php';
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Gmail SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com';      // YOUR GMAIL
        $mail->Password   = 'your-app-password-here';    // YOUR APP PASSWORD
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Email content
        $mail->setFrom('your-email@gmail.com', 'Student Feedback System');
        $mail->addAddress($email, $name);
        
        $course_list = "";
        foreach ($courses as $course) {
            $course_list .= "- {$course['course_name']} ({$course['course_code']})\n";
        }
        
        $mail->Subject = '⚠️ URGENT: Submit Your Course Feedback';
        $mail->Body    = "Dear $name,\n\n"
                       . "You have NOT submitted feedback for:\n\n"
                       . $course_list
                       . "\nPlease login: http://localhost:8000/login_student.php\n\n"
                       . "This is MANDATORY.\n\nThank you,\nAdmin";
        
        $mail->send();
        
        // Log to database
        $email_safe = mysqli_real_escape_string($conn, $email);
        mysqli_query($conn, "INSERT INTO feedback_reminders (student_email, courses_pending, sent_at) 
                            VALUES ('$email_safe', " . count($courses) . ", NOW())");
        
        echo "✅ Email sent to: $email<br>";
        return true;
        
    } catch (Exception $e) {
        echo "❌ Failed to send to $email: {$mail->ErrorInfo}<br>";
        return false;
    }
}
```

---

## Option 2: Using Gmail API (More Reliable)

### Step 1: Enable Gmail API
1. Go to: https://console.cloud.google.com/
2. Create new project
3. Enable Gmail API
4. Create credentials (OAuth 2.0)
5. Download credentials.json

### Step 2: Install Google Client Library
```bash
composer require google/apiclient
```

---

## Option 3: Using SendGrid (Free 100 emails/day)

### Step 1: Sign up
1. Go to: https://sendgrid.com/
2. Sign up for free account
3. Get API key

### Step 2: Install SendGrid
```bash
composer require sendgrid/sendgrid
```

### Step 3: Use SendGrid
```php
require 'vendor/autoload.php';

$email = new \SendGrid\Mail\Mail();
$email->setFrom("test@example.com", "Feedback System");
$email->setSubject("Pending Feedback");
$email->addTo($to_email, $to_name);
$email->addContent("text/plain", $message);

$sendgrid = new \SendGrid('YOUR_SENDGRID_API_KEY');
$response = $sendgrid->send($email);
```

---

## Current Setup (Testing Mode)

Right now, emails are saved to `email_logs.txt` file instead of being sent.

To check sent emails:
1. Open `email_logs.txt` in your project folder
2. You'll see all email content there

---

## Quick Test

After configuring, test by:
1. Login as admin
2. Go to "Student Management"
3. Click "Email" button for any student
4. Check if email arrives in Gmail

---

## Troubleshooting

**Error: "SMTP connect() failed"**
- Check Gmail app password is correct
- Enable "Less secure app access" (not recommended)
- Use app password instead of regular password

**Error: "Could not authenticate"**
- Make sure 2-Step Verification is enabled
- Generate new app password
- Check username is full email address

**Emails go to Spam**
- Add SPF/DKIM records to your domain
- Use verified sender email
- Ask recipients to mark as "Not Spam"
