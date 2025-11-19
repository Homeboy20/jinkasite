<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once '../admin/includes/auth.php';
require_once '../admin/includes/Security.php';

header('Content-Type: application/json');

// Require authentication for admin actions
try {
    $auth = requireAuth('admin');
    $currentUser = $auth->getCurrentUser();
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance()->getConnection();
$action = $_POST['action'] ?? '';

/**
 * Update delivery status
 */
if ($action === 'update_status') {
    $delivery_id = (int)$_POST['delivery_id'];
    $status = Security::sanitizeInput($_POST['status']);
    $location = Security::sanitizeInput($_POST['location'] ?? '');
    $notes = Security::sanitizeInput($_POST['notes'] ?? '');
    $latitude = isset($_POST['latitude']) ? (float)$_POST['latitude'] : null;
    $longitude = isset($_POST['longitude']) ? (float)$_POST['longitude'] : null;
    
    // Valid statuses
    $valid_statuses = ['pending', 'assigned', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'failed', 'returned'];
    
    if (!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'error' => 'Invalid status']);
        exit;
    }
    
    $db->begin_transaction();
    
    try {
        // Build update query dynamically based on status
        $timestamp_field = $status . '_at';
        $update_fields = [
            'delivery_status' => $status,
            $timestamp_field => 'NOW()'
        ];
        
        if ($location) {
            $update_fields['current_location'] = $location;
            $update_fields['last_location_update'] = 'NOW()';
        }
        
        if ($latitude && $longitude) {
            $update_fields['latitude'] = $latitude;
            $update_fields['longitude'] = $longitude;
        }
        
        // Build SET clause
        $set_clause = [];
        $params = [];
        $types = '';
        
        foreach ($update_fields as $field => $value) {
            if ($value === 'NOW()') {
                $set_clause[] = "$field = NOW()";
            } else {
                $set_clause[] = "$field = ?";
                $params[] = $value;
                $types .= is_numeric($value) ? 'd' : 's';
            }
        }
        
        $params[] = $delivery_id;
        $types .= 'i';
        
        $query = "UPDATE deliveries SET " . implode(', ', $set_clause) . ", updated_at = NOW() WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        // Insert into status history
        $historyStmt = $db->prepare("
            INSERT INTO delivery_status_history (delivery_id, status, location, notes, updated_by, latitude, longitude)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $updated_by = $currentUser['username'] ?? 'Admin';
        $historyStmt->bind_param('issssdd', $delivery_id, $status, $location, $notes, $updated_by, $latitude, $longitude);
        $historyStmt->execute();
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Delivery status updated successfully',
            'status' => $status
        ]);
        
    } catch (Exception $e) {
        $db->rollback();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit;
}

/**
 * Assign driver to delivery
 */
