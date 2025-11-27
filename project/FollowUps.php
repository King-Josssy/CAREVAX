<?php 
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Connection
$host = "localhost";
$user = "root";
$pass = "";
$db   = "carevax";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch VHTs and households for the dropdowns
$vhts = $conn->query("SELECT id, vht_name FROM vhts");
$households = $conn->query("SELECT id, household_name FROM households");

// Flash message variable
$flashMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vht_id = $_POST['vht_id'];
    $household_id = $_POST['household_id'];
    $task = $_POST['task'];
    $due_date = $_POST['due_date'];

    $status = 'Pending';

    $stmt = $conn->prepare("INSERT INTO followups (vht_id, household_id, task, due_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $vht_id, $household_id, $task, $due_date, $status);

    if ($stmt->execute()) {
        $flashMessage = "✅ Reminder assigned successfully!";
    } else {
        $flashMessage = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Follow Ups - Health Worker</title>
<style>
  body { margin:0; font-family: Arial, sans-serif; display:flex; min-height:100vh; background:#f4f6f8; }

  /* Sidebar */
  .sidebar { width:220px; background:#34495e; color:white; display:flex; flex-direction:column; padding:20px; transition: transform 0.3s ease; }
  .sidebar h2 { text-align:center; color:#fff; }
  .sidebar a { color:white; text-decoration:none; padding:10px; margin:5px 0; display:block; border-radius:8px; background:#34495e; }
  .sidebar a:hover, .sidebar a.active { background:#5e7b97; }
  .sidebar.hidden { transform: translateX(-280px); }

  /* Hamburger */
  .menu-toggle { position: fixed; top:15px; left:15px; cursor:pointer; z-index:1000;background-color: #34495e; border-radius: 30%; }
  .menu-toggle div { width:30px; height:4px; background:white; margin:6px 0; }

  /* Main content */
  .main-content { flex:1; padding:20px; transition: margin-left 0.3s ease; margin-left:20px; }
  .main-content.shifted { margin-left:0; }
  h1 { color:#2d3436; }

  form { background:white; padding:20px; border-radius:8px; margin-bottom:20px; }
  input, select, textarea { width:100%; padding:10px; margin:10px 0; border:1px solid #ccc; border-radius:6px; }
  button { background:#0071ce; color:white; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
  button:hover { background:#005fa3; }

  /* Flash message */
  .flash-message { padding:10px; border-radius:6px; margin-bottom:15px; color:white; }
  .flash-success { background:#4CAF50; }
  .flash-error { background:#f44336; }
</style>
</head>
<body>

<!-- Hamburger Icon -->
<div class="menu-toggle" onclick="toggleSidebar()">
  <div></div>
  <div></div>
  <div></div>
</div>

<div class="sidebar">
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

<div class="main-content">
    <h1>Assign Follow-up Tasks</h1>

    <!-- Flash Message -->
    <?php if ($flashMessage): ?>
      <div id="flashMessage" class="flash-message <?= strpos($flashMessage, '✅') !== false ? 'flash-success' : 'flash-error' ?>">
        <?= htmlspecialchars($flashMessage) ?>
      </div>
    <?php endif; ?>

    <form method="POST">
      <label for="vht_id">Select VHT:</label>
      <select name="vht_id" required>
        <option value="">-- Select VHT --</option>
        <?php while ($v = $vhts->fetch_assoc()): ?>
          <option value="<?= $v['id'] ?>"><?= htmlspecialchars($v['vht_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <label for="household_id">Select Household:</label>
      <select name="household_id" required>
        <option value="">-- Select Household --</option>
        <?php while ($h = $households->fetch_assoc()): ?>
          <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['household_name']) ?></option>
        <?php endwhile; ?>
      </select>

      <label for="task">Task Description:</label>
      <textarea name="task" required></textarea>

      <label for="due_date">Due Date:</label>
      <input type="date" name="due_date" required>

      <button type="submit">Assign Task</button>
    </form>
</div>

<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main-content');
    sidebar.classList.toggle('hidden');
    main.classList.toggle('shifted');
}

// Hide flash message after 3 seconds
const flash = document.getElementById('flashMessage');
if(flash){
    setTimeout(() => { flash.style.display = 'none'; }, 3000);
}
</script>

</body>
</html>
