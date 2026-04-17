<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];
$patient = $conn->query("SELECT * FROM Patient WHERE PatientID = $patient_id")->fetch_assoc();

// Get all departments
$departments = $conn->query("SELECT DepartmentID, DeptName, Description FROM Department ORDER BY DeptName");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Department - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .dept-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            height: 100%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .dept-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.2);
        }
        .dept-icon {
            font-size: 48px;
            color: #0f4c81;
            margin-bottom: 15px;
        }
        .dept-name {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .dept-desc {
            color: #666;
            font-size: 13px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="welcome-card">
            <h2>Welcome, <?php echo $patient['FirstName'] . ' ' . $patient['LastName']; ?>!</h2>
            <p>Please select a department to continue to your dashboard</p>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
        
        <div class="row">
            <?php while($dept = $departments->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="dept-card" onclick="selectDepartment(<?php echo $dept['DepartmentID']; ?>, '<?php echo addslashes($dept['DeptName']); ?>')">
                    <div class="dept-icon">
                        <i class="bi bi-building"></i>
                    </div>
                    <div class="dept-name"><?php echo $dept['DeptName']; ?></div>
                    <div class="dept-desc"><?php echo substr($dept['Description'] ?: 'Quality healthcare services', 0, 80); ?></div>
                    <button class="btn btn-primary btn-sm mt-3">Select</button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <script>
        function selectDepartment(deptId, deptName) {
            window.location.href = 'patient_dashboard.php?dept_id=' + deptId + '&dept_name=' + deptName;
        }
    </script>
</body>
</html>