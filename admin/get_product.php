<?php
session_start();

// Set JSON header FIRST before any output
header('Content-Type: application/json');

// Check authentication manually
if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

// Disable HTML error output - only JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid product ID']);
    exit;
}

$product_id = (int)$_GET['id'];

// Database connection
$host = 'localhost';
$dbname = 'jinka_plotter';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Fetch product data
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ?
    ");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    // Parse JSON fields
    $product['features'] = $product['features'] ? json_decode($product['features'], true) : [];
    $product['specifications'] = $product['specifications'] ? json_decode($product['specifications'], true) : [];
    
    // Ensure features is an array of strings (not objects)
    if (is_array($product['features'])) {
        $product['features'] = array_values($product['features']);
    }
    
    // Ensure specifications is an array of objects with name/value
    if (is_array($product['specifications'])) {
        $specs = [];
        foreach ($product['specifications'] as $key => $value) {
            if (is_array($value) && isset($value['name']) && isset($value['value'])) {
                $specs[] = $value;
            } elseif (is_string($key) && is_string($value)) {
                $specs[] = ['name' => $key, 'value' => $value];
            }
        }
        $product['specifications'] = $specs;
    }
    
    echo json_encode($product);
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
