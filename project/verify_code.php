<?php 
session_start();
include 'db_connect.php'; // your mysqli connection

if (!isset($_SESSION['reset_email_or_phone'])) {
    header("Location: forgot_password.php");
    exit();
}

$email_or_phone = $_SESSION['reset_email_or_phone'];
$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');

    if ($code) {
        // Find user
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? OR phone=? LIMIT 1");
        $stmt->bind_param("ss", $email_or_phone, $email_or_phone);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $user_id = $user['id'];

            // Check code in DB
            $check = $conn->prepare("SELECT * FROM password_resets WHERE user_id=? AND code=? ORDER BY id DESC LIMIT 1");
            $check->bind_param("is", $user_id, $code);
            $check->execute();
            $res = $check->get_result();

            if ($row = $res->fetch_assoc()) {
                $now = date('Y-m-d H:i:s');
                if ($now < $row['expires_at']) {
                    // Code is valid and not expired
                    $_SESSION['verified_user_id'] = $user_id;
                    $_SESSION['verified_email_or_phone'] = $email_or_phone;
                    $success = true;
                    header("Location: reset_password.php");
                    exit();
                } else {
                    $message = "⏰ Verification code expired. Please request a new one.";
                }
            } else {
                $message = "❌ Invalid verification code.";
            }
            $check->close();
        } else {
            $message = "⚠️ No account found for that email or phone.";
        }
        $stmt->close();
    } else {
        $message = "⚠️ Please enter your verification code.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify Code - CareVax</title>
<style>
body {
    font-family: Arial, sans-serif;
    background: #f4f6f8;
    margin: 0;
    padding: 0;
}
.container {
    max-width: 400px;
    margin: 80px auto;
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
}
input, button {
    width: 100%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: 1px solid #ccc;
    font-size: 16px;
}
button {
    background: #3498db;
    color: #fff;
    border: none;
    cursor: pointer;
}
button:hover {
    background: #2980b9;
}
.msg {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
    font-size: 14px;
}
.msg.success { background: #e9f8f0; color: #2ecc71; }
.msg.error { background: #fdecea; color: #e74c3c; }
.timer {
    color: #e67e22;
    font-weight: bold;
    margin-top: 10px;
}
</style>
<script>
// 5-minute countdown timer
let timeLeft = 300;
function updateTimer() {
  const timerEl = document.getElementById('timer');
  if (timeLeft <= 0) {
    timerEl.innerText = "⏰ Code expired!";
    document.getElementById('code').disabled = true;
    document.getElementById('verifyBtn').disabled = true;
  } else {
    const minutes = Math.floor(timeLeft / 60);
    const seconds = timeLeft % 60;
    timerEl.innerText = `⏳ Code expires in ${minutes}:${seconds < 10 ? '0' + seconds : seconds}`;
    timeLeft--;
    setTimeout(updateTimer, 1000);
  }
}
window.onload = updateTimer;
</script>
</head>
<body>
<div class="container">
<h2>Verify Your Code</h2>

<?php if ($message): ?>
<div class="msg error"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<form method="POST">
    <label for="code">Verification Code</label>
    <input type="text" name="code" id="code" placeholder="Enter the code sent to you" required autofocus>
    <button type="submit" id="verifyBtn">Verify Code</button>
</form>

<p class="timer" id="timer"></p>

<p style="margin-top:15px;">
    Didn’t receive a code? <a href="forgot_password.php">Resend Code</a>
</p>
</div>
</body>
</html>
