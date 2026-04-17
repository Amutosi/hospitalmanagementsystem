<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    exit();
}

require_once 'db_config.php';
$nurse_id = $_SESSION['nurse_id'];

$recent_prescriptions = $conn->query("
    SELECT p.*, CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    WHERE p.NurseID = $nurse_id
    ORDER BY p.PrescriptionDate DESC
    LIMIT 10
");

if($recent_prescriptions && $recent_prescriptions->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr><th>Patient</th><th>Medication</th><th>Dosage</th><th>Frequency</th><th>Date</th></tr>
            </thead>
            <tbody>
                <?php while($row = $recent_prescriptions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['PatientName']; ?></td>
                    <td><?php echo $row['MedicationName']; ?></td>
                    <td><?php echo $row['Dosage']; ?></td>
                    <td><?php echo $row['Frequency']; ?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($row['PrescriptionDate'])); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
<?php else: ?>
    <p class="text-center text-muted">No prescriptions given yet</p>
<?php endif; ?>