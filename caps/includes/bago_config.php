<?php
/**
 * Bago City Configuration - Database Driven
 * Removes hardcoded values and makes system configurable
 */

/**
 * Get Bago City barangays from database
 * @return array List of barangays
 */
function getBagoBarangaysFromDB() {
    global $conn;
    
    $barangays = [];
    
    try {
        $query = "SELECT barangay_name FROM bago_barangays WHERE is_active = 1 ORDER BY barangay_name ASC";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $barangays[] = $row['barangay_name'];
            }
        } else {
            // Fallback to hardcoded list if table doesn't exist
            $barangays = getDefaultBagoBarangays();
        }
    } catch (Exception $e) {
        // Fallback to hardcoded list on error
        $barangays = getDefaultBagoBarangays();
    }
    
    return $barangays;
}

/**
 * Get default Bago City barangays (fallback)
 * @return array Default list of barangays
 */
function getDefaultBagoBarangays() {
    return [
        'Abuanan',
        'Alianza', 
        'Atipuluan',
        'Bacong-Montilla',
        'Bagroy',
        'Balingasag',
        'Binubuhan',
        'Busay',
        'Calumangan',
        'Caridad',
        'Don Jorge L. Araneta',
        'Dulao',
        'Ilijan',
        'Lag-Asan',
        'Ma-ao',
        'Mailum',
        'Malingin',
        'Napoles',
        'Pacol',
        'Poblacion',
        'Sagasa',
        'Sampinit',
        'Tabunan',
        'Taloc'
    ];
}

/**
 * Get Bago City configuration from database
 * @return array Configuration settings
 */
function getBagoCityConfig() {
    global $conn;
    
    $config = [
        'city_name' => 'Bago City',
        'province' => 'Negros Occidental',
        'country' => 'Philippines',
        'validation_enabled' => true,
        'strict_validation' => true,
        'error_messages' => [
            'not_bago_resident' => 'You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents are allowed to register.',
            'wrong_province' => 'You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents from Negros Occidental are allowed to register.',
            'invalid_barangay' => 'You are NOT a Bago City resident. You CANNOT register to our system. Your ID must show one of the barangays of Bago City.',
            'name_mismatch' => 'The full name you entered does not match the name on your ID. Please check and try again.',
            'success_verified' => 'ID Verified - Bago City resident from Barangay'
        ]
    ];
    
    try {
        $query = "SELECT config_key, config_value FROM bago_city_config WHERE is_active = 1";
        $result = $conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $key = $row['config_key'];
                $value = $row['config_value'];
                
                // Parse JSON values
                if (in_array($key, ['error_messages'])) {
                    $config[$key] = json_decode($value, true) ?: $config[$key];
                } else {
                    $config[$key] = $value;
                }
            }
        }
    } catch (Exception $e) {
        // Use default config on error
        error_log("Bago City Config Error: " . $e->getMessage());
    }
    
    return $config;
}

/**
 * Initialize Bago City database tables if they don't exist
 */
function initializeBagoCityTables() {
    global $conn;
    
    try {
        // Create bago_barangays table
        $createBarangaysTable = "
        CREATE TABLE IF NOT EXISTS bago_barangays (
            id INT AUTO_INCREMENT PRIMARY KEY,
            barangay_name VARCHAR(100) NOT NULL UNIQUE,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->query($createBarangaysTable);
        
        // Insert default barangays if table is empty
        $checkQuery = "SELECT COUNT(*) as count FROM bago_barangays";
        $result = $conn->query($checkQuery);
        $count = $result->fetch_assoc()['count'];
        
        if ($count == 0) {
            $defaultBarangays = getDefaultBagoBarangays();
            $stmt = $conn->prepare("INSERT INTO bago_barangays (barangay_name) VALUES (?)");
            
            foreach ($defaultBarangays as $barangay) {
                $stmt->bind_param("s", $barangay);
                $stmt->execute();
            }
        }
        
        // Create bago_city_config table
        $createConfigTable = "
        CREATE TABLE IF NOT EXISTS bago_city_config (
            id INT AUTO_INCREMENT PRIMARY KEY,
            config_key VARCHAR(100) NOT NULL UNIQUE,
            config_value TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $conn->query($createConfigTable);
        
        // Insert default config if table is empty
        $checkConfigQuery = "SELECT COUNT(*) as count FROM bago_city_config";
        $result = $conn->query($checkConfigQuery);
        $count = $result->fetch_assoc()['count'];
        
        if ($count == 0) {
            $defaultConfig = [
                'city_name' => 'Bago City',
                'province' => 'Negros Occidental',
                'country' => 'Philippines',
                'validation_enabled' => '1',
                'strict_validation' => '1',
                'error_messages' => json_encode([
                    'not_bago_resident' => 'You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents are allowed to register.',
                    'wrong_province' => 'You are NOT a Bago City resident. You CANNOT register to our system. Only Bago City residents from Negros Occidental are allowed to register.',
                    'invalid_barangay' => 'You are NOT a Bago City resident. You CANNOT register to our system. Your ID must show one of the barangays of Bago City.',
                    'name_mismatch' => 'The full name you entered does not match the name on your ID. Please check and try again.',
                    'success_verified' => 'ID Verified - Bago City resident from Barangay'
                ])
            ];
            
            $stmt = $conn->prepare("INSERT INTO bago_city_config (config_key, config_value) VALUES (?, ?)");
            
            foreach ($defaultConfig as $key => $value) {
                $stmt->bind_param("ss", $key, $value);
                $stmt->execute();
            }
        }
        
    } catch (Exception $e) {
        error_log("Bago City Tables Initialization Error: " . $e->getMessage());
    }
}

/**
 * Update barangay list in database
 * @param array $barangays New list of barangays
 * @return bool Success status
 */
function updateBagoBarangays($barangays) {
    global $conn;
    
    try {
        $conn->begin_transaction();
        
        // Deactivate all existing barangays
        $conn->query("UPDATE bago_barangays SET is_active = 0");
        
        // Insert/activate new barangays
        $stmt = $conn->prepare("
            INSERT INTO bago_barangays (barangay_name, is_active) 
            VALUES (?, 1) 
            ON DUPLICATE KEY UPDATE is_active = 1
        ");
        
        foreach ($barangays as $barangay) {
            $stmt->bind_param("s", $barangay);
            $stmt->execute();
        }
        
        $conn->commit();
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Update Barangays Error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update configuration in database
 * @param string $key Configuration key
 * @param mixed $value Configuration value
 * @return bool Success status
 */
function updateBagoConfig($key, $value) {
    global $conn;
    
    try {
        // Convert arrays to JSON
        if (is_array($value)) {
            $value = json_encode($value);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO bago_city_config (config_key, config_value) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE config_value = VALUES(config_value)
        ");
        
        $stmt->bind_param("ss", $key, $value);
        return $stmt->execute();
        
    } catch (Exception $e) {
        error_log("Update Config Error: " . $e->getMessage());
        return false;
    }
}
?>
