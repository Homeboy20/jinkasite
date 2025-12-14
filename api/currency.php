<?php
/**
 * Currency Switcher API Endpoint
 */
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/CurrencyDetector.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (isset($data['currency'])) {
        $detector = CurrencyDetector::getInstance();
        $success = $detector->setCurrency($data['currency']);
        
        if ($success) {
            echo json_encode([
                'success' => true,
                'currency' => $detector->getCurrentCurrency(),
                'details' => $detector->getCurrencyDetails()
            ]);
        } else {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid currency code'
            ]);
        }
    } else {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Currency parameter missing'
        ]);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get current currency info
    $detector = CurrencyDetector::getInstance();
    echo json_encode([
        'success' => true,
        'currency' => $detector->getCurrentCurrency(),
        'country' => $detector->getCountryCode(),
        'details' => $detector->getCurrencyDetails(),
        'available' => $detector->getAvailableCurrencies()
    ]);
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
}
