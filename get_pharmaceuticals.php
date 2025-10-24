<?php
header('Content-Type: application/json');
include 'includes/conn.php';

try {
    // Query pharmaceuticals with their stock levels
    $query = "SELECT pharma_id, name, category, stock, unit 
              FROM pharmaceuticals 
              ORDER BY name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $pharmaceuticals = [];
    while ($row = $result->fetch_assoc()) {
        $pharmaceuticals[] = [
            'pharma_id' => $row['pharma_id'],
            'name' => $row['name'],
            'category' => $row['category'],
            'stock' => $row['stock'],
            'unit' => $row['unit'] ?? 'units'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'pharmaceuticals' => $pharmaceuticals,
        'count' => count($pharmaceuticals)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load pharmaceuticals',
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>

