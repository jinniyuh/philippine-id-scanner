<?php
/**
 * Delete Single File Handler
 */

session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("ERROR: Admin access required!");
}

$message = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filename'])) {
    $filename = basename($_POST['filename']); // Security: only basename
    
    // Blacklist - never allow deletion of these
    $blacklist = [
        'index.php', 'login.php', 'logout.php', 'conn.php',
        'admin_sidebar.php', 'staff_sidebar.php', 'client_sidebar.php',
        'delete_single_file.php', 'verify_file_usage.php', 'cleanup_unused_files.php'
    ];
    
    if (in_array($filename, $blacklist)) {
        $message = "ERROR: Cannot delete core file '$filename'";
    } elseif (file_exists($filename)) {
        if (unlink($filename)) {
            $success = true;
            $message = "✅ Successfully deleted: $filename";
        } else {
            $message = "ERROR: Failed to delete '$filename' - check permissions";
        }
    } else {
        $message = "ERROR: File '$filename' not found";
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Deletion Result</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="alert alert-<?php echo $success ? 'success' : 'danger'; ?>">
            <h4><?php echo htmlspecialchars($message); ?></h4>
        </div>
        <a href="verify_file_usage.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Verification
        </a>
    </div>
</body>
</html>

