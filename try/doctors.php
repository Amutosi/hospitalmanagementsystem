<?php
require_once 'db_config.php';

// Add doctor
if(isset($_POST['save'])) {
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $fee = $_POST['fee'];
    $qualification = $_POST['qualification'];
    $department = $_POST['department'];
    
    $sql = "INSERT INTO Doctor (FirstName, LastName, Specialization, ContactNo, Email, ConsultationFee, Qualification, DepartmentID, IsActive) 
            VALUES ('$fname', '$lname', '$specialization', '$contact', '$email', '$fee', '$qualification', '$department', 1)";
    
    if($conn->query($sql)) {
        echo "<script>alert('Doctor Added Successfully!'); window.location.href='?page=doctors';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Update doctor
if(isset($_POST['update'])) {
    $id = $_POST['id'];
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $specialization = $_POST['specialization'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $fee = $_POST['fee'];
    $qualification = $_POST['qualification'];
    $department = $_POST['department'];
    
    $sql = "UPDATE Doctor SET 
            FirstName='$fname', LastName='$lname', Specialization='$specialization', 
            ContactNo='$contact', Email='$email', ConsultationFee='$fee', 
            Qualification='$qualification', DepartmentID='$department' 
            WHERE DoctorID='$id'";
    
    if($conn->query($sql)) {
        echo "<script>alert('Doctor Updated Successfully!'); window.location.href='?page=doctors';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Delete doctor
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("UPDATE Doctor SET IsActive = 0 WHERE DoctorID = $id");
    echo "<script>alert('Doctor Deleted!'); window.location.href='?page=doctors';</script>";
}

// Get doctor for edit
$edit_doctor = null;
if(isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = $conn->query("SELECT * FROM Doctor WHERE DoctorID = $id");
    $edit_doctor = $result->fetch_assoc();
}

// Get departments for dropdown
$departments = $conn->query("SELECT DepartmentID, DeptName FROM Department WHERE 1=1");

// Get all doctors
$doctors = $conn->query("SELECT d.*, dept.DeptName FROM Doctor d LEFT JOIN Department dept ON d.DepartmentID = dept.DepartmentID WHERE d.IsActive = 1 ORDER BY d.DoctorID DESC");
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
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-info { background: #17a2b8; color: white; padding: 5px 10px; font-size: 12px; border: none; cursor: pointer; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
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
            <h2 style="margin: 0;">👨‍⚕️ Doctor Management</h2>
            <button class="btn btn-primary" onclick="toggleForm()">+ Add New Doctor</button>
        </div>

        <!-- Add/Edit Doctor Form -->
        <div id="addForm" class="form-box" style="display: <?php echo $edit_doctor ? 'block' : 'none'; ?>;">
            <h3><?php echo $edit_doctor ? '✏️ Edit Doctor' : '➕ Add New Doctor'; ?></h3>
            <form method="POST">
                <?php if($edit_doctor): ?>
                    <input type="hidden" name="id" value="<?php echo $edit_doctor['DoctorID']; ?>">
                <?php endif; ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="fname" class="form-control" value="<?php echo $edit_doctor['FirstName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="lname" class="form-control" value="<?php echo $edit_doctor['LastName'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Specialization *</label>
                        <input type="text" name="specialization" class="form-control" value="<?php echo $edit_doctor['Specialization'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" class="form-control" value="<?php echo $edit_doctor['ContactNo'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" value="<?php echo $edit_doctor['Email'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Consultation Fee ($) *</label>
                        <input type="number" step="0.01" name="fee" class="form-control" value="<?php echo $edit_doctor['ConsultationFee'] ?? ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Qualification</label>
                        <input type="text" name="qualification" class="form-control" value="<?php echo $edit_doctor['Qualification'] ?? ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <select name="department" class="form-control">
                            <option value="">Select Department</option>
                            <?php while($dept = $departments->fetch_assoc()): ?>
                                <option value="<?php echo $dept['DepartmentID']; ?>" <?php echo ($edit_doctor['DepartmentID'] ?? '') == $dept['DepartmentID'] ? 'selected' : ''; ?>>
                                    <?php echo $dept['DeptName']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    <button type="submit" name="<?php echo $edit_doctor ? 'update' : 'save'; ?>" class="btn btn-primary">
                        <?php echo $edit_doctor ? 'Update Doctor' : 'Save Doctor'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Doctors Table -->
        <div class="card">
            <div class="card-header">
                📋 Doctor List
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Specialization</th>
                                <th>Department</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Qualification</th>
                                <th>Fee</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($doctors && $doctors->num_rows > 0): ?>
                                <?php while($row = $doctors->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['DoctorID']; ?></td>
                                    <td><strong>Dr. <?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></strong></td>
                                    <td><?php echo $row['Specialization']; ?></td>
                                    <td><?php echo $row['DeptName'] ?? 'General'; ?></td>
                                    <td><?php echo $row['ContactNo']; ?></td>
                                    <td><?php echo $row['Email']; ?></td>
                                    <td><?php echo $row['Qualification'] ?: '-'; ?></td>
                                    <td>$<?php echo number_format($row['ConsultationFee'], 2); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?page=doctors&edit=<?php echo $row['DoctorID']; ?>" class="btn btn-warning" style="text-decoration: none;">Edit</a>
                                            <a href="?page=doctors&delete=<?php echo $row['DoctorID']; ?>" class="btn btn-danger" style="text-decoration: none;" onclick="return confirm('Delete this doctor?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        No doctors found. Click "Add New Doctor" to add one.
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
    </script>
</body>
</html>