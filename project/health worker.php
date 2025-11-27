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

// Fetch all children with vaccination status
$children_sql = "SELECT c.id, c.name, c.dob, c.gender,
                        pv.status, pv.date_given, v.name AS vaccine_name
                 FROM children c
                 LEFT JOIN patient_vaccines pv ON c.id = pv.child_id
                 LEFT JOIN vaccines v ON pv.vaccine_id = v.id
                 ORDER BY c.name ASC";
$children_result = $conn->query($children_sql);

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
  z-index: 0;
}
body::before {
  content: "";
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.65);
  z-index: 0;
}

/* Sidebar */
.sidebar, .main {
  position: relative;
  z-index: 1;
}
.sidebar {
    width: 220px;
    background: #2c3e50;
    color: white;
    display: flex;
    flex-direction: column;
    padding: 20px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    transform: translateX(0);
    transition: transform 0.3s ease;
    z-index: 1000;
}
.sidebar.hidden {
    transform: translateX(-280px);
}
.sidebar h2 { margin-bottom: 10px; }
.sidebar a, .dropdown-btn {
    color: white;
    text-decoration: none;
    margin: 5px 0;
    display: block;
    padding: 10px;
    border-radius: 8px;
    background: none;
    border: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
}
.sidebar a:hover, .dropdown-btn:hover { background: #34495e; }

/* Dropdown container */
.dropdown-container { display: none; flex-direction: column; padding-left: 15px; }
.dropdown-container a { padding: 8px 10px; background: #3b4a59; }

/* Hamburger Button */
.menu-toggle {
    position: fixed;
    top: 5px;
    left: 5px;
    font-size: 24px;
    color: white;
    padding: 5px;
    border-radius: 50px;
    cursor: pointer;
    z-index: 1100;
}

/* Main content */
.main {
    margin-left: 240px;
    padding: 30px;
    flex: 1;
    overflow-y: auto;
    transition: margin-left 0.3s ease;
}
.main.shifted { margin-left: 0; }
.main h2 { margin-bottom: 20px; color: #fcfcfcff; }

/* Cards */
.cards { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 30px; }
.card { background: white; padding: 20px; border-radius: 32px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); width: 170px; text-align: center; transition: transform 0.2s; border-top: 5px solid transparent; }
.card:hover { transform: translateY(-5px); }
.card h3 { margin-bottom: 10px; font-size: 18px; }
.card p { font-size: 20px; font-weight: bold; }
.badge-green { background:#2ecc71; color:white; padding:5px 10px; border-radius:12px; font-size: 12px; }
.badge-orange { background:#f39c12; color:white; padding:5px 10px; border-radius:12px; font-size: 12px; }
.badge-red { background:#e74c3c; color:white; padding:5px 10px; border-radius:12px; font-size: 12px; }
.badge-gray { background:#95a5a6; color:white; padding:5px 10px; border-radius:12px; font-size: 12px; }

/* Table */
table { width: 100%; border-collapse: collapse; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
th, td { padding: 15px; text-align: left; }
th { background: #3498db; color: white; position: sticky; top: 0; }
tr:nth-child(even) { background: #f2f2f2; }
tr:hover { background: #d6eaf8; }
</style>
</head>
<body>

<!-- Hamburger -->
<div class="menu-toggle" onclick="toggleSidebar()"><i class="fas fa-bars"></i></div>

<!-- Sidebar -->
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

<!-- Main -->
<div class="main">
    <h2>Health Worker Dashboard</h2>
    <div class="cards">
        <div class="card" style="border-top:5px solid #3498db;"><h3><i class="fas fa-users" style="color:#3498db;"></i> Total Children</h3><p><?= $total_children ?></p></div>
        <div class="card" style="border-top:5px solid #3498db;"><h3><i class="fas fa-check" style="color:#3498db;"></i> Vaccinations Today</h3><p><?= $completed_today ?></p></div>
        <div class="card" style="border-top:5px solid #3498db;"><h3><i class="fas fa-calendar" style="color:#3498db;"></i> Upcoming Vaccines</h3><p><?= $upcoming_count ?></p></div>
        <div class="card" style="border-top:5px solid #3498db;"><h3><i class="fas fa-exclamation-triangle" style="color:#3498db;"></i> Missed Vaccinations</h3><p><?= $missed_count ?></p></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Age</th>
                <th>Vaccine</th>
                <th>Status</th>
                <th>Date Given</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 1;
            while($row = $children_result->fetch_assoc()):
                $dob = new DateTime($row['dob']);
                $diff = (new DateTime())->diff($dob);
                $age = $diff->y . " yrs " . $diff->m . " mos";
                $statusClass = "badge-gray";
                if ($row['status'] === "completed") $statusClass = "badge-green";
                elseif ($row['status'] === "upcoming") $statusClass = "badge-orange";
                elseif ($row['status'] === "missed") $statusClass = "badge-red";
            ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['dob']) ?></td>
                <td><?= htmlspecialchars($row['gender']) ?></td>
                <td><?= $age ?></td>
                <td><?= htmlspecialchars($row['vaccine_name'] ?? '-') ?></td>
                <td><span class="<?= $statusClass ?>"><?= ucfirst($row['status'] ?? 'N/A') ?></span></td>
                <td><?= $row['date_given'] ?? '-' ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<script>
// Toggle sidebar
function toggleSidebar(){
    const sidebar = document.querySelector('.sidebar');
    const main = document.querySelector('.main');
    sidebar.classList.toggle('hidden');
    main.classList.toggle('shifted');
}

// Dropdown functionality
document.addEventListener('DOMContentLoaded', function(){
    const dropdowns = document.querySelectorAll('.dropdown-btn');
    dropdowns.forEach(btn => {
        btn.addEventListener('click', function(){
            this.classList.toggle('active');
            const container = this.nextElementSibling;
            if(container.style.display === "flex") container.style.display = "none";
            else container.style.display = "flex";
        });
    });
});
</script>
</body>
</html>
