<?php
include 'includes/conn.php';

echo "<h2>Transaction Debug</h2>";

// Check all transactions
echo "<h3>All Transactions:</h3>";
$all_query = "SELECT transaction_id, client_id, pharma_id, quantity, status, issued_date, request_date FROM transactions ORDER BY transaction_id DESC";
$result = $conn->query($all_query);

if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Client ID</th><th>Pharma ID</th><th>Quantity</th><th>Status</th><th>Issued Date</th><th>Request Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['transaction_id'] . "</td>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>" . $row['pharma_id'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['issued_date'] ? $row['issued_date'] : 'NULL') . "</td>";
        echo "<td>" . ($row['request_date'] ? $row['request_date'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No transactions found.";
}

// Check March 2024 transactions specifically
echo "<h3>March 2024 Transactions:</h3>";
$march_query = "SELECT transaction_id, client_id, pharma_id, quantity, status, issued_date, request_date FROM transactions WHERE MONTH(issued_date) = '03' AND YEAR(issued_date) = '2024' ORDER BY transaction_id DESC";
$march_result = $conn->query($march_query);

if ($march_result && $march_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Client ID</th><th>Pharma ID</th><th>Quantity</th><th>Status</th><th>Issued Date</th><th>Request Date</th></tr>";
    while ($row = $march_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['transaction_id'] . "</td>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>" . $row['pharma_id'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['issued_date'] ? $row['issued_date'] : 'NULL') . "</td>";
        echo "<td>" . ($row['request_date'] ? $row['request_date'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No March 2024 transactions found.";
}

// Check current year transactions
echo "<h3>Current Year Transactions:</h3>";
$current_year = date('Y');
$year_query = "SELECT transaction_id, client_id, pharma_id, quantity, status, issued_date, request_date FROM transactions WHERE YEAR(issued_date) = '$current_year' ORDER BY transaction_id DESC";
$year_result = $conn->query($year_query);

if ($year_result && $year_result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Client ID</th><th>Pharma ID</th><th>Quantity</th><th>Status</th><th>Issued Date</th><th>Request Date</th></tr>";
    while ($row = $year_result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['transaction_id'] . "</td>";
        echo "<td>" . $row['client_id'] . "</td>";
        echo "<td>" . $row['pharma_id'] . "</td>";
        echo "<td>" . $row['quantity'] . "</td>";
        echo "<td>" . $row['status'] . "</td>";
        echo "<td>" . ($row['issued_date'] ? $row['issued_date'] : 'NULL') . "</td>";
        echo "<td>" . ($row['request_date'] ? $row['request_date'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "No current year transactions found.";
}

// Test the exact query used in admin_transactions.php
echo "<h3>Test Query (March filter):</h3>";
$test_query = "SELECT COUNT(*) as cnt FROM transactions WHERE issued_date IS NOT NULL AND MONTH(issued_date) = '03' AND YEAR(issued_date) = YEAR(CURDATE())";
$test_result = $conn->query($test_query);
if ($test_result && $row = $test_result->fetch_assoc()) {
    echo "March transactions count: " . $row['cnt'];
} else {
    echo "Query failed: " . $conn->error;
}
?>
