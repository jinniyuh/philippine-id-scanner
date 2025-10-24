<?php
require 'includes/conn.php';

$type = $_GET['type'] ?? '';
$allowedTypes = ['livestock', 'poultry', 'pharmaceuticals'];

if (!in_array($type, $allowedTypes)) {
    echo "<p>Invalid type.</p>";
    exit;
}

if ($type === 'livestock' || $type === 'poultry') {
    // Query livestock_poultry filtered by animal_type
    $stmt = $conn->prepare("SELECT species, SUM(quantity) AS total_quantity FROM livestock_poultry WHERE animal_type = ? GROUP BY species");
    $stmt->bind_param("s", $type);
    $stmt->execute();
    $result = $stmt->get_result();
} elseif ($type === 'pharmaceuticals') {
    // Query pharmaceuticals table using stock instead of quantity
    $result = $conn->query("SELECT name, stock FROM pharmaceuticals");
} else {
    echo "<p>Invalid type.</p>";
    exit;
}

if ($result && $result->num_rows > 0) {
    echo '<table class="table table-bordered table-striped">';
    echo '<thead><tr><th>' . ($type === 'pharmaceuticals' ? 'Name' : 'Species') . '</th><th>Quantity</th></tr></thead><tbody>';

while ($row = $result->fetch_assoc()) {
    if ($type === 'pharmaceuticals') {
        $displayName = $row['name'];
        $quantity = $row['stock'];
    } else {
        $displayName = $row['species'];
        $quantity = $row['total_quantity'];
    }

    echo "<tr>
            <td>" . htmlspecialchars($displayName) . "</td>
            <td>" . htmlspecialchars($quantity) . "</td>
          </tr>";
}
echo '</tbody></table>';
}
?>