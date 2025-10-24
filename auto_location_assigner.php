<?php
/**
 * Auto Location Assigner
 * Automatically assigns coordinates to clients based on their barangay
 */

// Bago City barangay coordinates (approximate center points)
$barangay_coordinates = [
    'Abuanan' => ['lat' => 10.5254, 'lng' => 122.9915],
    'Alianza' => ['lat' => 10.575, 'lng' => 122.88],
    'Atipuluan' => ['lat' => 10.52, 'lng' => 122.87],
    'Bacong-Montilla' => ['lat' => 10.54, 'lng' => 122.89],
    'Bagroy' => ['lat' => 10.56, 'lng' => 122.84],
    'Balingasag' => ['lat' => 10.53, 'lng' => 122.86],
    'Binubuhan' => ['lat' => 10.51, 'lng' => 122.85],
    'Busay' => ['lat' => 10.58, 'lng' => 122.83],
    'Calumangan' => ['lat' => 10.55, 'lng' => 122.87],
    'Caridad' => ['lat' => 10.57, 'lng' => 122.85],
    'Dulao' => ['lat' => 10.54, 'lng' => 122.88],
    'Ilijan' => ['lat' => 10.52, 'lng' => 122.89],
    'Lag-Asan' => ['lat' => 10.535, 'lng' => 122.84],
    'Ma-ao' => ['lat' => 10.53, 'lng' => 122.87],
    'Mailum' => ['lat' => 10.56, 'lng' => 122.88],
    'Malingin' => ['lat' => 10.54, 'lng' => 122.86],
    'Napoles' => ['lat' => 10.55, 'lng' => 122.85],
    'Pacol' => ['lat' => 10.57, 'lng' => 122.86],
    'Poblacion' => ['lat' => 10.5378, 'lng' => 122.8369],
    'Sagasa' => ['lat' => 10.52, 'lng' => 122.84],
    'Sampinit' => ['lat' => 10.58, 'lng' => 122.87],
    'Tampalon' => ['lat' => 10.56, 'lng' => 122.89],
    'Tabunan' => ['lat' => 10.53, 'lng' => 122.83],
    'Taloc' => ['lat' => 10.55, 'lng' => 122.84]
];

/**
 * Automatically assign coordinates to a client based on their barangay
 * @param mysqli $conn Database connection
 * @param int $client_id Client ID to assign location to
 * @param string $barangay Barangay name (optional, will fetch from database if not provided)
 * @return bool True if successful, false otherwise
 */
function autoAssignClientLocation($conn, $client_id, $barangay = null) {
    global $barangay_coordinates;
    
    // If barangay not provided, fetch it from database
    if ($barangay === null) {
        $stmt = $conn->prepare("SELECT barangay FROM clients WHERE client_id = ?");
        $stmt->bind_param("i", $client_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        if (!$row) {
            return false; // Client not found
        }
        $barangay = $row['barangay'];
    }
    
    // Check if we have coordinates for this barangay
    if (isset($barangay_coordinates[$barangay])) {
        $lat = $barangay_coordinates[$barangay]['lat'];
        $lng = $barangay_coordinates[$barangay]['lng'];
        
        // Add small random offset to prevent exact overlap for multiple clients in same barangay
        $lat += (mt_rand(-50, 50) / 100000); // Random offset of ±0.0005 degrees
        $lng += (mt_rand(-50, 50) / 100000); // Random offset of ±0.0005 degrees
        
        // Update client coordinates
        $stmt = $conn->prepare("UPDATE clients SET latitude = ?, longitude = ? WHERE client_id = ?");
        $stmt->bind_param("ddi", $lat, $lng, $client_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } else {
        // If barangay not found in coordinates, set to NULL
        $stmt = $conn->prepare("UPDATE clients SET latitude = NULL, longitude = NULL WHERE client_id = ?");
        $stmt->bind_param("i", $client_id);
        $result = $stmt->execute();
        $stmt->close();
        
        return false;
    }
}

/**
 * Auto-assign locations to all clients with disseminated animals who don't have coordinates
 * @param mysqli $conn Database connection
 * @return array Results of the assignment process
 */
function autoAssignAllClientLocations($conn) {
    // Get all clients with disseminated animals who don't have coordinates
    $query = "SELECT DISTINCT c.client_id, c.full_name, c.barangay 
              FROM clients c 
              INNER JOIN livestock_poultry lp ON c.client_id = lp.client_id 
              WHERE UPPER(lp.source) = 'DISSEMINATED' 
                AND (c.latitude IS NULL OR c.longitude IS NULL)";
    
    $result = $conn->query($query);
    $results = [
        'success' => 0,
        'failed' => 0,
        'details' => []
    ];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $success = autoAssignClientLocation($conn, $row['client_id'], $row['barangay']);
            
            if ($success) {
                $results['success']++;
                $results['details'][] = [
                    'client_id' => $row['client_id'],
                    'name' => $row['full_name'],
                    'barangay' => $row['barangay'],
                    'status' => 'success'
                ];
            } else {
                $results['failed']++;
                $results['details'][] = [
                    'client_id' => $row['client_id'],
                    'name' => $row['full_name'],
                    'barangay' => $row['barangay'],
                    'status' => 'failed'
                ];
            }
        }
    }
    
    return $results;
}
?>