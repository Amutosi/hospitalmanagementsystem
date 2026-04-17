<?php
require_once 'db_config.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $stmt = $conn->prepare("UPDATE PatientTreatment SET Status = ? WHERE PatientTreatmentID = ?");
    $stmt->bind_param("si", $_GET['status'], $_GET['id']);
    $stmt->execute();
    header("Location: index.php?page=treatments");
}
?>