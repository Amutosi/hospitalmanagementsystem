<?php
require_once 'db_config.php';

header('Content-Type: application/json');

$type = isset($_GET['type']) ? $_GET['type'] : '';
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;

if(empty($type) || $patient_id == 0) {
    echo json_encode([]);
    exit;
}

$services = [];

if($type == 'Appointment') {
    $sql = "SELECT 
                a.AppointmentID as id, 
                CONCAT('Dr. ', d.FirstName, ' ', d.LastName, ' - ', DATE_FORMAT(a.AppointmentDateTime, '%Y-%m-%d %H:%i')) as name,
                d.ConsultationFee as price
            FROM Appointment a
            JOIN Doctor d ON a.DoctorID = d.DoctorID
            WHERE a.PatientID = $patient_id 
            AND a.Status = 'Completed'
            ORDER BY a.AppointmentDateTime DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $services[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => floatval($row['price'])
            ];
        }
    }
} elseif($type == 'Treatment') {
    $sql = "SELECT 
                pt.PatientTreatmentID as id, 
                CONCAT(t.TreatmentName, ' - Stage ', pt.SequenceOrder) as name,
                t.BaseCost as price
            FROM PatientTreatment pt
            JOIN Treatment t ON pt.TreatmentID = t.TreatmentID
            WHERE pt.PatientID = $patient_id 
            AND pt.Status = 'Completed'
            ORDER BY pt.StartDate DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $services[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => floatval($row['price'])
            ];
        }
    }
} elseif($type == 'LabTest') {
    $sql = "SELECT 
                plt.PatientLabTestID as id, 
                CONCAT(lt.TestName, ' - ', DATE_FORMAT(plt.OrderDate, '%Y-%m-%d')) as name,
                lt.StandardCost as price
            FROM PatientLabTest plt
            JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
            WHERE plt.PatientID = $patient_id 
            AND plt.Status = 'Completed'
            ORDER BY plt.OrderDate DESC
            LIMIT 50";
    
    $result = $conn->query($sql);
    if($result) {
        while($row = $result->fetch_assoc()) {
            $services[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'price' => floatval($row['price'])
            ];
        }
    }
}

// Also include services that are not yet billed (not in BillLineItem)
if($type == 'Appointment') {
    $sql2 = "SELECT 
                a.AppointmentID as id, 
                CONCAT('Dr. ', d.FirstName, ' ', d.LastName, ' - ', DATE_FORMAT(a.AppointmentDateTime, '%Y-%m-%d %H:%i')) as name,
                d.ConsultationFee as price
            FROM Appointment a
            JOIN Doctor d ON a.DoctorID = d.DoctorID
            WHERE a.PatientID = $patient_id 
            AND a.Status = 'Scheduled'
            ORDER BY a.AppointmentDateTime DESC
            LIMIT 50";
    
    $result2 = $conn->query($sql2);
    if($result2) {
        while($row = $result2->fetch_assoc()) {
            // Check if already billed
            $check = $conn->query("SELECT * FROM BillLineItem WHERE ServiceType = 'Appointment' AND ServiceID = {$row['id']}");
            if($check->num_rows == 0) {
                $services[] = [
                    'id' => $row['id'],
                    'name' => $row['name'] . ' (Pending)',
                    'price' => floatval($row['price'])
                ];
            }
        }
    }
}

echo json_encode($services);
?>