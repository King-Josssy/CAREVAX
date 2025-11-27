<?php
require 'db_connect.php'; // connect to DB
$child_id = $_GET['child_id'] ?? 0;
$stmt = $conn->prepare("SELECT id,name,dob,gender FROM children WHERE id=?");
$stmt->bind_param("i",$child_id);
$stmt->execute();
$child = $stmt->get_result()->fetch_assoc();
$stmt->close();

$stmt2 = $conn->prepare("SELECT pv.id,v.name,pv.date_scheduled,pv.status FROM patient_vaccines pv JOIN vaccines v ON pv.vaccine_id=v.id WHERE pv.child_id=?");
$stmt2->bind_param("i",$child_id);
$stmt2->execute();
$vaccines = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt2->close();

echo json_encode(['child'=>$child,'vaccines'=>$vaccines]);
