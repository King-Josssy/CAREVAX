<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'parent') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$parent_id = $_SESSION['user_id'];

// Fetch parent name
$parentQuery = $conn->query("SELECT name FROM users WHERE id = $parent_id LIMIT 1");
$parentName = ($parentQuery && $parentQuery->num_rows > 0) ? $parentQuery->fetch_assoc()['name'] : "Parent/Guardian";

// Fetch counts
$children = $conn->query("SELECT * FROM children WHERE parent_id = $parent_id");
$total_children = ($children) ? $children->num_rows : 0;

$upcoming_vaccines = $conn->query("
    SELECT c.name as child_name, v.name as vaccine_name, pv.date_scheduled
    FROM patient_vaccines pv
    JOIN children c ON pv.child_id = c.id
    JOIN vaccines v ON pv.vaccine_id = v.id
    WHERE c.parent_id = $parent_id AND pv.status = 'upcoming' AND pv.date_scheduled >= CURDATE() AND pv.date_scheduled <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
");
$upcomingCount = ($upcoming_vaccines) ? $upcoming_vaccines->num_rows : 0;

$missed_vaccines = $conn->query("
    SELECT c.name as child_name, v.name as vaccine_name, pv.date_scheduled
    FROM patient_vaccines pv
    JOIN children c ON pv.child_id = c.id
    JOIN vaccines v ON pv.vaccine_id = v.id
    WHERE c.parent_id = $parent_id AND pv.status = 'missed'
");
$missedCount = ($missed_vaccines) ? $missed_vaccines->num_rows : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Parent Dashboard - CareVax</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
 body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('parent.webp') no-repeat center center;
    background-size: cover;
    height: 100vh;
 }
 .dashboard {
    display: flex;
 }
 /* Sidebar */
 .sidebar {
    width: 250px;
    background: #2c3e50;
    color: white;
    min-height: 100vh;
    padding-top: 20px;
 }
 .sidebar h2 {
    text-align: center;
    margin-bottom: 1rem;
 }
 .sidebar ul {
    list-style: none;
    padding: 0;
 }
 .sidebar ul li {
    margin: 1rem 0;
 }
 .sidebar ul li a {
    color: white;
    text-decoration: none;
    padding: 10px 20px;
    display: block;
    transition: 0.3s;
 }
 .sidebar ul li a:hover {
    background: #388e3c;
    border-radius: 8px;
 }
 /* Main content */
 .main-content {
    flex: 1;
    padding: 2rem;
 }
 .main-content header h1 {
    font-size: 1.8rem;
    margin-bottom: 2rem;
 }
 /* Cards */
 .cards {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
 }
 .card-link {
    flex: 1;
    text-decoration: none;
 }
 .card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(238, 210, 210, 0.1);
    text-align: center;
    transition: 0.3s;
 }
 .card:hover {
    box-shadow: 0px 10px 20px rgba(0,0,0,0.2);
 }
 .card h3 {
    margin-bottom: 0.5rem;
 }
 /* Table */
 .table-section {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0px 6px 15px rgba(0,0,0,0.1);
 }
 table {
    width: 100%;
    border-collapse: collapse;
 }
 th, td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
    text-align: left;
 }
 th {
    background: #388e3c;
    color: white;
 }
 a {
    color: #2980b9;
    text-decoration: none;
 }
 a:hover {
    text-decoration: underline;
 }
</style>
</head>
<body>
<div class="dashboard">
  <aside class="sidebar">
    <h2>CareVax</h2>
    <ul>
      <li><a href="parent.php"><i class="fas fa-home"></i> Home</a></li>
      <li><a href="register_child.php"><i class="fas fa-child"></i> Register Child</a></li>
      <li><a href="history.php"><i class="fas fa-file-medical-alt"></i> Immunization History</a></li>
      <li><a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a></li>
      <li><a href="parent_report.php"><i class="fas fa-chart-line"></i> Reports</a></li>
      <li><a href="settings.php"><i class="fas fa-cogs"></i> Settings</a></li>
      <li><a href="logout.php" id="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
    </ul>
  </aside>
  <main class="main-content">
    <header>
      <h1><?= htmlspecialchars($parentName) ?></h1>
    </header>
    <section class="cards">
      <a href="upcoming_vaccines.php" class="card-link">
        <div class="card">
          <h3><i class="fas fa-syringe"></i> Upcoming Vaccines</h3>
          <p><?= $upcomingCount ?> scheduled this week</p>
        </div>
      </a>
      <a href="missed_vaccines.php" class="card-link">
        <div class="card">
          <h3><i class="fas fa-exclamation-triangle"></i> Missed Vaccines</h3>
          <p><?= $missedCount ?> overdue</p>
        </div>
      </a>
      <a href="children_list.php" class="card-link">
        <div class="card">
          <h3><i class="fas fa-users"></i> Total Children</h3>
          <p><?= $total_children ?> Registered</p>
        </div>
      </a>
    </section>
    <section class="table-section">
      <h2><i class="fas fa-notes-medical"></i> Recent Vaccination Records</h2>
      <table>
        <thead>
          <tr>
            <th>Child Name</th>
            <th>Vaccine</th>
            <th>Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $records = $conn->query("
            SELECT c.id as child_id, c.name as child_name, v.id as vaccine_id, v.name as vaccine_name, pv.date_scheduled, pv.status
            FROM patient_vaccines pv
            JOIN children c ON pv.child_id = c.id
            JOIN vaccines v ON pv.vaccine_id = v.id
            WHERE c.parent_id = $parent_id
            ORDER BY pv.date_scheduled DESC
            LIMIT 5
          ");
          if($records && $records->num_rows > 0){
              while($row = $records->fetch_assoc()){
                  $status_icon = $row['status'] == 'completed' ? 'fa-check-circle' : ($row['status']=='upcoming' ? 'fa-hourglass-half' : 'fa-exclamation-triangle');
                  $status_text = ucfirst($row['status']);
                  echo "<tr>
                      <td><a href='child_details.php?id={$row['child_id']}'>".htmlspecialchars($row['child_name'])."</a></td>
                      <td><a href='vaccine_details.php?id={$row['vaccine_id']}'>".htmlspecialchars($row['vaccine_name'])."</a></td>
                      <td>".htmlspecialchars($row['date_scheduled'] ?? '')."</td>
                      <td><i class='fas {$status_icon}'></i> {$status_text}</td>
                  </tr>";
              }
          } else {
              echo "<tr><td colspan='4'>No vaccination records found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </section>
  </main>
</div>
<script>
let missedCount = <?= $missedCount ?>;
if(missedCount > 0){
    alert(`You have ${missedCount} missed vaccines! Please check your child’s schedule.`);
}

document.getElementById('logout-link').addEventListener('click', function(e) {
    e.preventDefault(); // Stop the normal link behavior
    let confirmLogout = confirm("Are you sure you want to logout?");
    if(confirmLogout) {
        window.location.href = "logout.php"; // Redirect to logout.php
    }
});
</script> 
</body>
</html>
