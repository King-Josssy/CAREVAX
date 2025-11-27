<?php
include 'db_connect.php'; 
session_start();
$error = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = trim($_POST['password'] ?? '');

    if ($identifier && $password) {
        // check by email, username or phone
        $stmt = $conn->prepare("SELECT * FROM users WHERE (email=? OR username=? OR phone=?) LIMIT 1");
        $stmt->bind_param('sss', $identifier, $identifier, $identifier);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role']    = $user['role'];

                // redirect automatically based on role
        
                if ($user['role'] === 'healthworker') {
                    header('Location: welcome.php?role=healthworker');
                    exit();
                } elseif ($user['role'] === 'admin') {
                    header('Location: welcome.php?role=admin');
                    exit();
                } else {
                    $error = 'Unknown user role.';
                }
            } else {
                $error = 'Invalid password.';
            }
        } else {
            $error = 'User not found.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login - CareVax</title>
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
    .login-container {
      background: rgba(255, 255, 255, 0.92);
      padding: 2.5rem;
      border-radius: 12px;
      width: 350px;
      box-shadow: 0px 6px 20px rgba(0,0,0,0.3);
      text-align: center;
      animation: fadeIn 1s ease-in-out;
    }
    .login-container h2 {
      margin-bottom: 1rem;
      color: #2c3e50;
    }
    .login-container input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 1rem;
      outline: none;
      transition: 0.3s;
    }
    .login-container input:focus {
      border-color: #4CAF50;
      box-shadow: 0px 0px 8px rgba(76, 175, 80, 0.4);
    }
    .login-container button {
      width: 100%;
      padding: 12px;
      background: linear-gradient(90deg, #4caf50 60%, #388e3c 100%);
      color: white;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 10px;
      transition: 0.3s;
    }
    .login-container button:hover {
      background: linear-gradient(90deg, #45a049 60%, #2e7d32 100%);
    }
    .login-container p {
      margin-top: 1rem;
      font-size: 0.9rem;
      color: #444;
    }
    .login-container a {
      color: #4caf50;
      text-decoration: none;
      font-weight: bold;
    }
    .login-container a:hover {
      text-decoration: underline;
    }
    .error {
      color: red;
      margin-bottom: 1rem;
      font-size: 0.9rem;
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h2>Login to CareVax</h2>
    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" action="">
      <input type="text" name="identifier" placeholder="Email, Username, or Phone" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <a href="forgot password.php">Forgot password?</a>
    <p>Don’t have an account? <a href="signup.php">Sign Up</a></p>
  </div>
</body>
</html>
