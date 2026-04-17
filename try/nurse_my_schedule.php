<?php
session_start();
if(!isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$nurse_id = $_SESSION['nurse_id'];

// Get nurse info
$nurse = $conn->query("SELECT * FROM Nurse WHERE NurseID = $nurse_id")->fetch_assoc();

// Handle Delete Schedule
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    if($conn->query("DELETE FROM NurseSchedule WHERE ScheduleID = $id AND NurseID = $nurse_id")) {
        echo "<script>alert('Schedule deleted successfully!'); window.location.href='nurse_my_schedule.php';</script>";
    } else {
        echo "<script>alert('Error deleting schedule: " . $conn->error . "');</script>";
    }
}

// Get all schedules for this nurse
$schedules = $conn->query("
    SELECT s.*, d.DeptName 
    FROM NurseSchedule s
    LEFT JOIN Department d ON s.AssignedDepartmentID = d.DepartmentID
    WHERE s.NurseID = $nurse_id
    ORDER BY s.ShiftDate DESC, s.StartTime ASC
");

// Get upcoming schedules
$upcoming = $conn->query("
    SELECT s.*, d.DeptName 
    FROM NurseSchedule s
    LEFT JOIN Department d ON s.AssignedDepartmentID = d.DepartmentID
    WHERE s.NurseID = $nurse_id AND s.ShiftDate >= CURDATE()
    ORDER BY s.ShiftDate ASC, s.StartTime ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Nurse Portal</title>
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
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background: white;
            border-bottom: 2px solid #f0f0f0;
            padding: 15px 20px;
            font-weight: bold;
            border-radius: 15px 15px 0 0;
        }
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 8px 20px;
            text-decoration: none;
        }
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
            color: white;
        }
        .badge-morning { background: #ffc107; color: #000; }
        .badge-evening { background: #17a2b8; color: #fff; }
        .badge-night { background: #6c757d; color: #fff; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar-week"></i> My Work Schedule</h2>
            <a href="nurse_dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Upcoming Schedules -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-star-fill"></i> Upcoming Shifts
            </div>
            <div class="card-body">
                <?php if($upcoming && $upcoming->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Shift Type</th>
                                    <th>Time</th>
                                    <th>Department</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $upcoming->fetch_assoc()): ?>
                                <tr class="table-info">
                                    <td><strong><?php echo date('M d, Y', strtotime($row['ShiftDate'])); ?></strong></td>
                                    <td><?php echo date('l', strtotime($row['ShiftDate'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['ShiftType']); ?>">
                                            <?php echo $row['ShiftType']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($row['StartTime'])); ?> - <?php echo date('h:i A', strtotime($row['EndTime'])); ?></td>
                                    <td><?php echo $row['DeptName'] ?? 'General Ward'; ?></td>
                                    <td><?php echo $row['Notes'] ?: '-'; ?></td>
                                    <td>
                                        <a href="?delete=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this schedule?')">
                                            <i class="bi bi-trash"></i> Cancel
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No upcoming shifts scheduled</p>
                        <small>Go back to dashboard and click "Add Schedule" to create your first shift</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All Schedules History -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history"></i> Schedule History
                <button class="btn btn-sm btn-primary float-end" onclick="location.reload()">
                    <i class="bi bi-arrow-repeat"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <?php if($schedules && $schedules->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Shift Type</th>
                                    <th>Time</th>
                                    <th>Department</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $schedules->fetch_assoc()): 
                                    $isPast = strtotime($row['ShiftDate']) < strtotime(date('Y-m-d'));
                                    $isToday = $row['ShiftDate'] == date('Y-m-d');
                                ?>
                                <tr class="<?php echo $isPast ? 'text-muted' : ($isToday ? 'table-warning' : ''); ?>">
                                    <td><?php echo date('M d, Y', strtotime($row['ShiftDate'])); ?></td>
                                    <td><?php echo date('l', strtotime($row['ShiftDate'])); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($row['ShiftType']); ?>">
                                            <?php echo $row['ShiftType']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('h:i A', strtotime($row['StartTime'])); ?> - <?php echo date('h:i A', strtotime($row['EndTime'])); ?></td>
                                    <td><?php echo $row['DeptName'] ?? 'General Ward'; ?></td>
                                    <td><?php echo $row['Notes'] ?: '-'; ?></td>
                                    <td>
                                        <?php if($isPast): ?>
                                            <span class="badge bg-secondary">Completed</span>
                                        <?php elseif($isToday): ?>
                                            <span class="badge bg-warning">Today</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Upcoming</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-inbox" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No schedule records found</p>
                        <small>Click "Add Schedule" on the dashboard to create your schedule</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Add Schedule Button -->
        <div class="text-center mt-3">
            <a href="nurse_dashboard.php" class="btn btn-primary btn-lg">
                <i class="bi bi-plus-circle"></i> Add New Schedule
            </a>
        </div>
    </div>
</body>
</html>