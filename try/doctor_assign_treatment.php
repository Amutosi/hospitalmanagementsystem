<?php
// Ensure clean JSON output
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');

session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get POST data
    $patient_id = isset($_POST['patient_id']) ? $_POST['patient_id'] : 0;
    $treatment_id = isset($_POST['treatment_id']) ? $_POST['treatment_id'] : 0;
    $sequence = isset($_POST['sequence']) ? $_POST['sequence'] : 1;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : date('Y-m-d');
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    $lab_test_id = isset($_POST['lab_test_id']) ? $_POST['lab_test_id'] : 0;
    
    // Validate
    if($patient_id == 0 || $treatment_id == 0) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }
    
    // Check if already assigned
    $check = $conn->query("
        SELECT COUNT(*) as count FROM PatientTreatment 
        WHERE PatientID = $patient_id AND PrescribedByDoctor = $doctor_id AND LabTestID = $lab_test_id
    ");
    
    if($check && $check->num_rows > 0) {
        $existing = $check->fetch_assoc()['count'];
        if($existing > 0) {
            echo json_encode(['success' => false, 'message' => 'You have already assigned a treatment for this lab result']);
            exit();
        }
    }
    
    // Insert treatment
    $sql = "INSERT INTO PatientTreatment (PatientID, TreatmentID, StartDate, SequenceOrder, Status, Notes, PrescribedByDoctor, LabTestID) 
            VALUES ($patient_id, $treatment_id, '$start_date', $sequence, 'Scheduled', '$notes', $doctor_id, $lab_test_id)";
    
    if($conn->query($sql)) {
        $conn->query("UPDATE PatientLabTest SET ReviewedByDoctor = 1, ReviewDate = NOW() WHERE PatientLabTestID = $lab_test_id");
        echo json_encode(['success' => true, 'message' => 'Treatment assigned successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    }
    exit();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}
?>