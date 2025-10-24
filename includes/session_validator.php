<?php
/**
 * Session Validation Functions
 * Ensures that logged-in users are still active and authorized
 */

function validateUserSession($conn) {
    // Simple session validation - just check if session variables exist
    if ($_SESSION['role'] === 'client') {
        return isset($_SESSION['client_id']) && isset($_SESSION['role']) && isset($_SESSION['name']);
    } else {
        return isset($_SESSION['user_id']) && isset($_SESSION['role']) && isset($_SESSION['name']);
    }
}

function requireActiveSession($conn, $requiredRole = null) {
    if (!validateUserSession($conn)) {
        header("Location: login.php?error=session_expired");
        exit();
    }
    
    if ($requiredRole && $_SESSION['role'] !== $requiredRole) {
        header("Location: login.php?error=unauthorized");
        exit();
    }
}

function isUserActive($conn, $user_id, $role) {
    if ($role === 'admin' || $role === 'staff') {
        $stmt = $conn->prepare("SELECT status FROM users WHERE user_id = ? AND role = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $role);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            return $user && $user['status'] === 'Active';
        }
    } elseif ($role === 'client') {
        $stmt = $conn->prepare("SELECT status FROM clients WHERE client_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $client = $result->fetch_assoc();
            return $client && $client['status'] === 'Complied';
        }
    }
    
    return false;
}
?>
