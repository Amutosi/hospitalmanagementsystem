<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];
$patient_name = $_SESSION['patient_name'];

// Get treatments assigned to this patient
$treatments = $conn->query("
    SELECT pt.*, 
           t.TreatmentName, t.BaseCost, t.Description as TreatmentDescription,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName,
           CONCAT(n.FirstName, ' ', n.LastName) as NurseName,
           lt.TestName as RelatedLabTest
    FROM PatientTreatment pt
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    LEFT JOIN Doctor d ON pt.PrescribedByDoctor = d.DoctorID
    LEFT JOIN Nurse n ON pt.PrescribedByNurse = n.NurseID
    LEFT JOIN PatientLabTest plt ON pt.LabTestID = plt.PatientLabTestID
    LEFT JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    WHERE pt.PatientID = $patient_id
    ORDER BY pt.StartDate DESC
");

// Get statistics
$total_treatments = $treatments->num_rows;
$ongoing_treatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PatientID = $patient_id AND Status = 'Ongoing'")->fetch_assoc()['count'];
$completed_treatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PatientID = $patient_id AND Status = 'Completed'")->fetch_assoc()['count'];
$scheduled_treatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE PatientID = $patient_id AND Status = 'Scheduled'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Treatments - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
            overflow-x: hidden;
        }
        .sidebar {
            width: 280px;
            position: fixed;
            left: 0;
            top: 0;
            height: 100%;
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
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
            text-align: center;
            border-left: 4px solid;
        }
        .stat-card.primary { border-left-color: #0f4c81; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.warning { border-left-color: #ffc107; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 32px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .stat-label {
            color: #666;
            font-size: 13px;
            font-weight: 500;
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
        .treatment-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid;
        }
        .treatment-card.scheduled { border-left-color: #ffc107; }
        .treatment-card.ongoing { border-left-color: #17a2b8; }
        .treatment-card.completed { border-left-color: #28a745; }
        .treatment-card.cancelled { border-left-color: #dc3545; }
        .treatment-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }
        .doctor-badge {
            background: #1e3c72;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            display: inline-block;
        }
        .nurse-badge {
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            display: inline-block;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-scheduled { background: #ffc107; color: #000; }
        .status-ongoing { background: #17a2b8; color: #fff; }
        .status-completed { background: #28a745; color: #fff; }
        .status-cancelled { background: #dc3545; color: #fff; }
        @media (max-width: 768px) {
            .sidebar { left: -280px; }
            .main-content { margin-left: 0; }
            .sidebar.active { left: 0; }
            .stat-number { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <i class="bi bi-person-circle" style="font-size: 40px;"></i>
            <h3>Patient Portal</h3>
            <small><?php echo htmlspecialchars($patient_name); ?></small>
        </div>
        <ul class="sidebar-menu">
            <li onclick="window.location.href='patient_dashboard.php'">
                <i class="bi bi-speedometer2"></i> Dashboard
            </li>
            <li onclick="window.location.href='patient_appointments.php'">
                <i class="bi bi-calendar-check"></i> Appointments
            </li>
            <li onclick="window.location.href='patient_labtests.php'">
                <i class="bi bi-flask"></i> Lab Tests
            </li>
            <li onclick="window.location.href='patient_prescriptions.php'">
                <i class="bi bi-prescription"></i> Prescriptions
            </li>
            <li onclick="window.location.href='patient_bills.php'">
                <i class="bi bi-credit-card"></i> Bills
            </li>
            <li class="active" onclick="window.location.href='patient_treatments.php'">
                <i class="bi bi-clipboard2-pulse"></i> Treatments
            </li>
            <li onclick="window.location.href='patient_profile.php'">
                <i class="bi bi-person-circle"></i> Profile
            </li>
            <li onclick="window.location.href='logout.php'">
                <i class="bi bi-box-arrow-right"></i> Logout
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="top-bar">
            <div>
                <h4>Welcome, <?php echo htmlspecialchars($patient_name); ?>!</h4>
                <small><i class="bi bi-clipboard2-pulse"></i> Your Treatment Plans</small>
            </div>
            <div>
                <i class="bi bi-calendar"></i> <?php echo date('l, F d, Y'); ?>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3 mb-3">
                <div class="stat-card primary">
                    <div class="stat-number"><?php echo $total_treatments; ?></div>
                    <div class="stat-label">Total Treatments</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $scheduled_treatments; ?></div>
                    <div class="stat-label">Scheduled</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card info">
                    <div class="stat-number"><?php echo $ongoing_treatments; ?></div>
                    <div class="stat-label">Ongoing</div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $completed_treatments; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
        </div>

        <!-- Treatments List -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check"></i> My Treatment Plans
            </div>
            <div class="card-body">
                <?php if($treatments && $treatments->num_rows > 0): ?>
                    <?php while($row = $treatments->fetch_assoc()): 
                        $statusClass = strtolower($row['Status']);
                    ?>
                        <div class="treatment-card <?php echo $statusClass; ?>">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($row['TreatmentName']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($row['TreatmentDescription'] ?: 'No description available'); ?></p>
                                </div>
                                <span class="status-badge status-<?php echo $statusClass; ?>">
                                    <?php echo $row['Status']; ?>
                                </span>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <small class="text-muted">Stage</small>
                                    <div><strong>Stage <?php echo $row['SequenceOrder']; ?></strong></div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Start Date</small>
                                    <div><?php echo date('M d, Y', strtotime($row['StartDate'])); ?></div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">End Date</small>
                                    <div><?php echo $row['EndDate'] ? date('M d, Y', strtotime($row['EndDate'])) : '<span class="text-muted">Ongoing</span>'; ?></div>
                                </div>
                                <div class="col-md-3">
                                    <small class="text-muted">Cost</small>
                                    <div><strong>$<?php echo number_format($row['BaseCost'], 2); ?></strong></div>
                                </div>
                            </div>
                            <div class="row mt-2">
                                <div class="col-md-6">
                                    <small class="text-muted">Assigned By</small>
                                    <div>
                                        <?php if($row['PrescribedByDoctor']): ?>
                                            <span class="doctor-badge">👨‍⚕️ Dr. <?php echo htmlspecialchars($row['DoctorName']); ?></span>
                                        <?php else: ?>
                                            <span class="nurse-badge">👩‍⚕️ Nurse <?php echo htmlspecialchars($row['NurseName']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if($row['RelatedLabTest']): ?>
                                <div class="col-md-6">
                                    <small class="text-muted">Based on Lab Test</small>
                                    <div><span class="badge bg-info"><?php echo htmlspecialchars($row['RelatedLabTest']); ?></span></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if($row['Notes']): ?>
                            <div class="mt-2">
                                <small class="text-muted">Notes:</small>
                                <p class="small mb-0"><?php echo nl2br(htmlspecialchars($row['Notes'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard2-pulse" style="font-size: 64px; color: #ccc;"></i>
                        <h4 class="mt-3">No Treatments Assigned</h4>
                        <p class="text-muted">You don't have any treatment plans yet.</p>
                        <p class="text-muted small">Treatments will appear here once assigned by a doctor or nurse.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Information Note -->
        <div class="alert alert-info mt-3">
            <i class="bi bi-info-circle"></i> 
            <strong>Note:</strong> Treatments are assigned based on your medical condition and lab results. 
            Please follow the prescribed treatment plan for best results. If you have any questions, 
            consult your healthcare provider.
        </div>
    </div>

    <script>
        // Mobile menu toggle
        document.querySelector('.top-bar').addEventListener('click', function(e) {
            if(window.innerWidth <= 768) {
                document.querySelector('.sidebar').classList.toggle('active');
            }
        });
    </script>
</body>
</html>