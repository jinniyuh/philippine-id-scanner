<?php
session_start();
include 'includes/conn.php';

// Get the selected barangay from POST data
$selectedBarangay = isset($_POST['barangay']) ? $_POST['barangay'] : '';

// Build WHERE clause for barangay filtering
$barangayWhere = '';
if (!empty($selectedBarangay)) {
    $barangayWhere = " AND c.barangay = '" . mysqli_real_escape_string($conn, $selectedBarangay) . "'";
}

// Count total livestock with barangay filter
$queryTotalLivestock = "SELECT SUM(lp.quantity) as total FROM livestock_poultry lp 
                       LEFT JOIN clients c ON lp.client_id = c.client_id 
                       WHERE lp.animal_type = 'Livestock'" . $barangayWhere;
$resultTotalLivestock = mysqli_query($conn, $queryTotalLivestock);
$totalLivestock = 0;
if ($resultTotalLivestock && mysqli_num_rows($resultTotalLivestock) > 0) {
    $row = mysqli_fetch_assoc($resultTotalLivestock);
    $totalLivestock = $row['total'] ?? 0;
}

// Count total poultry with barangay filter
$queryTotalPoultry = "SELECT SUM(lp.quantity) as total FROM livestock_poultry lp 
                     LEFT JOIN clients c ON lp.client_id = c.client_id 
                     WHERE lp.animal_type = 'Poultry'" . $barangayWhere;
$resultTotalPoultry = mysqli_query($conn, $queryTotalPoultry);
$totalPoultry = 0;
if ($resultTotalPoultry && mysqli_num_rows($resultTotalPoultry) > 0) {
    $row = mysqli_fetch_assoc($resultTotalPoultry);
    $totalPoultry = $row['total'] ?? 0;
}

// Get livestock data for disseminated/owned calculation
$queryLivestock = "SELECT lp.*, c.barangay FROM livestock_poultry lp 
                  LEFT JOIN clients c ON lp.client_id = c.client_id 
                  WHERE lp.animal_type = 'Livestock'" . $barangayWhere;
$resultLivestock = mysqli_query($conn, $queryLivestock);
$livestockDisseminated = 0;
$livestockOwned = 0;

if ($resultLivestock && mysqli_num_rows($resultLivestock) > 0) {
    while ($row = mysqli_fetch_assoc($resultLivestock)) {
        if (strtolower($row['source']) === 'disseminated') {
            $livestockDisseminated += $row['quantity'];
        }
        if (strtolower($row['source']) === 'owned') {
            $livestockOwned += $row['quantity'];
        }
    }
}

// Get poultry data for disseminated/owned calculation
$queryPoultry = "SELECT lp.*, c.barangay FROM livestock_poultry lp 
                LEFT JOIN clients c ON lp.client_id = c.client_id 
                WHERE lp.animal_type = 'Poultry'" . $barangayWhere;
$resultPoultry = mysqli_query($conn, $queryPoultry);
$poultryDisseminated = 0;
$poultryOwned = 0;

if ($resultPoultry && mysqli_num_rows($resultPoultry) > 0) {
    while ($row = mysqli_fetch_assoc($resultPoultry)) {
        if (strtolower($row['source']) === 'disseminated') {
            $poultryDisseminated += $row['quantity'];
        }
        if (strtolower($row['source']) === 'owned') {
            $poultryOwned += $row['quantity'];
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'totalLivestock' => $totalLivestock,
    'totalPoultry' => $totalPoultry,
    'livestockDisseminated' => $livestockDisseminated,
    'livestockOwned' => $livestockOwned,
    'poultryDisseminated' => $poultryDisseminated,
    'poultryOwned' => $poultryOwned
]);
?>
