<?php
session_start();
require_once 'db_config.php';

$success = '';
$error = '';
$selected_role = isset($_GET['role']) ? $_GET['role'] : 'doctor';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $role = $_POST['role'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    
    if($password != $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        if($role == 'doctor') {
            $specialization = $_POST['specialization'];
            $qualification = $_POST['qualification'];
            $fee = $_POST['fee'];
            $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : 1; // Default to department 1
            
            // Check if username exists
            $check = $conn->query("SELECT * FROM Doctor WHERE Username = '$username' OR Email = '$email'");
            if($check->num_rows > 0) {
                $error = "Username or Email already exists!";
            } else {
                $sql = "INSERT INTO Doctor (FirstName, LastName, Specialization, ContactNo, Email, Username, Password, Qualification, ConsultationFee, DepartmentID, IsActive) 
                        VALUES ('$first_name', '$last_name', '$specialization', '$contact', '$email', '$username', '$password', '$qualification', '$fee', '$department_id', 1)";
                
                if($conn->query($sql)) {
                    $success = "Doctor registered successfully! You can now login.";
                } else {
                    $error = "Error: " . $conn->error;
                }
            }
        } 
        elseif($role == 'nurse') {
            $qualification = $_POST['qualification'];
            $shift = $_POST['shift'];
            $department_id = !empty($_POST['department_id']) ? $_POST['department_id'] : 1; // Default to department 1
            
            // Check if username exists
            $check = $conn->query("SELECT * FROM Nurse WHERE Username = '$username' OR Email = '$email'");
            if($check->num_rows > 0) {
                $error = "Username or Email already exists!";
            } else {
                $sql = "INSERT INTO Nurse (FirstName, LastName, Qualification, ContactNo, Email, Username, Password, DepartmentID, ShiftPreference, IsActive) 
                        VALUES ('$first_name', '$last_name', '$qualification', '$contact', '$email', '$username', '$password', '$department_id', '$shift', 1)";
                
                if($conn->query($sql)) {
                    $success = "Nurse registered successfully! You can now login.";
                } else {
                    $error = "Error: " . $conn->error;
                }
            }
        }
    }
}

// Get departments for dropdown
$departments = $conn->query("SELECT DepartmentID, DeptName FROM Department");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Hospital Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
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
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            max-width: 650px;
            margin: 0 auto;
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-header h1 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        .register-body {
            padding: 30px;
        }
        .role-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            justify-content: center;
        }
        .role-btn {
            flex: 1;
            text-align: center;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s;
            background: white;
        }
        .role-btn i {
            font-size: 24px;
            display: block;
            margin-bottom: 5px;
        }
        .role-btn.active {
            border-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102,126,234,0.4);
        }
        .error-alert {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-alert {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        @media (max-width: 500px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="bi bi-person-plus" style="font-size: 48px;"></i>
            <h1>Register as Staff</h1>
            <p>Doctor or Nurse Registration</p>
        </div>
        
        <div class="register-body">
            <?php if($error): ?>
                <div class="error-alert"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if($success): ?>
                <div class="success-alert"><?php echo $success; ?></div>
                <div class="login-link">
                    <a href="login.php" class="btn btn-primary">Go to Login</a>
                </div>
            <?php else: ?>
                <div class="role-selector">
                    <div class="role-btn <?php echo $selected_role == 'doctor' ? 'active' : ''; ?>" onclick="selectRole('doctor')">
                        <i class="bi bi-person-badge"></i>
                        <span>Doctor</span>
                    </div>
                    <div class="role-btn <?php echo $selected_role == 'nurse' ? 'active' : ''; ?>" onclick="selectRole('nurse')">
                        <i class="bi bi-person-heart"></i>
                        <span>Nurse</span>
                    </div>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="role" id="selected_role" value="<?php echo $selected_role; ?>">
                    
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
                            <label>Email *</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Contact Number *</label>
                            <input type="text" name="contact" class="form-control" required>
                        </div>
                    </div>
                    
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
                    
                    <!-- Doctor Fields -->
                    <div id="doctor_fields">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Specialization *</label>
                                <input type="text" name="specialization" class="form-control" placeholder="e.g., Cardiologist, Neurologist" required>
                            </div>
                            <div class="form-group">
                                <label>Qualification *</label>
                                <input type="text" name="qualification" class="form-control" placeholder="e.g., MBBS, MD" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Consultation Fee ($)</label>
                                <input type="number" step="0.01" name="fee" class="form-control" value="50">
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" class="form-control">
                                    <option value="1">General Medicine</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo $dept['DeptName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Nurse Fields -->
                    <div id="nurse_fields" style="display: none;">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Qualification *</label>
                                <input type="text" name="qualification" class="form-control" placeholder="e.g., BSN, RN" required>
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <select name="department_id" class="form-control">
                                    <option value="1">General Medicine</option>
                                    <?php 
                                    $departments->data_seek(0);
                                    while($dept = $departments->fetch_assoc()): ?>
                                        <option value="<?php echo $dept['DepartmentID']; ?>"><?php echo $dept['DeptName']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Shift Preference</label>
                            <select name="shift" class="form-control">
                                <option value="Morning">Morning</option>
                                <option value="Evening">Evening</option>
                                <option value="Night">Night</option>
                                <option value="Rotating">Rotating</option>
                            </select>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-register">Register</button>
                </form>
                
                <div class="login-link">
                    Already have an account? <a href="login.php">Login Here</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function selectRole(role) {
            document.getElementById('selected_role').value = role;
            
            // Update active state
            document.querySelectorAll('.role-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            event.currentTarget.classList.add('active');
            
            // Show/hide fields
            if(role === 'doctor') {
                document.getElementById('doctor_fields').style.display = 'block';
                document.getElementById('nurse_fields').style.display = 'none';
                // Make doctor fields required
                document.querySelectorAll('#doctor_fields input[type="text"]').forEach(input => {
                    if(input.name !== 'fee') input.required = true;
                });
                document.querySelectorAll('#nurse_fields input, #nurse_fields select').forEach(input => {
                    input.required = false;
                });
            } else {
                document.getElementById('doctor_fields').style.display = 'none';
                document.getElementById('nurse_fields').style.display = 'block';
                // Make nurse fields required
                document.querySelectorAll('#nurse_fields input[type="text"]').forEach(input => {
                    input.required = true;
                });
                document.querySelectorAll('#doctor_fields input, #doctor_fields select').forEach(input => {
                    input.required = false;
                });
            }
        }
        
        // Initialize based on default role
        document.addEventListener('DOMContentLoaded', function() {
            selectRole('<?php echo $selected_role; ?>');
        });
    </script>
</body>
</html>