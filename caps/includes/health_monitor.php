<?php
/**
 * Health Monitoring System for Veterinary Management
 * 
 * Tracks and monitors animal health, vaccinations, and health checks
 */

class HealthMonitor {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get upcoming vaccinations
     */
    public function getUpcomingVaccinations($days_ahead = 30) {
        $sql = "SELECT 
                    l.client_id,
                    c.full_name as client_name,
                    l.species,
                    l.quantity,
                    l.last_vaccination,
                    DATE_ADD(l.last_vaccination, INTERVAL 365 DAY) as next_vaccination_date,
                    DATEDIFF(DATE_ADD(l.last_vaccination, INTERVAL 365 DAY), CURDATE()) as days_until_vaccination
                FROM livestock_poultry l
                JOIN clients c ON l.client_id = c.client_id
                WHERE l.last_vaccination IS NOT NULL
                AND DATE_ADD(l.last_vaccination, INTERVAL 365 DAY) BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
                ORDER BY next_vaccination_date ASC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days_ahead);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $vaccinations = [];
        while ($row = $result->fetch_assoc()) {
            $vaccinations[] = $row;
        }
        
        return $vaccinations;
    }
    
    /**
     * Get overdue vaccinations
     */
    public function getOverdueVaccinations() {
        $sql = "SELECT 
                    l.client_id,
                    c.full_name as client_name,
                    l.species,
                    l.quantity,
                    l.last_vaccination,
                    DATE_ADD(l.last_vaccination, INTERVAL 365 DAY) as next_vaccination_date,
                    DATEDIFF(CURDATE(), DATE_ADD(l.last_vaccination, INTERVAL 365 DAY)) as days_overdue
                FROM livestock_poultry l
                JOIN clients c ON l.client_id = c.client_id
                WHERE l.last_vaccination IS NOT NULL
                AND DATE_ADD(l.last_vaccination, INTERVAL 365 DAY) < CURDATE()
                ORDER BY days_overdue DESC
                LIMIT 10";
        
        $result = $this->conn->query($sql);
        $overdue = [];
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $overdue[] = $row;
            }
        }
        
        return $overdue;
    }
    
    /**
     * Get recent health checks
     */
    public function getRecentHealthChecks($days_back = 30) {
        $sql = "SELECT 
                    l.client_id,
                    c.full_name as client_name,
                    l.species,
                    l.quantity,
                    l.updated_at as last_health_check_date,
                    l.health_status,
                    DATEDIFF(CURDATE(), l.updated_at) as days_since_check
                FROM livestock_poultry l
                JOIN clients c ON l.client_id = c.client_id
                WHERE l.health_status IS NOT NULL
                AND l.updated_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY l.updated_at DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days_back);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $health_checks = [];
        while ($row = $result->fetch_assoc()) {
            $health_checks[] = $row;
        }
        
        return $health_checks;
    }
    
    /**
     * Get animals needing health checks
     */
    public function getAnimalsNeedingHealthChecks($days_overdue = 90) {
        $sql = "SELECT 
                    l.client_id,
                    c.full_name as client_name,
                    l.species,
                    l.quantity,
                    l.updated_at as last_health_check_date,
                    DATEDIFF(CURDATE(), l.updated_at) as days_since_check
                FROM livestock_poultry l
                JOIN clients c ON l.client_id = c.client_id
                WHERE l.health_status IS NULL 
                OR l.updated_at < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                ORDER BY days_since_check DESC
                LIMIT 10";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $days_overdue);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $needing_checks = [];
        while ($row = $result->fetch_assoc()) {
            $needing_checks[] = $row;
        }
        
        return $needing_checks;
    }
    
    /**
     * Get health monitoring summary
     */
    public function getHealthSummary() {
        // Get total animals
        $result = $this->conn->query("SELECT SUM(quantity) as total FROM livestock_poultry");
        $total_animals = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $total_animals = (int)($row['total'] ?? 0);
        }
        
        // Get animals with recent health checks (last 30 days)
        $result = $this->conn->query("
            SELECT SUM(quantity) as total 
            FROM livestock_poultry 
            WHERE health_status IS NOT NULL 
            AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $recent_checks = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $recent_checks = (int)($row['total'] ?? 0);
        }
        
        // Get animals with recent vaccinations (last 30 days)
        $result = $this->conn->query("
            SELECT SUM(quantity) as total 
            FROM livestock_poultry 
            WHERE last_vaccination >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $recent_vaccinations = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $recent_vaccinations = (int)($row['total'] ?? 0);
        }
        
        // Get overdue vaccinations
        $result = $this->conn->query("
            SELECT SUM(quantity) as total 
            FROM livestock_poultry 
            WHERE last_vaccination IS NOT NULL 
            AND DATE_ADD(last_vaccination, INTERVAL 365 DAY) < CURDATE()
        ");
        $overdue_vaccinations = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $overdue_vaccinations = (int)($row['total'] ?? 0);
        }
        
        // Get animals needing health checks
        $result = $this->conn->query("
            SELECT SUM(quantity) as total 
            FROM livestock_poultry 
            WHERE health_status IS NULL 
            OR updated_at < DATE_SUB(CURDATE(), INTERVAL 90 DAY)
        ");
        $needing_checks = 0;
        if ($result && $row = $result->fetch_assoc()) {
            $needing_checks = (int)($row['total'] ?? 0);
        }
        
        return [
            'total_animals' => $total_animals,
            'recent_health_checks' => $recent_checks,
            'recent_vaccinations' => $recent_vaccinations,
            'overdue_vaccinations' => $overdue_vaccinations,
            'needing_health_checks' => $needing_checks,
            'health_check_percentage' => $total_animals > 0 ? round(($recent_checks / $total_animals) * 100, 1) : 0,
            'vaccination_percentage' => $total_animals > 0 ? round(($recent_vaccinations / $total_animals) * 100, 1) : 0
        ];
    }
    
    /**
     * Update health check date
     */
    public function updateHealthCheck($client_id, $species, $health_status = 'Healthy') {
        $sql = "UPDATE livestock_poultry 
                SET updated_at = CURRENT_TIMESTAMP, health_status = ?
                WHERE client_id = ? AND species = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("sis", $health_status, $client_id, $species);
        
        return $stmt->execute();
    }
    
    /**
     * Update vaccination date
     */
    public function updateVaccination($client_id, $species) {
        $sql = "UPDATE livestock_poultry 
                SET last_vaccination = CURDATE(), updated_at = CURRENT_TIMESTAMP
                WHERE client_id = ? AND species = ?";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $client_id, $species);
        
        return $stmt->execute();
    }
    
    /**
     * Get health alerts for dashboard
     */
    public function getHealthAlerts() {
        $alerts = [];
        
        // Check for overdue vaccinations
        $overdue_vaccinations = $this->getOverdueVaccinations();
        if (!empty($overdue_vaccinations)) {
            $alerts[] = [
                'type' => 'vaccination_overdue',
                'severity' => 'high',
                'title' => 'Overdue Vaccinations',
                'message' => count($overdue_vaccinations) . ' animal(s) have overdue vaccinations',
                'count' => count($overdue_vaccinations),
                'data' => array_slice($overdue_vaccinations, 0, 3)
            ];
        }
        
        // Check for animals needing health checks
        $needing_checks = $this->getAnimalsNeedingHealthChecks();
        if (!empty($needing_checks)) {
            $alerts[] = [
                'type' => 'health_check_needed',
                'severity' => 'medium',
                'title' => 'Health Checks Needed',
                'message' => count($needing_checks) . ' animal(s) need health checks',
                'count' => count($needing_checks),
                'data' => array_slice($needing_checks, 0, 3)
            ];
        }
        
        // Check for upcoming vaccinations
        $upcoming_vaccinations = $this->getUpcomingVaccinations(7); // Next 7 days
        if (!empty($upcoming_vaccinations)) {
            $alerts[] = [
                'type' => 'vaccination_upcoming',
                'severity' => 'low',
                'title' => 'Upcoming Vaccinations',
                'message' => count($upcoming_vaccinations) . ' vaccination(s) due in the next 7 days',
                'count' => count($upcoming_vaccinations),
                'data' => array_slice($upcoming_vaccinations, 0, 3)
            ];
        }
        
        return $alerts;
    }
}
?>
