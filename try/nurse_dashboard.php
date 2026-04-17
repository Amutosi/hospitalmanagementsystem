<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$nurse_id = $_SESSION['nurse_id'];

// Get nurse info
$nurse = $conn->query("SELECT * FROM Nurse WHERE NurseID = $nurse_id")->fetch_assoc();

// Get statistics
$prescriptions_count = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE NurseID = $nurse_id")->fetch_assoc()['count'];
$schedules_count = $conn->query("SELECT COUNT(*) as count FROM NurseSchedule WHERE NurseID = $nurse_id")->fetch_assoc()['count'];
$today_schedule = $conn->query("SELECT COUNT(*) as count FROM NurseSchedule WHERE NurseID = $nurse_id AND ShiftDate = CURDATE()")->fetch_assoc()['count'];

// Get today's schedule
$today_schedule_details = $conn->query("
    SELECT s.*, d.DeptName 
    FROM NurseSchedule s
    LEFT JOIN Department d ON s.AssignedDepartmentID = d.DepartmentID
    WHERE s.NurseID = $nurse_id AND s.ShiftDate = CURDATE()
    ORDER BY s.StartTime ASC
");

// Get recent prescriptions
$recent_prescriptions = $conn->query("
    SELECT p.*, CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    WHERE p.NurseID = $nurse_id
    ORDER BY p.PrescriptionDate DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - Hospital Management System</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .badge-morning { background: #ffc107; color: #000; }
        .badge-evening { background: #17a2b8; color: #fff; }
        .badge-night { background: #6c757d; color: #fff; }
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
            <i class="bi bi-person-heart" style="font-size: 40px;"></i>
            <h3>Nurse Portal</h3>
            <small><?php echo htmlspecialchars($nurse['FirstName'] . ' ' . $nurse['LastName']); ?></small>
        </div>
        <ul class="sidebar-menu">
            <li class="active" onclick="loadPage('dashboard')">
                <i class="bi bi-speedometer2"></i> Dashboard
            </li>
            <li onclick="loadPage('give_prescription')">
                <i class="bi bi-prescription"></i> Give Prescription
            </li>
            <li onclick="loadPage('my_prescriptions')">
                <i class="bi bi-list-ul"></i> My Prescriptions
            </li>
            <li onclick="loadPage('my_schedule')">
                <i class="bi bi-calendar-week"></i> My Schedule
            </li>
            <li onclick="loadPage('patients_list')">
                <i class="bi bi-people"></i> Patients List
            </li>
                    <li onclick="window.location.href='nurse_lab_results.php'">
    <i class="bi bi-flask"></i> Lab Results
</li>
            <li onclick="window.location.href='logout.php'">
                <i class="bi bi-box-arrow-right"></i> Logout
            </li>
    
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <h4>Welcome, Nurse <?php echo htmlspecialchars($nurse['FirstName'] . ' ' . $nurse['LastName']); ?>!</h4>
            <div>
                <i class="bi bi-calendar"></i> <?php echo date('F d, Y'); ?>
            </div>
        </div>
        
        <div id="pageContent">
            <!-- Dashboard Content -->
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="bi bi-prescription"></i>
                        <h3><?php echo $prescriptions_count; ?></h3>
                        <p>Total Prescriptions Given</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="bi bi-calendar-week"></i>
                        <h3><?php echo $schedules_count; ?></h3>
                        <p>Total Shifts Scheduled</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <i class="bi bi-clock"></i>
                        <h3><?php echo $today_schedule; ?></h3>
                        <p>Today's Shifts</p>
                    </div>
                </div>
            </div>
            
            <!-- Add Schedule Form -->
            <div class="card mt-3">
                <div class="card-header">
                    <i class="bi bi-plus-circle"></i> Add New Schedule
                    <button class="btn btn-sm btn-primary float-end" onclick="toggleScheduleForm()">
                        <i class="bi bi-plus"></i> Add Schedule
                    </button>
                </div>
                <div class="card-body" id="scheduleForm" style="display: none;">
                    <form method="POST" action="nurse_add_schedule.php">
                        <input type="hidden" name="nurse_id" value="<?php echo $nurse_id; ?>">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label>Shift Date *</label>
                                <input type="date" name="shift_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>Start Time *</label>
                                <input type="time" name="start_time" class="form-control" value="09:00" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>End Time *</label>
                                <input type="time" name="end_time" class="form-control" value="17:00" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label>Shift Type *</label>
                                <select name="shift_type" class="form-control" required>
                                    <option value="Morning">Morning</option>
                                    <option value="Evening">Evening</option>
                                    <option value="Night">Night</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2">
                                <label>Department</label>
                                <select name="department_id" class="form-control">
                                    <option value="">Select Department</option>
                                    <?php
                                    $depts = $conn->query("SELECT DepartmentID, DeptName FROM Department");
                                    while($dept = $depts->fetch_assoc()):
                                    ?>
                                        <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo $dept['DeptName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-2">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="2" placeholder="Any additional notes..."></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success mt-2">
                            <i class="bi bi-save"></i> Save Schedule
                        </button>
                        <button type="button" class="btn btn-secondary mt-2" onclick="toggleScheduleForm()">
                            <i class="bi bi-x"></i> Cancel
                        </button>
                    </form>
                </div>
            </div>
            
            <!-- Today's Schedule -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-calendar-check"></i> Today's Schedule - <?php echo date('l, F d, Y'); ?>
                    <a href="nurse_my_schedule.php" class="btn btn-sm btn-primary float-end">View All</a>
                </div>
                <div class="card-body">
                    <?php 
                    $today = date('Y-m-d');
                    $today_schedule_details = $conn->query("
                        SELECT s.*, d.DeptName 
                        FROM NurseSchedule s
                        LEFT JOIN Department d ON s.AssignedDepartmentID = d.DepartmentID
                        WHERE s.NurseID = $nurse_id AND s.ShiftDate = '$today'
                        ORDER BY s.StartTime ASC
                    ");
                    
                    if($today_schedule_details && $today_schedule_details->num_rows > 0): 
                    ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Shift Type</th>
                                        <th>Start Time</th>
                                        <th>End Time</th>
                                        <th>Department</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $today_schedule_details->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower($row['ShiftType']); ?>">
                                                <?php echo $row['ShiftType']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('h:i A', strtotime($row['StartTime'])); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['EndTime'])); ?></td>
                                        <td><?php echo $row['DeptName'] ?? 'General Ward'; ?></td>
                                        <td><?php echo $row['Notes'] ?: '-'; ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                            <p class="mt-2 text-muted">No schedule for today</p>
                            <small>Click "Add Schedule" to create your shift schedule</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Prescriptions -->
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-clock-history"></i> Recent Prescriptions Given
                    <button class="btn btn-sm btn-primary float-end" onclick="refreshPrescriptions()">
                        <i class="bi bi-arrow-repeat"></i> Refresh
                    </button>
                </div>
                <div class="card-body" id="prescriptionsList">
                    <?php if($recent_prescriptions && $recent_prescriptions->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Medication</th>
                                        <th>Dosage</th>
                                        <th>Frequency</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $recent_prescriptions->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['PatientName']; ?></td>
                                        <td><?php echo $row['MedicationName']; ?></td>
                                        <td><?php echo $row['Dosage']; ?></td>
                                        <td><?php echo $row['Frequency']; ?></td>
                                        <td><?php echo date('Y-m-d H:i', strtotime($row['PrescriptionDate'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-muted">No prescriptions given yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleScheduleForm() {
            var form = document.getElementById('scheduleForm');
            if(form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
        
        function refreshPrescriptions() {
            location.reload();
        }
        
        function loadPage(page) {
            if(page === 'dashboard') {
                location.reload();
            } else {
                window.location.href = 'nurse_' + page + '.php';
            }
        }
    </script>
</body>
</html>