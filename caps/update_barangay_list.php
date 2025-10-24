<?php
/**
 * Update Barangay List Script
 * Updates the database with the correct 24 barangays of Bago City
 */

include 'includes/conn.php';
include 'includes/bago_config.php';

echo "<h2>🔄 Updating Bago City Barangay List</h2>\n";

// The correct 24 barangays as provided by the user
$correctBarangays = [
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

echo "<h3>📋 Correct Barangay List:</h3>\n";
echo "<ul>\n";
foreach ($correctBarangays as $index => $barangay) {
    echo "<li>" . ($index + 1) . ". " . htmlspecialchars($barangay) . "</li>\n";
}
echo "</ul>\n";

echo "<h3>🔄 Updating Database...</h3>\n";

try {
    // Initialize tables if they don't exist
    initializeBagoCityTables();
    
    // Update the barangay list
    if (updateBagoBarangays($correctBarangays)) {
        echo "<div style='color: green; font-weight: bold;'>✅ Barangay list updated successfully!</div>\n";
    } else {
        echo "<div style='color: red; font-weight: bold;'>❌ Failed to update barangay list.</div>\n";
    }
    
    // Verify the update
    echo "<h3>✅ Verification:</h3>\n";
    $updatedBarangays = getBagoBarangaysFromDB();
    
    echo "<p><strong>Total barangays in database:</strong> " . count($updatedBarangays) . "</p>\n";
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
    echo "<tr><th>#</th><th>Barangay Name</th><th>Status</th></tr>\n";
    
    foreach ($correctBarangays as $index => $barangay) {
        $inDatabase = in_array($barangay, $updatedBarangays);
        $status = $inDatabase ? "✅ In Database" : "❌ Missing";
        $color = $inDatabase ? "green" : "red";
        
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td>" . htmlspecialchars($barangay) . "</td>";
        echo "<td style='color: $color; font-weight: bold;'>$status</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Show any extra barangays in database
    $extraBarangays = array_diff($updatedBarangays, $correctBarangays);
    if (!empty($extraBarangays)) {
        echo "<h4>⚠️ Extra barangays in database (not in correct list):</h4>\n";
        echo "<ul>\n";
        foreach ($extraBarangays as $extra) {
            echo "<li>" . htmlspecialchars($extra) . "</li>\n";
        }
        echo "</ul>\n";
    }
    
    // Show any missing barangays
    $missingBarangays = array_diff($correctBarangays, $updatedBarangays);
    if (!empty($missingBarangays)) {
        echo "<h4>❌ Missing barangays in database:</h4>\n";
        echo "<ul>\n";
        foreach ($missingBarangays as $missing) {
            echo "<li>" . htmlspecialchars($missing) . "</li>\n";
        }
        echo "</ul>\n";
    }
    
    if (empty($extraBarangays) && empty($missingBarangays)) {
        echo "<div style='color: green; font-weight: bold; font-size: 18px;'>🎉 Perfect! Database matches the correct barangay list exactly!</div>\n";
    }
    
} catch (Exception $e) {
    echo "<div style='color: red; font-weight: bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>\n";
}

echo "<h3>🧪 Testing Validation:</h3>\n";

// Test with a few barangays
$testBarangays = ['Poblacion', 'Alianza', 'Napoles'];
$testAddresses = [];

foreach ($testBarangays as $barangay) {
    $testAddresses[] = "Brgy. $barangay, Bago City, Negros Occidental";
}

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>\n";
echo "<tr><th>Test Address</th><th>Expected</th><th>Result</th><th>Message</th></tr>\n";

foreach ($testAddresses as $address) {
    list($isValid, $message) = validateBagoCityResidency($address);
    
    $result = $isValid ? "✅ PASS" : "❌ FAIL";
    $color = $isValid ? "green" : "red";
    
    echo "<tr>";
    echo "<td>" . htmlspecialchars($address) . "</td>";
    echo "<td>Should PASS</td>";
    echo "<td style='color: $color; font-weight: bold;'>$result</td>";
    echo "<td>" . htmlspecialchars($message) . "</td>";
    echo "</tr>\n";
}

echo "</table>\n";

echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px; margin-top: 20px;'>\n";
echo "<h4>✅ Update Complete!</h4>\n";
echo "<p>The barangay list has been updated with the correct 24 barangays of Bago City.</p>\n";
echo "<p><strong>Next steps:</strong></p>\n";
echo "<ul>\n";
echo "<li>✅ The system will now validate IDs using the correct barangay list</li>\n";
echo "<li>✅ Admin can manage barangays through the admin interface</li>\n";
echo "<li>✅ Validation will work with the updated list</li>\n";
echo "</ul>\n";
echo "</div>\n";
?>
