<?php
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';
require 'PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set the content type to JSON
header('Content-Type: application/json');

$mail = new PHPMailer(true);

try {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = 'smtp.hostinger.com'; // Your SMTP
    $mail->SMTPAuth   = true;
    $mail->Username   = 'no-reply@eazyhaven.com'; // Your email
    $mail->Password   = 'E@$Y#@ven2025';     // Email password
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    // Recipients
    $mail->setFrom('no-reply@eazyhaven.com', 'EazyHaven');
    $mail->addAddress('contact@eazyhaven.com'); // Your receiving email

    // Content
    $mail->isHTML(true);
    $mail->Subject = $_POST['subject'];
    $mail->Body    = "
        <strong>Name:</strong> {$_POST['name']}<br>
        <strong>Email:</strong> {$_POST['email']}<br>
        <strong>Message:</strong><br>" . nl2br($_POST['message']);

    $mail->send();
    
    // Return success JSON response
    echo json_encode(['status' => 'success', 'message' => 'Your message has been received. Thank you for contacting us!']);
} catch (Exception $e) {
    // Return error JSON response
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to send email. Please try again later.']);
}
