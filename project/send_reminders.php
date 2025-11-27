<?php
session_start();
require_once 'vendor/autoload.php'; // PHPMailer autoload

// --- Access control: Only health workers ---
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'healthworker') {
    header("Location: login.php");
    exit();
}

// --- Database connection ---
$conn = new mysqli("localhost", "root", "", "carevax");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- Check if sending method is selected ---
if (!isset($_POST['method']) || empty($_POST['method'])) {
    echo "<script>alert('Please select a sending method.'); window.location='manage_patients.php';</script>";
    exit;
}
$method = $_POST['method'];

// --- Fetch children + parent/mother info for upcoming/missed vaccines ---
$query = "
    SELECT 
        ch.id AS child_id, ch.name AS child_name, ch.dob,
        COALESCE(u.name, m.name) AS parent_name,
        COALESCE(u.phone_number, m.phone) AS contact,
        COALESCE(u.email, m.email) AS email,
        v.name AS vaccine_name,
        pv.date_scheduled,
        pv.status
    FROM children ch
    LEFT JOIN users u ON ch.parent_id = u.id AND u.role='parent'
    LEFT JOIN mothers m ON ch.mother_id = m.id
    LEFT JOIN patient_vaccines pv ON ch.id = pv.child_id
    LEFT JOIN vaccines v ON pv.vaccine_id = v.id
    WHERE pv.status IN ('upcoming', 'missed')
";

$result = $conn->query($query);

if (!$result || $result->num_rows === 0) {
    echo "<script>alert('No upcoming or missed vaccinations found.'); window.location='manage_patients.php';</script>";
    exit;
}

$sentCount = 0;

// --- Loop through each child record ---
while ($row = $result->fetch_assoc()) {
    $parentName   = $row['parent_name'] ?? 'Parent';
    $childName    = $row['child_name'] ?? 'Child';
    $vaccineName  = $row['vaccine_name'] ?? 'Vaccine';
    $dateScheduled= $row['date_scheduled'] ?? 'N/A';
    $contact      = $row['contact'] ?? '';
    $email        = $row['email'] ?? '';

    $message = "Hello $parentName, this is a reminder that $childName is scheduled for the $vaccineName vaccine on $dateScheduled. Please visit your nearest clinic on time. - CareVax Health Center";

    if ($method === 'SMS' && !empty($contact)) {
        // --- Send SMS via EgoSMS ---
        $url = 'https://www.egosms.co/api/v1/plain/';
        $postData = http_build_query([
            'username' => 'BBMI',
            'password' => 'asimblack256',
            'sender'   => 'BBMI',
            'message'  => $message,
            'to'       => $contact
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $sentCount++;

    } elseif ($method === 'Email' && !empty($email)) {
        // --- Send Email via PHPMailer ---
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'carevax50@gmail.com'; // sender email
            $mail->Password   = 'llefxigvgaqdocmz';   // app password
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('carevax50@gmail.com', 'CareVax Health Center');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Vaccination Reminder for $childName";
            $mail->Body    = nl2br($message);

            $mail->send();
            $sentCount++;
        } catch (Exception $e) {
            // Skip failed emails
            continue;
        }
    }
}

// --- Close DB connection ---
$conn->close();

// --- Confirmation alert ---
echo "<script>alert('Reminders sent successfully to $sentCount recipients via $method.'); window.location='manage_patients.php';</script>";
exit;
?>
