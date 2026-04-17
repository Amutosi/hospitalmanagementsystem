<?php
require_once 'db_config.php';

if (!isset($_GET['id'])) {
    die("Patient ID not provided");
}

$stmt = $conn->prepare("SELECT * FROM Patient WHERE PatientID = ?");
$stmt->bind_param("i", $_GET['id']);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    die("Patient not found");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Patient Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4>Patient Details</h4>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr><th>Patient ID:</th><td><?php echo $patient['PatientID']; ?></td></tr>
                    <tr><th>Name:</th><td><?php echo htmlspecialchars($patient['FirstName'] . ' ' . $patient['LastName']); ?></td></tr>
                    <tr><th>Date of Birth:</th><td><?php echo $patient['DateOfBirth']; ?></td></tr>
                    <tr><th>Gender:</th><td><?php echo $patient['Gender']; ?></td></tr>
                    <tr><th>Contact:</th><td><?php echo $patient['ContactNo']; ?></td></tr>
                    <tr><th>Email:</th><td><?php echo $patient['Email']; ?></td></tr>
                    <tr><th>Address:</th><td><?php echo nl2br(htmlspecialchars($patient['Address'])); ?></td></tr>
                    <tr><th>Registration Date:</th><td><?php echo $patient['RegistrationDate']; ?></td></tr>
                </table>
                <button class="btn btn-secondary" onclick="window.close()">Close</button>
            </div>
        </div>
    </div>
</body>
</html>