<?php
require_once 'db_config.php';

// Cache results for 5 minutes to reduce database load
$cacheFile = 'cache/stats_cache.json';
$cacheTime = 300; // 5 minutes

if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTime) {
    // Return cached data
    echo file_get_contents($cacheFile);
    exit;
}

// Get stats with optimized queries
$completed = $conn->query("SELECT COUNT(*) as count FROM Appointment WHERE DATE(AppointmentDateTime) = CURDATE() AND Status = 'Completed'")->fetch_assoc()['count'];
$pendingBills = $conn->query("SELECT COUNT(*) as count FROM Billing WHERE PaymentStatus IN ('Pending', 'Partial')")->fetch_assoc()['count'];
$activeTreatments = $conn->query("SELECT COUNT(*) as count FROM PatientTreatment WHERE Status IN ('Scheduled', 'Ongoing')")->fetch_assoc()['count'];

$stats = [
    'completed' => $completed,
    'pendingBills' => $pendingBills,
    'activeTreatments' => $activeTreatments
];

// Create cache directory if not exists
if (!is_dir('cache')) {
    mkdir('cache', 0777, true);
}

// Save to cache
file_put_contents($cacheFile, json_encode($stats));

echo json_encode($stats);
?>