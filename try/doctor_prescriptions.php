<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

$doctor_id = $_SESSION['doctor_id'];
$doctor = $conn->query("SELECT * FROM Doctor WHERE DoctorID = $doctor_id")->fetch_assoc();

// Handle Add Prescription
if(isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    
    // CHECK: If this patient already has nurse prescriptions
    $check_nurse = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE PatientID = $patient_id AND NurseID IS NOT NULL");
    $nurse_count = $check_nurse->fetch_assoc()['count'];
    
    if($nurse_count > 0) {
        // Patient already has nurse prescriptions - BLOCK doctor
        echo "<script>
            alert('❌ Cannot prescribe! This patient already has prescriptions from a Nurse. Only nurses can prescribe for this patient.');
            window.location.href = 'doctor_prescriptions.php';
        </script>";
        exit();
    }
    
    // If no nurse prescriptions, allow doctor to prescribe
    $sql = "INSERT INTO Prescription (PatientID, DoctorID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions) 
            VALUES ('$patient_id', '$doctor_id', NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions')";
    
    if($conn->query($sql)) {
        // Update patient last prescribed info
        $conn->query("UPDATE Patient SET LastPrescribedBy = 'Doctor', LastPrescriptionDate = NOW() WHERE PatientID = $patient_id");
        echo "<script>alert('✅ Prescription added successfully!'); window.location.href='doctor_prescriptions.php';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Delete Prescription
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM Prescription WHERE PrescriptionID = $id");
    echo "<script>alert('Prescription deleted!'); window.location.href='doctor_prescriptions.php';</script>";
}

// Get patients - EXCLUDE those who already have nurse prescriptions
$patients = $conn->query("
    SELECT p.PatientID, CONCAT(p.FirstName, ' ', p.LastName) as Name,
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND NurseID IS NOT NULL) as has_nurse_prescription
    FROM Patient p
    WHERE p.IsActive = 1
    ORDER BY Name ASC
");

// Get doctor's prescriptions
$prescriptions = $conn->query("
    SELECT p.*, CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    WHERE p.DoctorID = $doctor_id
    ORDER BY p.PrescriptionDate DESC
");

// Get count
$doctor_prescriptions_count = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE DoctorID = $doctor_id")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
       body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=1600&q=80');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    overflow-x: hidden;
}
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; }
        .btn-back { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; }
        .doctor-badge { background: #1e3c72; color: white; padding: 3px 10px; border-radius: 15px; font-size: 11px; }
        .locked-patient { background: #f8d7da; color: #721c24; }
        .prescription-form { background: #f8f9fa; padding: 20px; border-radius: 10px; }
        .stat-number { font-size: 32px; font-weight: bold; color: #1e3c72; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-prescription"></i> Doctor Prescriptions</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <!-- Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-center">
                    <div class="card-body">
                        <div class="stat-number"><?php echo $doctor_prescriptions_count; ?></div>
                        <p>Total Prescriptions by You</p>
                        <span class="doctor-badge"><i class="bi bi-person-badge"></i> Doctor Only</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Add Prescription Form -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Write New Prescription
                <button class="btn btn-sm btn-primary float-end" onclick="toggleForm()">+ New Prescription</button>
            </div>
            <div class="card-body" id="prescriptionForm" style="display: none;">
                <form method="POST" class="prescription-form" onsubmit="return validateForm()">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label>Select Patient *</label>
                            <select name="patient_id" id="patient_select" class="form-control" required>
                                <option value="">-- Select Patient --</option>
                                <?php while($row = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $row['PatientID']; ?>" 
                                        <?php echo $row['has_nurse_prescription'] > 0 ? 'disabled style="background:#f8d7da; color:#721c24;"' : ''; ?>>
                                        <?php echo $row['Name']; ?>
                                        <?php if($row['has_nurse_prescription'] > 0): ?>
                                            (🔒 Locked - Has Nurse Prescriptions)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small class="text-danger">⚠️ Patients with existing nurse prescriptions cannot be prescribed by doctors</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label>Medication Name *</label>
                            <input type="text" name="medication" id="medication" class="form-control" placeholder="e.g., Amoxicillin, Paracetamol" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Dosage *</label>
                            <input type="text" name="dosage" id="dosage" class="form-control" placeholder="e.g., 500mg, 1 tablet" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Frequency *</label>
                            <input type="text" name="frequency" id="frequency" class="form-control" placeholder="e.g., Twice daily" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Duration *</label>
                            <input type="text" name="duration" id="duration" class="form-control" placeholder="e.g., 7 days" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Follow-up Date (Optional)</label>
                            <input type="date" name="followup" class="form-control">
                        </div>
                        <div class="col-12 mb-3">
                            <label>Instructions</label>
                            <textarea name="instructions" id="instructions" class="form-control" rows="3" placeholder="Take with food, Before bedtime, etc."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_prescription" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Prescription
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                </form>
            </div>
        </div>
        
        <!-- Prescriptions List -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-ul"></i> My Prescriptions
                <span class="doctor-badge float-end">Doctor Only</span>
            </div>
            <div class="card-body">
                <?php if($prescriptions && $prescriptions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Patient</th>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $prescriptions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['PrescriptionDate'])); ?></td>
                                    <td><strong><?php echo $row['PatientName']; ?></strong></td>
                                    <td><?php echo $row['MedicationName']; ?></td>
                                    <td><?php echo $row['Dosage']; ?></td>
                                    <td><?php echo $row['Frequency']; ?></td>
                                    <td><?php echo $row['Duration']; ?></td>
                                    <td><?php echo substr($row['Instructions'], 0, 30) . (strlen($row['Instructions']) > 30 ? '...' : ''); ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $row['PrescriptionID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this prescription?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-prescription" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No prescriptions written by you yet</p>
                        <small>Click "New Prescription" to write your first prescription</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        
    </div>

    <script>
        function toggleForm() {
            var form = document.getElementById('prescriptionForm');
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
        
        function validateForm() {
            var patientSelect = document.getElementById('patient_select');
            var selectedOption = patientSelect.options[patientSelect.selectedIndex];
            
            if(patientSelect.value == '') {
                alert('Please select a patient');
                return false;
            }
            
            // Check if selected patient is disabled (has nurse prescriptions)
            if(selectedOption.disabled) {
                alert('❌ This patient already has prescriptions from a Nurse. Doctors cannot prescribe for this patient.');
                return false;
            }
            
            if(document.getElementById('medication').value == '') {
                alert('Please enter medication name');
                return false;
            }
            if(document.getElementById('dosage').value == '') {
                alert('Please enter dosage');
                return false;
            }
            if(document.getElementById('frequency').value == '') {
                alert('Please enter frequency');
                return false;
            }
            if(document.getElementById('duration').value == '') {
                alert('Please enter duration');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>