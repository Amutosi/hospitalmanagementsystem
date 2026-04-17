<?php
session_start();
require_once 'db_config.php';

// Check if user is logged in as admin, doctor, or nurse
if(!isset($_SESSION['admin_logged_in']) && !isset($_SESSION['doctor_logged_in']) && !isset($_SESSION['nurse_logged_in'])) {
    header("Location: login.php");
    exit();
}

// Set user info for display
$user_name = '';
$user_role = '';
if(isset($_SESSION['admin_logged_in'])) {
    $user_name = $_SESSION['admin_name'];
    $user_role = 'Admin';
} elseif(isset($_SESSION['doctor_logged_in'])) {
    $user_name = 'Dr. ' . $_SESSION['doctor_name'];
    $user_role = 'Doctor';
} elseif(isset($_SESSION['nurse_logged_in'])) {
    $user_name = 'Nurse ' . $_SESSION['nurse_name'];
    $user_role = 'Nurse';
}

$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
?>

<!DOCTYPE html>
<html>
<head>
    <title>Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;

    background: 
        
        url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1600&q=80') no-repeat center center/cover;

    overflow-x: hidden;
}
        .sidebar { width: 250px; position: fixed; left: 0; top: 0; height: 100%; background: #2c3e50; color: white; padding: 20px; overflow-y: auto; }
        .content { margin-left: 250px; padding: 20px; }
        .sidebar h4 { margin-bottom: 30px; text-align: center; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 15px; margin: 5px 0; border-radius: 5px; transition: 0.3s; }
        .sidebar a:hover { background: #34495e; }
        .sidebar a i { margin-right: 10px; }
        .active-page { background: #34495e; }
        .top-bar { background: white; padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .top-bar h2 { margin: 0; font-size: 20px; }
        @media (max-width: 768px) {
            .sidebar { left: -250px; }
            .content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4>🏥 Hospital MS</h4>
        <a href="?page=dashboard" class="<?php echo $page == 'dashboard' ? 'active-page' : ''; ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a href="?page=patients" class="<?php echo $page == 'patients' ? 'active-page' : ''; ?>">
            <i class="bi bi-people"></i> Patients
        </a>
        <a href="?page=doctors" class="<?php echo $page == 'doctors' ? 'active-page' : ''; ?>">
            <i class="bi bi-person-badge"></i> Doctors
        </a>
        <a href="?page=nurses" class="<?php echo $page == 'nurses' ? 'active-page' : ''; ?>">
            <i class="bi bi-person-heart"></i> Nurses
        </a>
        <a href="?page=appointments" class="<?php echo $page == 'appointments' ? 'active-page' : ''; ?>">
            <i class="bi bi-calendar-check"></i> Appointments
        </a>
        <a href="?page=treatments" class="<?php echo $page == 'treatments' ? 'active-page' : ''; ?>">
            <i class="bi bi-clipboard2-pulse"></i> Treatments
        </a>
        <a href="?page=billing" class="<?php echo $page == 'billing' ? 'active-page' : ''; ?>">
            <i class="bi bi-credit-card"></i> Billing
        </a>
        <a href="?page=labtests" class="<?php echo $page == 'labtests' ? 'active-page' : ''; ?>">
            <i class="bi bi-flask"></i> Lab Tests
        </a>
        <a href="?page=prescriptions" class="<?php echo $page == 'prescriptions' ? 'active-page' : ''; ?>">
            <i class="bi bi-prescription"></i> Prescriptions
        </a>
        <a href="?page=collaboration" class="<?php echo $page == 'collaboration' ? 'active-page' : ''; ?>">
            <i class="bi bi-people"></i> Dept Collaboration
        </a>
    </div>

    <div class="content">
        <div class="top-bar">
            <div>
                <h2><?php echo ucfirst($page); ?></h2>
                <small>Welcome, <?php echo $user_name; ?> (<?php echo $user_role; ?>)</small>
            </div>
            <div>
                <a href="logout.php" class="btn btn-sm btn-danger">Logout</a>
            </div>
        </div>

        <?php
        if($page == 'dashboard') {
            include 'dashboard.php';
        } elseif($page == 'patients') {
            include 'patients.php';
        } elseif($page == 'doctors') {
            include 'doctors.php';
        } elseif($page == 'nurses') {
            include 'nurses.php';
        } elseif($page == 'appointments') {
            include 'appointments.php';
        } elseif($page == 'treatments') {
            include 'treatments.php';
        } elseif($page == 'billing') {
            include 'billing.php';
        } elseif($page == 'labtests') {
            include 'labtests.php';
        } elseif($page == 'prescriptions') {
            include 'prescriptions.php';
        } elseif($page == 'collaboration') {
            include 'collaboration.php';
        } else {
            include 'dashboard.php';
        }
        ?>
    </div>
</body>
</html>