<?php
session_start();
require_once 'db_config.php';

if(!isset($_GET['id'])) {
    die("Patient ID not provided");
}

$patient_id = $_GET['id'];
$patient = $conn->query("SELECT * FROM Patient WHERE PatientID = $patient_id")->fetch_assoc();

$appointments = $conn->query("
    SELECT a.*, CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM Appointment a
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.PatientID = $patient_id
    ORDER BY a.AppointmentDateTime DESC
");

$prescriptions = $conn->query("
    SELECT p.*, CONCAT(n.FirstName, ' ', n.LastName) as NurseName
    FROM Prescription p
    LEFT JOIN Nurse n ON p.NurseID = n.NurseID
    WHERE p.PatientID = $patient_id
    ORDER BY p.PrescriptionDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4>Patient Information: <?php echo $patient['FirstName'] . ' ' . $patient['LastName']; ?></h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Date of Birth:</strong> <?php echo $patient['DateOfBirth']; ?></p>
                        <p><strong>Gender:</strong> <?php echo $patient['Gender']; ?></p>
                        <p><strong>Contact:</strong> <?php echo $patient['ContactNo']; ?></p>
                        <p><strong>Email:</strong> <?php echo $patient['Email'] ?: 'Not provided'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Blood Group:</strong> <?php echo $patient['BloodGroup'] ?: 'Not recorded'; ?></p>
                        <p><strong>Address:</strong> <?php echo nl2br($patient['Address'] ?: 'Not provided'); ?></p>
                        <p><strong>Emergency Contact:</strong> <?php echo $patient['EmergencyContactName'] ?: 'N/A'; ?> (<?php echo $patient['EmergencyContactNo'] ?: 'N/A'; ?>)</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Appointment History</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Doctor</th><th>Purpose</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $appointments->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['AppointmentDateTime']; ?></td>
                            <td>Dr. <?php echo $row['DoctorName']; ?></td>
                            <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                            <td><?php echo $row['Status']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Prescription History</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr><th>Date</th><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Given By</th></tr>
                    </thead>
                    <tbody>
                        <?php while($row = $prescriptions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['PrescriptionDate']; ?></td>
                            <td><?php echo $row['MedicationName']; ?></td>
                            <td><?php echo $row['Dosage']; ?></td>
                            <td><?php echo $row['Frequency']; ?></td>
                            <td><?php echo $row['NurseName'] ?: 'Doctor'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="text-center">
            <button class="btn btn-secondary" onclick="window.close()">Close</button>
        </div>
    </div>
</body>
</html>