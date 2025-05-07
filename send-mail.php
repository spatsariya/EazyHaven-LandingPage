<?php
// Include email configuration and SwiftMailer
require_once 'includes/email-config.php';
require_once 'lib/swift_required.php';

// Include the EmailValidator library manually
require_once 'lib/EmailValidator-4.x/src/EmailValidator.php';

// Ensure email-config.php is included and constants are defined
if (!defined('SMTP_USERNAME') || !defined('SMTP_PASSWORD') || !defined('SMTP_HOST') || !defined('SMTP_PORT') || !defined('SMTP_SECURE')) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Email configuration is missing. Please check email-config.php.']);
    exit;
}

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

// Add a basic email validation function to bypass the missing dependency
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Log the request method and raw POST data for debugging
logDebug("Request method", $_SERVER['REQUEST_METHOD']);
logDebug("Raw POST data", file_get_contents('php://input'));
logDebug("POST array", $_POST);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start output buffering to suppress unintended output
    ob_start();

    // Get and sanitize form data
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $subject = htmlspecialchars(trim($_POST['subject'] ?? 'Contact Form Submission'));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));

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

    if (!isValidEmail($email)) {
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

        // Send email using SwiftMailer
        logDebug("Attempting email using SwiftMailer");

        try {
            // Setup transport
            $transport = (new Swift_SmtpTransport(SMTP_HOST, SMTP_PORT, SMTP_SECURE))
                ->setUsername(SMTP_USERNAME)
                ->setPassword(SMTP_PASSWORD);

            // Enable logging
            $logger = new Swift_Plugins_Loggers_ArrayLogger();
            $transport->registerPlugin(new Swift_Plugins_LoggerPlugin($logger));

            // Create mailer
            $mailer = new Swift_Mailer($transport);

            // Create message
            $body = "<strong>Name:</strong> $name<br><strong>Email:</strong> $email<br><strong>Subject:</strong> $subject<br><strong>Message:</strong><br>" . nl2br($message);

            $swiftMessage = (new Swift_Message("New Message from EazyHaven: $subject"))
                ->setFrom([SMTP_USERNAME => 'EazyHaven Website'])
                ->setTo([SMTP_USERNAME])
                ->setReplyTo([$email => $name])
                ->setBody($body, 'text/html');

            $result = $mailer->send($swiftMessage);

            if ($result) {
                logDebug("Email sent successfully using SwiftMailer");
            } else {
                logDebug("Failed to send email via SwiftMailer");
            }

            // Log the email sending process
            file_put_contents('swiftmailer_log.txt', $logger->dump(), FILE_APPEND);
        } catch (Exception $e) {
            logDebug("Failed to send email via SwiftMailer", $e->getMessage());
            file_put_contents('swiftmailer_log.txt', $e->getMessage(), FILE_APPEND);
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