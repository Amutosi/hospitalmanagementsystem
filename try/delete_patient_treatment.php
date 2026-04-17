<?php
require_once 'db_config.php';

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM PatientTreatment WHERE PatientTreatmentID = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    header("Location: index.php?page=treatments");
}
?>