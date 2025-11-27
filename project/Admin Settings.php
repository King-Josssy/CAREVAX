<?php
// admin_settings.php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db_connect.php'; // must set $conn (mysqli)

// ---- Ensure system_settings table exists and has a row ----
$createSettingsSql = "
CREATE TABLE IF NOT EXISTS system_settings (
  id TINYINT NOT NULL PRIMARY KEY,
  system_name VARCHAR(255) DEFAULT 'CareVax',
  logo VARCHAR(255) DEFAULT '',
  theme ENUM('light','dark') DEFAULT 'light',
  timezone VARCHAR(100) DEFAULT 'Africa/Kampala',
  language VARCHAR(50) DEFAULT 'en',
  sms_api_key TEXT DEFAULT NULL,
  sms_sender VARCHAR(100) DEFAULT NULL,
  email_sender VARCHAR(100) DEFAULT NULL,
  reminder_before_days INT DEFAULT 3,
  reminder_on_due TINYINT(1) DEFAULT 1,
  reminder_after_days INT DEFAULT 7,
  password_min_length INT DEFAULT 8,
  password_require_numbers TINYINT(1) DEFAULT 1,
  password_require_special TINYINT(1) DEFAULT 0,
  two_factor_enabled TINYINT(1) DEFAULT 0,
  session_timeout_minutes INT DEFAULT 30
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
";
$conn->query($createSettingsSql);

// Ensure there's a single row with id=1
$chk = $conn->query("SELECT COUNT(*) AS c FROM system_settings")->fetch_assoc()['c'];
if ($chk == 0) {
    $conn->query("INSERT INTO system_settings (id) VALUES (1)");
}

// Load settings
$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();
if (!$settings) {
    die("Failed to load system settings.");
}

// Admin profile
$user_id = $_SESSION['user_id'];
$profileStmt = $conn->prepare("SELECT username, email, phone, role FROM users WHERE id=? LIMIT 1");
$profileStmt->bind_param("i", $user_id);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileStmt->close();

$messages = []; // collect messages for display

// Helper: sanitize text input
function s($v) { return trim($v ?? ''); }

// ---- Handle POST actions ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Update profile (email/phone)
    if (isset($_POST['update_profile'])) {
        $email = s($_POST['email']);
        $phone = s($_POST['phone']);
        if (filter_var($email, FILTER_VALIDATE_EMAIL) && $phone !== '') {
            $u = $conn->prepare("UPDATE users SET email=?, phone=? WHERE id=?");
            $u->bind_param("ssi", $email, $phone, $user_id);
            if ($u->execute()) {
                $messages[] = ['type'=>'success','text'=>'Profile updated successfully.'];
                $profile['email'] = $email; $profile['phone'] = $phone;
            } else $messages[] = ['type'=>'error','text'=>'Error updating profile.'];
            $u->close();
        } else {
            $messages[] = ['type'=>'error','text'=>'Invalid email or phone.'];
        }
    }

    // Change password (with policy checks)
    if (isset($_POST['update_password'])) {
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        // validate policy using settings
        $minlen = (int)$settings['password_min_length'];
        $require_numbers = (bool)$settings['password_require_numbers'];
        $require_special = (bool)$settings['password_require_special'];

        $errors = [];
        if ($new !== $confirm) $errors[] = "Passwords do not match.";
        if (strlen($new) < $minlen) $errors[] = "Password must be at least {$minlen} characters.";
        if ($require_numbers && !preg_match('/\d/',$new)) $errors[] = "Password must include a number.";
        if ($require_special && !preg_match('/[^a-zA-Z0-9]/',$new)) $errors[] = "Password must include a special character.";

        if (empty($errors)) {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE users SET password=? WHERE id=?");
            $up->bind_param("si",$hash,$user_id);
            if ($up->execute()) $messages[] = ['type'=>'success','text'=>'Password updated successfully.'];
            else $messages[] = ['type'=>'error','text'=>'Error updating password.'];
            $up->close();
        } else {
            $messages[] = ['type'=>'error','text'=>implode(' ', $errors)];
        }
    }

    // Update system configuration (reminders, notifications, password policies, session timeout, 2FA)
    if (isset($_POST['update_system'])) {
        $system_name = s($_POST['system_name']);
        $timezone = s($_POST['timezone']);
        $language = s($_POST['language']);
        $theme = in_array($_POST['theme'] ?? '', ['light','dark']) ? $_POST['theme'] : 'light';
        $sms_api_key = s($_POST['ACcbd4c592bf6bf314aa98d8607255b095']);
        $sms_sender = s($_POST['+19785069723']);
        $email_sender = s($_POST['email_sender']);
        $reminder_before_days = max(0,intval($_POST['reminder_before_days'] ?? 3));
        $reminder_on_due = isset($_POST['reminder_on_due']) ? 1:0;
        $reminder_after_days = max(0,intval($_POST['reminder_after_days'] ?? 7));
        $password_min_length = max(4,intval($_POST['password_min_length'] ?? 8));
        $password_require_numbers = isset($_POST['password_require_numbers'])?1:0;
        $password_require_special = isset($_POST['password_require_special'])?1:0;
        $two_factor_enabled = isset($_POST['two_factor_enabled'])?1:0;
        $session_timeout_minutes = max(1,intval($_POST['session_timeout_minutes'] ?? 30));

        // FIXED bind_param types: 7 strings + 8 integers = 15 placeholders
        $up = $conn->prepare("UPDATE system_settings SET system_name=?, timezone=?, language=?, theme=?, sms_api_key=?, sms_sender=?, email_sender=?, reminder_before_days=?, reminder_on_due=?, reminder_after_days=?, password_min_length=?, password_require_numbers=?, password_require_special=?, two_factor_enabled=?, session_timeout_minutes=? WHERE id=1");
        $up->bind_param("sssssssiiiiiiii",
            $system_name, $timezone, $language, $theme, $sms_api_key, $sms_sender, $email_sender,
            $reminder_before_days, $reminder_on_due, $reminder_after_days,
            $password_min_length, $password_require_numbers, $password_require_special, $two_factor_enabled, $session_timeout_minutes
        );
        if ($up->execute()) {
            $messages[] = ['type'=>'success','text'=>'System configuration updated.'];
            // refresh $settings
            $settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();
        } else {
            $messages[] = ['type'=>'error','text'=>'Error saving system configuration.'];
        }
        $up->close();
    }

    // Upload logo
    if (isset($_POST['upload_logo'])) {
        if (!empty($_FILES['logo_file']['name'])) {
            $allowed = ['image/png','image/jpeg','image/jpg','image/svg+xml'];
            if (!in_array($_FILES['logo_file']['type'], $allowed)) {
                $messages[] = ['type'=>'error','text'=>'Invalid logo file type.'];
            } else {
                $uploadDir = __DIR__ . '/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                $ext = pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION);
                $target = $uploadDir . '/system_logo.' . $ext;
                if (move_uploaded_file($_FILES['logo_file']['tmp_name'],$target)) {
                    // store relative path
                    $relPath = 'uploads/system_logo.' . $ext;
                    $stmt = $conn->prepare("UPDATE system_settings SET logo=? WHERE id=1");
                    $stmt->bind_param("s",$relPath);
                    if ($stmt->execute()) {
                        $messages[] = ['type'=>'success','text'=>'Logo uploaded.'];
                        $settings['logo'] = $relPath;
                    } else $messages[] = ['type'=>'error','text'=>'Failed to save logo path.'];
                    $stmt->close();
                } else {
                    $messages[] = ['type'=>'error','text'=>'Failed to move uploaded file.'];
                }
            }
        } else $messages[] = ['type'=>'error','text'=>'No file uploaded.'];
    }

    // Export a single table to CSV (POST 'export_table' with value)
    if (isset($_POST['export_table']) && !empty($_POST['export_table'])) {
        $table = preg_replace('/[^a-z0-9_]/i','',$_POST['export_table']);
        $allowed_tables = ['children','users','vaccines','patient_vaccines'];
        if (!in_array($table,$allowed_tables)) {
            $messages[] = ['type'=>'error','text'=>'Export table not allowed.'];
        } else {
            // stream CSV and exit
            $fname = $table . '_' . date('Ymd_His') . '.csv';
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="'.$fname.'"');
            $out = fopen('php://output','w');
            // get columns
            $res = $conn->query("SELECT * FROM `$table` LIMIT 1");
            $fields = [];
            if ($res && $res->field_count) {
                $finfo = $res->fetch_fields();
                foreach ($finfo as $f) $fields[] = $f->name;
                fputcsv($out,$fields);
            }
            // stream rows
            $r = $conn->query("SELECT * FROM `$table`");
            while($row = $r->fetch_assoc()) {
                $line = [];
                foreach ($fields as $c) $line[] = $row[$c];
                fputcsv($out,$line);
            }
            fclose($out);
            exit(); // immediate download
        }
    }

    // Full backup (CSV files zipped)
    if (isset($_POST['backup_now'])) {
        // create temp dir
        $tmpdir = sys_get_temp_dir() . '/carevax_backup_' . time();
        mkdir($tmpdir);
        $tables = ['children','users','vaccines','patient_vaccines'];
        foreach ($tables as $t) {
            $fpath = $tmpdir . '/' . $t . '.csv';
            $out = fopen($fpath,'w');
            $res = $conn->query("SELECT * FROM `$t` LIMIT 1");
            $fields = [];
            if ($res && $res->field_count) {
                foreach ($res->fetch_fields() as $f) $fields[] = $f->name;
                fputcsv($out,$fields);
            } else { // no rows, try to get schema from information_schema
                $cols = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='". $t ."' ORDER BY ORDINAL_POSITION");
                $fields = [];
                while($c = $cols->fetch_assoc()) $fields[] = $c['COLUMN_NAME'];
                if ($fields) fputcsv($out,$fields);
            }
            $r = $conn->query("SELECT * FROM `$t`");
            while($row = $r->fetch_assoc()) {
                $line = [];
                foreach ($fields as $c) $line[] = $row[$c] ?? '';
                fputcsv($out,$line);
            }
            fclose($out);
        }
        // zip
        $zipname = 'carevax_backup_' . date('Ymd_His') . '.zip';
        $zipPath = sys_get_temp_dir() . '/' . $zipname;
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE) === TRUE) {
            foreach ($tables as $t) $zip->addFile($tmpdir.'/'.$t.'.csv', $t.'.csv');
            $zip->close();
            // stream to browser
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.$zipname.'"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            // cleanup
            foreach ($tables as $t) @unlink($tmpdir.'/'.$t.'.csv');
            @rmdir($tmpdir);
            @unlink($zipPath);
            exit();
        } else {
            $messages[] = ['type'=>'error','text'=>'Failed to create backup ZIP.'];
        }
    }

    // Restore from uploaded ZIP of CSVs (destructive? This implementation inserts rows; it does NOT truncate tables)
    if (isset($_POST['restore_now'])) {
        if (!empty($_FILES['restore_file']['name'])) {
            $tmp = $_FILES['restore_file']['tmp_name'];
            $zip = new ZipArchive();
            if ($zip->open($tmp) === TRUE) {
                $allowed_tables = ['children','users','vaccines','patient_vaccines'];
                // iterate files
                for ($i=0;$i<$zip->numFiles;$i++) {
                    $name = $zip->getNameIndex($i);
                    $base = pathinfo($name,PATHINFO_FILENAME);
                    if (!in_array($base, $allowed_tables)) continue;
                    $stream = $zip->getStream($name);
                    if (!$stream) continue;
                    $header = null;
                    $rows = [];
                    while(($line = fgetcsv($stream)) !== false) {
                        if ($header === null) { $header = $line; continue; }
                        $row = array_combine($header,$line);
                        $rows[] = $row;
                    }
                    fclose($stream);
                    // simple insert for each row (prepare statement building)
                    if (!empty($rows)) {
                        $cols = array_keys($rows[0]);
                        $place = implode(',', array_fill(0, count($cols), '?'));
                        $types = str_repeat('s', count($cols)); // treat as strings
                        $sql = "INSERT INTO `$base` (`" . implode('`,`',$cols) . "`) VALUES ($place)";
                        $stmt = $conn->prepare($sql);
                        foreach ($rows as $r) {
                            $vals = array_map(function($v){ return $v === '' ? null : $v; }, array_values($r));
                            // bind_param needs references — build param array with references
                            $bind_params = [];
                            $bind_params[] = $types;
                            // convert nulls to nulls (mysqli will accept null if bound as null variable)
                            for ($k=0;$k<count($vals);$k++){
                                // ensure each is a variable and by reference
                                $bind_params[] = &$vals[$k];
                            }
                            // call_user_func_array requires an array of references
                            call_user_func_array(array($stmt, 'bind_param'), $bind_params);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }
                }
                $zip->close();
                $messages[] = ['type'=>'success','text'=>'Restore finished (rows inserted).'];
            } else {
                $messages[] = ['type'=>'error','text'=>'Invalid ZIP uploaded.'];
            }
        } else $messages[] = ['type'=>'error','text'=>'No restore file selected.'];
    }
}

