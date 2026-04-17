<?php
require_once 'db_config.php';

// Handle Add Treatment
if(isset($_POST['add_treatment'])) {
    $name = $_POST['name'];
    $duration = $_POST['duration'];
    $cost = $_POST['cost'];
    $description = $_POST['description'];
    
    $sql = "INSERT INTO Treatment (TreatmentName, StandardDurationDays, BaseCost, Description, IsActive) 
            VALUES ('$name', '$duration', '$cost', '$description', 1)";
    
    if($conn->query($sql)) {
        echo "<script>alert('Treatment added successfully!'); window.location.href='?page=treatments';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Update Treatment Status (Admin)
if(isset($_GET['update_status']) && isset($_GET['status'])) {
    $id = $_GET['update_status'];
    $status = $_GET['status'];
    
    $sql = "UPDATE PatientTreatment SET Status = '$status' WHERE PatientTreatmentID = $id";
    
    if($conn->query($sql)) {
        echo "<script>alert('Treatment status updated to $status!'); window.location.href='?page=treatments';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Assign Treatment to Patient
if(isset($_POST['assign_treatment'])) {
    $patient_id = $_POST['patient_id'];
    $treatment_id = $_POST['treatment_id'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'] ?: NULL;
    $sequence = $_POST['sequence'];
    $status = $_POST['status'];
    $prescribed_by = isset($_POST['prescribed_by']) ? $_POST['prescribed_by'] : 'Admin';
    
    $sql = "INSERT INTO PatientTreatment (PatientID, TreatmentID, StartDate, EndDate, SequenceOrder, Status, Notes) 
            VALUES ('$patient_id', '$treatment_id', '$start_date', " . ($end_date ? "'$end_date'" : "NULL") . ", '$sequence', '$status', 'Assigned by $prescribed_by')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Treatment assigned to patient successfully!'); window.location.href='?page=treatments';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Delete Treatment
if(isset($_GET['delete_treatment'])) {
    $id = $_GET['delete_treatment'];
    $sql = "UPDATE Treatment SET IsActive = 0 WHERE TreatmentID = $id";
    $conn->query($sql);
    echo "<script>alert('Treatment deleted!'); window.location.href='?page=treatments';</script>";
}

// Handle Remove Patient Treatment
if(isset($_GET['remove_patient_treatment'])) {
    $id = $_GET['remove_patient_treatment'];
    $conn->query("DELETE FROM PatientTreatment WHERE PatientTreatmentID = $id");
    echo "<script>alert('Treatment removed from patient!'); window.location.href='?page=treatments';</script>";
}

// Get all treatments
$treatments = $conn->query("SELECT * FROM Treatment WHERE IsActive = 1 ORDER BY TreatmentID DESC");

// Get patients for dropdown
$patients = $conn->query("SELECT PatientID, CONCAT(FirstName, ' ', LastName) as Name FROM Patient WHERE IsActive = 1");

// Get patient treatments (with doctor info)
$patientTreatments = $conn->query("
    SELECT pt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           t.TreatmentName,
           t.BaseCost,
           t.StandardDurationDays,
           CASE 
               WHEN pt.PrescribedByDoctor IS NOT NULL THEN CONCAT('Dr. ', d.FirstName, ' ', d.LastName)
               WHEN pt.PrescribedByNurse IS NOT NULL THEN CONCAT('Nurse ', n.FirstName, ' ', n.LastName)
               ELSE 'Admin'
           END as PrescribedBy,
           lt.TestName as RelatedLabTest
    FROM PatientTreatment pt
    JOIN Patient p ON pt.PatientID = p.PatientID
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    LEFT JOIN Doctor d ON pt.PrescribedByDoctor = d.DoctorID
    LEFT JOIN Nurse n ON pt.PrescribedByNurse = n.NurseID
    LEFT JOIN PatientLabTest plt ON pt.LabTestID = plt.PatientLabTestID
    LEFT JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    ORDER BY pt.StartDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Treatments Management</title>
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
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; }
        .btn-info { background: #17a2b8; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .status-scheduled { background: #ffc107; color: #000; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-ongoing { background: #17a2b8; color: white; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-completed { background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-cancelled { background: #dc3545; color: white; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .doctor-badge { background: #1e3c72; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; display: inline-block; }
        .nurse-badge { background: #28a745; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; display: inline-block; }
        .admin-badge { background: #6c757d; color: white; padding: 2px 8px; border-radius: 4px; font-size: 10px; display: inline-block; }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💊 Treatments Management</h2>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('treatmentsList')">📋 Treatments List</button>
            <button class="nav-tab" onclick="showTab('patientTreatments')">👨‍⚕️ All Patient Treatments</button>
            <button class="nav-tab" onclick="showTab('assignTreatment')">➕ Assign Treatment</button>
        </div>
        
        <!-- Tab 1: Treatments List -->
        <div id="treatmentsList" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    📋 Available Treatments
                    <button class="btn-primary" style="float: right; padding: 5px 15px;" onclick="showAddTreatmentForm()">+ Add Treatment</button>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Treatment Name</th><th>Duration (Days)</th><th>Cost ($)</th>
                                    <th>Description</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($treatments && $treatments->num_rows > 0): ?>
                                    <?php while($row = $treatments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['TreatmentID']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['TreatmentName']); ?></strong></td>
                                        <td><?php echo $row['StandardDurationDays']; ?> days</td>
                                        <td>$<?php echo number_format($row['BaseCost'], 2); ?></td>
                                        <td><?php echo htmlspecialchars(substr($row['Description'], 0, 50)) . (strlen($row['Description']) > 50 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="?page=treatments&delete_treatment=<?php echo $row['TreatmentID']; ?>" class="btn-danger" style="padding: 5px 10px; text-decoration: none;" onclick="return confirm('Delete this treatment?')">Delete</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="6" style="text-align: center;">No treatments found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: All Patient Treatments -->
        <div id="patientTreatments" class="tab-content">
            <div class="card">
                <div class="card-header">
                    👨‍⚕️ All Patient Treatment Plans
                    <button class="btn btn-sm btn-primary float-end" onclick="location.reload()">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Patient</th><th>Treatment</th><th>Stage</th>
                                    <th>Start Date</th><th>End Date</th><th>Status</th>
                                    <th>Cost</th><th>Prescribed By</th><th>Related Lab</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($patientTreatments && $patientTreatments->num_rows > 0): ?>
                                    <?php while($row = $patientTreatments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['PatientTreatmentID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                                        <td><?php echo htmlspecialchars($row['TreatmentName']); ?></td>
                                        <td>Stage <?php echo $row['SequenceOrder']; ?></td>
                                        <td><?php echo $row['StartDate']; ?></td>
                                        <td><?php echo $row['EndDate'] ?: 'Ongoing'; ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($row['Status']); ?>">
                                                <?php echo $row['Status']; ?>
                                            </span>
                                        </td>
                                        <td>$<?php echo number_format($row['BaseCost'], 2); ?></td>
                                        <td>
                                            <?php if(strpos($row['PrescribedBy'], 'Dr.') !== false): ?>
                                                <span class="doctor-badge">👨‍⚕️ <?php echo $row['PrescribedBy']; ?></span>
                                            <?php elseif(strpos($row['PrescribedBy'], 'Nurse') !== false): ?>
                                                <span class="nurse-badge">👩‍⚕️ <?php echo $row['PrescribedBy']; ?></span>
                                            <?php else: ?>
                                                <span class="admin-badge">👑 <?php echo $row['PrescribedBy']; ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $row['RelatedLabTest'] ?: '-'; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <?php if($row['Status'] == 'Scheduled'): ?>
                                                    <a href="?page=treatments&update_status=<?php echo $row['PatientTreatmentID']; ?>&status=Ongoing" class="btn-info" style="padding: 5px 10px; text-decoration: none;">Start</a>
                                                <?php endif; ?>
                                                <?php if($row['Status'] == 'Ongoing'): ?>
                                                    <a href="?page=treatments&update_status=<?php echo $row['PatientTreatmentID']; ?>&status=Completed" class="btn-success" style="padding: 5px 10px; text-decoration: none;">Complete</a>
                                                <?php endif; ?>
                                                <?php if($row['Status'] == 'Scheduled' || $row['Status'] == 'Ongoing'): ?>
                                                    <a href="?page=treatments&update_status=<?php echo $row['PatientTreatmentID']; ?>&status=Cancelled" class="btn-danger" style="padding: 5px 10px; text-decoration: none;" onclick="return confirm('Cancel this treatment?')">Cancel</a>
                                                <?php endif; ?>
                                                <a href="?page=treatments&remove_patient_treatment=<?php echo $row['PatientTreatmentID']; ?>" class="btn-danger" style="padding: 5px 10px; text-decoration: none;" onclick="return confirm('Remove this treatment?')">Remove</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="11" style="text-align: center;">No patient treatments found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </tr>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 3: Assign Treatment -->
        <div id="assignTreatment" class="tab-content">
            <div class="card">
                <div class="card-header">➕ Assign Treatment to Patient</div>
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
                                <label>Select Treatment *</label>
                                <select name="treatment_id" class="form-control" required>
                                    <option value="">Choose Treatment</option>
                                    <?php 
                                    $treatments->data_seek(0);
                                    while($row = $treatments->fetch_assoc()): ?>
                                        <option value="<?php echo $row['TreatmentID']; ?>"><?php echo $row['TreatmentName']; ?> - $<?php echo number_format($row['BaseCost'], 2); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Stage/Sequence Number *</label>
                                <input type="number" name="sequence" class="form-control" placeholder="e.g., 1, 2, 3" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Start Date *</label>
                                <input type="date" name="start_date" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>End Date (Optional)</label>
                                <input type="date" name="end_date" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Status *</label>
                                <select name="status" class="form-control" required>
                                    <option value="Scheduled">Scheduled</option>
                                    <option value="Ongoing">Ongoing</option>
                                    <option value="Completed">Completed</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Prescribed By</label>
                                <select name="prescribed_by" class="form-control">
                                    <option value="Admin">Admin</option>
                                    <option value="Doctor">Doctor</option>
                                    <option value="Nurse">Nurse</option>
                                </select>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="assign_treatment" class="btn-primary">Assign Treatment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Treatment Modal -->
    <div id="addTreatmentModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: white; margin: 10% auto; padding: 20px; width: 50%; max-width: 500px; border-radius: 10px;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3>Add New Treatment</h3>
                <span class="close" onclick="closeAddTreatmentForm()" style="cursor: pointer; font-size: 24px;">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Treatment Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Duration (Days) *</label>
                    <input type="number" name="duration" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Base Cost ($) *</label>
                    <input type="number" step="0.01" name="cost" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn-secondary" onclick="closeAddTreatmentForm()">Cancel</button>
                    <button type="submit" name="add_treatment" class="btn-primary">Add Treatment</button>
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
        
        function showAddTreatmentForm() {
            document.getElementById('addTreatmentModal').style.display = 'block';
        }
        
        function closeAddTreatmentForm() {
            document.getElementById('addTreatmentModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            let modal = document.getElementById('addTreatmentModal');
            if(event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>