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
$result = $stmt->get_result();
$child = $result->fetch_assoc();

if (!$child) {
    echo "Child not found.";
    exit();
}

// Fetch vaccines for this child
$vaccine_result = $conn->prepare("
    SELECT pv.id, pv.vaccine_id, pv.date_scheduled, pv.status, v.name AS vaccine_name
    FROM patient_vaccines pv
    JOIN vaccines v ON pv.vaccine_id = v.id
    WHERE pv.child_id = ?
    ORDER BY pv.date_scheduled ASC
");
$vaccine_result->bind_param("i", $child_id);
$vaccine_result->execute();
$vaccine_data = $vaccine_result->get_result();

// Handle update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $child_name = $_POST['child_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];

    // Update child info
    $updateChild = $conn->prepare("UPDATE children SET name=?, dob=?, gender=? WHERE id=?");
    $updateChild->bind_param("sssi", $child_name, $dob, $gender, $child_id);
    $updateChild->execute();

    // Update vaccines
    if (isset($_POST['vaccine_id'])) {
        $vaccine_ids = $_POST['vaccine_id'];
        $vaccine_dates = $_POST['vaccine_date'];
        $vaccine_statuses = $_POST['vaccine_status'];

        foreach ($vaccine_ids as $index => $pv_id) {
            $stmt = $conn->prepare("UPDATE patient_vaccines SET date_scheduled=?, status=? WHERE id=?");
            $stmt->bind_param("ssi", $vaccine_dates[$index], $vaccine_statuses[$index], $pv_id);
            $stmt->execute();
        }
    }

    header("Location: manage_patients.php?success=updated");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Child</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { margin:0; font-family:Arial,sans-serif; background:#f4f6f7; display:flex; }
        .sidebar { width:200px; background:#2c3e50; color:white; padding:20px; height:100vh; position:fixed; }
        .sidebar a { color:white; text-decoration:none; display:block; margin:10px 0; padding:10px; border-radius:6px; }
        .sidebar a:hover { background:#34495e; }
        .main-content { margin-left:220px; padding:30px; flex:1; background:#f4f6f7; }
        input, select { width:50%; padding:8px; margin-top:5px; border-radius:6px; border:1px solid #ccc; margin-bottom:10px; }
        button { padding:8px 12px; background:#27ae60; color:white; border:none; border-radius:6px; cursor:pointer; }
        .btn-secondary { background:#95a5a6; text-decoration:none; padding:8px 12px; color:white; border-radius:6px; }
        h2, h3 { margin-top:0; }
        hr { margin:15px 0; }
        label { font-weight:bold; margin-top:10px; display:block; }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <h2>CareVax</h2>
    <a href="health worker.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccination</a>
    <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
    <a href="healthworker_report.php"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="health settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main-content">
    <h2>Edit Child Record</h2>
    <form method="POST">
        <label>Child Name:</label>
        <input type="text" name="child_name" value="<?= htmlspecialchars($child['name']); ?>" required>

        <label>Date of Birth:</label>
        <input type="date" name="dob" value="<?= htmlspecialchars($child['dob']); ?>" required>

        <label>Gender:</label>
        <select name="gender" required>
            <option value="male" <?= $child['gender']=='male'?'selected':''; ?>>Male</option>
            <option value="female" <?= $child['gender']=='female'?'selected':''; ?>>Female</option>
        </select>

        <h3>Vaccines</h3>
        <?php while($v = $vaccine_data->fetch_assoc()): ?>
            <label>Vaccine: <?= htmlspecialchars($v['vaccine_name']); ?></label>
            <input type="hidden" name="vaccine_id[]" value="<?= $v['id']; ?>">
            <label>Scheduled Date:</label>
            <input type="date" name="vaccine_date[]" value="<?= $v['date_scheduled']; ?>" required>
            <label>Status:</label>
            <select name="vaccine_status[]">
                <option value="upcoming" <?= $v['status']=='upcoming'?'selected':''; ?>>Upcoming</option>
                <option value="completed" <?= $v['status']=='completed'?'selected':''; ?>>Completed</option>
                <option value="missed" <?= $v['status']=='missed'?'selected':''; ?>>Missed</option>
            </select>
            <hr>
        <?php endwhile; ?>

        <button type="submit">Save Changes</button>
        <a href="manage_patients.php" class="btn-secondary">Cancel</a>
    </form>
</div>

</body>
</html>
