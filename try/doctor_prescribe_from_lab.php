<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    $lab_test_id = $_POST['lab_test_id'];
    
    // Check if THIS doctor already prescribed for THIS lab test
    $check = $conn->query("
        SELECT COUNT(*) as count FROM Prescription 
        WHERE PatientID = $patient_id AND DoctorID = $doctor_id AND LabTestID = $lab_test_id
    ");
    $existing = $check->fetch_assoc()['count'];
    
    if($existing > 0) {
        echo json_encode([
            'success' => false, 
            'message' => 'You have already prescribed for this lab result'
        ]);
        exit();
    }
    
    // Allow doctor to prescribe (no nurse lock check)
    $sql = "INSERT INTO Prescription (PatientID, DoctorID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions, LabTestID) 
            VALUES ('$patient_id', '$doctor_id', NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions', '$lab_test_id')";
    
    if($conn->query($sql)) {
        $conn->query("UPDATE PatientLabTest SET ReviewedByDoctor = 1, ReviewDate = NOW() WHERE PatientLabTestID = $lab_test_id");
        echo json_encode(['success' => true, 'message' => 'Prescription added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>