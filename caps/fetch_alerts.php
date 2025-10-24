<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$alerts = [];

// 1. Low stock (≤ 50) - get up to 5 items
$lowStockQuery = "SELECT name, stock FROM pharmaceuticals WHERE stock <= 50 ORDER BY stock ASC LIMIT 5";
$lowResult = $conn->query($lowStockQuery);
while (($row = $lowResult->fetch_assoc()) && count($alerts) < 5) {
    $alerts[] = [
        'type' => 'Low Stock',
        'message' => "{$row['name']} ({$row['stock']} units left)"
    ];
}

// 2. Expiring within 30 days
$expiringQuery = "SELECT name, expiry_date FROM pharmaceuticals 
                  WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                  ORDER BY expiry_date ASC LIMIT 3";
$expResult = $conn->query($expiringQuery);
while (($row = $expResult->fetch_assoc()) && count($alerts) < 8) {
    $days = (new DateTime())->diff(new DateTime($row['expiry_date']))->days;
    $alerts[] = [
        'type' => 'Expiring Soon',
        'message' => "{$row['name']} ({$days} days)"
    ];
}

// 3. Pending pharmaceutical requests
$pendingQuery = "SELECT COUNT(*) as count FROM transactions WHERE status = 'Pending'";
$pendingResult = $conn->query($pendingQuery);
$pendingCount = $pendingResult->fetch_assoc()['count'];
if ($pendingCount > 0 && count($alerts) < 8) {
    $alerts[] = [
        'type' => 'Pending Requests',
        'message' => "{$pendingCount} pharmaceutical requests awaiting approval"
    ];
}

// 4. Animals needing vaccination (based on last vaccination date)
$vaccinationQuery = "SELECT COUNT(*) as count FROM livestock_poultry 
                     WHERE last_vaccination < DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                     OR last_vaccination IS NULL";
$vaccinationResult = $conn->query($vaccinationQuery);
$vaccinationCount = $vaccinationResult->fetch_assoc()['count'];
if ($vaccinationCount > 0 && count($alerts) < 8) {
    $alerts[] = [
        'type' => 'Vaccination Due',
        'message' => "{$vaccinationCount} animals need vaccination"
    ];
}

// 5. Health issues (animals with health status other than 'Healthy')
$healthQuery = "SELECT COUNT(*) as count FROM livestock_poultry WHERE health_status != 'Healthy'";
$healthResult = $conn->query($healthQuery);
$healthCount = $healthResult->fetch_assoc()['count'];
if ($healthCount > 0 && count($alerts) < 8) {
    $alerts[] = [
        'type' => 'Health Issues',
        'message' => "{$healthCount} animals have health concerns"
    ];
}

// Limit to 8 total alerts
$alerts = array_slice($alerts, 0, 8);

echo json_encode($alerts);
?>
