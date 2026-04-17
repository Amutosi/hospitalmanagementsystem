<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';

if(isset($_GET['id']) && isset($_GET['status'])) {
    $id = $_GET['id'];
    $status = $_GET['status'];
    
    // Update treatment status
    $sql = "UPDATE PatientTreatment SET Status = '$status' WHERE PatientTreatmentID = $id";
    
    if($conn->query($sql)) {
        echo "<script>
            alert('Treatment status updated to $status successfully!');
            window.location.href = 'doctor_dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Error updating treatment: " . $conn->error . "');
            window.location.href = 'doctor_dashboard.php';
        </script>";
    }
} else {
    header("Location: doctor_dashboard.php");
}
?>