<?php
require_once 'db_config.php';

$patients = $conn->query("SELECT COUNT(*) as count FROM Patient WHERE IsActive = 1")->fetch_assoc()['count'];
$doctors = $conn->query("SELECT COUNT(*) as count FROM Doctor WHERE IsActive = 1")->fetch_assoc()['count'];
$appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DATE(AppointmentDateTime) = CURDATE()")->fetch_assoc()['count'];
$pendingBills = $conn->query("SELECT COUNT(*) as count FROM Billing WHERE PaymentStatus IN ('Pending', 'Partial')")->fetch_assoc()['count'];

echo json_encode([
    'patients' => $patients,
    'doctors' => $doctors,
    'appointments' => $appointments,
    'pendingBills' => $pendingBills
]);
?>