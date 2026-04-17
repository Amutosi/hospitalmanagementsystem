<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

require_once 'db_config.php';
$nurse_id = $_SESSION['nurse_id'];

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patient_id = $_POST['patient_id'];
    $medication = $_POST['medication'];
    $dosage = $_POST['dosage'];
    $frequency = $_POST['frequency'];
    $duration = $_POST['duration'];
    $instructions = $_POST['instructions'];
    $lab_test_id = $_POST['lab_test_id'];
    
    // Check if this patient already has doctor prescriptions
    $check_doctor = $conn->query("
        SELECT COUNT(*) as count FROM Prescription 
        WHERE PatientID = $patient_id AND DoctorID IS NOT NULL
    ");
    $doctor_count = $check_doctor->fetch_assoc()['count'];
    
    if($doctor_count > 0) {
        echo json_encode([
            'success' => false, 
            'message' => '❌ This patient already has prescriptions from a Doctor. Nurses cannot prescribe for this patient.'
        ]);
        exit();
    }
    
    // Insert prescription
    $sql = "INSERT INTO Prescription (PatientID, NurseID, PrescriptionDate, MedicationName, Dosage, Frequency, Duration, Instructions, LabTestID) 
            VALUES ('$patient_id', '$nurse_id', NOW(), '$medication', '$dosage', '$frequency', '$duration', '$instructions', '$lab_test_id')";
    
    if($conn->query($sql)) {
        $conn->query("UPDATE PatientLabTest SET ReviewedByNurse = 1, ReviewDate = NOW() WHERE PatientLabTestID = $lab_test_id");
        echo json_encode(['success' => true, 'message' => 'Prescription added successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>