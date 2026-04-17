<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) {
    header("Location: patient_login.php");
    exit();
}

require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];

// Get patient data
$patient = $conn->query("SELECT * FROM Patient WHERE PatientID = $patient_id")->fetch_assoc();

// Get statistics
$appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE PatientID = $patient_id")->fetch_assoc()['count'];
$prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE PatientID = $patient_id")->fetch_assoc()['count'];
$labtests = $conn->query("SELECT COUNT(*) as count FROM PatientLabTest WHERE PatientID = $patient_id")->fetch_assoc()['count'];
$bills = $conn->query("SELECT SUM(TotalAmount - AmountPaid) as pending FROM Billing WHERE PatientID = $patient_id AND PaymentStatus != 'Paid'")->fetch_assoc()['pending'];

// Get upcoming appointments
$upcoming = $conn->query("
    SELECT a.*, CONCAT(d.FirstName, ' ', d.LastName) as DoctorName, d.Specialization
    FROM Appointment a
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.PatientID = $patient_id AND a.AppointmentDateTime >= NOW() AND a.Status != 'Cancelled'
    ORDER BY a.AppointmentDateTime ASC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Dashboard</title>
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
        .sidebar h4 { text-align: center; margin-bottom: 30px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; margin: 5px 0; transition: 0.3s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .sidebar a i { margin-right: 10px; }
        .content { margin-left: 260px; padding: 20px; }
        .top-bar { background: white; padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 32px; font-weight: bold; color: #667eea; }
        .stat-label { color: #666; margin-top: 5px; }
        .card { border: none; border-radius: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 1px solid #eee; font-weight: bold; }
        .logout-btn { background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; }
        .logout-btn:hover { background: rgba(255,255,255,0.3); }
        @media (max-width: 768px) { .sidebar { left: -260px; } .content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>🏥 Patient Portal</h4>
        <a href="patient_dashboard.php" class="active"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="patient_appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
        <a href="patient_labtests.php"><i class="bi bi-flask"></i> Lab Tests</a>
        <a href="patient_prescriptions.php"><i class="bi bi-prescription"></i> Prescriptions</a>
        <a href="patient_bills.php"><i class="bi bi-credit-card"></i> Bills</a>
        <a href="patient_treatments.php"><i class="bi bi-clipboard2-pulse"></i> Treatments</a>
        <a href="patient_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
        <a href="login.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="top-bar">
            <h4>Welcome, <?php echo $_SESSION['patient_name']; ?>!</h4>
            <div>Last login: <?php echo $patient['LastLogin'] ?: 'First time'; ?></div>
        </div>
        
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $appointments; ?></div>
                    <div class="stat-label">Total Appointments</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $prescriptions; ?></div>
                    <div class="stat-label">Prescriptions</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $labtests; ?></div>
                    <div class="stat-label">Lab Tests</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($bills ?: 0, 2); ?></div>
                    <div class="stat-label">Pending Bills</div>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">📅 Upcoming Appointments</div>
            <div class="card-body">
                <?php if($upcoming->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Date & Time</th><th>Doctor</th><th>Specialization</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                <?php while($row = $upcoming->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                    <td>Dr. <?php echo $row['DoctorName']; ?></td>
                                    <td><?php echo $row['Specialization']; ?></td>
                                    <td><span class="badge bg-warning"><?php echo $row['Status']; ?></span></td>
                                    <td><a href="patient_appointments.php" class="btn btn-sm btn-primary">View</a></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No upcoming appointments. <a href="patient_appointments.php">Book one now</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>