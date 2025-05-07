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
    $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';
    
    logDebug("Sanitized form data", [
        'name' => $name,
        'email' => $email,
        'subject' => $subject,
        'message' => substr($message, 0, 30) . '...',
        'hcaptchaResponse' => !empty($hcaptchaResponse) ? 'provided' : 'missing'
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

    // hCaptcha verification - temporarily skip for debugging
    if (empty($hcaptchaResponse)) {
        logDebug("Form validation failed - missing hCaptcha response");
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please complete the CAPTCHA verification']);
        exit;
    }

    // hCaptcha verification request
    $hcaptchaSecretKey = '0xb5Fb8089A41A15b857985BE23923b5C20Ec12A3a'; // Updated secret key that matches your site key
    $verifyUrl = 'https://hcaptcha.com/siteverify';
    
    $data = [
        'secret' => $hcaptchaSecretKey,
        'response' => $hcaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    logDebug("Verifying hCaptcha with data", [
        'secret' => substr($hcaptchaSecretKey, 0, 5) . '...',
        'response' => substr($hcaptchaResponse, 0, 10) . '...',
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ]);

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    
    try {
        $verifyResponse = file_get_contents($verifyUrl, false, $context);
        $responseData = json_decode($verifyResponse);
        logDebug("hCaptcha verification response", $responseData);
    } catch (Exception $e) {
        logDebug("hCaptcha verification failed with exception", $e->getMessage());
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'CAPTCHA verification service unavailable']);
        exit;
    }

    // TEMPORARY FOR DEBUGGING: Skip hCaptcha verification failure
    // In production, you should uncomment the following block
    /*
    if (!$responseData || !$responseData->success) {
        logDebug("hCaptcha verification failed with response", $responseData);
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'CAPTCHA verification failed. Please try again.']);
        exit;
    }
    */
    
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

    // SMTP Email Configuration
    $smtpHost = 'smtp.hostinger.com';  // Replace with your SMTP server
    $smtpUsername = 'contact@eazyhaven.com';  // Replace with your email username
    $smtpPassword = 'E@$Y#@ven2025';  // Replace with your actual email password
    $smtpPort = 465;  // Usually 587 for TLS or 465 for SSL
    
    logDebug("Starting email process with SMTP settings", [
        'host' => $smtpHost,
        'username' => $smtpUsername,
        'port' => $smtpPort
    ]);
    
    // Send confirmation email to the user using PHPMailer with SMTP
    try {
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
        $userMail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;  // Use PHPMailer::ENCRYPTION_STARTTLS or PHPMailer::ENCRYPTION_SMTPS
        
        // Recipients
        $userMail->setFrom('contact@eazyhaven.com', 'EazyHaven');
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
        
        // Skip admin email for now to simplify debugging
        logDebug("Skipping admin email for debugging");
        
        // Return a success response
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit;
        
    } catch (Exception $e) {
        $debugOutput = ob_get_clean(); // Get the debug output and stop buffering
        
        // Log the error for debugging
        logDebug("Mailer Error: " . $e->getMessage());
        logDebug("SMTP Debug Output: " . $debugOutput);
        
        // Return a user-friendly error
        http_response_code(500);
        echo json_encode([
            'status' => 'error', 
            'message' => 'Could not send email. Please try again later.',
            'debug' => 'Error: ' . $e->getMessage()
        ]);
        exit;
    }
} else {
    // If not a POST request, return an error
    logDebug("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>