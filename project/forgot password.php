<?php
session_start();
include 'db_connect.php'; // Your DB connection ($conn)

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

// --- EgoSMS function ---
function send_sms($phone, $message) {
    $username = 'BBMI';
    $password = 'asimblack256';
    $senderId = 'BBMI';
    $jsonEndpoint = 'https://www.egosms.co/api/v1/json/';

    // Ensure proper international format for Uganda
    $phone = preg_replace('/^0/', '+256', $phone);

    $data = [
        'username' => $username,
        'password' => $password,
        'sender' => $senderId,
        'to' => $phone,
        'message' => $message
    ];

    $ch = curl_init($jsonEndpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    if(curl_errno($ch)){
        $error_msg = curl_error($ch);
        curl_close($ch);
        return ['success'=>false,'error'=>$error_msg];
    }
    curl_close($ch);

    $result = json_decode($response, true);

    // Debug log
    file_put_contents('sms_log.txt', date('Y-m-d H:i:s')." - Phone: $phone - Response: ".print_r($result,true)."\n", FILE_APPEND);

    return $result;
}

// --- Main forgot password logic ---
$messages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $method = $_POST['method'] ?? 'email';

    if (!$email && !$phone) {
        $messages[] = ['type'=>'error','text'=>'Please enter email or phone.'];
    } else {
        // Fetch user
        if ($email) {
            $stmt = $conn->prepare("SELECT id, username, email FROM users WHERE email=? LIMIT 1");
            $stmt->bind_param("s",$email);
        } else {
            $stmt = $conn->prepare("SELECT id, username, phone FROM users WHERE phone=? LIMIT 1");
            $stmt->bind_param("s",$phone);
        }
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$user) {
            $messages[] = ['type'=>'error','text'=>'No user found with the provided info.'];
        } else {
            $user_id = $user['id'];
            $username = $user['username'];
            $user_email = $user['email'] ?? '';
            $user_phone = $user['phone'] ?? '';

            // Generate verification code
            $verification_code = strval(rand(100000,999999));
            $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            // Insert into password_resets
            $stmt = $conn->prepare("INSERT INTO password_resets (user_id, code, method, expires_at) VALUES (?,?,?,?)");
            $stmt->bind_param("isss", $user_id, $verification_code, $method, $expires_at);
            $stmt->execute();
            $stmt->close();

            // Set session to identify user
            $_SESSION['reset_email_or_phone'] = $email ?: $phone;

            // Send code
            if ($method === 'email') {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'carevax52@gmail.com';
                    $mail->Password = 'llefxigvgaqdocmz'; // App password
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom('carevax52@gmail.com','CareVax');
                    $mail->addAddress($user_email);

                    $mail->isHTML(true);
                    $mail->Subject = 'CareVax Verification Code';
                    $mail->Body = "
                        <h2>CareVax Security Verification</h2>
                        <p>Hello $username,</p>
                        <p>Your verification code is:</p>
                        <h3 style='color:#3498db;'>$verification_code</h3>
                        <p>Expires in 15 minutes.</p>
                        <p>If you didn’t request this, ignore this message.</p>
                    ";

                    $mail->send();
                    $messages[] = ['type'=>'success','text'=>'Verification code sent to your email.'];

                } catch (Exception $e) {
                    $messages[] = ['type'=>'error','text'=>"Email could not be sent: {$mail->ErrorInfo}"];
                }

            } else {
                // Convert local 07… number to +2567… before sending
                $user_phone = preg_replace('/^0/', '+256', $user_phone);

                // Send SMS
                $response = send_sms($user_phone, "Your CareVax verification code is $verification_code.");
                if(!empty($response['status']) && ($response['status'] == 'success' || $response['status'] == 'sent')){
                    $messages[] = ['type'=>'success','text'=>'Verification code sent via SMS.'];
                } else {
                    $messages[] = ['type'=>'error','text'=>'Failed to send SMS. Check sms_log.txt for details.'];
                }
            }

            // Redirect to verification page
            header("Location: verify_code.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - CareVax</title>
<style>
body{font-family:Arial,sans-serif;background:#f4f6f8;margin:0;padding:0;}
.container{max-width:500px;margin:80px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
input, select, button{width:100%;padding:10px;margin:8px 0;border-radius:6px;border:1px solid #ccc;}
button{background:#3498db;color:#fff;border:none;cursor:pointer;}
button:hover{background:#2980b9;}
.msg{padding:10px;margin-bottom:15px;border-radius:6px;}
.msg.success{background:#e9f8f0;color:#2ecc71;}
.msg.error{background:#fdecea;color:#e74c3c;}
</style>
</head>
<body>
<div class="container">
<h2>Forgot Password</h2>
<?php foreach($messages as $m): ?>
    <div class="msg <?= $m['type'] ?>"><?= htmlspecialchars($m['text']) ?></div>
<?php endforeach; ?>
<form method="POST">
<label>Email (optional)</label>
<input type="email" name="email" placeholder="Enter your email">
<label>Phone (optional)</label>
<input type="text" name="phone" placeholder="Enter your phone number">
<label>Send code via</label>
<select name="method">
    <option value="email">Email</option>
    <option value="sms">SMS</option>
</select>
<button type="submit">Send Verification Code</button>
</form>
</div>
</body>
</html>
