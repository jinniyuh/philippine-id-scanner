<?php
/**
 * Debug Image Validation
 * 
 * This script helps debug image validation issues
 */

// Test the image validation logic
function testImageValidation($imagePath) {
    echo "<h3>Testing Image: $imagePath</h3>";
    
    if (!file_exists($imagePath)) {
        echo "<div style='color: red;'>❌ File not found</div>";
        return;
    }
    
    echo "<div style='color: green;'>✅ File exists</div>";
    
    // Check file size
    $fileSize = filesize($imagePath);
    echo "<div>File size: " . number_format($fileSize) . " bytes</div>";
    
    if ($fileSize > 10 * 1024 * 1024) {
        echo "<div style='color: red;'>❌ File too large (>10MB)</div>";
        return;
    }
    echo "<div style='color: green;'>✅ File size OK</div>";
    
    // Check image info
    $imageInfo = getimagesize($imagePath);
    if ($imageInfo === false) {
        echo "<div style='color: red;'>❌ Invalid image file</div>";
        return;
    }
    
    echo "<div style='color: green;'>✅ Valid image file</div>";
    echo "<div>Image dimensions: {$imageInfo[0]} x {$imageInfo[1]}</div>";
    echo "<div>Image type: " . $imageInfo[2] . "</div>";
    
    // Check image type
    $imageType = $imageInfo[2];
    $supportedTypes = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_BMP];
    
    // Add TIFF and WEBP if available
    if (defined('IMAGETYPE_TIFF')) {
        $supportedTypes[] = IMAGETYPE_TIFF;
    }
    if (defined('IMAGETYPE_WEBP')) {
        $supportedTypes[] = IMAGETYPE_WEBP;
    }
    
    if (!in_array($imageType, $supportedTypes)) {
        echo "<div style='color: red;'>❌ Unsupported image type: $imageType</div>";
        return;
    }
    
    echo "<div style='color: green;'>✅ Supported image type</div>";
    
    // Test base64 encoding
    $imageData = file_get_contents($imagePath);
    $base64Image = base64_encode($imageData);
    echo "<div>Base64 size: " . strlen($base64Image) . " characters</div>";
    echo "<div style='color: green;'>✅ Base64 encoding successful</div>";
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Image Validation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>Debug Image Validation</h1>
    
    <form method="post" enctype="multipart/form-data">
        <label for="test_image">Select an image to test:</label><br>
        <input type="file" id="test_image" name="test_image" accept="image/*" required><br><br>
        <button type="submit">Test Image Validation</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
        $uploadedFile = $_FILES['test_image'];
        
        if ($uploadedFile['error'] === UPLOAD_ERR_OK) {
            $tempFile = $uploadedFile['tmp_name'];
            $originalName = $uploadedFile['name'];
            
            echo "<h2>Uploaded File: $originalName</h2>";
            testImageValidation($tempFile);
        } else {
            echo "<div class='error'>Upload error: " . $uploadedFile['error'] . "</div>";
        }
    }
    ?>
    
    <h2>Supported Image Types:</h2>
    <ul>
        <li>JPEG (IMAGETYPE_JPEG = <?php echo IMAGETYPE_JPEG; ?>)</li>
        <li>PNG (IMAGETYPE_PNG = <?php echo IMAGETYPE_PNG; ?>)</li>
        <li>GIF (IMAGETYPE_GIF = <?php echo IMAGETYPE_GIF; ?>)</li>
        <li>BMP (IMAGETYPE_BMP = <?php echo IMAGETYPE_BMP; ?>)</li>
        <?php if (defined('IMAGETYPE_TIFF')): ?>
        <li>TIFF (IMAGETYPE_TIFF = <?php echo IMAGETYPE_TIFF; ?>)</li>
        <?php endif; ?>
        <?php if (defined('IMAGETYPE_WEBP')): ?>
        <li>WEBP (IMAGETYPE_WEBP = <?php echo IMAGETYPE_WEBP; ?>)</li>
        <?php endif; ?>
    </ul>
</body>
</html>
