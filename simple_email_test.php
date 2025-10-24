<?php
echo "<h1>Simple Email Test</h1>";

if ($_POST) {
    echo "<h3>Form Data Received:</h3>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    if (isset($_POST['email_address'])) {
        $email = trim($_POST['email_address']);
        echo "<p><strong>Email entered:</strong> " . htmlspecialchars($email) . "</p>";
        
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "<p style='color: green;'>✅ Email is valid!</p>";
        } else {
            echo "<p style='color: red;'>❌ Email is invalid!</p>";
        }
    }
}
?>

<form method="POST">
    <p>Enter email: <input type="email" name="email_address" placeholder="test@example.com" required></p>
    <p><input type="submit" name="test_submit" value="Test Email"></p>
</form>
