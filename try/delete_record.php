<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $table = $_POST['table'];
    $id = $_POST['id'];
    $field = $_POST['field'];
    
    // For soft delete, update IsActive flag
    if ($table == 'Patient' || $table == 'Doctor' || $table == 'Treatment' || $table == 'LabTest') {
        $stmt = $conn->prepare("UPDATE $table SET IsActive = 0 WHERE $field = ?");
        $stmt->bind_param("i", $id);
    } else {
        $stmt = $conn->prepare("DELETE FROM $table WHERE $field = ?");
        $stmt->bind_param("i", $id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>