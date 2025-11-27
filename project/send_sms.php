<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'vendor/autoload.php';
use Twilio\Rest\Client;

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['child_id'])) {
    echo "No child selected.";
    exit();
}

$child_id = intval($_GET['child_id']);

$account_sid = 'ACcbd4c592bf6bf314aa98d8607255b095';
$auth_token  = '1e87ef19530083794e0ba2ccdc392987';
$twilio_number = '+19785069723';
$client = new Client($account_sid, $auth_token);

$conn = new mysqli("localhost","root","","carevax");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$today = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));

// Upcoming vaccines (tomorrow)
$upcoming_sql = "SELECT u.phone_number, ch.name AS child_name, v.name AS vaccine_name, pv.date_scheduled
FROM patient_vaccines pv
JOIN children ch ON pv.child_id = ch.id
JOIN users u ON ch.parent_id = u.id
JOIN vaccines v ON pv.vaccine_id = v.id
WHERE pv.status='upcoming' AND pv.date_scheduled=? AND ch.id=?";
$stmt = $conn->prepare($upcoming_sql);
$stmt->bind_param("si",$tomorrow,$child_id);
$stmt->execute();
$res1 = $stmt->get_result();
while($row = $res1->fetch_assoc()){
    $msg = "Reminder: Your child {$row['child_name']} has a {$row['vaccine_name']} vaccine scheduled on {$row['date_scheduled']}.";
    $client->messages->create($row['phone_number'], ['from'=>$twilio_number,'body'=>$msg]);
}

// Missed vaccines
$missed_sql = "SELECT u.phone_number, ch.name AS child_name, v.name AS vaccine_name, pv.date_scheduled
FROM patient_vaccines pv
JOIN children ch ON pv.child_id = ch.id
JOIN users u ON ch.parent_id = u.id
JOIN vaccines v ON pv.vaccine_id = v.id
WHERE pv.status='missed' AND pv.date_scheduled<=? AND ch.id=?";
$stmt2 = $conn->prepare($missed_sql);
$stmt2->bind_param("si",$today,$child_id);
$stmt2->execute();
$res2 = $stmt2->get_result();
while($row = $res2->fetch_assoc()){
    $msg = "Notice: Your child {$row['child_name']} missed the {$row['vaccine_name']} vaccine scheduled on {$row['date_scheduled']}. Please visit the clinic.";
    $client->messages->create($row['phone_number'], ['from'=>$twilio_number,'body'=>$msg]);
}

echo "<script>alert('SMS sent successfully'); window.location.href='manage_patients.php';</script>";
$conn->close();
?>
