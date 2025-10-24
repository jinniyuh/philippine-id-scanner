<?php
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <title>Compare Forgot Password Files</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; white-space: pre-wrap; }
        .success { color: green; background: #d4edda; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>";

echo "<h1>🔍 Compare Forgot Password Files</h1>";

echo "<div class='section'>";
echo "<h3>📋 File Comparison</h3>";

// Check if files exist
$files = [
    'forgot_password.php' => 'Main forgot password file',
    'test_user_forgot_password.php' => 'Working test file',
    'detailed_user_debug.php' => 'Debug file'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='success'>✅ $file exists - $description</div>";
        echo "<div class='code'>Size: " . filesize($file) . " bytes | Modified: " . date('Y-m-d H:i:s', filemtime($file)) . "</div>";
    } else {
        echo "<div class='error'>❌ $file MISSING - $description</div>";
    }
}

echo "</div>";

echo "<div class='section'>";
echo "<h3>🔧 Key Differences to Check</h3>";

echo "<p><strong>Common issues when columns exist but functionality doesn't work:</strong></p>";
echo "<ol>";
echo "<li><strong>Email field name mismatch:</strong> Using 'full_name' instead of 'name' for users</li>";
echo "<li><strong>Form field names:</strong> Input name vs POST parameter mismatch</li>";
echo "<li><strong>Database connection:</strong> Different database on live vs local</li>";
echo "<li><strong>File paths:</strong> Includes not found on live server</li>";
echo "<li><strong>SMTP settings:</strong> Different SMTP configuration on live</li>";
echo "<li><strong>PHP version:</strong> Different PHP version causing compatibility issues</li>";
echo "<li><strong>Error reporting:</strong> Errors hidden on live server</li>";
echo "</ol>";

echo "</div>";

echo "<div class='section'>";
echo "<h3>🧪 Quick Tests</h3>";

echo "<p><strong>Test 1: Check if the issue is in the main file</strong></p>";
echo "<p>Try the detailed debug script: <a href='detailed_user_debug.php'>detailed_user_debug.php</a></p>";

echo "<p><strong>Test 2: Check if the issue is SMTP</strong></p>";
echo "<p>Try the email debug script: <a href='test_email_debug.php'>test_email_debug.php</a></p>";

echo "<p><strong>Test 3: Check if the issue is user lookup</strong></p>";
echo "<p>Try the user debug script: <a href='test_user_forgot_password.php'>test_user_forgot_password.php</a></p>";

echo "</div>";

echo "<div class='section'>";
echo "<h3>🔍 Most Likely Issues</h3>";

echo "<p><strong>1. Error Reporting Disabled on Live Server</strong></p>";
echo "<p>Add this to the top of forgot_password.php to see errors:</p>";
echo "<div class='code'>error_reporting(E_ALL);
ini_set('display_errors', 1);</div>";

echo "<p><strong>2. Different File Paths</strong></p>";
echo "<p>Check if the includes are found:</p>";
echo "<div class='code'>if (!file_exists('includes/conn.php')) {
    die('conn.php not found');
}</div>";

echo "<p><strong>3. Database Connection Issues</strong></p>";
echo "<p>Check if the database connection is working:</p>";
echo "<div class='code'>if (\$conn->connect_error) {
    die('Database connection failed: ' . \$conn->connect_error);
}</div>";

echo "</div>";

echo "</body></html>";
?>
