<?php 
session_start();
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = trim($_POST['role'] ?? '');

    if (!$name || !$username || !$password || !$role) {
        $error = "Please fill in all required fields.";
    } else {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $allowed_roles = ['parent', 'admin', 'healthworker'];
        if (!in_array($role, $allowed_roles)) {
            $error = "Invalid role selected.";
        } else {
            // Check for duplicate username or email
            $stmt = $conn->prepare("SELECT * FROM users WHERE username=? OR email=? LIMIT 1");
            $stmt->bind_param("ss", $username, $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Username or email already exists. Please choose another.";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (name,email,phone,username,password,role) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param("ssssss", $name, $email, $phone, $username, $passwordHash, $role);
                if ($stmt->execute()) {
                    $success = ucfirst($role) . " registered successfully! You can now login.";
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign Up - CareVax</title>
<style>
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('vaccines.jpg') no-repeat center center/cover;
    height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}
.signup-container {
    background: rgba(255, 255, 255, 0.95);
    padding: 3rem;
    border-radius: 12px;
    width: 400px;
    text-align: center;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    animation: fadeIn 1s ease-in-out;
}
.signup-container h2 { margin-bottom: 1rem; color: #2c3e50; }
.signup-container input, .signup-container select {
    width: 100%; padding: 12px; margin: 8px 0; border-radius: 8px; border: 1px solid #ccc; font-size: 1rem; outline: none; transition: 0.3s;
}
.signup-container input:focus, .signup-container select:focus {
    border-color: #4CAF50;
    box-shadow: 0 0 8px rgba(76, 175, 80, 0.4);
}
.signup-container button {
    width: 100%; padding: 12px; background: linear-gradient(90deg, #4caf50 60%, #388e3c 100%);
    color: white; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; margin-top: 10px; transition: 0.3s;
}
.signup-container button:hover {
    background: linear-gradient(90deg, #45a049 60%, #2e7d32 100%);
}
.signup-container p { margin-top: 1rem; font-size: 0.9rem; color: #444; }
.signup-container a { color: #4caf50; font-weight: bold; text-decoration: none; }
.signup-container a:hover { text-decoration: underline; }
.error { color: red; margin-bottom: 1rem; font-weight: bold; }
.success { color: green; margin-bottom: 1rem; font-weight: bold; }
@keyframes fadeIn { from {opacity:0; transform: translateY(-20px);} to {opacity:1; transform: translateY(0);} }
</style>
<script>
function validateForm() {
    const name = document.forms["signupForm"]["name"].value.trim();
    const username = document.forms["signupForm"]["username"].value.trim();
    const password = document.forms["signupForm"]["password"].value.trim();
    const role = document.forms["signupForm"]["role"].value;

    if (!name || !username || !password || !role) {
        alert("Please fill in all required fields.");
        return false;
    }
    if(password.length < 6){
        alert("Password must be at least 6 characters.");
        return false;
    }
    return true;
}
</script>
</head>
<body>
<div class="signup-container">
    <h2>Create Your Account</h2>
    <?php if($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if($success): ?><p class="success"><?= $success ?></p><?php endif; ?>
    <form name="signupForm" method="POST" action="" onsubmit="return validateForm();">
        <input type="text" name="name" placeholder="Full Name" required>
        <input type="email" name="email" placeholder="Email (optional)">
        <input type="text" name="phone" placeholder="Phone Number (optional)">
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <select name="role" required>
            <option value="" disabled selected>Select role</option>
            <option value="healthworker">🩺 Health Worker</option>
            <option value="admin">🛡️ Admin</option>
        </select>
        <button type="submit">Sign Up</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</div>
</body>
</html>
