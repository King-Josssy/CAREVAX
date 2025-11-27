<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "carevax";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Connected successfully"; // For testing
?>
