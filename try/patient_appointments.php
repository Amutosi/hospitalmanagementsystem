<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) {
    header("Location: patient_login.php");
    exit();
}

require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];

// Book appointment
if(isset($_POST['book'])) {
    $doctor_id = $_POST['doctor_id'];
    $datetime = $_POST['datetime'];
    $purpose = $_POST['purpose'];
    
    $sql = "INSERT INTO Appointment (PatientID, DoctorID, AppointmentDateTime, Purpose, Status) 
            VALUES ('$patient_id', '$doctor_id', '$datetime', '$purpose', 'Scheduled')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='patient_appointments.php';</script>";
    }
}

// Cancel appointment
if(isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $conn->query("UPDATE Appointment SET Status = 'Cancelled' WHERE AppointmentID = $id");
    echo "<script>alert('Appointment cancelled!'); window.location.href='patient_appointments.php';</script>";
}

$doctors = $conn->query("SELECT DoctorID, CONCAT(FirstName, ' ', LastName) as Name, Specialization FROM Doctor WHERE IsActive = 1");
$appointments = $conn->query("
    SELECT a.*, CONCAT(d.FirstName, ' ', d.LastName) as DoctorName, d.Specialization
    FROM Appointment a
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.PatientID = $patient_id
    ORDER BY a.AppointmentDateTime DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
             body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;

    background: 
        
        url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1600&q=80') no-repeat center center/cover;

    overflow-x: hidden;
}
        .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; margin: 5px 0; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .sidebar a i { margin-right: 10px; }
        .content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #eee; font-weight: bold; }
        @media (max-width: 768px) { .sidebar { left: -260px; } .content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-center mb-4">🏥 Patient Portal</h4>
        <a href="patient_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="patient_appointments.php" class="active"><i class="bi bi-calendar-check"></i> Appointments</a>
        <a href="patient_labtests.php"><i class="bi bi-flask"></i> Lab Tests</a>
        <a href="patient_prescriptions.php"><i class="bi bi-prescription"></i> Prescriptions</a>
        <a href="patient_bills.php"><i class="bi bi-credit-card"></i> Bills</a>
        <a href="patient_treatments.php"><i class="bi bi-clipboard2-pulse"></i> Treatments</a>
        <a href="patient_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
        <a href="patient_logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="card">
            <div class="card-header">📅 Book New Appointment</div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label>Select Doctor</label>
                        <select name="doctor_id" class="form-control" required>
                            <option value="">Choose Doctor</option>
                            <?php while($doc = $doctors->fetch_assoc()): ?>
                                <option value="<?php echo $doc['DoctorID']; ?>">Dr. <?php echo $doc['Name']; ?> - <?php echo $doc['Specialization']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label>Date & Time</label>
                        <input type="datetime-local" name="datetime" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label>Purpose</label>
                        <textarea name="purpose" class="form-control" rows="1"></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" name="book" class="btn btn-primary">Book Appointment</button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">📋 My Appointments</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr><th>Date & Time</th><th>Doctor</th><th>Specialization</th><th>Purpose</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php while($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                <td>Dr. <?php echo $row['DoctorName']; ?></td>
                                <td><?php echo $row['Specialization']; ?></td>
                                <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $row['Status'] == 'Scheduled' ? 'warning' : ($row['Status'] == 'Completed' ? 'success' : 'danger'); ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['Status'] == 'Scheduled'): ?>
                                        <a href="?cancel=<?php echo $row['AppointmentID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel appointment?')">Cancel</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>