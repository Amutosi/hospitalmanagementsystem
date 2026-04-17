<?php
require_once 'db_config.php';

// Handle Generate Bill
if(isset($_POST['generate_bill'])) {
    $patient_id = $_POST['patient_id'];
    
    // Get services from the form
    $service_types = $_POST['service_type'] ?? [];
    $service_ids = $_POST['service_id'] ?? [];
    $service_quantities = $_POST['service_quantity'] ?? [];
    $service_prices = $_POST['service_price'] ?? [];
    $service_descriptions = $_POST['service_description'] ?? [];
    
    $subtotal = 0;
    $services_data = [];
    
    // Calculate subtotal and prepare services data
    for($i = 0; $i < count($service_types); $i++) {
        if(empty($service_types[$i]) || empty($service_ids[$i])) continue;
        
        $price = floatval($service_prices[$i]);
        $quantity = intval($service_quantities[$i]);
        $line_total = $price * $quantity;
        $subtotal += $line_total;
        
        // Map service type to match ENUM values in database
        $service_type_enum = '';
        switch($service_types[$i]) {
            case 'Appointment':
                $service_type_enum = 'Appointment';
                break;
            case 'Treatment':
                $service_type_enum = 'TreatmentStage';
                break;
            case 'LabTest':
                $service_type_enum = 'LabTest';
                break;
            default:
                $service_type_enum = 'Appointment';
        }
        
        $services_data[] = [
            'type' => $service_type_enum,
            'id' => $service_ids[$i],
            'quantity' => $quantity,
            'price' => $price,
            'description' => !empty($service_descriptions[$i]) ? $service_descriptions[$i] : 'Service Charge',
            'line_total' => $line_total
        ];
    }
    
    if($subtotal == 0) {
        echo "<script>alert('Please add at least one service with a valid price! Make sure to select a service from the dropdown.'); window.location.href='?page=billing';</script>";
        exit;
    }
    
    $tax = $subtotal * 0.10;
    $total = $subtotal + $tax;
    $bill_number = 'BILL' . date('Ymd') . rand(1000, 9999);
    
    // Insert into billing
    $sql = "INSERT INTO Billing (PatientID, BillNumber, BillDate, DueDate, Subtotal, TaxAmount, TotalAmount, AmountPaid, PaymentStatus) 
            VALUES ('$patient_id', '$bill_number', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 30 DAY), '$subtotal', '$tax', '$total', 0, 'Pending')";
    
    if($conn->query($sql)) {
        $bill_id = $conn->insert_id;
        
        // Insert bill line items
        foreach($services_data as $service) {
            $description = $conn->real_escape_string($service['description']);
            $line_sql = "INSERT INTO BillLineItem (BillID, ServiceType, ServiceID, Description, Quantity, UnitPrice, LineTotal) 
                        VALUES ('$bill_id', '{$service['type']}', '{$service['id']}', '$description', '{$service['quantity']}', '{$service['price']}', '{$service['line_total']}')";
            
            if(!$conn->query($line_sql)) {
                echo "<script>alert('Error inserting line item: " . $conn->error . "');</script>";
            }
        }
        
        echo "<script>alert('Bill Generated Successfully! Bill #: $bill_number - Total Amount: $$total'); window.location.href='?page=billing';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
    exit;
}

// Handle Make Payment
if(isset($_POST['make_payment'])) {
    $bill_id = $_POST['bill_id'];
    $amount = floatval($_POST['payment_amount']);
    $method = $_POST['payment_method'];
    
    // Get current bill
    $result = $conn->query("SELECT * FROM Billing WHERE BillID = $bill_id");
    $bill = $result->fetch_assoc();
    $new_paid = floatval($bill['AmountPaid']) + $amount;
    $total = floatval($bill['TotalAmount']);
    
    if($new_paid >= $total) {
        $status = 'Paid';
        $new_paid = $total;
    } else {
        $status = 'Partial';
    }
    
    $sql = "UPDATE Billing SET AmountPaid = '$new_paid', PaymentStatus = '$status', PaymentMethod = '$method', PaymentDate = CURDATE() WHERE BillID = $bill_id";
    
    if($conn->query($sql)) {
        echo "<script>alert('Payment of $$amount recorded successfully!'); window.location.href='?page=billing';</script>";
    } else {
        echo "<script>alert('Error: " . $conn->error . "');</script>";
    }
    exit;
}

