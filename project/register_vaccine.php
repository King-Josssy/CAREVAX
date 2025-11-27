<?php 
session_start();
include 'db_connect.php'; // $conn

// Handle form submission (Add / Update / Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'save') {
        $id = intval($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $quantity = intval($_POST['quantity'] ?? 0);
        $manufacturer = trim($_POST['manufacturer'] ?? '');
        $expiration = $_POST['expiration_date'] ?? null;

        if ($name === '') {
            $message = ['type' => 'error', 'text' => 'Vaccine name is required.'];
        } else {
            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE vaccines SET name=?, description=?, quantity=?, manufacturer=?, expiration_date=? WHERE id=?");
                $stmt->bind_param("ssissi", $name, $description, $quantity, $manufacturer, $expiration, $id);
                $stmt->execute();
                $message = ['type' => 'success', 'text' => 'Vaccine updated successfully.'];
            } else {
                $stmt = $conn->prepare("INSERT INTO vaccines (name, description, quantity, manufacturer, expiration_date) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("ssiss", $name, $description, $quantity, $manufacturer, $expiration);
                $stmt->execute();
                $message = ['type' => 'success', 'text' => 'Vaccine added successfully.'];
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare("DELETE FROM vaccines WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = ['type' => 'success', 'text' => 'Vaccine deleted successfully.'];
        }
    }
}

// Fetch vaccines
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
body {
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  margin: 0;
  padding: 0;
  height: 180vh;
  display: flex;
  background: 
    linear-gradient(rgba(0, 0, 0, 0.55), rgba(0, 0, 0, 0.55)), 
    url('re.jpeg') no-repeat center center/cover;
  background-attachment: fixed; /* Fixed background */
  color: #fff;
}

/* ===== Dashboard Layout ===== */
.dashboard {
  display: flex;
  width: 100%;
  height: 100%;
}

/* ===== Sidebar ===== */
.sidebar {
  width: 240px;
  background: rgba(44, 62, 80, 0.95);
  color: #fff;
  display: flex;
  flex-direction: column;
  padding: 25px 15px;
  position: fixed;
  top: 0;
  left: 0;
  height: 100%;
  box-shadow: 2px 0 8px rgba(0,0,0,0.3);
}

.sidebar h2 {
  margin: 0 0 25px 0;
  font-size: 1.4em;
  text-align: center;
  letter-spacing: 1px;
}

.sidebar a {
  display: block;
  color: #ecf0f1;
  text-decoration: none;
  padding: 12px;
  margin-bottom: 8px;
  border-radius: 8px;
  transition: 0.3s;
  font-weight: 500;
}

.sidebar a:hover,
.sidebar a.active {
  background: #3498db;
  color: #fff;
  transform: translateX(4px);
}

/* ===== Main Content ===== */
.main-content {
  margin-left: 260px; /* Space for fixed sidebar */
  flex-grow: 1;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 40px;
  box-sizing: border-box;
  flex-direction: column;
  text-align: center;
}

/* ===== Card Section ===== */
.card {
  background: rgba(255, 255, 255, 0.12);
  backdrop-filter: blur(8px);
  border-radius: 15px;
  padding: 25px 30px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.3);
  width: 70%;
  margin-bottom: 15px;
}

.card h3 {
  margin-top: 0;
  color: #00b4d8;
  font-size: 1.4em;
}

/* ===== Buttons ===== */
.buttons-below {
  display: flex;
  justify-content: center;
  gap: 15px;
  margin-top: 15px;
}

.buttons-below button {
  padding: 12px 22px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 600;
  color: #fff;
  transition: background 0.3s, transform 0.2s;
}

#editSelected {
  background-color: #006400;
}
#editSelected:hover {
  background-color: #008000;
  transform: scale(1.05);
}

#deleteSelected {
  background-color: #e74c3c;
}
#deleteSelected:hover {
  background-color: #c0392b;
  transform: scale(1.05);
}

/* ===== Table Styling ===== */
.table-container {
  max-height: 400px;
  overflow-y: auto;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 8px;
}

table {
  width: 100%;
  border-collapse: collapse;
  color: #fff;
}

table th, table td {
  padding: 32px 10px;
  border-bottom: 1px solid rgba(255,255,255,0.2);
  text-align: center;
}

table th {
  background: rgba(0, 0, 0, 0.75);
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.9em;
}

/* Highlight selected row */
#vaccineTable tr.selected {
    background-color: rgba(52, 152, 219, 0.5);
}

/* ===== Messages ===== */
.msg {
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 6px;
}

.msg.success {
  background: rgba(46, 204, 113, 0.2);
  color: #2ecc71;
}

.msg.error {
  background: rgba(231, 76, 60, 0.2);
  color: #e74c3c;
}

