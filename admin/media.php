<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();

// Get all products with their images
$query = "SELECT p.id, p.name, p.slug, 
          COUNT(pi.id) as image_count,
          GROUP_CONCAT(pi.id ORDER BY pi.is_featured DESC, pi.sort_order ASC) as image_ids
          FROM products p
          LEFT JOIN product_images pi ON p.id = pi.product_id
          GROUP BY p.id
          HAVING image_count > 0
          ORDER BY p.name ASC";
$result = $db->query($query);
$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

// Get all product images from database
$query = "SELECT pi.*, p.name as product_name, p.id as product_id 
          FROM product_images pi
          LEFT JOIN products p ON pi.product_id = p.id 
          ORDER BY p.name ASC, pi.is_featured DESC, pi.sort_order ASC";
$result = $db->query($query);
$images = [];
$imagesByProduct = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
    if (!isset($imagesByProduct[$row['product_id']])) {
        $imagesByProduct[$row['product_id']] = [];
    }
    $imagesByProduct[$row['product_id']][] = $row;
}

// Get orphaned images (files not in database)
$uploadDir = '../images/products/';
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
$dbFiles = array_column($images, 'image_path');
$orphanedFiles = array_diff($allFiles, $dbFiles);

// Get statistics
$totalImages = count($images);
$totalOrphaned = count($orphanedFiles);
$totalSize = 0;

foreach ($allFiles as $file) {
    $filePath = $uploadDir . $file;
    if (file_exists($filePath)) {
        $totalSize += filesize($filePath);
    }
}

