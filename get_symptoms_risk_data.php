<?php
/**
 * API Endpoint: Get Symptoms Risk Data from Pharmaceutical Requests
 * Pulls symptoms from pharmaceutical requests and categorizes them by risk level
 */

session_start();
include 'includes/conn.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

try {
    // Get symptoms data from pharmaceutical requests
    $symptoms_query = "
        SELECT 
            pr.request_id,
            pr.client_id,
            pr.species,
            pr.symptoms,
            pr.request_date,
            c.full_name as client_name,
            c.barangay
        FROM pharmaceutical_requests pr
        LEFT JOIN clients c ON pr.client_id = c.client_id
        WHERE pr.symptoms IS NOT NULL 
        AND pr.symptoms != ''
        AND pr.request_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY pr.request_date DESC
    ";
    
    $result = $conn->query($symptoms_query);
    $symptoms_data = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $symptoms_data[] = $row;
        }
    }
    
    // Categorize symptoms by risk level
    $risk_categories = [
        'Low' => [],
        'Medium' => [],
        'High' => [],
        'Critical' => []
    ];
    
    foreach ($symptoms_data as $request) {
        $symptoms = $request['symptoms'];
        $risk_level = categorizeSymptomsRisk($symptoms);
        
        $request['risk_level'] = $risk_level;
        $request['risk_score'] = calculateRiskScore($symptoms);
        $request['symptoms_array'] = explode(';', $symptoms);
        
        $risk_categories[$risk_level][] = $request;
    }
    
    // Calculate summary statistics
    $summary = [
        'Low' => ['count' => count($risk_categories['Low']), 'percentage' => 0],
        'Medium' => ['count' => count($risk_categories['Medium']), 'percentage' => 0],
        'High' => ['count' => count($risk_categories['High']), 'percentage' => 0],
        'Critical' => ['count' => count($risk_categories['Critical']), 'percentage' => 0]
    ];
    
    $total_requests = count($symptoms_data);
    if ($total_requests > 0) {
        foreach ($summary as $level => $data) {
            $summary[$level]['percentage'] = round(($data['count'] / $total_requests) * 100, 1);
        }
    }
    
    // Get high-risk animals requiring immediate attention
    $high_risk_animals = array_merge($risk_categories['High'], $risk_categories['Critical']);
    
    // Sort by risk score (highest first)
    usort($high_risk_animals, function($a, $b) {
        return $b['risk_score'] - $a['risk_score'];
    });
    
    echo json_encode([
        'success' => true,
        'summary' => $summary,
        'risk_categories' => $risk_categories,
        'high_risk_animals' => array_slice($high_risk_animals, 0, 10), // Top 10 high-risk
        'total_requests' => $total_requests,
        'generated_at' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch symptoms data: ' . $e->getMessage()
    ]);
}

/**
 * Categorize symptoms into risk levels
 */
function categorizeSymptomsRisk($symptoms) {
    $symptoms_lower = strtolower($symptoms);
    
    // Critical Risk Symptoms (80-100 points)
    if (strpos($symptoms_lower, 'sudden death') !== false || 
        strpos($symptoms_lower, 'biglaang pagkamatay') !== false ||
        strpos($symptoms_lower, 'paralysis') !== false ||
        strpos($symptoms_lower, 'pagkaparalisa') !== false) {
        return 'Critical';
    }
    
    // High Risk Symptoms (60-79 points)
    if (strpos($symptoms_lower, 'difficulty breathing') !== false || 
        strpos($symptoms_lower, 'hirap huminga') !== false ||
        strpos($symptoms_lower, 'gasping') !== false ||
        strpos($symptoms_lower, 'convulsions') !== false ||
        strpos($symptoms_lower, 'kombulsyon') !== false ||
        strpos($symptoms_lower, 'seizures') !== false ||
        strpos($symptoms_lower, 'swollen neck') !== false ||
        strpos($symptoms_lower, 'namamagang leeg') !== false) {
        return 'High';
    }
    
    // Medium Risk Symptoms (40-59 points)
    if (strpos($symptoms_lower, 'high fever') !== false || 
        strpos($symptoms_lower, 'mataas na lagnat') !== false ||
        strpos($symptoms_lower, 'diarrhea') !== false ||
        strpos($symptoms_lower, 'pagtatae') !== false ||
        strpos($symptoms_lower, 'swollen') !== false ||
        strpos($symptoms_lower, 'namamaga') !== false ||
        strpos($symptoms_lower, 'vomiting') !== false ||
        strpos($symptoms_lower, 'pagsusuka') !== false ||
        strpos($symptoms_lower, 'red spots') !== false ||
        strpos($symptoms_lower, 'mapupulang batik') !== false) {
        return 'Medium';
    }
    
    // Low Risk Symptoms (0-39 points)
    return 'Low';
}

/**
 * Calculate risk score based on symptoms
 */
function calculateRiskScore($symptoms) {
    $score = 0;
    $symptoms_lower = strtolower($symptoms);
    
    // High-risk symptoms (30-50 points)
    if (strpos($symptoms_lower, 'sudden death') !== false || 
        strpos($symptoms_lower, 'biglaang pagkamatay') !== false) {
        $score += 50;
    }
    if (strpos($symptoms_lower, 'difficulty breathing') !== false || 
        strpos($symptoms_lower, 'hirap huminga') !== false ||
        strpos($symptoms_lower, 'gasping') !== false) {
        $score += 40;
    }
    if (strpos($symptoms_lower, 'convulsions') !== false || 
        strpos($symptoms_lower, 'kombulsyon') !== false ||
        strpos($symptoms_lower, 'seizures') !== false) {
        $score += 45;
    }
    if (strpos($symptoms_lower, 'paralysis') !== false || 
        strpos($symptoms_lower, 'pagkaparalisa') !== false) {
        $score += 40;
    }
    
    // Medium-risk symptoms (15-25 points)
    if (strpos($symptoms_lower, 'high fever') !== false || 
        strpos($symptoms_lower, 'mataas na lagnat') !== false) {
        $score += 25;
    }
    if (strpos($symptoms_lower, 'diarrhea') !== false || 
        strpos($symptoms_lower, 'pagtatae') !== false) {
        $score += 20;
    }
    if (strpos($symptoms_lower, 'swollen') !== false || 
        strpos($symptoms_lower, 'namamaga') !== false) {
        $score += 20;
    }
    if (strpos($symptoms_lower, 'vomiting') !== false || 
        strpos($symptoms_lower, 'pagsusuka') !== false) {
        $score += 18;
    }
    if (strpos($symptoms_lower, 'weakness') !== false || 
        strpos($symptoms_lower, 'panghihina') !== false) {
        $score += 15;
    }
    
    // Low-risk symptoms (5-15 points)
    if (strpos($symptoms_lower, 'weight loss') !== false || 
        strpos($symptoms_lower, 'pagpayat') !== false) {
        $score += 10;
    }
    if (strpos($symptoms_lower, 'behavior') !== false || 
        strpos($symptoms_lower, 'ugali') !== false) {
        $score += 8;
    }
    if (strpos($symptoms_lower, 'lameness') !== false || 
        strpos($symptoms_lower, 'hirap tumayo') !== false) {
        $score += 12;
    }
    
    return min(100, $score); // Cap at 100
}

$conn->close();
?>
