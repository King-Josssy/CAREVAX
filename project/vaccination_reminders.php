<?php
session_start();
require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

// Only health workers can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Twilio credentials
$sid = 'ACcbd4c592bf6bf314aa98d8607255b095';
$token = '1e87ef19530083794e0ba2ccdc392987';
$twilio_number = '+19785069723';
$twilio = new Client($sid, $token);

// Handle Send SMS button
$sms_status = "";
if (isset($_POST['send_sms'])) {
    $sql_sms = "SELECT pv.id AS pv_id, c.name AS child_name, c.parent_phone, v.name AS vaccine_name, pv.date_given
                FROM patient_vaccines pv
                JOIN children c ON pv.child_id = c.id
                JOIN vaccines v ON pv.vaccine_id = v.id
                WHERE pv.status='upcoming' AND pv.reminder_sent_sms = 0";

    $result_sms = $conn->query($sql_sms);

    if ($result_sms && $result_sms->num_rows > 0) {
        while ($row = $result_sms->fetch_assoc()) {
            if (!empty($row['parent_phone'])) {
                $message = "Hello! Reminder: Your child {$row['child_name']} has the {$row['vaccine_name']} vaccine scheduled on {$row['date_given']}.";

                try {
                    $twilio->messages->create(
                        $row['parent_phone'],
                        [
                            'from' => $twilio_number,
                            'body' => $message
                        ]
                    );

                    // Mark reminder as sent
                    $update = $conn->prepare("UPDATE patient_vaccines SET reminder_sent_sms = 1 WHERE id = ?");
                    $update->bind_param("i", $row['pv_id']);
                    $update->execute();
                } catch (Exception $e) {
                    error_log("Failed to send SMS to {$row['parent_phone']}: " . $e->getMessage());
                }
            }
        }
        $sms_status = "✅ SMS reminders sent successfully!";
    } else {
        $sms_status = "ℹ️ No upcoming vaccinations to send SMS for.";
    }
}

// Fetch upcoming vaccinations for display
$sql = "SELECT pv.id AS pv_id, c.name AS child_name, c.parent_phone, v.name AS vaccine_name, pv.date_given, pv.reminder_sent_sms
        FROM patient_vaccines pv
        JOIN children c ON pv.child_id = c.id
        JOIN vaccines v ON pv.vaccine_id = v.id
        WHERE pv.status='upcoming'
        ORDER BY pv.date_given ASC";

$result = $conn->query($sql);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Vaccination Reminders - CareVax</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
body { margin:0; font-family:Arial,sans-serif; display:flex; background:#f4f6f7; }
.sidebar { width:200px; background:#2c3e50; color:white; display:flex; flex-direction:column; padding:20px; height:100vh; position:fixed; }
.sidebar a { color:white; text-decoration:none; margin:10px 0; padding:10px; border-radius:8px; }
.sidebar a:hover { background:#34495e; }
.main { margin-left:240px; padding:30px; flex:1; }
h2 { margin-bottom:20px; color:#2c3e50; }
table { width:100%; border-collapse:collapse; background:white; box-shadow:0 2px 10px rgba(0,0,0,0.1); border-radius:8px; overflow:hidden; }
th, td { padding:15px; text-align:left; }
th { background:#3498db; color:white; }
tr:nth-child(even) { background:#f2f2f2; }
tr:hover { background:#d6eaf8; }
.badge-upcoming { background:#f39c12; color:white; padding:4px 10px; border-radius:12px; font-size:12px; }
.badge-sent { background:#27ae60; color:white; padding:4px 10px; border-radius:12px; font-size:12px; }
.send-btn { margin: 20px 0; padding:10px 20px; background:#27ae60; color:white; border:none; border-radius:5px; cursor:pointer; }
.send-btn:hover { background:#2ecc71; }
.status-msg { margin-bottom:15px; color:green; font-weight:bold; }
</style>
</head>
<body>
<div class="sidebar">
    <h2>CareVax</h2>
    <a href="healthworker_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
    <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccination</a>
    <a href="manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
    <a href="vaccination_reminders.php"><i class="fas fa-bell"></i> Vaccination Reminders</a>
    <a href="healthworker_report.php"><i class="fas fa-chart-line"></i> Reports</a>
    <a href="settings.php"><i class="fas fa-cogs"></i> Settings</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
</div>

<div class="main">
    <h2>Upcoming Vaccination Reminders</h2>

    <?php if(!empty($sms_status)): ?>
        <div class="status-msg"><?= $sms_status ?></div>
    <?php endif; ?>

    <form method="POST">
        <button type="submit" name="send_sms" class="send-btn"><i class="fas fa-paper-plane"></i> Send SMS Reminders</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Child</th>
                <th>Parent Phone</th>
                <th>Vaccine</th>
                <th>Scheduled Date</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
        <?php if($result && $result->num_rows>0): ?>
            <?php while($row=$result->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($row['child_name']) ?></td>
                    <td><?= htmlspecialchars($row['parent_phone']) ?></td>
                    <td><?= htmlspecialchars($row['vaccine_name']) ?></td>
                    <td><?= htmlspecialchars($row['date_given']) ?></td>
                    <td>
                        <?php if($row['reminder_sent_sms']): ?>
                            <span class="badge-sent">Sent</span>
                        <?php else: ?>
                            <span class="badge-upcoming">Upcoming</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="5" style="text-align:center;">No upcoming vaccinations found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
