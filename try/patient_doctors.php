<?php
session_start();
require_once 'db_config.php';

// Get all doctors
$doctors = $conn->query("
    SELECT d.*, dept.DeptName 
    FROM Doctor d
    LEFT JOIN Department dept ON d.DepartmentID = dept.DepartmentID
    WHERE d.IsActive = 1 
    ORDER BY d.DoctorID ASC
");

// Default image for doctors without custom images
$default_image = 'assets/images/default/default-doctor.png';

// Custom images for specific doctors (by DoctorID)
$custom_doctor_images = [
    1 => 'assets/images/doctors/doctor1.jpg',
    2 => 'assets/images/doctors/doctor2.jpg',
    3 => 'assets/images/doctors/doctor3.jpg',
    32 => 'assets/images/doctors/doctor4.jpg',
    33 => 'assets/images/doctors/doctor5.jpg',
    34 => 'assets/images/doctors/doctor6.jpg',
     35 => 'assets/images/doctors/doctor7.jpg',
      36 => 'assets/images/doctors/doctor8.jpg',
       37 => 'assets/images/doctors/doctor9.jpg',
        38 => 'assets/images/doctors/doctor10.jpg',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Doctors - Hope Medical Services</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f2f5;
        }
        .navbar {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            padding: 15px 0;
        }
        .navbar-brand, .nav-link {
            color: white !important;
        }
        .page-header {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            color: white;
            padding: 60px 0;
            text-align: center;
            margin-top: 70px;
        }
        .doctor-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .doctor-img {
            width: 100%;
            height: 600px;
            object-fit: cover;
        }
        .default-img {
            width: 100%;
            height: 300px;
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .default-img i {
            font-size: 80px;
            color: white;
        }
        .specialty-badge {
            display: inline-block;
            background: #e8f0fe;
            color: #0f4c81;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
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
        .btn-back {
            background: linear-gradient(135deg, #0f4c81, #1a6d8f);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
        }
        .btn-back:hover {
            color: white;
        }
        @media (max-width: 768px) {
            .doctor-img, .default-img { height: 200px; }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg fixed-top">
        <div class="container">
            <a class="navbar-brand" href="login.php">
                <i class="bi bi-hospital"></i> Hope Medical Services
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="login.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient_doctors.php">Doctors</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient_nurses.php">Nurses</a></li>
                    <li class="nav-item"><a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#loginModal">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="patient_register.php">Register</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="page-header">
        <div class="container">
            <h1><i class="bi bi-person-badge"></i> Our Medical Team</h1>
            <p>Meet our experienced and dedicated doctors</p>
        </div>
    </section>

    <div class="container py-5">
        <div class="row">
            <?php while($doc = $doctors->fetch_assoc()): 
                // Check if this doctor has a custom image
                if(isset($custom_doctor_images[$doc['DoctorID']]) && file_exists($custom_doctor_images[$doc['DoctorID']])) {
                    $image_url = $custom_doctor_images[$doc['DoctorID']];
                    $has_image = true;
                } else {
                    $has_image = false;
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="doctor-card">
                    <?php if($has_image): ?>
                        <img src="<?php echo $image_url; ?>" class="doctor-img" alt="<?php echo $doc['FirstName']; ?>">
                    <?php else: ?>
                        <div class="default-img">
                            <i class="bi bi-person-badge"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title">Dr. <?php echo $doc['FirstName'] . ' ' . $doc['LastName']; ?></h5>
                        <span class="specialty-badge"><i class="bi bi-stethoscope"></i> <?php echo $doc['Specialization']; ?></span>
                        <p class="card-text mt-2"><i class="bi bi-mortarboard"></i> <?php echo $doc['Qualification'] ?: 'Medical Degree'; ?></p>
                        <p class="card-text"><i class="bi bi-building"></i> Department: <?php echo $doc['DeptName'] ?? 'General'; ?></p>
                        <p class="price"><i class="bi bi-cash"></i> Consultation: $<?php echo number_format($doc['ConsultationFee'], 2); ?></p>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="text-center mt-4">
            <a href="login.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Home</a>
        </div>
    </div>

    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5><i class="bi bi-hospital"></i> Hope Medical Services</h5>
                    <p>Providing quality healthcare services with compassion and excellence.</p>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="login.php">Home</a></li>
                        <li><a href="patient_doctors.php">Doctors</a></li>
                        <li><a href="patient_nurses.php">Nurses</a></li>
                        <li><a href="patient_register.php">Register</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact</h5>
                    <p><i class="bi bi-telephone"></i> +1 256 567 8900<br>
                    <i class="bi bi-envelope"></i> info@hopemedical.com<br>
                    <i class="bi bi-geo-alt"></i> Kampala, Uganda</p>
                </div>
            </div>
            <hr class="bg-light">
            <div class="text-center">
                <small>&copy; 2026 Hope Medical Services. All rights reserved.</small>
            </div>
        </div>
    </footer>
</body>
</html>