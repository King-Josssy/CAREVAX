<?php  
session_start();
include 'db_connect.php';

// Admin username from session
$admin_name = isset($_SESSION['username']) ? $_SESSION['username'] : "Admin";

// Count children (patients)
$patients = $conn->query("SELECT COUNT(*) as total FROM children")->fetch_assoc()['total'];

// Count healthcare workers
$workers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='healthworker'")->fetch_assoc()['total'];

// Count parents
$parents = $conn->query("SELECT COUNT(*) as total FROM users WHERE role='parent'")->fetch_assoc()['total'];

// Vaccination stats (for charts)
$vaccinated = $conn->query("SELECT COUNT(*) as total FROM patient_vaccines WHERE status='completed'")->fetch_assoc()['total'];
$upcoming   = $conn->query("SELECT COUNT(*) as total FROM patient_vaccines WHERE status='upcoming'")->fetch_assoc()['total'];
$missed     = $conn->query("SELECT COUNT(*) as total FROM patient_vaccines WHERE status='missed'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard</title>

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
body {
  margin: 0;
  font-family: Arial, sans-serif;
  display: flex;
  min-height: 100vh;
  background: 
    linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
    url('re.jpeg') no-repeat center center fixed;
  background-size: cover;
  color: #fff; /* Ensures text is readable over image */
}

/* Sidebar */
.sidebar {
  width: 220px;
  background: rgba(44,62,80,0.9); /* semi-transparent for readability */
  color: white;
  padding: 20px;
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  box-sizing: border-box;
}
.sidebar h2 { 
  text-align: center; 
  margin-bottom: 30px; 
}
.sidebar a {
  color:white;
  text-decoration:none;
  display:block;
  margin: 10px 0;
  padding: 10px;
  border-radius:6px;
  text-align: center;
}
.sidebar a:hover { background:#34495e; }

/* Main content */
.main {
  margin-left: 240px;
  padding: 30px;
  flex:1;
}

/* Cards */
.cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 20px;
  margin-bottom: 40px;
}
.card {
  background: rgba(255,255,255,0.95);
  padding: 25px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  text-align: center;
  transition: transform 0.2s ease-in-out;
  color: #2d3436; /* Card text color */
}
.card:hover { transform: scale(1.05); }
.card i, .card span { 
  font-size: 2.5rem; 
  color:#00cec9; 
  display:block; 
  margin-bottom:10px; 
}
.card h3 { 
  margin: 10px 0 5px; 
  font-size: 18px; 
  color: #000; 
}
.card p { 
  font-size: 22px; 
  margin:0; 
}
.card a { 
  text-decoration:none; 
  color:#0984e3; 
  font-size:14px; 
}
.card a:hover { text-decoration:underline; }

/* Charts container */
.charts {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  justify-content: center;
}
.chart-container {
  background: rgba(255,255,255,0.95);
  padding:20px;
  border-radius:10px;
  box-shadow:0 2px 6px rgba(0,0,0,0.15);
  flex: 1 1 600px;
  max-width: 400px;
  text-align:center;
}
.chart-container canvas {
  max-width: 100%;
  height: 400px;
}

</style>
</head>
<body>

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

<!-- Main content -->
<div class="main">
  <header>
    <h1>👨‍💼 Welcome, <?php echo htmlspecialchars($admin_name); ?></h1>
  </header>

  <!-- Cards -->
  <section class="cards">
    <div class="card">
      <i class="fas fa-child"></i>
      <h3>Total Patients</h3>
      <p><?php echo $patients; ?></p>
    </div>
    <div class="card">
      <i class="fas fa-user-nurse"></i>
      <h3>Total Healthcare Workers</h3>
      <p><?php echo $workers; ?></p>
    </div>
    <div class="card">
      <i class="fas fa-users"></i>
      <h3>Total Parents</h3>
      <p><?php echo $parents; ?></p>
    </div>
    <div class="card">
      <i class="fas fa-chart-line"></i>
      <h3>Reports</h3>
      <p><a href="reports.php">View detailed analytics</a></p>
    </div>
  </section>

  <!-- Vaccination Status Chart -->
  <section class="charts">
    <div class="chart-container">
      <h3>Vaccination Status</h3>
      <canvas id="vaccineChart"></canvas>
    </div>
  </section>
</div>

<script>
const ctx = document.getElementById('vaccineChart').getContext('2d');
const vaccineChart = new Chart(ctx, {
    type: 'pie',
    data: {
        labels: ['Completed', 'Upcoming', 'Missed'],
        datasets: [{
            label: 'Vaccination Status',
            data: [<?php echo $vaccinated; ?>, <?php echo $upcoming; ?>, <?php echo $missed; ?>],
            backgroundColor: ['#27ae60','#2980b9','#c0392b']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 16 } } }
        }
    }
});
</script>

</body>
</html>
