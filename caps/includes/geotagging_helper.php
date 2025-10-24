<?php
/**
 * Geotagging Helper Functions
 * Functions to check if clients are eligible for geotagging based on disseminated animals
 */

/**
 * Check if a client has disseminated livestock or poultry
 * @param mysqli $conn Database connection
 * @param int $client_id Client ID to check
 * @return bool True if client has disseminated animals, false otherwise
 */
function hasDisseminatedAnimals($conn, $client_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM livestock_poultry WHERE client_id = ? AND UPPER(source) = 'DISSEMINATED'");
    if (!$stmt) {
        return false;
    }
    
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    return ($row['count'] > 0);
}

/**
 * Get all clients who have disseminated animals with their location data
 * @param mysqli $conn Database connection
 * @return array Array of clients with disseminated animals and location data
 */
function getClientsWithDisseminatedAnimalsAndLocation($conn) {
    $query = "SELECT DISTINCT c.client_id, c.full_name, c.contact_number, c.barangay, c.purok, c.latitude, c.longitude, c.status, c.created_at 
              FROM clients c 
              INNER JOIN livestock_poultry lp ON c.client_id = lp.client_id 
              WHERE UPPER(lp.source) = 'DISSEMINATED' 
                AND c.latitude IS NOT NULL 
                AND c.longitude IS NOT NULL 
              ORDER BY c.full_name";
    
    $result = $conn->query($query);
    $clients = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Create formatted address
            $address_parts = array_filter([$row['purok'] ?? '', $row['barangay'] ?? '']);
            $row['address'] = implode(', ', $address_parts);
            $clients[] = $row;
        }
    }
    
    return $clients;
}

/**
 * Get statistics about clients with disseminated animals and location data
 * @param mysqli $conn Database connection
 * @return array Array with statistics
 */
function getDisseminatedAnimalsLocationStats($conn) {
    // Total clients with disseminated animals
    $total_with_disseminated = $conn->query("SELECT COUNT(DISTINCT c.client_id) as count 
                                            FROM clients c 
                                            INNER JOIN livestock_poultry lp ON c.client_id = lp.client_id 
                                            WHERE UPPER(lp.source) = 'DISSEMINATED'")->fetch_assoc()['count'];
    
    // Clients with disseminated animals and location data
    $with_location = $conn->query("SELECT COUNT(DISTINCT c.client_id) as count 
                                  FROM clients c 
                                  INNER JOIN livestock_poultry lp ON c.client_id = lp.client_id 
                                  WHERE UPPER(lp.source) = 'DISSEMINATED' 
                                    AND c.latitude IS NOT NULL 
                                    AND c.longitude IS NOT NULL")->fetch_assoc()['count'];
    
    // Clients with disseminated animals but no location data
    $without_location = $total_with_disseminated - $with_location;
    
    return [
        'total_with_disseminated' => $total_with_disseminated,
        'with_location' => $with_location,
        'without_location' => $without_location,
        'percentage' => $total_with_disseminated > 0 ? round(($with_location / $total_with_disseminated) * 100) : 0
    ];
}

/**
 * Check if geotagging is allowed for a client (has disseminated animals)
 * @param mysqli $conn Database connection
 * @param int $client_id Client ID to check
 * @return bool True if geotagging is allowed, false otherwise
 */
function isGeotaggingAllowed($conn, $client_id) {
    return hasDisseminatedAnimals($conn, $client_id);
}
?>
