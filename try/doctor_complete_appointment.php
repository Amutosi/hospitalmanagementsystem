<?php
session_start();
require_once 'db_config.php';

if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

if(isset($_GET['id'])) {
    $id = $_GET['id'];
    $conn->query("UPDATE Appointment SET Status = 'Completed' WHERE AppointmentID = $id");
    header("Location: doctor_appointments.php");
}
?>