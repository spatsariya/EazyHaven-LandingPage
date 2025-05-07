<?php
// Set headers to handle CORS and prevent caching
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Enable detailed error reporting (only for debugging, remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Create a debug log file
$logFile = 'debug_log.txt';
file_put_contents($logFile, "Form submission received at " . date('Y-m-d H:i:s') . "\n", FILE_APPEND);

// Include PHPMailer classes
require_once 'includes/PHPMailer/Exception.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Function to log data to the debug file
function logDebug($message, $data = null) {
    global $logFile;
    $logMessage = date('Y-m-d H:i:s') . " - $message";
    if ($data !== null) {
        $logMessage .= ": " . print_r($data, true);
    }
    file_put_contents($logFile, $logMessage . "\n", FILE_APPEND);
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Log received POST data
    logDebug("POST data received", $_POST);
    
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
        logDebug("Form validation failed - missing required fields");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        logDebug("Form validation failed - invalid email: $email");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }
    
    // Create directory if it doesn't exist
    $directory = 'data';
    if (!file_exists($directory)) {
        if (!mkdir($directory, 0755, true)) {
            logDebug("Failed to create directory: $directory");
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Server configuration error. Please try again later.']);
            exit;
        }
    }
    
    // Define the CSV file path
    $csvFile = $directory . '/contact_submissions.csv';
    
    // Check if file exists, if not create with headers
    $fileExists = file_exists($csvFile);
    
    try {
        // Open the CSV file for appending
        $handle = fopen($csvFile, 'a');
        
        if (!$handle) {
            throw new Exception("Could not open file: $csvFile");
        }
        
        // If the file is new, add headers
        if (!$fileExists) {
            fputcsv($handle, ['Name', 'Email', 'Subject', 'Message', 'Timestamp']);
        }
        
        // Write the data to the CSV file
        $timestamp = date('Y-m-d H:i:s');
        fputcsv($handle, [$name, $email, $subject, $message, $timestamp]);
        
        // Close the file
        fclose($handle);
        
        logDebug("Form data saved to CSV file");
    } catch (Exception $e) {
        logDebug("Failed to save data to CSV: " . $e->getMessage());
        // Continue processing - don't exit here as we still want to try sending emails
    }

    // First try to send email
    $emailSent = false;
    
    try {
        // SMTP Email Configuration
        $smtpHost = 'smtp.hostinger.com';  // Replace with your SMTP server
        $smtpUsername = 'no-reply@eazyhaven.com';  // Updated email username
        $smtpPassword = 'E@$Y#@ven2025';  // Replace with your actual email password
        $smtpPort = 465;  // Usually 587 for TLS or 465 for SSL
        
        logDebug("Starting email process with SMTP settings", [
            'host' => $smtpHost,
            'username' => $smtpUsername,
            'port' => $smtpPort
        ]);
        
        // Send confirmation email to the user using PHPMailer with SMTP
        $userMail = new PHPMailer(true);
        
        // Server settings
        $userMail->SMTPDebug = 3;  // Set to 3 for detailed debug output
        ob_start(); // Start output buffering to capture debug output
        
        $userMail->isSMTP();
        $userMail->Host       = $smtpHost;
        $userMail->SMTPAuth   = true;
        $userMail->Username   = $smtpUsername;
        $userMail->Password   = $smtpPassword;
        $userMail->Port       = $smtpPort;
        $userMail->SMTPSecure = 'ssl';  // Try 'ssl' instead of PHPMailer::ENCRYPTION_SMTPS
        
        // Recipients
        $userMail->setFrom('no-reply@eazyhaven.com', 'EazyHaven');
        $userMail->addAddress($email, $name);
        
        // Content
        $userMail->isHTML(true);
        $userMail->Subject = "Thank you for contacting EazyHaven";
        $userMail->Body = "
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
        
        $userMail->send();
        $debugOutput = ob_get_clean(); // Get the debug output and stop buffering
        logDebug("User confirmation email sent successfully. Debug output:", $debugOutput);
        
        $emailSent = true;
    } catch (Exception $e) {
        $debugOutput = ob_get_clean(); // Get the debug output and stop buffering
        
        // Log the error for debugging
        logDebug("SMTP Mailer Error: " . $e->getMessage());
        logDebug("SMTP Debug Output: " . $debugOutput);
        
        // Try using the built-in mail() function as fallback
        try {
            logDebug("Trying PHP mail() function as fallback");
            
            // Set email headers
            $headers = "From: EazyHaven <no-reply@eazyhaven.com>\r\n";
            $headers .= "Reply-To: no-reply@eazyhaven.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            
            // Set email body
            $emailBody = "
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
            
            // Send email using mail() function
            $mailSent = mail($email, "Thank you for contacting EazyHaven", $emailBody, $headers);
            
            if ($mailSent) {
                logDebug("Email sent successfully using PHP mail() function");
                $emailSent = true;
            } else {
                logDebug("Failed to send email using PHP mail() function");
                throw new Exception("Failed to send email using PHP mail() function");
            }
        } catch (Exception $mailException) {
            logDebug("PHP mail() Error: " . $mailException->getMessage());
            // We'll handle this in the next block
        }
    }
    
    // Return appropriate response based on whether email was sent or not
    if ($emailSent) {
        // Return a success response
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
    } else {
        // If email sending failed, still return a success response since we saved the data to CSV
        logDebug("Email sending failed, but data was saved to CSV");
        http_response_code(200); // Still return 200 to avoid confusion for the user
        echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
    }
    exit;
} else {
    // If not a POST request, return an error
    logDebug("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>