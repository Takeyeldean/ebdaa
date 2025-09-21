<?php
// Image Optimization Utility for Ebdaa
// This script optimizes all images in the uploads folder

function optimizeImage($sourcePath, $destinationPath, $quality = 80, $maxWidth = 800) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    
    // Create image resource based on type
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    // Calculate new dimensions
    $ratio = $width / $height;
    $newWidth = min($width, $maxWidth);
    $newHeight = $newWidth / $ratio;
    
    // Create new image
    $optimizedImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG
    if ($mimeType === 'image/png') {
        imagealphablending($optimizedImage, false);
        imagesavealpha($optimizedImage, true);
        $transparent = imagecolorallocatealpha($optimizedImage, 255, 255, 255, 127);
        imagefilledrectangle($optimizedImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize image
    imagecopyresampled($optimizedImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save optimized image
    $result = false;
    switch ($mimeType) {
        case 'image/jpeg':
            $result = imagejpeg($optimizedImage, $destinationPath, $quality);
            break;
        case 'image/png':
            $result = imagepng($optimizedImage, $destinationPath, 9);
            break;
        case 'image/gif':
            $result = imagegif($optimizedImage, $destinationPath);
            break;
        case 'image/webp':
            $result = imagewebp($optimizedImage, $destinationPath, $quality);
            break;
    }
    
    // Clean up memory
    imagedestroy($sourceImage);
    imagedestroy($optimizedImage);
    
    return $result;
}

function createWebP($sourcePath, $destinationPath, $quality = 80) {
    $imageInfo = getimagesize($sourcePath);
    if (!$imageInfo) {
        return false;
    }
    
    $mimeType = $imageInfo['mime'];
    
    switch ($mimeType) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$sourceImage) {
        return false;
    }
    
    $result = imagewebp($sourceImage, $destinationPath, $quality);
    imagedestroy($sourceImage);
    
    return $result;
}

// Main optimization process
echo "🚀 Starting image optimization...\n";

$uploadsDir = 'uploads/';
$optimizedDir = 'uploads/optimized/';
$webpDir = 'uploads/webp/';

// Create directories if they don't exist
if (!is_dir($optimizedDir)) {
    mkdir($optimizedDir, 0755, true);
}
if (!is_dir($webpDir)) {
    mkdir($webpDir, 0755, true);
}

$totalImages = 0;
$optimizedImages = 0;
$webpImages = 0;
$totalSavings = 0;

// Get all image files
$imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$files = glob($uploadsDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);

foreach ($files as $file) {
    $totalImages++;
    $filename = basename($file);
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    
    // Skip if already optimized
    if (strpos($filename, '_opt') !== false) {
        continue;
    }
    
    $originalSize = filesize($file);
    
    // Create optimized filename
    $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
    $optimizedFile = $optimizedDir . $nameWithoutExt . '_opt.' . $extension;
    $webpFile = $webpDir . $nameWithoutExt . '.webp';
    
    // Optimize image
    if (optimizeImage($file, $optimizedFile, 80, 800)) {
        $optimizedImages++;
        $optimizedSize = filesize($optimizedFile);
        $savings = $originalSize - $optimizedSize;
        $totalSavings += $savings;
        
        echo "✅ Optimized: $filename (Saved: " . number_format($savings) . " bytes)\n";
    }
    
    // Create WebP version
    if (createWebP($file, $webpFile, 80)) {
        $webpImages++;
        echo "✅ WebP created: $filename\n";
    }
}

echo "\n🎉 Image optimization completed!\n";
echo "📊 Results:\n";
echo "   - Total images processed: $totalImages\n";
echo "   - Optimized images: $optimizedImages\n";
echo "   - WebP images created: $webpImages\n";
echo "   - Total space saved: " . number_format($totalSavings) . " bytes (" . round($totalSavings / 1024, 2) . " KB)\n";
echo "   - Average savings: " . ($totalImages > 0 ? round(($totalSavings / $totalImages) / 1024, 2) : 0) . " KB per image\n";

// Generate optimized image manifest
$manifest = [
    'generated' => date('Y-m-d H:i:s'),
    'total_images' => $totalImages,
    'optimized_images' => $optimizedImages,
    'webp_images' => $webpImages,
    'total_savings' => $totalSavings
];

file_put_contents('uploads/optimization_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT));
echo "📄 Optimization manifest saved to uploads/optimization_manifest.json\n";
?>
