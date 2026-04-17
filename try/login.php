<?php
session_start();
require_once 'db_config.php';

$error = '';
$selected_role = isset($_GET['role']) ? $_GET['role'] : '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if($role == 'admin') {
        if($username == 'admin' && $password == 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_name'] = 'Administrator';
            $_SESSION['user_role'] = 'admin';
            $_SESSION['user_name'] = 'Administrator';
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid admin credentials!";
        }
    } 
    elseif($role == 'doctor') {
        $stmt = $conn->prepare("SELECT DoctorID, FirstName, LastName, Email, Username, Password, IsActive FROM Doctor WHERE (Username = ? OR Email = ?) AND IsActive = 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            if($password == $row['Password']) {
                $_SESSION['doctor_logged_in'] = true;
                $_SESSION['doctor_id'] = $row['DoctorID'];
                $_SESSION['doctor_name'] = $row['FirstName'] . ' ' . $row['LastName'];
                $_SESSION['user_role'] = 'doctor';
                $_SESSION['user_name'] = 'Dr. ' . $row['FirstName'] . ' ' . $row['LastName'];
                header("Location: doctor_dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Doctor not found!";
        }
    }
    elseif($role == 'nurse') {
        $stmt = $conn->prepare("SELECT NurseID, FirstName, LastName, Email, Username, Password, IsActive FROM Nurse WHERE (Username = ? OR Email = ?) AND IsActive = 1");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            if($password == $row['Password']) {
                $_SESSION['nurse_logged_in'] = true;
                $_SESSION['nurse_id'] = $row['NurseID'];
                $_SESSION['nurse_name'] = $row['FirstName'] . ' ' . $row['LastName'];
                $_SESSION['user_role'] = 'nurse';
                $_SESSION['user_name'] = 'Nurse ' . $row['FirstName'] . ' ' . $row['LastName'];
                header("Location: nurse_dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Nurse not found!";
        }
    }
    elseif($role == 'patient') {
        $stmt = $conn->prepare("SELECT PatientID, FirstName, LastName, Username, Password, IsActive FROM Patient WHERE Username = ? AND IsActive = 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($row = $result->fetch_assoc()) {
            if($password == $row['Password']) {
                $_SESSION['patient_logged_in'] = true;
                $_SESSION['patient_id'] = $row['PatientID'];
                $_SESSION['patient_name'] = $row['FirstName'] . ' ' . $row['LastName'];
                $_SESSION['patient_username'] = $row['Username'];
                $_SESSION['user_role'] = 'patient';
                
                $conn->query("UPDATE Patient SET LastLogin = NOW() WHERE PatientID = " . $row['PatientID']);
                
                header("Location: patient_dashboard.php");
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "Patient not found! Please register first.";
        }
    }
}

// Get doctors and nurses
$doctors = $conn->query("SELECT DoctorID, FirstName, LastName, Specialization, ConsultationFee, Qualification FROM Doctor WHERE IsActive = 1 LIMIT 6");
$nurses = $conn->query("SELECT NurseID, FirstName, LastName, Qualification, ShiftPreference FROM Nurse WHERE IsActive = 1 LIMIT 6");
$departments = $conn->query("SELECT DepartmentID, DeptName, Description FROM Department LIMIT 8");

// Map doctors to their specific images
$doctor_image_map = [
    1 => 'assets/images/doctors/doctor1.jpg',
    2 => 'assets/images/doctors/doctor2.jpg',
    3 => 'assets/images/doctors/doctor3.jpg',
    32 => 'assets/images/doctors/doctor4.jpg',
    5 => 'assets/images/doctors/doctor5.jpg',
    6 => 'assets/images/doctors/doctor6.jpg',

];

$nurse_image_map = [
    1 => 'assets/images/nurses/nurse1.jpg',
    2 => 'assets/images/nurses/nurse2.jpg',
    3 => 'assets/images/nurses/nurse3.jpg',
    4 => 'assets/images/nurses/nurse4.jpg',
    5 => 'assets/images/nurses/nurse5.jpg',
    6 => 'assets/images/nurses/nurse6.jpg',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System - Patient Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
        <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            overflow-x: hidden;
        }
        
        .navbar {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            color: white !important;
            font-size: 24px;
            font-weight: bold;
        }
        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            transition: 0.3s;
        }
        .nav-link:hover {
            color: white !important;
        }
        
        /* Hero Section with Background Image */
        .hero {
            background: url('https://images.unsplash.com/photo-1579684385127-1ef15d508118?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            
            padding: 120px 0;
            text-align: center;
        }
        .hero h1 {
            font-size: 48px;
            font-weight: 700;
            margin-bottom: 20px;
            animation: fadeInUp 1s ease;
        }
        .hero p {
            font-size: 40px;
            opacity: 20.0;
            animation: fadeInUp 1.2s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 40px;
        }
        .section-title h2 {
            font-size: 32px;
            font-weight: 700;
            color: #0f4c81;
            position: relative;
            display: inline-block;
            padding-bottom: 10px;
        }
        .section-title h2:after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
        }
        
        .doctor-card, .nurse-card, .dept-card {
            background: white;
            border-radius: 50px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 50px;
            height: 100%;
        }
        .doctor-card:hover, .nurse-card:hover, .dept-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .doctor-img, .nurse-img {
            width: 100%;
            height: 600px;
            object-fit: cover;
        }
        .card-body {
            padding: 20px;
        }
        .card-title {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 5px;
        }
        .card-text {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        .price {
            color: #0f4c81;
            font-weight: bold;
            font-size: 16px;
        }
        .specialty-badge {
            display: inline-block;
            background: #e8f0fe;
            color: black;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }
        
        /* About Us Section with Background Image */
        .about-section {
            background: linear-gradient(rgba(15, 76, 129, 0.85), rgba(26, 109, 143, 0.85)), url('https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1600&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 80px 0;
        }
        .about-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }
        .about-content h3 {
            color: black;
            font-size: 30px;
            margin-bottom: 20px;
        }
        .about-content p {
            color: black;
            font-size: 18px;
            line-height: 1.8;
            margin-bottom: 20px;
        }
        .about-stats {
            display: flex;
            justify-content: center;
            gap: 40px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .stat-item {
            text-align: center;
            background: white;
            padding: 20px;
            border-radius: 15px;
            min-width: 150px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: 800;
            color: black;
        }
        .stat-label {
            color: black;
            font-size: 14px;
        }
        .mission-vision {
            display: flex;
            gap: 30px;
            margin-top: 40px;
            flex-wrap: wrap;
        }
        .mission-box, .vision-box {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .mission-box i, .vision-box i {
            font-size: 40px;
            color: #0f4c81;
            margin-bottom: 15px;
        }
        .mission-box h4, .vision-box h4 {
            color: #0f4c81;
            margin-bottom: 10px;
        }
        
        .modal-content {
            border-radius: 20px;
        }
        .modal-header {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            color: white;
            border-radius: 20px 20px 0 0;
        }
        .role-selector {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        .role-card {
            text-align: center;
            padding: 10px;
            background: #f1f5f9;
            border-radius: 12px;
            cursor: pointer;
            transition: 0.3s;
        }
        .role-card:hover {
            background: #e0f2fe;
        }
        .role-card.active {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            color: white;
        }
        .role-card i {
            font-size: 20px;
            display: block;
        }
        .btn-login {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            color: white;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: bold;
            width: 100%;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
            margin-top: 50px;
        }
        .footer a {
            color: white;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        
        .default-img {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 300px;
        }
        .default-img i {
            font-size: 80px;
            color: white;
        }
        
        @media (max-width: 768px) {
            .hero h1 { font-size: 32px; }
            .hero { padding: 80px 0; }
            .section-title h2 { font-size: 24px; }
            .doctor-img, .nurse-img { height: 200px; }
            .about-stats { gap: 15px; }
            .stat-number { font-size: 24px; }
            .mission-vision { flex-direction: column; }
            .stat-item { min-width: 120px; padding: 15px; }
        }

        /* Departments Section Styling */

    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="bi bi-hospital"></i> Hope Medical Services
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="#about">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="#doctors">Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="#nurses">Nurses</a></li>
                    <li class="nav-item"><a class="nav-link" href="#departments">Departments</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient_register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="hero" id="home" style="margin-top: 70px;">
        <div class="container">
            <h1>Welcome to Hope Medical Services</h1>
            <p>Your Health, Our Priority | Quality Healthcare Services</p>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content">
                <h3>About Hope Medical Services</h3>
                <p>Hope Medical Services is a state-of-the-art healthcare facility dedicated to providing compassionate, high-quality medical care to our community. With over 20 years of excellence in healthcare delivery, we combine modern medical technology with personalized patient care.</p>
                <p>Our team of experienced doctors, skilled nurses, and dedicated staff work together to ensure that every patient receives the best possible treatment in a comfortable and caring environment.</p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-number">20+</div>
                        <div class="stat-label">Years of Excellence</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Expert Doctors</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50k+</div>
                        <div class="stat-label">Happy Patients</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Emergency Services</div>
                    </div>
                </div>
                
                <div class="mission-vision">
                    <div class="mission-box">
                        <i class="bi bi-flag"></i>
                        <h4>Our Mission</h4>
                        <p>To provide accessible, compassionate, and high-quality healthcare services that improve the health and well-being of our community.</p>
                    </div>
                    <div class="vision-box">
                        <i class="bi bi-eye"></i>
                        <h4>Our Vision</h4>
                        <p>To be the leading healthcare provider recognized for excellence in patient care, medical innovation, and community service.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Doctors Section -->
    <section id="doctors" class="py-5">
        <div class="container">
            <div class="section-title">
                <h2>Our Expert Doctors</h2>
                <p>Meet our team of experienced medical professionals</p>
            </div>
            <div class="row">
                <?php 
                $counter = 1;
                while($doc = $doctors->fetch_assoc()): 
                    $image_path = isset($doctor_image_map[$doc['DoctorID']]) ? $doctor_image_map[$doc['DoctorID']] : "assets/images/doctors/doctor{$counter}.jpg";
                    if(!file_exists($image_path)) {
                        $image_path = null;
                    }
                ?>
                <div class="col-md-4 col-lg-4">
                    <div class="doctor-card">
                        <?php if($image_path && file_exists($image_path)): ?>
                            <img src="<?php echo $image_path; ?>" class="doctor-img" alt="<?php echo $doc['FirstName']; ?>">
                        <?php else: ?>
                            <div class="default-img">
                                <i class="bi bi-person-badge"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title">Dr. <?php echo $doc['FirstName'] . ' ' . $doc['LastName']; ?></h5>
                            <span class="specialty-badge"><i class="bi bi-stethoscope"></i> <?php echo $doc['Specialization']; ?></span>
                            <p class="card-text mt-2"><i class="bi bi-mortarboard"></i> <?php echo $doc['Qualification'] ?: 'Medical Degree'; ?></p>
                            <p class="price"><i class="bi bi-cash"></i> Consultation: $<?php echo number_format($doc['ConsultationFee'], 2); ?></p>
                        </div>
                    </div>
                </div>
                <?php 
                $counter++;
                endwhile; 
                ?>
            </div>
            <div class="text-center mt-3">
                <a href="patient_doctors.php" class="btn btn-primary">View All Doctors</a>
            </div>
        </div>
    </section>

    <!-- Nurses Section -->
    <section id="nurses" class="py-5 bg-light">
        <div class="container">
            <div class="section-title">
                <h2>Caring Nurses</h2>
                <p>Dedicated nursing staff for your care</p>
            </div>
            <div class="row">
                <?php 
                $counter = 1;
                while($nurse = $nurses->fetch_assoc()): 
                    $image_path = isset($nurse_image_map[$nurse['NurseID']]) ? $nurse_image_map[$nurse['NurseID']] : "assets/images/nurses/nurse{$counter}.jpg";
                    if(!file_exists($image_path)) {
                        $image_path = null;
                    }
                ?>
                <div class="col-md-4 col-lg-4">
                    <div class="nurse-card">
                        <?php if($image_path && file_exists($image_path)): ?>
                            <img src="<?php echo $image_path; ?>" class="nurse-img" alt="<?php echo $nurse['FirstName']; ?>">
                        <?php else: ?>
                            <div class="default-img">
                                <i class="bi bi-person-heart"></i>
                            </div>
                        <?php endif; ?>
                        <div class="card-body">
                            <h5 class="card-title"><?php echo $nurse['FirstName'] . ' ' . $nurse['LastName']; ?></h5>
                            <p class="card-text"><i class="bi bi-mortarboard"></i> <?php echo $nurse['Qualification']; ?></p>
                            <p class="card-text"><i class="bi bi-clock"></i> Shift: <?php echo $nurse['ShiftPreference'] ?: 'Rotating'; ?></p>
                        </div>
                    </div>
                </div>
                <?php 
                $counter++;
                endwhile; 
                ?>
            </div>
            <div class="text-center mt-3">
                <a href="patient_nurses.php" class="btn btn-primary">View All Nurses</a>
            </div>
        </div>
    </section>

    <!-- Departments Section -->
    <section id="departments" class="py-5">
        <div class="container">
            <div class="section-title">
                <h2>Our Departments</h2>
                <p>Comprehensive medical services</p>
            </div>
            <div class="row">
                <?php while($dept = $departments->fetch_assoc()): ?>
                <div class="col-md-3 col-lg-3">
                    <div class="dept-card">
                        <div class="card-body text-center">
                            <i class="bi bi-building" style="font-size: 40px; color: #0f4c81;"></i>
                            <h5 class="card-title mt-2"><?php echo $dept['DeptName']; ?></h5>
                            <p class="card-text small"><?php echo substr($dept['Description'] ?: 'Quality healthcare services', 0, 60); ?>...</p>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-box-arrow-in-right"></i> Login to HMS</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="form-group mb-3">
                            <label>Select Your Role</label>
                            <div class="role-selector">
                                <div class="role-card" onclick="selectRoleModal('admin')">
                                    <i class="bi bi-shield-lock"></i>
                                    <span>Admin</span>
                                </div>
                                <div class="role-card" onclick="selectRoleModal('doctor')">
                                    <i class="bi bi-person-badge"></i>
                                    <span>Doctor</span>
                                </div>
                                <div class="role-card" onclick="selectRoleModal('nurse')">
                                    <i class="bi bi-person-heart"></i>
                                    <span>Nurse</span>
                                </div>
                                <div class="role-card" onclick="selectRoleModal('patient')">
                                    <i class="bi bi-person"></i>
                                    <span>Patient</span>
                                </div>
                            </div>
                            <input type="hidden" name="role" id="modal_role" value="patient">
                        </div>
                        <div class="mb-3">
                            <label>Username / Email</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn-login">Login</button>
                    </form>
                    <hr>
                    <div class="text-center">
                        <small>New Patient? <a href="patient_register.php">Register Here</a></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-hospital"></i> Hope Medical Services</h5>
                    <p>Providing quality healthcare services with compassion and excellence since 2005.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about">About Us</a></li>
                        <li><a href="#doctors">Doctors</a></li>
                        <li><a href="#nurses">Nurses</a></li>
                        <li><a href="#departments">Departments</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Information</h5>
                    <p><i class="bi bi-telephone"></i> +1 256 567 8900<br>
                    <i class="bi bi-envelope"></i> info@hopemedical.com<br>
                    <i class="bi bi-geo-alt"></i> 123 Healthcare Avenue<br>
                    Kampala, Uganda</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <small>&copy; 2026 Hope Medical Services. All rights reserved.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectRoleModal(role) {
            document.getElementById('modal_role').value = role;
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
        }
    </script>
</body>
</html>