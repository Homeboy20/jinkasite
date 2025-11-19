<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';
require_once 'includes/ai_helper.php';

// Require authentication
$auth = requireAuth('admin');

header('Content-Type: application/json');

// Get request data
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

$ai = new AIHelper();

try {
    switch ($action) {
        case 'optimize_description':
            $result = $ai->optimizeDescription(
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['category'] ?? ''
            );
            echo json_encode([
                'success' => true,
                'optimized_description' => $result
            ]);
            break;
            
        case 'generate_short_description':
            $result = $ai->generateShortDescription(
                $data['name'] ?? '',
                $data['description'] ?? ''
            );
            echo json_encode([
                'success' => true,
                'short_description' => $result
            ]);
            break;
            
        case 'extract_selling_points':
            $result = $ai->extractSellingPoints($data['product'] ?? []);
            echo json_encode([
                'success' => true,
                'selling_points' => $result
            ]);
            break;
            
        case 'generate_keywords':
            $result = $ai->generateSEOKeywords(
                $data['name'] ?? '',
                $data['description'] ?? '',
                $data['category'] ?? ''
            );
            echo json_encode([
                'success' => true,
                'keywords' => $result
            ]);
            break;
            
        case 'optimize_title':
            $result = $ai->optimizeProductTitle(
                $data['title'] ?? '',
                $data['specifications'] ?? []
            );
            echo json_encode([
                'success' => true,
                'optimized_title' => $result
            ]);
            break;
            
        case 'generate_features':
            $result = $ai->generateFeaturesFromSpecs(
                $data['name'] ?? '',
                $data['specifications'] ?? []
            );
            echo json_encode([
                'success' => true,
                'features' => $result
            ]);
            break;
            
        case 'full_optimization':
            // Complete AI-powered optimization
            $product = $data['product'] ?? [];
            
            $optimizations = [];
            
            // Optimize title
            if (!empty($product['name'])) {
                $optimizations['title'] = $ai->optimizeProductTitle(
                    $product['name'],
                    $product['specifications'] ?? []
                );
            }
            
            // Optimize description
            if (!empty($product['description'])) {
                $optimizations['description'] = $ai->optimizeDescription(
                    $product['name'] ?? '',
                    $product['description'],
                    $product['category'] ?? ''
                );
            }
            
            // Generate short description
            if (!empty($product['description'])) {
                $optimizations['short_description'] = $ai->generateShortDescription(
                    $product['name'] ?? '',
                    $optimizations['description'] ?? $product['description']
                );
            }
            
            // Extract selling points
            $optimizations['selling_points'] = $ai->extractSellingPoints($product);
            
            // Generate SEO keywords
            $optimizations['keywords'] = $ai->generateSEOKeywords(
                $product['name'] ?? '',
                $optimizations['description'] ?? $product['description'] ?? '',
                $product['category'] ?? ''
            );
            
            // Generate features from specs
            if (!empty($product['specifications'])) {
                $optimizations['features'] = $ai->generateFeaturesFromSpecs(
                    $product['name'] ?? '',
                    $product['specifications']
                );
            }
            
            echo json_encode([
                'success' => true,
                'optimizations' => $optimizations
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'AI optimization error: ' . $e->getMessage()
    ]);
}
