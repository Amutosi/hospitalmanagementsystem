<?php
session_start();
require_once 'db_config.php';

if(!isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

if(isset($_POST['nurse_id'])) {
    $nurse_id = $_POST['nurse_id'];
    $shift_date = $_POST['shift_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $shift_type = $_POST['shift_type'];
    $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : 'NULL';
    $notes = $_POST['notes'];
    
    $dept_value = ($department_id != 'NULL') ? "'$department_id'" : "NULL";
    
    $sql = "INSERT INTO NurseSchedule (NurseID, ShiftDate, StartTime, EndTime, ShiftType, AssignedDepartmentID, Notes) 
            VALUES ('$nurse_id', '$shift_date', '$start_time', '$end_time', '$shift_type', $dept_value, '$notes')";
    
    if($conn->query($sql)) {
        echo "<script>
            alert('Schedule added successfully!');
            window.location.href = 'nurse_dashboard.php';
        </script>";
    } else {
        echo "<script>
            alert('Error: " . $conn->error . "');
            window.location.href = 'nurse_dashboard.php';
        </script>";
    }
} else {
    header("Location: nurse_dashboard.php");
}
?>