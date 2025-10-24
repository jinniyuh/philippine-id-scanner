<?php
session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

$client_id = $_POST['client_id'] ?? '';
$compliance_status = $_POST['compliance_status'] ?? '';
$compliance_remarks = $_POST['compliance_remarks'] ?? '';
$compliance_date = $_POST['compliance_date'] ?? '';

if (empty($client_id) || empty($compliance_status)) {
    echo json_encode(['success' => false, 'error' => 'Client ID and compliance status are required']);
    exit();
}

// Validate remarks for Non-Compliant status
if ($compliance_status === 'Non-Compliant' && empty($compliance_remarks)) {
    echo json_encode(['success' => false, 'error' => 'Remarks are required when status is Non-Compliant']);
    exit();
}

// Validate compliance_date for Complied status
if ($compliance_status === 'Complied' && empty($compliance_date)) {
    echo json_encode(['success' => false, 'error' => 'Compliance date is required when status is Complied']);
    exit();
}

try {
    // Debug: Log the received data
    error_log("Compliance Update - Client ID: $client_id, Status: $compliance_status, Compliance Date: $compliance_date");
    
    // Check if client exists in compliance table
    $check_stmt = $conn->prepare("SELECT compliance_status FROM compliance WHERE client_id = ?");
    $check_stmt->bind_param("i", $client_id);
    $check_stmt->execute();
    $current_status = $check_stmt->get_result()->fetch_assoc();
    error_log("Current compliance status: " . ($current_status['compliance_status'] ?? 'Not found'));
    
    // Check if remarks column exists, if not add it
    $check_remarks_column = "SHOW COLUMNS FROM compliance LIKE 'remarks'";
    $check_remarks_result = $conn->query($check_remarks_column);
    
    if (!$check_remarks_result || $check_remarks_result->num_rows == 0) {
        // Add the remarks column
        $add_remarks_column = "ALTER TABLE compliance ADD COLUMN remarks TEXT NULL AFTER compliance_status";
        $conn->query($add_remarks_column);
    }
    
    // Check if compliance_date column exists, if not add it
    $check_compliance_date_column = "SHOW COLUMNS FROM compliance LIKE 'compliance_date'";
    $check_compliance_date_result = $conn->query($check_compliance_date_column);
    
    if (!$check_compliance_date_result || $check_compliance_date_result->num_rows == 0) {
        // Add the compliance_date column
        $add_compliance_date_column = "ALTER TABLE compliance ADD COLUMN compliance_date DATETIME NULL AFTER remarks";
        $conn->query($add_compliance_date_column);
    }
    
    // Prepare the update query based on status
    if ($compliance_status === 'Complied') {
        // Convert date to proper DATETIME format
        $compliance_datetime = $compliance_date . ' 00:00:00';
        // Set compliance_date when status is Complied
        $stmt = $conn->prepare("UPDATE compliance SET compliance_status = ?, remarks = ?, compliance_date = ? WHERE client_id = ?");
        $stmt->bind_param("sssi", $compliance_status, $compliance_remarks, $compliance_datetime, $client_id);
    } else {
        // Don't update compliance_date for other statuses
        $stmt = $conn->prepare("UPDATE compliance SET compliance_status = ?, remarks = ? WHERE client_id = ?");
        $stmt->bind_param("ssi", $compliance_status, $compliance_remarks, $client_id);
    }
    
    if ($stmt->execute()) {
        $affected_rows = $stmt->affected_rows;
        error_log("Update executed. Affected rows: $affected_rows");
        
        // Debug: Check if compliance_date was actually stored
        if ($compliance_status === 'Complied') {
            $check_date_stmt = $conn->prepare("SELECT compliance_date FROM compliance WHERE client_id = ?");
            $check_date_stmt->bind_param("i", $client_id);
            $check_date_stmt->execute();
            $stored_date = $check_date_stmt->get_result()->fetch_assoc();
            error_log("Stored compliance_date: " . ($stored_date['compliance_date'] ?? 'NULL'));
            error_log("Original compliance_date: $compliance_date, Converted: " . $compliance_date . ' 00:00:00');
        }
        
        // Log the activity
        $user_id = $_SESSION['user_id'];
        $action = "Updated compliance status to '{$compliance_status}' for client ID {$client_id}";
        $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
        $log_stmt->bind_param("is", $user_id, $action);
        $log_stmt->execute();
        
        echo json_encode(['success' => true, 'affected_rows' => $affected_rows]);
    } else {
        error_log("Update failed: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Failed to update compliance status: ' . $stmt->error]);
    }
    $stmt->close();
} catch (Exception $e) {
    error_log("Error updating compliance status: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error occurred']);
}

$conn->close();
?>
