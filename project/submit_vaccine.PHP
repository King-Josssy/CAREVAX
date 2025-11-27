<?php 
session_start();
include 'db_connect.php'; // $conn

// Initialize message
$message = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null; // hidden input, empty for new
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 0);
    $manufacturer = trim($_POST['manufacturer'] ?? '');
    $expiration = $_POST['expiration_date'] ?? null;

    if ($name === '') {
        $message = ['type' => 'error', 'text' => 'Vaccine name is required.'];
    } else {
        if ($id) {
            // Update existing vaccine
            $stmt = $conn->prepare("UPDATE vaccines SET name=?, description=?, quantity=?, manufacturer=?, expiration_date=? WHERE id=?");
            $stmt->bind_param("ssissi", $name, $description, $quantity, $manufacturer, $expiration, $id);
            if($stmt->execute()){
                $message = ['type' => 'success', 'text' => 'Vaccine updated successfully.'];
            } else {
                $message = ['type' => 'error', 'text' => 'Failed to update vaccine.'];
            }
            $stmt->close();
        } else {
            // Insert new vaccine
            $stmt = $conn->prepare("INSERT INTO vaccines (name, description, quantity, manufacturer, expiration_date) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssiss", $name, $description, $quantity, $manufacturer, $expiration);
            if($stmt->execute()){
                $message = ['type' => 'success', 'text' => 'Vaccine added successfully.'];
            } else {
                $message = ['type' => 'error', 'text' => 'Failed to add vaccine.'];
            }
            $stmt->close();
        }
    }
}

// Fetch existing vaccines to display
$vaccines = $conn->query("SELECT * FROM vaccines ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Vaccine Management</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* General */
body {font-family: Arial, sans-serif; margin:0; padding:0; background:#f4f6f8;}
.dashboard {display:flex; min-height:100vh;}

/* Sidebar */
.sidebar {width:220px; background:#2c3e50; color:#fff; padding:20px; display:flex; flex-direction:column; position:fixed; height:100%;}
.sidebar h2 {margin-top:0;margin-bottom:20px;}
.sidebar a {display:block;color:#fff;text-decoration:none;padding:10px;border-radius:4px;margin-bottom:5px;}
.sidebar a.active, .sidebar a:hover {background:#34495e;}

/* Main content */
.main-content {margin-left:240px; padding:24px; box-sizing:border-box; flex-grow:1;}
.card {background:#fff;padding:20px;border-radius:8px;margin-bottom:20px;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
form input, form textarea {width:100%;padding:8px;margin-bottom:12px;border:1px solid #ccc;border-radius:4px;box-sizing:border-box;}
form label {font-weight:bold;margin-bottom:4px;display:block;}
form button {padding:10px 20px;background:#3498db;color:#fff;border:none;border-radius:6px;cursor:pointer;}
form button:hover {background:#2980b9;}

/* Table */
.table-container {max-height:400px; overflow-y:auto;}
table {width:100%;border-collapse:collapse;}
table th, table td {border:1px solid #ddd;padding:10px;text-align:left;}
table th {background:#f0f0f0; position: sticky; top: 0; z-index:1;}

/* Messages */
.msg {padding:10px;margin-bottom:15px;border-radius:6px;}
.msg.success {background:#e9f8f0;color:#2ecc71;}
.msg.error {background:#fdecea;color:#e74c3c;}
</style>
</head>
<body>

<div class="dashboard">
  <!-- Sidebar -->
 <div class="sidebar">
   <h2>Admin</h2>
   <a href="Admin.php"><i class="fas fa-home"></i> Dashboard</a>
   <a href="admin manage_patients.php"><i class="fas fa-user-injured"></i> Manage Patients</a>
   <a href="manage_healthworkers.php"><i class="fas fa-user-nurse"></i> Manage Health Workers</a>
   <a href="admin manage_parents.php"><i class="fas fa-users"></i> Manage Parents</a>
   <a href="register_vaccine.php"><i class="fas fa-syringe"></i> Register Vaccines</a>
   <a href="reports.php"><i class="fas fa-chart-line"></i> Reports</a>
   <a href="Admin settings.php"><i class="fas fa-cogs"></i> Settings</a>
   <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
 </div>

  <!-- Main Content -->
  <main class="main-content">
    <header>
      <h1>Vaccine Management</h1>
    </header>

    <!-- Message -->
    <?php if(!empty($message)): ?>
      <div class="msg <?= $message['type'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message['text']) ?>
      </div>
    <?php endif; ?>

    <!-- Add / Update Vaccine Form -->
    <div class="card">
      <h2><i class="fas fa-plus-circle"></i> Add / Update Vaccine</h2>
      <form method="POST">
        <input type="hidden" name="id" value="">
        
        <label>Vaccine Name</label>
        <input type="text" name="name" placeholder="Enter vaccine name" required>

        <label>Description</label>
        <textarea name="description" placeholder="Enter description"></textarea>

        <label>Quantity</label>
        <input type="number" name="quantity" value="0" min="0">

        <label>Manufacturer</label>
        <input type="text" name="manufacturer" placeholder="Enter manufacturer">

        <label>Expiration Date</label>
        <input type="date" name="expiration_date">

        <button type="submit"><i class="fas fa-save"></i> Submit</button>
      </form>
    </div>

    <!-- Existing Vaccines Table -->
    <div class="card">
      <h2><i class="fas fa-list"></i> Existing Vaccines</h2>
      <div class="table-container">
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
                <td><?= htmlspecialchars($v['name'] ?? '') ?></td>
                <td><?= htmlspecialchars($v['description'] ?? '') ?></td>
                <td><?= htmlspecialchars($v['quantity'] ?? '0') ?></td>
                <td><?= htmlspecialchars($v['manufacturer'] ?? '') ?></td>
                <td><?= htmlspecialchars($v['expiration_date'] ?? '') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5">No vaccines found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script>
// Optional: Click row to edit form
const tableRows = document.querySelectorAll('tbody tr');
tableRows.forEach(row => {
    row.addEventListener('click', () => {
        const cells = row.children;
        document.querySelector('input[name="name"]').value = cells[0].innerText;
        document.querySelector('textarea[name="description"]').value = cells[1].innerText;
        document.querySelector('input[name="quantity"]').value = cells[2].innerText;
        document.querySelector('input[name="manufacturer"]').value = cells[3].innerText;
        document.querySelector('input[name="expiration_date"]').value = cells[4].innerText;
        // Hidden ID field would need a unique identifier (optional: add id in table)
    });
});
</script>

</body>
</html>
