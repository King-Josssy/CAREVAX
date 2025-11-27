<?php 
session_start();

// Only health workers can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$message = "";

// Fetch user details
$sql = "SELECT username, email, phone, role FROM users WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Handle password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password=? WHERE id=?");
    $update->bind_param("si", $new_password, $user_id);

    if ($update->execute()) {
        $message = "✅ Password updated successfully!";
    } else {
        $message = "❌ Error updating password.";
    }
    $update->close();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $email = $_POST['email'];
    $phone = $_POST['phone'];

    $update = $conn->prepare("UPDATE users SET email=?, phone=? WHERE id=?");
    $update->bind_param("ssi", $email, $phone, $user_id);

    if ($update->execute()) {
        $message = "✅ Profile updated successfully!";
        $user['email'] = $email;
        $user['phone'] = $phone;
    } else {
        $message = "❌ Error updating profile.";
    }
    $update->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Health Worker Settings - CareVax</title>
  <style>
    /* ===== Body & Background ===== */
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      min-height: 100vh;
      background: url('va.jpg') no-repeat center center fixed;
      background-size: cover;
      position: relative;
    }
    body::before {
      content: "";
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.65);
      z-index: 0;
    }

    /* ===== Hamburger Icon ===== */
    .menu-icon {
      position: fixed;
      top: 15px;
      left: 20px;
      width: 30px;
      height: 25px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      cursor: pointer;
      z-index: 1001;
    }
    .menu-icon div {
      height: 4px;
      width: 100%;
      background-color: white;
      border-radius: 2px;
      transition: all 0.3s ease;
    }

    /* ===== Sidebar ===== */
    .sidebar {
      position: fixed;
      top: 0;
      left: -250px;
      width: 250px;
      height: 100%;
      background-color: #2c3e50;
      color: white;
      display: flex;
      flex-direction: column;
      padding: 20px;
      box-sizing: border-box;
      transition: left 0.3s ease;
      z-index: 1000;
    }
    .sidebar.active {
      left: 0;
    }
    .sidebar a, .sidebar button {
      color: white;
      text-decoration: none;
      margin: 12px 0;
      padding: 10px;
      border-radius: 8px;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: background 0.3s ease;
      background: none;
      border: none;
      cursor: pointer;
      font-size: 16px;
      text-align: left;
    }
    .sidebar a:hover, .sidebar button:hover {
      background: #34495e;
    }
    .dropdown-container {
      display: none;
      flex-direction: column;
      margin-left: 10px;
    }

    /* ===== Main Content ===== */
    .main {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      position: relative;
      z-index: 1;
      padding: 20px;
      box-sizing: border-box;
    }

    /* ===== Container ===== */
    .container {
      background: white;
      padding: 30px;
      border-radius: 12px;
      max-width: 600px;
      width: 100%;
      box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.2);
      text-align: center;
    }

    /* ===== Headings ===== */
    h2, h3 {
      color: #2c3e50;
      margin-bottom: 20px;
    }
    h3 {
      margin-top: 30px;
    }

    /* ===== Inputs & Buttons ===== */
    input, button {
      width: 80%;
      padding: 12px;
      margin-bottom: 15px;
      border-radius: 8px;
      border: 1px solid #ccc;
      font-size: 15px;
      background-color: rgba(255, 255, 255, 0.9);
      color: #333;
    }
    button {
      background: #3498db;
      color: white;
      border: none;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
    }
    button:hover {
      background: #2980b9;
    }

    /* ===== Messages ===== */
    .message {
      font-size: 1rem;
      margin-bottom: 10px;
      color: darkred;
    }

    /* ===== Profile Info ===== */
    .profile-info {
      margin-bottom: 20px;
    }
    .profile-info p {
      font-size: 1rem;
      margin: 5px 0;
    }
  </style>
</head>
<body>

  <!-- Hamburger Icon -->
  <div class="menu-icon" onclick="toggleSidebar()">
    <div></div>
    <div></div>
    <div></div>
  </div>

  <!-- Sidebar -->
  <div id="sidebar" class="sidebar">
    <h2>CareVax</h2>
    <a href="health worker.php">🏠 Dashboard</a>
    <button class="dropdown-btn">💉 Vaccination ▼</button>
    <div class="dropdown-container">
      <a href="health register.php">Register Vaccination</a>
      <a href="manage_patients.php">Manage Patients</a>
    </div>
    <a href="healthworker_report.php">📊 Reports</a>
    <button class="dropdown-btn">🏡 Households ▼</button>
    <div class="dropdown-container">
      <a href="ManageHouseholds.php">Manage Households</a>
      <a href="FollowUps.php">Follow Ups</a>
    </div>
    <a href="health settings.php">⚙️ Settings</a>
    <a href="logout.php">🚪 Logout</a>
  </div>

  <!-- Main Content -->
  <div class="main">
    <div class="container">
      <h2>Health Worker Settings</h2>

      <?php if ($message): ?>
        <p class="message"><?= htmlspecialchars($message) ?></p>
      <?php endif; ?>

      <div class="profile-info">
        <p><strong>Username:</strong> <?= htmlspecialchars($user['username']) ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
        <p><strong>Role:</strong> <?= htmlspecialchars($user['role']) ?></p>
      </div>

      <!-- Update Profile -->
      <form method="POST">
        <h3>Update Profile</h3>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email" required>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Phone" required>
        <button type="submit" name="update_profile">Update Profile</button>
      </form>

      <!-- Update Password -->
      <form method="POST">
        <h3>Change Password</h3>
        <input type="password" name="new_password" placeholder="New Password" required>
        <button type="submit" name="update_password">Update Password</button>
      </form>
    </div>
  </div>

  <script>
    // Sidebar toggle
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('active');
    }

    // Dropdown functionality
    const dropdowns = document.querySelectorAll('.dropdown-btn');
    dropdowns.forEach(btn => {
      btn.addEventListener('click', () => {
        btn.classList.toggle('active');
        const container = btn.nextElementSibling;
        container.style.display = container.style.display === 'flex' ? 'none' : 'flex';
      });
    });
  </script>

</body>
</html>
