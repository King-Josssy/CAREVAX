<?php  
session_start();

// Only health workers can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ---- Fetch existing children for dropdown ----
$children_list = [];
$result = $conn->query("SELECT id, name, dob FROM children ORDER BY name ASC");
while($row = $result->fetch_assoc()) {
    $children_list[] = $row;
}

// ---- Fetch existing vaccines for dropdown ----
$vaccine_list = [];
$result = $conn->query("SELECT id, name FROM vaccines ORDER BY name ASC");
while($row = $result->fetch_assoc()) {
    $vaccine_list[] = $row;
}

// ---- Handle Child Registration / Vaccine Assignment ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_child'])) {

    $errors = [];

    if (!empty($_POST['existing_child'])) {
        // Assign vaccine to existing child
        $child_id = $_POST['existing_child'];

        if (empty($_POST['vaccine_id'])) {
            $errors[] = "Please select a vaccine for the existing child.";
        }

    } else {
        // Register new child — check required fields
        $child_name = trim($_POST['child_name']);
        $child_dob = trim($_POST['child_dob']);
        $child_gender = trim($_POST['child_gender']);
        $mother_name = trim($_POST['mother_name']);
        $mother_phone = trim($_POST['mother_phone']);
        $mother_email = trim($_POST['mother_email'] ?? '');

        if (!$child_name || !$child_dob || !$child_gender || !$mother_name || !$mother_phone) {
            $errors[] = "Please fill in all required fields for new child and mother.";
        }

        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM mothers WHERE phone=?");
            $stmt->bind_param("s", $mother_phone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($mother_id);
                $stmt->fetch();
            } else {
                $stmt_insert = $conn->prepare("INSERT INTO mothers (name, phone, email) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("sss", $mother_name, $mother_phone, $mother_email);
                $stmt_insert->execute();
                $mother_id = $stmt_insert->insert_id;
                $stmt_insert->close();
            }
            $stmt->close();

            $stmt_child = $conn->prepare("INSERT INTO children (name, dob, gender, mother_id) VALUES (?, ?, ?, ?)");
            $stmt_child->bind_param("sssi", $child_name, $child_dob, $child_gender, $mother_id);
            if ($stmt_child->execute()) {
                $success_message = "Child registered successfully!";
            }
            $child_id = $stmt_child->insert_id;
            $stmt_child->close();
        }
    }

    if (empty($errors) && !empty($_POST['vaccine_id'])) {
        $vaccine_id = $_POST['vaccine_id'];
        $date_given = $_POST['date_given'] ?? null;
        $stmt_vac = $conn->prepare("INSERT INTO patient_vaccines (child_id, vaccine_id, status, date_given) VALUES (?, ?, 'upcoming', ?)");
        $stmt_vac->bind_param("iis", $child_id, $vaccine_id, $date_given);
        $stmt_vac->execute();
        $stmt_vac->close();
    }
}

// Fetch total children
$total_sql = "SELECT COUNT(*) AS total_children FROM children";
$total_result = $conn->query($total_sql);
$total_children = $total_result->fetch_assoc()['total_children'] ?? 0;

// Vaccinations completed today
$today = date('Y-m-d');
$completed_sql = "SELECT COUNT(*) AS completed_today 
                  FROM patient_vaccines 
                  WHERE status='completed' AND date_given=?";
$completed_stmt = $conn->prepare($completed_sql);
$completed_stmt->bind_param("s", $today);
$completed_stmt->execute();
$completed_today = $completed_stmt->get_result()->fetch_assoc()['completed_today'] ?? 0;
$completed_stmt->close();

// Upcoming vaccinations
$upcoming_sql = "SELECT COUNT(*) AS upcoming_count 
                 FROM patient_vaccines 
                 WHERE status='upcoming'";
$upcoming_result = $conn->query($upcoming_sql);
$upcoming_count = $upcoming_result->fetch_assoc()['upcoming_count'] ?? 0;

// Missed vaccinations
$missed_sql = "SELECT COUNT(*) AS missed_count 
               FROM patient_vaccines 
               WHERE status='missed'";
