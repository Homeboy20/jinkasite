<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';
require_once 'includes/ai_helper.php';

// Require authentication
$auth = requireAuth('admin');

header('Content-Type: application/json');

// Get the Alibaba URL from the request
$data = json_decode(file_get_contents('php://input'), true);
$url = $data['url'] ?? '';
$aiOptimize = $data['ai_optimize'] ?? true; // Enable AI optimization by default
$downloadImages = $data['download_images'] ?? true; // Enable image download by default

if (empty($url)) {
    echo json_encode(['success' => false, 'message' => 'URL is required']);
    exit;
}

// Validate that it's an Alibaba URL
if (!preg_match('/alibaba\.com/i', $url)) {
    echo json_encode(['success' => false, 'message' => 'Please provide a valid Alibaba.com URL']);
    exit;
}

try {
    // Fetch the page content
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $html = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('Failed to fetch page. HTTP Code: ' . $httpCode);
    }
    
    if (empty($html)) {
        throw new Exception('No content received from URL');
    }
    
    // Parse the HTML to extract product information
    $product = extractProductData($html, $url);
    
    // Log what we extracted for debugging
    error_log("Extracted product name: " . ($product['name'] ?? 'EMPTY'));
    error_log("Extracted description length: " . strlen($product['description'] ?? ''));
    error_log("Extracted short_description length: " . strlen($product['short_description'] ?? ''));
    error_log("Extracted images count: " . count($product['images'] ?? []));
    error_log("Extracted specs count: " . count($product['specifications'] ?? []));
    error_log("Extracted features count: " . count($product['features'] ?? []));
    
    if (empty($product['name'])) {
        // Save HTML to file for debugging
        file_put_contents(__DIR__ . '/debug_alibaba.html', $html);
        throw new Exception('Could not extract product information from the page. HTML saved to debug_alibaba.html for analysis.');
    }
    
    // Download images if enabled
    if ($downloadImages && !empty($product['images'])) {
        $product['downloaded_images'] = downloadProductImages($product['images']);
        $product['primary_image'] = $product['downloaded_images'][0] ?? null;
    }
    
    // Apply AI optimization if enabled and only if we have a valid AI configuration
    // Skip AI if it would just return mock data
    if ($aiOptimize) {
        $ai = new AIHelper();
        
        // Check if AI is actually configured (not using mock data)
        $aiEnabled = $ai->getSetting('ai_enabled', '0');
        $hasAnyKey = !empty($ai->getSetting('ai_deepseek_key', '')) || 
                     !empty($ai->getSetting('ai_openai_key', '')) || 
                     !empty($ai->getSetting('ai_kimi_key', ''));
        
        if ($aiEnabled == '1' && $hasAnyKey) {
            $product = applyAIOptimization($product, $ai);
        } else {
            // Log that we're skipping AI optimization
            error_log("Skipping AI optimization - no API keys configured");
            $aiOptimize = false; // Mark as not optimized
        }
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product,
        'ai_optimized' => $aiOptimize,
        'images_downloaded' => $downloadImages && !empty($product['downloaded_images']),
        'debug' => [
            'raw_name' => $product['name'] ?? 'NONE',
            'has_ai_name' => isset($product['optimized_name']),
            'raw_description_length' => strlen($product['description'] ?? ''),
            'has_description' => !empty($product['description']),
            'optimized_description_length' => strlen($product['optimized_description'] ?? ''),
            'has_optimized_description' => isset($product['optimized_description']),
            'short_description_length' => strlen($product['short_description'] ?? ''),
            'has_short_description' => !empty($product['short_description']),
            'images_found' => count($product['images'] ?? []),
            'images_downloaded' => count($product['downloaded_images'] ?? []),
            'specs_found' => count($product['specifications'] ?? []),
            'features_found' => count($product['features'] ?? []),
            'has_seo_keywords' => isset($product['seo_keywords']),
            'has_selling_points' => isset($product['selling_points'])
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
}

function extractProductData($html, $url) {
    $product = [
        'name' => '',
        'description' => '',
        'short_description' => '',
        'specifications' => [],
        'features' => [],
        'price' => '',
        'images' => [],
        'source_url' => $url
    ];
    
    // Extract product title - Multiple patterns for better coverage
    $titlePatterns = [
        // Modern Alibaba patterns
        '/<h1[^>]*class="[^"]*title[^"]*"[^>]*>([^<]+)<\/h1>/i',
        '/<h1[^>]*>([^<]+)<\/h1>/i',
        // Data attributes
        '/<h1[^>]*data-spm-anchor-id[^>]*>([^<]+)<\/h1>/i',
        // Meta tags fallback
        '/<meta[^>]+property="og:title"[^>]+content="([^"]+)"/i',
        '/<title>([^<\|]+)/i'
    ];
    
    foreach ($titlePatterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $title = trim(strip_tags($matches[1]));
            // Clean up common suffixes
            $title = preg_replace('/\s*[-_|]\s*(Alibaba\.com|Alibaba|Buy).*/i', '', $title);
            if (!empty($title) && strlen($title) > 3) {
                $product['name'] = $title;
                break;
            }
        }
    }
    
    // Extract price - Multiple formats
    $pricePatterns = [
        // Modern Alibaba price formats
        '/US\s*\$\s*([\d,\.]+)\s*-\s*US\s*\$\s*([\d,\.]+)/i',
        '/\$\s*([\d,\.]+)\s*-\s*\$\s*([\d,\.]+)/i',
        '/US\s*\$\s*([\d,\.]+)/i',
        '/\$\s*([\d,\.]+)/i',
        '/Price:\s*US\s*\$\s*([\d,\.]+)/i'
    ];
    
    foreach ($pricePatterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            if (isset($matches[2])) {
                $product['price'] = 'US$ ' . $matches[1] . ' - $' . $matches[2];
            } else {
                $product['price'] = 'US$ ' . $matches[1];
            }
            break;
        }
    }
    
    // Extract description - Multiple patterns with enhanced extraction
    $descPatterns = [
        '/<div[^>]*class="[^"]*product-description[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<div[^>]*class="[^"]*description[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<div[^>]*data-role="[^"]*description[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<div[^>]*class="[^"]*detail-desc[^"]*"[^>]*>(.*?)<\/div>/is',
        '/<div[^>]*class="[^"]*product-info[^"]*"[^>]*>(.*?)<\/div>/is'
    ];
    
    foreach ($descPatterns as $pattern) {
        if (preg_match($pattern, $html, $matches)) {
            $desc = trim(strip_tags($matches[1], '<br><p>'));
            $desc = preg_replace('/<br\s*\/?>/i', "\n", $desc);
            $desc = preg_replace('/<p[^>]*>/i', "\n", $desc);
            $desc = preg_replace('/<\/p>/i', "\n", $desc);
            $desc = trim(strip_tags($desc));
            
            if (!empty($desc) && strlen($desc) > 50) {
                $product['description'] = $desc;
                $product['short_description'] = substr($desc, 0, 250) . '...';
                break;
            }
        }
    }
    
    // Fallback to meta description if no description found
    if (empty($product['description'])) {
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/i', $html, $matches)) {
            $product['description'] = trim($matches[1]);
            $product['short_description'] = substr($product['description'], 0, 250) . '...';
        } elseif (preg_match('/<meta[^>]+property=["\']og:description["\'][^>]+content=["\'](.*?)["\']/i', $html, $matches)) {
            $product['description'] = trim($matches[1]);
            $product['short_description'] = substr($product['description'], 0, 250) . '...';
        }
    }
    
    // If still no description, create one from product name and specs
    if (empty($product['description']) && !empty($product['name'])) {
        $product['description'] = "Product: " . $product['name'];
        if (!empty($product['specifications'])) {
            $product['description'] .= "\n\nSpecifications:\n";
            foreach (array_slice($product['specifications'], 0, 5) as $spec) {
                $product['description'] .= "- " . $spec['name'] . ": " . $spec['value'] . "\n";
            }
        }
        $product['short_description'] = substr($product['description'], 0, 250) . '...';
    }
    
    // Extract specifications from tables
    $specPatterns = [
        '/<tr[^>]*>\s*<td[^>]*>([^<]+)<\/td>\s*<td[^>]*>([^<]+)<\/td>\s*<\/tr>/i',
        '/<tr[^>]*>\s*<th[^>]*>([^<]+)<\/th>\s*<td[^>]*>([^<]+)<\/td>\s*<\/tr>/i'
    ];
    
    foreach ($specPatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = trim(strip_tags($match[1]));
                $value = trim(strip_tags($match[2]));
                
                // Clean up common labels
                $name = str_replace(':', '', $name);
                
                if (!empty($name) && !empty($value) && strlen($name) < 50 && strlen($value) < 200) {
                    $product['specifications'][] = [
                        'name' => $name,
                        'value' => $value
                    ];
                }
            }
            if (!empty($product['specifications'])) {
                break;
            }
        }
    }
    
    // Remove duplicate specifications
    $uniqueSpecs = [];
    $seenNames = [];
    foreach ($product['specifications'] as $spec) {
        if (!in_array($spec['name'], $seenNames)) {
            $uniqueSpecs[] = $spec;
            $seenNames[] = $spec['name'];
        }
    }
    $product['specifications'] = array_slice($uniqueSpecs, 0, 15);
    
    // Extract features from lists
    if (preg_match_all('/<li[^>]*>([^<]+)<\/li>/i', $html, $matches)) {
        foreach ($matches[1] as $feature) {
            $feature = trim(strip_tags($feature));
            // Filter out navigation items and short text
            if (!empty($feature) && 
                strlen($feature) > 10 && 
                strlen($feature) < 250 &&
                !preg_match('/(home|contact|about|login|sign|menu|more)/i', $feature)) {
                $product['features'][] = $feature;
            }
        }
    }
    
    // Limit and deduplicate features
    $product['features'] = array_slice(array_unique($product['features']), 0, 12);
    
    // Extract images - Multiple patterns
    $imagePatterns = [
        '/<img[^>]+class="[^"]*product[^"]*"[^>]+src="([^"]+)"/i',
        '/<img[^>]+data-src="([^"]+)"/i',
        '/<img[^>]+src="([^"]+)"/i'
    ];
    
    $foundImages = [];
    foreach ($imagePatterns as $pattern) {
        if (preg_match_all($pattern, $html, $matches)) {
            foreach ($matches[1] as $img) {
                // Filter valid product images
                if (preg_match('/^https?:\/\//i', $img) && 
                    !preg_match('/(logo|icon|button|arrow|banner|avatar)/i', $img) &&
                    preg_match('/\.(jpg|jpeg|png|webp)/i', $img)) {
                    $foundImages[] = $img;
                }
            }
        }
    }
    
    $product['images'] = array_slice(array_unique($foundImages), 0, 8);
    
    // Generate SKU from product name
    if (!empty($product['name'])) {
        $sku = strtoupper(preg_replace('/[^a-z0-9]+/i', '-', $product['name']));
        $sku = substr($sku, 0, 25) . '-' . substr(md5($url), 0, 4);
        $product['sku'] = $sku;
    }
    
    // Clean up the data
    $product['name'] = htmlspecialchars_decode($product['name']);
    $product['description'] = htmlspecialchars_decode($product['description']);
    $product['short_description'] = htmlspecialchars_decode($product['short_description']);
    
    return $product;
}