/* ===== Form Styling ===== */
.form-container {
  background-color: rgba(255, 255, 255, 0.2); /* Semi-transparent */
  padding: 25px 30px;
  border-radius: 12px;
  width: 350px;
  margin: 20px auto; /* Center form */
}

.form-container h2 {
  color: #00b4d8;
  margin-bottom: 15px;
  font-size: 1.5em;
  text-align: center;
  background-color: rgba(0,0,0,0.3);
  padding: 10px;
  border-radius: 8px;
}

.form-container label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
  color: #fff;
}

.form-container input {
  width: 100%;
  padding: 10px;
  margin-bottom: 15px;
  border-radius: 6px;
  border: 1px solid #ccc;
}

.form-container button {
  width: 100%;
  padding: 12px;
  border-radius: 8px;
  border: none;
  background-color: #4CAF50;
  color: #fff;
  font-weight: bold;
  cursor: pointer;
}

.form-container button:hover {
  background-color: #45a049;
}
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

    <?php if(!empty($message)): ?>
      <div class="msg <?= $message['type'] === 'success' ? 'success' : 'error' ?>">
        <?= htmlspecialchars($message['text']) ?>
      </div>
    <?php endif; ?>

    <!-- Improved Add / Update Vaccine Form -->
    <div class="form-container">
      <h2><i class="fas fa-plus-circle"></i> Add / Update Vaccine</h2>
      <form method="POST" id="vaccineForm">
        <input type="hidden" name="id" id="vaccineId" value="">
        <input type="hidden" name="action" id="formAction" value="save">

        <label for="vaccineName">Vaccine Name</label>
        <input type="text" name="name" id="vaccineName" placeholder="Enter vaccine name" required>

        <label for="vaccineDescription">Description</label>
        <input type="text" name="description" id="vaccineDescription" placeholder="Enter description">

        <label for="vaccineQuantity">Quantity</label>
        <input type="number" name="quantity" id="vaccineQuantity" value="0" min="0">

        <label for="vaccineManufacturer">Manufacturer</label>
        <input type="text" name="manufacturer" id="vaccineManufacturer" placeholder="Enter manufacturer">

        <label for="vaccineExpiration">Expiration Date</label>
        <input type="date" name="expiration_date" id="vaccineExpiration">

        <button type="submit"><i class="fas fa-save"></i> Submit</button>
      </form>
    </div>

    <!-- Existing Vaccines Table -->
    <div class="card">
      <h2><i class="fas fa-list"></i> Existing Vaccines</h2>
      <div class="table-container">
      <table id="vaccineTable">
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
          <?php foreach($vaccines as $v): ?>
          <tr data-id="<?= $v['id'] ?>">
            <td><?= htmlspecialchars($v['name'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['description'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['quantity'] ?? 0) ?></td>
            <td><?= htmlspecialchars($v['manufacturer'] ?? '') ?></td>
            <td><?= htmlspecialchars($v['expiration_date'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      </div>

      <div class="buttons-below">
        <button type="button" id="editSelected"><i class="fas fa-edit"></i> Edit</button>
        <button type="button" id="deleteSelected"><i class="fas fa-trash"></i> Delete</button>
      </div>
    </div>
  </main>
</div>

<script>
let selectedRow = null;
const table = document.getElementById('vaccineTable');
const rows = table.querySelectorAll('tbody tr');

rows.forEach(row => {
    row.addEventListener('click', () => {
        if (selectedRow) selectedRow.classList.remove('selected');
        selectedRow = row;
        row.classList.add('selected');
    });
});

// Edit Selected
document.getElementById('editSelected').addEventListener('click', () => {
    if (!selectedRow) { alert('Please select a row to edit.'); return; }

    document.getElementById('vaccineId').value = selectedRow.dataset.id;
    document.getElementById('vaccineName').value = selectedRow.cells[0].innerText;
    document.getElementById('vaccineDescription').value = selectedRow.cells[1].innerText;
    document.getElementById('vaccineQuantity').value = selectedRow.cells[2].innerText;
    document.getElementById('vaccineManufacturer').value = selectedRow.cells[3].innerText;
    document.getElementById('vaccineExpiration').value = selectedRow.cells[4].innerText;
    document.getElementById('formAction').value = 'save';
});

// Delete Selected
document.getElementById('deleteSelected').addEventListener('click', () => {
    if (!selectedRow) { alert('Please select a row to delete.'); return; }
    if (!confirm('Are you sure you want to delete this vaccine?')) return;

    document.getElementById('vaccineId').value = selectedRow.dataset.id;
    document.getElementById('formAction').value = 'delete';
    document.getElementById('vaccineForm').submit();
});
</script>

</body>
</html>
