<?php 
session_start();
include 'db_connect.php'; // $conn

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Admin profile
$user_id = (int)$_SESSION['user_id'];
$profile_result = $conn->query("SELECT username FROM users WHERE id=$user_id");
$profile = $profile_result && $profile_result->num_rows ? $profile_result->fetch_assoc() : ['username' => 'Admin'];

// Fetch counts
$children_count_result = $conn->query("SELECT COUNT(*) AS cnt FROM children");
$children_count = $children_count_result ? (int)$children_count_result->fetch_assoc()['cnt'] : 0;

$vaccines_count_result = $conn->query("SELECT COUNT(*) AS cnt FROM vaccines");
$vaccines_count = $vaccines_count_result ? (int)$vaccines_count_result->fetch_assoc()['cnt'] : 0;

$healthworker_count_result = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='healthworker'");
$healthworker_count = $healthworker_count_result ? (int)$healthworker_count_result->fetch_assoc()['cnt'] : 0;

$parents_count_result = $conn->query("SELECT COUNT(*) AS cnt FROM users WHERE role='parent'");
$parents_count = $parents_count_result ? (int)$parents_count_result->fetch_assoc()['cnt'] : 0;

// Fetch patient_vaccines info for charts
$chart_status = [];
$status_result = $conn->query("
    SELECT status, COUNT(*) AS cnt
    FROM patient_vaccines
    GROUP BY status
");
if($status_result){
    while($row = $status_result->fetch_assoc()) {
        $chart_status[$row['status']] = (int)$row['cnt'];
    }
}

// Vaccinations per vaccine
$chart_vaccine = [];
$vaccine_result = $conn->query("
    SELECT v.name AS vaccine_name, COUNT(pv.id) AS cnt
    FROM vaccines v
    LEFT JOIN patient_vaccines pv ON pv.vaccine_id = v.id AND pv.status='completed'
    GROUP BY v.id
");
if($vaccine_result){
    while($row = $vaccine_result->fetch_assoc()) {
        $chart_vaccine[$row['vaccine_name']] = (int)$row['cnt'];
    }
}

// Fetch recent vaccinations for table
$recent_vaccinations = [];
$recent_result = $conn->query("
    SELECT c.name AS child_name, v.name AS vaccine_name, pv.status, pv.date_given
    FROM patient_vaccines pv
    JOIN children c ON pv.child_id = c.id
    JOIN vaccines v ON pv.vaccine_id = v.id
    ORDER BY pv.id DESC
    LIMIT 10
");
if($recent_result){
    $recent_vaccinations = $recent_result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Reports</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- FIXED CHART.JS LINE -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
html, body {margin:0; padding:0; font-family: Arial, sans-serif; background:#f4f6f8; height:100%;}
.dashboard {display:flex; min-height:100vh;}
.sidebar {width:220px; background:#2c3e50; color:#fff; padding:20px; display:flex; flex-direction:column; position:fixed; height:100vh;}
.sidebar h2 {margin-top:0;margin-bottom:20px;}
.sidebar a {display:block;color:#fff;text-decoration:none;padding:10px;border-radius:4px;margin-bottom:5px;}
.sidebar a.active, .sidebar a:hover {background:#34495e;}
.main-content {margin-left:250px; flex-grow:1; padding:24px; box-sizing:border-box;}
h1,h2 {margin-top:0;}
table {width:100%; border-collapse:collapse; margin-bottom:30px;}
table th, table td {border:1px solid #ddd; padding:10px;}
table th {background:#34495e; color:#fff;}
.chart-container {width:100%; max-width:800px; margin-bottom:40px; height:400px; background:#fff; padding:15px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
.status-completed {color:#2ecc71;font-weight:bold;}
.status-upcoming {color:#f1c40f;font-weight:bold;}
.status-missed {color:#e74c3c;font-weight:bold;}
</style>
</head>
<body>
<div class="dashboard">
  <aside class="sidebar">
    <h2>Admin Panel</h2>
    <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="admin manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
    <a href="manage_healthworkers.php"><i class="fas fa-user-nurse"></i> Manage Health Workers</a>
    <a href="admin manage_parents.php"><i class="fas fa-users"></i> Manage Parents</a>
    <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccines</a>
    <a href="reports.php" class="active"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="Admin settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </aside>

  <main class="main-content">
    <h1>System Reports</h1>
    <p>Signed in as <strong><?= htmlspecialchars($profile['username'] ?? 'Admin') ?></strong></p>

    <!-- Quick Stats -->
    <h2>Quick Stats</h2>
    <table>
      <tr>
        <th>Total Children</th>
        <th>Total Vaccines</th>
        <th>Total Health Workers</th>
        <th>Total Parents</th>
      </tr>
      <tr>
        <td><?= htmlspecialchars($children_count) ?></td>
        <td><?= htmlspecialchars($vaccines_count) ?></td>
        <td><?= htmlspecialchars($healthworker_count) ?></td>
        <td><?= htmlspecialchars($parents_count) ?></td>
      </tr>
    </table>

    <!-- Charts -->
    <h2>Vaccination Status Overview</h2>
    <div class="chart-container">
      <canvas id="statusChart"></canvas>
    </div>

    <h2>Vaccinations Completed per Vaccine</h2>
    <div class="chart-container">
      <canvas id="vaccineChart"></canvas>
    </div>

    <!-- Recent Vaccinations Table -->
    <h2>Recent Vaccinations</h2>
    <table>
      <thead>
        <tr>
          <th>Child Name</th>
          <th>Vaccine</th>
          <th>Status</th>
          <th>Date Given</th>
        </tr>
      </thead>
      <tbody>
        <?php if($recent_vaccinations): ?>
          <?php foreach($recent_vaccinations as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['child_name'] ?? '') ?></td>
              <td><?= htmlspecialchars($r['vaccine_name'] ?? '') ?></td>
              <td class="status-<?= htmlspecialchars($r['status'] ?? 'upcoming') ?>"><?= htmlspecialchars(ucfirst($r['status'] ?? 'upcoming')) ?></td>
              <td><?= htmlspecialchars($r['date_given'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="4">No recent vaccinations.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </main>
</div>

<script>
// Vaccination Status Pie Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type:'pie',
    data:{
        labels: <?= json_encode(array_keys($chart_status)) ?>,
        datasets:[{
            data: <?= json_encode(array_values($chart_status)) ?>,
            backgroundColor:['#f1c40f','#2ecc71','#e74c3c']
        }]
    },
    options:{responsive:true, maintainAspectRatio:false}
});

// Vaccinations per Vaccine Bar Chart
const vaccineCtx = document.getElementById('vaccineChart').getContext('2d');
new Chart(vaccineCtx,{
    type:'bar',
    data:{
        labels: <?= json_encode(array_keys($chart_vaccine)) ?>,
        datasets:[{
            label:'Completed Vaccinations',
            data: <?= json_encode(array_values($chart_vaccine)) ?>,
            backgroundColor:'#3498db'
        }]
    },
    options:{
        responsive:true,
        maintainAspectRatio:false,
        scales:{y:{beginAtZero:true}}
    }
});
</script>
</body>
</html>
