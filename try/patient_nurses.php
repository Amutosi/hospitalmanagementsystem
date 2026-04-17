<?php
session_start();
require_once 'db_config.php';

// Get all nurses
$nurses = $conn->query("
    SELECT n.*, d.DeptName 
    FROM Nurse n
    LEFT JOIN Department d ON n.DepartmentID = d.DepartmentID
    WHERE n.IsActive = 1 
    ORDER BY n.NurseID ASC
");

// Default image for nurses without custom images
 // $default_image = 'assets/images/default/default-nurse.png';

// Custom images for specific nurses (by NurseID)
$custom_nurse_images = [
    1 => 'assets/images/nurses/nurse1.jpg',
    2 => 'assets/images/nurses/nurse2.jpg',
    3 => 'assets/images/nurses/nurse3.jpg',
    11 => 'assets/images/nurses/nurse4.jpg',
    12 => 'assets/images/nurses/nurse5.jpg',
    13 => 'assets/images/nurses/nurse6.jpg',
    14 => 'assets/images/nurses/nurse7.jpg',
    15 => 'assets/images/nurses/nurse8.jpg',
    16 => 'assets/images/nurses/nurse9.jpg',
    17 => 'assets/images/nurses/nurse10.jpg',
];


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Nurses - Hope Medical Services</title>
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
        .nurse-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s;
            margin-bottom: 25px;
            height: 100%;
        }
        .nurse-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.15);
        }
        .nurse-img {
            width: 100%;
            height: 500px;
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
        .shift-badge {
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
            .nurse-img, .default-img { height: 200px; }
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
            <h1><i class="bi bi-person-heart"></i> Our Nursing Staff</h1>
            <p>Dedicated and compassionate nurses caring for you</p>
        </div>
    </section>

    <div class="container py-5">
        <div class="row">
            <?php while($nurse = $nurses->fetch_assoc()): 
                // Check if this nurse has a custom image
                if(isset($custom_nurse_images[$nurse['NurseID']]) && file_exists($custom_nurse_images[$nurse['NurseID']])) {
                    // Use custom image
                    $image_url = $custom_nurse_images[$nurse['NurseID']];
                    $has_image = true;
                } else {
                    // Use default placeholder
                    $has_image = false;
                }
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="nurse-card">
                    <?php if($has_image): ?>
                        <img src="<?php echo $image_url; ?>" class="nurse-img" alt="<?php echo $nurse['FirstName']; ?>">
                    <?php else: ?>
                        <div class="default-img">
                            <i class="bi bi-person-heart"></i>
                        </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $nurse['FirstName'] . ' ' . $nurse['LastName']; ?></h5>
                        <span class="shift-badge"><i class="bi bi-mortarboard"></i> <?php echo $nurse['Qualification']; ?></span>
                        <p class="card-text mt-2"><i class="bi bi-clock"></i> Shift: <?php echo $nurse['ShiftPreference'] ?: 'Rotating'; ?></p>
                        <p class="card-text"><i class="bi bi-building"></i> Department: <?php echo $nurse['DeptName'] ?? 'General'; ?></p>
                        <p class="card-text"><i class="bi bi-envelope"></i> Email: <?php echo $nurse['Email'] ?: 'Not provided'; ?></p>
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