// Get all bills
$bills = $conn->query("
    SELECT b.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName
    FROM Billing b
    JOIN Patient p ON b.PatientID = p.PatientID
    ORDER BY b.BillDate DESC
");

// Get pending payments
$pending = $conn->query("
    SELECT b.*, CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           DATEDIFF(CURDATE(), b.DueDate) as DaysOverdue
    FROM Billing b
    JOIN Patient p ON b.PatientID = p.PatientID
    WHERE b.PaymentStatus IN ('Pending', 'Partial')
    ORDER BY b.DueDate ASC
");

// Get patients for dropdown
$patients = $conn->query("SELECT PatientID, CONCAT(FirstName, ' ', LastName) as Name FROM Patient WHERE IsActive = 1");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Billing Management</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .nav-tabs { display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #ddd; flex-wrap: wrap; }
        .nav-tab { padding: 10px 20px; cursor: pointer; background: #f8f9fa; border: none; border-radius: 5px 5px 0 0; font-size: 14px; }
        .nav-tab.active { background: #007bff; color: white; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #ddd; font-weight: bold; font-size: 16px; background: #f8f9fa; border-radius: 8px 8px 0 0; }
        .card-body { padding: 20px; }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-control { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        .service-item { 
            background: #f8f9fa; 
            padding: 15px; 
            margin-bottom: 15px; 
            border-radius: 8px;
            border: 1px solid #ddd;
        }
        .service-row {
            display: grid;
            grid-template-columns: 1fr 1fr 0.8fr 0.8fr 0.5fr;
            gap: 10px;
            align-items: center;
        }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; padding: 5px 10px; font-size: 12px; cursor: pointer; }
        .btn-secondary { background: #6c757d; color: white; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .status-paid { background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-pending { background: #fff3cd; color: #856404; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .status-partial { background: #d1ecf1; color: #0c5460; padding: 3px 8px; border-radius: 3px; display: inline-block; }
        .overdue { background: #f8d7da; }
        .modal {
            display: none;
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
            width: 50%;
            max-width: 500px;
            border-radius: 10px;
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
        .summary-box {
            background: #e9ecef;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .service-row { grid-template-columns: 1fr; gap: 8px; }
            .modal-content { width: 90%; margin: 20% auto; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>💰 Billing Management</h2>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('history')">📋 All Bills</button>
            <button class="nav-tab" onclick="showTab('generate')">➕ Generate Bill</button>
            <button class="nav-tab" onclick="showTab('pending')">⏰ Pending Payments</button>
        </div>
        
        <!-- Tab 1: Billing History -->
        <div id="history" class="tab-content active">
            <div class="card">
                <div class="card-header">📋 Billing History</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bill #</th><th>Patient</th><th>Date</th><th>Due Date</th>
                                    <th>Subtotal</th><th>Tax</th><th>Total</th><th>Paid</th>
                                    <th>Balance</th><th>Status</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($bills && $bills->num_rows > 0): ?>
                                    <?php while($row = $bills->fetch_assoc()): 
                                        $balance = floatval($row['TotalAmount']) - floatval($row['AmountPaid']);
                                    ?>
                                    <tr>
                                        <td><?php echo $row['BillNumber']; ?></td>
                                        <td><?php echo $row['PatientName']; ?></td>
                                        <td><?php echo $row['BillDate']; ?></td>
                                        <td><?php echo $row['DueDate']; ?></td>
                                        <td>$<?php echo number_format($row['Subtotal'], 2); ?></td>
                                        <td>$<?php echo number_format($row['TaxAmount'], 2); ?></td>
                                        <td>$<?php echo number_format($row['TotalAmount'], 2); ?></td>
                                        <td>$<?php echo number_format($row['AmountPaid'], 2); ?></td>
                                        <td>$<?php echo number_format($balance, 2); ?></td>
                                        <td><span class="status-<?php echo strtolower($row['PaymentStatus']); ?>"><?php echo $row['PaymentStatus']; ?></span></td>
                                        <td><button class="btn-danger" style="padding: 5px 10px;" onclick="viewBill(<?php echo $row['BillID']; ?>)">View</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="11" style="text-align: center;">No bills found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tab 2: Generate Bill -->
        <div id="generate" class="tab-content">
            <div class="card">
                <div class="card-header">➕ Generate New Bill</div>
                <div class="card-body">
                    <form method="POST" id="billForm">
                        <div class="form-group">
                            <label>Select Patient *</label>
                            <select name="patient_id" id="patient_id" class="form-control" required>
                                <option value="">Choose Patient</option>
                                <?php 
                                $patients->data_seek(0);
                                while($row = $patients->fetch_assoc()): ?>
                                    <option value="<?php echo $row['PatientID']; ?>"><?php echo $row['Name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <h4>Services to Bill</h4>
                        <div id="serviceItems">
                            <div class="service-item">
                                <div class="service-row">
                                    <select name="service_type[]" class="form-control service-type" required>
                                        <option value="">Select Type</option>
                                        <option value="Appointment">Appointment</option>
                                        <option value="Treatment">Treatment</option>
                                        <option value="LabTest">Lab Test</option>
                                    </select>
                                    <select name="service_id[]" class="form-control service-id" disabled required>
                                        <option value="">First select a service type</option>
                                    </select>
                                    <input type="number" name="service_quantity[]" class="form-control service-qty" placeholder="Quantity" value="1" min="1">
                                    <input type="number" step="0.01" name="service_price[]" class="form-control service-price" placeholder="Price" readonly>
                                    <input type="hidden" name="service_description[]" class="service-desc">
                                    <button type="button" class="btn-danger" onclick="removeService(this)">Remove</button>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn-secondary" onclick="addService()" style="margin: 10px 0;">+ Add Another Service</button>
                        
                        <div class="summary-box">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong>Subtotal:</strong> <span id="subtotal">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                <strong>Tax (10%):</strong> <span id="taxAmount">$0.00</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 20px; color: #28a745;">
                                <strong>Total Amount:</strong> <strong id="totalAmount">$0.00</strong>
                            </div>
                        </div>
                        
                        <div style="text-align: right; margin-top: 20px;">
                            <button type="submit" name="generate_bill" class="btn-primary">💰 Generate Bill</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Tab 3: Pending Payments -->
        <div id="pending" class="tab-content">
            <div class="card">
                <div class="card-header">⏰ Pending Payments</div>
                <div class="card-body">
                    <div style="overflow-x: auto;">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Bill #</th><th>Patient</th><th>Bill Date</th><th>Due Date</th>
                                    <th>Total</th><th>Paid</th><th>Balance</th><th>Status</th><th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if($pending && $pending->num_rows > 0): ?>
                                    <?php while($row = $pending->fetch_assoc()): 
                                        $balance = floatval($row['TotalAmount']) - floatval($row['AmountPaid']);
                                    ?>
                                    <tr class="<?php echo ($row['DaysOverdue'] ?? 0) > 0 ? 'overdue' : ''; ?>">
                                        <td><?php echo $row['BillNumber']; ?></td>
                                        <td><?php echo $row['PatientName']; ?></td>
                                        <td><?php echo $row['BillDate']; ?></td>
                                        <td><?php echo $row['DueDate']; ?></td>
                                        <td>$<?php echo number_format($row['TotalAmount'], 2); ?></td>
                                        <td>$<?php echo number_format($row['AmountPaid'], 2); ?></td>
                                        <td>$<?php echo number_format($balance, 2); ?></td>
                                        <td><span class="status-<?php echo strtolower($row['PaymentStatus']); ?>"><?php echo $row['PaymentStatus']; ?></span></td>
                                        <td><button class="btn-success" style="padding: 5px 10px;" onclick="openPaymentModal(<?php echo $row['BillID']; ?>, '<?php echo $row['BillNumber']; ?>', <?php echo $balance; ?>)">Pay Now</button></td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="9" style="text-align: center;">No pending payments</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Modal -->
    <div id="paymentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Make Payment</h3>
                <span class="close" onclick="closePaymentModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" name="bill_id" id="payment_bill_id">
                <div class="form-group">
                    <label>Bill Number:</label>
                    <strong id="payment_bill_number"></strong>
                </div>
                <div class="form-group">
                    <label>Balance Due:</label>
                    <strong id="payment_balance" style="color: #dc3545;"></strong>
                </div>
                <div class="form-group">
                    <label>Payment Amount *</label>
                    <input type="number" step="0.01" name="payment_amount" id="payment_amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Payment Method *</label>
                    <select name="payment_method" class="form-control" required>
                        <option value="Cash">💵 Cash</option>
                        <option value="Credit Card">💳 Credit Card</option>
                        <option value="Debit Card">💳 Debit Card</option>
                        <option value="Insurance">🏥 Insurance</option>
                        <option value="Bank Transfer">🏦 Bank Transfer</option>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 20px;">
                    <button type="button" class="btn-secondary" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" name="make_payment" class="btn-success">Process Payment</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let serviceCounter = 1;
        
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
        
        function addService() {
            const newItem = `
                <div class="service-item">
                    <div class="service-row">
                        <select name="service_type[]" class="form-control service-type" required>
                            <option value="">Select Type</option>
                            <option value="Appointment">Appointment</option>
                            <option value="Treatment">Treatment</option>
                            <option value="LabTest">Lab Test</option>
                        </select>
                        <select name="service_id[]" class="form-control service-id" disabled required>
                            <option value="">First select a service type</option>
                        </select>
                        <input type="number" name="service_quantity[]" class="form-control service-qty" placeholder="Quantity" value="1" min="1">
                        <input type="number" step="0.01" name="service_price[]" class="form-control service-price" placeholder="Price" readonly>
                        <input type="hidden" name="service_description[]" class="service-desc">
                        <button type="button" class="btn-danger" onclick="removeService(this)">Remove</button>
                    </div>
                </div>
            `;
            $('#serviceItems').append(newItem);
        }
        
        function removeService(btn) {
            $(btn).closest('.service-item').remove();
            calculateTotal();
        }
        
        function calculateTotal() {
            let subtotal = 0;
            $('.service-price').each(function() {
                let price = parseFloat($(this).val()) || 0;
                let qty = parseFloat($(this).closest('.service-row').find('.service-qty').val()) || 1;
                subtotal += price * qty;
            });
            
            const tax = subtotal * 0.10;
            const total = subtotal + tax;
            
            $('#subtotal').text('$' + subtotal.toFixed(2));
            $('#taxAmount').text('$' + tax.toFixed(2));
            $('#totalAmount').text('$' + total.toFixed(2));
        }
        
        $(document).on('change', '.service-type', function() {
            const serviceType = $(this).val();
            const serviceRow = $(this).closest('.service-row');
            const serviceSelect = serviceRow.find('.service-id');
            const patientId = $('#patient_id').val();
            
            if(!patientId) {
                alert('Please select a patient first');
                $(this).val('');
                return;
            }
            
            if(serviceType && patientId) {
                serviceSelect.prop('disabled', false);
                serviceSelect.html('<option value="">Loading services...</option>');
                
                $.ajax({
                    url: 'get_services.php',
                    type: 'GET',
                    data: { type: serviceType, patient_id: patientId },
                    dataType: 'json',
                    success: function(data) {
                        serviceSelect.empty();
                        if(data && data.length > 0) {
                            serviceSelect.append('<option value="">Select ' + serviceType + '</option>');
                            $.each(data, function(index, service) {
                                serviceSelect.append(`<option value="${service.id}" data-price="${service.price}" data-desc="${service.name}">${service.name} - $${parseFloat(service.price).toFixed(2)}</option>`);
                            });
                        } else {
                            serviceSelect.append('<option value="">No ' + serviceType.toLowerCase() + 's available for this patient</option>');
                        }
                    },
                    error: function() {
                        serviceSelect.empty();
                        serviceSelect.append('<option value="">Error loading services</option>');
                    }
                });
            } else {
                serviceSelect.prop('disabled', true);
                serviceSelect.empty();
                serviceSelect.append('<option value="">Select Service</option>');
            }
        });
        
        $(document).on('change', '.service-id', function() {
            const selected = $(this).find('option:selected');
            const price = selected.data('price');
            const desc = selected.data('desc');
            const serviceRow = $(this).closest('.service-row');
            
            if(price) {
                serviceRow.find('.service-price').val(price);
                serviceRow.find('.service-desc').val(desc);
                calculateTotal();
            }
        });
        
        $(document).on('input', '.service-qty', function() {
            calculateTotal();
        });
        
        function openPaymentModal(billId, billNumber, balance) {
            document.getElementById('payment_bill_id').value = billId;
            document.getElementById('payment_bill_number').innerText = billNumber;
            document.getElementById('payment_balance').innerHTML = '$' + balance.toFixed(2);
            document.getElementById('payment_amount').value = balance.toFixed(2);
            document.getElementById('paymentModal').style.display = 'block';
        }
        
        function closePaymentModal() {
            document.getElementById('paymentModal').style.display = 'none';
        }
        
        function viewBill(billId) {
            window.open('view_bill_by_id.php?id=' + billId, '_blank', 'width=900,height=700');
        }
        
        window.onclick = function(event) {
            let modal = document.getElementById('paymentModal');
            if(event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>