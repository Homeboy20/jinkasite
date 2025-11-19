<?php
// Disable all error output to ensure clean JSON responses
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering immediately
ob_start();

// Define access constant for config.php
define('JINKA_ACCESS', true);

session_start();
require_once '../includes/config.php';

// Clear any output buffers that might have content
while (ob_get_level() > 1) {
    ob_end_clean();
}
ob_clean();

// Set JSON header first
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    ob_end_flush();
    exit;
}

class ImageUploader {
    private $uploadDir = '../images/products/';
    private $maxFileSize = 10485760; // 10MB
    private $allowedTypes = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
    'image/avif', 'image/bmp', 'image/svg+xml', 'image/heic', 'image/heif',
    'image/jxl', 'image/x-icon', 'image/vnd.microsoft.icon'
    ];
    private $allowedExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'bmp', 'svg',
        'heic', 'heif', 'jxl', 'ico'
    ];
    
    public function __construct() {
        // Create upload directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    public function upload($file) {
        try {
            // Validate file
            $validation = $this->validateFile($file);
            if (!$validation['valid']) {
                return ['success' => false, 'error' => $validation['error']];
            }
            
            // Generate unique filename
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $filename = $this->generateUniqueFilename($extension);
            $filepath = $this->uploadDir . $filename;
            
            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                return ['success' => false, 'error' => 'Failed to move uploaded file'];
            }
            
            // Optimize image
            $this->optimizeImage($filepath, $extension);
            
            // Generate thumbnail
            $thumbnail = $this->generateThumbnail($filepath, $filename, $extension);
            
            return [
                'success' => true,
                'filename' => $filename,
                'thumbnail' => $thumbnail,
                'path' => 'images/products/' . $filename,
                'size' => filesize($filepath),
                'type' => $extension
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function validateFile($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['valid' => false, 'error' => 'Upload error: ' . $this->getUploadErrorMessage($file['error'])];
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            return ['valid' => false, 'error' => 'File size exceeds maximum allowed size (10MB)'];
        }
        
        // Check file type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return ['valid' => false, 'error' => 'Invalid file type. Allowed formats: JPG, PNG, GIF, WebP, AVIF, BMP, SVG, HEIC, HEIF, JXL, ICO'];
        }
        
        // Check file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            return ['valid' => false, 'error' => 'Invalid file extension'];
        }
        
        // Check if it's actually an image (skip formats that PHP cannot introspect reliably)
        if (!in_array($extension, ['svg', 'ico', 'heic', 'heif', 'jxl'])) {
            $imageInfo = getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                return ['valid' => false, 'error' => 'File is not a valid image'];
            }
        }
        
        return ['valid' => true];
    }
    
    private function generateUniqueFilename($extension) {
        do {
            $filename = 'product_' . uniqid() . '_' . time() . '.' . $extension;
        } while (file_exists($this->uploadDir . $filename));
        
        return $filename;
    }
    
    private function optimizeImage($filepath, $extension) {
        // Skip optimization for SVG and modern formats that don't need it
        if (in_array($extension, ['svg', 'avif', 'jxl'])) {
            return;
        }

        $image = null;
        
        // Load image based on type
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $image = @imagecreatefrompng($filepath);
                break;
            case 'gif':
                $image = @imagecreatefromgif($filepath);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($filepath);
                }
                break;
            case 'avif':
                if (function_exists('imagecreatefromavif')) {
                    $image = @imagecreatefromavif($filepath);
                }
                break;
            case 'bmp':
                if (function_exists('imagecreatefrombmp')) {
                    $image = @imagecreatefrombmp($filepath);
                }
                break;
            default:
                return;
        }
        
        if (!$image) {
            return;
        }
        
        // Get original dimensions
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize if too large (max 1600px width for modern displays)
        $maxWidth = 1600;
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($height * ($maxWidth / $width));
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            // Preserve transparency for PNG, GIF, WebP, and AVIF
            if (in_array($extension, ['png', 'gif', 'webp', 'avif'])) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            // Save optimized image
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($resized, $filepath, 90); // Higher quality
                    break;
                case 'png':
                    imagepng($resized, $filepath, 7); // Better compression
                    break;
                case 'gif':
                    imagegif($resized, $filepath);
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($resized, $filepath, 90);
                    }
                    break;
                case 'avif':
                    if (function_exists('imageavif')) {
                        imageavif($resized, $filepath, 85);
                    }
                    break;
                case 'bmp':
                    if (function_exists('imagebmp')) {
                        imagebmp($resized, $filepath);
                    }
                    break;
            }
            
            imagedestroy($resized);
        }
        
        imagedestroy($image);
    }
    
    private function generateThumbnail($filepath, $filename, $extension) {
        $thumbnailDir = $this->uploadDir . 'thumbnails/';
        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        // Skip thumbnail for SVG (use original)
        if ($extension === 'svg') {
            return null;
        }
        
        $image = null;
        
        // Load original image
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = @imagecreatefromjpeg($filepath);
                break;
            case 'png':
                $image = @imagecreatefrompng($filepath);
                break;
            case 'gif':
                $image = @imagecreatefromgif($filepath);
                break;
            case 'webp':
                if (function_exists('imagecreatefromwebp')) {
                    $image = @imagecreatefromwebp($filepath);
                }
                break;
            case 'avif':
                if (function_exists('imagecreatefromavif')) {
                    $image = @imagecreatefromavif($filepath);
                }
                break;
            case 'bmp':
                if (function_exists('imagecreatefrombmp')) {
                    $image = @imagecreatefrombmp($filepath);
                }
                break;
            default:
                return null;
        }
        
        if (!$image) {
            return null;
        }
        
        // Calculate thumbnail dimensions (300x300 for modern displays)
        $thumbSize = 300;
        $width = imagesx($image);
        $height = imagesy($image);
        
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        
        // Preserve transparency for modern formats
        if (in_array($extension, ['png', 'gif', 'webp', 'avif'])) {
            imagealphablending($thumb, false);
            imagesavealpha($thumb, true);
            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
            imagefilledrectangle($thumb, 0, 0, $thumbSize, $thumbSize, $transparent);
        }
        
        // Calculate crop dimensions to maintain aspect ratio
        $ratio = $width / $height;
        if ($ratio > 1) {
            // Landscape
            $newWidth = $height;
            $newHeight = $height;
            $srcX = ($width - $height) / 2;
            $srcY = 0;
        } else {
            // Portrait or square
            $newWidth = $width;
            $newHeight = $width;
            $srcX = 0;
            $srcY = ($height - $width) / 2;
        }
        
        imagecopyresampled($thumb, $image, 0, 0, $srcX, $srcY, $thumbSize, $thumbSize, $newWidth, $newHeight);
        
        // Save thumbnail (always as WebP for best compression if available, otherwise JPEG)
        $thumbnailPath = $thumbnailDir . 'thumb_' . pathinfo($filename, PATHINFO_FILENAME);
        
        if (function_exists('imagewebp')) {
            $thumbnailPath .= '.webp';
            imagewebp($thumb, $thumbnailPath, 85);
            $thumbFilename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.webp';
        } else {
            $thumbnailPath .= '.jpg';
            imagejpeg($thumb, $thumbnailPath, 85);
            $thumbFilename = 'thumb_' . pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
        }
        
        imagedestroy($image);
        imagedestroy($thumb);
        
        return $thumbFilename;
    }
    
    private function getUploadErrorMessage($errorCode) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
    
    public function deleteImage($filename) {
        if (empty($filename)) {
            return ['success' => false, 'error' => 'No filename provided'];
        }
        
        $filepath = $this->uploadDir . $filename;
        $thumbnailPath = $this->uploadDir . 'thumbnails/thumb_' . $filename;
        
        $mainDeleted = false;
        $thumbDeleted = false;
        
        // Delete main image
        if (file_exists($filepath)) {
            $mainDeleted = @unlink($filepath);
        } else {
            $mainDeleted = true; // Consider it deleted if it doesn't exist
        }
        
        // Delete thumbnail
        if (file_exists($thumbnailPath)) {
            $thumbDeleted = @unlink($thumbnailPath);
        } else {
            $thumbDeleted = true; // Consider it deleted if it doesn't exist
        }
        
        return [
            'success' => $mainDeleted && $thumbDeleted,
            'message' => $mainDeleted && $thumbDeleted ? 'Image deleted successfully' : 'Failed to delete some files'
        ];
    }
}

// Handle the upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        // Delete image
        $filename = trim($_POST['filename'] ?? '');
        // Basic sanitization
        $filename = basename($filename); // Prevent directory traversal
        $uploader = new ImageUploader();
        $result = $uploader->deleteImage($filename);
        
        ob_clean();
        echo json_encode($result);
        ob_end_flush();
        exit;
    } elseif (isset($_FILES['image'])) {
        // Upload new image
        $uploader = new ImageUploader();
        $result = $uploader->upload($_FILES['image']);
        
        ob_clean();
        echo json_encode($result);
        ob_end_flush();
        exit;
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'No file uploaded']);
        ob_end_flush();
        exit;
    }
} else {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    ob_end_flush();
    exit;
}
