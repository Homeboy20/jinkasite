<?php
/**
 * AI Helper Service
 * Integrates DeepSeek AI, Kimi AI, and OpenAI for product optimization
 */

class AIHelper {
    private $deepseek_api_key;
    private $kimi_api_key;
    private $openai_api_key;
    private $default_provider;
    private $db;
    
    public function __construct() {
        // Get database instance
        $this->db = Database::getInstance()->getConnection();
        
        // Load configuration file if not already loaded
        $config_file = __DIR__ . '/ai_config.php';
        if (file_exists($config_file)) {
            require_once $config_file;
        }
        
        // Load API keys from database settings (preferred) or config file
        $this->deepseek_api_key = $this->getSetting('ai_deepseek_key', defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '');
        $this->kimi_api_key = $this->getSetting('ai_kimi_key', defined('KIMI_API_KEY') ? KIMI_API_KEY : '');
        $this->openai_api_key = $this->getSetting('ai_openai_key', '');
        $this->default_provider = $this->getSetting('ai_default_provider', 'deepseek');
    }
    
    /**
     * Get setting from database
     */
    public function getSetting($key, $default = '') {
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->bind_param('s', $key);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['setting_value'] : $default;
    }
    
    /**
     * Optimize product description for SEO using DeepSeek AI
     */
    public function optimizeDescription($productName, $description, $category = '') {
        $prompt = "You are an SEO expert for e-commerce. Optimize this product description for search engines and customers:\n\n";
        $prompt .= "Product: {$productName}\n";
        if ($category) $prompt .= "Category: {$category}\n";
        $prompt .= "Current Description: {$description}\n\n";
        $prompt .= "Provide an SEO-optimized description that:\n";
        $prompt .= "1. Includes relevant keywords naturally\n";
        $prompt .= "2. Highlights key benefits and features\n";
        $prompt .= "3. Is engaging and persuasive\n";
        $prompt .= "4. Is 150-300 words\n";
        $prompt .= "5. Includes a call-to-action\n\n";
        $prompt .= "Return ONLY the optimized description without any explanations.";
        
        return $this->callDeepSeek($prompt);
    }
    
    /**
     * Generate SEO-friendly short description using Kimi AI
     */
    public function generateShortDescription($productName, $fullDescription) {
        $prompt = "Create a compelling 1-2 sentence product summary for:\n\n";
        $prompt .= "Product: {$productName}\n";
        $prompt .= "Full Description: {$fullDescription}\n\n";
        $prompt .= "Requirements:\n";
        $prompt .= "- Maximum 160 characters (for meta descriptions)\n";
        $prompt .= "- Include primary keyword\n";
        $prompt .= "- Be compelling and actionable\n";
        $prompt .= "- No quotation marks\n\n";
        $prompt .= "Return ONLY the short description.";
        
        return $this->callKimi($prompt);
    }
    
    /**
     * Extract key selling points from product data
     */
    public function extractSellingPoints($productData) {
        $prompt = "Analyze this product and identify the top 5 key selling points:\n\n";
        $prompt .= "Product Name: {$productData['name']}\n";
        if (!empty($productData['description'])) {
            $prompt .= "Description: {$productData['description']}\n";
        }
        if (!empty($productData['specifications'])) {
            $prompt .= "Specifications: " . json_encode($productData['specifications']) . "\n";
        }
        if (!empty($productData['features'])) {
            $prompt .= "Features: " . json_encode($productData['features']) . "\n";
        }
        $prompt .= "\nReturn 5 key selling points as a JSON array of strings. Each point should be concise (5-10 words). Focus on benefits, not just features.";
        $prompt .= "\nFormat: [\"point1\", \"point2\", \"point3\", \"point4\", \"point5\"]";
        
        $response = $this->callDeepSeek($prompt);
        
        // Try to parse JSON response
        $points = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($points)) {
            return $points;
        }
        
