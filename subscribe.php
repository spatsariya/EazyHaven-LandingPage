<?php
// Set headers to handle CORS and prevent caching
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the email from the POST data
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
        exit;
    }

    // Get current timestamp
    $timestamp = isset($_POST['timestamp']) ? $_POST['timestamp'] : date('Y-m-d H:i:s');
    
    // Create directory if it doesn't exist
    $directory = 'data';
    if (!file_exists($directory)) {
        mkdir($directory, 0755, true);
    }
    
    // Define the CSV file path
    $csvFile = $directory . '/newsletter_subscribers.csv';
    
    // Check if file exists, if not create with headers
    $fileExists = file_exists($csvFile);
    
    // Open the CSV file for appending
    $handle = fopen($csvFile, 'a');
    
    // If the file is new, add headers
    if (!$fileExists) {
        fputcsv($handle, ['Email', 'Timestamp']);
    }
    
    // Write the data to the CSV file
    fputcsv($handle, [$email, $timestamp]);
    
    // Close the file
    fclose($handle);

    // Send confirmation email to subscriber
    $to = $email;
    $subject = "Welcome to EazyHaven Newsletter";
    $message = "
    <html>
    <head>
        <title>Welcome to EazyHaven Newsletter</title>
    </head>
    <body>
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background-color: #86198f; padding: 20px; color: white; text-align: center;'>
                <h1>Welcome to EazyHaven!</h1>
            </div>
            <div style='padding: 20px; background-color: #f9f9f9;'>
                <p>Dear Subscriber,</p>
                <p>Thank you for joining our inner circle! You're now part of our exclusive community that receives:</p>
                <ul>
                    <li>Special product announcements</li>
                    <li>Exclusive skincare tips</li>
                    <li>Limited time offers</li>
                    <li>Early access to new collections</li>
                </ul>
                <p>We're excited to have you with us on this journey to radiant, beautiful skin.</p>
                <p>Warm regards,<br>The EazyHaven Team</p>
            </div>
            <div style='background-color: #333; color: #999; padding: 15px; text-align: center; font-size: 12px;'>
                <p>&copy; 2025 EazyHaven. All rights reserved.</p>
                <p>If you wish to unsubscribe, please <a href='#' style='color: #c026d3;'>click here</a>.</p>
            </div>
        </div>
    </body>
    </html>
    ";

    // To send HTML mail, the Content-type header must be set
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: EazyHaven <newsletter@eazyhaven.com>" . "\r\n";

    // Set up SMTP configuration for Hostinger
    ini_set("SMTP", "smtp.hostinger.com");
    ini_set("smtp_port", "465");
    ini_set("sendmail_from", "newsletter@eazyhaven.com");
    
    // Send the email (disabled for testing)
    // Uncomment the line below when you're ready to send emails
    // mail($to, $subject, $message, $headers);
    
    // Also, send a notification to the admin
    $adminEmail = "contact@eazyhaven.com";
    $adminSubject = "New Newsletter Subscription";
    $adminMessage = "A new user has subscribed to the newsletter: $email";
    $adminHeaders = "From: EazyHaven <newsletter@eazyhaven.com>" . "\r\n";
    
    // Send the admin notification (disabled for testing)
    // Uncomment the line below when you're ready to send emails
    // mail($adminEmail, $adminSubject, $adminMessage, $adminHeaders);

    // Return a success response
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => 'Subscription successful']);
    exit;
} else {
    // If not a POST request, return an error
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}
?>