<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

if(!isset($_GET['id'])) {
    die("Patient ID not provided");
}

$patient_id = $_GET['id'];
$nurse_id = $_SESSION['nurse_id'];

// Get patient details
$patient = $conn->query("SELECT * FROM Patient WHERE PatientID = $patient_id AND IsActive = 1")->fetch_assoc();

if(!$patient) {
    die("Patient not found");
}

// Get prescriptions given by this nurse to this patient
$prescriptions = $conn->query("
    SELECT p.* 
    FROM Prescription p
    WHERE p.PatientID = $patient_id AND p.NurseID = $nurse_id
    ORDER BY p.PrescriptionDate DESC
");

// Get treatments assigned by this nurse to this patient
$treatments = $conn->query("
    SELECT pt.*, t.TreatmentName, t.BaseCost
    FROM PatientTreatment pt
    JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
    WHERE pt.PatientID = $patient_id AND pt.PrescribedByNurse = $nurse_id
    ORDER BY pt.StartDate DESC
");

// Get all appointments for this patient
$appointments = $conn->query("
    SELECT a.*, CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM Appointment a
    JOIN Doctor d ON a.DoctorID = d.DoctorID
    WHERE a.PatientID = $patient_id
    ORDER BY a.AppointmentDateTime DESC
");

// Get lab tests for this patient
$labtests = $conn->query("
    SELECT plt.*, lt.TestName, lt.NormalRange
    FROM PatientLabTest plt
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    WHERE plt.PatientID = $patient_id
    ORDER BY plt.OrderDate DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Details - Nurse Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            overflow: hidden;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 25px;
            font-weight: 600;
            font-size: 18px;
        }
        .card-header i {
            margin-right: 10px;
        }
        .info-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .info-label {
            width: 150px;
            font-weight: 600;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
        .avatar-large {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
        }
        .avatar-large i {
            font-size: 50px;
            color: white;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-completed { background: #28a745; color: white; }
        .status-scheduled { background: #ffc107; color: #000; }
        .status-cancelled { background: #dc3545; color: white; }
        .status-pending { background: #17a2b8; color: white; }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e9ecef;
        }
        .table-custom {
            width: 100%;
        }
        .table-custom thead th {
            background: #f8f9fa;
            padding: 12px;
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }
        .table-custom tbody td {
            padding: 12px;
            vertical-align: middle;
        }
        @media (max-width: 768px) {
            .info-row {
                flex-direction: column;
            }
            .info-label {
                width: 100%;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Fixed Back Button -->
        <div class="mb-3">
            <a href="nurse_patients_list.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Back to Patients List
            </a>
            <button onclick="window.print()" class="btn-back" style="background: #17a2b8; margin-left: 10px;">
                <i class="bi bi-printer"></i> Print
            </button>
            <button onclick="window.close()" class="btn-back" style="background: #6c757d; margin-left: 10px;">
                <i class="bi bi-x-circle"></i> Close
            </button>
        </div>

        <!-- Patient Profile Card -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person-circle"></i> Patient Profile
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <div class="avatar-large">
                            <i class="bi bi-person"></i>
                        </div>
                        <h4 class="mt-2"><?php echo htmlspecialchars($patient['FirstName'] . ' ' . $patient['LastName']); ?></h4>
                        <span class="badge bg-secondary">ID: <?php echo $patient['PatientID']; ?></span>
                    </div>
                    <div class="col-md-8">
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-calendar3"></i> Date of Birth:</div>
                            <div class="info-value"><?php echo date('F d, Y', strtotime($patient['DateOfBirth'])); ?> 
                                (Age: <?php echo date_diff(date_create($patient['DateOfBirth']), date_create('today'))->y; ?> years)
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-gender-ambiguous"></i> Gender:</div>
                            <div class="info-value"><?php echo $patient['Gender']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-telephone"></i> Contact:</div>
                            <div class="info-value"><?php echo $patient['ContactNo']; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-envelope"></i> Email:</div>
                            <div class="info-value"><?php echo $patient['Email'] ?: 'Not provided'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-droplet"></i> Blood Group:</div>
                            <div class="info-value"><?php echo $patient['BloodGroup'] ?: 'Not recorded'; ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-house"></i> Address:</div>
                            <div class="info-value"><?php echo nl2br($patient['Address'] ?: 'Not provided'); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label"><i class="bi bi-calendar-check"></i> Registered:</div>
                            <div class="info-value"><?php echo date('F d, Y', strtotime($patient['RegistrationDate'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Emergency Contact Card -->
        <?php if($patient['EmergencyContactName'] || $patient['EmergencyContactNo']): ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-exclamation-triangle"></i> Emergency Contact
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Contact Name:</strong> <?php echo $patient['EmergencyContactName'] ?: 'N/A'; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Contact Number:</strong> <?php echo $patient['EmergencyContactNo'] ?: 'N/A'; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Prescriptions Given by This Nurse -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-prescription2"></i> Prescriptions Given by You
            </div>
            <div class="card-body">
                <?php if($prescriptions && $prescriptions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Medication</th>
                                    <th>Dosage</th>
                                    <th>Frequency</th>
                                    <th>Duration</th>
                                    <th>Instructions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $prescriptions->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($row['PrescriptionDate'])); ?></td>
                                    <td><?php echo htmlspecialchars($row['MedicationName']); ?></td>
                                    <td><?php echo $row['Dosage']; ?></td>
                                    <td><?php echo $row['Frequency']; ?></td>
                                    <td><?php echo $row['Duration']; ?></td>
                                    <td><?php echo $row['Instructions'] ?: '-'; ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No prescriptions given by you for this patient.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Treatments Assigned by This Nurse -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clipboard2-pulse"></i> Treatments Assigned by You
            </div>
            <div class="card-body">
                <?php if($treatments && $treatments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Treatment</th>
                                    <th>Stage</th>
                                    <th>Start Date</th>
                                    <th>End Date</th>
                                    <th>Status</th>
                                    <th>Cost</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $treatments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['TreatmentName']); ?></td>
                                    <td>Stage <?php echo $row['SequenceOrder']; ?></td>
                                    <td><?php echo $row['StartDate']; ?></td>
                                    <td><?php echo $row['EndDate'] ?: 'Ongoing'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                    <td>$<?php echo number_format($row['BaseCost'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No treatments assigned by you for this patient.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Appointment History -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-check"></i> Appointment History
            </div>
            <div class="card-body">
                <?php if($appointments && $appointments->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Doctor</th>
                                    <th>Purpose</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $appointments->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y h:i A', strtotime($row['AppointmentDateTime'])); ?></td>
                                    <td>Dr. <?php echo $row['DoctorName']; ?></td>
                                    <td><?php echo $row['Purpose'] ?: '-'; ?></td>
                                    <td><span class="status-badge status-<?php echo strtolower($row['Status']); ?>"><?php echo $row['Status']; ?></span></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No appointment history found.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Lab Tests -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-flask"></i> Lab Tests
            </div>
            <div class="card-body">
                <?php if($labtests && $labtests->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-custom">
                            <thead>
                                <tr>
                                    <th>Test Name</th>
                                    <th>Order Date</th>
                                    <th>Result Date</th>
                                    <th>Result</th>
                                    <th>Normal Range</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $labtests->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $row['TestName']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($row['OrderDate'])); ?></td>
                                    <td><?php echo $row['ResultDate'] ? date('M d, Y', strtotime($row['ResultDate'])) : 'Pending'; ?></td>
                                    <td><?php echo $row['ResultValue'] ?: 'Awaiting'; ?></td>
                                    <td><?php echo $row['NormalRange'] ?: 'N/A'; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $row['Status'] == 'Completed' ? 'completed' : 'pending'; ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-center py-3">No lab tests found.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>