<?php 
session_start();
include 'db_connect.php'; // $conn

$messages = [];

function s($v) { return trim($v ?? ''); }

// Handle adding new parent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_parent'])) {
    $name = s($_POST['name']);
    $email = s($_POST['email']);
    $phone = s($_POST['phone']);
    $password = password_hash('default123', PASSWORD_DEFAULT);

    if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $phone) {
        $stmt = $conn->prepare("INSERT INTO users (username,email,role,phone,password) VALUES (?,?,?,?,?)");
        $role = 'Parent';
        $stmt->bind_param("sssss", $name, $email, $role, $phone, $password);
        if ($stmt->execute()) {
            $messages[] = ['type'=>'success','text'=>'Parent added successfully. Default password: default123'];
        } else {
            $messages[] = ['type'=>'error','text'=>'Error adding parent. Email might already exist.'];
        }
        $stmt->close();
    } else {
        $messages[] = ['type'=>'error','text'=>'Please fill all fields correctly.'];
    }
}

// Fetch existing parents
$parents = $conn->query("SELECT id, username, email, phone FROM users WHERE role='Parent' ORDER BY username ASC")->fetch_all(MYSQLI_ASSOC);

// Admin profile
$user_id = $_SESSION['user_id'];
$profile = $conn->query("SELECT username FROM users WHERE id=$user_id")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Parents</title>
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
html, body {margin:0; padding:0; height:100%; font-family: Arial, sans-serif; background:#f4f6f8;}
.dashboard {display:flex; min-height:100vh;}
.sidebar {width:210px; background:#2c3e50; color:#fff; padding:20px; display:flex; flex-direction:column; height:100vh; position:fixed; top:0; left:0;}
.sidebar h2 {margin-top:0; margin-bottom:20px;}
.sidebar a {display:block; color:#fff; text-decoration:none; padding:10px 12px; border-radius:4px; margin-bottom:5px;}
.sidebar a.active, .sidebar a:hover {background:#34495e; padding-left:15px;}
.main-content {margin-left:240px; padding:24px; flex-grow:1; min-height:100vh; box-sizing:border-box;}
.card {background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
table {width:100%; border-collapse:collapse;}
table th, table td {border:1px solid #ddd; padding:10px; text-align:left;}
table th {background:#34495e; color:white;}
button, a.edit, a.delete {padding:6px 12px; border:none; border-radius:6px; cursor:pointer; text-decoration:none;}
button.edit, a.edit {background:#3498db; color:#fff;}
button.delete, a.delete {background:#e74c3c; color:#fff;}
.msg {padding:10px; margin-bottom:15px; border-radius:6px;}
.msg.success {background:#e9f8f0; color:#2ecc71;}
.msg.error {background:#fdecea; color:#e74c3c;}
</style>
</head>
<body>
<!-- Sidebar -->
<div class="sidebar">
   <h2>Admin</h2>
   <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
   <a href="admin manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
   <a href="manage_healthworkers.php"><i class="fas fa-user-nurse"></i> Manage Health Workers</a>
   <a href="admin manage_parents.php" class="active"><i class="fas fa-users"></i> Manage Parents</a>
   <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccines</a>
   <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
   <a href="Admin settings.php"><i class="fas fa-cogs"></i> Settings</a>
   <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<main class="main-content">
  <header>
    <h1>Parent Management</h1>
    <div>Signed in as <strong><?= htmlspecialchars($profile['username'] ?? '') ?></strong></div>
  </header>A

  <?php foreach($messages as $m): ?>
    <div class="msg <?= $m['type']=='success'?'success':'error' ?>"><?= htmlspecialchars($m['text'] ?? '') ?></div>
  <?php endforeach; ?>

  <!-- Add Parent Form -->
  <div class="card">
    <h2><i class="fas fa-user-plus"></i> Add New Parent</h2>
    <form method="POST">
      <label>Full Name</label>
      <input type="text" name="name" required>

      <label>Email</label>
      <input type="email" name="email" required>

      <label>Phone</label>
      <input type="text" name="phone" required>

      <button type="submit" name="add_parent"><i class="fas fa-plus-circle"></i> Add Parent</button>
    </form>
  </div>

  <!-- List Existing Parents -->
  <div class="card">
    <h2><i class="fas fa-users"></i> Existing Parents</h2>
    <table>
      <thead>
        <tr>
          <th>Name</th><th>Email</th><th>Phone</th><th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if($parents): ?>
          <?php foreach($parents as $p): ?>
            <tr>
              <td><?= htmlspecialchars($p['username'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['email'] ?? '') ?></td>
              <td><?= htmlspecialchars($p['phone'] ?? '') ?></td>
              <td>
                <a href="edit_parent.php?id=<?= $p['id'] ?>" class="edit"><i class="fas fa-edit"></i> Edit</a>
                <a href="delete_parent.php?id=<?= $p['id'] ?>" class="delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i> Delete</a>
              </td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No parents found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</main>
</body>
</html>
