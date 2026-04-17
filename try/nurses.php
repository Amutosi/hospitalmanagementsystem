<?php
require_once 'db_config.php';

// Handle Add Prescription by Nurse
if(isset($_POST['add_prescription'])) {
    $patient_id = $_POST['patient_id'];
    $nurse_id = $_POST['nurse_id'];
    $appointment_id = !empty($_POST['appointment_id']) ? $_POST['appointment_id'] : 'NULL';
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    
    // Handle NULL values properly
    $appointment_value = ($appointment_id != 'NULL') ? "'$appointment_id'" : "NULL";
    
    // Set DoctorID as NULL since Nurse is giving prescription
    $doctor_value = "NULL";
    
    $sql = "INSERT INTO Prescription (PatientID, DoctorID, NurseID, AppointmentID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions) 
            VALUES ('$patient_id', $doctor_value, '$nurse_id', $appointment_value, NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Prescription added successfully by Nurse!'); window.location.href='?page=nurses';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Add Nurse
if(isset($_POST['add_nurse'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $qualification = $_POST['qualification'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $department = $_POST['department'] ?: 'NULL';
    $shift = $_POST['shift'];
    
    $sql = "INSERT INTO Nurse (FirstName, LastName, Qualification, ContactNo, Email, DepartmentID, ShiftPreference, IsActive) 
            VALUES ('$fname', '$lname', '$qualification', '$contact', '$email', " . ($department ? "'$department'" : "NULL") . ", '$shift', 1)";
    
    if($conn->query($sql)) {
        echo "<script>alert('Nurse added successfully!'); window.location.href='?page=nurses';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Update Nurse
if(isset($_POST['update_nurse'])) {
    $id = $_POST['id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $qualification = $_POST['qualification'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $department = $_POST['department'] ?: 'NULL';
    $shift = $_POST['shift'];
    
    $sql = "UPDATE Nurse SET 
            FirstName='$fname', LastName='$lname', Qualification='$qualification', 
            ContactNo='$contact', Email='$email', DepartmentID=" . ($department ? "'$department'" : "NULL") . ", 
            ShiftPreference='$shift' 
            WHERE NurseID='$id'";
    
    if($conn->query($sql)) {
        echo "<script>alert('Nurse updated successfully!'); window.location.href='?page=nurses';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Delete Nurse
if(isset($_GET['delete_nurse'])) {
    $id = $_GET['delete_nurse'];
    $conn->query("UPDATE Nurse SET IsActive = 0 WHERE NurseID = $id");
    echo "<script>alert('Nurse deleted!'); window.location.href='?page=nurses';</script>";
}

// Get nurse for edit
$edit_nurse = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM Nurse WHERE NurseID = $id");
    $edit_nurse = $result->fetch_assoc();
}

// Get departments for dropdown
$departments = $conn->query("SELECT DepartmentID, DeptName FROM Department WHERE 1=1");

// Get all nurses
$nurses = $conn->query("
    SELECT n.*, d.DeptName 
    FROM Nurse n
    LEFT JOIN Department d ON n.DepartmentID = d.DepartmentID 
    WHERE n.IsActive = 1 
    ORDER BY n.NurseID DESC
");

// Get patients for prescription dropdown
$patients = $conn->query("SELECT PatientID, CONCAT(FirstName, ' ', LastName) as Name FROM Patient WHERE IsActive = 1");

// Get appointments for prescription dropdown
$appointments = $conn->query("
    SELECT a.AppointmentID, CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM Appointment a
    JOIN Patient p ON a.PatientID = p.PatientID
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.Status = 'Completed'
    ORDER BY a.AppointmentDateTime DESC
");

// Get completed lab tests for prescription reference
$completedLabTests = $conn->query("
    SELECT plt.PatientLabTestID, plt.PatientID, lt.TestName, plt.ResultValue,
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    WHERE plt.Status = 'Completed'
    ORDER BY plt.ResultDate DESC
    LIMIT 20
");

// Get prescriptions added by nurses
$prescriptions = $conn->query("
    SELECT p.*, 
           CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName,
           CONCAT(n.FirstName, ' ', n.LastName) as NurseName,
           n.Qualification as NurseQualification
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    LEFT JOIN Nurse n ON p.NurseID = n.NurseID
    ORDER BY p.PrescriptionDate DESC
    LIMIT 50
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Nurses Management & Prescriptions</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; flex-wrap: wrap; }
        .nav-tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; border: none; border-radius: 5px 5px 0 0; }
        .nav-tab.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .lab-result-card {
            background: #e8f4f8;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            cursor: pointer;
        }
        .lab-result-card:hover {
            background: #d1ecf1;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80%;
            overflow-y: auto;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .close { cursor: pointer; font-size: 24px; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>👩‍⚕️ Nurses Management & Prescriptions</h2>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('nursesList')">📋 Nurses List</button>
            <button class="nav-tab" onclick="showTab('addPrescription')">💊 Give Prescription</button>
            <button class="nav-tab" onclick="showTab('prescriptionsList')">📝 Prescriptions Given</button>
            <button class="nav-tab" onclick="showTab('labResults')">🔬 Lab Results</button>
        </div>
        
        <!-- Tab 1: Nurses List -->
        <div id="nursesList" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    📋 Nurses List
                    <button class="btn-primary" style="float: right; padding: 5px 15px;" onclick="showAddNurseForm()">+ Add New Nurse</button>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Name</th><th>Qualification</th><th>Department</th>
                                    <th>Contact</th><th>Email</th><th>Shift</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($nurses && $nurses->num_rows > 0): ?>
                                    <?php while($row = $nurses->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['NurseID']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['Qualification']); ?></td>
                                        <td><?php echo $row['DeptName'] ?? 'General'; ?></td>
                                        <td><?php echo $row['ContactNo']; ?></td>
                                        <td><?php echo $row['Email']; ?></td>
                                        <td><?php echo $row['ShiftPreference'] ?: 'Rotating'; ?></td>
                                        <td>
                                            <a href="?page=nurses&edit=<?php echo $row['NurseID']; ?>" class="btn-warning" style="padding: 5px 10px; text-decoration: none;">Edit</a>
                                            <a href="?page=nurses&delete_nurse=<?php echo $row['NurseID']; ?>" class="btn-danger" style="padding: 5px 10px; text-decoration: none;" onclick="return confirm('Delete this nurse?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" style="text-align: center;">No nurses found</td></tr>
                                <?php endif; ?>
                            </tbody>
                         </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Give Prescription -->
        <div id="addPrescription" class="tab-content">
            <div class="card">
                <div class="card-header">💊 Give Prescription to Patient</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Patient *</label>
                                <select name="patient_id" class="form-control" required>
                                    <option value="">Choose Patient</option>
                                    <?php 
                                    $patients->data_seek(0);
                                    while($row = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $row['PatientID']; ?>"><?php echo $row['Name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nurse Giving Prescription *</label>
                                <select name="nurse_id" class="form-control" required>
                                    <option value="">Choose Nurse</option>
                                    <?php 
                                    $nurses->data_seek(0);
                                    while($row = $nurses->fetch_assoc()): ?>
                                        <option value="<?php echo $row['NurseID']; ?>"><?php echo $row['FirstName'] . ' ' . $row['LastName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Associated Appointment (Optional)</label>
                                <select name="appointment_id" class="form-control">
                                    <option value="">Select Appointment</option>
                                    <?php 
                                    $appointments->data_seek(0);
                                    while($row = $appointments->fetch_assoc()): ?>
                                        <option value="<?php echo $row['AppointmentID']; ?>"><?php echo $row['PatientName']; ?> - Dr. <?php echo $row['DoctorName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Medication Name *</label>
                                <input type="text" name="medication" class="form-control" placeholder="e.g., Amoxicillin, Paracetamol" required>
                            </div>
                            <div class="form-group">
                                <label>Dosage *</label>
                                <input type="text" name="dosage" class="form-control" placeholder="e.g., 500mg, 1 tablet" required>
                            </div>
                            <div class="form-group">
                                <label>Frequency *</label>
                                <input type="text" name="frequency" class="form-control" placeholder="e.g., Twice daily, Every 8 hours" required>
                            </div>
                            <div class="form-group">
                                <label>Duration *</label>
                                <input type="text" name="duration" class="form-control" placeholder="e.g., 7 days, 2 weeks" required>
                            </div>
                            <div class="form-group full-width">
                                <label>Instructions</label>
                                <textarea name="instructions" class="form-control" rows="3" placeholder="Take with food, Before bedtime, etc."></textarea>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="add_prescription" class="btn-primary">Give Prescription</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab 3: Prescriptions Given -->
        <div id="prescriptionsList" class="tab-content">
            <div class="card">
                <div class="card-header">📝 Prescriptions Given by Nurses</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Patient</th><th>Given By (Nurse)</th><th>Date</th>
                                    <th>Medication</th><th>Dosage</th><th>Frequency</th>
                                    <th>Duration</th><th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($prescriptions && $prescriptions->num_rows > 0): ?>
                                    <?php while($row = $prescriptions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['PrescriptionID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                                        <td>👩‍⚕️ <?php echo htmlspecialchars($row['NurseName'] ?: 'Nurse'); ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($row['PrescriptionDate'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['MedicationName']); ?></td>
                                        <td><?php echo $row['Dosage']; ?></td>
                                        <td><?php echo $row['Frequency']; ?></td>
                                        <td><?php echo $row['Duration']; ?></td>
                                        <td><?php echo htmlspecialchars(substr($row['Instructions'], 0, 30)) . (strlen($row['Instructions']) > 30 ? '...' : ''); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" style="text-align: center;">No prescriptions given yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 4: Lab Results -->
        <div id="labResults" class="tab-content">
            <div class="card">
                <div class="card-header">🔬 Lab Results - Reference for Prescriptions</div>
                <div class="card-body">
                    <div class="form-group">
                        <label>Filter by Patient:</label>
                        <select id="filterPatient" class="form-control" onchange="filterLabResults()">
                            <option value="">All Patients</option>
                            <?php 
                            $patients->data_seek(0);
                            while($row = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $row['PatientID']; ?>"><?php echo $row['Name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div id="labResultsList">
                        <?php if($completedLabTests && $completedLabTests->num_rows > 0): ?>
                            <?php while($row = $completedLabTests->fetch_assoc()): ?>
                                <div class="lab-result-card" data-patient="<?php echo $row['PatientID']; ?>">
                                    <strong>Patient:</strong> <?php echo htmlspecialchars($row['PatientName']); ?><br>
                                    <strong>Test:</strong> <?php echo htmlspecialchars($row['TestName']); ?><br>
                                    <strong>Result:</strong> <?php echo htmlspecialchars($row['ResultValue']); ?><br>
                                    <small>Use this information to prescribe medication</small>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No completed lab tests found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Nurse Modal -->
    <div id="nurseModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle"><?php echo $edit_nurse ? 'Edit Nurse' : 'Add New Nurse'; ?></h3>
                <span class="close" onclick="closeNurseModal()">&times;</span>
            </div>
            <form method="POST">
                <?php if($edit_nurse): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_nurse['NurseID']; ?>">
                <?php endif; ?>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="fname" class="form-control" value="<?php echo $edit_nurse['FirstName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="lname" class="form-control" value="<?php echo $edit_nurse['LastName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Qualification *</label>
                        <input type="text" name="qualification" class="form-control" value="<?php echo $edit_nurse['Qualification'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-control">
                            <option value="">Select Department</option>
                            <?php 
                            $departments->data_seek(0);
                            while($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['DepartmentID']; ?>" <?php echo ($edit_nurse['DepartmentID'] ?? '') == $dept['DepartmentID'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['DeptName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" class="form-control" value="<?php echo $edit_nurse['ContactNo'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $edit_nurse['Email'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Shift Preference</label>
                        <select name="shift" class="form-control">
                            <option value="Morning" <?php echo ($edit_nurse['ShiftPreference'] ?? '') == 'Morning' ? 'selected' : ''; ?>>Morning</option>
                            <option value="Evening" <?php echo ($edit_nurse['ShiftPreference'] ?? '') == 'Evening' ? 'selected' : ''; ?>>Evening</option>
                            <option value="Night" <?php echo ($edit_nurse['ShiftPreference'] ?? '') == 'Night' ? 'selected' : ''; ?>>Night</option>
                            <option value="Rotating" <?php echo ($edit_nurse['ShiftPreference'] ?? '') == 'Rotating' ? 'selected' : ''; ?>>Rotating</option>
                        </select>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closeNurseModal()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_nurse ? 'update_nurse' : 'add_nurse'; ?>" class="btn-primary">
                        <?php echo $edit_nurse ? 'Update Nurse' : 'Save Nurse'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function showAddNurseForm() {
            document.getElementById('nurseModal').style.display = 'block';
        }
        
        function closeNurseModal() {
            document.getElementById('nurseModal').style.display = 'none';
        }
        
        function filterLabResults() {
            const filter = document.getElementById('filterPatient').value;
            const cards = document.querySelectorAll('.lab-result-card');
            
            cards.forEach(card => {
                const patientId = card.getAttribute('data-patient');
                if(filter === '' || filter === patientId) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
        
        <?php if($edit_nurse): ?>
        document.getElementById('nurseModal').style.display = 'block';
        <?php endif; ?>
        
        window.onclick = function(event) {
            let modal = document.getElementById('nurseModal');
            if(event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>