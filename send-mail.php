<?php
// Include email configuration
require_once 'includes/email-config.php';

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
        
        // Implement Direct SMTP to send emails
        function sendEmailViaSMTP($to, $subject, $body, $from, $fromName, $replyTo) {
            $smtpHost = SMTP_HOST;
            $smtpPort = SMTP_PORT;
            $smtpUser = SMTP_USER;
            $smtpPass = SMTP_PASSWORD;

            $socket = fsockopen($smtpHost, $smtpPort, $errno, $errstr, 30);
            if (!$socket) {
                throw new Exception("Failed to connect to SMTP server: $errstr ($errno)");
            }

            // Helper function to send commands and read responses
            function sendCommand($socket, $command, $expectedCode) {
                fwrite($socket, $command . "\r\n");
                $response = fgets($socket, 512);
                if (substr($response, 0, 3) != $expectedCode) {
                    throw new Exception("SMTP error: $response");
                }
            }

            // Read initial server response
            fgets($socket, 512);

            // Send EHLO
            sendCommand($socket, "EHLO " . gethostname(), 250);

            // Start TLS if required
            if (SMTP_SECURE === 'tls') {
                sendCommand($socket, "STARTTLS", 220);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                sendCommand($socket, "EHLO " . gethostname(), 250);
            }

            // Authenticate
            sendCommand($socket, "AUTH LOGIN", 334);
            sendCommand($socket, base64_encode($smtpUser), 334);
            sendCommand($socket, base64_encode($smtpPass), 235);

            // Set sender
            sendCommand($socket, "MAIL FROM:<$from>", 250);

            // Set recipient
            sendCommand($socket, "RCPT TO:<$to>", 250);

            // Send data
            sendCommand($socket, "DATA", 354);
            $headers = "From: $fromName <$from>\r\n";
            $headers .= "Reply-To: $replyTo\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            fwrite($socket, $headers . "\r\n" . $body . "\r\n.\r\n");
            $response = fgets($socket, 512);
            if (substr($response, 0, 3) != 250) {
                throw new Exception("SMTP error: $response");
            }

            // Quit and close connection
            sendCommand($socket, "QUIT", 221);
            fclose($socket);
        }

        try {
            sendEmailViaSMTP(
                ADMIN_EMAIL,
                "Contact Form: $subject",
                "<html><body><h2>New Contact Form Submission</h2><p><strong>Name:</strong> $name</p><p><strong>Email:</strong> $email</p><p><strong>Subject:</strong> $subject</p><p><strong>Message:</strong></p><p>" . nl2br(htmlspecialchars($message)) . "</p></body></html>",
                EMAIL_FROM,
                EMAIL_NAME,
                $email
            );
            logDebug("Email sent successfully using Direct SMTP");
        } catch (Exception $e) {
            logDebug("Failed to send email via Direct SMTP", $e->getMessage());
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