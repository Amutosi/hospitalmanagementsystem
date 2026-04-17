<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

// Get doctor info
$doctor = $conn->query("SELECT * FROM Doctor WHERE DoctorID = $doctor_id")->fetch_assoc();

// Get statistics
$today_appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DoctorID = $doctor_id AND DATE(AppointmentDateTime) = CURDATE()")->fetch_assoc()['count'];
$total_appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DoctorID = $doctor_id")->fetch_assoc()['count'];
$completed_appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DoctorID = $doctor_id AND Status = 'Completed'")->fetch_assoc()['count'];
$pending_appointments = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DoctorID = $doctor_id AND Status = 'Scheduled'")->fetch_assoc()['count'];

// Get today's appointments
$today_appointments_list = $conn->query("
    SELECT a.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName, p.ContactNo
    FROM Appointment a
    JOIN Patient p ON a.PatientID = p.PatientID
    WHERE a.DoctorID = $doctor_id AND DATE(a.AppointmentDateTime) = CURDATE()
    ORDER BY a.AppointmentDateTime ASC
");

// Get upcoming appointments
$upcoming_appointments = $conn->query("
    SELECT a.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName, p.ContactNo
    FROM Appointment a
    JOIN Patient p ON a.PatientID = p.PatientID
    WHERE a.DoctorID = $doctor_id AND a.AppointmentDateTime > NOW() AND a.Status != 'Cancelled'
    ORDER BY a.AppointmentDateTime ASC
    LIMIT 10
");

// Get recent patients
$recent_patients = $conn->query("
    SELECT p.PatientID, p.FirstName, p.LastName, p.ContactNo,
           (SELECT COUNT(*) FROM Appointment WHERE PatientID = p.PatientID AND DoctorID = $doctor_id) as visit_count,
           (SELECT MAX(AppointmentDateTime) FROM Appointment WHERE PatientID = p.PatientID AND DoctorID = $doctor_id) as last_visit
    FROM Patient p
    WHERE p.PatientID IN (SELECT DISTINCT PatientID FROM Appointment WHERE DoctorID = $doctor_id)
    ORDER BY (SELECT MAX(AppointmentDateTime) FROM Appointment WHERE PatientID = p.PatientID AND DoctorID = $doctor_id) DESC
    LIMIT 10
");

// Get treatments assigned by this doctor
$doctor_treatments = $conn->query("
    SELECT pt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           t.TreatmentName, t.BaseCost,
           lt.TestName as RelatedLabTest
    FROM PatientTreatment pt
    JOIN Patient p ON pt.PatientID = p.PatientID
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    LEFT JOIN PatientLabTest plt ON pt.LabTestID = plt.PatientLabTestID
    LEFT JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    WHERE pt.PrescribedByDoctor = $doctor_id
    ORDER BY pt.StartDate DESC
    LIMIT 10
");

// Get total treatments count
$total_treatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PrescribedByDoctor = $doctor_id")->fetch_assoc()['count'];
$ongoing_treatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PrescribedByDoctor = $doctor_id AND Status = 'Ongoing'")->fetch_assoc()['count'];
$completed_treatments_count = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PrescribedByDoctor = $doctor_id AND Status = 'Completed'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
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
        .sidebar {
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding-top: 20px;
            transition: all 0.3s;
            z-index: 1000;
        }
        .sidebar-header {
            text-align: center;
            padding: 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .sidebar-header h3 {
            font-size: 20px;
            margin-top: 10px;
        }
        .sidebar-header small {
            font-size: 12px;
            opacity: 0.8;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin-top: 20px;
        }
        .sidebar-menu li {
            padding: 12px 25px;
            margin: 5px 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .sidebar-menu li:hover {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }
        .sidebar-menu li.active {
            background: rgba(255,255,255,0.3);
        }
        .sidebar-menu li i {
            margin-right: 12px;
            width: 20px;
        }
        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.3s;
        }
        .top-bar {
            background: white;
            padding: 15px 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card i {
            font-size: 40px;
            float: right;
            opacity: 0.3;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 15px 20px;
            font-weight: bold;
            border-radius: 15px 15px 0 0;
        }
        .btn-primary {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(30,60,114,0.4);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-scheduled { background: #ffc107; color: #000; }
        .status-completed { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        .status-ongoing { background: #17a2b8; color: #fff; }
        .treatment-badge {
            background: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
            .sidebar.active { left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-person-badge" style="font-size: 40px;"></i>
            <h3>Doctor Portal</h3>
            <small>Dr. <?php echo htmlspecialchars($doctor['FirstName'] . ' ' . $doctor['LastName']); ?></small>
            <br>
            <small><?php echo htmlspecialchars($doctor['Specialization']); ?></small>
        </div>
        <ul class="sidebar-menu">
            <li class="active" onclick="loadPage('dashboard')">
                <i class="bi bi-speedometer2"></i> Dashboard
            </li>
            <li onclick="loadPage('appointments')">
                <i class="bi bi-calendar-check"></i> My Appointments
            </li>
            <li onclick="loadPage('patients')">
                <i class="bi bi-people"></i> My Patients
            </li>
            <li onclick="loadPage('schedule')">
                <i class="bi bi-calendar-week"></i> My Schedule
            </li>
            <li onclick="window.location.href='doctor_prescriptions.php'">
                <i class="bi bi-prescription"></i> Prescriptions
            </li>
            <li onclick="window.location.href='doctor_lab_results.php'">
                <i class="bi bi-flask"></i> Lab Results
            </li>
            <li onclick="window.location.href='doctor_labtests.php'">
                <i class="bi bi-flask"></i> Lab Tests
            </li>
            <li onclick="window.location.href='logout.php'">
                <i class="bi bi-box-arrow-right"></i> Logout
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h4>Welcome, Dr. <?php echo htmlspecialchars($doctor['FirstName'] . ' ' . $doctor['LastName']); ?>!</h4>
                <small><?php echo htmlspecialchars($doctor['Specialization']); ?> | <?php echo htmlspecialchars($doctor['Qualification']); ?></small>
            </div>
            <div>
                <i class="bi bi-calendar"></i> <?php echo date('l, F d, Y'); ?>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-calendar-today"></i>
                    <h3><?php echo $today_appointments; ?></h3>
                    <p>Today's Appointments</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-calendar-week"></i>
                    <h3><?php echo $total_appointments; ?></h3>
                    <p>Total Appointments</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-check-circle"></i>
                    <h3><?php echo $completed_appointments; ?></h3>
                    <p>Completed</p>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card">
                    <i class="bi bi-clock-history"></i>
                    <h3><?php echo $pending_appointments; ?></h3>
                    <p>Pending</p>
                </div>
            </div>
        </div>

        <!-- Treatment Statistics Row -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <i class="bi bi-clipboard2-pulse"></i>
                    <h3><?php echo $total_treatments; ?></h3>
                    <p>Total Treatments</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <i class="bi bi-play-circle"></i>
                    <h3><?php echo $ongoing_treatments; ?></h3>
                    <p>Ongoing Treatments</p>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stat-card">
                    <i class="bi bi-check-circle-fill"></i>
                    <h3><?php echo $completed_treatments_count; ?></h3>
                    <p>Completed Treatments</p>
                </div>
            </div>
        </div>
        
        <!-- Today's Appointments -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-check"></i> Today's Appointments
                <a href="doctor_appointments.php" class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <?php if($today_appointments_list && $today_appointments_list->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $today_appointments_list->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                    <td><strong><?php echo $row['PatientName']; ?></strong></td>
                                    <td><?php echo $row['ContactNo']; ?></td>
                                    <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['Status'] == 'Scheduled'): ?>
                                            <button class="btn btn-sm btn-success" onclick="completeAppointment(<?php echo $row['AppointmentID']; ?>)">
                                                Complete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No appointments scheduled for today</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Treatments Assigned by Doctor -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard2-pulse"></i> Treatments Assigned by You
                <a href="doctor_treatments.php" class="btn btn-sm btn-primary float-end">View All</a>
            </div>
            <div class="card-body">
                <?php if($doctor_treatments && $doctor_treatments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient</th>
                                    <th>Treatment</th>
                                    <th>Stage</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Related Lab Test</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $doctor_treatments->fetch_assoc()): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($row['PatientName']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($row['TreatmentName']); ?></td>
                                    <td>Stage <?php echo $row['SequenceOrder']; ?></td>
                                    <td><?php echo $row['StartDate']; ?></td>
                                    <td><?php echo $row['EndDate'] ?: 'Ongoing'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['RelatedLabTest'] ?: '-'; ?></td>
                                    <td>
                                        <?php if($row['Status'] == 'Scheduled'): ?>
                                            <button class="btn btn-sm btn-primary" onclick="startTreatment(<?php echo $row['PatientTreatmentID']; ?>)">
                                                Start
                                            </button>
                                        <?php elseif($row['Status'] == 'Ongoing'): ?>
                                            <button class="btn btn-sm btn-success" onclick="completeTreatment(<?php echo $row['PatientTreatmentID']; ?>)">
                                                Complete
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-3">
                        <i class="bi bi-clipboard2-pulse" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No treatments assigned yet</p>
                        <small>Assign treatments from lab results page</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Upcoming Appointments -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Upcoming Appointments
            </div>
            <div class="card-body">
                <?php if($upcoming_appointments && $upcoming_appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $upcoming_appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                    <td><?php echo $row['PatientName']; ?></td>
                                    <td><?php echo $row['ContactNo']; ?></td>
                                    <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No upcoming appointments</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Patients -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-people"></i> Recent Patients
            </div>
            <div class="card-body">
                <?php if($recent_patients && $recent_patients->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Patient Name</th>
                                    <th>Contact</th>
                                    <th>Visits</th>
                                    <th>Last Visit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $recent_patients->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['FirstName'] . ' ' . $row['LastName']; ?></td>
                                    <td><?php echo $row['ContactNo']; ?></td>
                                    <td><?php echo $row['visit_count']; ?> visits</td>
                                    <td><?php echo $row['last_visit'] ? date('M d, Y', strtotime($row['last_visit'])) : '-'; ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-info" onclick="viewPatient(<?php echo $row['PatientID']; ?>)">
                                            View History
                                        </button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center text-muted">No patients yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function loadPage(page) {
            if(page === 'dashboard') {
                location.reload();
            } else {
                window.location.href = 'doctor_' + page + '.php';
            }
        }
        
        function completeAppointment(id) {
            if(confirm('Mark this appointment as completed?')) {
                window.location.href = 'doctor_complete_appointment.php?id=' + id;
            }
        }
        
        function viewPatient(id) {
            window.open('doctor_view_patient.php?id=' + id, '_blank', 'width=900,height=700');
        }
        
        function startTreatment(id) {
            if(confirm('Start this treatment?')) {
                window.location.href = 'doctor_update_treatment.php?id=' + id + '&status=Ongoing';
            }
        }
        
        function completeTreatment(id) {
            if(confirm('Complete this treatment?')) {
                window.location.href = 'doctor_update_treatment.php?id=' + id + '&status=Completed';
            }
        }
    </script>
</body>
</html>