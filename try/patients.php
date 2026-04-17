<?php
require_once 'db_config.php';

// Add patient with all fields
if(isset($_POST['save'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $email = $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    $blood = $_POST['blood'] ?? '';
    $emergency_name = $_POST['emergency_name'] ?? '';
    $emergency_contact = $_POST['emergency_contact'] ?? '';
    
    $sql = "INSERT INTO Patient (FirstName, LastName, DateOfBirth, Gender, ContactNo, Email, Address, BloodGroup, EmergencyContactName, EmergencyContactNo, RegistrationDate, IsActive) 
            VALUES ('$fname', '$lname', '$dob', '$gender', '$contact', '$email', '$address', '$blood', '$emergency_name', '$emergency_contact', CURDATE(), 1)";
    
    if($conn->query($sql)) {
        echo "<script>alert('Patient Added Successfully!'); window.location.href='?page=patients';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Update patient
if(isset($_POST['update'])) {
    $id = $_POST['id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $blood = $_POST['blood'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_contact = $_POST['emergency_contact'];
    
    $sql = "UPDATE Patient SET 
            FirstName='$fname', LastName='$lname', DateOfBirth='$dob', Gender='$gender', 
            ContactNo='$contact', Email='$email', Address='$address', BloodGroup='$blood', 
            EmergencyContactName='$emergency_name', EmergencyContactNo='$emergency_contact' 
            WHERE PatientID='$id'";
    
    if($conn->query($sql)) {
        echo "<script>alert('Patient Updated Successfully!'); window.location.href='?page=patients';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Delete patient
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("UPDATE Patient SET IsActive = 0 WHERE PatientID = $id");
    echo "<script>alert('Patient Deleted!'); window.location.href='?page=patients';</script>";
}

// Get patient for edit
$edit_patient = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM Patient WHERE PatientID = $id");
    $edit_patient = $result->fetch_assoc();
}

// Get all patients
$patients = $conn->query("SELECT * FROM Patient WHERE IsActive = 1 ORDER BY PatientID DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .container { padding: 20px; }
        .form-box { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .form-box h3 { margin-top: 0; margin-bottom: 20px; color: #2c3e50; }
        .form-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 13px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .full-width { grid-column: span 3; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-info { background: #17a2b8; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 16px; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .card-body { padding: 15px; overflow-x: auto; }
        .action-buttons { display: flex; gap: 5px; }
        @media (max-width: 768px) {
            .form-grid { grid-template-columns: 1fr; }
            .full-width { grid-column: span 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">🏥 Patient Management</h2>
            <button class="btn btn-primary" onclick="toggleForm()">+ Add New Patient</button>
        </div>

        <!-- Add/Edit Patient Form -->
        <div id="addForm" class="form-box" style="display: <?php echo $edit_patient ? 'block' : 'none'; ?>;">
            <h3><?php echo $edit_patient ? '✏️ Edit Patient' : '➕ Add New Patient'; ?></h3>
            <form method="POST">
                <?php if($edit_patient): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_patient['PatientID']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="fname" class="form-control" value="<?php echo $edit_patient['FirstName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="lname" class="form-control" value="<?php echo $edit_patient['LastName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" class="form-control" value="<?php echo $edit_patient['DateOfBirth'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male" <?php echo ($edit_patient['Gender'] ?? '') == 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($edit_patient['Gender'] ?? '') == 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($edit_patient['Gender'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" class="form-control" value="<?php echo $edit_patient['ContactNo'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $edit_patient['Email'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood" class="form-control">
                            <option value="">Select Blood Group</option>
                            <option value="A+" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'A+' ? 'selected' : ''; ?>>A+</option>
                            <option value="A-" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'A-' ? 'selected' : ''; ?>>A-</option>
                            <option value="B+" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'B+' ? 'selected' : ''; ?>>B+</option>
                            <option value="B-" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'B-' ? 'selected' : ''; ?>>B-</option>
                            <option value="O+" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'O+' ? 'selected' : ''; ?>>O+</option>
                            <option value="O-" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'O-' ? 'selected' : ''; ?>>O-</option>
                            <option value="AB+" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'AB+' ? 'selected' : ''; ?>>AB+</option>
                            <option value="AB-" <?php echo ($edit_patient['BloodGroup'] ?? '') == 'AB-' ? 'selected' : ''; ?>>AB-</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control" value="<?php echo $edit_patient['EmergencyContactName'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Number</label>
                        <input type="text" name="emergency_contact" class="form-control" value="<?php echo $edit_patient['EmergencyContactNo'] ?? ''; ?>">
                    </div>
                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="address" class="form-control" rows="2"><?php echo $edit_patient['Address'] ?? ''; ?></textarea>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_patient ? 'update' : 'save'; ?>" class="btn btn-primary">
                        <?php echo $edit_patient ? 'Update Patient' : 'Save Patient'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Patients Table -->
        <div class="card">
            <div class="card-header">
                📋 Patient List (Showing all fields)
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>DOB</th>
                                <th>Gender</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Blood Group</th>
                                <th>Emergency Contact</th>
                                <th>Address</th>
                                <th>Reg Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($patients && $patients->num_rows > 0): ?>
                                <?php while($row = $patients->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['PatientID']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></strong></td>
                                    <td><?php echo date('Y-m-d', strtotime($row['DateOfBirth'])); ?></td>
                                    <td><?php echo $row['Gender']; ?></td>
                                    <td><?php echo $row['ContactNo']; ?></td>
                                    <td><?php echo htmlspecialchars($row['Email'] ?: '-'); ?></td>
                                    <td><?php echo $row['BloodGroup'] ?: '-'; ?></td>
                                    <td>
                                        <?php if($row['EmergencyContactName'] || $row['EmergencyContactNo']): ?>
                                            <?php echo htmlspecialchars($row['EmergencyContactName'] ?: '-'); ?><br>
                                            <small><?php echo $row['EmergencyContactNo'] ?: '-'; ?></small>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($row['Address'] ?: '-', 0, 30)); ?></td>
                                    <td><?php echo $row['RegistrationDate']; ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=patients&edit=<?php echo $row['PatientID']; ?>" class="btn btn-warning" style="text-decoration: none;">Edit</a>
                                            <a href="?page=patients&delete=<?php echo $row['PatientID']; ?>" class="btn btn-danger" style="text-decoration: none;" onclick="return confirm('Delete this patient?')">Delete</a>
                                            <button class="btn btn-info" onclick="viewPatient(<?php echo $row['PatientID']; ?>)">View</button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px;">
                                        No patients found. Click "Add New Patient" to add one.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            var form = document.getElementById('addForm');
            if(form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
        
        function viewPatient(id) {
            window.open('view_patient.php?id=' + id, '_blank', 'width=900,height=700');
        }
    </script>
</body>
</html>