<?php
session_start();
include 'db_connect.php';

// Check if user is verified
if (!isset($_SESSION['verified_user_id'])) {
    header("Location: forgot_password.php");
    exit();
}

$user_id = $_SESSION['verified_user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password === '' || $confirm_password === '') {
        $message = "⚠️ Please fill in all fields.";
    } elseif ($new_password !== $confirm_password) {
        $message = "❌ Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $message = "🔒 Password must be at least 6 characters long.";
    } else {
        // Hash password securely
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);

        // Update password in DB
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param("si", $hashed, $user_id);

        if ($stmt->execute()) {
            $message = "✅ Password reset successful! Redirecting to login...";
            
            // Clear session
            unset($_SESSION['verified_user_id']);
            unset($_SESSION['verified_email_or_phone']);

            // Redirect to login after 3 seconds
            echo "<meta http-equiv='refresh' content='3;url=login.php'>";
        } else {
            $message = "⚠️ Failed to update password. Try again.";
        }

        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - CareVax</title>
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
</style>
</head>
<body>
<div class="container">
<h2>Reset Your Password</h2>

<?php if ($message): ?>
<div class="msg <?= strpos($message, '✅') !== false ? 'success' : 'error' ?>">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<form method="POST">
    <label>New Password</label>
    <input type="password" name="new_password" placeholder="Enter new password" required>
    
    <label>Confirm Password</label>
    <input type="password" name="confirm_password" placeholder="Re-enter new password" required>
    
    <button type="submit">Change Password</button>
</form>
</div>
</body>
</html>
