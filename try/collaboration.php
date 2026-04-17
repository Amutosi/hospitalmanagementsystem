<?php
require_once 'db_config.php';

// Handle Add Collaboration
if(isset($_POST['add_collaboration'])) {
    $patient_treatment_id = $_POST['patient_treatment_id'];
    $lead_department = $_POST['lead_department'];
    $supporting_department = $_POST['supporting_department'];
    $start_date = $_POST['start_date'];
    $end_date = !empty($_POST['end_date']) ? "'{$_POST['end_date']}'" : "NULL";
    $role_description = $_POST['role_description'];
    
    $sql = "INSERT INTO DepartmentCollaboration (PatientTreatmentID, LeadDepartmentID, SupportingDepartmentID, CollaborationStartDate, CollaborationEndDate, RoleDescription) 
            VALUES ('$patient_treatment_id', '$lead_department', '$supporting_department', '$start_date', $end_date, '$role_description')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Collaboration added!'); window.location.href='?page=collaboration';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Delete
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM DepartmentCollaboration WHERE CollaborationID = $id");
    echo "<script>alert('Collaboration removed!'); window.location.href='?page=collaboration';</script>";
}

// Get data for dropdowns
$patientTreatments = $conn->query("
    SELECT pt.PatientTreatmentID, 
           CONCAT(p.FirstName, ' ', p.LastName, ' - ', t.TreatmentName, ' (Stage ', pt.SequenceOrder, ')') as Name
    FROM PatientTreatment pt
    JOIN Patient p ON pt.PatientID = p.PatientID
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    WHERE pt.Status IN ('Scheduled', 'Ongoing')
");

$departments = $conn->query("SELECT DepartmentID, DeptName FROM Department");

// Get all collaborations
$collaborations = $conn->query("
    SELECT c.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           t.TreatmentName,
           pt.SequenceOrder,
           ld.DeptName as LeadDept,
           sd.DeptName as SupportDept
    FROM DepartmentCollaboration c
    JOIN PatientTreatment pt ON c.PatientTreatmentID = pt.PatientTreatmentID
    JOIN Patient p ON pt.PatientID = p.PatientID
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    JOIN Department ld ON c.LeadDepartmentID = ld.DepartmentID
    JOIN Department sd ON c.SupportingDepartmentID = sd.DepartmentID
    ORDER BY c.CollaborationStartDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Department Collaboration</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; text-decoration: none; display: inline-block; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .lead { background: #d4edda; color: #155724; padding: 2px 8px; border-radius: 4px; display: inline-block; font-size: 12px; }
        .support { background: #d1ecf1; color: #0c5460; padding: 2px 8px; border-radius: 4px; display: inline-block; font-size: 12px; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="container">
        <h2>🤝 Department Collaboration</h2>
        
        <!-- Add Form -->
        <div class="card">
            <div class="card-header">➕ Add Department Collaboration</div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Patient Treatment *</label>
                            <select name="patient_treatment_id" class="form-control" required>
                                <option value="">Select Treatment</option>
                                <?php while($row = $patientTreatments->fetch_assoc()): ?>
                                    <option value="<?php echo $row['PatientTreatmentID']; ?>"><?php echo $row['Name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Lead Department *</label>
                            <select name="lead_department" class="form-control" required>
                                <option value="">Select Lead Dept</option>
                                <?php 
                                $departments->data_seek(0);
                                while($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo $dept['DeptName']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Supporting Department *</label>
                            <select name="supporting_department" class="form-control" required>
                                <option value="">Select Support Dept</option>
                                <?php 
                                $departments->data_seek(0);
                                while($dept = $departments->fetch_assoc()): ?>
                                    <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo $dept['DeptName']; ?></option>
                                <?php endwhile; ?>
                            </select>
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
                            <label>Role Description</label>
                            <textarea name="role_description" class="form-control" rows="2" placeholder="Describe department roles..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_collaboration" class="btn-primary">Add Collaboration</button>
                </form>
            </div>
        </div>
        
        <!-- Collaborations List -->
        <div class="card">
            <div class="card-header">📋 Active Collaborations</div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>Patient</th><th>Treatment</th><th>Stage</th>
                                <th>Lead Dept</th><th>Support Dept</th>
                                <th>Start Date</th><th>End Date</th><th>Role</th><th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($collaborations && $collaborations->num_rows > 0): ?>
                                <?php while($row = $collaborations->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['PatientName']; ?></td>
                                    <td><?php echo $row['TreatmentName']; ?></td>
                                    <td>Stage <?php echo $row['SequenceOrder']; ?></td>
                                    <td><span class="lead">🏥 <?php echo $row['LeadDept']; ?></span></td>
                                    <td><span class="support">🤝 <?php echo $row['SupportDept']; ?></span></td>
                                    <td><?php echo $row['CollaborationStartDate']; ?></td>
                                    <td><?php echo $row['CollaborationEndDate'] ?: 'Ongoing'; ?></td>
                                    <td><?php echo substr($row['RoleDescription'] ?? '', 0, 40); ?></td>
                                    <td><a href="?page=collaboration&delete=<?php echo $row['CollaborationID']; ?>" class="btn-danger" onclick="return confirm('Remove?')">Remove</a></td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="9" style="text-align: center;">No collaborations found</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>