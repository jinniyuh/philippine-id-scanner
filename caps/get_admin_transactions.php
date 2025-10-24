<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$status = $_GET['status'] ?? '';

if ($status === 'all') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.request_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              ORDER BY t.request_date DESC";
} elseif ($status === 'approved') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.request_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status IN ('Approved', 'Issued')
              ORDER BY t.request_date DESC";
} elseif ($status === 'pending') {
    $query = "SELECT t.transaction_id, t.quantity, t.status, t.request_date, 
                     c.full_name as client_name, p.name as pharma_name
              FROM transactions t
              JOIN clients c ON t.client_id = c.client_id
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status = 'Pending'
              ORDER BY t.request_date DESC";
} elseif ($status === 'dispensed') {
    $query = "SELECT p.name as pharma_name, 
                     SUM(t.quantity) as total_dispensed,
                     COUNT(t.transaction_id) as times_dispensed,
                     MAX(t.request_date) as last_dispensed
              FROM transactions t
              JOIN pharmaceuticals p ON t.pharma_id = p.pharma_id
              WHERE t.status IN ('Approved', 'Issued')
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

header('Content-Type: application/json');
echo json_encode($data);
?>