/**
 * Download product images to server
 */
function downloadProductImages($imageUrls) {
    $uploadDir = '../images/products/';
    
    // Create directory if it doesn't exist
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $downloadedImages = [];
    $maxImages = 5; // Limit to first 5 images
    $count = 0;
    
    foreach ($imageUrls as $imageUrl) {
        if ($count >= $maxImages) break;
        
        try {
            // Generate unique filename
            $extension = pathinfo(parse_url($imageUrl, PHP_URL_PATH), PATHINFO_EXTENSION);
            if (empty($extension) || !in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'webp'])) {
                $extension = 'jpg';
            }
            
            $filename = 'alibaba-' . time() . '-' . $count . '.' . $extension;
            $filepath = $uploadDir . $filename;
            
            // Download image
            $ch = curl_init($imageUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && !empty($imageData)) {
                // Verify it's actually an image
                $imageInfo = @getimagesizefromstring($imageData);
                if ($imageInfo !== false) {
                    file_put_contents($filepath, $imageData);
                    $downloadedImages[] = $filename;
                    $count++;
                }
            }
        } catch (Exception $e) {
            // Skip this image and continue
            continue;
        }
    }
    
    return $downloadedImages;
}

/**
 * Apply AI optimization to product data
 */
function applyAIOptimization($product, AIHelper $ai) {
    try {
        error_log("AI Optimization - Input data:");
        error_log("  Name: " . ($product['name'] ?? 'NONE'));
        error_log("  Description: " . strlen($product['description'] ?? '') . " chars");
        error_log("  Images: " . count($product['downloaded_images'] ?? $product['images'] ?? []));
        error_log("  Specs: " . count($product['specifications'] ?? []));
        error_log("  Features: " . count($product['features'] ?? []));
        
        // Optimize product title
        if (!empty($product['name'])) {
            $optimizedTitle = $ai->optimizeProductTitle(
                $product['name'],
                $product['specifications']
            );
            $product['optimized_name'] = $optimizedTitle;
            error_log("AI Optimization - Title optimized: " . $optimizedTitle);
        }
        
        // Optimize description - use existing or generate from specs/features
        $contextInfo = '';
        if (!empty($product['specifications'])) {
            $contextInfo .= "\nSpecifications:\n";
            foreach (array_slice($product['specifications'], 0, 10) as $spec) {
                $contextInfo .= "- {$spec['name']}: {$spec['value']}\n";
            }
        }
        if (!empty($product['features'])) {
            $contextInfo .= "\nFeatures:\n";
            foreach (array_slice($product['features'], 0, 8) as $feature) {
                $contextInfo .= "- {$feature}\n";
            }
        }
        if (!empty($product['downloaded_images']) || !empty($product['images'])) {
            $imageCount = count($product['downloaded_images'] ?? $product['images'] ?? []);
            $contextInfo .= "\nImages: {$imageCount} product images available\n";
        }
        
        if (!empty($product['description'])) {
            $optimizedDesc = $ai->optimizeDescription(
                $product['name'],
                $product['description'] . "\n" . $contextInfo,
                ''
            );
            $product['optimized_description'] = $optimizedDesc;
            error_log("AI Optimization - Description optimized: " . strlen($optimizedDesc) . " chars");
        } elseif (!empty($contextInfo)) {
            // Generate description from context if none exists
            $optimizedDesc = $ai->optimizeDescription(
                $product['name'],
                "Product details:\n" . $contextInfo,
                ''
            );
            $product['optimized_description'] = $optimizedDesc;
            $product['description'] = $optimizedDesc; // Also set as main description
            error_log("AI Optimization - Description generated from context: " . strlen($optimizedDesc) . " chars");
        }
        
        // Generate enhanced short description
        if (!empty($product['description'])) {
            $shortDesc = $ai->generateShortDescription(
                $product['name'],
                $product['optimized_description'] ?? $product['description']
            );
            $product['optimized_short_description'] = $shortDesc;
        }
        
        // Generate SEO keywords
        $keywords = $ai->generateSEOKeywords(
            $product['name'],
            $product['optimized_description'] ?? $product['description'],
            ''
        );
        $product['seo_keywords'] = $keywords;
        
        // Extract selling points
        $sellingPoints = $ai->extractSellingPoints($product);
        $product['selling_points'] = $sellingPoints;
        
        // Generate features from specifications if features are empty
        if (empty($product['features']) && !empty($product['specifications'])) {
            $features = $ai->generateFeaturesFromSpecs(
                $product['name'],
                $product['specifications']
            );
            $product['optimized_features'] = $features;
        } elseif (!empty($product['features'])) {
            // Enhance existing features
            $enhancedFeatures = [];
            foreach (array_slice($product['features'], 0, 8) as $feature) {
                $enhancedFeatures[] = $feature;
            }
            $product['optimized_features'] = $enhancedFeatures;
        }
        
        // Mark as AI optimized
        $product['ai_optimized'] = true;
        
    } catch (Exception $e) {
        // If AI optimization fails, continue with original data
        $product['ai_optimization_error'] = $e->getMessage();
    }
    
    return $product;
}
