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

$patients = $conn->query("
    SELECT p.*, 
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND NurseID = $nurse_id) as prescriptions_count,
           (SELECT MAX(PrescriptionDate) FROM Prescription WHERE PatientID = p.PatientID AND NurseID = $nurse_id) as last_prescription_date
    FROM Patient p
    WHERE p.IsActive = 1
    ORDER BY p.PatientID DESC
");

$total_patients = $patients->num_rows;
$total_prescriptions = $conn->query("SELECT COUNT(*) as count FROM Prescription WHERE NurseID = $nurse_id")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients List - Nurse Portal</title>
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
        .container-fluid {
            max-width: 1400px;
            margin: 0 auto;
        }
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
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            border-left: 4px solid;
        }
        .stat-card.primary { border-left-color: #667eea; }
        .stat-card.success { border-left-color: #28a745; }
        .stat-card.info { border-left-color: #17a2b8; }
        .stat-card.warning { border-left-color: #ffc107; }
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
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header-custom h4 {
            margin: 0;
            font-weight: 600;
        }
        .search-box {
            background: rgba(255,255,255,0.2);
            border: none;
            border-radius: 50px;
            padding: 8px 15px;
            color: white;
            width: 250px;
        }
        .search-box::placeholder {
            color: rgba(255,255,255,0.7);
        }
        .search-box:focus {
            outline: none;
            background: rgba(255,255,255,0.3);
        }
        .patient-table {
            width: 100%;
            margin-bottom: 0;
        }
        .patient-table thead th {
            background: #f8f9fa;
            padding: 15px;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            color: #555;
            border-bottom: 2px solid #e9ecef;
        }
        .patient-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }
        .patient-table tbody tr {
            transition: all 0.3s;
        }
        .patient-table tbody tr:hover {
            background: #f8f9ff;
            transform: scale(1.01);
        }
        .badge-prescription {
            background: #e9ecef;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .btn-view {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-view:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
            margin-right: 12px;
        }
        .patient-info {
            display: flex;
            align-items: center;
        }
        .patient-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        .patient-id {
            font-size: 11px;
            color: #999;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-state i {
            font-size: 80px;
            color: #ccc;
            margin-bottom: 20px;
        }
        @media (max-width: 768px) {
            body { padding: 10px; }
            .patient-table thead th { font-size: 10px; padding: 8px; }
            .patient-table tbody td { padding: 8px; font-size: 12px; }
            .btn-view { padding: 4px 10px; font-size: 10px; }
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
                    <h1><i class="bi bi-people"></i> Patients Management</h1>
                    <p class="text-muted mb-0">View and manage all patients in the system</p>
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
                <div class="stat-card primary">
                    <div class="stat-number"><?php echo $total_patients; ?></div>
                    <div class="stat-label">Total Patients</div>
                    <i class="bi bi-people" style="font-size: 28px; float: right; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $total_prescriptions; ?></div>
                    <div class="stat-label">Prescriptions Given</div>
                    <i class="bi bi-prescription2" style="font-size: 28px; float: right; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="stat-number"><?php echo round($total_patients > 0 ? ($total_prescriptions / $total_patients) : 0, 1); ?></div>
                    <div class="stat-label">Avg Prescriptions/Patient</div>
                    <i class="bi bi-graph-up" style="font-size: 28px; float: right; opacity: 0.3;"></i>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo date('M Y'); ?></div>
                    <div class="stat-label">Current Month</div>
                    <i class="bi bi-calendar3" style="font-size: 28px; float: right; opacity: 0.3;"></i>
                </div>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="main-card">
            <div class="card-header-custom">
                <h4><i class="bi bi-list-check"></i> Patient List</h4>
                <div>
                    <input type="text" id="searchInput" class="search-box" placeholder="🔍 Search patient...">
                </div>
            </div>
            <div class="table-responsive">
                <?php if($patients && $patients->num_rows > 0): ?>
                    <table class="patient-table" id="patientsTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Patient Information</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Registration Date</th>
                                <th>Prescriptions</th>
                                <th>Last Prescription</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $patients->fetch_assoc()): 
                                $initials = strtoupper(substr($row['FirstName'], 0, 1) . substr($row['LastName'], 0, 1));
                                $lastRx = $row['last_prescription_date'] ? date('M d, Y', strtotime($row['last_prescription_date'])) : 'Never';
                            ?>
                            <tr>
                                <td><span class="badge bg-secondary">#<?php echo $row['PatientID']; ?></span></td>
                                <td>
                                    <div class="patient-info">
                                        <div class="avatar"><?php echo $initials; ?></div>
                                        <div>
                                            <div class="patient-name"><?php echo htmlspecialchars($row['FirstName'] . ' ' . $row['LastName']); ?></div>
                                            <div class="patient-id">ID: <?php echo $row['PatientID']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="bi bi-telephone"></i> <?php echo $row['ContactNo']; ?><br>
                                    <small class="text-muted"><i class="bi bi-droplet"></i> <?php echo $row['BloodGroup'] ?: 'Blood group N/A'; ?></small>
                                </td>
                                <td>
                                    <?php if($row['Email']): ?>
                                        <i class="bi bi-envelope"></i> <?php echo htmlspecialchars($row['Email']); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="bi bi-calendar3"></i> <?php echo date('M d, Y', strtotime($row['RegistrationDate'])); ?>
                                </td>
                                <td>
                                    <span class="badge-prescription">
                                        <i class="bi bi-prescription2"></i> <?php echo $row['prescriptions_count']; ?> prescriptions
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted"><?php echo $lastRx; ?></small>
                                </td>
                                <td>
                                    <button class="btn-view" onclick="viewPatient(<?php echo $row['PatientID']; ?>)">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                    <!-- Prescribe button removed -->
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h4>No Patients Found</h4>
                        <p class="text-muted">There are no patients registered in the system yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            let searchValue = this.value.toLowerCase();
            let table = document.getElementById('patientsTable');
            let rows = table.getElementsByTagName('tr');
            
            for(let i = 1; i < rows.length; i++) {
                let row = rows[i];
                let text = row.textContent.toLowerCase();
                if(text.indexOf(searchValue) > -1) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
        
        function viewPatient(id) {
            window.open('nurse_view_patient.php?id=' + id, '_blank', 'width=1000,height=700');
        }
    </script>
</body>
</html>