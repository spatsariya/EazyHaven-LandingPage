<?php
// Set headers to handle CORS and prevent caching
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Include PHPMailer classes
require_once 'includes/PHPMailer/Exception.php';
require_once 'includes/PHPMailer/PHPMailer.php';
require_once 'includes/PHPMailer/SMTP.php';

// Import PHPMailer classes into the global namespace
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? 'Contact Form Submission', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);
    $hcaptchaResponse = $_POST['h-captcha-response'] ?? '';
    
    // Validate form data
    if (empty($name) || empty($email) || empty($message)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please fill in all required fields']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }

    // Verify hCaptcha
    if (empty($hcaptchaResponse)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Please complete the CAPTCHA verification']);
        exit;
    }

    // hCaptcha verification request
    $hcaptchaSecretKey = 'ES_dba4b289340e45ea8a7bca4bc297a086'; // Your hCaptcha secret key
    $verifyUrl = 'https://hcaptcha.com/siteverify';
    
    $data = [
        'secret' => $hcaptchaSecretKey,
        'response' => $hcaptchaResponse,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];

    $options = [
        'http' => [
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data)
        ]
    ];

    $context = stream_context_create($options);
    $verifyResponse = file_get_contents($verifyUrl, false, $context);
    $responseData = json_decode($verifyResponse);

    // If hCaptcha verification fails
    if (!$responseData->success) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'CAPTCHA verification failed. Please try again.']);
        exit;
    }
    
    // Create directory if it doesn't exist
    $directory = 'data';
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Define the CSV file path
    $csvFile = $directory . '/contact_submissions.csv';
    
    // Check if file exists, if not create with headers
    $fileExists = file_exists($csvFile);
    
    // Open the CSV file for appending
    $handle = fopen($csvFile, 'a');
    
    // If the file is new, add headers
    if (!$fileExists) {
        fputcsv($handle, ['Name', 'Email', 'Subject', 'Message', 'Timestamp']);
    }
    
    // Write the data to the CSV file
    $timestamp = date('Y-m-d H:i:s');
    fputcsv($handle, [$name, $email, $subject, $message, $timestamp]);
    
    // Close the file
    fclose($handle);

    // SMTP Email Configuration
    $smtpHost = 'smtp.hostinger.com';  // Replace with your SMTP server
    $smtpUsername = 'contact@eazyhaven.com';  // Replace with your email username
    $smtpPassword = 'E@$Y#@ven2025';  // Replace with your actual email password
    $smtpPort = 465;  // Usually 587 for TLS or 465 for SSL
    
    // Send confirmation email to the user using PHPMailer with SMTP
    try {
        $userMail = new PHPMailer(true);
        
        // Server settings
        $userMail->SMTPDebug = 0;  // Set to 0 for no debug output, 1 or 2 for debug output
        $userMail->isSMTP();
        $userMail->Host       = $smtpHost;
        $userMail->SMTPAuth   = true;
        $userMail->Username   = $smtpUsername;
        $userMail->Password   = $smtpPassword;
        $userMail->Port       = $smtpPort;
        $userMail->SMTPSecure = 'ssl';  // Use 'tls' or 'ssl'
        
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
        
        // Send notification to admin
        $adminMail = new PHPMailer(true);
        
        // Server settings (same as above)
        $adminMail->SMTPDebug = 0;
        $adminMail->isSMTP();
        $adminMail->Host       = $smtpHost;
        $adminMail->SMTPAuth   = true;
        $adminMail->Username   = $smtpUsername;
        $adminMail->Password   = $smtpPassword;
        $adminMail->Port       = $smtpPort;
        $adminMail->SMTPSecure = 'ssl';
        
        // Recipients
        $adminMail->setFrom('no-reply@eazyhaven.com', 'EazyHaven Website');
        $adminMail->addAddress('contact@eazyhaven.com', 'EazyHaven Contact');
        $adminMail->addReplyTo($email, $name);
        
        // Optionally CC to support email
        $supportEmail = "support@eazyhaven.com";
        if ($supportEmail != 'contact@eazyhaven.com') {
            $adminMail->addCC($supportEmail, 'EazyHaven Support');
        }
        
        // Content
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
        
        // Return a success response
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
        exit;
        
    } catch (Exception $e) {
        // Log the error for debugging
        error_log("Mailer Error: " . $e->getMessage());
        
        // Return a user-friendly error
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Could not send email. Please try again later.']);
        exit;
    }
} else {
    // If not a POST request, return an error
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>