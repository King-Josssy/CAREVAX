<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$healthworker_id = $_SESSION['user_id'];

// Total children registered by this health worker
$total_children = $conn->query("SELECT COUNT(*) AS total FROM children WHERE healthworker_id='$healthworker_id'")->fetch_assoc()['total'];

// Total vaccines given
$total_vaccines = $conn->query("SELECT COUNT(*) AS total FROM vaccinations WHERE healthworker_id='$healthworker_id'")->fetch_assoc()['total'];

// Pending vaccinations
$pending_vaccines = $conn->query("SELECT COUNT(*) AS total FROM vaccinations WHERE status='Pending' AND healthworker_id='$healthworker_id'")->fetch_assoc()['total'];

// Fetch detailed vaccination records
$records = $conn->query("
    SELECT c.name AS child_name, vac.vaccine_name, vac.date_given, vac.status
    FROM vaccinations vac
    JOIN children c ON vac.patient_id = c.id
    WHERE vac.healthworker_id='$healthworker_id'
    ORDER BY vac.date_given DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Health Worker Report - CareVax</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    margin: 0;
    font-family: Arial, sans-serif;
    display: flex;
    background: url('re.jpeg') no-repeat center center fixed;
    background-size: cover;
    position: relative;
}
body::before {
    content: "";
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 0;
}
.sidebar, .content, .cards, .card, table, canvas {
    position: relative;
    z-index: 1;
}
.sidebar {
    width: 220px;
    background: #2c3e50;
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    display: flex;
    flex-direction: column;
    padding-top: 20px;
    transition: transform 0.3s ease;
}
.sidebar.hidden {
    transform: translateX(-220px);
}
.sidebar h2 {
    text-align: center;
    color: #fff;
    margin-bottom: 30px;
}
.sidebar a {
    color: #fff;
    padding: 12px 20px;
    text-decoration: none;
    display: flex;
    align-items: center;
}
.sidebar a:hover {
    background: #3e4f5fff;
}
.sidebar i {
    margin-right: 10px;
}
.menu-toggle {
    position: fixed;
    top: 15px;
    left: 15px;
    font-size: 26px;
    color: white;
   
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    z-index: 1001;
}
.content {
    margin-left: 240px;
    padding: 30px;
    width: calc(100% - 240px);
    transition: margin-left 0.3s ease, width 0.3s ease;
}
.content.shifted {
    margin-left: 20px;
    width: calc(100% - 20px);
}
.cards {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
}
.card {
    flex: 1;
    background: rgba(255,255,255,0.85);
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
    text-align: center;
}
.card h3 { margin: 10px 0; }
table {
    width: 100%;
    border-collapse: collapse;
    background: rgba(255,255,255,0.85);
    border-radius: 10px;
    overflow: hidden;
}
th, td {
    border: 1px solid #ddd;
    padding: 12px;
    text-align: center;
}
th { background: rgba(0,0,0,0.8); color: white; }
canvas {
    background: rgba(255,255,255,0.85);
    border-radius: 10px;
    padding: 20px;
    margin-top: 30px;
    box-shadow: 0 3px 6px rgba(0,0,0,0.1);
}
</style>
</head>
<body>

<!-- Hamburger Menu -->
<div class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('hidden'); document.querySelector('.content').classList.toggle('shifted');">
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

<div class="content">
    <h1>Health Worker Report</h1>
    <div class="cards">
        <div class="card">
            <h3>Total Children</h3>
            <p><b><?php echo $total_children; ?></b></p>
        </div>
        <div class="card">
            <h3>Total Vaccines Given</h3>
            <p><b><?php echo $total_vaccines; ?></b></p>
        </div>
        <div class="card">
            <h3>Pending Vaccines</h3>
            <p><b><?php echo $pending_vaccines; ?></b></p>
        </div>
    </div>

    <h2>Vaccination Records</h2>
    <table>
        <tr>
            <th>Child Name</th>
            <th>Vaccine</th>
            <th>Date Given</th>
            <th>Status</th>
        </tr>
        <?php while ($row = $records->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['child_name']); ?></td>
            <td><?php echo htmlspecialchars($row['vaccine_name']); ?></td>
            <td><?php echo htmlspecialchars($row['date_given']); ?></td>
            <td><?php echo htmlspecialchars($row['status']); ?></td>
        </tr>
        <?php } ?>
    </table>

    <canvas id="reportChart" width="400" height="150"></canvas>
</div>

<script>
const ctx = document.getElementById('reportChart');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Total Children', 'Vaccines Given', 'Pending'],
        datasets: [{
            label: 'Vaccination Summary',
            data: [<?php echo $total_children; ?>, <?php echo $total_vaccines; ?>, <?php echo $pending_vaccines; ?>],
            backgroundColor: ['#1abc9c','#3498db','#e74c3c']
        }]
    },
    options: {
        responsive: true,
        scales: { y: { beginAtZero: true } }
    }
});
</script>
</body>
</html>
