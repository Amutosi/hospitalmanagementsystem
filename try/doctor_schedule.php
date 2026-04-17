<?php
session_start();
if(!isset($_SESSION['doctor_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$doctor_id = $_SESSION['doctor_id'];

// Get doctor info
$doctor = $conn->query("SELECT * FROM Doctor WHERE DoctorID = $doctor_id")->fetch_assoc();

// Handle Add Schedule
if(isset($_POST['add_schedule'])) {
    $schedule_date = $_POST['schedule_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $notes = $_POST['notes'];
    
    // Check if schedule already exists for this date and time
    $check = $conn->query("SELECT * FROM DoctorSchedule WHERE DoctorID = $doctor_id AND ScheduleDate = '$schedule_date' AND StartTime = '$start_time'");
    if($check->num_rows > 0) {
        echo "<script>alert('Schedule already exists for this date and time!');</script>";
    } else {
        $sql = "INSERT INTO DoctorSchedule (DoctorID, ScheduleDate, StartTime, EndTime, Status, Notes) 
                VALUES ('$doctor_id', '$schedule_date', '$start_time', '$end_time', 'Available', '$notes')";
        
        if($conn->query($sql)) {
            echo "<script>alert('Schedule added successfully!'); window.location.href='doctor_schedule.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    }
}

// Handle Delete Schedule
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM DoctorSchedule WHERE ScheduleID = $id");
    echo "<script>alert('Schedule deleted!'); window.location.href='doctor_schedule.php';</script>";
}

// Handle Update Status
if(isset($_GET['status']) && isset($_GET['id'])) {
    $status = $_GET['status'];
    $id = $_GET['id'];
    $conn->query("UPDATE DoctorSchedule SET Status = '$status' WHERE ScheduleID = $id");
    echo "<script>alert('Schedule updated!'); window.location.href='doctor_schedule.php';</script>";
}

// Get all schedules for this doctor
$schedules = $conn->query("
    SELECT * FROM DoctorSchedule 
    WHERE DoctorID = $doctor_id 
    ORDER BY ScheduleDate DESC, StartTime ASC
");

// Get upcoming schedules
$upcoming = $conn->query("
    SELECT * FROM DoctorSchedule 
    WHERE DoctorID = $doctor_id AND ScheduleDate >= CURDATE() 
    ORDER BY ScheduleDate ASC, StartTime ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Doctor Portal</title>
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
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-back:hover {
            color: white;
        }
        .schedule-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .status-available { background: #28a745; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; display: inline-block; }
        .status-booked { background: #dc3545; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; display: inline-block; }
        .status-completed { background: #6c757d; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px; display: inline-block; }
        @media (max-width: 768px) {
            .container-fluid { padding: 10px; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-calendar-week"></i> My Work Schedule</h2>
            <a href="doctor_dashboard.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <!-- Add Schedule Form -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-plus-circle"></i> Add New Schedule
                <button class="btn btn-sm btn-primary float-end" onclick="toggleForm()">
                    <i class="bi bi-plus"></i> Add Schedule
                </button>
            </div>
            <div class="card-body" id="scheduleForm" style="display: none;">
                <form method="POST" class="schedule-form">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label>Date *</label>
                            <input type="date" name="schedule_date" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Start Time *</label>
                            <input type="time" name="start_time" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>End Time *</label>
                            <input type="time" name="end_time" class="form-control" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Optional notes">
                        </div>
                    </div>
                    <button type="submit" name="add_schedule" class="btn btn-primary">
                        <i class="bi bi-save"></i> Save Schedule
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="toggleForm()">
                        Cancel
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Upcoming Schedules -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <i class="bi bi-star-fill"></i> Upcoming Schedules
            </div>
            <div class="card-body">
                <?php if($upcoming && $upcoming->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $upcoming->fetch_assoc()): 
                                    $dayOfWeek = date('l', strtotime($row['ScheduleDate']));
                                ?>
                                <tr class="table-info">
                                    <td><strong><?php echo date('M d, Y', strtotime($row['ScheduleDate'])); ?></strong></td>
                                    <td><?php echo $dayOfWeek; ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['StartTime'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['EndTime'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['Notes'] ?: '-'; ?></td>
                                    <td>
                                        <?php if($row['Status'] == 'Available'): ?>
                                            <a href="?status=Booked&id=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-warning" onclick="return confirm('Mark as booked?')">Mark Booked</a>
                                        <?php elseif($row['Status'] == 'Booked'): ?>
                                            <a href="?status=Completed&id=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as completed?')">Complete</a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this schedule?')">Delete</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-calendar-x" style="font-size: 48px; color: #ccc;"></i>
                        <p class="mt-2 text-muted">No upcoming schedules</p>
                        <small>Click "Add Schedule" to create your work schedule</small>
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
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $schedules->fetch_assoc()): 
                                    $dayOfWeek = date('l', strtotime($row['ScheduleDate']));
                                    $isPast = strtotime($row['ScheduleDate']) < strtotime(date('Y-m-d'));
                                ?>
                                <tr class="<?php echo $isPast ? 'text-muted' : ''; ?>">
                                    <td><?php echo date('M d, Y', strtotime($row['ScheduleDate'])); ?></td>
                                    <td><?php echo $dayOfWeek; ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['StartTime'])); ?></td>
                                    <td><?php echo date('h:i A', strtotime($row['EndTime'])); ?></td>
                                    <td>
                                        <span class="status-<?php echo strtolower($row['Status']); ?>">
                                            <?php echo $row['Status']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['Notes'] ?: '-'; ?></td>
                                    <td>
                                        <?php if(!$isPast && $row['Status'] == 'Available'): ?>
                                            <a href="?status=Booked&id=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-warning">Mark Booked</a>
                                        <?php elseif(!$isPast && $row['Status'] == 'Booked'): ?>
                                            <a href="?status=Completed&id=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-success">Complete</a>
                                        <?php endif; ?>
                                        <?php if(!$isPast): ?>
                                            <a href="?delete=<?php echo $row['ScheduleID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete?')">Delete</a>
                                        <?php else: ?>
                                            <span class="text-muted">Completed</span>
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
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleForm() {
            var form = document.getElementById('scheduleForm');
            if(form.style.display === 'none' || form.style.display === '') {
                form.style.display = 'block';
            } else {
                form.style.display = 'none';
            }
        }
    </script>
</body>
</html>