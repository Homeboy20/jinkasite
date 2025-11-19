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
    exit;
}

// Initialize database connection
$db = Database::getInstance()->getConnection();

$uploadDir = '../images/products/';

// Handle different actions
$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Handle JSON requests
if (!$action) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;
}

switch ($action) {
    case 'delete':
        // Delete image from database and filesystem
        $id = (int)($_POST['id'] ?? 0);
        $filename = trim($_POST['filename'] ?? '');
        $filename = basename($filename); // Prevent directory traversal
        
        if ($id > 0) {
            // Delete from database
            $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            
            // Delete file
            $filepath = $uploadDir . $filename;
            $thumbnailPath = $uploadDir . 'thumbnails/thumb_' . $filename;
            
            $deleted = false;
            if (file_exists($filepath)) {
                $deleted = @unlink($filepath);
            }
            
            if (file_exists($thumbnailPath)) {
                @unlink($thumbnailPath);
            }
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Image deleted successfully'
            ]);
            exit;
        } else {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Invalid image ID'
            ]);
            exit;
        }
        break;
        
    case 'delete_orphaned':
        // Delete orphaned file
        $filename = trim($_POST['filename'] ?? '');
        $filename = basename($filename); // Prevent directory traversal
        
        if (empty($filename)) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'No filename provided'
            ]);
            exit;
        }
        
        $filepath = $uploadDir . $filename;
        $thumbnailPath = $uploadDir . 'thumbnails/thumb_' . $filename;
        
        $deleted = false;
        if (file_exists($filepath)) {
            $deleted = @unlink($filepath);
        }
        
        if (file_exists($thumbnailPath)) {
            @unlink($thumbnailPath);
        }
        
        ob_clean();
        echo json_encode([
            'success' => $deleted,
            'message' => $deleted ? 'File deleted successfully' : 'File not found'
        ]);
        exit;
        
    case 'cleanup_orphaned':
        // Find and delete all orphaned files
        try {
            $query = "SELECT image_path FROM product_images";
            $result = $db->query($query);
            $dbFiles = [];
            while ($row = $result->fetch_assoc()) {
                $dbFiles[] = $row['image_path'];
            }
            
            // Get all files in directory
            $allFiles = [];
            if (is_dir($uploadDir)) {
                $files = scandir($uploadDir);
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && $file != 'thumbnails' && is_file($uploadDir . $file)) {
                        $allFiles[] = $file;
                    }
                }
            }
            
            // Find orphaned files
            $orphanedFiles = array_diff($allFiles, $dbFiles);
            
            $deletedCount = 0;
            $errors = [];
            
            foreach ($orphanedFiles as $file) {
                $filepath = $uploadDir . $file;
                $thumbnailPath = $uploadDir . 'thumbnails/thumb_' . $file;
                
                if (file_exists($filepath)) {
                    if (@unlink($filepath)) {
                        $deletedCount++;
                    } else {
                        $errors[] = $file;
                    }
                }
                
                if (file_exists($thumbnailPath)) {
                    @unlink($thumbnailPath);
                }
            }
            
            ob_clean();
            echo json_encode([
                'success' => true,
                'deleted' => $deletedCount,
                'total_orphaned' => count($orphanedFiles),
                'errors' => $errors,
                'message' => "Successfully deleted $deletedCount of " . count($orphanedFiles) . " orphaned files"
            ]);
            exit;
        } catch (Exception $e) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Failed to cleanup: ' . $e->getMessage()
            ]);
            exit;
        }
        
    case 'bulk_delete':
        // Bulk delete images
        $input = json_decode(file_get_contents('php://input'), true);
        $ids = $input['ids'] ?? [];
        
        if (empty($ids)) {
            ob_clean();
            echo json_encode([
                'success' => false,
                'error' => 'No images selected'
            ]);
            exit;
        }
        
        $deletedCount = 0;
        foreach ($ids as $id) {
            $id = (int)$id;
            
            // Get filename
            $stmt = $db->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row) {
                $filename = $row['image_path'];
                
                // Delete from database
                $stmt = $db->prepare("DELETE FROM product_images WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                
                // Delete file
                $filepath = $uploadDir . $filename;
                $thumbnailPath = $uploadDir . 'thumbnails/thumb_' . $filename;
                
                if (file_exists($filepath)) {
                    @unlink($filepath);
                }
                
                if (file_exists($thumbnailPath)) {
                    @unlink($thumbnailPath);
                }
                
                $deletedCount++;
            }
        }
        
        ob_clean();
        echo json_encode([
            'success' => true,
            'deleted' => $deletedCount,
            'message' => "Deleted $deletedCount images"
        ]);
        exit;
        
    default:
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Invalid action'
        ]);
        exit;
}
