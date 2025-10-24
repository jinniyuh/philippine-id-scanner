<?php
session_start();
include 'includes/conn.php';
include 'includes/health_monitor.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

$healthMonitor = new HealthMonitor($conn);

try {
    $action = $_GET['action'] ?? 'summary';
    
    switch ($action) {
        case 'summary':
            $summary = $healthMonitor->getHealthSummary();
            $alerts = $healthMonitor->getHealthAlerts();
            
            echo json_encode([
                'success' => true,
                'summary' => $summary,
                'alerts' => $alerts,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
            break;
            
        case 'upcoming_vaccinations':
            $days = intval($_GET['days'] ?? 30);
            $vaccinations = $healthMonitor->getUpcomingVaccinations($days);
            
            echo json_encode([
                'success' => true,
                'vaccinations' => $vaccinations,
                'count' => count($vaccinations)
            ]);
            break;
            
        case 'overdue_vaccinations':
            $vaccinations = $healthMonitor->getOverdueVaccinations();
            
            echo json_encode([
                'success' => true,
                'vaccinations' => $vaccinations,
                'count' => count($vaccinations)
            ]);
            break;
            
        case 'recent_health_checks':
            $days = intval($_GET['days'] ?? 30);
            $health_checks = $healthMonitor->getRecentHealthChecks($days);
            
            echo json_encode([
                'success' => true,
                'health_checks' => $health_checks,
                'count' => count($health_checks)
            ]);
            break;
            
        case 'needing_health_checks':
            $days = intval($_GET['days'] ?? 90);
            $animals = $healthMonitor->getAnimalsNeedingHealthChecks($days);
            
            echo json_encode([
                'success' => true,
                'animals' => $animals,
                'count' => count($animals)
            ]);
            break;
            
        case 'alerts':
            $alerts = $healthMonitor->getHealthAlerts();
            
            echo json_encode([
                'success' => true,
                'alerts' => $alerts,
                'count' => count($alerts)
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to fetch health monitoring data',
        'message' => $e->getMessage()
    ]);
}
?>
