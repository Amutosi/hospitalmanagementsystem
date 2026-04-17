<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

// Handle complete appointment
if(isset($_GET['complete'])) {
    $id = $_GET['complete'];
    $conn->query("UPDATE Appointment SET Status = 'Completed' WHERE AppointmentID = $id");
    echo "<script>alert('Appointment completed!'); window.location.href='doctor_appointments.php';</script>";
}

// Handle cancel appointment
if(isset($_GET['cancel'])) {
    $id = $_GET['cancel'];
    $conn->query("UPDATE Appointment SET Status = 'Cancelled' WHERE AppointmentID = $id");
    echo "<script>alert('Appointment cancelled!'); window.location.href='doctor_appointments.php';</script>";
}

// Get all appointments
$appointments = $conn->query("
    SELECT a.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName, p.ContactNo, p.Email
    FROM Appointment a
    JOIN Patient p ON a.PatientID = p.PatientID
    WHERE a.DoctorID = $doctor_id
    ORDER BY a.AppointmentDateTime DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Appointments - Doctor Portal</title>
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
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .btn-back { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; padding: 8px 20px; border-radius: 8px; text-decoration: none; }
        .status-badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
        .status-scheduled { background: #ffc107; color: #000; }
        .status-completed { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar-check"></i> My Appointments</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="card">
            <div class="card-header">All Appointments</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient Name</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $appointments->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                <td><strong><?php echo $row['PatientName']; ?></strong></td>
                                <td><?php echo $row['ContactNo']; ?></td>
                                <td><?php echo $row['Email'] ?: '-'; ?></td>
                                <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                                        <?php echo $row['Status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if($row['Status'] == 'Scheduled'): ?>
                                        <a href="?complete=<?php echo $row['AppointmentID']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Complete this appointment?')">Complete</a>
                                        <a href="?cancel=<?php echo $row['AppointmentID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancel this appointment?')">Cancel</a>
                                    <?php endif; ?>
                                    <button class="btn btn-sm btn-info" onclick="viewPatient(<?php echo $row['PatientID']; ?>)">View Patient</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function viewPatient(id) {
            window.open('doctor_view_patient.php?id=' + id, '_blank', 'width=900,height=700');
        }
    </script>
</body>
</html>