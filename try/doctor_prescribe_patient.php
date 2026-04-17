<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];
$patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : 0;

$patient = $conn->query("SELECT * FROM Patient WHERE PatientID = $patient_id")->fetch_assoc();

if(isset($_POST['add_prescription'])) {
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    
    $sql = "INSERT INTO Prescription (PatientID, DoctorID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions) 
            VALUES ('$patient_id', '$doctor_id', NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Prescription added for {$patient['FirstName']} {$patient['LastName']}!'); window.location.href='doctor_patients.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Prescribe for <?php echo $patient['FirstName'] . ' ' . $patient['LastName']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Write Prescription for: <?php echo $patient['FirstName'] . ' ' . $patient['LastName']; ?></h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label>Medication Name *</label>
                        <input type="text" name="medication" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Dosage *</label>
                        <input type="text" name="dosage" class="form-control" placeholder="e.g., 500mg" required>
                    </div>
                    <div class="mb-3">
                        <label>Frequency *</label>
                        <input type="text" name="frequency" class="form-control" placeholder="e.g., Twice daily" required>
                    </div>
                    <div class="mb-3">
                        <label>Duration *</label>
                        <input type="text" name="duration" class="form-control" placeholder="e.g., 7 days" required>
                    </div>
                    <div class="mb-3">
                        <label>Instructions</label>
                        <textarea name="instructions" class="form-control" rows="3"></textarea>
                    </div>
                    <button type="submit" name="add_prescription" class="btn btn-primary">Save Prescription</button>
                    <a href="doctor_patients.php" class="btn btn-secondary">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>