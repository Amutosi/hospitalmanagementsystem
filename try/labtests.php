<?php
require_once 'db_config.php';

// Handle Add Lab Test
if(isset($_POST['add_labtest'])) {
    $name = $_POST['name'];
    $cost = $_POST['cost'];
    $normal_range = $_POST['normal_range'];
    $description = $_POST['description'];
    
    $sql = "INSERT INTO LabTest (TestName, StandardCost, NormalRange, Description, IsActive) 
            VALUES ('$name', '$cost', '$normal_range', '$description', 1)";
    
    if($conn->query($sql)) {
        echo "<script>alert('Lab test added successfully!'); window.location.href='?page=labtests';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Order Lab Test
if(isset($_POST['order_labtest'])) {
    $patient_id = $_POST['patient_id'];
    $labtest_id = $_POST['labtest_id'];
    $doctor_id = $_POST['doctor_id'];
    $notes = $_POST['notes'];
    
    $sql = "INSERT INTO PatientLabTest (PatientID, LabTestID, OrderDate, Status, OrderedByDoctorID, Notes) 
            VALUES ('$patient_id', '$labtest_id', NOW(), 'Ordered', '$doctor_id', '$notes')";
    
    if($conn->query($sql)) {
        echo "<script>alert('Lab test ordered successfully!'); window.location.href='?page=labtests';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Update Test Result
if(isset($_POST['update_result'])) {
    $test_id = $_POST['test_id'];
    $result_value = $_POST['result_value'];
    
    $sql = "UPDATE PatientLabTest SET ResultValue = '$result_value', ResultDate = NOW(), Status = 'Completed' WHERE PatientLabTestID = $test_id";
    
    if($conn->query($sql)) {
        echo "<script>alert('Test results updated!'); window.location.href='?page=labtests';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
}

// Handle Delete Lab Test
if(isset($_GET['delete_labtest'])) {
    $id = $_GET['delete_labtest'];
    $conn->query("UPDATE LabTest SET IsActive = 0 WHERE LabTestID = $id");
    echo "<script>alert('Lab test deleted!'); window.location.href='?page=labtests';</script>";
}

// Get all lab tests
$labtests = $conn->query("SELECT * FROM LabTest WHERE IsActive = 1 ORDER BY LabTestID DESC");

// Get patients for dropdown
$patients = $conn->query("SELECT PatientID, CONCAT(FirstName, ' ', LastName) as Name FROM Patient WHERE IsActive = 1");

// Get doctors for dropdown
$doctors = $conn->query("SELECT DoctorID, CONCAT(FirstName, ' ', LastName, ' - ', Specialization) as Name FROM Doctor WHERE IsActive = 1");

// Get ordered lab tests
$orderedTests = $conn->query("
    SELECT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           lt.TestName,
           lt.StandardCost,
           lt.NormalRange,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    LEFT JOIN Doctor d ON plt.OrderedByDoctorID = d.DoctorID
    ORDER BY plt.OrderDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lab Tests Management</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; flex-wrap: wrap; }
        .nav-tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; border: none; border-radius: 5px 5px 0 0; }
        .nav-tab.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .form-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-info { background: #17a2b8; color: white; padding: 5px 10px; font-size: 12px; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .status-ordered { background: #ffc107; color: #000; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-processing { background: #17a2b8; color: white; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-completed { background: #28a745; color: white; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .result-normal { color: #28a745; font-weight: bold; }
        .result-abnormal { color: #dc3545; font-weight: bold; }
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
        }
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        .close { cursor: pointer; font-size: 24px; }
        .test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: #f8f9fa;
        }
        @media (max-width: 768px) {
            .form-row { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔬 Lab Tests Management</h2>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('testsList')">📋 Lab Tests Catalog</button>
            <button class="nav-tab" onclick="showTab('orderTests')">➕ Order Lab Test</button>
            <button class="nav-tab" onclick="showTab('results')">📊 Test Results</button>
        </div>
        
        <!-- Tab 1: Lab Tests Catalog -->
        <div id="testsList" class="tab-content active">
            <div class="card">
                <div class="card-header">
                    📋 Lab Tests Catalog
                    <button class="btn-primary" style="float: right; padding: 5px 15px;" onclick="showAddTestForm()">+ Add Lab Test</button>
                </div>
                <div class="card-body">
                    <div class="form-row">
                        <?php if($labtests && $labtests->num_rows > 0): ?>
                            <?php while($row = $labtests->fetch_assoc()): ?>
                                <div class="test-card">
                                    <h4><?php echo htmlspecialchars($row['TestName']); ?></h4>
                                    <p><strong>Cost:</strong> $<?php echo number_format($row['StandardCost'], 2); ?></p>
                                    <p><strong>Normal Range:</strong> <?php echo htmlspecialchars($row['NormalRange'] ?: 'N/A'); ?></p>
                                    <p><small><?php echo htmlspecialchars(substr($row['Description'], 0, 100)); ?></small></p>
                                    <a href="?page=labtests&delete_labtest=<?php echo $row['LabTestID']; ?>" class="btn-danger" style="padding: 5px 10px; text-decoration: none; display: inline-block;" onclick="return confirm('Delete this test?')">Delete</a>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No lab tests found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Order Lab Test -->
        <div id="orderTests" class="tab-content">
            <div class="card">
                <div class="card-header">➕ Order Lab Test</div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Select Patient *</label>
                                <select name="patient_id" class="form-control" required>
                                    <option value="">Choose Patient</option>
                                    <?php 
                                    $patients->data_seek(0);
                                    while($row = $patients->fetch_assoc()): ?>
                                        <option value="<?php echo $row['PatientID']; ?>"><?php echo $row['Name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Select Lab Test *</label>
                                <select name="labtest_id" class="form-control" required>
                                    <option value="">Choose Test</option>
                                    <?php 
                                    $labtests->data_seek(0);
                                    while($row = $labtests->fetch_assoc()): ?>
                                        <option value="<?php echo $row['LabTestID']; ?>"><?php echo $row['TestName']; ?> - $<?php echo number_format($row['StandardCost'], 2); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ordering Doctor *</label>
                                <select name="doctor_id" class="form-control" required>
                                    <option value="">Select Doctor</option>
                                    <?php 
                                    $doctors->data_seek(0);
                                    while($row = $doctors->fetch_assoc()): ?>
                                        <option value="<?php echo $row['DoctorID']; ?>"><?php echo $row['Name']; ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Notes / Instructions</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Any special instructions for the lab..."></textarea>
                            </div>
                        </div>
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="order_labtest" class="btn-primary">Order Lab Test</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab 3: Test Results -->
        <div id="results" class="tab-content">
            <div class="card">
                <div class="card-header">📊 Test Results</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th><th>Patient</th><th>Test Name</th><th>Order Date</th>
                                    <th>Ordered By</th><th>Result</th><th>Normal Range</th><th>Status</th><th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($orderedTests && $orderedTests->num_rows > 0): ?>
                                    <?php while($row = $orderedTests->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $row['PatientLabTestID']; ?></td>
                                        <td><?php echo htmlspecialchars($row['PatientName']); ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['TestName']); ?></strong></td>
                                        <td><?php echo date('Y-m-d', strtotime($row['OrderDate'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['DoctorName'] ?: 'N/A'); ?></td>
                                        <td>
                                            <?php if($row['ResultValue']): ?>
                                                <span class="<?php echo isResultNormal($row['ResultValue'], $row['NormalRange']) ? 'result-normal' : 'result-abnormal'; ?>">
                                                    <?php echo htmlspecialchars($row['ResultValue']); ?>
                                                </span>
                                            <?php else: ?>
                                                <em>Pending</em>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['NormalRange'] ?: 'N/A'); ?></td>
                                        <td>
                                            <span class="status-<?php echo strtolower($row['Status']); ?>">
                                                <?php echo $row['Status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($row['Status'] != 'Completed'): ?>
                                                <button class="btn-info" style="padding: 5px 10px;" onclick="enterResult(<?php echo $row['PatientLabTestID']; ?>, '<?php echo htmlspecialchars($row['TestName']); ?>')">Enter Result</button>
                                            <?php else: ?>
                                                <button class="btn-info" style="padding: 5px 10px;" onclick="viewReport(<?php echo $row['PatientLabTestID']; ?>)">View Report</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" style="text-align: center;">No lab test orders found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add Lab Test Modal -->
    <div id="addTestModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Lab Test</h3>
                <span class="close" onclick="closeAddTestForm()">&times;</span>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Test Name *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Standard Cost ($) *</label>
                    <input type="number" step="0.01" name="cost" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Normal Range (e.g., 4.5-11.0 K/uL)</label>
                    <input type="text" name="normal_range" class="form-control" placeholder="Example: 4.5-11.0 K/uL">
                </div>
                <div class="form-group">
                    <label>Description / Instructions</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn-secondary" onclick="closeAddTestForm()">Cancel</button>
                    <button type="submit" name="add_labtest" class="btn-primary">Add Test</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Enter Result Modal -->
    <div id="resultModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Enter Lab Test Result</h3>
                <span class="close" onclick="closeResultModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="test_id" id="result_test_id">
                <div class="form-group">
                    <label>Test Name</label>
                    <input type="text" id="result_test_name" class="form-control" readonly>
                </div>
                <div class="form-group">
                    <label>Result Value *</label>
                    <textarea name="result_value" id="result_value" class="form-control" rows="3" required placeholder="Enter test results..."></textarea>
                </div>
                <div style="text-align: right;">
                    <button type="button" class="btn-secondary" onclick="closeResultModal()">Cancel</button>
                    <button type="submit" name="update_result" class="btn-primary">Save Results</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            document.querySelectorAll('.nav-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function showAddTestForm() {
            document.getElementById('addTestModal').style.display = 'block';
        }
        
        function closeAddTestForm() {
            document.getElementById('addTestModal').style.display = 'none';
        }
        
        function enterResult(id, testName) {
            document.getElementById('result_test_id').value = id;
            document.getElementById('result_test_name').value = testName;
            document.getElementById('resultModal').style.display = 'block';
        }
        
        function closeResultModal() {
            document.getElementById('resultModal').style.display = 'none';
        }
        
        function viewReport(id) {
            window.open('view_lab_report.php?id=' + id, '_blank', 'width=800,height=600');
        }
        
        window.onclick = function(event) {
            let modal1 = document.getElementById('addTestModal');
            let modal2 = document.getElementById('resultModal');
            if(event.target == modal1) {
                modal1.style.display = 'none';
            }
            if(event.target == modal2) {
                modal2.style.display = 'none';
            }
        }
    </script>
</body>
</html>

<?php
function isResultNormal($resultValue, $normalRange) {
    if(empty($normalRange) || $normalRange == 'N/A') {
        return true;
    }
    if(preg_match('/(\d+(?:\.\d+)?)\s*-\s*(\d+(?:\.\d+)?)/', $normalRange, $matches)) {
        $min = floatval($matches[1]);
        $max = floatval($matches[2]);
        $value = floatval($resultValue);
        return ($value >= $min && $value <= $max);
    }
    return true;
}
?>