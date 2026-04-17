<?php
require_once 'db_config.php';

// Get patients and doctors for dropdown
$patients = $conn->query("SELECT PatientID, CONCAT(FirstName,' ',LastName) as Name FROM Patient WHERE IsActive=1");
$doctors = $conn->query("SELECT DoctorID, CONCAT(FirstName,' ',LastName) as Name, ConsultationFee FROM Doctor WHERE IsActive=1");

// Add appointment
if(isset($_POST['save'])) {
    $patient_id = $_POST['patient_id'];
    $doctor_id = $_POST['doctor_id'];
    $datetime = $_POST['datetime'];
    $duration = $_POST['duration'];
    $purpose = $_POST['purpose'];
    
    $sql = "INSERT INTO Appointment (PatientID, DoctorID, AppointmentDateTime, DurationMinutes, Purpose, Status) 
            VALUES ('$patient_id', '$doctor_id', '$datetime', '$duration', '$purpose', 'Scheduled')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Appointment Booked Successfully!'); window.location.href='?page=appointments';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Complete appointment and generate bill
if(isset($_GET['complete'])) {
    $id = $_GET['complete'];
    
    // Get appointment details
    $appt = $conn->query("SELECT a.*, d.ConsultationFee, d.FirstName as DoctorFName, d.LastName as DoctorLName, 
                          p.FirstName as PatientFName, p.LastName as PatientLName 
                          FROM Appointment a 
                          JOIN Doctor d ON a.DoctorID = d.DoctorID 
                          JOIN Patient p ON a.PatientID = p.PatientID 
                          WHERE a.AppointmentID = $id");
    $appointment = $appt->fetch_assoc();
    
    if($appointment) {
        // Update appointment status
        $conn->query("UPDATE Appointment SET Status = 'Completed' WHERE AppointmentID = $id");
        
        // Generate bill number
        $billNumber = 'BILL' . date('Ymd') . rand(1000, 9999);
        
        // Calculate amounts
        $subtotal = $appointment['ConsultationFee'];
        $tax = $subtotal * 0.10; // 10% tax
        $total = $subtotal + $tax;
        
        // Insert into billing
        $bill_sql = "INSERT INTO Billing (PatientID, BillNumber, BillDate, DueDate, Subtotal, TaxAmount, TotalAmount, AmountPaid, PaymentStatus) 
                     VALUES ('{$appointment['PatientID']}', '$billNumber', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), '$subtotal', '$tax', '$total', 0, 'Pending')";
        
        if($conn->query($bill_sql)) {
            $bill_id = $conn->insert_id;
            
            // Add bill line item
            $line_sql = "INSERT INTO BillLineItem (BillID, ServiceType, ServiceID, Description, Quantity, UnitPrice, LineTotal) 
                         VALUES ('$bill_id', 'Appointment', '$id', 'Doctor Consultation - Dr. {$appointment['DoctorFName']} {$appointment['DoctorLName']}', 1, '$subtotal', '$subtotal')";
            $conn->query($line_sql);
            
            echo "<script>alert('Appointment Completed! Bill #$billNumber generated for $$total'); window.location.href='?page=appointments';</script>";
        } else {
            echo "<script>alert('Appointment completed but bill generation failed: " . $conn->error . "'); window.location.href='?page=appointments';</script>";
        }
    } else {
        echo "<script>alert('Error finding appointment details'); window.location.href='?page=appointments';</script>";
    }
}

// Cancel appointment
if(isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $conn->query("UPDATE Appointment SET Status = 'Cancelled' WHERE AppointmentID = $id");
    echo "<script>alert('Appointment Cancelled!'); window.location.href='?page=appointments';</script>";
}

// Delete appointment
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM Appointment WHERE AppointmentID = $id");
    echo "<script>alert('Appointment Deleted!'); window.location.href='?page=appointments';</script>";
}

