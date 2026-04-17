<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

function isResultNormal($resultValue, $normalRange) {
    if(empty($normalRange) || $normalRange == 'N/A') return true;
    if(preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $normalRange, $matches)) {
        $min = floatval($matches[1]);
        $max = floatval($matches[2]);
        $value = floatval($resultValue);
        return ($value >= $min && $value <= $max);
    }
    return true;
}

// Get all lab tests with proper status
$lab_tests = $conn->query("
    SELECT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           p.ContactNo, p.DateOfBirth,
           lt.TestName, lt.NormalRange, lt.StandardCost,
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND DoctorID = $doctor_id AND LabTestID = plt.PatientLabTestID) as has_doctor_prescription_this,
           (SELECT COUNT(*) FROM PatientTreatment WHERE PatientID = p.PatientID AND PrescribedByDoctor = $doctor_id AND LabTestID = plt.PatientLabTestID) as has_doctor_treatment_this,
           (SELECT COUNT(*) FROM Prescription WHERE PatientID = p.PatientID AND NurseID IS NOT NULL) as has_any_nurse_prescription,
           (SELECT COUNT(*) FROM PatientTreatment WHERE PatientID = p.PatientID AND PrescribedByNurse IS NOT NULL) as has_any_nurse_treatment
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    WHERE plt.Status = 'Completed' 
    AND plt.ResultValue IS NOT NULL AND plt.ResultValue != ''
    ORDER BY plt.ResultDate DESC
    LIMIT 50
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Results - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
        .lab-card { transition: all 0.3s; border-left: 4px solid; background: white; border-radius: 20px; margin-bottom: 20px; overflow: hidden; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .lab-card.normal { border-left-color: #28a745; cursor: pointer; }
        .lab-card.abnormal { border-left-color: #dc3545; cursor: pointer; }
        .lab-card.acted { border-left-color: #6c757d; background: #e9ecef; cursor: default; opacity: 0.7; }
        .lab-card.locked { border-left-color: #ffc107; background: #fff3cd; cursor: not-allowed; opacity: 0.7; }
        .result-normal { color: #28a745; font-weight: bold; }
        .result-abnormal { color: #dc3545; font-weight: bold; }
        .badge-acted { background: #6c757d; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-locked { background: #ffc107; color: #856404; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .badge-pending { background: #17a2b8; color: white; padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: bold; display: inline-block; }
        .btn-back { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; padding: 8px 20px; border-radius: 50px; text-decoration: none; }
        .filter-buttons { margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 10px; }
        .filter-btn { padding: 8px 20px; border-radius: 50px; border: none; cursor: pointer; background: #e9ecef; transition: all 0.3s; font-weight: 500; }
        .filter-btn.active { background: #1e3c72; color: white; }
        .modal-content { border-radius: 20px; }
        .modal-header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border-radius: 20px 20px 0 0; }
        .form-control, .form-select { border-radius: 10px; border: 2px solid #e9ecef; padding: 10px 15px; }
        .btn-prescribe { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; }
        .btn-treatment { background: linear-gradient(135deg, #17a2b8 0%, #007bff 100%); color: white; border: none; padding: 10px 20px; border-radius: 50px; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1><i class="bi bi-flask"></i> Lab Test Results</h1>
                    <p class="text-muted mb-0">Review lab results and prescribe treatments/medications</p>
                </div>
                <a href="doctor_dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
            </div>
        </div>

        <!-- Filter Buttons -->
        <div class="filter-buttons">
            <button class="filter-btn active" data-filter="all">📋 All Results</button>
            <button class="filter-btn" data-filter="pending">⏳ Pending Action</button>
            <button class="filter-btn" data-filter="acted">✅ My Actions</button>
            <button class="filter-btn" data-filter="locked">🔒 Nurse Actions</button>
        </div>

        <div class="row" id="labResultsContainer">
            <?php if($lab_tests && $lab_tests->num_rows > 0): ?>
                <?php while($row = $lab_tests->fetch_assoc()): 
                    $isNormal = isResultNormal($row['ResultValue'], $row['NormalRange']);
                    
                    $hasDoctorActedThis = ($row['has_doctor_prescription_this'] > 0 || $row['has_doctor_treatment_this'] > 0);
                    $hasAnyNurseAction = ($row['has_any_nurse_prescription'] > 0 || $row['has_any_nurse_treatment'] > 0);
                    
                    if($hasAnyNurseAction) {
                        $cardClass = 'locked';
                        $statusText = '🔒 Locked - A nurse has already prescribed for this patient';
                        $canAct = false;
                    } elseif($hasDoctorActedThis) {
                        $cardClass = 'acted';
                        $statusText = '✅ You already acted on this lab result';
                        $canAct = false;
                    } else {
                        $cardClass = 'pending';
                        $statusText = $isNormal ? '📋 Normal - Ready for action' : '⚠️ Abnormal - Requires attention';
                        $canAct = true;
                    }
                    
                    $patientData = htmlspecialchars(json_encode([
                        'PatientLabTestID' => $row['PatientLabTestID'],
                        'PatientID' => $row['PatientID'],
                        'PatientName' => $row['PatientName'],
                        'ContactNo' => $row['ContactNo'],
                        'DateOfBirth' => $row['DateOfBirth'],
                        'TestName' => $row['TestName'],
                        'ResultValue' => $row['ResultValue'],
                        'NormalRange' => $row['NormalRange'],
                        'ResultDate' => $row['ResultDate'],
                        'CanAct' => $canAct
                    ]), ENT_QUOTES, 'UTF-8');
                ?>
                    <div class="col-md-6 col-lg-4 mb-3 lab-card-item" data-status="<?php echo $cardClass; ?>">
                        <div class="lab-card <?php echo $cardClass == 'pending' ? ($isNormal ? 'normal' : 'abnormal') : $cardClass; ?>" <?php echo $canAct ? 'onclick="openActionModal(' . $patientData . ')"' : ''; ?>>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($row['PatientName']); ?></h6>
                                        <small class="text-muted"><i class="bi bi-calendar"></i> <?php echo date('M d, Y', strtotime($row['ResultDate'])); ?></small>
                                    </div>
                                    <i class="bi bi-prescription2 fs-4 text-muted"></i>
                                </div>
                                <hr>
                                <div class="mb-2"><strong><?php echo htmlspecialchars($row['TestName']); ?></strong></div>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Result:</small>
                                        <div class="<?php echo $isNormal ? 'result-normal' : 'result-abnormal'; ?>">
                                            <?php echo htmlspecialchars($row['ResultValue']); ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Normal Range:</small>
                                        <div><?php echo htmlspecialchars($row['NormalRange'] ?: 'N/A'); ?></div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <?php if($hasAnyNurseAction): ?>
                                        <span class="badge-locked"><i class="bi bi-lock-fill"></i> <?php echo $statusText; ?></span>
                                    <?php elseif($hasDoctorActedThis): ?>
                                        <span class="badge-acted"><i class="bi bi-check-circle-fill"></i> <?php echo $statusText; ?></span>
                                    <?php else: ?>
                                        <span class="badge-pending"><i class="bi bi-hourglass-split"></i> <?php echo $statusText; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="card text-center">
                        <div class="card-body py-5">
                            <i class="bi bi-flask" style="font-size: 64px; color: #ccc;"></i>
                            <h4 class="mt-3">No Lab Results Found</h4>
                            <p class="text-muted">No lab results available.</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title"><i class="bi bi-prescription2"></i> Take Action Based on Lab Result</h5><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button></div><div class="modal-body" id="modalBody"></div></div></div></div>

    <script>
        // Filter function
        document.addEventListener('DOMContentLoaded', function() {
            const filterBtns = document.querySelectorAll('.filter-btn');
            const cards = document.querySelectorAll('.lab-card-item');
            
            filterBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    filterBtns.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    const filterValue = this.getAttribute('data-filter');
                    
                    cards.forEach(card => {
                        const cardStatus = card.getAttribute('data-status');
                        
                        if(filterValue === 'all') {
                            card.style.display = 'block';
                        } else if(filterValue === cardStatus) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            });
        });
        
        function openActionModal(labResult) {
            if(!labResult.CanAct) {
                Swal.fire({ 
                    icon: 'warning', 
                    title: 'Action Not Available', 
                    text: 'This patient has already been acted upon. You cannot prescribe or assign treatments for this patient.', 
                    confirmButtonColor: '#ffc107' 
                });
                return;
            }
            
            const isNormal = (() => { 
                if(!labResult.NormalRange) return true; 
                const match = labResult.NormalRange.match(/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/); 
                if(match) { 
                    const min = parseFloat(match[1]), max = parseFloat(match[2]), val = parseFloat(labResult.ResultValue); 
                    return val >= min && val <= max; 
                } 
                return true; 
            })();
            
            $('#modalBody').html(`
                <div class="row"><div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6><i class="bi bi-person"></i> Patient Information</h6><hr><p><strong>Name:</strong> ${labResult.PatientName}</p><p><strong>Contact:</strong> ${labResult.ContactNo || 'N/A'}</p><p><strong>DOB:</strong> ${labResult.DateOfBirth || 'N/A'}</p></div></div></div>
                <div class="col-md-6"><div class="card mb-3"><div class="card-body"><h6><i class="bi bi-flask"></i> Lab Result</h6><hr><p><strong>Test:</strong> ${labResult.TestName}</p><p><strong>Result:</strong> <span class="${isNormal ? 'text-success' : 'text-danger'} fw-bold">${labResult.ResultValue}</span></p><p><strong>Normal Range:</strong> ${labResult.NormalRange || 'N/A'}</p><p><strong>Date:</strong> ${new Date(labResult.ResultDate).toLocaleDateString()}</p></div></div></div></div>
                <div class="row mt-3"><div class="col-md-6"><div class="card"><div class="card-body"><h6><i class="bi bi-capsule"></i> Prescribe Medication</h6><hr><form id="prescriptionForm"><input type="hidden" name="patient_id" value="${labResult.PatientID}"><input type="hidden" name="lab_test_id" value="${labResult.PatientLabTestID}"><div class="mb-2"><label>Medication Name</label><input type="text" name="medication" class="form-control" required></div><div class="row"><div class="col-6"><label>Dosage</label><input type="text" name="dosage" class="form-control" placeholder="e.g., 500mg" required></div><div class="col-6"><label>Frequency</label><input type="text" name="frequency" class="form-control" placeholder="e.g., Twice daily" required></div></div><div class="row mt-2"><div class="col-6"><label>Duration</label><input type="text" name="duration" class="form-control" placeholder="e.g., 7 days" required></div></div><div class="mt-2"><label>Instructions</label><textarea name="instructions" class="form-control" rows="2" placeholder="Take with food, etc."></textarea></div><button type="submit" class="btn-prescribe w-100 mt-3">💊 Prescribe Medication</button></form></div></div></div>
                <div class="col-md-6"><div class="card"><div class="card-body"><h6><i class="bi bi-clipboard2-pulse"></i> Assign Treatment</h6><hr><form id="treatmentForm"><input type="hidden" name="patient_id" value="${labResult.PatientID}"><input type="hidden" name="lab_test_id" value="${labResult.PatientLabTestID}"><div class="mb-2"><label>Select Treatment</label><select name="treatment_id" class="form-control" required><option value="">-- Select Treatment --</option><?php $treatments = $conn->query("SELECT TreatmentID, TreatmentName, BaseCost FROM Treatment WHERE IsActive = 1"); while($t = $treatments->fetch_assoc()): ?><option value="<?php echo $t['TreatmentID']; ?>"><?php echo $t['TreatmentName']; ?> - $<?php echo number_format($t['BaseCost'], 2); ?></option><?php endwhile; ?></select></div><div class="row"><div class="col-6"><label>Stage/Sequence</label><input type="number" name="sequence" class="form-control" value="1" min="1" required></div><div class="col-6"><label>Start Date</label><input type="date" name="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div></div><div class="mt-2"><label>Notes</label><textarea name="notes" class="form-control" rows="2" placeholder="Based on lab results..."></textarea></div><button type="submit" class="btn-treatment w-100 mt-3">📋 Assign Treatment</button></form></div></div></div></div>
            `);
            
            $('#actionModal').modal('show');
            
            // FIXED: Prescription Form Handler
            // Prescription Form Handler - FIXED
$('#prescriptionForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    var formData = {
        patient_id: $(this).find('input[name="patient_id"]').val(),
        medication: $(this).find('input[name="medication"]').val(),
        dosage: $(this).find('input[name="dosage"]').val(),
        frequency: $(this).find('input[name="frequency"]').val(),
        duration: $(this).find('input[name="duration"]').val(),
        instructions: $(this).find('textarea[name="instructions"]').val(),
        lab_test_id: $(this).find('input[name="lab_test_id"]').val()
    };
    
    console.log('Sending prescription data:', formData);
    
    Swal.fire({
        title: 'Processing...',
        text: 'Saving prescription',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: 'doctor_prescribe_from_lab.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log('Prescription response:', response);
            
            if(response.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Success!', 
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => { 
                    $('#actionModal').modal('hide');
                    location.reload();
                });
            } else { 
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Failed', 
                    text: response.message 
                });
            }
        }, 
        error: function(xhr, status, error) {
            console.log('AJAX Error - Status:', status);
            console.log('Response Text:', xhr.responseText);
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: 'Failed to add prescription. Check console for details.' 
            });
        }
    });
});
            
            // FIXED: Treatment Form Handler
            // Treatment Form Handler - FIXED
$('#treatmentForm').off('submit').on('submit', function(e) {
    e.preventDefault();
    
    // Get form data
    var formData = {
        patient_id: $(this).find('input[name="patient_id"]').val(),
        treatment_id: $(this).find('select[name="treatment_id"]').val(),
        sequence: $(this).find('input[name="sequence"]').val(),
        start_date: $(this).find('input[name="start_date"]').val(),
        notes: $(this).find('textarea[name="notes"]').val(),
        lab_test_id: $(this).find('input[name="lab_test_id"]').val()
    };
    
    console.log('Sending treatment data:', formData);
    
    Swal.fire({
        title: 'Processing...',
        text: 'Assigning treatment',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    $.ajax({
        url: 'doctor_assign_treatment.php',
        type: 'POST',
        data: formData,
        dataType: 'json',
        success: function(response) {
            console.log('Treatment response:', response);
            
            if(response.success) {
                Swal.fire({ 
                    icon: 'success', 
                    title: 'Success!', 
                    text: response.message,
                    timer: 1500,
                    showConfirmButton: false
                }).then(() => { 
                    $('#actionModal').modal('hide');
                    location.reload();
                });
            } else { 
                Swal.fire({ 
                    icon: 'error', 
                    title: 'Failed', 
                    text: response.message 
                });
            }
        }, 
        error: function(xhr, status, error) {
            console.log('AJAX Error - Status:', status);
            console.log('Response Text:', xhr.responseText);
            Swal.fire({ 
                icon: 'error', 
                title: 'Error', 
                text: 'Failed to assign treatment. Check console for details.' 
            });
        }
    });
});
        }
    </script>
</body>
</html>