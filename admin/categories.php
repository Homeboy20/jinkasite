<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once 'includes/auth.php';

// Require authentication
$auth = requireAuth('admin');
$currentUser = $auth->getCurrentUser();

$db = Database::getInstance()->getConnection();
$message = '';
$messageType = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $name = Security::sanitizeInput($_POST['name']);
        $slug = Security::sanitizeInput($_POST['slug']);
        $description = Security::sanitizeInput($_POST['description']);
        $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $sort_order = (int)$_POST['sort_order'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $seo_title = Security::sanitizeInput($_POST['seo_title']);
        $seo_description = Security::sanitizeInput($_POST['seo_description']);
        
        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO categories (name, slug, description, parent_id, sort_order, is_active, seo_title, seo_description) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('sssiiiss', $name, $slug, $description, $parent_id, $sort_order, $is_active, $seo_title, $seo_description);
            
            if ($stmt->execute()) {
                $message = 'Category created successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error creating category: ' . $db->error;
                $messageType = 'error';
            }
        } else {
            $id = (int)$_POST['id'];
            
            // Check if trying to set parent as self or child
            if ($parent_id && ($parent_id == $id || $this->isChildCategory($db, $id, $parent_id))) {
                $message = 'Cannot set category as parent of itself or its child!';
                $messageType = 'error';
            } else {
                $stmt = $db->prepare("UPDATE categories SET name=?, slug=?, description=?, parent_id=?, sort_order=?, is_active=?, seo_title=?, seo_description=? WHERE id=?");
                $stmt->bind_param('sssiiissi', $name, $slug, $description, $parent_id, $sort_order, $is_active, $seo_title, $seo_description, $id);
                
                if ($stmt->execute()) {
                    $message = 'Category updated successfully!';
                    $messageType = 'success';
                } else {
                    $message = 'Error updating category: ' . $db->error;
                    $messageType = 'error';
                }
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        
        // Check if category has products
        $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $check_stmt->bind_param('i', $id);
        $check_stmt->execute();
        $product_count = $check_stmt->get_result()->fetch_assoc()['count'];
        
        // Check if category has child categories
        $child_stmt = $db->prepare("SELECT COUNT(*) as count FROM categories WHERE parent_id = ?");
        $child_stmt->bind_param('i', $id);
        $child_stmt->execute();
        $child_count = $child_stmt->get_result()->fetch_assoc()['count'];
        
        if ($product_count > 0) {
            $message = "Cannot delete category with $product_count products. Move products first.";
            $messageType = 'error';
        } elseif ($child_count > 0) {
            $message = "Cannot delete category with $child_count subcategories. Delete subcategories first.";
            $messageType = 'error';
        } else {
            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param('i', $id);
            
            if ($stmt->execute()) {
                $message = 'Category deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting category: ' . $db->error;
                $messageType = 'error';
            }
        }
    }
}

