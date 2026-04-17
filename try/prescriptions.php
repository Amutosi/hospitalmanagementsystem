<?php

require_once 'db_config.php';

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['doctor_logged_in']) && !isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Get all prescriptions (from both doctors and nurses)
$prescriptions = $conn->query("
    SELECT p.*, 
           CONCAT(pat.FirstName, ' ', pat.LastName) as PatientName,
           pat.ContactNo, pat.DateOfBirth,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName,
           CONCAT(n.FirstName, ' ', n.LastName) as NurseName,
           lt.TestName as RelatedLabTest
    FROM Prescription p
    JOIN Patient pat ON p.PatientID = pat.PatientID
    LEFT JOIN Doctor d ON p.DoctorID = d.DoctorID
    LEFT JOIN Nurse n ON p.NurseID = n.NurseID
    LEFT JOIN PatientLabTest plt ON p.LabTestID = plt.PatientLabTestID
    LEFT JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    ORDER BY p.PrescriptionDate DESC
");

// Get statistics
$total_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription")->fetch_assoc()['count'];
$doctor_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE DoctorID IS NOT NULL")->fetch_assoc()['count'];
$nurse_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE NurseID IS NOT NULL")->fetch_assoc()['count'];
$recent_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE PrescriptionDate >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Prescriptions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background: #f0f2f5; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .page-header { background: white; border-radius: 20px; padding: 25px 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .page-header h1 { font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); transition: all 0.3s; border-left: 4px solid; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.15); }
        .stat-card.primary { border-left-color: #3498db; }
        .stat-card.success { border-left-color: #2ecc71; }
        .stat-card.info { border-left-color: #9b59b6; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-number { font-size: 32px; font-weight: 800; margin-bottom: 5px; }
        .stat-label { color: #666; font-size: 14px; font-weight: 500; }
        .main-card { background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; }
        .card-header-custom { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); padding: 18px 25px; color: white; }
        .card-header-custom h4 { margin: 0; font-weight: 600; }
        .prescription-table { width: 100%; margin-bottom: 0; }
        .prescription-table thead th { background: #f8f9fa; padding: 15px; font-weight: 600; font-size: 13px; text-transform: uppercase; color: #555; border-bottom: 2px solid #e9ecef; }
        .prescription-table tbody td { padding: 15px; vertical-align: middle; border-bottom: 1px solid #e9ecef; }
        .prescription-table tbody tr:hover { background: #f8f9ff; }
        .badge-doctor { background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%); color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .badge-nurse { background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%); color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 600; display: inline-block; }
        .medication-badge { background: #e9ecef; color: #495057; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; display: inline-block; }
        .filter-buttons { margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-btn { padding: 8px 20px; border-radius: 50px; border: none; cursor: pointer; background: #e9ecef; transition: all 0.3s; font-weight: 500; }
        .filter-btn.active { background: #2c3e50; color: white; }
        .empty-state { text-align: center; padding: 60px 20px; }
        .empty-state i { font-size: 80px; color: #ccc; margin-bottom: 20px; }
        @media (max-width: 768px) {
            .prescription-table thead th { font-size: 10px; padding: 8px; }
            .prescription-table tbody td { padding: 8px; font-size: 12px; }
            .stat-number { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-prescription2"></i> All Prescriptions</h1>
                    <p class="text-muted mb-0">View all prescriptions given by Doctors and Nurses</p>
                </div>
                <a href="?page=dashboard" class="btn btn-secondary rounded-pill">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="stat-number"><?php echo $total_prescriptions; ?></div>
                    <div class="stat-label">Total Prescriptions</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $doctor_prescriptions; ?></div>
                    <div class="stat-label">By Doctors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-number"><?php echo $nurse_prescriptions; ?></div>
                    <div class="stat-label">By Nurses</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $recent_prescriptions; ?></div>
                    <div class="stat-label">Last 7 Days</div>
                </div>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">📋 All Prescriptions</button>
            <button class="filter-btn" data-filter="doctor">👨‍⚕️ Doctors Only</button>
            <button class="filter-btn" data-filter="nurse">👩‍⚕️ Nurses Only</button>
        </div>

        <!-- Prescriptions Table -->
        <div class="main-card">
            <div class="card-header-custom">
                <h4><i class="bi bi-list-check"></i> Prescription History</h4>
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
                                <th><i class="bi bi-person-badge"></i> Prescribed By</th>
                                <th><i class="bi bi-flask"></i> Related Lab Test</th>
                            </tr>
                        </thead>
                        <tbody id="prescriptionsTable">
                            <?php while($row = $prescriptions->fetch_assoc()): 
                                $prescriberType = !empty($row['DoctorID']) ? 'doctor' : 'nurse';
                                $prescriberName = !empty($row['DoctorID']) ? 'Dr. ' . $row['DoctorName'] : 'Nurse ' . $row['NurseName'];
                            ?>
                                <tr data-type="<?php echo $prescriberType; ?>">
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['PrescriptionDate'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['PatientName']); ?></strong><br>
                                        <small class="text-muted">📞 <?php echo $row['ContactNo'] ?? 'N/A'; ?></small>
                                    </td>
                                    <td><span class="medication-badge"><?php echo htmlspecialchars($row['MedicationName']); ?></span></td>
                                    <td><?php echo htmlspecialchars($row['Dosage']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Frequency']); ?></td>
                                    <td><?php echo htmlspecialchars($row['Duration']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($row['Instructions'] ?? '-', 0, 40)); ?></td>
                                    <td>
                                        <span class="badge-<?php echo $prescriberType; ?>">
                                            <i class="bi bi-<?php echo $prescriberType == 'doctor' ? 'person-badge' : 'person-heart'; ?>"></i>
                                            <?php echo htmlspecialchars($prescriberName); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($row['RelatedLabTest']): ?>
                                            <span class="badge bg-info text-white"><?php echo htmlspecialchars($row['RelatedLabTest']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-prescription2"></i>
                        <h4>No Prescriptions Found</h4>
                        <p class="text-muted">No prescriptions have been given yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const rows = document.querySelectorAll('#prescriptionsTable tr');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    // Remove active class from all buttons
                    filterBtns.forEach(b => b.classList.remove('active'));
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    rows.forEach(row => {
                        if(filterValue === 'all') {
                            row.style.display = '';
                        } else if(filterValue === row.getAttribute('data-type')) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>