<?php
/**
 * Enhanced Alibaba.com Product Import API
 * 
 * Features:
 * - Advanced web scraping with multiple selectors
 * - AI-powered product optimization
 * - Automatic image download and optimization
 * - Price analysis and suggestions
 * - SEO optimization
 * - Specification extraction
 * - Category mapping
 */

// Suppress all output and errors
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Start output buffering and clean any existing output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

define('JINKA_ACCESS', true);

// Clean any whitespace or output that might have been generated
ob_clean();

session_start();
require_once '../includes/config.php';
require_once 'includes/auth.php';

// Final cleanup and set headers
while (ob_get_level() > 1) {
    ob_end_clean();
}
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');

// Check authentication using the same auth system as admin pages
try {
    $auth = new AdminAuth();
    if (!$auth->isAuthenticated()) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized - Please log in']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Authentication error']);
    exit;
}

class AlibabaImporter {
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
    private $imageDir = '../images/products/';
    private $maxImages = 10;
    private $aiEnabled = true;
    
    public function __construct() {
        if (!is_dir($this->imageDir)) {
            mkdir($this->imageDir, 0755, true);
        }
    }
    
    /**
     * Main import function
     */
    public function importProduct($url) {
        try {
            // Validate URL
            if (!$this->validateUrl($url)) {
                return ['success' => false, 'error' => 'Invalid Alibaba URL'];
            }
            
            // Fetch product data
            $html = $this->fetchPage($url);
            if (!$html) {
                return ['success' => false, 'error' => 'Failed to fetch product page'];
            }
            
            // Extract product data
            $productData = $this->extractProductData($html, $url);
            
            // Download images
            if (!empty($productData['images'])) {
                $productData['downloaded_images'] = $this->downloadImages($productData['images']);
            }
            
            // AI optimization
            if ($this->aiEnabled && !empty($productData['name'])) {
                $productData = $this->applyAIOptimization($productData);
            }
            
            // Add metadata
            $productData['import_source'] = 'alibaba';
            $productData['import_url'] = $url;
            $productData['import_date'] = date('Y-m-d H:i:s');

            return [
                'success' => true,
                'product' => $productData,
                'message' => 'Product imported successfully'
            ];        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Import failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate Alibaba URL
     */
    private function validateUrl($url) {
        if (empty($url)) return false;
        
        $validDomains = [
            'alibaba.com',
            'www.alibaba.com',
            'm.alibaba.com'
        ];
        
        $parsedUrl = parse_url($url);
        if (!isset($parsedUrl['host'])) return false;
        
        foreach ($validDomains as $domain) {
            if (stripos($parsedUrl['host'], $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Fetch page content
     */
    private function fetchPage($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ],
            CURLOPT_ENCODING => 'gzip, deflate'
        ]);
        
        $html = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$html) {
            return false;
        }
        
        return $html;
    }
    
    /**
     * Extract product data from HTML with advanced techniques
     */
    private function extractProductData($html, $url) {
        $dom = new DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?>' . $html);
        $xpath = new DOMXPath($dom);
        
        $product = [
            'name' => '',
            'description' => '',
            'short_description' => '',
            'price' => 0,
            'moq' => 1,
            'images' => [],
            'specifications' => [],
            'features' => [],
            'category' => '',
            'supplier' => '',
            'extraction_method' => []
        ];
        
        // STRATEGY 1: Try JSON-LD structured data first (most reliable)
        $jsonLdData = $this->extractJsonLd($html);
        if ($jsonLdData) {
            $product = array_merge($product, $jsonLdData);
            $product['extraction_method'][] = 'json-ld';
        }
        
        // STRATEGY 2: Extract product name with multiple fallbacks
        if (empty($product['name'])) {
            $nameSelectors = [
                "//h1[@class='title']",
                "//h1[contains(@class, 'product-title')]",
                "//h1[contains(@class, 'title')]",
                "//div[@class='product-title']//h1",
                "//meta[@property='og:title']/@content",
                "//meta[@name='title']/@content",
                "//title"
            ];
            
            foreach ($nameSelectors as $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes->length > 0) {
                    $product['name'] = trim($nodes->item(0)->nodeValue ?? $nodes->item(0)->value ?? '');
                    if (!empty($product['name'])) {
                        $product['name'] = preg_replace('/\s*-\s*Alibaba\.com.*$/i', '', $product['name']);
                        $product['extraction_method'][] = 'xpath-name';
                        break;
                    }
                }
            }
        }
        
        // STRATEGY 3: Advanced image extraction with multiple methods
        $imageUrls = [];
        
        // Method 1: Check for JavaScript data objects
        if (preg_match('/window\.detailData\s*=\s*({.+?});/s', $html, $matches) ||
            preg_match('/var\s+imageList\s*=\s*(\[.+?\]);/s', $html, $matches)) {
            $jsonData = json_decode($matches[1], true);
            if ($jsonData) {
                $imageUrls = array_merge($imageUrls, $this->extractImagesFromJson($jsonData));
                $product['extraction_method'][] = 'js-data-images';
            }
        }
        
        // Method 2: XPath selectors with data attributes
        $imageSelectors = [
            "//img[@class='main-image']/@src",
            "//img[@data-src]/@data-src",
            "//img[@data-lazy]/@data-lazy",
            "//img[contains(@class, 'img-detail')]/@src",
            "//img[contains(@class, 'slider')]/@src",
            "//img[contains(@class, 'gallery')]/@src",
            "//img[contains(@class, 'product')]/@src",
            "//div[@class='image-view']//img/@src",
            "//div[contains(@class, 'gallery')]//img/@src",
            "//ul[contains(@class, 'image-list')]//img/@src",
            "//meta[@property='og:image']/@content",
            "//meta[@name='twitter:image']/@content"
        ];
        
        foreach ($imageSelectors as $selector) {
            $nodes = $xpath->query($selector);
            foreach ($nodes as $node) {
                $imgUrl = trim($node->value);
                if (!empty($imgUrl) && !in_array($imgUrl, $imageUrls)) {
                    // Skip small icons and placeholders
                    if (stripos($imgUrl, 'icon') !== false || 
                        stripos($imgUrl, 'placeholder') !== false ||
                        stripos($imgUrl, 'loading') !== false ||
                        preg_match('/_\d{1,2}x\d{1,2}\./', $imgUrl)) {
                        continue;
                    }
                    
                    // Convert to full URL
                    $imgUrl = $this->normalizeImageUrl($imgUrl);
                    
                    // Get high-res version
                    $imgUrl = $this->getHighResImageUrl($imgUrl);
                    
                    // Validate image URL
                    if ($this->isValidImageUrl($imgUrl)) {
                        $imageUrls[] = $imgUrl;
                        if (count($imageUrls) >= $this->maxImages) break 2;
                    }
                }
            }
        }
        
        // Method 3: Regex pattern matching for image URLs in HTML
        if (count($imageUrls) < 3) {
            preg_match_all('/https?:\/\/[^\s"\'>]+\.(?:jpg|jpeg|png|webp|avif)/i', $html, $matches);
            if (!empty($matches[0])) {
                foreach ($matches[0] as $url) {
                    if (!in_array($url, $imageUrls) && 
                        stripos($url, 'alibaba') !== false &&
                        !stripos($url, 'logo') &&
                        !stripos($url, 'icon')) {
                        $url = $this->getHighResImageUrl($url);
                        if ($this->isValidImageUrl($url)) {
                            $imageUrls[] = $url;
                            if (count($imageUrls) >= $this->maxImages) break;
                        }
                    }
                }
                $product['extraction_method'][] = 'regex-images';
            }
        }
        
        $product['images'] = array_unique($imageUrls);
        
        // Extract price
        $priceSelectors = [
            "//span[contains(@class, 'price')]",
            "//div[contains(@class, 'price')]//span",
            "//meta[@property='product:price:amount']/@content"
        ];
        
        foreach ($priceSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $priceText = trim($nodes->item(0)->nodeValue ?? $nodes->item(0)->value ?? '');
                preg_match('/[\d,\.]+/', $priceText, $matches);
                if (!empty($matches[0])) {
                    $product['price'] = (float)str_replace(',', '', $matches[0]);
                    break;
                }
            }
        }
        
        // Extract MOQ
        $moqSelectors = [
            "//span[contains(text(), 'Min. Order')]/..//span[@class='value']",
            "//div[contains(@class, 'moq')]",
            "//span[contains(text(), 'MOQ')]/..//span"
        ];
        
        foreach ($moqSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $moqText = trim($nodes->item(0)->nodeValue);
                preg_match('/\d+/', $moqText, $matches);
                if (!empty($matches[0])) {
                    $product['moq'] = (int)$matches[0];
                    break;
                }
            }
        }
        
        // Extract description
        $descSelectors = [
            "//div[contains(@class, 'description')]",
            "//div[contains(@class, 'detail-description')]",
            "//meta[@name='description']/@content",
            "//meta[@property='og:description']/@content"
        ];
        
