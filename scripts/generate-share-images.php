#!/usr/bin/env php
<?php
/**
 * generate-share-images.php
 *
 * Scan images/full and write 1200x630 WebP crops to images/share.
 */

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$sourceDir = $projectRoot . '/images/full';
$destDir   = $projectRoot . '/images/share';
$ogW       = 1200;
$ogH       =  630;

// make sure destination exists
if (! is_dir($destDir)) {
    if (! mkdir($destDir, 0755, true)) {
        fwrite(STDERR, "Error: could not create {$destDir}\n");
        exit(1);
    }
}

// grab all JPG/PNG/WEBP files
$patterns = ['jpg','jpeg','png','webp'];
$files = [];
foreach ($patterns as $ext) {
    $files = array_merge($files, glob("{$sourceDir}/*.{$ext}"));
}

if (empty($files)) {
    fwrite(STDOUT, "No images found in {$sourceDir}\n");
    exit(0);
}

foreach ($files as $srcPath) {
    $base     = pathinfo($srcPath, PATHINFO_FILENAME);
    $destPath = "{$destDir}/{$base}.webp";

    try {
        if (extension_loaded('imagick')) {
            // Imagick: easy center-crop and resize.
            $im = new Imagick($srcPath);
            $im->cropThumbnailImage($ogW, $ogH);
            $im->setImageFormat('webp');
            $im->writeImage($destPath);
            $im->clear();
            $im->destroy();
        } else {
            // GD fallback
            [$w, $h, $type] = getimagesize($srcPath);
            switch ($type) {
                case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($srcPath); break;
                case IMAGETYPE_PNG:  $src = imagecreatefrompng($srcPath);  break;
                case IMAGETYPE_WEBP: $src = imagecreatefromwebp($srcPath); break;
                default:
                    throw new RuntimeException("Unsupported image type: {$srcPath}");
            }

            // compute resized dimensions to cover OG box
            $srcRatio = $w / $h;
            $ogRatio  = $ogW / $ogH;
            if ($srcRatio > $ogRatio) {
                // Too wide: resize to full height.
                $newH = $ogH;
                $newW = (int) round($ogH * $srcRatio);
            } else {
                // Too tall: resize to full width.
                $newW = $ogW;
                $newH = (int) round($ogW / $srcRatio);
            }

            // resize
            $tmp = imagecreatetruecolor($newW, $newH);
            imagecopyresampled($tmp, $src, 0,0, 0,0, $newW, $newH, $w, $h);

            // Center-crop to exact 1200x630.
            $dst = imagecreatetruecolor($ogW, $ogH);
            $x = (int)(($newW - $ogW) / 2);
            $y = (int)(($newH - $ogH) / 2);
            imagecopy($dst, $tmp, 0,0, $x,$y, $ogW, $ogH);

            // write WebP
            if (! imagewebp($dst, $destPath, 80)) {
                throw new RuntimeException("Failed to save WebP: {$destPath}");
            }

            // clean up GD resources
            imagedestroy($src);
            imagedestroy($tmp);
            imagedestroy($dst);
        }

        fwrite(STDOUT, "Created share/{$base}.webp\n");
    } catch (\Throwable $e) {
        fwrite(STDERR, "Error processing {$srcPath}: {$e->getMessage()}\n");
    }
}

fwrite(STDOUT, "Done.\n");
