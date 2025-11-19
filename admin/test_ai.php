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
$provider = $data['provider'] ?? 'deepseek';

try {
    $ai = new AIHelper();
    
    // Simple test prompt
    $testPrompt = "Say 'AI connection successful' in exactly 5 words or less.";
    
    // Test based on provider
    $result = null;
    switch ($provider) {
        case 'openai':
            $result = $ai->callOpenAI($testPrompt);
            break;
        case 'kimi':
            $result = $ai->callKimi($testPrompt);
            break;
        case 'deepseek':
        default:
            $result = $ai->callDeepSeek($testPrompt);
            break;
    }
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => $result,
            'provider' => strtoupper($provider)
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No response received from AI provider. Check your API key and try again.'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
