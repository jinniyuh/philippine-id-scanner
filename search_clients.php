<?php
include 'includes/conn.php';

$query = $_GET['query'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT client_id, full_name FROM clients WHERE full_name LIKE CONCAT('%', ?, '%') LIMIT 10");
$stmt->bind_param("s", $query);
$stmt->execute();
$result = $stmt->get_result();

$clients = [];
while ($row = $result->fetch_assoc()) {
    $clients[] = [
        'client_id' => $row['client_id'],
        'full_name' => $row['full_name']
    ];
}

echo json_encode($clients);
?>