$missed_result = $conn->query($missed_sql);
$missed_count = $missed_result->fetch_assoc()['missed_count'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Health Worker Dashboard - CareVax</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {
  display: flex;
  margin: 0;
  font-family: Arial, sans-serif;
  min-height: 100vh;
  background: url('va.jpg') no-repeat center center fixed;
  background-size: cover;
  position: relative;
}
body::before {
  content: "";
  position: absolute;
  inset: 0;
  background: rgba(0, 0, 0, 0.85);
  z-index: 0;
}

/* Sidebar */
.sidebar {
  width: 180px;
  background: #2c3e50;
  color: white;
  display: flex;
  flex-direction: column;
  padding: 20px;
  height: 100vh;
  position: fixed;
  z-index: 2;
  transform: translateX(-220px); /* Hidden by default */
  transition: transform 0.3s ease;
}
.sidebar.active {
  transform: translateX(0); /* Show when active */
}
.sidebar h2 { margin-bottom: 30px; }
.sidebar a {
  color: white;
  text-decoration: none;
  margin: 10px 0;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px;
  border-radius: 8px;
}
.sidebar a:hover { background: #34495e; }

/* Hamburger */
#sidebarToggle {
  position: fixed;
  top: 15px;
  left: 15px;
  z-index: 3;
  cursor: pointer;
  color: white;
  font-size: 24px;
}

/* Main content */
.main {
  margin-left: 10%;
  padding: 30px;
  flex: 1;
  overflow-y: auto;
  position: relative;
  z-index: 1;
  transition: margin-left 0.3s ease;
}
.sidebar.active ~ .main {
  margin-left: 220px;
}

.main h2 { margin-bottom: 20px; color: #ffffff; }
.cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
.card {
  background: rgba(255, 255, 255, 0.85);
  padding: 20px;
  border-radius: 32px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  width: 170px;
  text-align: center;
  transition: transform 0.2s;
  border-top: 5px solid transparent;
}
.card:hover { transform: translateY(-5px); }
.card h3 { margin-bottom: 10px; font-size: 18px; }
.card p { font-size: 20px; font-weight: bold; }

form {
  background: rgba(255, 255, 255, 0.15);
  backdrop-filter: blur(10px);
  padding: 40px;
  border-radius: 15px;
  box-shadow: 0 4px 25px rgba(0,0,0,0.3);
  margin: 50px auto;
  max-width: 800px;
  z-index: 1;
  color: white;
}
form h3 {
  margin-top: 0;
  margin-bottom: 20px;
  color: #05dfefff;
  text-align: center;
}
form input, form select {
  width: 60%;
  padding: 12px;
  margin-bottom: 15px;
  margin-left: 120px;
  border-radius: 8px;
  border: 1px solid #ccc;
  font-size: 15px;
  background-color: rgba(255, 255, 255, 0.8);
  color: #333;
}
form input[type="submit"] {
  background: #3498db;
  color: white;
  border: none;
  padding: 12px 16px;
  cursor: pointer;
  border-radius: 8px;
  font-size: 16px;
  width: 30%;
  display: block;
  margin: 20px auto 0 auto;
}
form input[type="submit"]:hover { background: #2980b9; }
.success-message {
  background: #d4edda;
  color: #155724;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  max-width: 800px;
  margin: 10px auto;
  font-weight: bold;
}
.error-message {
  background: #f8d7da;
  color: #721c24;
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  max-width: 800px;
  margin: 10px auto;
  font-weight: bold;
}
</style>
</head>
<body>

<div class="sidebar">
  <h2>CareVax</h2>
  <a href="health worker.php">🏠 Dashboard</a>
  <a href="health register.php">💉 Register Vaccination</a>
  <a href="manage_patients.php">👥 Manage Patients</a>
  <a href="healthworker_report.php">📊 Reports</a>
  <a href="ManageHouseholds.php">🏡 Manage Households</a>
  <a href="FollowUps.php">📋 Follow Ups</a>
  <a href="health settings.php">⚙️ Settings</a>
  <a href="logout.php">🚪 Logout</a>
</div>

<div id="sidebarToggle"><i class="fas fa-bars"></i></div>

<div class="main">
    <h2>Health Worker Dashboard</h2>

    <?php if (!empty($errors)): ?>
        <?php foreach($errors as $error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <div class="cards">
        <div class="card" style="border-top: 5px solid #3498db;">
            <h3><i class="fas fa-users" style="color:#3498db;"></i> Total Children</h3>
            <p><?= $total_children ?></p>
        </div>
        <div class="card" style="border-top: 5px solid #3498db;">
            <h3><i class="fas fa-check" style="color:#3498db;"></i> Vaccinations Today</h3>
            <p><?= $completed_today ?></p>
        </div>
        <div class="card" style="border-top: 5px solid #3498db;">
            <h3><i class="fas fa-calendar" style="color:#3498db;"></i> Upcoming Vaccines</h3>
            <p><?= $upcoming_count ?></p>
        </div>
        <div class="card" style="border-top: 5px solid #3498db;">
            <h3><i class="fas fa-exclamation-triangle" style="color:#3498db;"></i> Missed Vaccinations</h3>
            <p><?= $missed_count ?></p>
        </div>
    </div>

    <h2>Register Child / Assign Vaccine</h2>
    <form method="post" action="">
        <h3>Select Existing Child (optional)</h3>
        <select name="existing_child" id="existing_child_select">
            <option value="">-- New Child --</option>
            <?php foreach($children_list as $child): ?>
                <option value="<?= $child['id'] ?>"><?= htmlspecialchars($child['name'] . " (" . $child['dob'] . ")") ?></option>
            <?php endforeach; ?>
        </select>

        <div id="new_child_fields">
            <h3>Child Information</h3>
            <input type="text" name="child_name" placeholder="Child Name">
            <input type="date" name="child_dob">
            <select name="child_gender">
                <option value="">Select Gender</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
            </select>

            <h3>Mother Information</h3>
            <input type="text" name="mother_name" placeholder="Mother Name">
            <input type="text" name="mother_phone" placeholder="Mother Phone">
            <input type="email" name="mother_email" placeholder="Mother Email">
        </div>

        <h3>Select Vaccine</h3>
        <select name="vaccine_id" id="vaccine_select">
            <option value="">-- Select Vaccine --</option>
            <?php foreach($vaccine_list as $vaccine): ?>
                <option value="<?= $vaccine['id'] ?>"><?= htmlspecialchars($vaccine['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <h3>Date to be Given</h3>
        <input type="date" name="date_given" id="date_given">

        <input type="submit" name="register_child" value="Submit">
    </form>
</div>

<script>
const existingChildSelect = document.getElementById('existing_child_select');
const newChildFields = document.getElementById('new_child_fields');
existingChildSelect.addEventListener('change', function() {
    if (this.value) {
        Array.from(newChildFields.querySelectorAll('input, select')).forEach(el => el.disabled = true);
    } else {
        Array.from(newChildFields.querySelectorAll('input, select')).forEach(el => el.disabled = false);
    }
});

// Sidebar toggle
const sidebar = document.querySelector('.sidebar');
const toggle = document.getElementById('sidebarToggle');
toggle.addEventListener('click', () => {
    sidebar.classList.toggle('active');
});
</script>
</body>
</html>
