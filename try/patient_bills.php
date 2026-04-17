<?php
session_start();
if(!isset($_SESSION['patient_logged_in'])) {
    header("Location: login.php");
    exit();
}

require_once 'db_config.php';
$patient_id = $_SESSION['patient_id'];

// Handle payment submission
if(isset($_POST['make_payment'])) {
    $bill_id = $_POST['bill_id'];
    $amount = floatval($_POST['payment_amount']);
    $payment_method = $_POST['payment_method'];
    
    // Get current bill
    $bill = $conn->query("SELECT * FROM Billing WHERE BillID = $bill_id AND PatientID = $patient_id")->fetch_assoc();
    
    if($bill) {
        $new_paid = floatval($bill['AmountPaid']) + $amount;
        $total = floatval($bill['TotalAmount']);
        
        if($new_paid >= $total) {
            $status = 'Paid';
            $new_paid = $total;
        } else {
            $status = 'Partial';
        }
        
        $sql = "UPDATE Billing SET AmountPaid = '$new_paid', PaymentStatus = '$status', PaymentMethod = '$payment_method', PaymentDate = NOW() WHERE BillID = $bill_id";
        
        if($conn->query($sql)) {
            echo "<script>alert('Payment of $$amount recorded successfully!'); window.location.href='patient_bills.php';</script>";
        } else {
            echo "<script>alert('Error: " . $conn->error . "');</script>";
        }
    }
}

// Get all bills for this patient
$bills = $conn->query("
    SELECT * FROM Billing 
    WHERE PatientID = $patient_id 
    ORDER BY BillDate DESC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Bills</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
             body {
    font-family: 'Inter', sans-serif;
    min-height: 100vh;

    background: 
        
        url('https://images.unsplash.com/photo-1586773860418-d37222d8fce3?auto=format&fit=crop&w=1600&q=80') no-repeat center center/cover;

    overflow-x: hidden;
}
        .sidebar { width: 260px; position: fixed; left: 0; top: 0; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding-top: 20px; }
        .sidebar a { color: white; text-decoration: none; display: block; padding: 12px 20px; }
        .sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }
        .content { margin-left: 260px; padding: 20px; }
        .card { border: none; border-radius: 15px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: white; border-bottom: 2px solid #f0f0f0; padding: 15px 20px; font-weight: bold; border-radius: 15px 15px 0 0; }
        .status-paid { background: #d4edda; color: #155724; padding: 5px 12px; border-radius: 20px; display: inline-block; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; padding: 5px 12px; border-radius: 20px; display: inline-block; font-size: 12px; font-weight: bold; }
        .status-partial { background: #d1ecf1; color: #0c5460; padding: 5px 12px; border-radius: 20px; display: inline-block; font-size: 12px; font-weight: bold; }
        .btn-pay { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 8px 20px; border-radius: 50px; font-weight: 600; }
        .modal-content { border-radius: 20px; }
        .modal-header { background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 20px 20px 0 0; }
        @media (max-width: 768px) { .sidebar { left: -260px; } .content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="text-center mb-4">🏥 Patient Portal</h4>
        <a href="patient_dashboard.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
        <a href="patient_appointments.php"><i class="bi bi-calendar-check"></i> Appointments</a>
        <a href="patient_labtests.php"><i class="bi bi-flask"></i> Lab Tests</a>
        <a href="patient_prescriptions.php"><i class="bi bi-prescription"></i> Prescriptions</a>
        <a href="patient_bills.php" class="active"><i class="bi bi-credit-card"></i> Bills</a>
        <a href="patient_treatments.php"><i class="bi bi-clipboard2-pulse"></i> Treatments</a>
        <a href="patient_profile.php"><i class="bi bi-person-circle"></i> Profile</a>
        <a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
    
    <div class="content">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-credit-card"></i> My Bills
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bill #</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($bills && $bills->num_rows > 0): ?>
                                <?php while($row = $bills->fetch_assoc()): 
                                    $balance = floatval($row['TotalAmount']) - floatval($row['AmountPaid']);
                                ?>
                                <tr>
                                    <td><?php echo $row['BillNumber']; ?></td>
                                    <td><?php echo $row['BillDate']; ?></td>
                                    <td><?php echo $row['DueDate']; ?></td>
                                    <td>$<?php echo number_format($row['TotalAmount'], 2); ?></td>
                                    <td>$<?php echo number_format($row['AmountPaid'], 2); ?></td>
                                    <td>$<?php echo number_format($balance, 2); ?></td>
                                    <td>
                                        <?php if($row['PaymentStatus'] == 'Paid'): ?>
                                            <span class="status-paid"><i class="bi bi-check-circle"></i> Paid</span>
                                        <?php elseif($row['PaymentStatus'] == 'Partial'): ?>
                                            <span class="status-partial"><i class="bi bi-exclamation-triangle"></i> Partial</span>
                                        <?php else: ?>
                                            <span class="status-pending"><i class="bi bi-clock"></i> Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($balance > 0): ?>
                                            <button class="btn-pay" onclick="openPaymentModal(<?php echo $row['BillID']; ?>, '<?php echo $row['BillNumber']; ?>', <?php echo $balance; ?>)">
                                                <i class="bi bi-credit-card"></i> Pay Now
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success"><i class="bi bi-check-circle"></i> Fully Paid</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">No bills found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-credit-card"></i> Make Payment</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="paymentForm">
                        <input type="hidden" name="bill_id" id="bill_id">
                        <div class="mb-3">
                            <label>Bill Number:</label>
                            <strong id="bill_number"></strong>
                        </div>
                        <div class="mb-3">
                            <label>Balance Due:</label>
                            <strong id="balance_due" style="color: #dc3545;"></strong>
                        </div>
                        <div class="mb-3">
                            <label>Payment Amount *</label>
                            <input type="number" step="0.01" name="payment_amount" id="payment_amount" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Payment Method *</label>
                            <select name="payment_method" class="form-control" required>
                                <option value="Credit Card">💳 Credit Card</option>
                                <option value="Debit Card">💳 Debit Card</option>
                                <option value="Mobile Money">📱 Mobile Money</option>
                                <option value="Bank Transfer">🏦 Bank Transfer</option>
                                <option value="Cash">💵 Cash</option>
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="make_payment" class="btn btn-success">Process Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openPaymentModal(billId, billNumber, balance) {
            document.getElementById('bill_id').value = billId;
            document.getElementById('bill_number').innerText = billNumber;
            document.getElementById('balance_due').innerHTML = '$' + balance.toFixed(2);
            document.getElementById('payment_amount').value = balance.toFixed(2);
            $('#paymentModal').modal('show');
        }
    </script>
</body>
</html>