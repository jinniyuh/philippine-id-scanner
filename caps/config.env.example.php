<?php
/**
 * Environment Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Copy this file to: config.env.php
 * 2. Update the values with your actual credentials
 * 3. NEVER commit config.env.php to Git!
 * 4. Keep config.env.php secure (chmod 600)
 */

return [
    // Database Configuration
    'db_host' => 'localhost',
    'db_user' => 'your_database_username',
    'db_pass' => 'your_database_password',
    'db_name' => 'your_database_name',
    
    // Security Settings
    'session_lifetime' => 3600, // 1 hour
    'max_login_attempts' => 5,
    'lockout_duration' => 900, // 15 minutes
    
    // Application Settings
    'app_name' => 'Bago City Veterinary Office',
    'timezone' => 'Asia/Manila',
    'max_upload_size' => 5242880, // 5MB
    
    // API Settings (optional)
    'flask_api_url' => 'http://localhost:5000',
    'api_timeout' => 30
];
?>

