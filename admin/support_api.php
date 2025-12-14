<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once 'includes/auth.php';
require_once '../includes/SupportSystem.php';

header('Content-Type: application/json');

$auth = requireAuth('support_agent');
$currentUser = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();
$support = new SupportSystem($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (!$action) {
    echo json_encode(['success' => false, 'error' => 'No action specified']);
    exit;
}

function response($data) {
    echo json_encode($data);
    exit;
}

function sanitizeArrayParam($value) {
    if ($value === null || $value === '') {
        return [];
    }

    $items = is_array($value) ? $value : [$value];
    $sanitized = [];

    foreach ($items as $item) {
        $clean = Security::sanitizeInput($item);
        if ($clean !== '') {
            $sanitized[] = $clean;
        }
    }

    return $sanitized;
}

try {
    switch ($action) {
        case 'tickets.list':
            $filters = [
                'status' => sanitizeArrayParam($_GET['status'] ?? []),
                'priority' => sanitizeArrayParam($_GET['priority'] ?? []),
                'category' => sanitizeArrayParam($_GET['category'] ?? []),
                'assigned_to' => $_GET['assigned_to'] ?? null,
                'customer_id' => $_GET['customer_id'] ?? null,
                'ticket_number' => Security::sanitizeInput($_GET['ticket_number'] ?? ''),
                'search' => Security::sanitizeInput($_GET['search'] ?? ''),
                'date_from' => Security::sanitizeInput($_GET['date_from'] ?? ''),
                'date_to' => Security::sanitizeInput($_GET['date_to'] ?? '')
            ];

            if (empty($filters['assigned_to'])) {
                unset($filters['assigned_to']);
            }
            if (empty($filters['customer_id'])) {
                unset($filters['customer_id']);
            }
            if (empty($filters['ticket_number'])) {
                unset($filters['ticket_number']);
            }
            if (empty($filters['search'])) {
                unset($filters['search']);
            }
            if (empty($filters['date_from'])) {
                unset($filters['date_from']);
            }
            if (empty($filters['date_to'])) {
                unset($filters['date_to']);
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 25);

            $result = $support->getTicketsAdmin($filters, $page, $limit);
            response(['success' => true] + $result);

        case 'tickets.detail':
            $ticket_id = (int)($_GET['ticket_id'] ?? 0);
            if (!$ticket_id) {
                response(['success' => false, 'error' => 'Ticket ID is required']);
            }
            $ticket = $support->getTicketDetailsAdmin($ticket_id);
            if (!$ticket) {
                response(['success' => false, 'error' => 'Ticket not found']);
            }
            response(['success' => true, 'ticket' => $ticket]);

        case 'tickets.reply':
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            $message = trim($_POST['message'] ?? '');
            $is_internal = isset($_POST['internal']) && $_POST['internal'] === '1';

            if (!$ticket_id || empty($message)) {
                response(['success' => false, 'error' => 'Ticket ID and message are required']);
            }

            $result = $support->addTicketMessage($ticket_id, [
                'sender_type' => $is_internal ? 'system' : 'agent',
                'sender_id' => $currentUser['id'],
                'sender_name' => $currentUser['full_name'],
                'message' => $message,
                'is_internal' => $is_internal ? 1 : 0
            ]);

            response($result);

        case 'tickets.assign':
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            $assignee = (int)($_POST['assigned_to'] ?? 0);

            if (!$ticket_id || !$assignee) {
                response(['success' => false, 'error' => 'Ticket ID and assignee are required']);
            }

            $success = $support->assignTicket($ticket_id, $assignee);
            response(['success' => (bool)$success]);

        case 'tickets.update_status':
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            $status = Security::sanitizeInput($_POST['status'] ?? '');

            if (!$ticket_id || !$status) {
                response(['success' => false, 'error' => 'Ticket ID and status are required']);
            }

            $success = $support->updateTicketStatus($ticket_id, $status);
            response(['success' => (bool)$success]);

        case 'tickets.update_priority':
            $ticket_id = (int)($_POST['ticket_id'] ?? 0);
            $priority = Security::sanitizeInput($_POST['priority'] ?? '');

            if (!$ticket_id || !$priority) {
                response(['success' => false, 'error' => 'Ticket ID and priority are required']);
            }

            $success = $support->updateTicketPriority($ticket_id, $priority);
            response(['success' => (bool)$success]);

        case 'tickets.stats':
            $stats = $support->getSupportOverviewStats();
            response(['success' => true, 'stats' => $stats]);

        case 'chats.list':
            $filters = [
                'status' => sanitizeArrayParam($_GET['status'] ?? []),
                'assigned_to' => $_GET['assigned_to'] ?? null,
                'search' => Security::sanitizeInput($_GET['search'] ?? '')
            ];
            if (empty($filters['assigned_to'])) {
                unset($filters['assigned_to']);
            }
            if (empty($filters['search'])) {
                unset($filters['search']);
            }
            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 25);
            $result = $support->getLiveChatSessions($filters, $page, $limit);
            response(['success' => true] + $result);

        case 'chats.messages':
            $session_id = Security::sanitizeInput($_GET['session_id'] ?? '');
            $since_id = (int)($_GET['since_id'] ?? 0);
            if (!$session_id) {
                response(['success' => false, 'error' => 'Session ID is required']);
            }
            $messages = $support->getChatMessages($session_id, $since_id);
            response(['success' => true, 'messages' => $messages]);

        case 'chats.send':
            $session_id = Security::sanitizeInput($_POST['session_id'] ?? '');
            $message = trim($_POST['message'] ?? '');
            if (!$session_id || !$message) {
                response(['success' => false, 'error' => 'Session ID and message are required']);
            }
            $result = $support->addChatMessage($session_id, [
                'sender_type' => 'agent',
                'sender_id' => $currentUser['id'],
                'sender_name' => $currentUser['full_name'],
                'message' => $message,
                'message_type' => 'text'
            ]);
            response($result);

        case 'chats.assign':
            $session_id = Security::sanitizeInput($_POST['session_id'] ?? '');
            $assignee = (int)($_POST['assigned_to'] ?? $currentUser['id']);
            if (!$session_id || !$assignee) {
                response(['success' => false, 'error' => 'Session ID and assignee are required']);
            }
            $success = $support->assignChatSession($session_id, $assignee);
            response(['success' => (bool)$success]);

        case 'chats.update_status':
            $session_id = Security::sanitizeInput($_POST['session_id'] ?? '');
            $status = Security::sanitizeInput($_POST['status'] ?? '');
            if (!$session_id || !$status) {
                response(['success' => false, 'error' => 'Session ID and status are required']);
            }
            $success = $support->updateChatStatus($session_id, $status);
            response(['success' => (bool)$success]);

        case 'faq.list':
            $filters = [
                'category' => Security::sanitizeInput($_GET['category'] ?? ''),
                'is_active' => $_GET['is_active'] ?? null
            ];
            if (empty($filters['category'])) {
                unset($filters['category']);
            }
            if ($filters['is_active'] === null || $filters['is_active'] === '') {
                unset($filters['is_active']);
            }
            $faqs = $support->getFAQsAdmin($filters);
            response(['success' => true, 'faqs' => $faqs]);

        case 'faq.save':
            $data = [
                'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
                'category' => Security::sanitizeInput($_POST['category'] ?? ''),
                'question' => trim($_POST['question'] ?? ''),
                'answer' => trim($_POST['answer'] ?? ''),
                'display_order' => (int)($_POST['display_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1
            ];

            if (empty($data['category']) || empty($data['question']) || empty($data['answer'])) {
                response(['success' => false, 'error' => 'Category, question, and answer are required']);
            }

            $result = $support->saveFAQ($data);
            response($result);

        case 'faq.delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                response(['success' => false, 'error' => 'FAQ ID required']);
            }
            $success = $support->deleteFAQ($id);
            response(['success' => (bool)$success]);

        case 'canned.list':
            $filters = [
                'category' => Security::sanitizeInput($_GET['category'] ?? ''),
                'is_active' => $_GET['is_active'] ?? null
            ];
            if (empty($filters['category'])) {
                unset($filters['category']);
            }
            if ($filters['is_active'] === null || $filters['is_active'] === '') {
                unset($filters['is_active']);
            }
            $responses = $support->getCannedResponses($filters);
            response(['success' => true, 'responses' => $responses]);

        case 'canned.save':
            $data = [
                'id' => !empty($_POST['id']) ? (int)$_POST['id'] : null,
                'title' => Security::sanitizeInput($_POST['title'] ?? ''),
                'shortcut' => Security::sanitizeInput($_POST['shortcut'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
                'category' => Security::sanitizeInput($_POST['category'] ?? 'general'),
                'is_active' => isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1,
                'created_by' => $currentUser['id']
            ];

            if (empty($data['title']) || empty($data['message'])) {
                response(['success' => false, 'error' => 'Title and message are required']);
            }

            $result = $support->saveCannedResponse($data);
            response($result);

        case 'canned.delete':
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) {
                response(['success' => false, 'error' => 'Response ID required']);
            }
            $success = $support->deleteCannedResponse($id);
            response(['success' => (bool)$success]);

        case 'feedback.stats':
            $filters = [
                'agent_id' => !empty($_GET['agent_id']) ? (int)$_GET['agent_id'] : null,
                'score' => !empty($_GET['score']) ? (int)$_GET['score'] : null,
                'date_from' => Security::sanitizeInput($_GET['date_from'] ?? ''),
                'date_to' => Security::sanitizeInput($_GET['date_to'] ?? ''),
                'category' => Security::sanitizeInput($_GET['category'] ?? '')
            ];
            $stats = $support->getTicketSatisfactionStats($filters);
            response(['success' => true, 'stats' => $stats]);

        case 'feedback.list':
            $filters = [
                'agent_id' => !empty($_GET['agent_id']) ? (int)$_GET['agent_id'] : null,
                'score' => !empty($_GET['score']) ? (int)$_GET['score'] : null,
                'date_from' => Security::sanitizeInput($_GET['date_from'] ?? ''),
                'date_to' => Security::sanitizeInput($_GET['date_to'] ?? ''),
                'category' => Security::sanitizeInput($_GET['category'] ?? '')
            ];
            $filters = array_filter($filters, function ($value) {
                return $value !== null && $value !== '';
            });

            $page = (int)($_GET['page'] ?? 1);
            $limit = (int)($_GET['limit'] ?? 25);
            $result = $support->getTicketSatisfactionList($filters, $page, $limit);
            response(['success' => true] + $result);

        case 'agents.board':
            $board = $support->getAgentBoard();
            response([
                'success' => true,
                'agents' => $board['agents'] ?? [],
                'queue' => $board['queue'] ?? []
            ]);

        case 'agents.status':
            $payload = [];
            foreach (['is_online', 'auto_assign', 'max_active_tickets', 'sound_enabled'] as $field) {
                if (isset($_POST[$field])) {
                    $payload[$field] = (int)$_POST[$field];
                }
            }
            if (!empty($_POST['heartbeat'])) {
                $payload['heartbeat'] = 1;
            }

            if (!$payload) {
                response(['success' => false, 'error' => 'No status fields provided']);
            }

            $result = $support->updateAgentStatus($currentUser['id'], $payload);
            response($result);

        case 'queue.assign':
            $ticket_id = isset($_POST['ticket_id']) && $_POST['ticket_id'] !== '' ? (int)$_POST['ticket_id'] : null;
            $agent_id = isset($_POST['agent_id']) && $_POST['agent_id'] !== '' ? (int)$_POST['agent_id'] : null;

            $result = $support->assignNextQueuedTicket($agent_id, $ticket_id);
            response($result);

        default:
            response(['success' => false, 'error' => 'Unknown action']);
    }
} catch (Exception $e) {
    response(['success' => false, 'error' => $e->getMessage()]);
}