// reload settings after possible updates
$settings = $conn->query("SELECT * FROM system_settings WHERE id=1")->fetch_assoc();

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin Settings — System Configuration</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root { --accent: #3498db; --success:#2ecc71; --danger:#e74c3c; }
    body { margin: 0; font-family: Inter,Segoe UI,Roboto,Arial; background:#f4f6f8; color:#222; }
    .sidebar { width:220px; background:#2c3e50; position:fixed; top:0; left:0; bottom:0; color:#fff; padding:20px; box-sizing:border-box; }
    .sidebar h2 { margin:0 0 20px; font-size:18px; }
    .sidebar a { color:#fff; display:block; padding:8px 10px; border-radius:6px; text-decoration:none; margin-bottom:8px; }
    .sidebar a.active, .sidebar a:hover { background:#34495e; }
    .topbar { margin-left:220px; background:#fff; padding:16px 24px; box-shadow:0 1px 4px rgba(0,0,0,0.05); display:flex; align-items:center; justify-content:space-between; }
    .container { margin:24px 24px 80px 240px; max-width:1200px; }
    h1 { margin:0 0 12px; color:#2c3e50; }
    .grid { display:grid; grid-template-columns: 1fr 360px; gap:20px; align-items:start; }
    .card { background:#fff; border-radius:10px; padding:16px; box-shadow:0 6px 20px rgba(0,0,0,0.06); }
    form .row { display:flex; gap:12px; }
    label { font-size:13px; color:#333; display:block; margin-bottom:6px; }
    input[type=text], input[type=email], input[type=password], input[type=number], select, textarea { width:100%; padding:10px 12px; border:1px solid #ddd; border-radius:8px; box-sizing:border-box; }
    .btn { display:inline-block; padding:10px 14px; background:var(--accent); color:#fff; border:none; border-radius:8px; cursor:pointer; text-decoration:none; }
    .btn.warn { background:#f1c40f; color:#111; }
    .btn.ghost { background:transparent; color:var(--accent); border:1px solid var(--accent); }
    .msg { padding:10px 12px; border-radius:8px; margin-bottom:12px; }
    .msg.success { background:#e9f8f0; color:var(--success); border:1px solid rgba(46,204,113,0.12); }
    .msg.error { background:#fdecea; color:var(--danger); border:1px solid rgba(231,76,60,0.12); }

    /* small helpers */
    .small { font-size:13px; color:#666; }
    .field-inline { display:flex; gap:8px; align-items:center; }
    .logo-preview { width:100%; height:120px; display:flex; align-items:center; justify-content:center; background:#f0f0f0; border-radius:8px; overflow:hidden; }
    .logo-preview img { max-height:100%; max-width:100%; }
    .export-list button { display:block; width:100%; margin-bottom:8px; }
    .section-title { font-weight:600; margin-bottom:10px; color:#2c3e50; }
    @media (max-width:980px) {
      .grid { grid-template-columns:1fr; }
      .sidebar { position:relative; width:100%; height:auto; }
      .topbar { margin-left:0; }
      .container { margin:16px; }
    }
  </style>
  <script>
    function toggleThemePreview() {
      const theme = document.querySelector('select[name="theme"]').value;
      document.body.style.background = theme === 'dark' ? '#1f2937' : '#f4f6f8';
    }
  </script>
</head>
<body>
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

  <div class="topbar">
    <div><strong>System Settings</strong></div>
    <div class="small">Signed in as <strong><?= htmlspecialchars($profile['username'] ?? 'Admin') ?></strong></div>
  </div>

  <div class="container">
    <h1>Admin Settings — System Configuration</h1>

    <!-- messages -->
    <?php foreach($messages as $m): ?>
      <div class="msg <?= $m['type']=='success' ? 'success' : 'error' ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <div class="grid">
      <!-- left: main settings -->
      <div>
        <!-- Profile card (existing) -->
        <div class="card">
          <div style="display:flex;gap:12px;align-items:center;">
            <div style="width:68px;height:68px;border-radius:10px;background:#f1f1f1;display:flex;align-items:center;justify-content:center;">
              <i class="fas fa-user" style="font-size:28px;color:#999"></i>
            </div>
            <div>
              <div style="font-weight:700"><?= htmlspecialchars($profile['username'] ?? '') ?></div>
              <div class="small"><?= htmlspecialchars($profile['email'] ?? '') ?></div>
            </div>
          </div>

          <hr style="margin:12px 0">

          <form method="POST">
            <div style="display:flex;gap:12px;">
              <div style="flex:1">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($profile['email'] ?? '') ?>" required>
              </div>
              <div style="width:150px">
                <label>Phone</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" required>
              </div>
            </div>
            <div style="margin-top:10px;">
              <button class="btn" name="update_profile" value="1">Update Profile</button>
            </div>
          </form>
        </div>

        <!-- Password card -->
        <div class="card" style="margin-top:16px;">
          <div class="section-title">Security & Access Control</div>
          <form method="POST">
            <label>New Password</label>
            <input type="password" name="new_password" placeholder="New password" required>
            <label>Confirm Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm" required>
            <div style="margin-top:10px;">
              <button class="btn" name="update_password" value="1">Change Password</button>
            </div>
          </form>
        </div>

        <!-- System configuration -->
        <div class="card" style="margin-top:16px;">
          <div class="section-title">System Configuration</div>
          <form method="POST" onsubmit="toggleThemePreview()">
            <label>System Name</label>
            <input type="text" name="system_name" value="<?= htmlspecialchars($settings['system_name']) ?>">

            <label>Timezone</label>
            <select name="timezone">
              <?php
                $tzs = DateTimeZone::listIdentifiers();
                foreach($tzs as $tz) {
                  $sel = $tz === $settings['timezone'] ? 'selected' : '';
                  echo "<option value=\"".htmlspecialchars($tz)."\" $sel>".htmlspecialchars($tz)."</option>";
                }
              ?>
            </select>

            <label>Language</label>
            <select name="language">
              <?php $langs = ['en'=>'English','fr'=>'Français','sw'=>'Swahili'];
                foreach($langs as $k=>$v) {
                  $sel = ($k === $settings['language']) ? 'selected':''; echo "<option value=\"$k\" $sel>$v</option>";
                }
              ?>
            </select>

            <label>Theme</label>
            <select name="theme" onchange="toggleThemePreview()">
              <option value="light" <?= $settings['theme']=='light'?'selected':'' ?>>Light</option>
              <option value="dark" <?= $settings['theme']=='dark'?'selected':'' ?>>Dark</option>
            </select>

            <hr style="margin:12px 0">

            <div class="section-title">Notifications (SMS / Email)</div>
            <label>SMS API Key</label>
            <input type="text" name="sms_api_key" value="<?= htmlspecialchars($settings['sms_api_key']) ?>">

            <label>SMS Sender ID</label>
            <input type="text" name="sms_sender" value="<?= htmlspecialchars($settings['sms_sender']) ?>">

            <label>Email Sender</label>
            <input type="text" name="email_sender" value="<?= htmlspecialchars($settings['email_sender']) ?>">

            <hr style="margin:12px 0">

            <div class="section-title">Reminder intervals (days)</div>
            <div style="display:flex; gap:8px;">
              <div style="flex:1">
                <label>Before due (days)</label>
                <input type="number" name="reminder_before_days" min="0" value="<?= intval($settings['reminder_before_days']) ?>">
              </div>
              <div style="flex:1">
                <label>On due date</label>
                <div style="margin-top:6px;">
                  <label><input type="checkbox" name="reminder_on_due" <?= $settings['reminder_on_due'] ? 'checked' : '' ?>> Send</label>
                </div>
              </div>
              <div style="flex:1">
                <label>After missed (days)</label>
                <input type="number" name="reminder_after_days" min="0" value="<?= intval($settings['reminder_after_days']) ?>">
              </div>
            </div>

            <hr style="margin:12px 0">

            <div class="section-title">Password policy</div>
            <div style="display:flex; gap:8px;">
              <div style="flex:1">
                <label>Min length</label>
                <input type="number" name="password_min_length" min="4" value="<?= intval($settings['password_min_length']) ?>">
              </div>
              <div style="flex:1">
                <label style="display:block;">Require number
                  <input type="checkbox" name="password_require_numbers" <?= $settings['password_require_numbers'] ? 'checked':'' ?> style="margin-left:8px;">
                </label>
              </div>
              <div style="flex:1">
                <label style="display:block;">Require special
                  <input type="checkbox" name="password_require_special" <?= $settings['password_require_special'] ? 'checked':'' ?> style="margin-left:8px;">
                </label>
              </div>
            </div>

            <div style="margin-top:12px;">
              <label>Enable Two-factor authentication</label>
              <div style="margin-top:6px;">
                <label><input type="checkbox" name="two_factor_enabled" <?= $settings['two_factor_enabled'] ? 'checked':'' ?>> Enabled</label>
              </div>
            </div>

            <div style="margin-top:12px;">
              <label>Session timeout (minutes)</label>
              <input type="number" name="session_timeout_minutes" min="1" value="<?= intval($settings['session_timeout_minutes']) ?>">
            </div>

            <div style="margin-top:12px;">
              <button class="btn" name="update_system" value="1">Save System Settings</button>
            </div>
          </form>
        </div>

        <!-- Data & Backup -->
        <div class="card" style="margin-top:16px;">
          <div class="section-title">Data Export & Backup</div>
          <div class="small" style="margin-bottom:8px;">Export individual table as CSV</div>
          <form method="POST">
            <div class="export-list">
              <button class="btn ghost" name="export_table" value="children">Export Children (CSV)</button>
              <button class="btn ghost" name="export_table" value="users">Export Users (CSV)</button>
              <button class="btn ghost" name="export_table" value="vaccines">Export Vaccines (CSV)</button>
              <button class="btn ghost" name="export_table" value="patient_vaccines">Export Vaccinations (CSV)</button>
            </div>
          </form>

          <hr style="margin:12px 0">

          <form method="POST" style="display:inline;">
            <button class="btn" name="backup_now" value="1">Download Full Backup (ZIP)</button>
          </form>

          <hr style="margin:12px 0">

          <div class="section-title">Restore</div>
          <div class="small">Upload a ZIP containing CSVs named: children.csv, users.csv, vaccines.csv, patient_vaccines.csv</div>
          <form method="POST" enctype="multipart/form-data" style="margin-top:8px;">
            <input type="file" name="restore_file" accept=".zip" required>
            <div style="margin-top:8px;">
              <button class="btn warn" name="restore_now" value="1">Restore From ZIP (Insert Rows)</button>
            </div>
          </form>
        </div>
      </div>

      <!-- right sidebar: system preferences, logo, quick actions -->
      <div>
        <div class="card">
          <div class="section-title">System Preferences</div>
          <div class="small">System name</div>
          <div style="font-weight:700; margin-bottom:8px;"><?= htmlspecialchars($settings['system_name']) ?></div>

          <div style="margin-top:8px;">
            <div class="small">Logo</div>
            <div class="logo-preview" style="margin-top:8px;">
              <?php if (!empty($settings['logo']) && file_exists(__DIR__.'/'.$settings['logo'])): ?>
                <img src="<?= htmlspecialchars($settings['logo']) ?>" alt="logo">
              <?php else: ?>
                <div class="small">No logo uploaded</div>
              <?php endif; ?>
            </div>
            <form method="POST" enctype="multipart/form-data" style="margin-top:8px;">
              <input type="file" name="logo_file" accept="image/*" required>
              <div style="margin-top:8px;">
                <button class="btn" name="upload_logo" value="1">Upload Logo</button>
              </div>
            </form>
          </div>

          <hr style="margin:12px 0">
          <div class="section-title">Quick Actions</div>
          <a href="manage_vaccines.php" class="btn ghost" style="display:block;margin-bottom:8px;">Manage Vaccines</a>
          <a href="manage_healthworkers.php" class="btn ghost" style="display:block;margin-bottom:8px;">Manage Health Workers</a>
          <a href="manage_parents.php" class="btn ghost" style="display:block;margin-bottom:8px;">Manage Parents</a>
        </div>

        <div class="card" style="margin-top:16px;">
          <div class="section-title">SMS / Email Preview</div>
          <div class="small">Saved SMS Sender</div>
          <div style="font-weight:700; margin-bottom:8px;"><?= htmlspecialchars($settings['sms_sender'] ?? '-') ?></div>
          <div class="small">Saved Email Sender</div>
          <div style="font-weight:700; margin-bottom:8px;"><?= htmlspecialchars($settings['email_sender'] ?? '-') ?></div>
        </div>
      </div>
    </div>
  </div>

  <script>
    // small UI helpers
    document.querySelectorAll('button').forEach(b=>{
      b.addEventListener('click', (e)=>{
        // allow normal form submissions
      });
    });
  </script>
</body>
</html>
