<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);

try {
    $mail->SMTPDebug = 2; // Enable verbose debug output
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'carevax50@gmail.com';
    $mail->Password = 'llefxigvgaqdocmz';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom('carevax50@gmail.com', 'CareVax Test');
    $mail->addAddress('kingjossy36@gmail.com');
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test from PHPMailer.';

    $mail->send();
    echo "Email sent successfully!";
} catch (Exception $e) {
    echo "Error: {$mail->ErrorInfo}";
}
