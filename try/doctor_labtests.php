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

// Get lab tests ordered by this doctor
$lab_tests_ordered = $conn->query("
    SELECT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           p.ContactNo, p.DateOfBirth,
           lt.TestName, lt.NormalRange, lt.StandardCost,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    LEFT JOIN Doctor d ON plt.OrderedByDoctorID = d.DoctorID
    WHERE plt.OrderedByDoctorID = $doctor_id
    ORDER BY plt.OrderDate DESC
");

// Get lab tests for patients of this doctor (appointments)
$lab_tests_for_patients = $conn->query("
    SELECT DISTINCT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           p.ContactNo, p.DateOfBirth,
           lt.TestName, lt.NormalRange, lt.StandardCost,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    LEFT JOIN Doctor d ON plt.OrderedByDoctorID = d.DoctorID
    WHERE p.PatientID IN (
        SELECT DISTINCT PatientID FROM Appointment WHERE DoctorID = $doctor_id
    )
    ORDER BY plt.OrderDate DESC
");

// Get all completed lab tests for patients of this doctor
$completed_lab_tests = $conn->query("
    SELECT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           p.ContactNo, p.DateOfBirth,
           lt.TestName, lt.NormalRange, lt.StandardCost,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName,
           CASE 
               WHEN plt.ResultValue IS NOT NULL AND plt.ResultValue != '' THEN 'Completed'
               ELSE 'Pending'
           END as TestStatus
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    LEFT JOIN Doctor d ON plt.OrderedByDoctorID = d.DoctorID
    WHERE (plt.OrderedByDoctorID = $doctor_id OR p.PatientID IN (SELECT DISTINCT PatientID FROM Appointment WHERE DoctorID = $doctor_id))
    ORDER BY plt.OrderDate DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Tests - Doctor Portal</title>
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
        .container-fluid { max-width: 1400px; margin: 0 auto; }
        .page-header { background: white; border-radius: 20px; padding: 25px 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .page-header h1 { font-size: 28px; font-weight: 700; background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .stat-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; border-left: 4px solid; }
        .stat-card.primary { border-left-color: #3498db; }
        .stat-card.success { border-left-color: #2ecc71; }
        .stat-card.warning { border-left-color: #f39c12; }
        .stat-number { font-size: 32px; font-weight: 800; }
        .card { border: none; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin-bottom: 20px; overflow: hidden; }
        .card-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; padding: 15px 20px; font-weight: 600; }
        .nav-tabs { border-bottom: none; gap: 10px; }
        .nav-tabs .nav-link { border: none; border-radius: 50px; padding: 10px 25px; color: #555; background: #e9ecef; margin-right: 10px; }
        .nav-tabs .nav-link.active { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; }
        .badge-pending { background: #f39c12; color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; }
        .badge-completed { background: #2ecc71; color: white; padding: 5px 12px; border-radius: 20px; font-size: 11px; }
        .btn-back { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; padding: 8px 20px; border-radius: 50px; text-decoration: none; }
        .btn-back:hover { color: white; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-flask"></i> Lab Tests</h1>
                    <p class="text-muted mb-0">View lab tests ordered for your patients</p>
                </div>
                <a href="doctor_dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card primary">
                    <div class="stat-number"><?php echo $lab_tests_ordered->num_rows; ?></div>
                    <div>Tests Ordered by You</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card success">
                    <div class="stat-number"><?php echo $lab_tests_for_patients->num_rows; ?></div>
                    <div>Tests for Your Patients</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card warning">
                    <div class="stat-number"><?php echo $completed_lab_tests->num_rows; ?></div>
                    <div>Total Lab Tests</div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="labTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#ordered" type="button" role="tab">📋 Ordered by Me</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#patients" type="button" role="tab">👨‍⚕️ My Patients' Tests</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#completed" type="button" role="tab">✅ Completed Results</button>
            </li>
        </ul>

        <div class="tab-content">
            <!-- Tab 1: Ordered by Doctor -->
            <div class="tab-pane fade show active" id="ordered" role="tabpanel">
                <div class="card">
                    <div class="card-header">📋 Lab Tests Ordered by You</div>
                    <div class="card-body">
                        <?php if($lab_tests_ordered && $lab_tests_ordered->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Patient</th><th>Test Name</th><th>Order Date</th><th>Status</th><th>Result</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $lab_tests_ordered->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['PatientName']; ?></td>
                                            <td><?php echo $row['TestName']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['OrderDate'])); ?></td>
                                            <td><?php echo $row['Status'] == 'Completed' ? '<span class="badge-completed">Completed</span>' : '<span class="badge-pending">Pending</span>'; ?></td>
                                            <td><?php echo $row['ResultValue'] ?: 'Awaiting results'; ?></td>
                                            <td>
                                                <?php if($row['Status'] == 'Completed'): ?>
                                                    <button class="btn btn-sm btn-info" onclick="viewResult(<?php echo $row['PatientLabTestID']; ?>)">View Result</button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No lab tests ordered by you yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab 2: My Patients' Tests -->
            <div class="tab-pane fade" id="patients" role="tabpanel">
                <div class="card">
                    <div class="card-header">👨‍⚕️ Lab Tests for Your Patients</div>
                    <div class="card-body">
                        <?php if($lab_tests_for_patients && $lab_tests_for_patients->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Patient</th><th>Test Name</th><th>Ordered By</th><th>Order Date</th><th>Status</th><th>Result</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $lab_tests_for_patients->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $row['PatientName']; ?></td>
                                            <td><?php echo $row['TestName']; ?></td>
                                            <td><?php echo $row['DoctorName'] ?: 'Admin'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['OrderDate'])); ?></td>
                                            <td><?php echo $row['Status'] == 'Completed' ? '<span class="badge-completed">Completed</span>' : '<span class="badge-pending">Pending</span>'; ?></td>
                                            <td><?php echo $row['ResultValue'] ?: 'Awaiting results'; ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No lab tests found for your patients.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Tab 3: Completed Results -->
            <div class="tab-pane fade" id="completed" role="tabpanel">
                <div class="card">
                    <div class="card-header">✅ Completed Lab Test Results</div>
                    <div class="card-body">
                        <?php if($completed_lab_tests && $completed_lab_tests->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr><th>Patient</th><th>Test Name</th><th>Order Date</th><th>Result Date</th><th>Result</th><th>Normal Range</th><th>Action</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $completed_lab_tests->fetch_assoc()): ?>
                                            <?php if($row['TestStatus'] == 'Completed'): ?>
                                            <tr>
                                                <td><?php echo $row['PatientName']; ?></td>
                                                <td><?php echo $row['TestName']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($row['OrderDate'])); ?></td>
                                                <td><?php echo $row['ResultDate'] ? date('M d, Y', strtotime($row['ResultDate'])) : '-'; ?></td>
                                                <td class="<?php echo isResultAbnormal($row['ResultValue'], $row['NormalRange']) ? 'text-danger fw-bold' : 'text-success'; ?>">
                                                    <?php echo $row['ResultValue']; ?>
                                                </td>
                                                <td><?php echo $row['NormalRange'] ?: 'N/A'; ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" onclick="viewResult(<?php echo $row['PatientLabTestID']; ?>)">
                                                        <i class="bi bi-eye"></i> View Report
                                                    </button>
                                                    <button class="btn btn-sm btn-success" onclick="prescribeFromResult(<?php echo $row['PatientLabTestID']; ?>, '<?php echo $row['PatientName']; ?>', '<?php echo $row['TestName']; ?>', '<?php echo addslashes($row['ResultValue']); ?>')">
                                                        <i class="bi bi-prescription"></i> Prescribe
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endif; ?>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">No completed lab test results yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function viewResult(id) {
            window.open('view_lab_report.php?id=' + id, '_blank', 'width=900,height=700');
        }
        
        function prescribeFromResult(testId, patientName, testName, resultValue) {
            Swal.fire({
                title: 'Prescribe Medication',
                html: `
                    <div class="text-start">
                        <p><strong>Patient:</strong> ${patientName}</p>
                        <p><strong>Lab Test:</strong> ${testName}</p>
                        <p><strong>Result:</strong> ${resultValue}</p>
                        <hr>
                        <label>Medication Name:</label>
                        <input type="text" id="medication" class="form-control mb-2">
                        <label>Dosage:</label>
                        <input type="text" id="dosage" class="form-control mb-2">
                        <label>Frequency:</label>
                        <input type="text" id="frequency" class="form-control mb-2">
                        <label>Duration:</label>
                        <input type="text" id="duration" class="form-control mb-2">
                        <label>Instructions:</label>
                        <textarea id="instructions" class="form-control" rows="2"></textarea>
                        <input type="hidden" id="lab_test_id" value="${testId}">
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Prescribe',
                preConfirm: () => {
                    return {
                        medication: document.getElementById('medication').value,
                        dosage: document.getElementById('dosage').value,
                        frequency: document.getElementById('frequency').value,
                        duration: document.getElementById('duration').value,
                        instructions: document.getElementById('instructions').value,
                        lab_test_id: document.getElementById('lab_test_id').value
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'doctor_prescribe_from_lab.php',
                        type: 'POST',
                        data: {
                            patient_id: <?php echo isset($row) ? $row['PatientID'] : '0'; ?>,
                            medication: result.value.medication,
                            dosage: result.value.dosage,
                            frequency: result.value.frequency,
                            duration: result.value.duration,
                            instructions: result.value.instructions,
                            lab_test_id: result.value.lab_test_id
                        },
                        success: function(response) {
                            Swal.fire('Success', 'Prescription added successfully!', 'success');
                            location.reload();
                        },
                        error: function() {
                            Swal.fire('Error', 'Failed to add prescription', 'error');
                        }
                    });
                }
            });
        }
        
        function isResultAbnormal(result, normalRange) {
            if(!normalRange) return false;
            // Simple parsing for numeric ranges
            if(normalRange.includes('-')) {
                let parts = normalRange.split('-');
                let min = parseFloat(parts[0]);
                let max = parseFloat(parts[1]);
                let val = parseFloat(result);
                return val < min || val > max;
            }
            // For text results like "Negative", "Positive"
            if(normalRange.toLowerCase() === 'negative' && result.toLowerCase() === 'positive') return true;
            if(normalRange.toLowerCase() === 'positive' && result.toLowerCase() === 'negative') return true;
            return false;
        }
    </script>
</body>
</html>