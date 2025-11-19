<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

// Correctly locate and require necessary files
$base_dir = __DIR__;
require_once $base_dir . '/includes/auth.php';


// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid action.'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$action) {
    echo json_encode($response);
    exit;
}

try {
    switch ($action) {
        case 'create':
        case 'update':
            $name = Security::sanitizeInput($_POST['name'] ?? '');
            $slug = Security::sanitizeInput($_POST['slug'] ?? '');
            $description = Security::sanitizeInput($_POST['description'] ?? '');
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
            $sort_order = (int)($_POST['sort_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $seo_title = Security::sanitizeInput($_POST['seo_title'] ?? '');
            $seo_description = Security::sanitizeInput($_POST['seo_description'] ?? '');

            if (empty($name) || empty($slug)) {
                 $response['error'] = 'Category Name and Slug are required.';
                 echo json_encode($response);
                 exit;
            }

            if ($action === 'create') {
                $stmt = $db->prepare("INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('sssiiiss', $name, $slug, $description, $parent_id, $sort_order, $is_active, $seo_title, $seo_description);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Category created successfully!'];
                } else {
                    $response['error'] = 'Database error: ' . $db->error;
                }
            } else {
                $id = (int)($_POST['category_id'] ?? 0);
                 if ($id === 0) {
                    $response['error'] = 'Category ID is missing for update.';
                    echo json_encode($response);
                    exit;
                }
                $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, description=?, parent_id=?, sort_order=?, is_active=?, seo_title=?, seo_description=? WHERE id=?");
                $stmt->bind_param('sssiiissi', $name, $slug, $description, $parent_id, $sort_order, $is_active, $seo_title, $seo_description, $id);
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Category updated successfully!'];
                } else {
                    $response['error'] = 'Database error: ' . $db->error;
                }
            }
            break;

        case 'check_slug':
            $slug = $_POST['slug'] ?? '';
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
            $stmt = $db->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
            $stmt->bind_param('si', $slug, $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $response = ['success' => true, 'exists' => $result->num_rows > 0];
            break;

        case 'update_sort':
            $draggedId = (int)($_POST['category_id'] ?? 0);
            $newSortOrder = (int)($_POST['new_sort_order'] ?? 0);
            
            // This is a simplified sort update. A full implementation would shift other items.
            $stmt = $db->prepare("UPDATE categories SET sort_order = ? WHERE id = ?");
            $stmt->bind_param('ii', $newSortOrder, $draggedId);
            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Sort order updated.'];
            } else {
                $response['error'] = 'Failed to update sort order.';
            }
            break;

        case 'bulk_activate':
        case 'bulk_deactivate':
            $ids = json_decode($_POST['category_ids'] ?? '[]');
            if (!empty($ids) && is_array($ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
                $is_active = ($action === 'bulk_activate') ? 1 : 0;
                
                $stmt = $db->prepare("UPDATE categories SET is_active = ? WHERE id IN ($ids_placeholder)");
                
                // Dynamically create the type string and parameters array
                $types = 'i' . str_repeat('i', count($ids));
                $params = array_merge([$is_active], $ids);
                
                $stmt->bind_param($types, ...$params);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Categories updated.'];
                } else {
                    $response['error'] = 'Failed to update categories.';
                }
            } else {
                $response['error'] = 'Invalid category IDs provided.';
            }
            break;

        case 'bulk_delete':
            $ids = json_decode($_POST['category_ids'] ?? '[]');
            if (!empty($ids) && is_array($ids)) {
                $ids_placeholder = implode(',', array_fill(0, count($ids), '?'));
                
                // A robust check would be to ensure no products or children are associated.
                // For now, we proceed with deletion as requested by the UI logic.
                $stmt = $db->prepare("DELETE FROM categories WHERE id IN ($ids_placeholder)");
                
                $types = str_repeat('i', count($ids));
                $stmt->bind_param($types, ...$ids);
                
                if ($stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Categories deleted.'];
                } else {
                    $response['error'] = 'Failed to delete categories. They may have products or sub-categories.';
                }
            } else {
                $response['error'] = 'Invalid category IDs provided.';
            }
            break;
            
        default:
            $response['error'] = 'Unknown action specified.';
            break;
    }
} catch (Exception $e) {
    $response['error'] = 'An exception occurred: ' . $e->getMessage();
}

echo json_encode($response);
exit;