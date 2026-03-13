<?php
// Gmail SMTP Email Sender
// Download PHPMailer first: https://github.com/PHPMailer/PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If you don't have PHPMailer, download it or use this simple method:

function send_gmail($to, $to_name, $subject, $message) {
    // Configure your Gmail settings here
    $from_email = "your-email@gmail.com";  // YOUR GMAIL
    $from_password = "your-app-password";   // YOUR GMAIL APP PASSWORD
    $from_name = "Student Feedback System";
    
    // Using PHPMailer (recommended)
    require 'PHPMailer/PHPMailer.php';
    require 'PHPMailer/SMTP.php';
    require 'PHPMailer/Exception.php';
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = $from_email;
        $mail->Password   = $from_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($to, $to_name);
        
        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        return "Error: {$mail->ErrorInfo}";
    }
}

// Alternative: Simple mail() function (requires PHP mail configured)
function send_simple_email($to, $subject, $message) {
    $headers = "From: noreply@studentfeedback.edu\r\n";
    $headers .= "Reply-To: admin@studentfeedback.edu\r\n";
    $headers .= "X-Priority: 1\r\n";
    
    return mail($to, $subject, $message, $headers);
}
?>