// Get all appointments
$appointments = $conn->query("
    SELECT a.*, 
           CONCAT(p.FirstName,' ',p.LastName) as PatientName,
           CONCAT(d.FirstName,' ',d.LastName) as DoctorName,
           d.ConsultationFee
    FROM Appointment a
    LEFT JOIN Patient p ON a.PatientID = p.PatientID
    LEFT JOIN Doctor d ON a.DoctorID = d.DoctorID
    ORDER BY a.AppointmentDateTime DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .container { padding: 20px; }
        .form-box { background: white; padding: 20px; margin-bottom: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: none; }
        .form-box h3 { margin-top: 0; margin-bottom: 20px; color: #2c3e50; }
        .form-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .form-group { margin-bottom: 5px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #555; font-size: 13px; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .full-width { grid-column: span 2; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-warning { background: #ffc107; color: #000; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-info { background: #17a2b8; color: white; padding: 5px 10px; font-size: 12px; text-decoration: none; display: inline-block; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; font-weight: bold; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 16px; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .card-body { padding: 15px; overflow-x: auto; }
        .status-scheduled { background: #ffc107; color: #000; padding: 3px 8px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .status-completed { background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .status-cancelled { background: #dc3545; color: white; padding: 3px 8px; border-radius: 3px; font-size: 12px; display: inline-block; }
        .action-buttons { display: flex; gap: 5px; flex-wrap: wrap; }
        .fee { color: #28a745; font-weight: bold; }
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
            <h2 style="margin: 0;">📅 Appointment Management</h2>
            <button class="btn btn-primary" onclick="toggleForm()">+ Book New Appointment</button>
        </div>

        <!-- Book Appointment Form -->
        <div id="addForm" class="form-box">
            <h3>📝 Book New Appointment</h3>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Select Patient *</label>
                        <select name="patient_id" class="form-control" required>
                            <option value="">-- Select Patient --</option>
                            <?php 
                            $patients->data_seek(0);
                            while($row = $patients->fetch_assoc()): ?>
                                <option value="<?php echo $row['PatientID']; ?>"><?php echo htmlspecialchars($row['Name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Doctor *</label>
                        <select name="doctor_id" class="form-control" required>
                            <option value="">-- Select Doctor --</option>
                            <?php 
                            $doctors->data_seek(0);
                            while($row = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $row['DoctorID']; ?>"><?php echo htmlspecialchars($row['Name']); ?> - $<?php echo $row['ConsultationFee']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date & Time *</label>
                        <input type="datetime-local" name="datetime" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Duration (minutes) *</label>
                        <input type="number" name="duration" class="form-control" value="30" required>
                    </div>
                    <div class="form-group full-width">
                        <label>Purpose / Reason</label>
                        <textarea name="purpose" class="form-control" rows="3" placeholder="Enter reason for appointment..."></textarea>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">Cancel</button>
                    <button type="submit" name="save" class="btn btn-primary">Book Appointment</button>
                </div>
            </form>
        </div>

        <!-- Appointments Table -->
        <div class="card">
            <div class="card-header">
                📋 Appointment List
            </div>
            <div class="card-body">
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Fee</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($appointments && $appointments->num_rows > 0): ?>
                                <?php while($row = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['AppointmentID']; ?></td>
                                    <td><?php echo htmlspecialchars($row['PatientName'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['DoctorName'] ?? 'N/A'); ?></td>
                                    <td><?php echo date('Y-m-d h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                    <td><?php echo $row['DurationMinutes']; ?> min</td>
                                    <td class="fee">$<?php echo number_format($row['ConsultationFee'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($row['Purpose'] ?: '-'); ?></td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        if($row['Status'] == 'Scheduled') $status_class = 'status-scheduled';
                                        elseif($row['Status'] == 'Completed') $status_class = 'status-completed';
                                        elseif($row['Status'] == 'Cancelled') $status_class = 'status-cancelled';
                                        ?>
                                        <span class="<?php echo $status_class; ?>"><?php echo $row['Status']; ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <?php if($row['Status'] == 'Scheduled'): ?>
                                                <a href="?page=appointments&complete=<?php echo $row['AppointmentID']; ?>" class="btn btn-success" onclick="return confirm('Complete this appointment? A bill will be generated automatically.')">Complete & Bill</a>
                                                <a href="?page=appointments&cancel=<?php echo $row['AppointmentID']; ?>" class="btn btn-warning" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                            <?php endif; ?>
                                            <?php if($row['Status'] == 'Completed'): ?>
    <a href="billing.php?appointment_id=<?php echo $row['AppointmentID']; ?>" target="_blank" class="btn btn-info">View Bill</a>
<?php endif; ?>
                                            <a href="?page=appointments&delete=<?php echo $row['AppointmentID']; ?>" class="btn btn-danger" onclick="return confirm('Delete this appointment?')">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" style="text-align: center; padding: 40px;">
                                        No appointments found. Click "Book New Appointment" to schedule one.
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