<?php
// Include email configuration
require_once 'includes/email-config.php';

// Include PHPMailer classes
require_once 'includes/PHPMailer/Exception.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Set headers for API response
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Debug log file
$logFile = 'debug_log.txt';
function logDebug($message, $data = null) {
    global $logFile;
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
}

file_put_contents($logFile, "Form submission received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? 'Contact Form Submission', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);
    
    logDebug("Received form data", [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => substr($message, 0, 30) . '...'
    ]);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        logDebug("Validation failed - missing required fields");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logDebug("Validation failed - invalid email: $email");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }
    
    // Save to CSV (as backup in case email fails)
    try {
        // Create directory if it doesn't exist
        $directory = 'data';
        if (!file_exists($directory)) {
            if (!mkdir($directory, 0755, true)) {
                throw new Exception("Failed to create directory: $directory");
            }
        }
        
        // Define CSV file path
        $csvFile = $directory . '/contact_submissions.csv';
        $fileExists = file_exists($csvFile);
        
        // Open file for appending
        $handle = fopen($csvFile, 'a');
        if (!$handle) {
            throw new Exception("Could not open file: $csvFile");
        }
        
        // Add headers if new file
        if (!$fileExists) {
            fputcsv($handle, ['Name', 'Email', 'Subject', 'Message', 'Timestamp']);
        }
        
        // Write data
        $timestamp = date('Y-m-d H:i:s');
        fputcsv($handle, [$name, $email, $subject, $message, $timestamp]);
        fclose($handle);
        
        logDebug("Form data saved to CSV file");
    } catch (Exception $e) {
        logDebug("Failed to save to CSV: " . $e->getMessage());
        // Continue to email step - don't exit
    }
    
    // Send emails using PHPMailer
    try {
        // VISITOR CONFIRMATION EMAIL
        $mail = new PHPMailer(true);
        
        // SMTP settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_SECURE;
        $mail->Port = SMTP_PORT;
        $mail->SMTPDebug = 0;
        
        // Email content
        $mail->setFrom(EMAIL_FROM, EMAIL_NAME);
        $mail->addAddress($email, $name);
        $mail->isHTML(true);
        $mail->Subject = "Thank you for contacting EazyHaven";
        $mail->Body = "
        <html>
        <head>
            <title>Thank You for Contacting EazyHaven</title>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #86198f; padding: 20px; color: white; text-align: center;'>
                    <h1>Thank You for Contacting Us!</h1>
                </div>
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p>Dear $name,</p>
                    <p>Thank you for reaching out to EazyHaven. We have received your message and will get back to you as soon as possible.</p>
                    <p>Here's a summary of your submission:</p>
                    <ul>
                        <li><strong>Name:</strong> $name</li>
                        <li><strong>Email:</strong> $email</li>
                        <li><strong>Subject:</strong> $subject</li>
                        <li><strong>Message:</strong> $message</li>
                    </ul>
                    <p>We appreciate your interest in EazyHaven and look forward to connecting with you.</p>
                    <p>Warm regards,<br>The EazyHaven Team</p>
                </div>
                <div style='background-color: #333; color: #999; padding: 15px; text-align: center; font-size: 12px;'>
                    <p>&copy; " . date('Y') . " EazyHaven. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $mail->send();
        logDebug("Confirmation email sent to visitor");
        
        // ADMIN NOTIFICATION EMAIL
        $adminMail = new PHPMailer(true);
        $adminMail->isSMTP();
        $adminMail->Host = SMTP_HOST;
        $adminMail->SMTPAuth = true;
        $adminMail->Username = SMTP_USER;
        $adminMail->Password = SMTP_PASSWORD;
        $adminMail->SMTPSecure = SMTP_SECURE;
        $adminMail->Port = SMTP_PORT;
        
        $adminMail->setFrom(EMAIL_FROM, 'EazyHaven Website');
        $adminMail->addAddress(ADMIN_EMAIL, 'EazyHaven Admin');
        $adminMail->addReplyTo($email, $name);
        
        $adminMail->isHTML(true);
        $adminMail->Subject = "New Contact Form Submission from $name";
        $adminMail->Body = "
        <html>
        <head>
            <title>New Contact Form Submission</title>
        </head>
        <body>
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <div style='background-color: #86198f; padding: 20px; color: white; text-align: center;'>
                    <h1>New Contact Form Submission</h1>
                </div>
                <div style='padding: 20px; background-color: #f9f9f9;'>
                    <p>You have received a new contact form submission with the following details:</p>
                    <ul>
                        <li><strong>Name:</strong> $name</li>
                        <li><strong>Email:</strong> $email</li>
                        <li><strong>Subject:</strong> $subject</li>
                        <li><strong>Message:</strong> $message</li>
                        <li><strong>Submitted:</strong> $timestamp</li>
                    </ul>
                    <p>Please respond to this inquiry as soon as possible.</p>
                </div>
            </div>
        </body>
        </html>";
        
        $adminMail->send();
        logDebug("Notification email sent to admin");
        
        // Success response
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        
    } catch (Exception $e) {
        logDebug("Email sending failed: " . $e->getMessage());
        
        // Send success anyway since we saved to CSV
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
    }
    
    exit;
} else {
    // Not a POST request
    logDebug("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>