// Helper function to check if a category is a child of another
function isChildCategory($db, $parent_id, $child_id) {
    $stmt = $db->prepare("SELECT parent_id FROM categories WHERE id = ?");
    $stmt->bind_param('i', $child_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if (!$result || !$result['parent_id']) {
        return false;
    }
    
    if ($result['parent_id'] == $parent_id) {
        return true;
    }
    
    return isChildCategory($db, $parent_id, $result['parent_id']);
}

// Get categories with hierarchy
$categories_result = $db->query("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count,
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY COALESCE(c.parent_id, c.id), c.sort_order, c.name
");

$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

// Get parent categories for dropdown
$parent_categories = $db->query("SELECT id, name FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY name");

// Get category for editing if ID is provided
$editing_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param('i', $edit_id);
    $stmt->execute();
    $editing_category = $stmt->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management - JINKA Admin</title>
    <link href="../css/style.css" rel="stylesheet">
    <link href="css/admin.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
        // Define critical functions immediately in head to avoid "not defined" errors
        function generateSlugModal(name) {
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9 -]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            const slugInput = document.getElementById('modal_slug');
            if (slugInput) {
                slugInput.value = slug;
            }
        }
        
        function loadCategoryDataModal(categoryId) {
            fetch(`get_categories.php?id=${categoryId}`, {
                credentials: 'include'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.category) {
                    const category = result.category;
                    document.getElementById('modal_category_id').value = category.id;
                    document.getElementById('modal_name').value = category.name;
                    document.getElementById('modal_slug').value = category.slug || '';
                    document.getElementById('modal_description').value = category.description || '';
                    document.getElementById('modal_parent_id').value = category.parent_id || '';
                    document.getElementById('modal_sort_order').value = category.sort_order || 0;
                    document.getElementById('modal_seo_title').value = category.seo_title || '';
                    document.getElementById('modal_seo_description').value = category.seo_description || '';
                    document.getElementById('modal_is_active').checked = category.is_active == 1;
                }
            })
            .catch(error => console.error('Failed to load category:', error));
        }
        
        function openCategoryModal(categoryId = null) {
            // This will be properly implemented when full script loads
            // But makes the function available immediately for onclick attributes
            const modal = document.getElementById('categoryModal');
            if (!modal) {
                console.log('Modal not ready, waiting for DOM...');
                document.addEventListener('DOMContentLoaded', function() {
                    openCategoryModal(categoryId);
                });
                return;
            }
            
            const title = document.getElementById('categoryModalTitle');
            const saveText = document.getElementById('categorySaveText');
            const form = document.getElementById('categoryModalForm');
            
            form.reset();
            document.getElementById('modal_category_id').value = '';
            document.getElementById('modal_is_active').checked = true;
            document.getElementById('modal_sort_order').value = 0;
            
            if (categoryId) {
                title.innerHTML = '<i class="fas fa-edit"></i> Edit Category';
                saveText.textContent = 'Update Category';
                document.getElementById('modal_action').value = 'update';
                loadCategoryDataModal(categoryId);
            } else {
                title.innerHTML = '<i class="fas fa-plus"></i> Add New Category';
                saveText.textContent = 'Create Category';
                document.getElementById('modal_action').value = 'create';
            }
            
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10);
            setTimeout(() => document.getElementById('modal_name').focus(), 100);
        }
        
        function closeCategoryModal() {
            const modal = document.getElementById('categoryModal');
            if (modal) {
                modal.classList.remove('show');
                setTimeout(() => modal.style.display = 'none', 300);
            }
        }
        
        // Make available globally
        window.openCategoryModal = openCategoryModal;
        window.closeCategoryModal = closeCategoryModal;
        window.generateSlugModal = generateSlugModal;
        window.loadCategoryDataModal = loadCategoryDataModal;
    </script>
    <style>
        /* Modern Category Management Styles */
        .container {
            width: 100%;
            padding: 0 1rem;
        }

        /* Force full width layout */
        .admin-main {
            width: 100% !important;
            max-width: none !important;
        }

        .categories-header,
        .bulk-actions-bar,
        .search-filter-container,
        .categories-list {
            width: 100%;
            max-width: none !important;
        }

        .category-management {
            background: #f8fafc;
            min-height: 100vh;
        }

        /* Categories Action Bar */
        .categories-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
        }

        .header-info h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-info h2 i {
            font-size: 0.875rem;
        }

        .header-info p {
            margin: 0.25rem 0 0 0;
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .count-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.125rem 0.5rem;
            border-radius: 12px;
            font-size: 0.6875rem;
            font-weight: 500;
        }

        .add-category-btn {
            background: white;
            color: #667eea;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .add-category-btn i {
            font-size: 0.75rem;
            margin-right: 0.25rem;
        }

        .add-category-btn:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        /* Bulk Actions Bar - No Container */
        .bulk-actions-bar {
            background: #eff6ff;
            border: 1px solid #3b82f6;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .bulk-actions-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .selected-count {
            font-weight: 600;
            color: #1e40af;
            font-size: 1rem;
        }

        .bulk-actions-buttons {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .bulk-btn {
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .bulk-btn-activate {
            background: #10b981;
            color: white;
        }

        .bulk-btn-deactivate {
            background: #f59e0b;
            color: white;
        }

        .bulk-btn-delete {
            background: #ef4444;
            color: white;
        }

        .bulk-btn-clear {
            background: #6b7280;
            color: white;
        }

        .bulk-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        /* Search and Filter Container - No Container */
        .search-filter-container {
            padding: 1.5rem;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            border: 1px solid #e5e7eb;
        }

        .search-filter-content {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
        }

        .select-all-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            white-space: nowrap;
            font-weight: 500;
            cursor: pointer;
        }

        .select-all-label input[type="checkbox"] {
            transform: scale(1.1);
            cursor: pointer;
        }

        .search-box {
            flex: 1;
            position: relative;
        }

        .filter-select {
            min-width: 150px;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            font-size: 0.875rem;
        }

        /* Categories List - No Container */
        .categories-list {
            /* No background, no borders, no container styling */
        }

        .category-form-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
            position: sticky;
            top: 2rem;
        }

        .form-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .form-header h3 {
            margin: 0;
            font-size: 1.25rem;
        }

        .form-body {
            padding: 2.5rem;
        }

        .form-group {
            margin-bottom: 2rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.2s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s ease;
        }

        .btn-secondary:hover {
            background: #4b5563;
        }

        .categories-list-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .list-header {
            background: #f9fafb;
            padding: 2rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-filter-section {
            margin-bottom: 2rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            padding: 0 2rem;
            padding-top: 2rem;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            background: white;
            min-width: 150px;
        }

        .category-item {
            padding: 2rem;
            border-bottom: 1px solid #f3f4f6;
            transition: all 0.3s ease;
            cursor: move;
            position: relative;
        }

        .category-item:hover {
            background-color: #fafbfc;
            border-left: 4px solid #667eea;
            padding-left: 1.9rem;
        }

        .category-item:last-child {
            border-bottom: none;
        }

        .category-item.dragging {
            opacity: 0.5;
            transform: rotate(5deg);
            z-index: 1000;
        }

        .category-item.drag-over {
            border-top: 3px solid #667eea;
            background-color: #eff6ff;
        }

        .drag-handle {
            position: absolute;
            left: 0.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            cursor: grab;
            font-size: 1.25rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .category-item:hover .drag-handle {
            opacity: 1;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .category-main {
            display: grid;
            grid-template-columns: auto 1fr auto auto;
            gap: 2rem;
            align-items: center;
        }

        .category-selection {
            display: flex;
            align-items: center;
        }

        .category-info {
            flex: 1;
            min-width: 0;
        }

        .category-name {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-hierarchy {
            color: #6b7280;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-description {
            color: #4b5563;
            font-size: 0.875rem;
            line-height: 1.5;
            margin-bottom: 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .category-meta {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            justify-content: center;
            min-width: 400px;
        }

        .meta-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.875rem;
            color: #6b7280;
            background: #f8fafc;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            min-width: 80px;
            text-align: center;
        }

        .meta-item i {
            color: #667eea;
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }

        .meta-value {
            font-weight: 600;
            color: #1f2937;
        }

        .meta-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .category-status {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
            min-width: 100px;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            align-items: center;
            min-width: 140px;
            justify-content: flex-end;
        }

        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            align-items: flex-end;
            min-width: 120px;
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.75rem;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background: #3b82f6;
            color: white;
        }

        .btn-edit:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }

        .btn-delete {
            background: #ef4444;
            color: white;
        }

        .btn-delete:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-delete:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border-left: 4px solid #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .form-section {
            margin: 2rem 0;
            padding-top: 2rem;
            border-top: 1px solid #e5e7eb;
        }

        .form-section h4 {
            margin: 0 0 1rem 0;
            color: #374151;
            font-size: 1rem;
            font-weight: 600;
        }

        .slug-preview {
            font-family: monospace;
            background: #f3f4f6;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.875rem;
            color: #4b5563;
            margin-top: 0.25rem;
        }

        @media (max-width: 1024px) {
            .categories-header {
                flex-direction: column;
                gap: 1.5rem;
                text-align: center;
            }

            .search-filter-content {
                flex-direction: column;
                gap: 1rem;
            }

            .bulk-actions-content {
                flex-direction: column;
                gap: 1rem;
            }

            .bulk-actions-buttons {
                justify-content: center;
            }
            
            .category-main {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .category-meta {
                min-width: auto;
                justify-content: flex-start;
                flex-wrap: wrap;
            }
        }

        @media (max-width: 768px) {
            .categories-header {
                padding: 1.5rem;
            }

            .search-filter-container {
                padding: 1.5rem;
            }

            .bulk-actions-bar {
                padding: 1.5rem;
            }

            .search-filter-content {
                align-items: stretch;
            }

            .filter-select {
                min-width: auto;
            }

            .page-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .category-main {
                grid-template-columns: 1fr;
                gap: 1rem;
                text-align: left;
            }
            
            .category-meta {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
                min-width: auto;
            }
            
            .category-selection {
                order: -1;
            }
            
            .category-status {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                min-width: auto;
            }
            
            .action-buttons {
                flex-direction: row;
                justify-content: flex-start;
                min-width: auto;
            }
            
            .action-buttons {
                flex-direction: row;
                justify-content: flex-start;
            }
            
            .page-header {
                padding: 2rem 0;
            }
            
            .page-header h1 {
                font-size: 2rem;
            }
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            opacity: 0;
            transition: all 0.3s ease;
        }

        .modal-overlay.show {
            opacity: 1;
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            max-width: 800px;
            width: 100%;
            max-height: 90vh;
            overflow: hidden;
            transform: scale(0.7) translateY(50px);
            transition: all 0.3s ease;
        }

        .modal-overlay.show .modal-container {
            transform: scale(1) translateY(0);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 600;
        }

        .modal-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s ease;
        }

        .modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .modal-body {
            padding: 2.5rem;
            max-height: 60vh;
            overflow-y: auto;
        }

        .modal-footer {
            padding: 2rem;
            background: #f8fafc;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-title {
            margin: 0 0 1.5rem 0;
            color: #374151;
            font-size: 1.125rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-help {
            color: #6b7280;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }

        .char-counter {
            font-size: 0.75rem;
            color: #6b7280;
            margin-top: 0.25rem;
            text-align: right;
        }

        .char-counter.warning {
            color: #f59e0b;
        }

        .char-counter.danger {
            color: #ef4444;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .checkbox-label:hover {
            color: #667eea;
        }

        @media (max-width: 768px) {
            .modal-container {
                margin: 1rem;
                max-width: calc(100% - 2rem);
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body class="category-management">
    <div class="admin-container">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Content -->
            <div class="container">
                <?php if ($message): ?>
                    <div class="alert alert-<?= $messageType ?>">
                        <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                        <?= htmlspecialchars($message) ?>
                    </div>
                <?php endif; ?>

                <!-- Categories Management Section -->
                <!-- Header with Add Button -->
                <div class="categories-header">
                    <div class="header-info">
                        <h2>
                            <i class="fas fa-list"></i> 
                            Categories <span class="count-badge"><?= count($categories) ?> total</span>
                        </h2>
                        <p>Manage and organize your product categories</p>
                    </div>
                    <button onclick="openCategoryModal()" class="btn-primary add-category-btn">
                        <i class="fas fa-plus"></i> Add New Category
                    </button>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulkActions" class="bulk-actions-bar" style="display: none;">
                    <div class="bulk-actions-content">
                        <span id="selectedCount" class="selected-count">0 categories selected</span>
                        <div class="bulk-actions-buttons">
                            <button onclick="bulkActivate()" class="bulk-btn bulk-btn-activate">
                                <i class="fas fa-eye"></i> Activate
                            </button>
                            <button onclick="bulkDeactivate()" class="bulk-btn bulk-btn-deactivate">
                                <i class="fas fa-eye-slash"></i> Deactivate
                            </button>
                            <button onclick="bulkDelete()" class="bulk-btn bulk-btn-delete">
                                <i class="fas fa-trash"></i> Delete Selected
                            </button>
                            <button onclick="clearSelection()" class="bulk-btn bulk-btn-clear">
                                <i class="fas fa-times"></i> Clear
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Search and Filter -->
                <div class="search-filter-container">
                    <div class="search-filter-content">
                        <label class="select-all-label">
                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            <span>Select All</span>
                        </label>
                        <div class="search-box">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" id="categorySearch" class="search-input" 
                                   placeholder="Search categories..." onkeyup="filterCategories()">
                        </div>
                        <select id="statusFilter" class="filter-select" onchange="filterCategories()">
                            <option value="">All Status</option>
                            <option value="active">Active Only</option>
                            <option value="inactive">Inactive Only</option>
                        </select>
                        <select id="typeFilter" class="filter-select" onchange="filterCategories()">
                            <option value="">All Types</option>
                            <option value="root">Root Categories</option>
                            <option value="child">Subcategories</option>
                        </select>
                    </div>
                </div>

                <!-- Categories List -->
                <div class="categories-list" id="categoriesList">
                                <?php foreach ($categories as $category): ?>
                                    <div class="category-item" 
                                         data-id="<?= $category['id'] ?>"
                                         data-name="<?= strtolower($category['name']) ?>"
                                         data-status="<?= $category['is_active'] ? 'active' : 'inactive' ?>"
                                         data-type="<?= $category['parent_id'] ? 'child' : 'root' ?>"
                                         data-sort="<?= $category['sort_order'] ?>"
                                         draggable="true">
                                        <div class="drag-handle">
                                            <i class="fas fa-grip-vertical"></i>
                                        </div>
                                        <div class="category-main">
                                            <!-- Selection Checkbox -->
                                            <div class="category-selection">
                                                <input type="checkbox" class="category-checkbox" value="<?= $category['id'] ?>" 
                                                       onchange="updateBulkActions()" style="margin-right: 0.5rem;">
                                            </div>

                                            <!-- Category Info -->
                                            <div class="category-info">
                                                <div class="category-name">
                                                    <?php if ($category['parent_id']): ?>
                                                        <i class="fas fa-level-down-alt" style="color: #6b7280;"></i>
                                                    <?php endif; ?>
                                                    <?= htmlspecialchars($category['name']) ?>
                                                </div>
                                                <?php if ($category['parent_name']): ?>
                                                    <div class="category-hierarchy">
                                                        <i class="fas fa-arrow-up"></i> 
                                                        Under <?= htmlspecialchars($category['parent_name']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($category['description']): ?>
                                                    <div class="category-description">
                                                        <?= htmlspecialchars(substr($category['description'], 0, 120)) ?>
                                                        <?= strlen($category['description']) > 120 ? '...' : '' ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Category Metadata -->
                                            <div class="category-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-box"></i>
                                                    <div class="meta-value"><?= $category['product_count'] ?></div>
                                                    <div class="meta-label">Products</div>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-sitemap"></i>
                                                    <div class="meta-value"><?= $category['child_count'] ?></div>
                                                    <div class="meta-label">Children</div>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-sort"></i>
                                                    <div class="meta-value"><?= $category['sort_order'] ?></div>
                                                    <div class="meta-label">Order</div>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-link"></i>
                                                    <div class="meta-value" style="font-size: 0.75rem; font-family: monospace;">
                                                        <?= htmlspecialchars($category['slug']) ?>
                                                    </div>
                                                    <div class="meta-label">Slug</div>
                                                </div>
                                            </div>

                                            <!-- Category Status & Actions -->
                                            <div class="category-status">
                                                <?php if ($category['is_active']): ?>
                                                    <span class="badge badge-success">
                                                        <i class="fas fa-check"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge badge-danger">
                                                        <i class="fas fa-times"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <div class="action-buttons">
                                                    <button onclick="openCategoryModal(<?= $category['id'] ?>)" 
                                                           class="btn-sm btn-edit" title="Edit Category">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                    <form method="POST" style="display: inline;" 
                                                          onsubmit="return confirm('Are you sure you want to delete this category?')">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                                        <button type="submit" class="btn-sm btn-delete" 
                                                                <?= ($category['product_count'] > 0 || $category['child_count'] > 0) ? 'disabled' : '' ?>
                                                                title="<?= ($category['product_count'] > 0 || $category['child_count'] > 0) ? 'Cannot delete category with products or subcategories' : 'Delete Category' ?>">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (empty($categories)): ?>
                                    <div class="empty-state">
                                        <i class="fas fa-tags"></i>
                                        <h3>No Categories Found</h3>
                                        <p>Create your first category to get started organizing your products.</p>
                                    </div>
                                <?php endif; ?>
                                
                                <div id="noResults" class="empty-state" style="display: none;">
                                    <i class="fas fa-search"></i>
                                    <h3>No Categories Match Your Search</h3>
                                    <p>Try adjusting your search criteria or filters.</p>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>

    <!-- Category Modal -->
    <div id="categoryModal" class="modal-overlay" style="display: none;">
        <div class="modal-container">
            <div class="modal-header">
                <h2 id="categoryModalTitle">
                    <i class="fas fa-plus"></i>
                    Add New Category
                </h2>
                <button type="button" class="modal-close" onclick="closeCategoryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form id="categoryModalForm" class="category-form" onsubmit="return false;">
                    <input type="hidden" id="modal_category_id" name="category_id">
                    <input type="hidden" id="modal_action" name="action" value="create">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_name">
                                <i class="fas fa-tag"></i> Category Name *
                            </label>
                            <input type="text" id="modal_name" name="name" class="form-control" required 
                                   placeholder="Enter category name..."
                                   onkeyup="generateSlugModal(this.value)">
                        </div>

                        <div class="form-group">
                            <label for="modal_slug">
                                <i class="fas fa-link"></i> URL Slug *
                            </label>
                            <input type="text" id="modal_slug" name="slug" class="form-control" required 
                                   placeholder="category-url-slug">
                            <div class="slug-preview" id="modalSlugPreview"></div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="modal_parent_id">
                                <i class="fas fa-sitemap"></i> Parent Category
                            </label>
                            <select id="modal_parent_id" name="parent_id" class="form-control">
                                <option value="">None (Root Category)</option>
                                <?php $parent_categories->data_seek(0); ?>
                                <?php while ($parent = $parent_categories->fetch_assoc()): ?>
                                    <option value="<?= $parent['id'] ?>">
                                        <?= htmlspecialchars($parent['name']) ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="modal_sort_order">
                                <i class="fas fa-sort-numeric-down"></i> Sort Order
                            </label>
                            <input type="number" id="modal_sort_order" name="sort_order" class="form-control"
                                   value="0" placeholder="0">
                            <small class="form-help">Lower numbers appear first</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="modal_description">
                            <i class="fas fa-align-left"></i> Description
                        </label>
                        <textarea id="modal_description" name="description" class="form-control" rows="3"
                                  placeholder="Category description..."></textarea>
                    </div>

                    <!-- SEO Section -->
                    <div class="form-section">
                        <h4 class="section-title">
                            <i class="fas fa-search"></i> SEO Settings
                        </h4>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="modal_seo_title">SEO Title</label>
                                <input type="text" id="modal_seo_title" name="seo_title" class="form-control" maxlength="255"
                                       placeholder="SEO optimized title...">
                                <div class="char-counter" data-max="255">0 / 255 characters</div>
                            </div>

                            <div class="form-group">
                                <label for="modal_seo_description">SEO Description</label>
                                <textarea id="modal_seo_description" name="seo_description" class="form-control" maxlength="255" rows="2"
                                          placeholder="SEO meta description..."></textarea>
                                <div class="char-counter" data-max="255">0 / 255 characters</div>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox-group">
                            <input type="checkbox" id="modal_is_active" name="is_active" value="1" checked>
                            <label for="modal_is_active" class="checkbox-label">
                                <i class="fas fa-eye"></i> Active Category
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn-secondary" onclick="closeCategoryModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" form="categoryModalForm" class="btn-primary" id="categorySaveBtn">
                    <i class="fas fa-save"></i>
                    <span id="categorySaveText">Create Category</span>
                </button>
            </div>
        </div>
    </div>

    <script>
        // =================================================================================
        // SCRIPT EXECUTION STARTS HERE
        // =================================================================================

        // --- Global State ---
        let draggedElement = null;

        // =================================================================================
        // HELPER FUNCTIONS
        // =================================================================================

        /**
         * Displays a notification toast.
         * @param {string} message The message to display.
         * @param {string} type 'info', 'success', or 'error'.
         */
        function showNotification(message, type = 'info') {
            const existing = document.querySelector('.notification-toast');
            if (existing) existing.remove();

            const notification = document.createElement('div');
            const colors = { success: '#10b981', error: '#ef4444', info: '#3b82f6' };
            notification.className = `notification-toast`;
            notification.style.cssText = `
                position: fixed; top: 20px; right: 20px; background: white;
                padding: 1rem 1.5rem; border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 9999;
                max-width: 320px; border-left: 4px solid ${colors[type] || colors.info};
                font-size: 0.9rem;
            `;
            notification.innerHTML = message;
            document.body.appendChild(notification);

            setTimeout(() => notification.remove(), 5000);
        }

        /**
         * Updates the slug preview URL in the modal.
         */
        function updateSlugPreviewModal(slug) {
            const preview = document.getElementById('modalSlugPreview');
            if (preview) {
                preview.textContent = slug ? `URL: /category/${slug}` : '';
                preview.style.display = slug ? 'block' : 'none';
            }
        }

        /**
         * Updates character counters for SEO fields.
         */
        function updateCharCounter(input) {
            const counter = input.parentNode.querySelector('.char-counter');
            if (!counter) return;
            
            const maxLength = parseInt(input.getAttribute('maxlength'), 10);
            const currentLength = input.value.length;
            counter.textContent = `${currentLength} / ${maxLength}`;

            if (currentLength > maxLength) counter.style.color = '#ef4444';
            else if (currentLength > maxLength * 0.9) counter.style.color = '#f59e0b';
            else counter.style.color = '#6b7280';
        }

        // =================================================================================
        // MAIN INITIALIZATION (RUNS ONCE DOM IS LOADED)
        // =================================================================================
        document.addEventListener('DOMContentLoaded', function() {
            
            // --- Modal Form Event Listeners ---
            document.getElementById('categoryModalForm')?.addEventListener('submit', handleModalFormSubmit);
            document.getElementById('modal_name')?.addEventListener('keyup', (e) => {
                generateSlugModal(e.target.value);
                const seoTitle = document.getElementById('modal_seo_title');
                if (seoTitle && !seoTitle.dataset.edited) {
                    seoTitle.value = e.target.value;
                }
            });
            document.getElementById('modal_slug')?.addEventListener('input', (e) => {
                updateSlugPreviewModal(e.target.value);
                validateSlugModal(e.target.value);
            });
            document.getElementById('modal_seo_title')?.addEventListener('input', (e) => {
                e.target.dataset.edited = "true";
            });

            // --- Page-level Event Listeners ---
            document.getElementById('categoryModal')?.addEventListener('click', (e) => {
                if (e.target.id === 'categoryModal') closeCategoryModal();
            });
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closeCategoryModal();
            });
            document.getElementById('selectAll')?.addEventListener('change', toggleSelectAll);
            document.querySelectorAll('.category-checkbox').forEach(cb => cb.addEventListener('change', updateBulkActions));
            document.getElementById('categorySearch')?.addEventListener('keyup', filterCategories);
            document.getElementById('statusFilter')?.addEventListener('change', filterCategories);
            document.getElementById('typeFilter')?.addEventListener('change', filterCategories);

            // --- Initializers ---
            initializeCharCounters();
            initializeDragAndDrop();
            updateBulkActions(); // Initial check on load
            updateCategoryCount(document.querySelectorAll('.category-item').length);
        });

        // =================================================================================
        // FEATURE-SPECIFIC FUNCTIONS
        // =================================================================================

        // --- Form & Validation ---
        function initializeCharCounters() {
            document.querySelectorAll('#modal_seo_title, #modal_seo_description').forEach(input => {
                updateCharCounter(input); // Initial update
                input.addEventListener('input', () => updateCharCounter(input));
            });
        }

        async function handleModalFormSubmit(event) {
            event.preventDefault();
            const form = event.target;
            const saveBtn = document.getElementById('categorySaveBtn');
            const originalText = saveBtn.innerHTML;

            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

            try {
                const formData = new FormData(form);
                const response = await fetch('category_api.php', { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();

                if (result.success) {
                    showNotification(`✅ ${result.message}`, 'success');
                    closeCategoryModal();
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(`❌ Error: ${result.error || 'Failed to save category'}`, 'error');
                }
            } catch (error) {
                console.error('Category save error:', error);
                showNotification('❌ Network error. Please try again.', 'error');
            } finally {
                saveBtn.disabled = false;
                saveBtn.innerHTML = originalText;
            }
        }

        async function validateSlugModal(slug) {
            if (!slug) return;
            const slugInput = document.getElementById('modal_slug');
            try {
                const formData = new FormData();
                formData.append('action', 'check_slug');
                formData.append('slug', slug);
                const editingId = document.getElementById('modal_category_id').value;
                if (editingId) formData.append('id', editingId);

                const response = await fetch('category_api.php', { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();
                
                slugInput.style.borderColor = result.exists ? '#ef4444' : '#10b981';
                if (result.exists) showNotification('⚠️ This slug is already in use', 'warning');

            } catch (error) {
                console.error('Slug validation error:', error);
                slugInput.style.borderColor = '#6b7280';
            }
        }

        // --- Drag and Drop ---
        function initializeDragAndDrop() {
            document.querySelectorAll('.category-item[draggable="true"]').forEach(item => {
                item.addEventListener('dragstart', handleDragStart);
                item.addEventListener('dragend', handleDragEnd);
                item.addEventListener('dragover', handleDragOver);
                item.addEventListener('drop', handleDrop);
                item.addEventListener('dragenter', handleDragEnter);
                item.addEventListener('dragleave', handleDragLeave);
            });
        }
        function handleDragStart(e) {
            draggedElement = this;
            this.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
        }
        function handleDragEnd() {
            this.classList.remove('dragging');
            document.querySelectorAll('.category-item').forEach(item => item.classList.remove('drag-over'));
        }
        function handleDragOver(e) { e.preventDefault(); }
        function handleDragEnter(e) { e.preventDefault(); if (this !== draggedElement) this.classList.add('drag-over'); }
        function handleDragLeave() { this.classList.remove('drag-over'); }
        function handleDrop(e) {
            e.stopPropagation();
            if (draggedElement !== this) {
                updateSortOrders(draggedElement.dataset.id, this.dataset.sort, draggedElement.dataset.sort);
            }
            this.classList.remove('drag-over');
        }
        async function updateSortOrders(draggedId, newSortOrder, oldSortOrder) {
            showNotification('📦 Updating category order...', 'info');
            try {
                const formData = new FormData();
                formData.append('action', 'update_sort');
                formData.append('category_id', draggedId);
                formData.append('new_sort_order', newSortOrder);
                formData.append('old_sort_order', oldSortOrder);

                const response = await fetch('category_api.php', { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();

                if (result.success) {
                    showNotification('✅ Order updated!', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification('❌ Failed to update order', 'error');
                }
            } catch (error) {
                console.error('Sort update error:', error);
                showNotification('❌ Network error during sort', 'error');
            }
        }

        // --- Filtering & Counts ---
        function filterCategories() {
            const search = document.getElementById('categorySearch').value.toLowerCase();
            const status = document.getElementById('statusFilter').value;
            const type = document.getElementById('typeFilter').value;
            const items = document.querySelectorAll('.category-item');
            let visibleCount = 0;

            items.forEach(item => {
                const show = (!search || item.dataset.name.toLowerCase().includes(search)) &&
                             (!status || item.dataset.status === status) &&
                             (!type || item.dataset.type === type);
                item.style.display = show ? 'block' : 'none';
                if (show) visibleCount++;
            });

            document.getElementById('noResults').style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
            updateCategoryCount(visibleCount);
        }
        function updateCategoryCount(count) {
            const header = document.querySelector('.categories-header .count-badge');
            if(header) header.textContent = `${count} of ${document.querySelectorAll('.category-item').length} total`;
        }

        // --- Bulk Actions ---
        function toggleSelectAll() {
            const isChecked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.category-checkbox').forEach(cb => cb.checked = isChecked);
            updateBulkActions();
        }
        function updateBulkActions() {
            const allCBs = document.querySelectorAll('.category-checkbox');
            const checkedCBs = document.querySelectorAll('.category-checkbox:checked');
            const count = checkedCBs.length;
            
            document.getElementById('bulkActions').style.display = count > 0 ? 'block' : 'none';
            document.getElementById('selectedCount').textContent = `${count} category${count !== 1 ? 'ies' : ''} selected`;
            
            const selectAll = document.getElementById('selectAll');
            selectAll.indeterminate = count > 0 && count < allCBs.length;
            selectAll.checked = count > 0 && count === allCBs.length;
        }
        function clearSelection() {
            document.querySelectorAll('.category-checkbox:checked').forEach(cb => cb.checked = false);
            updateBulkActions();
        }
        function getSelectedCategories() {
            return Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
        }
        async function performBulkAction(action, categoryIds) {
            showNotification('⏳ Processing bulk action...', 'info');
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('category_ids', JSON.stringify(categoryIds));
                const response = await fetch('category_api.php', { method: 'POST', body: formData, credentials: 'include' });
                const result = await response.json();
                if (result.success) {
                    showNotification(`✅ ${result.message || 'Bulk action complete!'}`, 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showNotification(`❌ ${result.error || 'Bulk action failed'}`, 'error');
                }
            } catch (error) {
                console.error('Bulk action error:', error);
                showNotification('❌ Network error during bulk action', 'error');
            }
        }
        function bulkActivate() {
            const ids = getSelectedCategories();
            if (ids.length > 0 && confirm(`Activate ${ids.length} selected categories?`)) {
                performBulkAction('bulk_activate', ids);
            }
        }
        function bulkDeactivate() {
            const ids = getSelectedCategories();
            if (ids.length > 0 && confirm(`Deactivate ${ids.length} selected categories?`)) {
                performBulkAction('bulk_deactivate', ids);
            }
        }
        function bulkDelete() {
            const ids = getSelectedCategories();
            if (ids.length > 0 && confirm(`DELETE ${ids.length} selected categories? This cannot be undone.`)) {
                performBulkAction('bulk_delete', ids);
            }
        }
    </script>
</body>
</html>