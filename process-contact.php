<?php
// Set headers to handle CORS and prevent caching
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize form data
    $name = filter_var($_POST['name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'] ?? 'Contact Form Submission', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_STRING);
    
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

    // Send confirmation email to the user
    $to = $email;
    $emailSubject = "Thank you for contacting EazyHaven";
    $userMessage = "
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
    </html>
    ";

    // Set up email headers
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: EazyHaven <contact@eazyhaven.com>" . "\r\n";

    // Set up SMTP configuration for Hostinger
    ini_set("SMTP", "smtp.hostinger.com");
    ini_set("smtp_port", "465");
    ini_set("sendmail_from", "contact@eazyhaven.com");
    
    // Send the email (comment out for testing)
    mail($to, $emailSubject, $userMessage, $headers);
    
    // Send notification to admin
    $adminEmail = "contact@eazyhaven.com";
    $adminSubject = "New Contact Form Submission from $name";
    $adminMessage = "
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
    </html>
    ";
    
    $adminHeaders = "MIME-Version: 1.0" . "\r\n";
    $adminHeaders .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $adminHeaders .= "From: EazyHaven Website <no-reply@eazyhaven.com>" . "\r\n";
    $adminHeaders .= "Reply-To: $name <$email>" . "\r\n";
    
    // Send the admin notification (comment out for testing)
    mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);
    
    // Optionally CC to support email
    $supportEmail = "support@eazyhaven.com";
    if ($supportEmail != $adminEmail) {
        mail($supportEmail, $adminSubject, $adminMessage, $adminHeaders);
    }

    // Return a success response and redirect
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Your message has been sent successfully!']);
    exit;
} else {
    // If not a POST request, return an error
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>