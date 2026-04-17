<?php
require_once 'db_config.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

$result = $conn->query("
    SELECT plt.*, 
           CONCAT(p.FirstName, ' ', p.LastName) as PatientName,
           p.DateOfBirth, p.Gender,
           lt.TestName, lt.NormalRange, lt.Description as TestDescription,
           CONCAT(d.FirstName, ' ', d.LastName) as DoctorName
    FROM PatientLabTest plt
    JOIN Patient p ON plt.PatientID = p.PatientID
    JOIN LabTest lt ON plt.LabTestID = lt.LabTestID
    LEFT JOIN Doctor d ON plt.OrderedByDoctorID = d.DoctorID
    WHERE plt.PatientLabTestID = $id
");

$test = $result->fetch_assoc();

if(!$test) {
    die("Test not found");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Lab Report - <?php echo htmlspecialchars($test['TestName']); ?></title>
    <style>
        body { font-family: Arial; padding: 20px; }
        .report { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #007bff; padding-bottom: 20px; margin-bottom: 20px; }
        .info-row { display: flex; margin-bottom: 10px; }
        .label { width: 150px; font-weight: bold; }
        .result-box { background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; }
        button { padding: 10px 20px; margin: 10px; cursor: pointer; }
        @media print {
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="report">
        <div class="header">
            <h2>🏥 LABORATORY REPORT</h2>
            <p><?php echo date('F d, Y', strtotime($test['ResultDate'] ?: $test['OrderDate'])); ?></p>
        </div>
        
        <div class="info-row">
            <div class="label">Patient Name:</div>
            <div><?php echo htmlspecialchars($test['PatientName']); ?></div>
        </div>
        <div class="info-row">
            <div class="label">Date of Birth:</div>
            <div><?php echo $test['DateOfBirth']; ?> (Age: <?php echo date_diff(date_create($test['DateOfBirth']), date_create('today'))->y; ?> years)</div>
        </div>
        <div class="info-row">
            <div class="label">Gender:</div>
            <div><?php echo $test['Gender']; ?></div>
        </div>
        <div class="info-row">
            <div class="label">Test Name:</div>
            <div><strong><?php echo htmlspecialchars($test['TestName']); ?></strong></div>
        </div>
        <div class="info-row">
            <div class="label">Order Date:</div>
            <div><?php echo date('F d, Y', strtotime($test['OrderDate'])); ?></div>
        </div>
        <div class="info-row">
            <div class="label">Ordered By:</div>
            <div>Dr. <?php echo htmlspecialchars($test['DoctorName']); ?></div>
        </div>
        
        <div class="result-box">
            <h3>Test Results</h3>
            <div class="info-row">
                <div class="label">Result:</div>
                <div><strong><?php echo nl2br(htmlspecialchars($test['ResultValue'] ?: 'Pending')); ?></strong></div>
            </div>
            <div class="info-row">
                <div class="label">Normal Range:</div>
                <div><?php echo htmlspecialchars($test['NormalRange'] ?: 'N/A'); ?></div>
            </div>
        </div>
        
        <div class="info-row">
            <div class="label">Notes:</div>
            <div><?php echo nl2br(htmlspecialchars($test['Notes'] ?: 'No additional notes')); ?></div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <button onclick="window.print()">🖨️ Print Report</button>
            <button onclick="window.close()">❌ Close</button>
        </div>
        
        <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666;">
            This is a computer generated report. No signature required.
        </div>
    </div>
</body>
</html>