<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$nurse_id = $_SESSION['nurse_id'];

// Get nurse info
$nurse = $conn->query("SELECT * FROM Nurse WHERE NurseID = $nurse_id")->fetch_assoc();

// Handle prescription submission
if(isset($_POST['give_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    
    // FIRST: Check if this patient already has doctor prescriptions
    $check_doctor = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE PatientID = $patient_id AND DoctorID IS NOT NULL");
    $doctor_count = $check_doctor->fetch_assoc()['count'];
    
    if($doctor_count > 0) {
        // Patient already has doctor prescriptions - BLOCK nurse
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Cannot Prescribe!',
                text: 'This patient already has prescriptions from a Doctor. Only doctors can prescribe for this patient.',
                confirmButtonColor: '#dc3545'
            }).then(() => { window.location.href = 'nurse_give_prescription.php'; });
        </script>";
        exit();
    }
    
    // If no doctor prescriptions, allow nurse to prescribe
    $sql = "INSERT INTO Prescription (PatientID, NurseID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions) 
            VALUES ('$patient_id', '$nurse_id', NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions')";
    
    if($conn->query($sql)) {
        $patient = $conn->query("SELECT FirstName, LastName FROM Patient WHERE PatientID = $patient_id")->fetch_assoc();
        $patient_name = $patient['FirstName'] . ' ' . $patient['LastName'];
        
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Prescription Given!',
                text: 'Prescription given successfully to $patient_name',
                confirmButtonColor: '#28a745',
                timer: 2000
            }).then(() => { window.location.href = 'nurse_dashboard.php'; });
        </script>";
        exit();
    } else {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '" . addslashes($conn->error) . "',
                confirmButtonColor: '#dc3545'
            });
        </script>";
    }
}

