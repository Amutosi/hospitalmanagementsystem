<?php
require_once 'db_config.php';

if (isset($_GET['id']) && isset($_GET['status'])) {
    $stmt = $conn->prepare("UPDATE Appointment SET Status = ? WHERE AppointmentID = ?");
    $stmt->bind_param("si", $_GET['status'], $_GET['id']);
    $stmt->execute();
    header("Location: index.php?page=appointments");
}
?>