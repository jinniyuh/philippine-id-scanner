<?php
/**
 * Export Verification Results
 */

session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("ERROR: Admin access required!");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $safe_delete = json_decode($_POST['safe_delete'] ?? '[]', true);
    $review_needed = json_decode($_POST['review_needed'] ?? '[]', true);
    $keep_files = json_decode($_POST['keep_files'] ?? '[]', true);
    
    $output = "FILE VERIFICATION RESULTS\n";
    $output .= "Generated: " . date('Y-m-d H:i:s') . "\n";
    $output .= str_repeat("=", 80) . "\n\n";
    
    $output .= "SUMMARY\n";
    $output .= str_repeat("-", 80) . "\n";
    $output .= "Safe to Delete: " . count($safe_delete) . " files\n";
    $output .= "Review Needed: " . count($review_needed) . " files\n";
    $output .= "Keep (Active): " . count($keep_files) . " files\n";
    $output .= "Total Scanned: " . (count($safe_delete) + count($review_needed) + count($keep_files)) . " files\n\n";
    
    $output .= str_repeat("=", 80) . "\n\n";
    
    $output .= "SAFE TO DELETE (" . count($safe_delete) . " files)\n";
    $output .= str_repeat("-", 80) . "\n";
    $output .= "These files are NOT referenced anywhere in your codebase:\n\n";
    foreach ($safe_delete as $file) {
        $output .= "  ❌ " . $file . "\n";
    }
    $output .= "\n" . str_repeat("=", 80) . "\n\n";
    
    $output .= "REVIEW NEEDED (" . count($review_needed) . " files)\n";
    $output .= str_repeat("-", 80) . "\n";
    $output .= "These files have low usage - verify manually:\n\n";
    foreach ($review_needed as $file) {
        $output .= "  ⚠️  " . $file . "\n";
    }
    $output .= "\n" . str_repeat("=", 80) . "\n\n";
    
    $output .= "KEEP THESE (" . count($keep_files) . " files)\n";
    $output .= str_repeat("-", 80) . "\n";
    $output .= "These files are actively used:\n\n";
    foreach ($keep_files as $file) {
        $output .= "  ✅ " . $file . "\n";
    }
    
    // Send as download
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="file_verification_results_' . date('Y-m-d_His') . '.txt"');
    header('Content-Length: ' . strlen($output));
    echo $output;
    exit;
}
?>

