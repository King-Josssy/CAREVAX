<?php
session_start();
require_once 'vendor/autoload.php';

// Restrict access to health workers
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

// --- Database connection ---
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Fetch children with latest vaccine status ---
$children = $conn->query("
    SELECT ch.id AS child_id, ch.name AS child_name, ch.dob, ch.gender,
           COALESCE(u.name, m.name) AS parent_name,
           COALESCE(u.phone_number, m.phone) AS contact,
           COALESCE(pv.status, 'N/A') AS vaccine_status,
           pv.vaccine_id, pv.date_scheduled
    FROM children ch
    LEFT JOIN users u ON ch.parent_id = u.id AND u.role='parent'
    LEFT JOIN mothers m ON ch.mother_id = m.id
    LEFT JOIN (
        SELECT child_id, MAX(id) AS latest_vaccine_id
        FROM patient_vaccines
        GROUP BY child_id
    ) latest ON ch.id = latest.child_id
    LEFT JOIN patient_vaccines pv ON pv.id = latest.latest_vaccine_id
    ORDER BY ch.name ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Patients - CareVax</title>
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
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  z-index: 0;
}
.sidebar, .main { position: relative; z-index: 1; }
.main h1 { margin-bottom: 20px; color: #fff; }
.sidebar {
    width: 220px;
    background: #2c3e50;
    color: white;
    padding: 20px;
    height: 100vh;
    position: fixed;
    transition: transform 0.3s ease;
}
.sidebar a {
    color: white;
    text-decoration: none;
    display: block;
    margin: 10px 0;
    padding: 10px;
    border-radius: 6px;
}
.sidebar a:hover, .sidebar a.active { background: #34495e; }
.sidebar.hidden { transform: translateX(-220px); }

.main {
    margin-left: 240px;
    padding: 30px;
    flex: 1;
    transition: margin-left 0.3s ease;
}
.main.shifted { margin-left: 0; }

table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 10px;
    overflow: hidden;
}
th, td {
    padding: 10px;
    border-bottom: 1px solid #ddd;
    text-align: left;
}
th { background: #3498db; color: white; }
button {
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    margin-right: 5px;
}
.edit-btn { background: #2d2704ff; color: white; }
.reschedule-btn { background: #27ae60; color: white; }
.sms-btn { background: #3498db; color: white; }
form {
    margin-top: 20px;
    background: white;
    padding: 15px;
    border-radius: 10px;
}
label { margin-right: 10px; }
select {
    padding: 5px 8px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

/* Hamburger menu toggle */
.menu-toggle {
    position: fixed;
    top: 0px;
    left: 0px;
    font-size: 26px;
    color: white;
   
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    z-index: 1001;
}
</style>
</head>
<body>

<!-- Hamburger Menu -->
<div class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('hidden'); document.querySelector('.main').classList.toggle('shifted');">
    &#9776;
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

<div class="main">
    <h1>Manage Patients</h1>

    <table>
        <thead>
            <tr>
                <th>Child Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Parent/Mother Name</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($children && $children->num_rows > 0):
                while($row = $children->fetch_assoc()):
                    $child_id = $row['child_id'] ?? '';
                    $child_name = htmlspecialchars($row['child_name'] ?? 'Unknown');
                    $dob = htmlspecialchars($row['dob'] ?? 'N/A');
                    $gender = htmlspecialchars($row['gender'] ?? 'N/A');
                    $parent_name = htmlspecialchars($row['parent_name'] ?? 'N/A');
                    $contact = htmlspecialchars($row['contact'] ?? 'N/A');
                    $status = htmlspecialchars(ucfirst($row['vaccine_status'] ?? 'N/A'));
            ?>
            <tr>
                <td><?= $child_name; ?></td>
                <td><?= $dob; ?></td>
                <td><?= $gender; ?></td>
                <td><?= $parent_name; ?></td>
                <td><?= $contact; ?></td>
                <td>
                    <span style="font-weight:bold; color:
                        <?= strtolower($status) == 'completed' ? '#27ae60' :
                           (strtolower($status) == 'missed' ? '#c0392b' : '#2980b9'); ?>">
                        <?= $status; ?>
                    </span>
                </td>
                <td>
                    <button class="edit-btn" onclick="window.location.href='edit_child.php?id=<?= $child_id; ?>'">Edit</button>
                    <button class="reschedule-btn" onclick="window.location.href='reschedule.php?id=<?= $child_id; ?>'">Reschedule</button>
                </td>
            </tr>
            <?php 
                endwhile;
            else:
            ?>
            <tr>
                <td colspan="7" style="text-align:center; color:red;">No patient data found.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- SMS/Email Reminders Form -->
    <form method="post" action="send_reminders.php">
        <h3>Send Reminders</h3>
        <p style="margin-bottom:10px;">This will send reminders automatically to all parents with upcoming or missed vaccinations.</p>

        <label for="method">Send via:</label>
        <select name="method" id="method" required>
            <option value="" disabled selected>-- Choose Method --</option>
            <option value="SMS">SMS (EgoSMS)</option>
            <option value="Email">Email</option>
        </select>

        <button type="submit" class="sms-btn">Send Reminders</button>
    </form>
</div>

</body>
</html>
