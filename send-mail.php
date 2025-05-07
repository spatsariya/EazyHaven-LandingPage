<?php
// Include email configuration
require_once 'includes/email-config.php';

// SMTP email sending function
function sendSMTPMail($to, $subject, $message, $headers) {
    $smtpHost = 'smtp.hostinger.com'; // Replace with your SMTP host
    $smtpPort = 465; // Use 587 for TLS or 465 for SSL
    $smtpUser = 'no-reply@eazyhaven.com'; // Replace with your SMTP username
    $smtpPass = 'E@$Y#@ven2025'; // Replace with your SMTP password

    $socket = fsockopen("ssl://$smtpHost", $smtpPort, $errno, $errstr, 10);
    if (!$socket) {
        return "Connection failed: $errstr ($errno)";
    }

    // Helper function to send commands and get responses
    function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        return fgets($socket, 512);
    }

    // SMTP handshake
    sendCommand($socket, "EHLO $smtpHost");
    sendCommand($socket, "AUTH LOGIN");
    sendCommand($socket, base64_encode($smtpUser));
    sendCommand($socket, base64_encode($smtpPass));

    // Mail setup
    sendCommand($socket, "MAIL FROM: <$smtpUser>");
    sendCommand($socket, "RCPT TO: <$to>");
    sendCommand($socket, "DATA");

    // Email content
    $emailContent = "Subject: $subject\r\n$headers\r\n\r\n$message\r\n.";
    sendCommand($socket, $emailContent);

    // End session
    sendCommand($socket, "QUIT");
    fclose($socket);

    return true;
}

// Example usage
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = "Test Email";
    $message = "This is a test email.";
    $headers = "From: no-reply@eazyhaven.com";

    $result = sendSMTPMail($to, $subject, $message, $headers);

    if ($result === true) {
        echo json_encode(['status' => 'success', 'message' => 'Email sent successfully.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $result]);
    }
}
?>