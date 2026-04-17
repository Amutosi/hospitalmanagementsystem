<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing doctor_assign_treatment.php<br><br>";

// Set session
session_start();
$_SESSION['doctor_logged_in'] = true;
$_SESSION['doctor_id'] = 1; // Change to your actual doctor ID

require_once 'db_config.php';

// Get a valid patient, treatment, and lab test
$patient = $conn->query("SELECT PatientID FROM Patient LIMIT 1")->fetch_assoc();
$treatment = $conn->query("SELECT TreatmentID FROM Treatment LIMIT 1")->fetch_assoc();
$labtest = $conn->query("SELECT PatientLabTestID FROM PatientLabTest LIMIT 1")->fetch_assoc();

echo "Patient ID: " . $patient['PatientID'] . "<br>";
echo "Treatment ID: " . $treatment['TreatmentID'] . "<br>";
echo "Lab Test ID: " . $labtest['PatientLabTestID'] . "<br><br>";

// Prepare POST data
$_POST['patient_id'] = $patient['PatientID'];
$_POST['treatment_id'] = $treatment['TreatmentID'];
$_POST['sequence'] = 1;
$_POST['start_date'] = date('Y-m-d');
$_POST['notes'] = 'Test treatment';
$_POST['lab_test_id'] = $labtest['PatientLabTestID'];

// Capture output
ob_start();
include 'doctor_assign_treatment.php';
$output = ob_get_clean();

echo "Raw output:<br>";
echo "<pre>" . htmlspecialchars($output) . "</pre><br>";

// Try to decode JSON
$json = json_decode($output, true);
if($json) {
    echo "JSON parsed successfully:<br>";
    print_r($json);
} else {
    echo "Failed to parse JSON. Last error: " . json_last_error_msg();
}
?>