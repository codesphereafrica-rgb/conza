<?php
// Script: tools/resize_uploads.php
// Usage: php tools/resize_uploads.php

$base = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads';
$maxWidth = 320;
$maxHeight = 320;

if (!is_dir($base)) {
    echo "Uploads folder not found: $base\n";
    exit(1);
}

$files = array_values(array_filter(scandir($base), function($f) use ($base) {
    return is_file($base . DIRECTORY_SEPARATOR . $f);
}));

if (count($files) === 0) {
    echo "No files to process in $base\n";
    exit(0);
}

$processed = 0;
$skipped = 0;

foreach ($files as $file) {
    $path = $base . DIRECTORY_SEPARATOR . $file;
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'])) {
        $skipped++;
        continue;
    }

    $info = @getimagesize($path);
    if (!$info) { echo "Skipping invalid image: $file\n"; $skipped++; continue; }
    $mime = $info['mime'] ?? '';

    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $src = @imagecreatefromjpeg($path);
            break;
        case 'image/png':
            $src = @imagecreatefrompng($path);
            break;
        case 'image/gif':
            $src = @imagecreatefromgif($path);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $src = @imagecreatefromwebp($path);
            } else { $src = false; }
            break;
        default:
            $src = false;
    }

    if (!$src) { echo "Cannot open image: $file\n"; $skipped++; continue; }

    $width = imagesx($src);
    $height = imagesy($src);

    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
    $newW = max(1, (int) round($width * $ratio));
    $newH = max(1, (int) round($height * $ratio));

    if ($newW === $width && $newH === $height) {
        imagedestroy($src);
        echo "Already size OK: $file ($width x $height)\n";
        $skipped++;
        continue;
    }

    $dst = imagecreatetruecolor($newW, $newH);
    // preserve transparency for PNG/GIF/WebP
    if (in_array($mime, ['image/png','image/gif','image/webp'])) {
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }

    imagecopyresampled($dst, $src, 0,0,0,0, $newW, $newH, $width, $height);

    // overwrite
    $ok = false;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $ok = imagejpeg($dst, $path, 80);
            break;
        case 'image/png':
            $ok = imagepng($dst, $path, 8);
            break;
        case 'image/gif':
            $ok = imagegif($dst, $path);
            break;
        case 'image/webp':
            if (function_exists('imagewebp')) { $ok = imagewebp($dst, $path, 80); }
            break;
    }

    imagedestroy($src);
    imagedestroy($dst);

    if ($ok) {
        echo "Resized: $file -> {$newW}x{$newH}\n";
        $processed++;
    } else {
        echo "Failed to resize: $file\n";
        $skipped++;
    }
}

echo "Done. Processed: $processed, Skipped: $skipped\n";
