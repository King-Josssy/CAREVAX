<?php
session_start();
include 'db_connect.php'; // connect to your DB

// Restrict access to health workers only
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

// Check if child ID is provided
if (!isset($_GET['id'])) {
    echo "No child selected.";
    exit();
}

$child_id = intval($_GET['id']);

// Fetch child info
$stmt = $conn->prepare("SELECT * FROM children WHERE id = ?");
$stmt->bind_param("i", $child_id);
$stmt->execute();
$child_result = $stmt->get_result();
$child = $child_result->fetch_assoc();

if (!$child) {
    echo "Child not found.";
    exit();
}

// Fetch upcoming vaccines
$vaccine_stmt = $conn->prepare("
    SELECT pv.id, pv.date_scheduled, v.name AS vaccine_name
    FROM patient_vaccines pv
    JOIN vaccines v ON pv.vaccine_id = v.id
    WHERE pv.child_id = ? AND pv.status = 'upcoming'
    ORDER BY pv.date_scheduled ASC
");
$vaccine_stmt->bind_param("i", $child_id);
$vaccine_stmt->execute();
$upcoming_vaccines = $vaccine_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vaccine_ids = $_POST['vaccine_id'];
    $vaccine_dates = $_POST['vaccine_date'];

    foreach ($vaccine_ids as $index => $pv_id) {
        $stmt = $conn->prepare("UPDATE patient_vaccines SET date_scheduled=? WHERE id=?");
        $stmt->bind_param("si", $vaccine_dates[$index], $pv_id);
        $stmt->execute();
    }

    header("Location: manage_patients.php?success=rescheduled");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reschedule Vaccines - CareVax</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; font-family:Arial,sans-serif; background:#f4f6f7; display:flex; }
        .sidebar { width:200px; background:#2c3e50; color:white; padding:20px; height:100vh; position:fixed; }
        .sidebar a { color:white; text-decoration:none; display:block; margin:10px 0; padding:10px; border-radius:6px; }
        .sidebar a:hover { background:#34495e; }
        .main-content { margin-left:220px; padding:30px; flex:1; background:#f4f6f7; }
        input { width:50%; padding:8px; margin-top:5px; border-radius:6px; border:1px solid #ccc; margin-bottom:10px; }
        button { padding:8px 12px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; }
        .btn-secondary { background:#95a5a6; text-decoration:none; padding:8px 12px; color:white; border-radius:6px; }
        label { font-weight:bold; margin-top:10px; display:block; }
        h2, h3 { margin-top:0; }
        hr { margin:15px 0; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>CareVax</h2>
    <a href="health worker.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccination</a>
    <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
    <a href="schedule_reminders.php"><i class="fas fa-bell"></i> Vaccination Reminders</a>
    <a href="healthworker_report.php"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <h2>Reschedule Vaccines for <?= htmlspecialchars($child['name']); ?></h2>
    <?php if ($upcoming_vaccines->num_rows == 0): ?>
        <p>No upcoming vaccines for this child.</p>
        <a href="manage_patients.php" class="btn-secondary">Back</a>
    <?php else: ?>
    <form method="POST">
        <?php while($v = $upcoming_vaccines->fetch_assoc()): ?>
            <label>Vaccine: <?= htmlspecialchars($v['vaccine_name']); ?></label>
            <input type="hidden" name="vaccine_id[]" value="<?= $v['id']; ?>">
            <label>Scheduled Date:</label>
            <input type="date" name="vaccine_date[]" value="<?= $v['date_scheduled']; ?>" required>
            <hr>
        <?php endwhile; ?>
        <button type="submit">Save New Dates</button>
        <a href="manage_patients.php" class="btn-secondary">Cancel</a>
    </form>
    <?php endif; ?>
</div>

</body>
</html>
