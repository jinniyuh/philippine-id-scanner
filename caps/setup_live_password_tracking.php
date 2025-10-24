<?php
include 'includes/conn.php';

try {
    // Check if column already exists
    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'password_changed_at'");
    
    if ($checkColumn->num_rows == 0) {
        // Add password_changed_at column to users table
        $sql1 = "ALTER TABLE users ADD COLUMN password_changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP";
        $conn->query($sql1);
        echo "✓ Added password_changed_at column to users table\n";
        
        // Set initial value for existing users to their created_at date
        $sql2 = "UPDATE users SET password_changed_at = created_at WHERE password_changed_at IS NULL";
        $conn->query($sql2);
        echo "✓ Updated existing users with initial password change date\n";
    } else {
        echo "✓ password_changed_at column already exists\n";
    }
    
    echo "\n✅ Password tracking setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

$conn->close();
?>
