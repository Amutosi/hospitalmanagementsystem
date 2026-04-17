<?php
function canPrescribe($conn, $patient_id, $role) {
    // Check if patient has any existing prescriptions
    $check = $conn->query("
        SELECT 
            COUNT(*) as total_prescriptions,
            SUM(CASE WHEN DoctorID IS NOT NULL THEN 1 ELSE 0 END) as doctor_prescriptions,
            SUM(CASE WHEN NurseID IS NOT NULL THEN 1 ELSE 0 END) as nurse_prescriptions
        FROM Prescription 
        WHERE PatientID = $patient_id
    ");
    
    $result = $check->fetch_assoc();
    
    if($result['total_prescriptions'] == 0) {
        return ['can_prescribe' => true, 'message' => 'No existing prescriptions'];
    }
    
    if($role == 'Doctor') {
        if($result['nurse_prescriptions'] > 0) {
            return ['can_prescribe' => false, 'message' => 'This patient already has prescriptions from a Nurse. Doctors cannot prescribe for this patient.'];
        }
        return ['can_prescribe' => true, 'message' => 'Doctor can prescribe'];
    }
    
    if($role == 'Nurse') {
        if($result['doctor_prescriptions'] > 0) {
            return ['can_prescribe' => false, 'message' => 'This patient already has prescriptions from a Doctor. Nurses cannot prescribe for this patient.'];
        }
        return ['can_prescribe' => true, 'message' => 'Nurse can prescribe'];
    }
    
    return ['can_prescribe' => true, 'message' => 'OK'];
}
?>