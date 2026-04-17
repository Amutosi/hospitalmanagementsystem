<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) { header("Location: patient_login.php"); exit(); }
require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];

$prescriptions = $conn->query("
    SELECT p.*, CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM Prescription p
    LEFT JOIN Doctor d ON p.DoctorID = d.DoctorID
    WHERE p.PatientID = $patient_id
    ORDER BY p.PrescriptionDate DESC
");
?>
<!DOCTYPE html>
<html>
<head><title>My Prescriptions</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
         body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;

    background: 
        
        url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1600&q=80') no-repeat center center/cover;

    overflow-x: hidden;
}
    .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding-top: 20px; }
    .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; }
    .sidebar a:hover { background: rgba(255,255,255,0.2); }
    .content { margin-left: 260px; padding: 20px; }
    .card { border: none; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    @media (max-width: 768px) { .sidebar { left: -260px; } .content { margin-left: 0; } }
</style>
</head>
<body>
<div class="sidebar">
    <h4 class="text-center mb-4">🏥 Patient Portal</h4>
    <a href="patient_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="patient_appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
    <a href="patient_labtests.php"><i class="bi bi-flask"></i> Lab Tests</a>
    <a href="patient_prescriptions.php" class="active"><i class="bi bi-prescription"></i> Prescriptions</a>
    <a href="patient_bills.php"><i class="bi bi-credit-card"></i> Bills</a>
    <a href="patient_treatments.php"><i class="bi bi-clipboard2-pulse"></i> Treatments</a>
    <a href="patient_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
    <a href="patient_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>
<div class="content">
    <div class="card">
        <div class="card-header bg-white fw-bold">📝 My Prescriptions</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>Date</th><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Prescribed By</th><th>Instructions</th></tr></thead>
                    <tbody>
                        <?php while($row = $prescriptions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo date('Y-m-d', strtotime($row['PrescriptionDate'])); ?></td>
                            <td><?php echo $row['MedicationName']; ?></td>
                            <td><?php echo $row['Dosage']; ?></td>
                            <td><?php echo $row['Frequency']; ?></td>
                            <td><?php echo $row['Duration']; ?></td>
                            <td><?php echo $row['DoctorName'] ?: 'Nurse'; ?></td>
                            <td><?php echo $row['Instructions']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>