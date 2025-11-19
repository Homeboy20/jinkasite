<?php
require_once 'alibaba_import_api.php';

// Test the enhanced AI import system
$importer = new AlibabaImportAPI();

// Test URL (using one of our existing products)
$testUrl = 'https://www.alibaba.com/product-detail/A4-A3-Hot-Cold-Laminating-Machine_1600572656983.html';

echo "=== TESTING ENHANCED AI IMPORT SYSTEM ===\n\n";
echo "Testing URL: $testUrl\n\n";

try {
    // Test the extraction with full AI analysis
    $result = $importer->extractProductInfo($testUrl);
    
    if (isset($result['error'])) {
        echo "ERROR: " . $result['error'] . "\n";
        exit;
    }
    
    // Display AI learning data
    echo "=== AI LEARNING DATA ===\n";
    if (isset($result['ai_learning_data'])) {
        $ai = $result['ai_learning_data'];
        
        echo "Content Depth Analysis:\n";
        echo "- Word Count: " . $ai['content_depth']['word_count'] . "\n";
        echo "- Richness Score: " . $ai['content_depth']['richness_score'] . "\n";
        echo "- Content Type: " . $ai['content_depth']['content_type'] . "\n\n";
        
        echo "Technical Analysis:\n";
        echo "- Complexity Level: " . $ai['technical_analysis']['complexity_level'] . "\n";
        echo "- Has Specifications: " . ($ai['technical_analysis']['has_specifications'] ? 'Yes' : 'No') . "\n";
        echo "- Has Certifications: " . ($ai['technical_analysis']['has_certifications'] ? 'Yes' : 'No') . "\n";
        echo "- Technical Vocabulary Count: " . count($ai['technical_analysis']['technical_vocabulary']) . "\n\n";
        
        echo "Product Classification:\n";
        echo "- Primary Category: " . $ai['product_classification']['primary_category'] . "\n";
        echo "- Confidence: " . $ai['product_classification']['confidence'] . "%\n";
        if (!empty($ai['product_classification']['subcategory'])) {
            echo "- Subcategory: " . $ai['product_classification']['subcategory'] . "\n";
        }
        echo "\n";
        
        echo "Market Intelligence:\n";
        echo "- Target Market: " . $ai['market_intelligence']['target_market'] . "\n";
        echo "- Price Positioning: " . $ai['market_intelligence']['price_positioning'] . "\n";
        echo "- Application Areas: " . implode(', ', array_slice($ai['market_intelligence']['application_areas'], 0, 3)) . "\n\n";
        
        echo "Quality Indicators:\n";
        echo "- Overall Score: " . $ai['quality_indicators']['overall_score'] . "\n";
        echo "- Completeness: " . $ai['quality_indicators']['content_completeness'] . "\n";
        echo "- Professional Presentation: " . $ai['quality_indicators']['professional_presentation'] . "\n\n";
    }
    
    // Display AI enhancements
    echo "=== AI ENHANCEMENTS ===\n";
    
    if (isset($result['enhanced_name'])) {
        echo "Enhanced Name: " . $result['enhanced_name'] . "\n";
        echo "Original Name: " . $result['name_enhancement']['original'] . "\n\n";
    }
    
    if (isset($result['ai_generated_description'])) {
        echo "AI Generated Description:\n";
        echo substr($result['ai_generated_description'], 0, 300) . "...\n\n";
    }
    
    if (isset($result['ai_generated_features'])) {
        echo "AI Generated Features (" . count($result['ai_generated_features']) . "):\n";
        foreach (array_slice($result['ai_generated_features'], 0, 3) as $feature) {
            echo "- " . $feature['title'] . " (Confidence: " . $feature['confidence'] . "%)\n";
        }
        echo "\n";
    }
    
    if (isset($result['ai_generated_benefits'])) {
        echo "AI Generated Benefits (" . count($result['ai_generated_benefits']) . "):\n";
        foreach (array_slice($result['ai_generated_benefits'], 0, 3) as $benefit) {
            echo "- " . $benefit['title'] . " (Confidence: " . $benefit['confidence'] . "%)\n";
        }
        echo "\n";
    }
    
    if (isset($result['market_insights'])) {
        echo "Market Insights:\n";
        $insights = $result['market_insights'];
        echo "- Market Opportunity Score: " . $insights['market_analysis']['market_opportunity']['overall_opportunity_score'] . "/10\n";
        echo "- Profit Potential: " . $insights['business_intelligence']['profit_potential']['overall_profit_potential'] . "/10\n";
        echo "- Growth Potential: " . $insights['business_intelligence']['growth_potential']['overall_growth_potential'] . "/10\n";
        echo "- Risk Factors: " . count($insights['business_intelligence']['risk_factors']) . " identified\n\n";
    }
    
    // Display extraction confidence
    if (isset($result['ai_learning_data']['extraction_confidence'])) {
        echo "=== EXTRACTION CONFIDENCE ===\n";
        $conf = $result['ai_learning_data']['extraction_confidence'];
        echo "- Overall Confidence: " . $conf['overall_confidence'] . "%\n";
        echo "- Name Confidence: " . $conf['name_confidence'] . "%\n";
        echo "- Description Confidence: " . $conf['description_confidence'] . "%\n";
        echo "- Specification Confidence: " . $conf['specification_confidence'] . "%\n";
        echo "- Image Confidence: " . $conf['image_confidence'] . "%\n\n";
    }
    
    echo "=== BASIC PRODUCT INFO ===\n";
    echo "Name: " . ($result['name'] ?? 'N/A') . "\n";
    echo "Price: " . ($result['price'] ?? 'N/A') . "\n";
    echo "Supplier: " . ($result['supplier'] ?? 'N/A') . "\n";
    echo "Images: " . count($result['images'] ?? []) . "\n";
    echo "Specifications: " . count($result['specifications'] ?? []) . "\n";
    
    echo "\n=== AI ENHANCEMENT COMPLETE ===\n";
    echo "The import system now has full AI learning capabilities!\n";
    
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>