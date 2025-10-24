<?php
session_start();
include 'includes/conn.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $client_id = $_POST['client_id'] ?? '';
    $pharma_id = $_POST['pharma_id'] ?? '';
    $quantity = $_POST['quantity'] ?? '';
    $status = 'Approved'; // Default status for manually added transactions
    $request_date = date('Y-m-d');
    $issued_date = date('Y-m-d');
    
    // Validate inputs
    if (empty($client_id) || empty($pharma_id) || empty($quantity)) {
        $_SESSION['error'] = "Client, medicine and quantity are required fields.";
        header("Location: admin_transactions.php");
        exit();
    }
    
    // Validate that client exists
    $stmt = $conn->prepare("SELECT client_id FROM clients WHERE client_id = ?");
    $stmt->bind_param("i", $client_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $_SESSION['error'] = "Selected client does not exist.";
        header("Location: admin_transactions.php");
        exit();
    }
    
    // Insert transaction
    $stmt = $conn->prepare("INSERT INTO transactions (client_id, pharma_id, quantity, status, request_date, issued_date) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $client_id, $pharma_id, $quantity, $status, $request_date, $issued_date);
    
    if ($stmt->execute()) {
        // Update pharmaceutical stock if status is Approved or Issued
        if ($status === 'Approved' || $status === 'Issued') {
            $update_stock = $conn->prepare("UPDATE pharmaceuticals SET stock = stock - ? WHERE pharma_id = ?");
            $update_stock->bind_param("ii", $quantity, $pharma_id);
            $update_stock->execute();
        }
        
        // Create notification for the client
        $message = "Your transaction for medicine has been " . strtolower($status) . ".";
        $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, status) VALUES (?, ?, 'unread')");
        $notif_stmt->bind_param("is", $client_id, $message);
        $notif_stmt->execute();
        
        $_SESSION['success'] = "Transaction added successfully.";
    } else {
        $_SESSION['error'] = "Error adding transaction: " . $conn->error;
    }
    
    // Redirect back to transactions page
    header("Location: admin_transactions.php");
    exit();
} else {
    // If not a POST request, redirect to transactions page
    header("Location: admin_transactions.php");
    exit();
}
?>
