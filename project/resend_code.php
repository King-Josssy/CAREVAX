<?php
session_start();
include 'db_connect.php'; // your mysqli connection

if (!isset($_SESSION['reset_email_or_phone'])) {
    header("Location: forgot_password.php");
    exit();
}

$email_or_phone = $_SESSION['reset_email_or_phone'];

// Generate a new 6-digit verification code
$new_code = rand(100000, 999999);
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes')); // expires in 5 minutes

// Find the user in the database
$stmt = $conn->prepare("SELECT id, email FROM users WHERE email=? OR phone=? LIMIT 1");
$stmt->bind_param("ss", $email_or_phone, $email_or_phone);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_id = $row['id'];
    $user_email = $row['email'];

    // Store the new code in password_resets
    $insert = $conn->prepare("INSERT INTO password_resets (user_id, code, expires_at) VALUES (?, ?, ?)");
    $insert->bind_param("iss", $user_id, $new_code, $expires_at);
    $insert->execute();
    $insert->close();

    // ---------------------------
    // For testing: show the code on screen
    $_SESSION['message'] = "A new verification code has been generated: <b>$new_code</b> (expires in 5 minutes)";

    // ---------------------------
    // In production: send email using PHPMailer
    /*
    require 'vendor/autoload.php';
    use PHPMailer\PHPMailer\PHPMailer;
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your_email@gmail.com';
        $mail->Password = 'your_app_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('your_email@gmail.com', 'CareVax');
        $mail->addAddress($user_email);
        $mail->isHTML(true);
        $mail->Subject = 'Your CareVax Verification Code';
        $mail->Body = "Hello! Your verification code is <b>$new_code</b>. It expires in 5 minutes.";
        $mail->send();
        $_SESSION['message'] = "A new verification code has been sent to your email.";
    } catch (Exception $e) {
        $_SESSION['message'] = "Mailer error: {$mail->ErrorInfo}";
    }
    */

    header("Location: verify_code.php");
    exit();

} else {
    $_SESSION['message'] = "User not found!";
    header("Location: forgot_password.php");
    exit();
}
?>