// Get patients for dropdown - EXCLUDE those who already have doctor prescriptions
$patients = $conn->query("
    SELECT p.PatientID, CONCAT(p.FirstName, ' ', p.LastName) as Name,
           p.BloodGroup, p.ContactNo,
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND DoctorID IS NOT NULL) as has_doctor_prescription
    FROM Patient p
    WHERE p.IsActive = 1
    ORDER BY FirstName ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Give Prescription - Nurse Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=1600&q=80');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    overflow-x: hidden;
}
        .container-fluid {
            max-width: 1200px;
            margin: 0 auto;
        }
        /* Header Section */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        /* Main Form Card */
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 18px 25px;
            color: white;
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 600;
            font-size: 18px;
        }
        .card-header-custom h4 i {
            margin-right: 10px;
        }
        .card-body-custom {
            padding: 30px;
        }
        /* Form Styles */
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
        }
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        /* Button Styles */
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            background: #5a6268;
        }
        /* Recent Prescriptions Card */
        .recent-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .recent-header {
            background: #f8f9fa;
            padding: 15px 25px;
            border-bottom: 2px solid #e9ecef;
        }
        .recent-header h5 {
            margin: 0;
            color: #333;
            font-weight: 600;
        }
        .prescription-table {
            margin-bottom: 0;
        }
        .prescription-table thead th {
            background: #f8f9fa;
            padding: 12px 15px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        .prescription-table tbody td {
            padding: 12px 15px;
            vertical-align: middle;
        }
        .medication-badge {
            background: #e9ecef;
            color: #495057;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .locked-option {
            background: #f8d7da;
            color: #721c24;
        }
        .info-banner {
            background: #e8f4f8;
            border-left: 4px solid #17a2b8;
            padding: 15px 20px;
            border-radius: 12px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .card-body-custom { padding: 20px; }
            .btn-submit, .btn-cancel { width: 100%; margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-prescription2"></i> Give Prescription</h1>
                    <p class="text-muted mb-0">Write and send prescription to patient</p>
                </div>
                <div>
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-person-heart"></i> Nurse: <?php echo htmlspecialchars($nurse['FirstName'] . ' ' . $nurse['LastName']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Main Form Card -->
        <div class="form-card">
            <div class="card-header-custom">
                <h4><i class="bi bi-pencil-square"></i> Prescription Form</h4>
            </div>
            <div class="card-body-custom">
                <form method="POST" onsubmit="return validateForm()">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-person"></i> Select Patient *
                            </label>
                            <select name="patient_id" id="patient_id" class="form-select" required>
                                <option value="">-- Choose Patient --</option>
                                <?php while($row = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $row['PatientID']; ?>" 
                                        <?php echo $row['has_doctor_prescription'] > 0 ? 'disabled class="locked-option"' : ''; ?>
                                        data-blood="<?php echo $row['BloodGroup']; ?>"
                                        data-contact="<?php echo $row['ContactNo']; ?>">
                                        <?php echo htmlspecialchars($row['Name']); ?>
                                        <?php if($row['has_doctor_prescription'] > 0): ?>
                                            🔒 (Locked - Has Doctor Prescriptions)
                                        <?php endif; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <div id="patientInfo" class="mt-2 small text-muted"></div>
                            <small class="text-danger">
                                <i class="bi bi-exclamation-triangle"></i> Patients with doctor prescriptions cannot receive nurse prescriptions
                            </small>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">
                                <i class="bi bi-capsule"></i> Medication Name *
                            </label>
                            <input type="text" name="medication" id="medication" class="form-control" 
                                   placeholder="e.g., Amoxicillin, Paracetamol" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="bi bi-eyedropper"></i> Dosage *
                            </label>
                            <input type="text" name="dosage" id="dosage" class="form-control" 
                                   placeholder="e.g., 500mg, 1 tablet" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="bi bi-clock"></i> Frequency *
                            </label>
                            <input type="text" name="frequency" id="frequency" class="form-control" 
                                   placeholder="e.g., Twice daily" required>
                        </div>
                        
                        <div class="col-md-4">
                            <label class="form-label">
                                <i class="bi bi-hourglass-split"></i> Duration *
                            </label>
                            <input type="text" name="duration" id="duration" class="form-control" 
                                   placeholder="e.g., 7 days" required>
                        </div>
                        
                        <div class="col-12">
                            <label class="form-label">
                                <i class="bi bi-chat-text"></i> Instructions
                            </label>
                            <textarea name="instructions" id="instructions" class="form-control" 
                                      placeholder="Take with food, Before bedtime, Store in cool place, etc." rows="3"></textarea>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex gap-3 justify-content-end">
                        <a href="nurse_dashboard.php" class="btn-cancel">
                            <i class="bi bi-x-circle"></i> Cancel
                        </a>
                        <button type="submit" name="give_prescription" class="btn-submit">
                            <i class="bi bi-send"></i> Give Prescription
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Recent Prescriptions Card -->
        <div class="recent-card">
            <div class="recent-header">
                <h5><i class="bi bi-clock-history"></i> Recently Given Prescriptions</h5>
            </div>
            <div class="table-responsive">
                <?php
                $recent = $conn->query("
                    SELECT p.*, CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName
                    FROM Prescription p
                    JOIN Patient pat ON p.PatientID = pat.PatientID
                    WHERE p.NurseID = $nurse_id
                    ORDER BY p.PrescriptionDate DESC
                    LIMIT 5
                ");
                ?>
                <?php if($recent && $recent->num_rows > 0): ?>
                    <table class="table prescription-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3"></i> Date</th>
                                <th><i class="bi bi-person"></i> Patient</th>
                                <th><i class="bi bi-capsule"></i> Medication</th>
                                <th><i class="bi bi-eyedropper"></i> Dosage</th>
                                <th><i class="bi bi-clock"></i> Frequency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $recent->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, H:i', strtotime($row['PrescriptionDate'])); ?></td>
                                <td><strong><?php echo htmlspecialchars($row['PatientName']); ?></strong></td>
                                <td><span class="medication-badge"><?php echo htmlspecialchars($row['MedicationName']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                                <td><?php echo htmlspecialchars($row['Frequency']); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-prescription2" style="font-size: 40px; color: #ccc;"></i>
                        <p class="text-muted mt-2 mb-0">No prescriptions given yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Info Banner -->
        <div class="info-banner">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3" style="color: #17a2b8;"></i>
             
            </div>
        </div>
    </div>

    <script>
        // Show patient info when selected
        document.getElementById('patient_id').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            var blood = selected.getAttribute('data-blood');
            var contact = selected.getAttribute('data-contact');
            var infoDiv = document.getElementById('patientInfo');
            
            if(this.value) {
                infoDiv.innerHTML = '<i class="bi bi-droplet"></i> Blood Group: ' + (blood || 'Unknown') + 
                                   ' | <i class="bi bi-telephone"></i> Contact: ' + (contact || 'N/A');
            } else {
                infoDiv.innerHTML = '';
            }
        });
        
        function validateForm() {
            var patientSelect = document.getElementById('patient_id');
            var selectedOption = patientSelect.options[patientSelect.selectedIndex];
            
            if(patientSelect.value == '') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Missing Information',
                    text: 'Please select a patient',
                    confirmButtonColor: '#ffc107'
                });
                return false;
            }
            
            // Check if selected patient is disabled (has doctor prescriptions)
            if(selectedOption.disabled) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Prescribe!',
                    text: 'This patient already has prescriptions from a Doctor. Nurses cannot prescribe for this patient.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }
            
            var medication = document.getElementById('medication').value;
            var dosage = document.getElementById('dosage').value;
            var frequency = document.getElementById('frequency').value;
            var duration = document.getElementById('duration').value;
            
            if(medication == '') {
                Swal.fire({ icon: 'warning', title: 'Missing Medication', text: 'Please enter medication name', confirmButtonColor: '#ffc107' });
                return false;
            }
            if(dosage == '') {
                Swal.fire({ icon: 'warning', title: 'Missing Dosage', text: 'Please enter dosage', confirmButtonColor: '#ffc107' });
                return false;
            }
            if(frequency == '') {
                Swal.fire({ icon: 'warning', title: 'Missing Frequency', text: 'Please enter frequency', confirmButtonColor: '#ffc107' });
                return false;
            }
            if(duration == '') {
                Swal.fire({ icon: 'warning', title: 'Missing Duration', text: 'Please enter duration', confirmButtonColor: '#ffc107' });
                return false;
            }
            
            return true;
        }
    </script>
</body>
</html>