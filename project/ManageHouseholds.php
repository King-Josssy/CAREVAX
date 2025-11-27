<?php 
// ManageHouseholds.php
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Fetch all households (registered by VHTs) with number of children
$sql = "SELECT 
            h.id, 
            h.household_name, 
            h.village, 
            v.vht_name, 
            h.date_registered,
            h.num_children
        FROM households h
        JOIN vhts v ON h.vht_id = v.id";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Households - Health Worker</title>
  <style>
    body {
      margin: 0;
      font-family: Arial, sans-serif;
      display: flex;
      min-height: 100vh;
      background: #f4f6f8;
    }
    
    .sidebar {
      width: 220px;
      background:#34495e;
      color: white;
      display: flex;
      flex-direction: column;
      padding: 20px;
      transition: transform 0.3s ease;
    }
    .sidebar.hidden {
      transform: translateX(-320px);
    }
    .sidebar h2 { color: #ffffffff; text-align: center; }
   
    .sidebar a {
      color: white;
      text-decoration: none;
      padding: 10px;
      margin: 5px 0;
      display: block;
      border-radius: 8px;
      background-color:#34495e ;
    }
    .sidebar a:hover, .sidebar a.active {
      background:  #5e7b97ff;
    }

    .menu-toggle {
      position: fixed;
      top: 15px;
      left: 15px;
      font-size: 26px;
      color: white;
      background: #34495e;
      padding: 8px 12px;
      border-radius: 6px;
      cursor: pointer;
      z-index: 1001;
    }

    .main-content {
      flex: 1;
      padding: 20px;
      transition: margin-left 0.3s ease;
      margin-left: 20px;
    }
    .main-content.shifted {
      margin-left: 20px;
    }

    h1 { color: #2d3436; }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }
    th {
      background: #008dceff;
      color: white;
    }
    tr:hover { background: #f1f1f1; }
  </style>
</head>
<body>

<!-- Hamburger Menu -->
<div class="menu-toggle" onclick="document.querySelector('.sidebar').classList.toggle('hidden'); document.querySelector('.main-content').classList.toggle('shifted');">
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

<div class="main-content">
    <h1>Registered Households</h1>
    <table>
      <tr>
        <th>ID</th>
        <th>Household Name</th>
        <th>Location</th>
        <th>Registered By (VHT)</th>
        <th>Date Registered</th>
        <th>Total Children</th>
      </tr>
      <?php
      if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
          echo "<tr>
                  <td>{$row['id']}</td>
                  <td>{$row['household_name']}</td>
                  <td>{$row['village']}</td>
                  <td>{$row['vht_name']}</td>
                  <td>{$row['date_registered']}</td>
                  <td>{$row['num_children']}</td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='6'>No households found</td></tr>";
      }
      ?>
    </table>
</div>
</body>
</html>
