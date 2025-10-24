<?php
/**
 * File Usage Verification Tool
 * Scans all PHP files to find what's actually being used
 */

set_time_limit(300); // 5 minutes max

session_start();
include 'includes/conn.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("ERROR: Admin access required!");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>File Usage Verification</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { padding: 20px; background: #f8f9fa; }
        .file-card { background: white; padding: 15px; margin: 10px 0; border-radius: 8px; border-left: 4px solid #0066cc; }
        .safe-delete { border-left-color: #dc3545; background: #fff5f5; }
        .keep-file { border-left-color: #28a745; background: #f0fff4; }
        .review-file { border-left-color: #ffc107; background: #fffbf0; }
        .usage-count { font-weight: bold; font-size: 1.2em; }
        .used-by { font-size: 0.9em; color: #666; margin-left: 20px; }
        .section-header { background: #0066cc; color: white; padding: 10px; margin: 20px 0 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1><i class="fas fa-search"></i> File Usage Verification Tool</h1>
        <p class="lead">Scanning all PHP files to verify what's actually being used...</p>
        
        <?php
        
        // Function to scan a file for includes/requires
        function scanFileForReferences($filepath) {
            if (!file_exists($filepath)) return [];
            
            $content = file_get_contents($filepath);
            $references = [];
            
            // Look for include, require, include_once, require_once
            $patterns = [
                '/(?:include|require|include_once|require_once)\s*[(\'"]\s*([^)\'";]+)/i',
                '/fetch\([\'"]([^\'"]+\.php)[\'"]\)/i', // JavaScript fetch calls
                '/href=[\'"]([^\'"]+\.php)[\'"](?!\s*class=)/i', // HTML hrefs (not nav items)
                '/action=[\'"]([^\'"]+\.php)[\'"]/i', // Form actions
                '/location:\s*[\'"]([^\'"]+\.php)[\'"]/i', // Header redirects
                '/window\.location\s*=\s*[\'"]([^\'"]+\.php)[\'"]/i' // JS redirects
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $content, $matches)) {
                    foreach ($matches[1] as $match) {
                        // Clean up the match
                        $match = trim($match);
                        $match = str_replace(['includes/', 'api/', '../'], '', $match);
                        if (!empty($match) && strpos($match, '.php') !== false) {
                            $references[] = basename($match);
                        }
                    }
                }
            }
            
            return array_unique($references);
        }
        
        // Function to get all PHP files
        function getAllPhpFiles($dir = '.', $exclude_dirs = ['vendor', 'node_modules', 'archive', 'documentation']) {
            $files = [];
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($items as $item) {
                if ($item->isFile() && $item->getExtension() === 'php') {
                    $path = $item->getPathname();
                    
                    // Skip excluded directories
                    $skip = false;
                    foreach ($exclude_dirs as $exclude) {
                        if (strpos($path, DIRECTORY_SEPARATOR . $exclude . DIRECTORY_SEPARATOR) !== false) {
                            $skip = true;
                            break;
                        }
                    }
                    
                    if (!$skip) {
                        $files[] = str_replace('\\', '/', $path);
                    }
                }
            }
            
            return $files;
        }
        
        echo '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> Scanning files... This may take a minute...</div>';
        flush();
        
        // Get all PHP files
        $all_files = getAllPhpFiles('.');
        $file_usage = [];
        
        // Initialize usage counter for all files
        foreach ($all_files as $file) {
            $basename = basename($file);
            $file_usage[$basename] = [
                'path' => $file,
                'count' => 0,
                'referenced_by' => []
            ];
        }
        
        // Scan each file for references to other files
        foreach ($all_files as $file) {
            $references = scanFileForReferences($file);
            foreach ($references as $ref) {
                if (isset($file_usage[$ref])) {
                    $file_usage[$ref]['count']++;
                    $file_usage[$ref]['referenced_by'][] = basename($file);
                }
            }
        }
        
        // Always-keep files (core entry points)
        $always_keep = [
            'index.php', 'login.php', 'logout.php', 'conn.php', 
            'admin_sidebar.php', 'staff_sidebar.php', 'client_sidebar.php'
        ];
        
        // Categorize files
        $safe_to_delete = [];
        $should_keep = [];
        $review_needed = [];
        
        foreach ($file_usage as $filename => $info) {
            // Check if it's a core file
            if (in_array($filename, $always_keep)) {
                $should_keep[$filename] = $info;
            }
            // Check if it starts with admin_, staff_, or client_
            elseif (preg_match('/^(admin|staff|client)_/', $filename)) {
                $should_keep[$filename] = $info;
            }
            // Check if it's actively used (referenced 1+ times)
            elseif ($info['count'] >= 1) {
                $should_keep[$filename] = $info;
            }
            // Check if it starts with test_, check_, debug_
            elseif (preg_match('/^(test|check|debug|examine|setup|generate|populate|import)_/', $filename)) {
                $safe_to_delete[$filename] = $info;
            }
            // Otherwise needs review
            else {
                $review_needed[$filename] = $info;
            }
        }
        
        // Sort arrays
        ksort($safe_to_delete);
        ksort($should_keep);
        ksort($review_needed);
        
        ?>
        
        <!-- Summary Statistics -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">✅ Keep These</h5>
                        <p class="card-text display-4"><?php echo count($should_keep); ?></p>
                        <small>Actively used files</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">🗑️ Safe to Delete</h5>
                        <p class="card-text display-4"><?php echo count($safe_to_delete); ?></p>
                        <small>Not referenced anywhere</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">⚠️ Review These</h5>
                        <p class="card-text display-4"><?php echo count($review_needed); ?></p>
                        <small>Manual verification needed</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Safe to Delete Section -->
        <div class="section-header">
            <h3><i class="fas fa-trash"></i> ❌ SAFE TO DELETE (<?php echo count($safe_to_delete); ?> files)</h3>
        </div>
        <p class="text-muted">These files are NOT referenced anywhere in your codebase:</p>
        
        <?php foreach ($safe_to_delete as $filename => $info): ?>
            <div class="file-card safe-delete">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5>
                            <i class="fas fa-file-code text-danger"></i> 
                            <code><?php echo htmlspecialchars($filename); ?></code>
                        </h5>
                        <p class="text-muted mb-1"><?php echo htmlspecialchars($info['path']); ?></p>
                        <span class="badge bg-danger">
                            <i class="fas fa-times"></i> Not referenced (<?php echo $info['count']; ?> uses)
                        </span>
                    </div>
                    <button class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo htmlspecialchars($filename); ?>')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Review Needed Section -->
        <div class="section-header mt-5">
            <h3><i class="fas fa-exclamation-triangle"></i> ⚠️ REVIEW NEEDED (<?php echo count($review_needed); ?> files)</h3>
        </div>
        <p class="text-muted">These files have low usage - verify manually before deleting:</p>
        
        <?php foreach ($review_needed as $filename => $info): ?>
            <div class="file-card review-file">
                <h5>
                    <i class="fas fa-file-code text-warning"></i> 
                    <code><?php echo htmlspecialchars($filename); ?></code>
                </h5>
                <p class="text-muted mb-1"><?php echo htmlspecialchars($info['path']); ?></p>
                <div>
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-link"></i> Referenced <?php echo $info['count']; ?> time(s)
                    </span>
                    <?php if (!empty($info['referenced_by'])): ?>
                        <div class="used-by mt-2">
                            <strong>Used by:</strong>
                            <ul class="mb-0">
                                <?php foreach (array_slice($info['referenced_by'], 0, 5) as $ref): ?>
                                    <li><?php echo htmlspecialchars($ref); ?></li>
                                <?php endforeach; ?>
                                <?php if (count($info['referenced_by']) > 5): ?>
                                    <li><em>... and <?php echo count($info['referenced_by']) - 5; ?> more</em></li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <!-- Keep Files Section -->
        <div class="section-header mt-5">
            <h3><i class="fas fa-check-circle"></i> ✅ KEEP THESE (<?php echo count($should_keep); ?> files)</h3>
        </div>
        <p class="text-muted">These files are actively used in your system:</p>
        
        <div class="row">
            <?php 
            $chunks = array_chunk($should_keep, ceil(count($should_keep) / 3), true);
            foreach ($chunks as $chunk): 
            ?>
                <div class="col-md-4">
                    <?php foreach ($chunk as $filename => $info): ?>
                        <div class="file-card keep-file">
                            <h6>
                                <i class="fas fa-check-circle text-success"></i> 
                                <code class="small"><?php echo htmlspecialchars($filename); ?></code>
                            </h6>
                            <span class="badge bg-success">
                                <?php echo $info['count']; ?> reference(s)
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Actions -->
        <div class="card mt-5">
            <div class="card-body">
                <h4><i class="fas fa-rocket"></i> Next Steps</h4>
                <ol>
                    <li><strong>Review the "Safe to Delete" section</strong> - These files are confirmed unused</li>
                    <li><strong>Check "Review Needed" files manually</strong> - Look at what's referencing them</li>
                    <li><strong>Use the cleanup tool</strong> to remove confirmed unused files</li>
                    <li><strong>Test thoroughly</strong> after cleanup</li>
                </ol>
                
                <div class="mt-3">
                    <a href="cleanup_unused_files.php" class="btn btn-danger btn-lg">
                        <i class="fas fa-trash"></i> Proceed to Cleanup Tool
                    </a>
                    <a href="UNUSED_FILES_REPORT.md" target="_blank" class="btn btn-info btn-lg">
                        <i class="fas fa-file-alt"></i> View Full Report
                    </a>
                    <a href="admin_dashboard.php" class="btn btn-secondary btn-lg">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Export Results -->
        <div class="card mt-3">
            <div class="card-body">
                <h5>📊 Export Verification Results</h5>
                <form method="POST" action="export_verification.php">
                    <input type="hidden" name="safe_delete" value='<?php echo json_encode(array_keys($safe_to_delete)); ?>'>
                    <input type="hidden" name="review_needed" value='<?php echo json_encode(array_keys($review_needed)); ?>'>
                    <input type="hidden" name="keep_files" value='<?php echo json_encode(array_keys($should_keep)); ?>'>
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="fas fa-download"></i> Export Results to Text File
                    </button>
                </form>
            </div>
        </div>
        
    </div>
    
    <script>
        function confirmDelete(filename) {
            if (confirm('Are you sure you want to delete ' + filename + '?\n\nThis action cannot be undone.')) {
                // Create form and submit
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete_single_file.php';
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'filename';
                input.value = filename;
                
                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>

