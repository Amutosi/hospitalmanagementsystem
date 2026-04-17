<?php
require_once 'db_config.php';

$success = '';
$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $contact = $_POST['contact'];
    $email = $_POST['email'];
    $address = $_POST['address'];
    $blood_group = $_POST['blood_group'];
    $emergency_name = $_POST['emergency_name'];
    $emergency_contact = $_POST['emergency_contact'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if username exists
        $check = $conn->query("SELECT * FROM Patient WHERE Username = '$username'");
        if($check->num_rows > 0) {
            $error = "Username already taken!";
        } else {
            $sql = "INSERT INTO Patient (FirstName, LastName, DateOfBirth, Gender, ContactNo, Email, Address, BloodGroup, EmergencyContactName, EmergencyContactNo, Username, Password, RegistrationDate, IsActive) 
                    VALUES ('$first_name', '$last_name', '$dob', '$gender', '$contact', '$email', '$address', '$blood_group', '$emergency_name', '$emergency_contact', '$username', '$password', CURDATE(), 1)";
            
            if($conn->query($sql)) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Patient Registration</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .register-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 700px;
            margin: 0 auto;
            padding: 40px;
        }
        h1 { text-align: center; color: #667eea; margin-bottom: 30px; }
        h3 { color: #667eea; margin: 20px 0 15px 0; font-size: 18px; border-bottom: 1px solid #eee; padding-bottom: 8px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 8px; }
        .btn { width: 100%; padding: 12px; background: #667eea; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #5a67d8; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        .login-link { text-align: center; margin-top: 20px; }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        @media (max-width: 600px) { 
            .form-row { grid-template-columns: 1fr; }
            .register-container { padding: 20px; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>🏥 Patient Registration</h1>
        
        <?php if($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success"><?php echo $success; ?></div>
            <div class="login-link"><a href="patient_login.php">Click here to login</a></div>
        <?php else: ?>
            <form method="POST">
                <!-- Personal Information -->
                <h3>📋 Personal Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Date of Birth *</label>
                        <input type="date" name="dob" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Gender *</label>
                        <select name="gender" class="form-control" required>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Blood Group</label>
                        <select name="blood_group" class="form-control">
                            <option value="">Select Blood Group</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Residential Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="House No, Street, City, State, ZIP Code"></textarea>
                </div>
                
                <!-- Emergency Contact Information -->
                <h3>🚨 Emergency Contact Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Emergency Contact Name</label>
                        <input type="text" name="emergency_name" class="form-control" placeholder="Full name of emergency contact">
                    </div>
                    <div class="form-group">
                        <label>Emergency Contact Number</label>
                        <input type="text" name="emergency_contact" class="form-control" placeholder="Phone number">
                    </div>
                </div>
                
                <!-- Account Information -->
                <h3>🔐 Account Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label>Username *</label>
                        <input type="text" name="username" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Confirm Password *</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn">Register</button>
            </form>
            
            <div class="login-link">
                Already have an account? <a href="patient_login.php">Login Here</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>