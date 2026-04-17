<?php
require_once 'db_config.php';

if (isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE LabTest SET IsActive = 0 WHERE LabTestID = ?");
    $stmt->bind_param("i", $_GET['id']);
    $stmt->execute();
    header("Location: index.php?page=labtests");
}
?>