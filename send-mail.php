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

// Enable detailed error reporting (only for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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
    // Start output buffering to suppress unintended output
    ob_start();

    // Get and sanitize form data
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? 'Contact Form Submission', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    
    logDebug("Sanitized form data", [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => substr($message, 0, 30) . '...'
    ]);
    
    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        logDebug("Validation failed - missing required fields");
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logDebug("Validation failed - invalid email: $email");
        ob_end_clean();
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
        
        // Since we've saved to CSV, return success to the user
        // This ensures a good user experience regardless of email status
        ob_end_clean();
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
        
        // Now try to send email (after sending response to user)
        // This way, if email fails, the user doesn't have to wait
        
        // Continue processing email in the background
        logDebug("Attempting email in background");
        
        try {
            // Initialize PHPMailer
            logDebug("Initializing PHPMailer");
            $mail = new PHPMailer(true); // true enables exceptions
            
            // Debug settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER; // Set to DEBUG_SERVER for detailed logs
            $mail->Debugoutput = function ($str, $level) {
                logDebug("PHPMailer debug [$level]", $str);
            };
            
            // Server settings
            if (method_exists($mail, 'isSMTP')) {
                $mail->isSMTP(); // Use isSMTP() only if it exists
                logDebug("isSMTP() method called successfully");
            } else {
                logDebug("isSMTP() method not available in PHPMailer version");
                $mail->Mailer = 'smtp';
            }
            
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            
            // Set timeout to prevent long waits
            $mail->Timeout = 10;
            
            // Add extra options for compatibility
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ];
            
            logDebug("SMTP settings configured", [
                'host' => SMTP_HOST,
                'port' => SMTP_PORT,
                'secure' => SMTP_SECURE,
                'username' => SMTP_USER,
            ]);
            
            // Add fallback to HELO if EHLO fails
            $smtpInstance = $mail->getSMTPInstance();
            if (!$smtpInstance->hello(gethostname())) {
                logDebug("EHLO failed, attempting HELO");
                if (!$smtpInstance->sendCommand('HELO', 'HELO ' . gethostname(), 250)) {
                    throw new Exception('SMTP HELO failed: ' . $smtpInstance->getError()['error']);
                }
            }
            
            // Sender and recipient
            $mail->setFrom(EMAIL_FROM, EMAIL_NAME);
            $mail->addAddress(ADMIN_EMAIL);
            $mail->addReplyTo($email, $name);
            
            // Content
            if (method_exists($mail, 'isHTML')) {
                $mail->isHTML(true); // Use isHTML() only if it exists
                logDebug("isHTML() method called successfully");
            } else {
                logDebug("isHTML() method not available in PHPMailer version");
                $mail->ContentType = 'text/html'; // Fallback for HTML emails
            }
            $mail->Subject = "Contact Form: $subject";
            
            // Create message body
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
            
            $mail->Body = $htmlMessage;
            $mail->AltBody = $plainMessage;
            
            logDebug("Attempting to send email");
            
            if ($mail->send()) {
                logDebug("Email sent successfully");
            } else {
                logDebug("Mailer Error", $mail->ErrorInfo);
                throw new Exception("Mailer Error: " . $mail->ErrorInfo);
            }
            
        } catch (Exception $e) {
            logDebug("Failed to send email", $e->getMessage());
            ob_end_clean(); // Clear buffer
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
            exit;
        }
        
    } catch (Exception $e) {
        logDebug("Failed to save to CSV: " . $e->getMessage());
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Server error. Please try again later.']);
    }
} else {
    // Ensure no extra output
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>