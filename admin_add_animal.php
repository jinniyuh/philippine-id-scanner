<?php
session_start();
include 'includes/conn.php';
// Check if user is logged in as admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $animal_type = $_POST['animal_type'];
    $species = $_POST['species'];
    $quantity = (int)$_POST['quantity'];
    $client_id = (int)$_POST['client_id'];
    $source = $_POST['source'];
    $health_status = $_POST['health_status'];
    $weight = ($animal_type === 'Livestock') ? (float)$_POST['weight'] : 0;

    $sql = "INSERT INTO livestock_poultry (animal_type, species, weight, quantity, client_id, source, health_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssdiiss", $animal_type, $species, $weight, $quantity, $client_id, $source, $health_status);
 
    if ($stmt->execute()) {
        $_SESSION['success'] = "New $animal_type added successfully";

        // If source is Disseminated, also insert into compliance table
        if (strcasecmp($source, 'Disseminated') === 0) {
            try {
                $compSql = "INSERT INTO compliance (client_id, animal_type, species, compliance_status, transfer_date) 
                            VALUES (?, ?, ?, 'Pending', CURDATE())";
                $compStmt = $conn->prepare($compSql);
                if ($compStmt) {
                    $compStmt->bind_param("iss", $client_id, $animal_type, $species);
                    $compStmt->execute();
                    $compStmt->close();
                }
            } catch (Exception $e) {
                error_log('Compliance insert error (admin_add_animal): ' . $e->getMessage());
            }
        }
    }

    header("Location: admin_livestock_poultry.php");
    exit();
} else {
    header("Location: admin_livestock_poultry.php");
    exit();
}
?>
