<?php
/**
 * Global Alert Data API
 * Provides alert data for the global alert system
 */

session_start();
include 'includes/conn.php';
include 'includes/global_alert.php';

header('Content-Type: application/json');

try {
    $globalAlert = new GlobalAlert($conn);
    $alertData = $globalAlert->getAlertData();
    
    if ($alertData) {
        echo json_encode([
            'success' => true,
            'alert' => $alertData,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'alert' => null,
            'message' => 'No alerts at this time'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to check alerts: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
