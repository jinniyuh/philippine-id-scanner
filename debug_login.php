<?php
session_start();
require_once 'includes/conn.php';
include 'includes/activity_logger.php';

function sanitize($data) {
    return htmlspecialchars(trim($data));
}

$error = '';
$success = '';
$debug_info = [];

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Debug Login</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .debug { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 5px; margin: 10px 0; font-family: monospace; white-space: pre-wrap; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        input[type='text'], input[type='password'] { padding: 8px; width: 200px; margin: 5px; }
        input[type='submit'] { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>";

echo "<h1>🔍 Debug Login Functionality</h1>";

// Test login if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    $username = strtolower(sanitize($_POST['username'] ?? ''));
    $password = $_POST['password'] ?? '';
    $loginSuccess = false;
    
    $debug_info[] = "=== TESTING LOGIN ===";
    $debug_info[] = "Username: $username";
    $debug_info[] = "Password: " . (empty($password) ? "EMPTY" : "PROVIDED");
    
    if (!$username || !$password) {
        $error = "Please enter username and password.";
        $debug_info[] = "❌ Missing username or password";
    } else {
        $debug_info[] = "✅ Both username and password provided";
        
        // Check clients table first
        $debug_info[] = "🔍 Checking clients table...";
        $clientStmt = $conn->prepare("SELECT * FROM clients WHERE username = ?");
        if ($clientStmt) {
            $clientStmt->bind_param("s", $username);
            $clientStmt->execute();
            $clientResult = $clientStmt->get_result();
            $client = $clientResult->fetch_assoc();
            $clientStmt->close();
            
            if ($client) {
                $debug_info[] = "✅ Client found: " . $client['full_name'] . " (ID: " . $client['client_id'] . ")";
                $debug_info[] = "🔐 Checking password...";
                
                if (password_verify($password, $client['password'])) {
                    $debug_info[] = "✅ Client password verified successfully!";
                    $_SESSION['client_id'] = $client['client_id'];
                    $_SESSION['role'] = 'client';
                    $_SESSION['name'] = $client['full_name'];
                    $loginSuccess = true;
                    $success = "✅ CLIENT LOGIN SUCCESSFUL!";
                } else {
                    $debug_info[] = "❌ Client password verification failed";
                }
            } else {
                $debug_info[] = "❌ No client found with username: $username";
            }
        } else {
            $debug_info[] = "❌ Failed to prepare client query: " . $conn->error;
        }
        
        // Check users table if client login failed
        if (!$loginSuccess) {
            $debug_info[] = "🔍 Checking users table...";
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                $stmt->close();
                
                if ($user) {
                    $debug_info[] = "✅ User found: " . $user['name'] . " (ID: " . $user['user_id'] . ", Role: " . $user['role'] . ")";
                    
                    // Check status if column exists
                    if (isset($user['status'])) {
                        $debug_info[] = "📋 User status: " . $user['status'];
                        if ($user['status'] !== 'Active') {
                            $debug_info[] = "❌ User account is disabled";
                            $error = "Your account is disabled. Please contact an administrator.";
                        }
                    } else {
                        $debug_info[] = "ℹ️ No status column found (assuming active)";
                    }
                    
                    if (!isset($user['status']) || $user['status'] === 'Active') {
                        $debug_info[] = "🔐 Checking password...";
                        
                        if (password_verify($password, $user['password'])) {
                            $debug_info[] = "✅ User password verified successfully!";
                            
                            // Test logActivity function
                            try {
                                logActivity($conn, $user['user_id'], 'Test login from debug script');
                                $debug_info[] = "✅ logActivity function works";
                            } catch (Exception $e) {
                                $debug_info[] = "❌ logActivity failed: " . $e->getMessage();
                            }
                            
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['role'] = $user['role'];
                            $_SESSION['name'] = $user['name'];
                            $loginSuccess = true;
                            $success = "✅ USER LOGIN SUCCESSFUL! Role: " . $user['role'];
                        } else {
                            $debug_info[] = "❌ User password verification failed";
                        }
                    }
                } else {
                    $debug_info[] = "❌ No user found with username: $username";
                }
            } else {
                $debug_info[] = "❌ Failed to prepare user query: " . $conn->error;
            }
        }
        
        if (!$loginSuccess) {
            $error = "❌ LOGIN FAILED: Invalid credentials";
            $debug_info[] = "❌ Final result: LOGIN FAILED";
        }
    }
}

// Show available users for testing
echo "<div class='section'>";
echo "<h3>👥 Available Users for Testing</h3>";

try {
    // Show clients
    $clients = $conn->query("SELECT client_id, full_name, username, email FROM clients LIMIT 5");
    if ($clients && $clients->num_rows > 0) {
        echo "<h4>Clients:</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Test</th></tr>";
        while ($client = $clients->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($client['client_id']) . "</td>";
            echo "<td>" . htmlspecialchars($client['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($client['username']) . "</td>";
            echo "<td>" . htmlspecialchars($client['email'] ?? 'No email') . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_username').value='" . htmlspecialchars($client['username']) . "'\">Test</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Show users
    $users = $conn->query("SELECT user_id, name, username, email, role FROM users LIMIT 5");
    if ($users && $users->num_rows > 0) {
        echo "<h4>Users (Admin/Staff):</h4>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Test</th></tr>";
        while ($user = $users->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email'] ?? 'No email') . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td><button onclick=\"document.getElementById('test_username').value='" . htmlspecialchars($user['username']) . "'\">Test</button></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Database error: " . $e->getMessage() . "</div>";
}

echo "</div>";

// Show debug information
if (!empty($debug_info)) {
    echo "<div class='section'>";
    echo "<h3>🔍 Debug Information</h3>";
    foreach ($debug_info as $info) {
        echo "<div class='debug'>" . htmlspecialchars($info) . "</div>";
    }
    echo "</div>";
}

// Show errors/success
if ($error) {
    echo "<div class='error'>" . htmlspecialchars($error) . "</div>";
}

if ($success) {
    echo "<div class='success'>" . htmlspecialchars($success) . "</div>";
}

// Test form
echo "<div class='section'>";
echo "<h3>🧪 Test Login</h3>";
echo "<form method='POST'>";
echo "<p>Username: <input type='text' name='username' id='test_username' required></p>";
echo "<p>Password: <input type='password' name='password' required></p>";
echo "<input type='submit' name='test_login' value='Test Login'>";
echo "</form>";
echo "</div>";

echo "<div class='section'>";
echo "<h3>⚠️ Important</h3>";
echo "<p><strong>Delete this file after debugging!</strong> This file shows sensitive information and should not be left on a live server.</p>";
echo "</div>";

echo "</body></html>";
?>
