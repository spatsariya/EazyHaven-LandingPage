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
file_put_contents($logFile, "Form submission received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Function to log data to the debug file
function logDebug($message, $data = null) {
    global $logFile;
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
}

// Log the request method and raw POST data for debugging
logDebug("Request method", $_SERVER['REQUEST_METHOD']);
logDebug("Raw POST data", file_get_contents('php://input'));
logDebug("POST array", $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? 'Contact Form Submission', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);
    
    logDebug("Sanitized form data", [
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
        
        // Send email using PHPMailer
        logDebug("Initializing PHPMailer");
        $mail = new PHPMailer(true); // true enables exceptions
        
        try {
            // Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                  // Enable verbose debug output (0 for no output)
            $mail->isSMTP();                                        // Send using SMTP
            $mail->Host       = SMTP_HOST;                          // Set the SMTP server to send through
            $mail->SMTPAuth   = true;                               // Enable SMTP authentication
            $mail->Username   = SMTP_USER;                          // SMTP username
            $mail->Password   = SMTP_PASSWORD;                      // SMTP password
            $mail->SMTPSecure = SMTP_SECURE;                        // Enable TLS/SSL encryption
            $mail->Port       = SMTP_PORT;                          // TCP port to connect to
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            // Capture SMTP debug output
            ob_start();
            $mail->Debugoutput = function($str, $level) {
                logDebug("PHPMailer debug [$level]", $str);
            };
            
            // Sender and recipient
            $mail->setFrom(EMAIL_FROM, EMAIL_NAME);
            $mail->addAddress(ADMIN_EMAIL);                         // Add a recipient (admin)
            $mail->addReplyTo($email, $name);                       // Reply to sender
            
            // Content
            $mail->isHTML(true);                                    // Set email format to HTML
            $mail->Subject = "Contact Form: $subject";
            
            // Create HTML and plaintext versions of the message
            $htmlMessage = "
            <html>
            <head>
                <title>Contact Form Submission</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #2d3748; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                    .info { margin-bottom: 20px; }
                    .info strong { color: #2d3748; }
                    .message { background-color: #f9f9f9; padding: 15px; border-left: 4px solid #2d3748; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>New Contact Form Submission</h2>
                    <div class='info'>
                        <p><strong>Name:</strong> $name</p>
                        <p><strong>Email:</strong> $email</p>
                        <p><strong>Subject:</strong> $subject</p>
                    </div>
                    <div class='message'>
                        <p><strong>Message:</strong></p>
                        <p>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            $plainMessage = "
            New Contact Form Submission
            --------------------------
            
            Name: $name
            Email: $email
            Subject: $subject
            
            Message:
            $message
            ";
            
            $mail->Body    = $htmlMessage;
            $mail->AltBody = $plainMessage;
            
            logDebug("Attempting to send email");
            $mail->send();
            logDebug("Email sent successfully");
            
            // Send auto-reply to the user
            $autoReply = new PHPMailer(true);
            $autoReply->isSMTP();
            $autoReply->Host       = SMTP_HOST;
            $autoReply->SMTPAuth   = true;
            $autoReply->Username   = SMTP_USER;
            $autoReply->Password   = SMTP_PASSWORD;
            $autoReply->SMTPSecure = SMTP_SECURE;
            $autoReply->Port       = SMTP_PORT;
            $autoReply->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
            
            $autoReply->setFrom(EMAIL_FROM, EMAIL_NAME);
            $autoReply->addAddress($email, $name);
            
            $autoReply->isHTML(true);
            $autoReply->Subject = "Thank you for contacting EazyHaven";
            
            $autoReplyHtml = "
            <html>
            <head>
                <title>Thank You for Contacting Us</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    h2 { color: #2d3748; border-bottom: 1px solid #eee; padding-bottom: 10px; }
                    .message { margin-top: 20px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h2>Thank You for Contacting EazyHaven</h2>
                    <p>Dear $name,</p>
                    <div class='message'>
                        <p>Thank you for reaching out to us. We have received your message and will get back to you as soon as possible.</p>
                        <p>For your records, here is a copy of your message:</p>
                        <p><strong>Subject:</strong> $subject</p>
                        <p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>
                    </div>
                    <p>Best regards,<br>The EazyHaven Team</p>
                </div>
            </body>
            </html>
            ";
            
            $autoReplyText = "
            Thank You for Contacting EazyHaven
            ----------------------------------
            
            Dear $name,
            
            Thank you for reaching out to us. We have received your message and will get back to you as soon as possible.
            
            For your records, here is a copy of your message:
            
            Subject: $subject
            Message:
            $message
            
            Best regards,
            The EazyHaven Team
            ";
            
            $autoReply->Body    = $autoReplyHtml;
            $autoReply->AltBody = $autoReplyText;
            
            logDebug("Attempting to send auto-reply email");
            $autoReply->send();
            logDebug("Auto-reply email sent successfully");
            
            // Return success response
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
            
        } catch (Exception $e) {
            logDebug("Failed to send email: " . $mail->ErrorInfo);
            
            // Email failed but we saved to CSV, so return success message
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
        }
        
    } catch (Exception $e) {
        logDebug("Failed to save to CSV: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again later.']);
        exit;
    }
} else {
    // Not a POST request
    logDebug("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>