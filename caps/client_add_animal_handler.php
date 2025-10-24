<?php
session_start();
include 'includes/conn.php';

// Set headers for JSON response
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['client_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

try {
    $client_id = $_SESSION['client_id'];
    $type = $_POST['type'];
    $species = $_POST['species'];
    $sex = $_POST['sex'] ?? 'N/A';
    
    
    // Some fields might be disabled on the client (e.g., Poultry),
    // which means they won't be sent in the POST body. Provide safe defaults.
    $quantity = isset($_POST['quantity']) && $_POST['quantity'] !== '' ? $_POST['quantity'] : '1';
    $weight = isset($_POST['weight']) && $_POST['weight'] !== '' ? $_POST['weight'] : 'N/A';
    $health_status = isset($_POST['health_status']) && $_POST['health_status'] !== '' ? $_POST['health_status'] : 'N/A';
    $source = $_POST['source'] ?? 'Owned';

    // Log received data
    error_log("Adding animal - Client ID: $client_id, Type: $type, Species: $species, Sex: $sex, Source: $source");

    $sql = "INSERT INTO livestock_poultry (client_id, animal_type, species, sex, quantity, weight, health_status, source) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("isssssss", $client_id, $type, $species, $sex, $quantity, $weight, $health_status, $source);
    
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error . " SQL: " . $sql);
    }

    // If source is Disseminated, also insert into compliance table
    if (strcasecmp($source, 'Disseminated') === 0) {
        try {
            $compSql = "INSERT INTO compliance (client_id, animal_type, species, compliance_status, transfer_date) 
                        VALUES (?, ?, ?, 'Pending', CURDATE())";
            $compStmt = $conn->prepare($compSql);
            if ($compStmt) {
                $compStmt->bind_param("iss", $client_id, $type, $species);
                $compStmt->execute();
                $compStmt->close();
            } else {
                error_log('Compliance insert prepare failed: ' . $conn->error);
            }
        } catch (Exception $inner) {
            error_log('Compliance insert error: ' . $inner->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'animal_id' => $conn->insert_id,
        'message' => 'Animal added successfully'
    ]);

} catch (Exception $e) {
    error_log("Error in add_animal_handler.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>