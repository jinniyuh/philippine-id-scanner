<?php
session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

try {
    // Get monthly transaction data for the last 6 months
    $sql = "SELECT 
                DATE_FORMAT(request_date, '%Y-%m') as month,
                COUNT(*) as transaction_count,
                SUM(quantity) as total_quantity
            FROM transactions 
            WHERE status = 'Approved' 
            AND request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(request_date, '%Y-%m')
            ORDER BY month";
    
    $result = $conn->query($sql);
    $monthly_data = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $monthly_data[$row['month']] = [
                'transactions' => (int)$row['transaction_count'],
                'quantity' => (int)$row['total_quantity']
            ];
        }
    }
    
    // Generate labels for the last 6 months
    $labels = [];
    $transactions = [];
    $pharmaceuticals = [];
    
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $month_name = date('M', strtotime($month));
        $labels[] = $month_name;
        
        if (isset($monthly_data[$month])) {
            $transactions[] = $monthly_data[$month]['transactions'];
            $pharmaceuticals[] = $monthly_data[$month]['quantity'];
        } else {
            $transactions[] = 0;
            $pharmaceuticals[] = 0;
        }
    }
    
    echo json_encode([
        'success' => true,
        'labels' => $labels,
        'transactions' => $transactions,
        'pharmaceuticals' => $pharmaceuticals,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch monthly trends',
        'message' => $e->getMessage()
    ]);
}
?>