// Format size
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Manager - JINKA Admin</title>
    <link rel="stylesheet" href="css/admin.css">
    <style>
        /* Modern Media Manager Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .media-manager {
            min-height: 100vh;
            padding: 2rem 0;
        }

        .media-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header Section */
        .media-hero {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .media-hero-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 2rem;
        }

        .media-hero-title {
            flex: 1;
        }

        .media-hero-title h1 {
            margin: 0 0 0.5rem 0;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .media-hero-title p {
            margin: 0;
            color: #64748b;
            font-size: 1.1rem;
        }

        .media-hero-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .media-upload-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 12px 32px rgba(15, 23, 42, 0.12);
        }

        .media-upload-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .media-upload-header h2 {
            font-size: 1.5rem;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 0;
        }

        .media-upload-header span {
            font-size: 0.95rem;
            color: #64748b;
        }

        .upload-dropzone {
            position: relative;
            border: 2px dashed #c7d2fe;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.06) 0%, rgba(118, 75, 162, 0.08) 100%);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .upload-dropzone.dragover {
            border-color: #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.12) 0%, rgba(118, 75, 162, 0.14) 100%);
            box-shadow: 0 12px 24px rgba(102, 126, 234, 0.25);
        }

        .upload-dropzone input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .upload-dropzone p {
            font-size: 1.1rem;
            color: #334155;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .upload-dropzone small {
            display: block;
            color: #64748b;
            font-size: 0.9rem;
        }

        .media-upload-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .upload-status {
            font-size: 0.95rem;
            color: #475569;
        }

        .upload-status.error {
            color: #dc2626;
        }

        .upload-status.success {
            color: #16a34a;
        }

        .btn-hero {
            padding: 1rem 2rem;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-hero-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-hero-secondary {
            background: white;
            color: #667eea;
            border: 2px solid #667eea;
        }

        .btn-hero-secondary:hover {
            background: #f8f9ff;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-icon.green {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-icon.red {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-icon.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1;
        }

        .stat-change {
            font-size: 0.875rem;
            color: #10b981;
            margin-top: 0.5rem;
        }

        /* Tabs */
        .media-tabs {
            background: white;
            border-radius: 16px;
            padding: 1rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .tabs-nav {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: thin;
            scrollbar-color: #667eea #f1f5f9;
        }

        .tabs-nav::-webkit-scrollbar {
            height: 6px;
        }

        .tabs-nav::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .tabs-nav::-webkit-scrollbar-thumb {
            background: #667eea;
            border-radius: 3px;
        }

        .tabs-nav::-webkit-scrollbar-thumb:hover {
            background: #5568d3;
        }

        .tab-button {
            padding: 1rem 1.5rem;
            background: transparent;
            border: 2px solid transparent;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.3s ease;
            white-space: nowrap;
            position: relative;
            flex-shrink: 0;
        }

        .tab-button:hover {
            background: #f8f9ff;
            color: #667eea;
            border-color: #e0e7ff;
        }

        .tab-button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .tab-badge {
            display: inline-block;
            background: rgba(255,255,255,0.3);
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-left: 0.5rem;
        }

        .tab-button.active .tab-badge {
            background: rgba(255,255,255,0.3);
        }

        /* Filters */
        .media-filters {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .filters-grid {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 1rem;
            align-items: center;
        }

        .search-box {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.25rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .filter-select {
            padding: 1rem 1.5rem;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        /* Media Grid */
        .media-content {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2rem;
            padding: 1rem 0;
        }

        .media-card {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .media-card:hover {
            border-color: #667eea;
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.25), 0 10px 20px rgba(102, 126, 234, 0.15);
            transform: translateY(-8px);
        }

        .media-card-image {
            aspect-ratio: 4/3;
            overflow: hidden;
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            position: relative;
        }

        .checkbox-select {
            position: absolute;
            top: 12px;
            left: 12px;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 10;
            accent-color: #667eea;
            transform: scale(1.2);
        }

        .media-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .media-card:hover .media-card-image img {
            transform: scale(1.1);
        }

        .media-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom, 
                rgba(0,0,0,0.8) 0%, 
                transparent 30%, 
                transparent 70%, 
                rgba(0,0,0,0.8) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 1.25rem;
        }

        .media-card:hover .media-overlay {
            opacity: 1;
        }

        .media-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .badge {
            padding: 0.375rem 1rem;
            border-radius: 24px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .badge-featured {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }

        .badge-orphaned {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .media-actions {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
        }

        .action-btn {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.95);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            backdrop-filter: blur(10px);
        }

        .action-btn:hover {
            transform: scale(1.15) rotate(5deg);
            box-shadow: 0 8px 20px rgba(0,0,0,0.25);
            background: white;
        }

        .action-btn:active {
            transform: scale(0.95);
        }

        .media-card-info {
            padding: 1.25rem;
            background: linear-gradient(to bottom, white 0%, #f9fafb 100%);
        }

        .media-title {
            font-weight: 700;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 0.5rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.4;
        }

        .media-title a {
            color: inherit;
            text-decoration: none;
            transition: color 0.2s ease;
        }

        .media-title a:hover {
            color: #667eea;
            text-decoration: underline;
        }

        .media-meta {
            font-size: 0.875rem;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            line-height: 1.6;
        }

        .media-meta::before {
            content: '📄';
            font-size: 0.875rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: linear-gradient(135deg, #f9fafb 0%, #f1f5f9 100%);
            border-radius: 16px;
            margin: 2rem 0;
        }

        .empty-state-icon {
            font-size: 6rem;
            margin-bottom: 1.5rem;
            opacity: 0.4;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .empty-state h2 {
            font-size: 1.75rem;
            color: #1e293b;
            margin-bottom: 0.75rem;
            font-weight: 700;
        }

        .empty-state p {
            color: #64748b;
            font-size: 1.125rem;
            max-width: 500px;
            margin: 0 auto;
            line-height: 1.6;
        }

        /* Bulk Actions */
        .bulk-actions-bar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            display: none;
            align-items: center;
            gap: 1rem;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .bulk-actions-bar.visible {
            display: flex;
        }

        .bulk-actions-bar strong {
            flex: 1;
        }

        .checkbox-select {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            width: 24px;
            height: 24px;
            cursor: pointer;
            z-index: 2;
            accent-color: #667eea;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .media-hero-content {
                flex-direction: column;
                align-items: flex-start;
            }

            .media-hero-title h1 {
                font-size: 2rem;
            }

            .filters-grid {
                grid-template-columns: 1fr;
            }

            .media-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 1.25rem;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .media-card-info {
                padding: 1rem;
            }

            .action-btn {
                width: 42px;
                height: 42px;
                font-size: 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .media-content {
                padding: 1rem;
            }

            .media-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .media-card-image {
                aspect-ratio: 16/9;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .loading {
            animation: pulse 2s infinite;
        }

        /* Smooth Transitions */
        * {
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }
    </style>
</head>
<body class="admin-body">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="media-manager">
            <div class="media-container">
                <!-- Hero Section -->
                <div class="media-hero">
                    <div class="media-hero-content">
                        <div class="media-hero-title">
                            <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 1rem;">
                                <a href="products.php" style="text-decoration: none; color: #667eea; font-size: 1.5rem;">←</a>
                                <h1>🖼️ Media Manager</h1>
                            </div>
                            <p>Manage all your product images and media files</p>
                        </div>
                        <div class="media-hero-actions">
                            <button class="btn-hero btn-hero-primary" type="button" onclick="triggerMediaUpload()">
                                ⬆️ Upload Media
                            </button>
                            <button class="btn-hero btn-hero-secondary" type="button" onclick="optimizeAll()">
                                ⚡ Optimize All
                            </button>
                            <button class="btn-hero btn-hero-secondary" type="button" onclick="cleanupOrphaned()">
                                🗑️ Cleanup Orphaned
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Upload Section -->
                <div class="media-upload-card">
                    <div class="media-upload-header">
                        <h2>📤 Upload New Media</h2>
                        <span>Supports JPG, PNG, GIF, WebP, AVIF, SVG, BMP, HEIC/HEIF, JXL, ICO · Max size 10MB</span>
                    </div>
                    <div id="media-dropzone" class="upload-dropzone" onclick="triggerMediaUpload()">
                        <input type="file" id="media-upload-input" accept=".jpg,.jpeg,.png,.gif,.webp,.avif,.svg,.bmp,.heic,.heif,.jxl,.ico,image/*" multiple>
                        <div class="upload-icon">⬆️</div>
                        <p>Drag &amp; drop files here or click to browse</p>
                        <small>Uploaded files become available across product create and edit screens</small>
                    </div>
                    <div class="media-upload-actions">
                        <button class="btn-hero btn-hero-primary" type="button" onclick="triggerMediaUpload()">
                            Select Files
                        </button>
                        <span class="upload-status" id="media-upload-status">Ready to upload</span>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Total Images</div>
                                <div class="stat-value"><?= $totalImages ?></div>
                                <div class="stat-change">↑ Active</div>
                            </div>
                            <div class="stat-icon blue">📸</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Orphaned Files</div>
                                <div class="stat-value" style="color: <?= $totalOrphaned > 0 ? '#ef4444' : '#10b981' ?>;">
                                    <?= $totalOrphaned ?>
                                </div>
                                <div class="stat-change" style="color: <?= $totalOrphaned > 0 ? '#ef4444' : '#10b981' ?>;">
                                    <?= $totalOrphaned > 0 ? '⚠️ Needs cleanup' : '✓ All clean' ?>
                                </div>
                            </div>
                            <div class="stat-icon <?= $totalOrphaned > 0 ? 'red' : 'green' ?>">
                                <?= $totalOrphaned > 0 ? '⚠️' : '✓' ?>
                            </div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Storage Used</div>
                                <div class="stat-value" style="font-size: 1.75rem;"><?= formatBytes($totalSize) ?></div>
                                <div class="stat-change">💾 Disk space</div>
                            </div>
                            <div class="stat-icon blue">💾</div>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-header">
                            <div>
                                <div class="stat-label">Featured</div>
                                <div class="stat-value">
                                    <?= count(array_filter($images, fn($img) => $img['is_featured'])) ?>
                                </div>
                                <div class="stat-change">⭐ Highlighted</div>
                            </div>
                            <div class="stat-icon orange">⭐</div>
                        </div>
                    </div>
                </div>

                <!-- Tabs - Organized by Product -->
                <div class="media-tabs">
                    <div class="tabs-nav" style="overflow-x: auto; display: flex; gap: 0.5rem;">
                        <?php if (!empty($products)): ?>
                            <?php foreach ($products as $index => $product): ?>
                <button class="tab-button <?= $index === 0 ? 'active' : '' ?>" 
                    onclick="switchTab('product-<?= $product['id'] ?>', this)">
                                    <?= htmlspecialchars($product['name']) ?> 
                                    <span class="tab-badge"><?= $product['image_count'] ?></span>
                                </button>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ($totalOrphaned > 0): ?>
                            <button class="tab-button" onclick="switchTab('orphaned', this)">
                                🗑️ Orphaned <span class="tab-badge"><?= $totalOrphaned ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Filters -->
                <div class="media-filters">
                    <div class="filters-grid">
                        <div class="search-box">
                            <span class="search-icon">🔍</span>
                            <input type="text" id="search-input" class="search-input" placeholder="Search images, products...">
                        </div>
                        <select id="sort-select" class="filter-select" onchange="sortImages(this.value)">
                            <option value="newest">📅 Newest First</option>
                            <option value="oldest">📅 Oldest First</option>
                            <option value="name">🔤 Name</option>
                            <option value="size">💾 Size</option>
                        </select>
                        <select class="filter-select" id="format-filter" onchange="filterByFormat(this.value)">
                            <option value="">All Formats</option>
                            <option value="jpg">JPG/JPEG</option>
                            <option value="png">PNG</option>
                            <option value="webp">WebP</option>
                            <option value="avif">AVIF</option>
                            <option value="gif">GIF</option>
                            <option value="svg">SVG</option>
                            <option value="bmp">BMP</option>
                            <option value="heic">HEIC</option>
                            <option value="heif">HEIF</option>
                            <option value="jxl">JXL</option>
                            <option value="ico">ICO</option>
                        </select>
                    </div>
                </div>

                <!-- Bulk Actions -->
                <div class="bulk-actions-bar" id="bulk-actions">
                    <strong><span id="selected-count">0</span> items selected</strong>
                    <button class="btn-hero btn-hero-secondary" onclick="clearSelection()">Clear</button>
                    <button class="btn-hero btn-hero-primary" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);" onclick="bulkDelete()">
                        Delete Selected
                    </button>
                </div>

                <!-- Content -->
                <div class="media-content">
                    <!-- Product Tabs -->
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $index => $product): ?>
                            <div class="tab-content <?= $index === 0 ? 'active' : '' ?>" id="tab-product-<?= $product['id'] ?>">
                                <div class="product-header" style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0;">
                                    <h2 style="font-size: 1.5rem; color: #1e293b; margin-bottom: 0.5rem;">
                                        <?= htmlspecialchars($product['name']) ?>
                                    </h2>
                                    <p style="color: #64748b;">
                                        <?= $product['image_count'] ?> image<?= $product['image_count'] != 1 ? 's' : '' ?>
                                        • <a href="products.php?edit=<?= $product['id'] ?>" style="color: #667eea;">Edit Product</a>
                                    </p>
                                </div>
                                <div class="media-grid">
                                    <?php 
                                    $productImages = $imagesByProduct[$product['id']] ?? [];
                                    foreach ($productImages as $img):
                                        $extension = strtolower(pathinfo($img['image_path'] ?? '', PATHINFO_EXTENSION));
                                        $filePath = '../images/products/' . $img['image_path'];
                                        $fileExists = file_exists($filePath);
                                        $fileSizeBytes = $fileExists ? filesize($filePath) : 0;
                                        $createdAt = $img['created_at'] ?? null;
                                        if (!$createdAt && $fileExists) {
                                            $createdAt = date('Y-m-d H:i:s', filemtime($filePath));
                                        }
                                        $createdTimestamp = $createdAt ? strtotime($createdAt) : 0;
                                    ?>
                                        <div class="media-card" data-id="<?= $img['id'] ?>" data-filename="<?= $img['image_path'] ?>" data-format="<?= $extension ?>" data-size="<?= $fileSizeBytes ?>" data-created="<?= $createdTimestamp ?>">
                                            <div class="media-card-image">
                                                <input type="checkbox" class="checkbox-select" data-id="<?= $img['id'] ?>" 
                                                       onchange="updateBulkActions()">
                                                <img src="../images/products/<?= htmlspecialchars($img['image_path']) ?>" 
                                                     alt="<?= htmlspecialchars($img['alt_text'] ?? 'Product image') ?>"
                                                     loading="lazy">
                                                <div class="media-overlay">
                                                    <div class="media-badges">
                                                        <?php if ($img['is_featured']): ?>
                                                            <span class="badge badge-featured">★ Main Image</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="media-actions">
                                                        <button class="action-btn" onclick="viewImage('<?= $img['image_path'] ?>')" title="View Full Size">👁️</button>
                                                        <button class="action-btn" onclick="deleteImage(<?= $img['id'] ?>, '<?= $img['image_path'] ?>')" title="Delete">🗑️</button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="media-card-info">
                                                <div class="media-title">
                                                    <?= basename($img['image_path']) ?>
                                                </div>
                                                <div class="media-meta">
                                                    <?php
                                                    if ($fileExists) {
                                                        echo formatBytes($fileSizeBytes) . ' • ';
                                                    }
                                                    echo strtoupper(pathinfo($img['image_path'], PATHINFO_EXTENSION));
                                                    ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">🖼️</div>
                            <h2>No images found</h2>
                            <p>Upload some images in the Products section to see them here.</p>
                        </div>
                    <?php endif; ?>

                    <!-- Orphaned Tab -->
                    <?php if ($totalOrphaned > 0): ?>
                        <div class="tab-content" id="tab-orphaned">
                            <div class="product-header" style="margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #ef4444;">
                                <h2 style="font-size: 1.5rem; color: #ef4444; margin-bottom: 0.5rem;">
                                    🗑️ Orphaned Files
                                </h2>
                                <p style="color: #64748b;">
                                    <?= $totalOrphaned ?> file<?= $totalOrphaned != 1 ? 's' : '' ?> not associated with any product
                                </p>
                            </div>
                            <div class="media-grid">
                                <?php foreach ($orphanedFiles as $file): ?>
                                    <?php 
                                    $orphanExt = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                    $orphanPath = '../images/products/' . $file;
                                    $orphanExists = file_exists($orphanPath);
                                    $orphanSize = $orphanExists ? filesize($orphanPath) : 0;
                                    $orphanCreated = $orphanExists ? filemtime($orphanPath) : 0;
                                    ?>
                                    <div class="media-card" data-filename="<?= $file ?>" data-format="<?= $orphanExt ?>" data-size="<?= $orphanSize ?>" data-created="<?= $orphanCreated ?>">
                                        <div class="media-card-image">
                                            <img src="../images/products/<?= htmlspecialchars($file) ?>" 
                                                 alt="<?= htmlspecialchars($file) ?>"
                                                 loading="lazy">
                                            <div class="media-overlay">
                                                <div class="media-badges">
                                                    <span class="badge badge-orphaned">⚠️ Orphaned</span>
                                                </div>
                                                <div class="media-actions">
                                                    <button class="action-btn" onclick="viewImage('<?= $file ?>')" title="View">👁️</button>
                                                    <button class="action-btn" onclick="deleteOrphanedFile('<?= $file ?>')" title="Delete">🗑️</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="media-card-info">
                                            <div class="media-title"><?= basename($file) ?></div>
                                            <div class="media-meta">
                                                <?php
                                                if ($orphanExists) {
                                                    echo formatBytes($orphanSize) . ' • ';
                                                }
                                                echo strtoupper(pathinfo($file, PATHINFO_EXTENSION));
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <script>
        const mediaUploadInput = document.getElementById('media-upload-input');
        const mediaDropzone = document.getElementById('media-dropzone');
        const mediaUploadStatus = document.getElementById('media-upload-status');
        const mediaAllowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'bmp', 'heic', 'heif', 'jxl', 'ico'];
        const mediaMaxSize = 10 * 1024 * 1024; // 10MB

        function updateUploadStatus(message, state = '') {
            if (!mediaUploadStatus) {
                return;
            }
            mediaUploadStatus.textContent = message;
            mediaUploadStatus.classList.remove('error', 'success');
            if (state) {
                mediaUploadStatus.classList.add(state);
            }
        }

        function triggerMediaUpload() {
            mediaUploadInput?.click();
        }

        async function handleMediaFiles(files) {
            if (!files || files.length === 0) {
                return;
            }

            let uploadedCount = 0;
            const errors = [];

            for (const file of files) {
                const extension = file.name.split('.').pop().toLowerCase();

                if (!mediaAllowedExtensions.includes(extension)) {
                    errors.push(`${file.name}: Unsupported format`);
                    continue;
                }

                if (file.size > mediaMaxSize) {
                    errors.push(`${file.name}: File exceeds 10MB limit`);
                    continue;
                }

                updateUploadStatus(`Uploading ${file.name}...`);

                const formData = new FormData();
                formData.append('image', file);

                try {
                    const response = await fetch('upload_image.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        uploadedCount += 1;
                    } else {
                        errors.push(`${file.name}: ${result.error || result.message || 'Upload failed'}`);
                    }
                } catch (error) {
                    errors.push(`${file.name}: ${error.message}`);
                }
            }

            if (mediaUploadInput) {
                mediaUploadInput.value = '';
            }

            if (uploadedCount > 0) {
                updateUploadStatus(`Uploaded ${uploadedCount} file${uploadedCount === 1 ? '' : 's'} successfully`, 'success');
                setTimeout(() => {
                    location.reload();
                }, 1200);
            } else if (errors.length > 0) {
                updateUploadStatus('Upload failed', 'error');
                alert(errors.join('\n'));
            } else {
                updateUploadStatus('No valid files selected', 'error');
            }
        }

        mediaUploadInput?.addEventListener('change', (event) => {
            const files = Array.from(event.target.files || []);
            handleMediaFiles(files);
        });

        mediaDropzone?.addEventListener('dragover', (event) => {
            event.preventDefault();
            mediaDropzone.classList.add('dragover');
        });

        mediaDropzone?.addEventListener('dragleave', () => {
            mediaDropzone.classList.remove('dragover');
        });

        mediaDropzone?.addEventListener('drop', (event) => {
            event.preventDefault();
            mediaDropzone.classList.remove('dragover');
            const files = Array.from(event.dataTransfer?.files || []);
            handleMediaFiles(files);
        });

        updateUploadStatus('Ready to upload');

        function switchTab(tabName, button) {
            document.querySelectorAll('.tab-button').forEach(tab => tab.classList.remove('active'));
            if (button) {
                button.classList.add('active');
            }

            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            const target = document.getElementById('tab-' + tabName);
            if (target) {
                target.classList.add('active');
            }
        }

        function optimizeAll() {
            alert('Batch optimization is coming soon.');
        }

        function viewImage(filename) {
            window.open('../images/products/' + filename, '_blank');
        }

        async function deleteImage(id, filename) {
            if (!confirm('Delete this image? This action cannot be undone.')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                formData.append('filename', filename);

                const response = await fetch('media_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Failed to delete image');
                console.error(error);
            }
        }

        async function deleteOrphanedFile(filename) {
            if (!confirm('Delete this orphaned file? This action cannot be undone.')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'delete_orphaned');
                formData.append('filename', filename);

                const response = await fetch('media_actions.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Failed to delete file');
                console.error(error);
            }
        }

        async function cleanupOrphaned() {
            if (!confirm('Delete all orphaned files? This action cannot be undone.')) return;

            try {
                const response = await fetch('media_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'action=cleanup_orphaned'
                });

                console.log('Response status:', response.status);
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                console.log('Response text:', text);

                let result;
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    throw new Error('Invalid JSON response from server');
                }

                if (result.success) {
                    alert('✅ Deleted ' + result.deleted + ' orphaned files');
                    location.reload();
                } else {
                    alert('❌ Error: ' + (result.error || 'Unknown error'));
                }
            } catch (error) {
                console.error('Cleanup error:', error);
                alert('❌ Failed to cleanup orphaned files: ' + error.message);
            }
        }

        function updateBulkActions() {
            const selected = document.querySelectorAll('.checkbox-select:checked').length;
            const bulkActions = document.getElementById('bulk-actions');
            const selectedCount = document.getElementById('selected-count');

            selectedCount.textContent = selected;
            bulkActions.classList.toggle('visible', selected > 0);
        }

        function clearSelection() {
            document.querySelectorAll('.checkbox-select').forEach(cb => cb.checked = false);
            updateBulkActions();
        }

        async function bulkDelete() {
            const selected = Array.from(document.querySelectorAll('.checkbox-select:checked'))
                .map(cb => cb.dataset.id);

            if (!confirm(`Delete ${selected.length} images? This action cannot be undone.`)) return;

            try {
                const response = await fetch('media_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'bulk_delete',
                        ids: selected
                    })
                });

                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.error);
                }
            } catch (error) {
                alert('Failed to delete images');
                console.error(error);
            }
        }

        // Search functionality
        document.getElementById('search-input')?.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            document.querySelectorAll('.media-card').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Filter by format
        function filterByFormat(format) {
            document.querySelectorAll('.media-card').forEach(card => {
                if (!format) {
                    card.style.display = '';
                    return;
                }

                const ext = (card.dataset.format || card.dataset.filename?.split('.').pop() || '').toLowerCase();

                if (!ext) {
                    card.style.display = 'none';
                    return;
                }

                if (format === 'jpg') {
                    card.style.display = (ext === 'jpg' || ext === 'jpeg') ? '' : 'none';
                } else {
                    card.style.display = ext === format ? '' : 'none';
                }
            });
        }

        function sortImages(mode) {
            const activeGrid = document.querySelector('.tab-content.active .media-grid');
            if (!activeGrid) {
                return;
            }

            const cards = Array.from(activeGrid.querySelectorAll('.media-card'));
            if (cards.length === 0) {
                return;
            }

            const getSize = (card) => parseInt(card.dataset.size || '0', 10) || 0;
            const getCreated = (card) => parseInt(card.dataset.created || '0', 10) || 0;
            const getName = (card) => (card.dataset.filename || '').toLowerCase();

            let comparator;

            switch (mode) {
                case 'name':
                    comparator = (a, b) => getName(a).localeCompare(getName(b));
                    break;
                case 'size':
                    comparator = (a, b) => getSize(b) - getSize(a);
                    break;
                case 'oldest':
                    comparator = (a, b) => getCreated(a) - getCreated(b);
                    break;
                case 'newest':
                default:
                    comparator = (a, b) => getCreated(b) - getCreated(a);
                    break;
            }

            cards.sort(comparator).forEach(card => activeGrid.appendChild(card));
        }
    </script>
</body>
</html>
