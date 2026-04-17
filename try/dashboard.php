<?php
require_once 'db_config.php';

// FAST LOADING: Simple direct queries without subqueries
$patientCount = 0;
$doctorCount = 0;
$todayCount = 0;
$pendingAmount = 0;

// Get patient count
$result = $conn->query("SELECT COUNT(*) as total FROM Patient WHERE IsActive = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $patientCount = $row['total'];
}

// Get doctor count
$result = $conn->query("SELECT COUNT(*) as total FROM Doctor WHERE IsActive = 1");
if ($result) {
    $row = $result->fetch_assoc();
    $doctorCount = $row['total'];
}

// Get today's appointments
$result = $conn->query("SELECT COUNT(*) as total FROM Appointment WHERE DATE(AppointmentDateTime) = CURDATE()");
if ($result) {
    $row = $result->fetch_assoc();
    $todayCount = $row['total'];
}

// Get pending amount
$result = $conn->query("SELECT SUM(TotalAmount - AmountPaid) as total FROM Billing WHERE PaymentStatus IN ('Pending', 'Partial')");
if ($result && $row = $result->fetch_assoc()) {
    $pendingAmount = $row['total'] ?? 0;
}

// Get recent 5 appointments only
$appointments = $conn->query("
    SELECT 
        a.AppointmentDateTime,
        a.Status,
        CONCAT(COALESCE(p.FirstName, ''), ' ', COALESCE(p.LastName, '')) as PatientName,
        CONCAT(COALESCE(d.FirstName, ''), ' ', COALESCE(d.LastName, '')) as DoctorName
    FROM Appointment a
    LEFT JOIN Patient p ON a.PatientID = p.PatientID
    LEFT JOIN Doctor d ON a.DoctorID = d.DoctorID
    ORDER BY a.AppointmentDateTime DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html>
<head>
    <style>
        .stat-box {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 28px;
            font-weight: bold;
            margin: 5px 0;
            color: #2c3e50;
        }
        .stat-label {
            color: #7f8c8d;
            font-size: 13px;
            margin: 0;
        }
        .recent-table {
            width: 100%;
            background: white;
            border-collapse: collapse;
        }
        .recent-table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        .recent-table td {
            padding: 10px;
            border-bottom: 1px solid #dee2e6;
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-scheduled { background: #ffc107; color: #000; }
        .badge-completed { background: #28a745; color: #fff; }
        .badge-cancelled { background: #dc3545; color: #fff; }
        .card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-top: 20px;
        }
        .card-header {
            padding: 12px 15px;
            border-bottom: 1px solid #dee2e6;
            font-weight: bold;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        .card-body {
            padding: 0;
        }
    </style>
</head>
<body>

<div style="padding: 10px;">
    <!-- Statistics Row -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="stat-box">
            <div class="stat-number"><?php echo $patientCount; ?></div>
            <div class="stat-label">Total Patients</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $doctorCount; ?></div>
            <div class="stat-label">Doctors</div>
        </div>
        <div class="stat-box">
            <div class="stat-number"><?php echo $todayCount; ?></div>
            <div class="stat-label">Today's Appointments</div>
        </div>
        <div class="stat-box">
            <div class="stat-number">$<?php echo number_format($pendingAmount, 2); ?></div>
            <div class="stat-label">Pending Amount</div>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="card">
        <div class="card-header">
            📋 Recent Appointments
        </div>
        <div class="card-body">
            <?php if ($appointments && $appointments->num_rows > 0): ?>
            <table class="recent-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $appointments->fetch_assoc()): 
                        $patientName = trim($row['PatientName']) ?: 'N/A';
                        $doctorName = trim($row['DoctorName']) ?: 'N/A';
                        $dateTime = date('M d, h:i A', strtotime($row['AppointmentDateTime']));
                        $statusClass = '';
                        switch($row['Status']) {
                            case 'Scheduled': $statusClass = 'badge-scheduled'; break;
                            case 'Completed': $statusClass = 'badge-completed'; break;
                            case 'Cancelled': $statusClass = 'badge-cancelled'; break;
                            default: $statusClass = 'badge-scheduled';
                        }
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($patientName); ?></td>
                        <td><?php echo htmlspecialchars($doctorName); ?></td>
                        <td><?php echo $dateTime; ?></td>
                        <td><span class="badge <?php echo $statusClass; ?>"><?php echo $row['Status']; ?></span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #999;">
                No appointments found
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>