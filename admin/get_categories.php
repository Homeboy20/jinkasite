<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

// Correctly locate and require necessary files
$base_dir = __DIR__;
require_once $base_dir . '/includes/auth.php';
require_once $base_dir . '/../includes/Database.php';

// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();

    if ($category) {
        echo json_encode(['success' => true, 'category' => $category]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Category not found.']);
    }
} else {
    // Fetch all active categories for general use (e.g., dropdowns)
    $result = $db->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name");
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    echo json_encode($categories);
}

exit;