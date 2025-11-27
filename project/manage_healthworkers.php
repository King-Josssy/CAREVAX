<?php
session_start();
include 'db_connect.php'; // make sure $conn is defined here

// Check if connection is valid
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$messages = [];

// Helper function to sanitize input
function s($v) { return trim($v ?? ''); }

// Handle adding new health worker
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_worker'])) {
    $name = s($_POST['name']);
    $email = s($_POST['email']);
    $role = s($_POST['role']);
    $phone = s($_POST['phone']);
    $password = password_hash('default123', PASSWORD_DEFAULT); // default password

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $role && $phone) {
        $stmt = $conn->prepare("INSERT INTO users (username,email,role,phone,password) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssss", $name, $email, $role, $phone, $password);
        if ($stmt->execute()) {
            $messages[] = ['type'=>'success','text'=>'Health worker added successfully. Default password: default123'];
        } else {
            $messages[] = ['type'=>'error','text'=>'Error adding health worker. Email might already exist.'];
        }
        $stmt->close();
    } else {
        $messages[] = ['type'=>'error','text'=>'Please fill all fields correctly.'];
    }
}

// Fetch existing health workers (exclude parents)
$workers = $conn->query("SELECT id, username, email, role, phone FROM users WHERE role != 'parent' ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);

// Admin profile
$user_id = $_SESSION['user_id'] ?? 0;
$profile = $conn->query("SELECT username FROM users WHERE id=$user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Health Workers</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
html, body { 
  margin: 0;
  font-family: Arial, sans-serif;
  min-height: 100vh;
  background: url('re.jpeg') no-repeat center center fixed;
  background-size: cover;
  position: relative;
  z-index: 0;
}

/* Dark overlay — only affects background */
body::before {
  content: "";
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.55); /* adjust darkness */
  z-index: 0;
  pointer-events: none; /* content remains clickable */
}

/* All main content above overlay */
.dashboard,
.h1,
.main-content,
.sidebar,
.card,
.form-container {
  position: relative;
  z-index: 1; /* content appears above overlay */
}



h1{
  color: #fff;
  margin-bottom: 20px;
}

.dashboard {
    display: flex;
    min-height: 100vh; /* full height */
}

/* Sidebar */
.sidebar {
    width: 240px;
    background: #2c3e50;
    color: #fff;
    padding: 20px;
    display: flex;
    flex-direction: column;
    height: 100vh;
    position: fixed;
    top: 0;
    left: 0;
}

.sidebar h2 {
    margin-top: 0;
    margin-bottom: 20px;
}

.sidebar a {
    display: block;
    color: #fff;
    text-decoration: none;
    padding: 10px 12px;
    border-radius: 4px;
    margin-bottom: 5px;
}

.sidebar a.active, .sidebar a:hover {
    background: #34495e;
    padding-left: 15px;
}

/* Main content */
.main-content {
    margin-left: 240px;
    padding: 24px;
    flex-grow: 1;
    min-height: 100vh;
    box-sizing: border-box;
}

/* Card layout */
.card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

/* Table styling */
table {
    width: 100%;
    border-collapse: collapse;
}

table th, table td {
    border: 1px solid #000000ff;
    padding: 10px;
    text-align: left;
}

table th {
    background: #02284bff;
}

/* Buttons */
button, a.edit, a.delete {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    text-decoration: none;
}

button.edit, a.edit {
    background: #3498db;
    color: #fff;
}

button.delete, a.delete {
    background: #e74c3c;
    color: #fff;
}

/* Messages */
.msg {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 6px;
}

.msg.success {background:#e9f8f0;color:#2ecc71;}
.msg.error {background:#fdecea;color:#e74c3c;}
</style>

</head>
<body>
<div class="dashboard">
   <!-- Sidebar -->
 <div class="sidebar">
   <h2>Admin</h2>
   <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
   <a href="admin manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
   <a href="manage_healthworkers.php"><i class="fas fa-user-nurse"></i> Manage Health Workers</a>
   <a href="admin manage_parents.php"><i class="fas fa-users"></i> Manage Parents</a>
   <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccines</a>
   <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
   <a href="Admin settings.php"><i class="fas fa-cogs"></i> Settings</a>
   <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
 </div>

  <main class="main-content">
    <header>
      <h1>Health Worker Management</h1>
      <div>Signed in as <strong><?= htmlspecialchars($profile['username'] ?? 'Admin') ?></strong></div>
    </header>

    <?php foreach($messages as $m): ?>
      <div class="msg <?= $m['type'] == 'success' ? 'success':'error' ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <!-- Add Health Worker Form -->
    <div class="card">
      <h2><i class="fas fa-user-plus"></i> Add New Health Worker</h2>
      <form method="POST">
        <label>Full Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Role</label>
        <select name="role">
          <option>Doctor</option>
          <option>Nurse</option>
          <option>Pharmacist</option>
          <option>Technician</option>
        </select>

        <label>Phone</label>
        <input type="text" name="phone" required>

        <button type="submit" name="add_worker"><i class="fas fa-plus-circle"></i> Add Worker</button>
      </form>
    </div>

    <!-- List Existing Health Workers -->
    <div class="card">
      <h2><i class="fas fa-users-medical"></i> Existing Health Workers</h2>
      <table>
        <thead>
          <tr>
            <th>Name</th><th>Email</th><th>Role</th><th>Phone</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if($workers): ?>
            <?php foreach($workers as $w): ?>
              <tr>
                <td><?= htmlspecialchars($w['username']) ?></td>
                <td><?= htmlspecialchars($w['email']) ?></td>
                <td><?= htmlspecialchars($w['role']) ?></td>
                <td><?= htmlspecialchars($w['phone']) ?></td>
                <td>
                  <a href="edit_healthworker.php?id=<?= $w['id'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                  <a href="delete_healthworker.php?id=<?= $w['id'] ?>" class="delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i> Delete</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5">No health workers found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </main>
</div>
</body>
</html>