if ($action === 'assign_driver') {
    $delivery_id = (int)$_POST['delivery_id'];
    $driver_name = Security::sanitizeInput($_POST['driver_name']);
    $driver_phone = Security::sanitizeInput($_POST['driver_phone']);
    $vehicle_number = Security::sanitizeInput($_POST['vehicle_number'] ?? '');
    $estimated_delivery_date = Security::sanitizeInput($_POST['estimated_delivery_date'] ?? '');
    
    try {
        $stmt = $db->prepare("
            UPDATE deliveries 
            SET driver_name = ?, 
                driver_phone = ?, 
                vehicle_number = ?,
                estimated_delivery_date = ?,
                delivery_status = 'assigned',
                assigned_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param('ssssi', $driver_name, $driver_phone, $vehicle_number, $estimated_delivery_date, $delivery_id);
        $stmt->execute();
        
        // Add to history
        $historyStmt = $db->prepare("
            INSERT INTO delivery_status_history (delivery_id, status, notes, updated_by)
            VALUES (?, 'assigned', ?, ?)
        ");
        $notes = "Driver assigned: $driver_name ($driver_phone)";
        $updated_by = $currentUser['username'] ?? 'Admin';
        $historyStmt->bind_param('iss', $delivery_id, $notes, $updated_by);
        $historyStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Driver assigned successfully'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit;
}

/**
 * Get delivery details
 */
if ($action === 'get_delivery') {
    $delivery_id = (int)$_GET['id'];
    
    $stmt = $db->prepare("
        SELECT d.*, o.order_number, o.customer_name, o.customer_email
        FROM deliveries d
        LEFT JOIN orders o ON d.order_id = o.id
        WHERE d.id = ?
    ");
    $stmt->bind_param('i', $delivery_id);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if ($delivery) {
        // Get history
        $historyStmt = $db->prepare("
            SELECT * FROM delivery_status_history
            WHERE delivery_id = ?
            ORDER BY created_at DESC
        ");
        $historyStmt->bind_param('i', $delivery_id);
        $historyStmt->execute();
        $history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $delivery['history'] = $history;
        
        echo json_encode([
            'success' => true,
            'delivery' => $delivery
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Delivery not found']);
    }
    
    exit;
}

/**
 * Create new delivery
 */
if ($action === 'create_delivery') {
    $order_id = (int)$_POST['order_id'];
    $delivery_address = Security::sanitizeInput($_POST['delivery_address']);
    $delivery_instructions = Security::sanitizeInput($_POST['delivery_instructions'] ?? '');
    
    // Generate tracking number
    $tracking_number = 'JINKA-DEL-' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    
    // Check if tracking number exists (unlikely but possible)
    $checkStmt = $db->prepare("SELECT id FROM deliveries WHERE tracking_number = ?");
    $checkStmt->bind_param('s', $tracking_number);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        $tracking_number .= '-' . time();
    }
    
    try {
        $stmt = $db->prepare("
            INSERT INTO deliveries (order_id, tracking_number, delivery_address, delivery_instructions, delivery_status)
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $stmt->bind_param('isss', $order_id, $tracking_number, $delivery_address, $delivery_instructions);
        $stmt->execute();
        
        $delivery_id = $db->insert_id;
        
        // Add to history
        $historyStmt = $db->prepare("
            INSERT INTO delivery_status_history (delivery_id, status, notes, updated_by)
            VALUES (?, 'pending', 'Delivery created', ?)
        ");
        $updated_by = $currentUser['username'] ?? 'Admin';
        $historyStmt->bind_param('is', $delivery_id, $updated_by);
        $historyStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Delivery created successfully',
            'delivery_id' => $delivery_id,
            'tracking_number' => $tracking_number
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    exit;
}

/**
 * Track delivery by tracking number (public endpoint - no auth required)
 */
if ($action === 'track') {
    // Allow public access for tracking
    $tracking_number = Security::sanitizeInput($_GET['tracking_number'] ?? '');
    
    if (!$tracking_number) {
        echo json_encode(['success' => false, 'error' => 'Tracking number required']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT d.*, o.order_number, o.customer_name
        FROM deliveries d
        LEFT JOIN orders o ON d.order_id = o.id
        WHERE d.tracking_number = ?
    ");
    $stmt->bind_param('s', $tracking_number);
    $stmt->execute();
    $delivery = $stmt->get_result()->fetch_assoc();
    
    if ($delivery) {
        // Get history
        $historyStmt = $db->prepare("
            SELECT status, location, notes, created_at
            FROM delivery_status_history
            WHERE delivery_id = ?
            ORDER BY created_at DESC
        ");
        $historyStmt->bind_param('i', $delivery['id']);
        $historyStmt->execute();
        $history = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        $delivery['history'] = $history;
        
        // Remove sensitive information
        unset($delivery['driver_phone'], $delivery['delivery_notes']);
        
        echo json_encode([
            'success' => true,
            'delivery' => $delivery
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Tracking number not found']);
    }
    
    exit;
}

// Invalid action
echo json_encode(['success' => false, 'error' => 'Invalid action']);
?>
