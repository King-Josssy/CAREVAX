<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$messages = [];

// Handle form submission for adding/updating vaccines
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $quantity = intval($_POST['quantity'] ?? 0);
    $manufacturer = $_POST['manufacturer'] ?? '';
    $expiration = $_POST['expiration'] ?? null;

    // Check if vaccine already exists
    $check = $conn->prepare("SELECT id FROM vaccines WHERE name=?");
    $check->bind_param("s", $name);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Update existing vaccine quantity
        $update = $conn->prepare("UPDATE vaccines SET quantity=quantity+?, description=?, manufacturer=?, expiration_date=? WHERE name=?");
        $update->bind_param("issss", $quantity, $description, $manufacturer, $expiration, $name);
        $update->execute();
        $messages[] = ['type'=>'success','text'=>"Vaccine updated successfully!"];
    } else {
        // Insert new vaccine
        $insert = $conn->prepare("INSERT INTO vaccines (name, description, quantity, manufacturer, expiration_date) VALUES (?, ?, ?, ?, ?)");
        $insert->bind_param("ssiss", $name, $description, $quantity, $manufacturer, $expiration);
        $insert->execute();
        $messages[] = ['type'=>'success','text'=>"New vaccine added successfully!"];
    }
}

// Fetch all vaccines
$vaccine_result = $conn->query("SELECT * FROM vaccines ORDER BY name ASC");
$vaccines = $vaccine_result ? $vaccine_result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Vaccines</title>
<style>
body {font-family: Arial, sans-serif; padding:20px; background:#f4f6f8;}
.card {background:#fff; padding:20px; border-radius:8px; margin-bottom:20px; box-shadow:0 2px 8px rgba(0,0,0,0.1);}
table {width:100%; border-collapse:collapse;}
table th, table td {border:1px solid #ddd; padding:10px; text-align:left;}
table th {background:#f0f0f0;}
input, textarea {width:100%; padding:8px; margin:5px 0; box-sizing:border-box;}
button {padding:8px 12px; background:#3498db; color:#fff; border:none; border-radius:6px; cursor:pointer;}
.msg.success {background:#e9f8f0; color:#2ecc71; padding:10px; border-radius:6px; margin-bottom:15px;}
.msg.error {background:#fdecea; color:#e74c3c; padding:10px; border-radius:6px; margin-bottom:15px;}
</style>
</head>
<body>
<div class="card">
<h2>Add / Update Vaccine</h2>
<?php foreach($messages as $m): ?>
<div class="msg <?= $m['type'] ?>"><?= $m['text'] ?></div>
<?php endforeach; ?>
<form method="POST">
    <label>Vaccine Name</label>
    <input type="text" name="name" required>

    <label>Description</label>
    <textarea name="description"></textarea>

    <label>Quantity</label>
    <input type="number" name="quantity" value="0" min="0">

    <label>Manufacturer</label>
    <input type="text" name="manufacturer">

    <label>Expiration Date</label>
    <input type="date" name="expiration">

    <button type="submit">Submit</button>
</form>
</div>

<div class="card">
<h2>Existing Vaccines</h2>
<table>
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Quantity</th>
            <th>Manufacturer</th>
            <th>Expiration Date</th>
        </tr>
    </thead>
    <tbody>
    <?php if($vaccines): ?>
        <?php foreach($vaccines as $v): ?>
        <tr>
            <td><?= htmlspecialchars($v['name']) ?></td>
            <td><?= htmlspecialchars($v['description']) ?></td>
            <td><?= htmlspecialchars($v['quantity']) ?></td>
            <td><?= htmlspecialchars($v['manufacturer']) ?></td>
            <td><?= htmlspecialchars($v['expiration_date']) ?></td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="5">No vaccines found.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</body>
</html>