        // Fallback: extract points from response
        preg_match_all('/[\d\.\-\*]\s*(.+?)(?=[\d\.\-\*]|\n|$)/s', $response, $matches);
        if (!empty($matches[1])) {
            return array_slice(array_map('trim', $matches[1]), 0, 5);
        }
        
        return [];
    }
    
    /**
     * Generate SEO keywords for product
     */
    public function generateSEOKeywords($productName, $description, $category = '') {
        $prompt = "Generate SEO keywords for this product:\n\n";
        $prompt .= "Product: {$productName}\n";
        if ($category) $prompt .= "Category: {$category}\n";
        $prompt .= "Description: {$description}\n\n";
        $prompt .= "Provide 10-15 relevant keywords/phrases that potential customers would search for.\n";
        $prompt .= "Include:\n";
        $prompt .= "- Primary keywords\n";
        $prompt .= "- Long-tail keywords\n";
        $prompt .= "- Related search terms\n";
        $prompt .= "- Target market: Kenya and Tanzania\n\n";
        $prompt .= "Return as comma-separated values only.";
        
        return $this->callKimi($prompt);
    }
    
    /**
     * Enhance product title for SEO
     */
    public function optimizeProductTitle($title, $specifications = []) {
        $prompt = "Optimize this product title for SEO while keeping it natural and readable:\n\n";
        $prompt .= "Current Title: {$title}\n";
        if (!empty($specifications)) {
            $prompt .= "Key Specs: " . json_encode($specifications) . "\n";
        }
        $prompt .= "\nCreate an SEO-friendly title that:\n";
        $prompt .= "1. Is 50-60 characters\n";
        $prompt .= "2. Includes primary keywords\n";
        $prompt .= "3. Mentions key specification if relevant\n";
        $prompt .= "4. Sounds natural and professional\n";
        $prompt .= "5. Attracts clicks\n\n";
        $prompt .= "Return ONLY the optimized title.";
        
        return $this->callDeepSeek($prompt);
    }
    
    /**
     * Generate product features from specifications
     */
    public function generateFeaturesFromSpecs($productName, $specifications) {
        $prompt = "Convert these technical specifications into customer-friendly feature benefits:\n\n";
        $prompt .= "Product: {$productName}\n";
        $prompt .= "Specifications:\n";
        foreach ($specifications as $spec) {
            $prompt .= "- {$spec['name']}: {$spec['value']}\n";
        }
        $prompt .= "\nGenerate 5-8 feature statements that:\n";
        $prompt .= "1. Explain benefits, not just specs\n";
        $prompt .= "2. Are customer-focused\n";
        $prompt .= "3. Are concise (one line each)\n";
        $prompt .= "4. Highlight competitive advantages\n\n";
        $prompt .= "Return as JSON array of strings.";
        
        $response = $this->callKimi($prompt);
        
        // Try to parse JSON
        $features = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($features)) {
            return $features;
        }
        
        // Fallback: extract features from response
        preg_match_all('/[\d\.\-\*]\s*(.+?)(?=[\d\.\-\*]|\n|$)/s', $response, $matches);
        if (!empty($matches[1])) {
            return array_map('trim', $matches[1]);
        }
        
        return [];
    }
    
    /**
     * Call DeepSeek AI API
     */
    public function callDeepSeek($prompt) {
        if (empty($this->deepseek_api_key)) {
            return $this->getMockResponse('deepseek', $prompt);
        }
        
        $url = 'https://api.deepseek.com/v1/chat/completions';
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional e-commerce and SEO expert specializing in product optimization for African markets, particularly Kenya and Tanzania.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1000
        ];
        
        return $this->makeAPIRequest($url, $data, $this->deepseek_api_key);
    }
    
    /**
     * Call Kimi AI API
     */
    public function callKimi($prompt) {
        if (empty($this->kimi_api_key)) {
            return $this->getMockResponse('kimi', $prompt);
        }
        
        $url = 'https://api.moonshot.cn/v1/chat/completions';
        
        $data = [
            'model' => 'moonshot-v1-8k',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are an expert in e-commerce product marketing and SEO optimization for East African markets.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 800
        ];
        
        return $this->makeAPIRequest($url, $data, $this->kimi_api_key);
    }
    
    /**
     * Call OpenAI API (GPT-4 or GPT-3.5)
     */
    public function callOpenAI($prompt, $model = 'gpt-4o-mini') {
        if (empty($this->openai_api_key)) {
            return $this->getMockResponse('openai', $prompt);
        }
        
        $url = 'https://api.openai.com/v1/chat/completions';
        
        // Use model from settings if available
        $preferred_model = $this->getSetting('ai_openai_model', $model);
        
        $data = [
            'model' => $preferred_model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a professional e-commerce and SEO expert specializing in product optimization for African markets, particularly Kenya and Tanzania. Provide clear, actionable, and market-specific advice.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.7,
            'max_tokens' => 1200
        ];
        
        return $this->makeAPIRequest($url, $data, $this->openai_api_key);
    }
    
    /**
     * Call the default AI provider based on settings
     */
    private function callAI($prompt) {
        switch ($this->default_provider) {
            case 'openai':
                return $this->callOpenAI($prompt);
            case 'kimi':
                return $this->callKimi($prompt);
            case 'deepseek':
            default:
                return $this->callDeepSeek($prompt);
        }
    }
    
    /**
     * Make API request to AI service
     */
    private function makeAPIRequest($url, $data, $apiKey) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            error_log("AI API Error: HTTP {$httpCode} - {$error}");
            return null;
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['choices'][0]['message']['content'])) {
            return trim($result['choices'][0]['message']['content']);
        }
        
        return null;
    }
    
    /**
     * Get mock response for testing without API keys
     */
    private function getMockResponse($service, $prompt) {
        // Generate realistic mock responses based on prompt type
        if (strpos($prompt, 'Optimize this product description') !== false) {
            return "Professional-grade cutting plotter designed for commercial signage and graphics applications. This high-precision machine features advanced servo motor technology for exceptional accuracy and reliability. Perfect for sign makers, vehicle branding professionals, and commercial printing businesses. With its large cutting width and user-friendly interface, you can handle projects of any size with ease. Includes comprehensive software support and training materials. Ideal for vinyl cutting, heat transfer applications, and custom graphics production. Backed by industry-leading warranty and local support in Kenya and Tanzania.";
        }
        
        if (strpos($prompt, 'compelling 1-2 sentence') !== false || strpos($prompt, 'short description') !== false) {
            return "Professional 1350mm cutting plotter for commercial signage, vehicle graphics, and custom vinyl applications with precision servo motor technology.";
        }
        
        if (strpos($prompt, 'key selling points') !== false) {
            return json_encode([
                "High-precision servo motor technology",
                "Large 1350mm cutting width",
                "Professional-grade reliability",
                "Comprehensive software included",
                "Local support in Kenya & Tanzania"
            ]);
        }
        
        if (strpos($prompt, 'SEO keywords') !== false) {
            return "cutting plotter Kenya, vinyl cutter Tanzania, professional signage equipment, vehicle graphics machine, commercial printing plotter, sign making equipment, heat transfer cutter, wide format plotter, plotting machine East Africa, digital cutting system";
        }
        
        if (strpos($prompt, 'Optimize this product title') !== false) {
            return "Professional 1350mm Cutting Plotter - Commercial Signage & Graphics";
        }
        
        if (strpos($prompt, 'customer-friendly feature') !== false) {
            return json_encode([
                "Achieve precision cuts every time with advanced servo motor",
                "Handle large projects with 1350mm cutting width",
                "Save time with high-speed operation up to 800mm/s",
                "Easy setup with USB and RS-232 connectivity",
                "Reduce waste with exceptional ±0.1mm accuracy",
                "Professional results for all vinyl materials",
                "Backed by local technical support team"
            ]);
        }
        
        return "AI optimization completed successfully. (Demo mode - configure API keys for full functionality)";
    }
}
