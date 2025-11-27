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

// Fetch all children with parent and vaccination info
$children_query = "
    SELECT 
        ch.id AS child_id,
        ch.name AS child_name,
        ch.dob,
        ch.gender,
        u.username AS parent_name,
        COALESCE(vac.status, 'Pending') AS vaccine_status,
        vac.date_given
    FROM children ch
    LEFT JOIN users u ON ch.parent_id = u.id AND u.role='parent'
    LEFT JOIN vaccinations vac ON ch.id = vac.patient_id
    ORDER BY ch.id DESC
";

$children = $conn->query($children_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Patients - CareVax</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ===== Root / Variables ===== */
:root {
  --accent: #3498db;
  --success: #2ecc71;
  --danger: #e74c3c;
}

/* ===== Body & Background ===== */
body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  min-height: 100vh;
  display: flex;
  background:
    linear-gradient(rgba(0,0,0,0.4), rgba(0,0,0,0.4)),
    url('re.jpeg') no-repeat center center fixed;
  background-size: cover;
  color: #fff;
}

/* ===== Dashboard Layout ===== */
.dashboard {
  display: flex;
  min-height: 100vh;
}

/* ===== Sidebar ===== */
.sidebar {
  width: 220px;
  background: rgba(44,62,80,0.9); /* semi-transparent */
  color: #fff;
  padding: 20px;
  display: flex;
  flex-direction: column;
  position: fixed;
  height: 100vh;
  box-sizing: border-box;
}
.sidebar h2 {
  margin: 0 0 20px;
  text-align: center;
}
.sidebar a {
  display: block;
  color: #fff;
  text-decoration: none;
  padding: 10px;
  border-radius: 4px;
  margin-bottom: 5px;
  text-align: center;
}
.sidebar a.active,
.sidebar a:hover {
  background: rgba(52,73,94,0.9);
}

/* ===== Main Content ===== */
.main-content {
  margin-left: 240px;
  padding: 24px;
  flex-grow: 1;
  box-sizing: border-box;
}

/* ===== Cards ===== */
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
  color: #2d3436;
}
.card:hover { transform: scale(1.05); }
.card i, .card span {
  font-size: 2.5rem;
  color: #00cec9;
  display: block;
  margin-bottom: 10px;
}
.card h3 {
  margin: 10px 0 5px;
  font-size: 18px;
  color: #2c3e50;
}
.card p { font-size: 22px; margin: 0; }

/* ===== Tables ===== */
table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 20px;
}
table th,
table td {
  border: 1px solid #000;
  padding: 10px;
  text-align: left;
}
table th {
  background: #010a13ff;
  color: #ffffffff;
}

/* ===== Status Colors ===== */
.status-done { color: var(--success); font-weight: bold; }
.status-pending { color: var(--danger); font-weight: bold; }

/* ===== Buttons ===== */
button, a.view, a.delete {
  padding: 6px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  text-decoration: none;
  margin-right: 5px;
}
a.view { background: var(--success); color: #fff; }
a.delete { background: var(--danger); color: #fff; }

/* ===== Charts ===== */
.charts {
  display: flex;
  flex-wrap: wrap;
  gap: 30px;
  justify-content: center;
}
.chart-container {
  background: rgba(255,255,255,0.95);
  padding: 20px;
  border-radius: 10px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.15);
  flex: 1 1 600px;
  max-width: 400px;
  text-align: center;
}
.chart-container canvas {
  max-width: 100%;
  height: 400px;
}

/* ===== Responsive ===== */
@media (max-width: 980px) {
  .main-content { margin-left: 0; padding: 16px; }
  .sidebar { position: relative; width: 100%; height: auto; }
  .cards { grid-template-columns: 1fr; }
  .chart-container { max-width: 95%; }
}

</style>
</head>
<body>

<div class="dashboard">
  <!-- Sidebar -->
  <aside class="sidebar">
    <h2>Admin Panel</h2>
    <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="admin manage_patients.php" class="active"><i class="fas fa-user-injured"></i> Manage Patients</a>
    <a href="manage_healthworkers.php"><i class="fas fa-user-nurse"></i> Manage Health Workers</a>
    <a href="admin manage_parents.php"><i class="fas fa-users"></i> Manage Parents</a>
    <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccines</a>
    <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="Admin settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
  </aside>

  <main class="main-content">
    <h1>Manage Patients</h1>
    <p>Signed in as <strong><?= htmlspecialchars($profile['username']); ?></strong></p>

    <table>
        <thead>
            <tr>
                <th>Child Name</th>
                <th>DOB</th>
                <th>Gender</th>
                <th>Parent Name</th>
                <th>Status</th>
                <th>Date Given</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if($children && $children->num_rows > 0): ?>
                <?php while($row = $children->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['child_name']); ?></td>
                    <td><?= htmlspecialchars($row['dob']); ?></td>
                    <td><?= htmlspecialchars($row['gender']); ?></td>
                    <td><?= htmlspecialchars($row['parent_name'] ?? '-'); ?></td>
                    <td>
                        <span class="status-<?= htmlspecialchars(strtolower($row['vaccine_status'])); ?>">
                            <?= htmlspecialchars(ucfirst($row['vaccine_status'])); ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($row['date_given'] ?? '-'); ?></td>
                    <td>
                        <a href="view_patient.php?id=<?= $row['child_id']; ?>" class="view"><i class="fas fa-eye"></i> View</a>
                        <a href="delete_patient.php?id=<?= $row['child_id']; ?>" class="delete" onclick="return confirm('Are you sure?');"><i class="fas fa-trash-alt"></i> Delete</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7">No children found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
  </main>
</div>

</body>
</html>
