<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

// Get all patients who have visited this doctor
$patients = $conn->query("
    SELECT DISTINCT p.PatientID, p.FirstName, p.LastName, p.DateOfBirth, p.Gender, p.ContactNo, p.Email,
           (SELECT COUNT(*) FROM Appointment WHERE PatientID = p.PatientID AND DoctorID = $doctor_id) as visit_count,
           (SELECT MAX(AppointmentDateTime) FROM Appointment WHERE PatientID = p.PatientID AND DoctorID = $doctor_id) as last_visit
    FROM Appointment a
    JOIN Patient p ON a.PatientID = p.PatientID
    WHERE a.DoctorID = $doctor_id
    ORDER BY last_visit DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Patients - Doctor Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: url('https://images.unsplash.com/photo-1551076805-e1869033e561?auto=format&fit=crop&w=1600&q=80');
    background-size: cover;
    background-attachment: fixed;
    background-position: center;
    overflow-x: hidden;
}
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .btn-back { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); color: white; border: none; padding: 8px 20px; border-radius: 8px; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-people"></i> My Patients</h2>
            <a href="doctor_dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <div class="card">
            <div class="card-header">Patient List</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age/Gender</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Visits</th>
                                <th>Last Visit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $patients->fetch_assoc()): 
                                $age = date_diff(date_create($row['DateOfBirth']), date_create('today'))->y;
                            ?>
                            <tr>
                                <td><strong><?php echo $row['FirstName'] . ' ' . $row['LastName']; ?></strong></td>
                                <td><?php echo $age; ?> yrs / <?php echo $row['Gender']; ?></td>
                                <td><?php echo $row['ContactNo']; ?></td>
                                <td><?php echo $row['Email'] ?: '-'; ?></td>
                                <td><?php echo $row['visit_count']; ?> visits</td>
                                <td><?php echo $row['last_visit'] ? date('M d, Y', strtotime($row['last_visit'])) : '-'; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary" onclick="viewPatient(<?php echo $row['PatientID']; ?>)">
                                        View Profile
                                    </button>
                                    
                                </td>
                            </tr>
                            <td>
    <a href="doctor_prescribe_patient.php?patient_id=<?php echo $row['PatientID']; ?>" class="btn btn-sm btn-success">
        <i class="bi bi-prescription"></i> Prescribe
    </a>
</td>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function viewPatient(id) {
            window.open('doctor_view_patient.php?id=' + id, '_blank', 'width=900,height=700');
        }
        
        function bookAppointment(id) {
            window.location.href = 'doctor_book_appointment.php?patient_id=' + id;
        }
    </script>
</body>
</html>