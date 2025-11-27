<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$role = $_SESSION['role'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Welcome - CareVax</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: url('yy.jpg') no-repeat center center/cover;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: white;
    }
    .welcome-box {
      background: rgba(0,0,0,0.6);
      padding: 3rem;
      border-radius: 12px;
      box-shadow: 0px 6px 20px rgba(0,0,0,0.4);
      animation: fadeIn 1s ease-in-out;
    }
    .welcome-box h1 { font-size: 2rem; margin-bottom: 1rem; }
    .welcome-box p { font-size: 1.1rem; margin-bottom: 2rem; }
    .welcome-box a {
      background: linear-gradient(90deg, #4caf50 60%, #388e3c 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 1rem;
      transition: 0.3s;
    }
    .welcome-box a:hover {
      background: linear-gradient(90deg, #45a049 60%, #2e7d32 100%);
    }
    @keyframes fadeIn {
      from {opacity: 0; transform: translateY(-20px);}
      to {opacity: 1; transform: translateY(0);}
    }
  </style>
</head>
<body>
  <div class="welcome-box">
    <h1>Welcome, <?= htmlspecialchars($name) ?> 🎉</h1>
    <p>You have successfully logged in to <b>CareVax</b> as a <b><?= ucfirst($role) ?></b>.</p>
    <?php if ($role === 'parent'): ?>
      <a href="parent.php">Go to Parent Dashboard</a>
    <?php elseif ($role === 'healthworker'): ?>
      <a href="health worker.php">Go to Health Worker Dashboard</a>
    <?php elseif ($role === 'admin'): ?>
      <a href="admin.php">Go to Admin Dashboard</a>
    <?php endif; ?>
  </div>
</body>
</html>
