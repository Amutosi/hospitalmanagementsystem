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

// Get prescriptions by this nurse
$prescriptions = $conn->query("
    SELECT p.*, CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName,
           pat.ContactNo, pat.BloodGroup,
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND DoctorID IS NOT NULL) as has_doctor_prescription
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    WHERE p.NurseID = $nurse_id
    ORDER BY p.PrescriptionDate DESC
");

// Get counts
$total_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE NurseID = $nurse_id")->fetch_assoc()['count'];
$total_patients = $conn->query("SELECT COUNT(DISTINCT PatientID) as count FROM Prescription WHERE NurseID = $nurse_id")->fetch_assoc()['count'];
$recent_count = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE NurseID = $nurse_id AND PrescriptionDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Prescriptions - Nurse Portal</title>
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
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
        /* Header Section */
        .page-header {
            background: white;
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .page-header h1 {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 5px;
        }
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 5px solid;
            cursor: pointer;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .stat-card .stat-icon {
            font-size: 40px;
            float: right;
            opacity: 0.2;
        }
        .stat-card .stat-number {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 5px;
        }
        .stat-card .stat-label {
            color: #666;
            font-size: 14px;
            font-weight: 500;
        }
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
        /* Main Card */
        .main-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 18px 25px;
            color: white;
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 600;
            font-size: 18px;
        }
        .card-header-custom h4 i {
            margin-right: 10px;
        }
        /* Table Styles */
        .prescription-table {
            width: 100%;
            margin-bottom: 0;
        }
        .prescription-table thead th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #555;
            border-bottom: 2px solid #e9ecef;
        }
        .prescription-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        .prescription-table tbody tr:hover {
            background: #f8f9ff;
            cursor: pointer;
        }
        /* Badge Styles */
        .badge-doctor {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .badge-active {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .medication-badge {
            background: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }
        .empty-state h4 {
            color: #666;
            margin-bottom: 10px;
        }
        .btn-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        /* Tooltip */
        .prescription-tooltip {
            position: relative;
            cursor: help;
        }
        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 10px; }
            .stat-card .stat-number { font-size: 24px; }
            .prescription-table thead th { font-size: 10px; padding: 8px; }
            .prescription-table tbody td { padding: 8px; font-size: 12px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-prescription2"></i> My Prescriptions</h1>
                    <p class="text-muted mb-0">View all prescriptions you have given to patients</p>
                </div>
                <div>
                    <span class="badge bg-success px-3 py-2">
                        <i class="bi bi-person-heart"></i> Nurse: <?php echo htmlspecialchars($nurse['FirstName'] . ' ' . $nurse['LastName']); ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card primary" onclick="location.reload()">
                    <i class="bi bi-prescription2 stat-icon"></i>
                    <div class="stat-number"><?php echo $total_prescriptions; ?></div>
                    <div class="stat-label">Total Prescriptions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <i class="bi bi-people stat-icon"></i>
                    <div class="stat-number"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Patients Served</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <i class="bi bi-calendar-week stat-icon"></i>
                    <div class="stat-number"><?php echo $recent_count; ?></div>
                    <div class="stat-label">Last 7 Days</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <i class="bi bi-clock-history stat-icon"></i>
                    <div class="stat-number"><?php echo date('M Y'); ?></div>
                    <div class="stat-label">Current Month</div>
                </div>
            </div>
        </div>

        <!-- Prescriptions List -->
        <div class="main-card">
            <div class="card-header-custom">
                <h4>
                    <i class="bi bi-list-check"></i> 
                    Prescription History
                    <span class="badge bg-light text-dark ms-2"><?php echo $total_prescriptions; ?> Records</span>
                </h4>
            </div>
            <div class="table-responsive">
                <?php if($prescriptions && $prescriptions->num_rows > 0): ?>
                    <table class="prescription-table">
                        <thead>
                            <tr>
                                <th><i class="bi bi-calendar3"></i> Date</th>
                                <th><i class="bi bi-person"></i> Patient</th>
                                <th><i class="bi bi-capsule"></i> Medication</th>
                                <th><i class="bi bi-eyedropper"></i> Dosage</th>
                                <th><i class="bi bi-clock"></i> Frequency</th>
                                <th><i class="bi bi-hourglass-split"></i> Duration</th>
                                <th><i class="bi bi-chat-text"></i> Instructions</th>
                                <th><i class="bi bi-info-circle"></i> Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $prescriptions->fetch_assoc()): ?>
                            <tr onclick="viewPrescription(<?php echo $row['PrescriptionID']; ?>)">
                                <td>
                                    <strong><?php echo date('M d, Y', strtotime($row['PrescriptionDate'])); ?></strong><br>
                                    <small class="text-muted"><?php echo date('h:i A', strtotime($row['PrescriptionDate'])); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['PatientName']); ?></strong><br>
                                    <small class="text-muted">📞 <?php echo $row['ContactNo'] ?? 'N/A'; ?></small><br>
                                    <small class="text-muted">🩸 <?php echo $row['BloodGroup'] ?? 'Unknown'; ?></small>
                                </td>
                                <td>
                                    <span class="medication-badge">
                                        <i class="bi bi-capsule"></i> <?php echo htmlspecialchars($row['MedicationName']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                                <td><?php echo htmlspecialchars($row['Frequency']); ?></td>
                                <td><?php echo htmlspecialchars($row['Duration']); ?></td>
                                <td class="prescription-tooltip" title="<?php echo htmlspecialchars($row['Instructions']); ?>">
                                    <?php 
                                    $instructions = $row['Instructions'] ?: 'No special instructions';
                                    echo strlen($instructions) > 40 ? substr($instructions, 0, 40) . '...' : $instructions;
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['has_doctor_prescription'] > 0): ?>
                                        <span class="badge-doctor">
                                            <i class="bi bi-exclamation-triangle"></i> Doctor also prescribed
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-active">
                                            <i class="bi bi-check-circle"></i> Active
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-prescription2"></i>
                        <h4>No Prescriptions Yet</h4>
                        <p class="text-muted">You haven't given any prescriptions to patients yet.</p>
                        <a href="nurse_give_prescription.php" class="btn btn-custom">
                            <i class="bi bi-plus-circle"></i> Give First Prescription
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="alert alert-info mt-3 rounded-3" style="background: rgba(255,255,255,0.95); border-left: 4px solid #667eea;">
            <div class="d-flex align-items-center">
                <i class="bi bi-info-circle-fill fs-4 me-3" style="color: #667eea;"></i>
                <div>
                    <strong>Note:</strong> If a patient has doctor prescriptions, you cannot prescribe for them. 
                    Each patient can only receive prescriptions from one role (Doctor OR Nurse).
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewPrescription(id) {
            // You can add a modal to view full prescription details
            alert('Prescription ID: ' + id + '\nFull details view coming soon!');
        }
    </script>
</body>
</html>