        foreach ($descSelectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $descText = trim($nodes->item(0)->nodeValue ?? $nodes->item(0)->value ?? '');
                if (!empty($descText) && strlen($descText) > 50) {
                    $product['description'] = $this->cleanText($descText);
                    break;
                }
            }
        }
        
        // Extract specifications
        $specNodes = $xpath->query("//div[contains(@class, 'specification')]//tr | //table[contains(@class, 'spec')]//tr");
        foreach ($specNodes as $node) {
            $cells = $xpath->query(".//td", $node);
            if ($cells->length >= 2) {
                $key = trim($cells->item(0)->nodeValue);
                $value = trim($cells->item(1)->nodeValue);
                if (!empty($key) && !empty($value)) {
                    $product['specifications'][] = [
                        'name' => $key,
                        'value' => $value
                    ];
                }
            }
        }
        
        // Extract supplier info
        $supplierNodes = $xpath->query("//a[contains(@class, 'company-name')] | //div[contains(@class, 'supplier-name')]");
        if ($supplierNodes->length > 0) {
            $product['supplier'] = trim($supplierNodes->item(0)->nodeValue);
        }
        
        // ENHANCED AI LEARNING: Add comprehensive product analysis
        $product['ai_learning_data'] = $this->performComprehensiveProductAnalysis($html, $product);
        
        // AI ENHANCEMENT: Enrich extracted data with intelligent analysis
        $product = $this->enhanceProductWithAI($product, $html);

        return $product;
    }
    
    /**
     * Perform comprehensive product analysis for AI learning
     */
    private function performComprehensiveProductAnalysis($html, $product) {
        $analysis = [
            'content_depth' => $this->analyzeContentDepth($html),
            'technical_analysis' => $this->analyzeTechnicalContent($html),
            'market_intelligence' => $this->analyzeMarketPositioning($html),
            'product_classification' => $this->classifyProduct($product, $html),
            'quality_indicators' => $this->assessContentQuality($html),
            'extraction_confidence' => $this->calculateExtractionConfidence($product)
        ];
        
        return $analysis;
    }
    
    /**
     * Analyze content depth and richness
     */
    private function analyzeContentDepth($html) {
        $analysis = [
            'total_content_length' => strlen(strip_tags($html)),
            'word_count' => str_word_count(strip_tags($html)),
            'image_count' => substr_count($html, '<img'),
            'table_count' => substr_count($html, '<table'),
            'list_count' => substr_count($html, '<ul') + substr_count($html, '<ol'),
            'section_count' => substr_count($html, '<section') + substr_count($html, '<div'),
            'richness_score' => 0
        ];
        
        // Calculate richness score
        $analysis['richness_score'] = min(100, 
            ($analysis['word_count'] / 100) * 0.3 +
            ($analysis['image_count'] / 5) * 0.2 +
            ($analysis['table_count'] / 2) * 0.2 +
            ($analysis['list_count'] / 3) * 0.15 +
            ($analysis['section_count'] / 10) * 0.15
        );
        
        return $analysis;
    }
    
    /**
     * Analyze technical content and specifications
     */
    private function analyzeTechnicalContent($html) {
        $text = strtolower(strip_tags($html));
        
        $technical = [
            'has_specifications' => false,
            'has_certifications' => false,
            'has_measurements' => false,
            'has_materials' => false,
            'technical_vocabulary' => [],
            'complexity_level' => 'basic'
        ];
        
        // Check for specifications
        if (preg_match('/spec|parameter|dimension|capacity|voltage|power|weight/i', $text)) {
            $technical['has_specifications'] = true;
        }
        
        // Check for certifications
        if (preg_match('/\b(?:CE|ISO|FCC|RoHS|UL|FDA|SGS|CCC|ETL)\b/i', $text)) {
            $technical['has_certifications'] = true;
        }
        
        // Check for measurements
        if (preg_match('/\d+(?:\.\d+)?\s*(?:mm|cm|m|inch|ft|kg|g|lbs|°C|°F|V|A|W|Hz|RPM|PSI|MPa)/i', $text)) {
            $technical['has_measurements'] = true;
        }
        
        // Check for materials
        if (preg_match('/(?:steel|aluminum|plastic|PVC|ABS|nylon|polyester|cotton|leather|glass|ceramic)/i', $text)) {
            $technical['has_materials'] = true;
        }
        
        // Extract technical vocabulary
        preg_match_all('/\b[A-Z]{2,}(?:\d+)?\b/', $html, $matches);
        $technical['technical_vocabulary'] = array_unique(array_slice($matches[0], 0, 20));
        
        // Determine complexity level
        $complexityScore = 0;
        if ($technical['has_specifications']) $complexityScore += 25;
        if ($technical['has_certifications']) $complexityScore += 20;
        if ($technical['has_measurements']) $complexityScore += 20;
        if ($technical['has_materials']) $complexityScore += 15;
        if (count($technical['technical_vocabulary']) > 5) $complexityScore += 20;
        
        if ($complexityScore >= 80) $technical['complexity_level'] = 'advanced';
        elseif ($complexityScore >= 50) $technical['complexity_level'] = 'intermediate';
        
        return $technical;
    }
    
    /**
     * Analyze market positioning and target audience
     */
    private function analyzeMarketPositioning($html) {
        $text = strtolower(strip_tags($html));
        
        $positioning = [
            'target_market' => 'general',
            'price_positioning' => 'mid-range',
            'application_areas' => [],
            'competitive_advantages' => [],
            'market_signals' => []
        ];
        
        // Detect target market
        $markets = [
            'industrial' => ['industrial', 'factory', 'manufacturing', 'commercial', 'heavy duty'],
            'professional' => ['professional', 'office', 'business', 'commercial'],
            'consumer' => ['home', 'personal', 'diy', 'household', 'consumer'],
            'medical' => ['medical', 'hospital', 'healthcare', 'clinical'],
            'educational' => ['school', 'university', 'educational', 'training']
        ];
        
        $maxScore = 0;
        foreach ($markets as $market => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($text, $keyword);
            }
            if ($score > $maxScore) {
                $maxScore = $score;
                $positioning['target_market'] = $market;
            }
        }
        
        // Detect price positioning signals
        if (preg_match('/(?:premium|high.end|luxury|professional.grade)/i', $text)) {
            $positioning['price_positioning'] = 'premium';
        } elseif (preg_match('/(?:budget|affordable|economic|low.cost|cheap)/i', $text)) {
            $positioning['price_positioning'] = 'budget';
        }
        
        // Extract application areas
        preg_match_all('/(?:suitable for|used for|perfect for|ideal for|designed for)\s+([^.!?]+)/i', $html, $matches);
        $positioning['application_areas'] = array_slice($matches[1] ?? [], 0, 5);
        
        // Extract competitive advantages
        preg_match_all('/(?:advantage|benefit|feature|unique|special|exclusive):\s*([^.!?]+)/i', $html, $matches);
        $positioning['competitive_advantages'] = array_slice($matches[1] ?? [], 0, 5);
        
        return $positioning;
    }
    
    /**
     * Classify product using AI analysis
     */
    private function classifyProduct($product, $html) {
        $classification = [
            'primary_category' => 'general',
            'subcategory' => '',
            'product_type' => '',
            'confidence' => 0,
            'alternative_categories' => []
        ];
        
        $text = strtolower($product['name'] . ' ' . $product['description'] . ' ' . strip_tags($html));
        
        // Advanced classification categories
        $categories = [
            'plotter_cutting' => [
                'keywords' => ['plotter', 'cutting', 'vinyl', 'cutter', 'signmaking', 'plot', 'blade'],
                'weight' => 0,
                'subcategories' => ['vinyl plotter', 'cutting plotter', 'pen plotter', 'laser cutter']
            ],
            'printing_equipment' => [
                'keywords' => ['printer', 'printing', 'inkjet', 'laser printer', 'thermal', 'sublimation'],
                'weight' => 0,
                'subcategories' => ['inkjet printer', 'laser printer', 'thermal printer', '3d printer']
            ],
            'laminating_equipment' => [
                'keywords' => ['laminator', 'laminating', 'lamination', 'hot roll', 'cold roll'],
                'weight' => 0,
                'subcategories' => ['hot laminator', 'cold laminator', 'pouch laminator', 'roll laminator']
            ],
            'office_equipment' => [
                'keywords' => ['office', 'business', 'commercial', 'copier', 'scanner', 'binding'],
                'weight' => 0,
                'subcategories' => ['copier', 'scanner', 'binding machine', 'shredder']
            ],
            'industrial_machinery' => [
                'keywords' => ['industrial', 'machinery', 'equipment', 'motor', 'pump', 'conveyor'],
                'weight' => 0,
                'subcategories' => ['motor', 'pump', 'conveyor', 'automation equipment']
            ],
            'consumables' => [
                'keywords' => ['ink', 'cartridge', 'blade', 'vinyl', 'paper', 'film', 'supplies'],
                'weight' => 0,
                'subcategories' => ['ink cartridge', 'cutting blade', 'vinyl roll', 'transfer paper']
            ]
        ];
        
        // Calculate category weights
        foreach ($categories as $category => &$data) {
            foreach ($data['keywords'] as $keyword) {
                $count = substr_count($text, $keyword);
                $data['weight'] += $count * (strlen($keyword) > 5 ? 3 : 2); // Longer keywords get more weight
            }
        }
        
        // Find best match
        $maxWeight = 0;
        $bestCategory = 'general';
        foreach ($categories as $category => $data) {
            if ($data['weight'] > $maxWeight) {
                $maxWeight = $data['weight'];
                $bestCategory = $category;
            }
        }
        
        if ($maxWeight > 0) {
            $classification['primary_category'] = $bestCategory;
            $classification['confidence'] = min(95, $maxWeight * 10);
            
            // Try to identify subcategory
            foreach ($categories[$bestCategory]['subcategories'] as $subcat) {
                if (stripos($text, $subcat) !== false) {
                    $classification['subcategory'] = $subcat;
                    break;
                }
            }
        }
        
        // Get alternative categories
        arsort($categories);
        $alternatives = array_slice(array_keys($categories), 1, 3, true);
        foreach ($alternatives as $alt) {
            if ($categories[$alt]['weight'] > 0) {
                $classification['alternative_categories'][] = $alt;
            }
        }
        
        return $classification;
    }
    
    /**
     * Assess overall content quality
     */
    private function assessContentQuality($html) {
        $quality = [
            'completeness' => 0,
            'accuracy_indicators' => 0,
            'professional_presentation' => 0,
            'information_density' => 0,
            'overall_score' => 0
        ];
        
        $text = strip_tags($html);
        $wordCount = str_word_count($text);
        
        // Completeness (based on content length and structure)
        $quality['completeness'] = min(100, ($wordCount / 200) * 100);
        
        // Accuracy indicators (technical terms, measurements, certifications)
        $accuracyScore = 0;
        if (preg_match('/\d+(?:\.\d+)?\s*(?:mm|cm|inch|kg|lbs|°C|°F|V|A|W)/i', $text)) $accuracyScore += 30;
        if (preg_match('/\b(?:CE|ISO|FCC|RoHS|UL|FDA|SGS)\b/i', $text)) $accuracyScore += 25;
        if (preg_match('/model|part.number|sku|specification/i', $text)) $accuracyScore += 20;
        if (substr_count($html, '<table') > 0) $accuracyScore += 25;
        $quality['accuracy_indicators'] = min(100, $accuracyScore);
        
        // Professional presentation (proper formatting, images, structure)
        $presentationScore = 0;
        if (substr_count($html, '<img') >= 3) $presentationScore += 25;
        if (substr_count($html, '<ul') + substr_count($html, '<ol') >= 2) $presentationScore += 20;
        if (substr_count($html, '<h1') + substr_count($html, '<h2') + substr_count($html, '<h3') >= 2) $presentationScore += 25;
        if (preg_match('/<p[^>]*>/', $html)) $presentationScore += 15;
        if (substr_count($html, '<div') >= 5) $presentationScore += 15;
        $quality['professional_presentation'] = min(100, $presentationScore);
        
        // Information density
        $quality['information_density'] = min(100, ($wordCount / strlen($html)) * 1000);
        
        // Overall score
        $quality['overall_score'] = round(
            ($quality['completeness'] * 0.3) +
            ($quality['accuracy_indicators'] * 0.3) +
            ($quality['professional_presentation'] * 0.25) +
            ($quality['information_density'] * 0.15)
        );
        
        return $quality;
    }
    
    /**
     * Calculate confidence in extraction accuracy
     */
    private function calculateExtractionConfidence($product) {
        $confidence = [
            'name_confidence' => 0,
            'description_confidence' => 0,
            'specification_confidence' => 0,
            'image_confidence' => 0,
            'overall_confidence' => 0
        ];
        
        // Name confidence
        if (!empty($product['name'])) {
            $nameLength = strlen($product['name']);
            $confidence['name_confidence'] = min(100, max(0, ($nameLength - 10) * 5));
        }
        
        // Description confidence
        if (!empty($product['description'])) {
            $descLength = strlen($product['description']);
            $confidence['description_confidence'] = min(100, max(0, ($descLength - 50) / 5));
        }
        
        // Specification confidence
        $specCount = count($product['specifications'] ?? []);
        $confidence['specification_confidence'] = min(100, $specCount * 15);
        
        // Image confidence
        $imageCount = count($product['images'] ?? []);
        $confidence['image_confidence'] = min(100, $imageCount * 20);
        
        // Overall confidence
        $confidence['overall_confidence'] = round(
            ($confidence['name_confidence'] * 0.3) +
            ($confidence['description_confidence'] * 0.3) +
            ($confidence['specification_confidence'] * 0.2) +
            ($confidence['image_confidence'] * 0.2)
        );
        
        return $confidence;
    }
    
    /**
     * Enhance product data using AI insights
     */
    private function enhanceProductWithAI($product, $html) {
        // Enhance product name if needed
        if (!empty($product['ai_learning_data']['product_classification'])) {
            $product = $this->enhanceProductName($product);
        }
        
        // Generate comprehensive description using AI analysis
        $product = $this->generateComprehensiveDescription($product, $html);
        
        // Enhance specifications with AI context
        $product = $this->enhanceSpecifications($product);
        
        // Add AI-generated features and benefits
        $product = $this->generateFeaturesAndBenefits($product, $html);
        
        // Add market intelligence insights
        $product = $this->addMarketInsights($product);
        
        return $product;
    }
    
    /**
     * Enhance product name using AI classification
     */
    private function enhanceProductName($product) {
        if (empty($product['name'])) return $product;
        
        $classification = $product['ai_learning_data']['product_classification'];
        $originalName = $product['name'];
        
        // Clean and enhance the name
        $enhancedName = $originalName;
        
        // Remove supplier noise
        $enhancedName = preg_replace('/\b(?:factory|wholesale|supplier|manufacturer|company|ltd|inc|corp)\b/i', '', $enhancedName);
        
        // Add descriptive context if confidence is high
        if ($classification['confidence'] > 80 && !empty($classification['subcategory'])) {
            $subcategory = ucwords($classification['subcategory']);
            if (stripos($enhancedName, $subcategory) === false) {
                // Only add if it's not already mentioned
                $enhancedName = $subcategory . ' - ' . $enhancedName;
            }
        }
        
        // Clean up spacing and format
        $enhancedName = preg_replace('/\s+/', ' ', $enhancedName);
        $enhancedName = trim($enhancedName, ' -');
        
        $product['enhanced_name'] = $enhancedName;
        $product['name_enhancement'] = [
            'original' => $originalName,
            'enhanced' => $enhancedName,
            'changes_made' => ['supplier_noise_removed', 'category_context_added'],
            'enhancement_confidence' => $classification['confidence']
        ];
        
        return $product;
    }
    
    /**
     * Generate comprehensive description using AI analysis
     */
    private function generateComprehensiveDescription($product, $html) {
        $learning = $product['ai_learning_data'];
        $classification = $learning['product_classification'];
        $technical = $learning['technical_analysis'];
        $positioning = $learning['market_intelligence'];
        
        // Build comprehensive description
        $description = '';
        
        // 1. Product introduction based on classification
        if ($classification['confidence'] > 70) {
            $productType = str_replace('_', ' ', $classification['primary_category']);
            $description .= "This " . ucwords($productType);
            
            if (!empty($classification['subcategory'])) {
                $description .= " (" . ucwords($classification['subcategory']) . ")";
            }
            
            $description .= " is designed for " . $positioning['target_market'] . " applications. ";
        }
        
        // 2. Add original description if available
        if (!empty($product['description'])) {
            $cleanDesc = $this->cleanText($product['description']);
            if (strlen($cleanDesc) > 20) {
                $description .= $cleanDesc . " ";
            }
        }
        
        // 3. Add technical highlights
        if ($technical['complexity_level'] !== 'basic') {
            $description .= "\n\nTechnical Features:\n";
            
            if ($technical['has_specifications']) {
                $description .= "• Detailed technical specifications included\n";
            }
            if ($technical['has_certifications']) {
                $description .= "• Quality certifications and compliance standards\n";
            }
            if ($technical['has_measurements']) {
                $description .= "• Precise measurements and dimensions provided\n";
            }
            if ($technical['has_materials']) {
                $description .= "• High-quality materials and construction\n";
            }
        }
        
        // 4. Add application areas
        if (!empty($positioning['application_areas'])) {
            $description .= "\n\nSuitable for: " . implode(', ', array_slice($positioning['application_areas'], 0, 3)) . "\n";
        }
        
        // 5. Add competitive advantages
        if (!empty($positioning['competitive_advantages'])) {
            $description .= "\nKey Benefits: " . implode(', ', array_slice($positioning['competitive_advantages'], 0, 3)) . "\n";
        }
        
        // 6. Add quality indicators
        $qualityScore = $learning['quality_indicators']['overall_score'];
        if ($qualityScore > 80) {
            $description .= "\nThis product listing demonstrates high-quality documentation and comprehensive product information.";
        }
        
        $product['ai_generated_description'] = trim($description);
        $product['description_metadata'] = [
            'generation_method' => 'ai_comprehensive',
            'confidence' => $learning['extraction_confidence']['overall_confidence'],
            'content_sources' => ['classification', 'technical_analysis', 'market_positioning'],
            'enhancement_level' => $technical['complexity_level']
        ];
        
        return $product;
    }
    
    /**
     * Enhance specifications with AI context
     */
    private function enhanceSpecifications($product) {
        if (empty($product['specifications'])) return $product;
        
        $technical = $product['ai_learning_data']['technical_analysis'];
        $enhancedSpecs = [];
        
        foreach ($product['specifications'] as $spec) {
            $enhanced = $spec;
            
            // Add context and categorization
            $enhanced['category'] = $this->categorizeSpecification($spec['name']);
            $enhanced['importance'] = $this->assessSpecificationImportance($spec['name'], $spec['value']);
            $enhanced['unit_type'] = $this->identifyUnitType($spec['value']);
            
            // Add formatting suggestions
            $enhanced['formatted_value'] = $this->formatSpecificationValue($spec['value']);
            
            $enhancedSpecs[] = $enhanced;
        }
        
        // Sort by importance
        usort($enhancedSpecs, function($a, $b) {
            return $b['importance'] - $a['importance'];
        });
        
        $product['enhanced_specifications'] = $enhancedSpecs;
        $product['specification_metadata'] = [
            'total_count' => count($enhancedSpecs),
            'high_importance_count' => count(array_filter($enhancedSpecs, function($s) { return $s['importance'] >= 8; })),
            'technical_complexity' => $technical['complexity_level'],
            'categories_covered' => array_unique(array_column($enhancedSpecs, 'category'))
        ];
        
        return $product;
    }
    
    /**
     * Categorize specification type
     */
    private function categorizeSpecification($name) {
        $categories = [
            'dimensions' => ['size', 'dimension', 'length', 'width', 'height', 'diameter', 'thickness'],
            'power' => ['power', 'voltage', 'current', 'wattage', 'frequency', 'amperage'],
            'performance' => ['speed', 'capacity', 'throughput', 'efficiency', 'accuracy', 'precision'],
            'materials' => ['material', 'construction', 'finish', 'coating', 'surface'],
            'weight' => ['weight', 'mass'],
            'temperature' => ['temperature', 'thermal', 'heat', 'cooling'],
            'pressure' => ['pressure', 'psi', 'mpa', 'bar'],
            'certification' => ['certification', 'standard', 'compliance', 'approval'],
            'connectivity' => ['interface', 'connection', 'port', 'communication', 'protocol'],
            'general' => ['other']
        ];
        
        $nameLower = strtolower($name);
        
        foreach ($categories as $category => $keywords) {
            foreach ($keywords as $keyword) {
                if (stripos($nameLower, $keyword) !== false) {
                    return $category;
                }
            }
        }
        
        return 'general';
    }
    
    /**
     * Assess specification importance
     */
    private function assessSpecificationImportance($name, $value) {
        $importance = 5; // Base importance
        
        $highImportanceKeywords = ['size', 'power', 'voltage', 'capacity', 'speed', 'accuracy', 'material'];
        $mediumImportanceKeywords = ['weight', 'color', 'finish', 'brand', 'model'];
        
        $nameLower = strtolower($name);
        
        foreach ($highImportanceKeywords as $keyword) {
            if (stripos($nameLower, $keyword) !== false) {
                $importance = 9;
                break;
            }
        }
        
        foreach ($mediumImportanceKeywords as $keyword) {
            if (stripos($nameLower, $keyword) !== false) {
                $importance = 7;
                break;
            }
        }
        
        // Boost importance if value contains numbers (measurable)
        if (preg_match('/\d/', $value)) {
            $importance += 1;
        }
        
        // Boost importance if value contains units
        if (preg_match('/(?:mm|cm|m|inch|ft|kg|g|lbs|°C|°F|V|A|W|Hz|RPM|PSI|MPa)/i', $value)) {
            $importance += 2;
        }
        
        return min(10, $importance);
    }
    
    /**
     * Identify unit type in specification value
     */
    private function identifyUnitType($value) {
        $unitTypes = [
            'length' => '/(?:mm|cm|m|inch|ft)/i',
            'weight' => '/(?:kg|g|lbs|oz)/i',
            'temperature' => '/(?:°C|°F|celsius|fahrenheit)/i',
            'electrical' => '/(?:V|A|W|Hz|kW|mA)/i',
            'pressure' => '/(?:PSI|MPa|bar|kPa)/i',
            'speed' => '/(?:RPM|Hz|m\/s|km\/h|mph)/i',
            'percentage' => '/(?:%|percent)/i',
            'time' => '/(?:seconds?|minutes?|hours?|days?)/i'
        ];
        
        foreach ($unitTypes as $type => $pattern) {
            if (preg_match($pattern, $value)) {
                return $type;
            }
        }
        
        return 'text';
    }
    
    /**
     * Format specification value for better presentation
     */
    private function formatSpecificationValue($value) {
        // Add spaces before units
        $formatted = preg_replace('/(\d)([A-Za-z])/', '$1 $2', $value);
        
        // Standardize common units
        $unitReplacements = [
            '/\bmm\b/i' => 'mm',
            '/\bcm\b/i' => 'cm',
            '/\bkg\b/i' => 'kg',
            '/\bw\b/i' => 'W',
            '/\bv\b/i' => 'V',
            '/\ba\b/i' => 'A'
        ];
        
        foreach ($unitReplacements as $pattern => $replacement) {
            $formatted = preg_replace($pattern, $replacement, $formatted);
        }
        
        return $formatted;
    }
    
    /**
     * Generate features and benefits using AI analysis
     */
    private function generateFeaturesAndBenefits($product, $html) {
        $learning = $product['ai_learning_data'];
        $features = [];
        $benefits = [];
        
        // Extract features from technical analysis
        if ($learning['technical_analysis']['has_specifications']) {
            $features[] = [
                'title' => 'Detailed Specifications',
                'description' => 'Comprehensive technical specifications provided for informed decision making',
                'category' => 'technical',
                'confidence' => 90
            ];
        }
        
        if ($learning['technical_analysis']['has_certifications']) {
            $features[] = [
                'title' => 'Quality Certifications',
                'description' => 'Meets international quality and safety standards',
                'category' => 'quality',
                'confidence' => 95
            ];
        }
        
        if ($learning['technical_analysis']['has_measurements']) {
            $features[] = [
                'title' => 'Precise Dimensions',
                'description' => 'Accurate measurements provided for perfect fit and integration',
                'category' => 'technical',
                'confidence' => 85
            ];
        }
        
        // Extract features from classification
        $classification = $learning['product_classification'];
        if ($classification['confidence'] > 80) {
            $category = str_replace('_', ' ', $classification['primary_category']);
            $features[] = [
                'title' => ucwords($category) . ' Expertise',
                'description' => "Specialized design for " . $category . " applications",
                'category' => 'specialization',
                'confidence' => $classification['confidence']
            ];
        }
        
        // Generate benefits from market positioning
        $positioning = $learning['market_intelligence'];
        if ($positioning['target_market'] !== 'general') {
            $benefits[] = [
                'title' => ucfirst($positioning['target_market']) . ' Grade Quality',
                'description' => "Designed specifically for " . $positioning['target_market'] . " use requirements",
                'category' => 'market_fit',
                'confidence' => 80
            ];
        }
        
        if ($positioning['price_positioning'] === 'premium') {
            $benefits[] = [
                'title' => 'Premium Quality',
                'description' => 'High-end materials and construction for superior performance',
                'category' => 'quality',
                'confidence' => 85
            ];
        }
        
        // Add quality-based benefits
        $qualityScore = $learning['quality_indicators']['overall_score'];
        if ($qualityScore > 80) {
            $benefits[] = [
                'title' => 'Well-Documented Product',
                'description' => 'Comprehensive product information ensures you know exactly what you\'re purchasing',
                'category' => 'confidence',
                'confidence' => min(95, $qualityScore)
            ];
        }
        
        $product['ai_generated_features'] = $features;
        $product['ai_generated_benefits'] = $benefits;
        
        return $product;
    }
    
    /**
     * Add market intelligence insights
     */
    private function addMarketInsights($product) {
        $learning = $product['ai_learning_data'];
        $positioning = $learning['market_intelligence'];
        $classification = $learning['product_classification'];
        
        $insights = [
            'market_analysis' => [
                'target_market' => $positioning['target_market'],
                'price_positioning' => $positioning['price_positioning'],
                'competitive_landscape' => $this->analyzeCompetitiveLandscape($classification),
                'market_opportunity' => $this->assessMarketOpportunity($positioning, $classification)
            ],
            'business_intelligence' => [
                'demand_indicators' => $this->analyzeDemandIndicators($product),
                'profit_potential' => $this->assessProfitPotential($positioning, $learning['quality_indicators']),
                'risk_factors' => $this->identifyRiskFactors($learning),
                'growth_potential' => $this->assessGrowthPotential($classification, $positioning)
            ],
            'optimization_suggestions' => [
                'pricing_strategy' => $this->suggestPricingStrategy($positioning, $classification),
                'marketing_angles' => $this->suggestMarketingAngles($learning),
                'target_keywords' => $this->suggestTargetKeywords($product, $classification),
                'content_improvements' => $this->suggestContentImprovements($learning)
            ]
        ];
        
        $product['market_insights'] = $insights;
        
        return $product;
    }
    
    /**
     * Analyze competitive landscape
     */
    private function analyzeCompetitiveLandscape($classification) {
        $category = $classification['primary_category'];
        
        $landscapes = [
            'plotter_cutting' => [
                'market_size' => 'medium',
                'competition_level' => 'moderate',
                'key_players' => ['Roland', 'Graphtec', 'Silhouette'],
                'differentiation_factors' => ['cutting_precision', 'software_compatibility', 'material_versatility']
            ],
            'printing_equipment' => [
                'market_size' => 'large',
                'competition_level' => 'high',
                'key_players' => ['HP', 'Canon', 'Epson', 'Roland'],
                'differentiation_factors' => ['print_quality', 'speed', 'cost_per_print', 'format_support']
            ],
            'laminating_equipment' => [
                'market_size' => 'small',
                'competition_level' => 'low',
                'key_players' => ['GBC', 'Fellowes', 'Royal Sovereign'],
                'differentiation_factors' => ['lamination_quality', 'speed', 'format_support', 'ease_of_use']
            ],
            'office_equipment' => [
                'market_size' => 'large',
                'competition_level' => 'high',
                'key_players' => ['Canon', 'HP', 'Brother', 'Kyocera'],
                'differentiation_factors' => ['reliability', 'cost_efficiency', 'connectivity', 'multifunctionality']
            ],
            'industrial_machinery' => [
                'market_size' => 'medium',
                'competition_level' => 'moderate',
                'key_players' => ['Various by subcategory'],
                'differentiation_factors' => ['precision', 'durability', 'automation', 'customization']
            ],
            'consumables' => [
                'market_size' => 'large',
                'competition_level' => 'high',
                'key_players' => ['3M', 'Avery', 'Brand specific'],
                'differentiation_factors' => ['quality', 'compatibility', 'price', 'availability']
            ]
        ];
        
        return $landscapes[$category] ?? $landscapes['office_equipment'];
    }
    
    /**
     * Assess market opportunity
     */
    private function assessMarketOpportunity($positioning, $classification) {
        $factors = [];
        
        // Market size factor
        $marketSize = $this->analyzeCompetitiveLandscape($classification)['market_size'];
        $factors['market_size_score'] = $marketSize === 'large' ? 8 : ($marketSize === 'medium' ? 6 : 4);
        
        // Target market specificity
        $targetMarket = $positioning['target_market'];
        $factors['target_specificity_score'] = $targetMarket === 'professional' ? 8 : 
                                              ($targetMarket === 'commercial' ? 7 : 5);
        
        // Price positioning
        $pricePos = $positioning['price_positioning'];
        $factors['price_opportunity_score'] = $pricePos === 'premium' ? 7 : 
                                             ($pricePos === 'mid_range' ? 8 : 6);
        
        // Competition level
        $competitionLevel = $this->analyzeCompetitiveLandscape($classification)['competition_level'];
        $factors['competition_score'] = $competitionLevel === 'low' ? 9 : 
                                       ($competitionLevel === 'moderate' ? 7 : 5);
        
        $factors['overall_opportunity_score'] = round(array_sum($factors) / count($factors));
        
        return $factors;
    }
    
    /**
     * Analyze demand indicators
     */
    private function analyzeDemandIndicators($product) {
        $indicators = [];
        
        // Product name demand signals
        $name = strtolower($product['name'] ?? '');
        $indicators['trend_keywords'] = $this->countTrendKeywords($name);
        
        // Image count as popularity indicator
        $imageCount = count($product['images'] ?? []);
        $indicators['visual_appeal_score'] = min(10, $imageCount * 2);
        
        // Specification detail as professional demand
        $specCount = count($product['specifications'] ?? []);
        $indicators['professional_demand_score'] = min(10, $specCount);
        
        // Description length as information demand
        $descLength = strlen($product['description'] ?? '');
        $indicators['information_demand_score'] = min(10, $descLength / 50);
        
        $indicators['overall_demand_score'] = round(
            ($indicators['visual_appeal_score'] * 0.3) +
            ($indicators['professional_demand_score'] * 0.4) +
            ($indicators['information_demand_score'] * 0.3)
        );
        
        return $indicators;
    }
    
    /**
     * Count trending keywords in product name
     */
    private function countTrendKeywords($text) {
        $trendingKeywords = [
            'digital', 'smart', 'automated', 'precision', 'professional', 
            'commercial', 'industrial', 'high-speed', 'multi-function',
            'wireless', 'portable', 'compact', 'eco-friendly', 'energy-efficient'
        ];
        
        $count = 0;
        foreach ($trendingKeywords as $keyword) {
            if (stripos($text, $keyword) !== false) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Assess profit potential
     */
    private function assessProfitPotential($positioning, $qualityIndicators) {
        $factors = [];
        
        // Price positioning factor
        $pricePos = $positioning['price_positioning'];
        $factors['price_margin_potential'] = $pricePos === 'premium' ? 9 : 
                                           ($pricePos === 'mid_range' ? 7 : 4);
        
        // Quality factor
        $qualityScore = $qualityIndicators['overall_score'];
        $factors['quality_premium'] = round($qualityScore / 10);
        
        // Target market factor
        $targetMarket = $positioning['target_market'];
        $factors['market_premium'] = $targetMarket === 'professional' ? 8 : 
                                   ($targetMarket === 'commercial' ? 6 : 4);
        
        // Competition factor (less competition = higher potential)
        $factors['competition_advantage'] = 7; // Default moderate
        
        $factors['overall_profit_potential'] = round(array_sum($factors) / count($factors));
        
        return $factors;
    }
    
    /**
     * Identify risk factors
     */
    private function identifyRiskFactors($learning) {
        $risks = [];
        
        // Quality risks
        $qualityScore = $learning['quality_indicators']['overall_score'];
        if ($qualityScore < 60) {
            $risks[] = [
                'type' => 'quality',
                'level' => 'high',
                'description' => 'Low quality score may indicate poor product documentation or reliability'
            ];
        }
        
        // Technical complexity risks
        $techLevel = $learning['technical_analysis']['complexity_level'];
        if ($techLevel === 'advanced') {
            $risks[] = [
                'type' => 'complexity',
                'level' => 'medium',
                'description' => 'High technical complexity may limit target market size'
            ];
        }
        
        // Market positioning risks
        $positioning = $learning['market_intelligence']['price_positioning'];
        if ($positioning === 'budget') {
            $risks[] = [
                'type' => 'pricing',
                'level' => 'medium',
                'description' => 'Budget pricing may indicate margin pressure or quality concerns'
            ];
        }
        
        // Confidence risks
        $confidence = $learning['extraction_confidence']['overall_confidence'];
        if ($confidence < 70) {
            $risks[] = [
                'type' => 'data_quality',
                'level' => 'medium',
                'description' => 'Low extraction confidence may indicate incomplete product information'
            ];
        }
        
        return $risks;
    }
    
    /**
     * Assess growth potential
     */
    private function assessGrowthPotential($classification, $positioning) {
        $factors = [];
        
        // Category growth potential
        $category = $classification['primary_category'];
        $categoryGrowth = [
            'plotter_cutting' => 7,
            'printing_equipment' => 6,
            'laminating_equipment' => 5,
            'office_equipment' => 6,
            'industrial_machinery' => 8,
            'consumables' => 9
        ];
        
        $factors['category_growth'] = $categoryGrowth[$category] ?? 6;
        
        // Market positioning growth
        $targetMarket = $positioning['target_market'];
        $factors['market_growth'] = $targetMarket === 'professional' ? 8 : 
                                  ($targetMarket === 'commercial' ? 7 : 5);
        
        // Innovation indicators
        $factors['innovation_score'] = $this->assessInnovationLevel($classification);
        
        $factors['overall_growth_potential'] = round(array_sum($factors) / count($factors));
        
        return $factors;
    }
    
    /**
     * Assess innovation level
     */
    private function assessInnovationLevel($classification) {
        $innovativeKeywords = ['smart', 'digital', 'automated', 'ai', 'iot', 'wireless', 'cloud'];
        $subcategory = strtolower($classification['subcategory'] ?? '');
        
        $score = 5; // Base score
        foreach ($innovativeKeywords as $keyword) {
            if (stripos($subcategory, $keyword) !== false) {
                $score += 1;
            }
        }
        
        return min(10, $score);
    }
    
    /**
     * Suggest pricing strategy
     */
    private function suggestPricingStrategy($positioning, $classification) {
        $currentPos = $positioning['price_positioning'];
        $category = $classification['primary_category'];
        $confidence = $classification['confidence'];
        
        $strategies = [];
        
        if ($confidence > 80) {
            if ($currentPos === 'budget' && $category === 'professional') {
                $strategies[] = [
                    'strategy' => 'value_positioning',
                    'description' => 'Consider premium pricing for professional market',
                    'risk_level' => 'medium'
                ];
            } elseif ($currentPos === 'premium' && $category === 'consumables') {
                $strategies[] = [
                    'strategy' => 'competitive_pricing',
                    'description' => 'Monitor competitive pricing for consumables',
                    'risk_level' => 'low'
                ];
            } else {
                $strategies[] = [
                    'strategy' => 'maintain_current',
                    'description' => 'Current pricing strategy appears appropriate',
                    'risk_level' => 'low'
                ];
            }
        }
        
        return $strategies;
    }
    
    /**
     * Suggest marketing angles
     */
    private function suggestMarketingAngles($learning) {
        $angles = [];
        
        $technical = $learning['technical_analysis'];
        $positioning = $learning['market_intelligence'];
        $quality = $learning['quality_indicators'];
        
        // Technical angle
        if ($technical['complexity_level'] === 'advanced') {
            $angles[] = [
                'angle' => 'technical_excellence',
                'message' => 'Emphasize advanced technical capabilities and precision',
                'target' => 'technical_buyers'
            ];
        }
        
        // Quality angle
        if ($quality['overall_score'] > 80) {
            $angles[] = [
                'angle' => 'quality_assurance',
                'message' => 'Highlight comprehensive documentation and quality standards',
                'target' => 'quality_conscious_buyers'
            ];
        }
        
        // Market fit angle
        if ($positioning['target_market'] === 'professional') {
            $angles[] = [
                'angle' => 'professional_grade',
                'message' => 'Focus on professional-grade features and reliability',
                'target' => 'business_customers'
            ];
        }
        
        return $angles;
    }
    
    /**
     * Suggest target keywords
     */
    private function suggestTargetKeywords($product, $classification) {
        $keywords = [];
        
        // Base category keywords
        $category = $classification['primary_category'];
        $categoryKeywords = [
            'plotter_cutting' => ['vinyl cutter', 'plotter', 'cutting machine', 'signmaking'],
            'printing_equipment' => ['printer', 'printing', 'inkjet', 'large format'],
            'laminating_equipment' => ['laminator', 'laminating', 'pouch laminator'],
            'office_equipment' => ['office', 'business', 'productivity', 'workflow'],
            'industrial_machinery' => ['industrial', 'manufacturing', 'production'],
            'consumables' => ['supplies', 'materials', 'consumables', 'replacement']
        ];
        
        $keywords = array_merge($keywords, $categoryKeywords[$category] ?? []);
        
        // Add product-specific keywords from name
        $name = strtolower($product['name'] ?? '');
        $nameWords = explode(' ', $name);
        foreach ($nameWords as $word) {
            if (strlen($word) > 3 && !in_array($word, ['the', 'and', 'for', 'with'])) {
                $keywords[] = $word;
            }
        }
        
        // Add technical keywords if applicable
        if (!empty($product['specifications'])) {
            $keywords[] = 'specifications';
            $keywords[] = 'technical';
        }
        
        return array_unique(array_slice($keywords, 0, 10));
    }
    
    /**
     * Suggest content improvements
     */
    private function suggestContentImprovements($learning) {
        $improvements = [];
        
        $quality = $learning['quality_indicators'];
        $technical = $learning['technical_analysis'];
        $confidence = $learning['extraction_confidence'];
        
        // Description improvements
        if ($quality['description_quality'] < 70) {
            $improvements[] = [
                'area' => 'description',
                'suggestion' => 'Enhance product description with more detailed features and benefits',
                'priority' => 'high'
            ];
        }
        
        // Technical content improvements
        if (!$technical['has_specifications']) {
            $improvements[] = [
                'area' => 'specifications',
                'suggestion' => 'Add detailed technical specifications to improve buyer confidence',
                'priority' => 'high'
            ];
        }
        
        // Image improvements
        if ($confidence['image_confidence'] < 60) {
            $improvements[] = [
                'area' => 'images',
                'suggestion' => 'Add more high-quality product images from different angles',
                'priority' => 'medium'
            ];
        }
        
        // Certification improvements
        if (!$technical['has_certifications']) {
            $improvements[] = [
                'area' => 'certifications',
                'suggestion' => 'Add quality certifications and compliance information',
                'priority' => 'medium'
            ];
        }
        
        return $improvements;
    }
    
    /**
     * Extract JSON-LD structured data
     */
    private function extractJsonLd($html) {
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is', $html, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $jsonString) {
                $data = json_decode(trim($jsonString), true);
                if ($data && isset($data['@type']) && $data['@type'] === 'Product') {
                    $product = [];
                    
                    if (isset($data['name'])) {
                        $product['name'] = $data['name'];
                    }
                    
                    if (isset($data['description'])) {
                        $product['description'] = $data['description'];
                    }
                    
                    if (isset($data['image'])) {
                        $product['images'] = is_array($data['image']) ? $data['image'] : [$data['image']];
                    }
                    
                    if (isset($data['offers']['price'])) {
                        $product['price'] = (float)$data['offers']['price'];
                    }
                    
                    return $product;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract images from JSON data
     */
    private function extractImagesFromJson($data, &$images = []) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_string($value) && preg_match('/\.(jpg|jpeg|png|webp|avif)$/i', $value)) {
                    $images[] = $value;
                } elseif (is_array($value) || is_object($value)) {
                    $this->extractImagesFromJson($value, $images);
                }
            }
        } elseif (is_object($data)) {
            $this->extractImagesFromJson((array)$data, $images);
        }
        
        return $images;
    }
    
    /**
     * Normalize image URL
     */
    private function normalizeImageUrl($url) {
        // Protocol-relative URL
        if (strpos($url, '//') === 0) {
            return 'https:' . $url;
        }
        
        // Relative URL
        if (strpos($url, '/') === 0) {
            return 'https://www.alibaba.com' . $url;
        }
        
        // Already absolute
        if (preg_match('/^https?:\/\//i', $url)) {
            return $url;
        }
        
        return 'https://www.alibaba.com/' . ltrim($url, '/');
    }
    
    /**
     * Validate image URL
     */
    private function isValidImageUrl($url) {
        // Basic URL validation
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Check if it's an image extension
        if (!preg_match('/\.(jpg|jpeg|png|gif|webp|avif)($|\?)/i', $url)) {
            return false;
        }
        
        // Avoid common non-product images
        $excludePatterns = [
            'logo', 'icon', 'avatar', 'badge', 'banner',
            'button', 'sprite', 'placeholder', 'loading',
            'social', 'payment', 'flag', 'star'
        ];
        
        $urlLower = strtolower($url);
        foreach ($excludePatterns as $pattern) {
            if (stripos($urlLower, $pattern) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get high resolution image URL with advanced techniques
     */
    private function getHighResImageUrl($url) {
        // Strategy 1: Replace size parameters in Alibaba CDN URLs
        $replacements = [
            // Alibaba specific patterns
            '/_\d+x\d+\.(jpg|jpeg|png|webp|avif)/i' => '_800x800.$1',
            '/\.summ\.(jpg|jpeg|png|webp|avif)/i' => '.$1',
            '/_50x50\./i' => '_800x800.',
            '/_100x100\./i' => '_800x800.',
            '/_200x200\./i' => '_800x800.',
            '/_220x220\./i' => '_800x800.',
            
            // Remove size query parameters
            '/[\?&]size=\d+x\d+/i' => '',
            '/[\?&]width=\d+/i' => '',
            '/[\?&]height=\d+/i' => '',
            
            // Replace quality parameters
            '/[\?&]quality=\d+/i' => '?quality=100',
        ];
        
        foreach ($replacements as $pattern => $replacement) {
            $url = preg_replace($pattern, $replacement, $url);
        }
        
        // Strategy 2: Add high-quality parameters if not present
        if (stripos($url, 'alibaba') !== false && stripos($url, '?') === false) {
            $url .= '?quality=100';
        }
        
        return $url;
    }
    
    /**
     * Download and save images with advanced retry logic
     */
    private function downloadImages($imageUrls) {
        $downloadedFiles = [];
        $failedUrls = [];
        
        foreach ($imageUrls as $index => $url) {
            try {
                $imageData = $this->downloadImageWithRetry($url, 3);
                
                if (!$imageData) {
                    $failedUrls[] = $url;
                    continue;
                }
                
                // Validate image data quality
                if (strlen($imageData) < 5000) {
                    // Too small, probably an error image or placeholder
                    $failedUrls[] = $url;
                    continue;
                }
                
                // Generate unique filename
                $extension = $this->getImageExtension($imageData);
                $filename = 'alibaba_' . uniqid() . '_' . time() . '_' . $index . '.' . $extension;
                $filepath = $this->imageDir . $filename;
                
                // Save image
                if (file_put_contents($filepath, $imageData)) {
                    // Verify saved file
                    if (filesize($filepath) > 5000) {
                        // Optimize image
                        $this->optimizeImage($filepath, $extension);
                        $downloadedFiles[] = $filename;
                    } else {
                        @unlink($filepath);
                        $failedUrls[] = $url;
                    }
                } else {
                    $failedUrls[] = $url;
                }
                
                // Small delay to avoid rate limiting
                usleep(300000); // 0.3 seconds
                
            } catch (Exception $e) {
                $failedUrls[] = $url;
                continue;
            }
        }
        
        // Log failed downloads for debugging
        if (!empty($failedUrls)) {
            error_log('Failed to download images: ' . implode(', ', $failedUrls));
        }
        
        return $downloadedFiles;
    }
    
    /**
     * Download single image with retry logic
     */
    private function downloadImageWithRetry($url, $maxRetries = 3) {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            $attempt++;
            
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 5,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_HTTPHEADER => [
                    'Accept: image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8',
                    'Accept-Language: en-US,en;q=0.9',
                    'Accept-Encoding: gzip, deflate, br',
                    'Cache-Control: no-cache',
                    'Referer: https://www.alibaba.com/'
                ],
                CURLOPT_ENCODING => 'gzip, deflate'
            ]);
            
            $imageData = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            $error = curl_error($ch);
            curl_close($ch);
            
            // Check for success
            if ($httpCode === 200 && $imageData && strlen($imageData) > 5000) {
                // Verify it's an actual image
                if (strpos($contentType, 'image/') === 0) {
                    return $imageData;
                }
                
                // Double-check with finfo
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageData);
                
                if (strpos($mimeType, 'image/') === 0) {
                    return $imageData;
                }
            }
            
            // If not last attempt, wait before retry
            if ($attempt < $maxRetries) {
                usleep(500000); // 0.5 second delay between retries
            }
        }
        
        return false;
    }
    
    /**
     * Get image extension from data
     */
    private function getImageExtension($imageData) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/avif' => 'avif'
        ];
        
        return $extensions[$mimeType] ?? 'jpg';
    }
    
    /**
     * Optimize downloaded image
     */
    private function optimizeImage($filepath, $extension) {
        if (!file_exists($filepath)) return;
        
        $image = null;
        
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
        }
        
        if (!$image) return;
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Resize if too large
        $maxWidth = 1600;
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = intval($height * ($maxWidth / $width));
            
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            
            if (in_array($extension, ['png', 'gif', 'webp'])) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
                imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
            }
            
            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            
            switch ($extension) {
                case 'jpg':
                case 'jpeg':
                    imagejpeg($resized, $filepath, 90);
                    break;
                case 'png':
                    imagepng($resized, $filepath, 7);
                    break;
                case 'gif':
                    imagegif($resized, $filepath);
                    break;
                case 'webp':
                    if (function_exists('imagewebp')) {
                        imagewebp($resized, $filepath, 90);
                    }
                    break;
            }
            
            imagedestroy($resized);
        }
        
        imagedestroy($image);
    }
    
    /**
     * Apply AI optimization to product data
     */
    private function applyAIOptimization($product) {
        // Generate optimized product name
        $product['optimized_name'] = $this->optimizeProductName($product['name']);
        
        // Generate SEO-friendly short description
        if (empty($product['short_description']) && !empty($product['description'])) {
            $product['short_description'] = $this->generateShortDescription($product['description']);
        }
        
        // Optimize description for SEO
        if (!empty($product['description'])) {
            $product['optimized_description'] = $this->optimizeDescription($product['description']);
        }
        
        // Generate slug
        $product['slug'] = $this->generateSlug($product['optimized_name'] ?? $product['name']);
        
        // Extract features from specifications with AI scoring
        if (!empty($product['specifications'])) {
            $product['key_features'] = $this->extractKeyFeatures($product['specifications']);
        }
        
        // Suggest category with confidence scoring
        $categoryData = $this->suggestCategory(
            $product['optimized_name'] ?? $product['name'],
            $product['description'] ?? '',
            $product['specifications'] ?? []
        );
        
        $product['suggested_category'] = $categoryData['category'];
        $product['category_confidence'] = $categoryData['confidence'];
        $product['alternative_categories'] = $categoryData['alternatives'];
        
        // Analyze image quality if images are present
        if (!empty($product['images'])) {
            $product['image_quality_scores'] = $this->analyzeImageQuality($product['images']);
        }
        
        // Price suggestions with market intelligence
        if ($product['price'] > 0) {
            $product['price_suggestions'] = $this->generatePriceSuggestions($product['price']);
        }
        
        // SEO meta data
        $product['meta_title'] = $this->generateMetaTitle($product);
        $product['meta_description'] = $this->generateMetaDescription($product);
        $product['meta_keywords'] = $this->generateMetaKeywords($product);
        
        $product['ai_optimized'] = true;
        $product['ai_optimization_timestamp'] = date('Y-m-d H:i:s');
        
        return $product;
    }
    
    /**
     * Analyze image quality with AI scoring
     */
    private function analyzeImageQuality($images) {
        $scores = [];
        
        foreach ($images as $index => $imagePath) {
            $fullPath = $this->uploadDir . '/' . $imagePath;
            
            if (!file_exists($fullPath)) {
                $scores[$index] = [
                    'overall_score' => 0,
                    'issues' => ['File not found']
                ];
                continue;
            }
            
            $score = 100;
            $issues = [];
            $details = [];
            
            // Get image info
            $imageInfo = getimagesize($fullPath);
            $fileSize = filesize($fullPath);
            
            if (!$imageInfo) {
                $scores[$index] = [
                    'overall_score' => 0,
                    'issues' => ['Invalid image file']
                ];
                continue;
            }
            
            list($width, $height, $type) = $imageInfo;
            
            // Strategy 1: Resolution scoring
            $totalPixels = $width * $height;
            $details['resolution'] = $width . 'x' . $height;
            $details['file_size'] = round($fileSize / 1024, 2) . ' KB';
            
            if ($width < 400 || $height < 400) {
                $score -= 30;
                $issues[] = 'Low resolution (minimum 400x400 recommended)';
            } else if ($width < 800 || $height < 800) {
                $score -= 15;
                $issues[] = 'Medium resolution (800x800+ recommended for better quality)';
            } else {
                $details['resolution_quality'] = 'Good';
            }
            
            // Strategy 2: Aspect ratio scoring
            $aspectRatio = $width / $height;
            $details['aspect_ratio'] = round($aspectRatio, 2);
            
            // Prefer square or standard ratios (1:1, 4:3, 16:9, 3:2)
            $standardRatios = [1.0, 1.33, 1.78, 1.5];
            $ratioMatch = false;
            
            foreach ($standardRatios as $standard) {
                if (abs($aspectRatio - $standard) < 0.1) {
                    $ratioMatch = true;
                    break;
                }
            }
            
            if (!$ratioMatch) {
                $score -= 5;
                $issues[] = 'Non-standard aspect ratio';
            }
            
            // Strategy 3: File size scoring
            $pixelsPerKB = $totalPixels / ($fileSize / 1024);
            
            if ($fileSize < 5000) {
                $score -= 25;
                $issues[] = 'Very small file size (possible placeholder or compressed)';
            } else if ($fileSize > 5000000) {
                $score -= 10;
                $issues[] = 'Very large file size (optimization recommended)';
            }
            
            // Strategy 4: Format scoring
            $format = image_type_to_extension($type, false);
            $details['format'] = $format;
            
            $modernFormats = ['webp', 'avif'];
            if (in_array($format, $modernFormats)) {
                $details['format_quality'] = 'Modern format';
            } else if ($format === 'jpg' || $format === 'jpeg' || $format === 'png') {
                $details['format_quality'] = 'Standard format';
            } else {
                $score -= 5;
                $issues[] = 'Consider using modern formats (WebP, AVIF)';
            }
            
            // Strategy 5: Quality grade
            if ($score >= 90) {
                $grade = 'A+ (Excellent)';
            } else if ($score >= 80) {
                $grade = 'A (Very Good)';
            } else if ($score >= 70) {
                $grade = 'B (Good)';
            } else if ($score >= 60) {
                $grade = 'C (Acceptable)';
            } else if ($score >= 50) {
                $grade = 'D (Poor)';
            } else {
                $grade = 'F (Unacceptable)';
            }
            
            $scores[$index] = [
                'overall_score' => max(0, $score),
                'grade' => $grade,
                'details' => $details,
                'issues' => $issues,
                'is_acceptable' => $score >= 60
            ];
        }
        
        return $scores;
    }
    
    /**
     * Optimize product name with advanced AI techniques
     */
    private function optimizeProductName($name) {
        // Strategy 1: Remove spam keywords and supplier jargon
        $spamPatterns = [
            '/^(hot\s+sale|wholesale|factory(\s+direct)?|supply|cheap|best\s+price|promotion)\s+/i',
            '/\s+(wholesale|factory|supply|price|alibaba|china)$/i',
            '/\s*\([^)]*(?:MOQ|wholesale|factory|OEM|ODM)[^)]*\)/i',
            '/\s*[\[\{][^\]\}]*(?:MOQ|wholesale|factory|OEM|ODM)[^\]\}]*[\]\}]/i'
        ];
        
        foreach ($spamPatterns as $pattern) {
            $name = preg_replace($pattern, '', $name);
        }
        
        // Strategy 2: Clean up excessive punctuation and special chars
        $name = preg_replace('/[!@#$%^&*()_+=\[\]{}|\\:;<>?\/]+/', ' ', $name);
        $name = preg_replace('/["\']/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);
        
        // Strategy 3: Proper capitalization
        $name = trim($name);
        $words = explode(' ', $name);
        $capitalizedWords = [];
        
        $smallWords = ['a', 'an', 'and', 'as', 'at', 'but', 'by', 'for', 'in', 'of', 'on', 'or', 'the', 'to', 'with'];
        
        foreach ($words as $i => $word) {
            $lowerWord = strtolower($word);
            
            // Always capitalize first and last word
            if ($i === 0 || $i === count($words) - 1 || !in_array($lowerWord, $smallWords)) {
                $capitalizedWords[] = ucfirst($lowerWord);
            } else {
                $capitalizedWords[] = $lowerWord;
            }
        }
        
        $name = implode(' ', $capitalizedWords);
        
        // Strategy 4: Remove trailing/leading hyphens
        $name = trim($name, ' -');
        
        // Strategy 5: Limit length to reasonable value
        if (strlen($name) > 100) {
            $name = substr($name, 0, 97) . '...';
        }
        
        return $name;
    }
    
    /**
     * Generate short description with AI enhancement
     */
    private function generateShortDescription($description) {
        $description = $this->cleanText($description);
        
        if (empty($description)) {
            return '';
        }
        
        // Strategy 1: Extract first meaningful sentences
        $sentences = preg_split('/(?<=[.!?])\s+/', $description);
        $short = '';
        $count = 0;
        $targetLength = 160;
        
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            
            // Skip very short or promotional sentences
            if (strlen($sentence) < 10 || 
                preg_match('/(wholesale|factory|cheap|buy|order|contact)/i', $sentence)) {
                continue;
            }
            
            if (strlen($short . $sentence) > $targetLength || $count >= 2) {
                break;
            }
            
            $short .= $sentence . ' ';
            $count++;
        }
        
        $short = trim($short);
        
        // Strategy 2: If too short, try to extract key features
        if (strlen($short) < 50 && !empty($description)) {
            $short = substr($description, 0, $targetLength);
            $short = substr($short, 0, strrpos($short, ' ')) . '...';
        }
        
        // Strategy 3: Remove promotional language
        $short = preg_replace('/(contact us|click here|buy now|order now)/i', '', $short);
        $short = preg_replace('/\s+/', ' ', $short);
        
        return trim($short);
    }
    
    /**
     * Optimize description with AI formatting
     */
    private function optimizeDescription($description) {
        $description = $this->cleanText($description);
        
        if (empty($description)) {
            return '';
        }
        
        // Strategy 1: Add proper paragraph breaks
        $description = preg_replace('/([.!?])\s+([A-Z])/', "$1\n\n$2", $description);
        
        // Strategy 2: Format lists properly
        $description = preg_replace('/^[-•*]\s+/m', '• ', $description);
        
        // Strategy 3: Remove promotional blocks
        $spamPhrases = [
            'contact us for',
            'click here to',
            'buy now',
            'order now',
            'whatsapp',
            'wechat',
            'skype'
        ];
        
        $lines = explode("\n", $description);
        $cleanedLines = [];
        
        foreach ($lines as $line) {
            $lineLower = strtolower($line);
            $isSpam = false;
            
            foreach ($spamPhrases as $phrase) {
                if (stripos($lineLower, $phrase) !== false) {
                    $isSpam = true;
                    break;
                }
            }
            
            if (!$isSpam && strlen(trim($line)) > 5) {
                $cleanedLines[] = $line;
            }
        }
        
        $description = implode("\n", $cleanedLines);
        
        // Strategy 4: Add spacing for readability
        $description = preg_replace('/\n{3,}/', "\n\n", $description);
        
        return trim($description);
    }
    
    /**
     * Generate slug
     */
    private function generateSlug($text) {
        $slug = strtolower($text);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }
    
    /**
     * Extract key features with AI enhancement
     */
    private function extractKeyFeatures($specifications) {
        $features = [];
        
        // Strategy 1: Define priority specifications
        $prioritySpecs = [
            'material' => 10,
            'size' => 9,
            'dimensions' => 9,
            'weight' => 8,
            'power' => 8,
            'voltage' => 7,
            'capacity' => 8,
            'color' => 6,
            'warranty' => 9,
            'brand' => 7,
            'model' => 7,
            'output' => 7,
            'input' => 6,
            'speed' => 7,
            'temperature' => 6,
            'pressure' => 6,
            'frequency' => 6
        ];
        
        // Strategy 2: Score and extract specifications
        $scoredSpecs = [];
        
        foreach ($specifications as $spec) {
            if (isset($spec['name']) && isset($spec['value'])) {
                $nameLower = strtolower($spec['name']);
                $score = 1; // Base score
                
                // Calculate priority score
                foreach ($prioritySpecs as $key => $priority) {
                    if (stripos($nameLower, $key) !== false) {
                        $score = $priority;
                        break;
                    }
                }
                
                // Boost score for detailed values
                if (strlen($spec['value']) > 10 && strlen($spec['value']) < 100) {
                    $score += 2;
                }
                
                // Penalize very short or very long values
                if (strlen($spec['value']) < 3 || strlen($spec['value']) > 150) {
                    $score -= 3;
                }
                
                $scoredSpecs[] = [
                    'name' => $spec['name'],
                    'value' => $spec['value'],
                    'score' => $score
                ];
            }
        }
        
        // Strategy 3: Sort by score and take top 8 features
        usort($scoredSpecs, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        $topSpecs = array_slice($scoredSpecs, 0, 8);
        
        foreach ($topSpecs as $spec) {
            $features[] = $spec['name'] . ': ' . $spec['value'];
        }
        
        return $features;
    }
    
    /**
     * Suggest category with AI-based confidence scoring
     */
    private function suggestCategory($productName, $description, $specifications) {
        // Strategy 1: Define category keywords with weights
        $categories = [
            'Electronics' => [
                'keywords' => ['electronic', 'digital', 'computer', 'phone', 'tablet', 'laptop', 'monitor', 
                              'speaker', 'headphone', 'camera', 'charger', 'cable', 'usb', 'hdmi', 'led', 
                              'lcd', 'battery', 'power bank', 'adapter', 'wireless', 'bluetooth'],
                'weight' => 0
            ],
            'Home & Garden' => [
                'keywords' => ['furniture', 'chair', 'table', 'sofa', 'bed', 'lamp', 'light', 'garden', 
                              'outdoor', 'pot', 'planter', 'tool', 'decoration', 'curtain', 'rug', 'mat',
                              'storage', 'organizer', 'kitchen', 'bathroom'],
                'weight' => 0
            ],
            'Clothing & Apparel' => [
                'keywords' => ['shirt', 't-shirt', 'dress', 'pants', 'jeans', 'jacket', 'coat', 'shoe', 
                              'boot', 'sneaker', 'hat', 'cap', 'scarf', 'glove', 'sock', 'underwear',
                              'clothing', 'apparel', 'fashion', 'wear'],
                'weight' => 0
            ],
            'Sports & Outdoors' => [
                'keywords' => ['sport', 'fitness', 'gym', 'exercise', 'yoga', 'running', 'cycling', 
                              'camping', 'hiking', 'fishing', 'hunting', 'bicycle', 'bike', 'skateboard',
                              'ball', 'racket', 'tent', 'backpack', 'outdoor'],
                'weight' => 0
            ],
            'Tools & Hardware' => [
                'keywords' => ['tool', 'drill', 'saw', 'hammer', 'screwdriver', 'wrench', 'plier', 
                              'hardware', 'nail', 'screw', 'bolt', 'nut', 'washer', 'bearing', 'gear',
                              'motor', 'pump', 'valve', 'pipe', 'fitting'],
                'weight' => 0
            ],
            'Automotive' => [
                'keywords' => ['car', 'auto', 'vehicle', 'automotive', 'truck', 'motorcycle', 'bike', 
                              'tire', 'wheel', 'engine', 'brake', 'suspension', 'exhaust', 'filter',
                              'oil', 'battery', 'alternator', 'starter', 'headlight'],
                'weight' => 0
            ],
            'Beauty & Personal Care' => [
                'keywords' => ['beauty', 'cosmetic', 'makeup', 'skincare', 'hair', 'nail', 'perfume', 
                              'fragrance', 'lotion', 'cream', 'serum', 'mask', 'brush', 'mirror',
                              'personal care', 'hygiene', 'grooming'],
                'weight' => 0
            ],
            'Toys & Games' => [
                'keywords' => ['toy', 'game', 'puzzle', 'doll', 'action figure', 'lego', 'block', 
                              'playot', 'kid', 'child', 'baby', 'infant', 'educational', 'learning',
                              'stuffed animal', 'plush', 'board game'],
                'weight' => 0
            ],
            'Office Supplies' => [
                'keywords' => ['office', 'stationery', 'pen', 'pencil', 'paper', 'notebook', 'folder', 
                              'binder', 'desk', 'calculator', 'printer', 'scanner', 'shredder',
                              'stapler', 'clip', 'tape', 'marker', 'highlighter'],
                'weight' => 0
            ],
            'Industrial & Scientific' => [
                'keywords' => ['industrial', 'machinery', 'equipment', 'commercial', 'professional', 
                              'heavy duty', 'laboratory', 'scientific', 'measurement', 'testing',
                              'sensor', 'controller', 'automation', 'manufacturing'],
                'weight' => 0
            ]
        ];
        
        // Strategy 2: Calculate keyword match weights
        $searchText = strtolower($productName . ' ' . $description);
        
        // Add specification values to search text
        foreach ($specifications as $spec) {
            if (isset($spec['value'])) {
                $searchText .= ' ' . strtolower($spec['value']);
            }
        }
        
        foreach ($categories as $category => &$data) {
            foreach ($data['keywords'] as $keyword) {
                // Count occurrences (more matches = higher confidence)
                $count = substr_count($searchText, $keyword);
                
                // Weight by keyword position (name > description > specs)
                if (stripos($productName, $keyword) !== false) {
                    $data['weight'] += $count * 5; // Name matches are most important
                } else if (stripos($description, $keyword) !== false) {
                    $data['weight'] += $count * 2; // Description matches
                } else {
                    $data['weight'] += $count; // Spec matches
                }
            }
        }
        
        // Strategy 3: Find category with highest weight
        $maxWeight = 0;
        $suggestedCategory = 'General';
        $confidence = 0;
        
        foreach ($categories as $category => $data) {
            if ($data['weight'] > $maxWeight) {
                $maxWeight = $data['weight'];
                $suggestedCategory = $category;
            }
        }
        
        // Calculate confidence percentage (0-100)
        if ($maxWeight > 0) {
            $totalWeight = array_sum(array_column($categories, 'weight'));
            $confidence = round(($maxWeight / max($totalWeight, 1)) * 100);
            
            // Cap at 95% (never 100% certain with AI)
            $confidence = min($confidence, 95);
        }
        
        // Strategy 4: Return with confidence score
        return [
            'category' => $suggestedCategory,
            'confidence' => $confidence,
            'alternatives' => $this->getAlternativeCategories($categories, $maxWeight)
        ];
    }
    
    /**
     * Get alternative category suggestions
     */
    private function getAlternativeCategories($categories, $topWeight) {
        $alternatives = [];
        
        foreach ($categories as $category => $data) {
            // Include categories with at least 30% of top weight
            if ($data['weight'] > 0 && $data['weight'] >= ($topWeight * 0.3)) {
                $alternatives[] = [
                    'name' => $category,
                    'score' => $data['weight']
                ];
            }
        }
        
        // Sort by score
        usort($alternatives, function($a, $b) {
            return $b['score'] - $a['score'];
        });
        
        // Return top 3 (excluding the main suggestion which is first)
        return array_slice($alternatives, 1, 3);
    }
    
    /**
     * Generate price suggestions with market intelligence
     */
    private function generatePriceSuggestions($originalPrice) {
        // Strategy 1: Define markup tiers based on market analysis
        $markupTiers = [
            'economy' => [
                'multiplier' => 1.8,
                'description' => 'Budget-friendly option for price-sensitive customers'
            ],
            'standard' => [
                'multiplier' => 2.3,
                'description' => 'Balanced price-to-value ratio for general market'
            ],
            'premium' => [
                'multiplier' => 2.8,
                'description' => 'Premium positioning for quality-focused buyers'
            ],
            'luxury' => [
                'multiplier' => 3.5,
                'description' => 'Luxury pricing for exclusive, high-end market'
            ]
        ];
        
        $suggestions = [];
        
        // Strategy 2: Calculate prices for each tier
        foreach ($markupTiers as $tier => $data) {
            $calculatedPrice = $originalPrice * $data['multiplier'];
            
            // Strategy 3: Apply psychological pricing
            $finalPrice = $this->applyPsychologicalPricing($calculatedPrice);
            
            // Calculate profit margin
            $profit = $finalPrice - $originalPrice;
            $marginPercent = round((($finalPrice - $originalPrice) / $finalPrice) * 100);
            
            $suggestions[$tier] = [
                'price' => number_format($finalPrice, 2),
                'raw_price' => $finalPrice,
                'profit' => number_format($profit, 2),
                'margin' => $marginPercent . '%',
                'description' => $data['description'],
                'recommended' => ($tier === 'standard') // Recommend standard tier
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Apply psychological pricing strategies
     */
    private function applyPsychologicalPricing($price) {
        // Strategy 1: Charm pricing (ending in .99 or .95)
        if ($price < 10) {
            // For prices under $10, use .99
            return floor($price) + 0.99;
        } else if ($price < 100) {
            // For prices under $100, use .95
            return floor($price) + 0.95;
        } else if ($price < 1000) {
            // For prices under $1000, round to nearest 9 (e.g., 249, 499, 799)
            $rounded = round($price / 10) * 10;
            return $rounded - 1;
        } else {
            // For prices over $1000, round to nearest 99 (e.g., 1299, 1999)
            $rounded = round($price / 100) * 100;
            return $rounded - 1;
        }
    }
    
    /**
     * Generate meta title
     */
    private function generateMetaTitle($product) {
        $name = $product['optimized_name'] ?? $product['name'];
        $title = $name;
        
        // Add category if available
        if (!empty($product['suggested_category'])) {
            $title .= ' - ' . ucfirst($product['suggested_category']);
        }
        
        // Add brand name
        $title .= ' | JINKA Plotter';
        
        // Limit to 60 characters for SEO
        if (strlen($title) > 60) {
            $title = substr($title, 0, 57) . '...';
        }
        
        return $title;
    }
    
    /**
     * Generate meta description
     */
    private function generateMetaDescription($product) {
        $desc = $product['short_description'] ?? '';
        
        if (empty($desc) && !empty($product['description'])) {
            $desc = $this->generateShortDescription($product['description']);
        }
        
        // Add call to action
        if (!empty($desc)) {
            $desc .= ' Buy now from JINKA Plotter.';
        }
        
        // Limit to 160 characters for SEO
        if (strlen($desc) > 160) {
            $desc = substr($desc, 0, 157) . '...';
        }
        
        return $desc;
    }
    
    /**
     * Generate meta keywords
     */
    private function generateMetaKeywords($product) {
        $keywords = [];
        
        // Add product name words
        $nameWords = explode(' ', $product['name']);
        $keywords = array_merge($keywords, array_slice($nameWords, 0, 3));
        
        // Add category
        if (!empty($product['suggested_category'])) {
            $keywords[] = $product['suggested_category'];
        }
        
        // Add specification keywords
        if (!empty($product['specifications'])) {
            foreach (array_slice($product['specifications'], 0, 3) as $spec) {
                $keywords[] = strtolower($spec['name']);
            }
        }
        
        // Clean and deduplicate
        $keywords = array_unique(array_map('trim', $keywords));
        $keywords = array_filter($keywords, function($k) {
            return strlen($k) > 2;
        });
        
        return implode(', ', array_slice($keywords, 0, 10));
    }
    
    /**
     * Clean text
     */
    private function cleanText($text) {
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        return $text;
    }
}

// Handle request with comprehensive error handling
try {
    $action = $_POST['action'] ?? $_GET['action'] ?? null;

    if ($action === 'import') {
        $url = trim($_POST['url'] ?? '');
        
        if (empty($url)) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'URL is required']);
            exit;
        }
        
        $importer = new AlibabaImporter();
        $result = $importer->importProduct($url);
        
        ob_clean();
        echo json_encode($result);
        exit;
    }

    // Test endpoint
    if ($action === 'test') {
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Alibaba Import API is running',
            'version' => '2.1',
            'features' => [
                'Advanced web scraping',
                'AI product optimization',
                'Automatic image download',
                'SEO optimization',
                'Price analysis',
                'Category suggestion'
            ]
        ]);
        exit;
    }

    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
} catch (Error $e) {
    ob_clean();
    echo json_encode([
        'success' => false, 
        'error' => 'Fatal error: ' . $e->getMessage(),
        'debug' => [
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ]);
    exit;
}
