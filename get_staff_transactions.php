<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = $_GET['status'] ?? '';

if ($status === 'all') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.issued_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.issued_date IS NOT NULL
              ORDER BY t.issued_date DESC";
} elseif ($status === 'approved') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.issued_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status IN ('Approved', 'Issued') AND t.issued_date IS NOT NULL
              ORDER BY t.issued_date DESC";
} elseif ($status === 'pending') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.issued_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status = 'Pending' AND t.issued_date IS NOT NULL
              ORDER BY t.issued_date DESC";
} elseif ($status === 'dispensed') {
    $query = "SELECT p.name as pharma_name, 
                     SUM(t.quantity) as total_dispensed,
                     COUNT(t.transaction_id) as times_dispensed,
                     MAX(t.issued_date) as last_dispensed
              FROM transactions t
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status IN ('Approved', 'Issued') AND t.issued_date IS NOT NULL
              GROUP BY p.pharma_id, p.name
              ORDER BY total_dispensed DESC";
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status parameter']);
    exit();
}

$result = mysqli_query($conn, $query);

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => 'Database query failed: ' . mysqli_error($conn)]);
    exit();
}

$data = [];
while ($row = mysqli_fetch_assoc($result)) {
    $data[] = $row;
}

// Debug logging
error_log("Staff Transactions API - Status: $status, Count: " . count($data));

header('Content-Type: application/json');
echo json_encode($data);
?>
