<?php
session_start();
include 'db_connect.php';

$code = $_POST['code'] ?? null;
$user_id = $_SESSION['user_id'];

if(!$code || !$user_id) die("Missing code or user.");

$stmt = $conn->prepare("SELECT * FROM password_resets WHERE user_id=? AND code=? AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
$stmt->bind_param("is", $user_id, $code);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0){
    echo "✅ Code verified successfully!";
    // Optional: mark as used or delete the record
    $conn->query("DELETE FROM password_resets WHERE user_id=$user_id AND code='$code'");
} else {
    echo "❌ Invalid or expired code.";
}
$stmt->close();
?>
