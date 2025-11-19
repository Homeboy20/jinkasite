<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Ensure the user is authenticated as admin
$auth = requireAuth('admin');

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();

    $search = trim($_GET['q'] ?? '');
    $formatFilter = strtolower(trim($_GET['format'] ?? ''));
    $productFilter = isset($_GET['product_id']) && $_GET['product_id'] !== ''
        ? (int)$_GET['product_id']
        : null;

    $uploadDir = '../images/products/';
    $images = [];

    $formatBytes = function ($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    };

    // Load media records linked to products
    $stmt = $db->prepare("SELECT pi.id, pi.image_path, pi.product_id, pi.is_featured, pi.sort_order, pi.alt_text, pi.created_at, p.name AS product_name
                           FROM product_images pi
                           LEFT JOIN products p ON pi.product_id = p.id
                           ORDER BY pi.created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();

    $indexed = [];

    while ($row = $result->fetch_assoc()) {
        if (empty($row['image_path'])) {
            continue;
        }

        $filename = $row['image_path'];
        $filePath = $uploadDir . $filename;
        $exists = is_file($filePath);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $filesize = $exists ? filesize($filePath) : null;

        $indexed[$filename] = [
            'id' => (int)$row['id'],
            'filename' => $filename,
            'url' => 'images/products/' . $filename,
            'product_id' => $row['product_id'] ? (int)$row['product_id'] : null,
            'product_name' => $row['product_name'] ?? null,
            'is_featured' => (bool)$row['is_featured'],
            'sort_order' => isset($row['sort_order']) ? (int)$row['sort_order'] : 0,
            'alt_text' => $row['alt_text'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'exists' => $exists,
            'extension' => $extension,
            'filesize' => $filesize,
            'size_label' => $filesize ? $formatBytes($filesize) : null,
            'last_modified' => $exists ? date(DATE_ATOM, filemtime($filePath)) : null,
            'source' => 'product',
            'orphaned' => false
        ];
    }

    $stmt->close();

    // Include files that live on disk but are not linked to a product yet
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..' || $file === 'thumbnails') {
                continue;
            }

            $filePath = $uploadDir . $file;
            if (!is_file($filePath)) {
                continue;
            }

            if (!isset($indexed[$file])) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $filesize = filesize($filePath);

                $indexed[$file] = [
                    'id' => null,
                    'filename' => $file,
                    'url' => 'images/products/' . $file,
                    'product_id' => null,
                    'product_name' => null,
                    'is_featured' => false,
                    'sort_order' => 0,
                    'alt_text' => null,
                    'created_at' => null,
                    'exists' => true,
                    'extension' => $extension,
                    'filesize' => $filesize,
                    'size_label' => $formatBytes($filesize),
                    'last_modified' => date(DATE_ATOM, filemtime($filePath)),
                    'source' => 'filesystem',
                    'orphaned' => true
                ];
            }
        }
    }

    $images = array_values($indexed);

    // Apply filters
    $filtered = array_filter($images, function ($item) use ($search, $formatFilter, $productFilter) {
        $matchesSearch = true;
        $matchesFormat = true;
        $matchesProduct = true;

        if ($search !== '') {
            $haystack = strtolower($item['filename'] . ' ' . ($item['product_name'] ?? ''));
            $matchesSearch = strpos($haystack, strtolower($search)) !== false;
        }

        if ($formatFilter !== '') {
            $matchesFormat = $item['extension'] === $formatFilter;
            if (!$matchesFormat && $formatFilter === 'jpg') {
                $matchesFormat = in_array($item['extension'], ['jpg', 'jpeg'], true);
            }
        }

        if ($productFilter !== null) {
            if ($productFilter === 0) {
                $matchesProduct = empty($item['product_id']);
            } else {
                $matchesProduct = (int)$item['product_id'] === $productFilter;
            }
        }

        return $matchesSearch && $matchesFormat && $matchesProduct;
    });

    // Re-index filtered results
    $filtered = array_values($filtered);

    echo json_encode([
        'success' => true,
        'images' => $filtered,
        'counts' => [
            'total' => count($images),
            'filtered' => count($filtered)
        ]
    ]);
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
