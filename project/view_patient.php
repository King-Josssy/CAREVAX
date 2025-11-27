<?php 
session_start();
include 'db_connect.php'; // $conn

// Ensure admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if an ID is provided
if (!isset($_GET['id'])) {
    echo "<p>No patient ID provided.</p>";
    exit();
}

$child_id = intval($_GET['id']);

// Fetch child and parent details
$query = "
    SELECT 
        c.id, 
        c.name AS child_name, 
        c.dob, 
        c.gender,
        u.username AS parent_name, 
        u.email AS parent_email, 
        u.phone AS parent_phone
    FROM children c
    LEFT JOIN users u ON c.parent_id = u.id
    WHERE c.id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $child_id);
$stmt->execute();
$result = $stmt->get_result();
$child = $result->fetch_assoc();

if (!$child) {
    echo "<p>Child not found.</p>";
    exit();
}

// Fetch vaccination records
$vaccines_query = "
    SELECT vaccine_name, status, date_given
    FROM vaccinations
    WHERE patient_id = ?
";
$vstmt = $conn->prepare($vaccines_query);
$vstmt->bind_param("i", $child_id);
$vstmt->execute();
$vaccines_result = $vstmt->get_result();
$vaccines = $vaccines_result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>View Patient Details</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body {font-family: Arial, sans-serif; background:#f4f6f8; margin:0; padding:0;}
.container {max-width:800px; margin:40px auto; background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
h1 {text-align:center; color:#2c3e50;}
h2 {margin-top:30px; color:#34495e;}
table {width:100%; border-collapse:collapse; margin-top:10px;}
table th, table td {padding:10px; border:1px solid #ddd;}
table th {background:#34495e; color:white;}
.status-done {color:#2ecc71; font-weight:bold;}
.status-pending {color:#e74c3c; font-weight:bold;}
a.back {display:inline-block; margin-top:20px; background:#3498db; color:white; padding:8px 14px; border-radius:6px; text-decoration:none;}
</style>
</head>
<body>
<div class="container">
  <h1><i class="fas fa-child"></i> Child Details</h1>
  
  <h2>Personal Information</h2>
  <p><strong>Child Name:</strong> <?= htmlspecialchars($child['child_name'] ?? '-') ?></p>
  <p><strong>Date of Birth:</strong> <?= htmlspecialchars($child['dob'] ?? '-') ?></p>
  <p><strong>Gender:</strong> <?= htmlspecialchars($child['gender'] ?? '-') ?></p>
  
  <h2>Parent Information</h2>
  <p><strong>Parent Name:</strong> <?= htmlspecialchars($child['parent_name'] ?? '-') ?></p>
  <p><strong>Email:</strong> <?= htmlspecialchars($child['parent_email'] ?? '-') ?></p>
  <p><strong>Phone:</strong> <?= htmlspecialchars($child['parent_phone'] ?? '-') ?></p>

  <h2>Vaccination Records</h2>
  <?php if ($vaccines): ?>
    <table>
      <tr>
        <th>Vaccine Name</th>
        <th>Status</th>
        <th>Date Given</th>
      </tr>
      <?php foreach ($vaccines as $v): ?>
        <tr>
          <td><?= htmlspecialchars($v['vaccine_name'] ?? '-') ?></td>
          <td class="<?= ($v['status'] ?? '')=='Done' ? 'status-done' : 'status-pending' ?>">
              <?= htmlspecialchars($v['status'] ?? '-') ?>
          </td>
          <td><?= htmlspecialchars($v['date_given'] ?? '-') ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No vaccination records found for this child.</p>
  <?php endif; ?>

  <a href="admin manage_patients.php" class="back"><i class="fas fa-arrow-left"></i> Back</a>
</div>
</body>
</html>
