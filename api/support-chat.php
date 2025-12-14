<?php
/**
 * Support Chat API Endpoint
 * Handles live chat and ticket operations
 */

define('JINKA_ACCESS', true);
require_once '../includes/config.php';
require_once '../includes/SupportSystem.php';

header('Content-Type: application/json');

$support = new SupportSystem($conn);
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get customer ID if logged in
$customer_id = null;
if (isset($_SESSION['customer_id'])) {
    $customer_id = $_SESSION['customer_id'];
}

switch ($action) {
    case 'create_chat_session':
        $guest_name = $_POST['guest_name'] ?? ($_SESSION['customer_name'] ?? 'Guest');
        $guest_email = $_POST['guest_email'] ?? ($_SESSION['customer_email'] ?? '');
        
        $result = $support->createChatSession([
            'customer_id' => $customer_id,
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'visitor_ip' => $_SERVER['REMOTE_ADDR'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            'page_url' => $_SERVER['HTTP_REFERER'] ?? ''
        ]);
        
        echo json_encode($result);
        break;
        
    case 'send_message':
        $session_id = $_POST['session_id'] ?? '';
        $message = trim($_POST['message'] ?? '');
        
        if (!$session_id || !$message) {
            echo json_encode(['success' => false, 'error' => 'Missing required fields']);
            break;
        }
        
        $sender_name = $_SESSION['customer_name'] ?? 'Guest';
        
        $result = $support->addChatMessage($session_id, [
            'sender_type' => 'customer',
            'sender_id' => $customer_id,
            'sender_name' => $sender_name,
            'message' => $message,
            'message_type' => 'text'
        ]);
        
        echo json_encode($result);
        break;
        
    case 'get_messages':
        $session_id = $_GET['session_id'] ?? '';
        $since_id = (int)($_GET['since_id'] ?? 0);
        
        if (!$session_id) {
            echo json_encode(['success' => false, 'error' => 'Session ID required']);
            break;
        }
        
        $messages = $support->getChatMessages($session_id, $since_id);
        $session = $support->getChatSessionByPublicId($session_id);
        $session_status = $session['status'] ?? null;
        echo json_encode([
            'success' => true,
            'messages' => $messages,
            'session_status' => $session_status,
            'session_active' => in_array($session_status, ['active','waiting','transferred'], true)
        ]);
        break;
        
    case 'get_session':
        $session_id = $_GET['session_id'] ?? '';
        
        if (!$session_id) {
            echo json_encode(['success' => false, 'error' => 'Session ID required']);
            break;
        }
        
        $session = $support->getChatSessionByPublicId($session_id);
        if (!$session) {
            echo json_encode(['success' => false, 'error' => 'Session not found']);
            break;
        }
        echo json_encode(['success' => true, 'session' => $session]);
        break;
        
    case 'end_chat':
        $session_id = $_POST['session_id'] ?? '';
        $rating = (int)($_POST['rating'] ?? 0);
        $feedback = $_POST['feedback'] ?? '';
        
        if (!$session_id) {
            echo json_encode(['success' => false, 'error' => 'Session ID required']);
            break;
        }
        
        $result = $support->endChatSession($session_id, $rating, $feedback);
        echo json_encode(['success' => $result]);
        break;
        
    case 'create_ticket':
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $category = $_POST['category'] ?? 'other';
        $priority = $_POST['priority'] ?? 'medium';
        
        if (!$subject || !$message) {
            echo json_encode(['success' => false, 'error' => 'Subject and message are required']);
            break;
        }
        
        $guest_name = $_POST['guest_name'] ?? ($_SESSION['customer_name'] ?? '');
        $guest_email = $_POST['guest_email'] ?? ($_SESSION['customer_email'] ?? '');
        $guest_phone = $_POST['guest_phone'] ?? '';
        
        $result = $support->createTicket([
            'customer_id' => $customer_id,
            'guest_name' => $guest_name,
            'guest_email' => $guest_email,
            'guest_phone' => $guest_phone,
            'subject' => $subject,
            'message' => $message,
            'category' => $category,
            'priority' => $priority,
            'sender_name' => $guest_name
        ]);
        
        echo json_encode($result);
        break;
        
    case 'add_ticket_message':
        $ticket_id = (int)($_POST['ticket_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        
        if (!$ticket_id || !$message) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID and message are required']);
            break;
        }
        
        $sender_name = $_SESSION['customer_name'] ?? 'Guest';
        
        $result = $support->addTicketMessage($ticket_id, [
            'sender_type' => 'customer',
            'sender_id' => $customer_id,
            'sender_name' => $sender_name,
            'message' => $message
        ]);
        
        echo json_encode($result);
        break;
        
    case 'get_tickets':
        if (!$customer_id) {
            echo json_encode(['success' => false, 'error' => 'Login required']);
            break;
        }
        
        $status = $_GET['status'] ?? null;
        $tickets = $support->getCustomerTickets($customer_id, $status);
        
        echo json_encode(['success' => true, 'tickets' => $tickets]);
        break;
        
    case 'get_ticket':
        $ticket_id = (int)($_GET['ticket_id'] ?? 0);
        
        if (!$ticket_id) {
            echo json_encode(['success' => false, 'error' => 'Ticket ID required']);
            break;
        }
        
        $ticket = $support->getTicketDetails($ticket_id, $customer_id);
        
        if (!$ticket) {
            echo json_encode(['success' => false, 'error' => 'Ticket not found']);
            break;
        }
        
        echo json_encode(['success' => true, 'ticket' => $ticket]);
        break;
        
    case 'search_faq':
        $query = $_GET['q'] ?? '';
        
        if (!$query) {
            echo json_encode(['success' => false, 'error' => 'Search query required']);
            break;
        }
        
        $results = $support->searchFAQs($query);
        echo json_encode(['success' => true, 'results' => $results]);
        break;
        
    case 'get_faqs':
        $category = $_GET['category'] ?? null;
        $faqs = $support->getFAQs($category);
        
        // Group by category
        $grouped = [];
        foreach ($faqs as $faq) {
            $grouped[$faq['category']][] = $faq;
        }
        
        echo json_encode(['success' => true, 'faqs' => $grouped]);
        break;
        
    case 'mark_faq_helpful':
        $faq_id = (int)($_POST['faq_id'] ?? 0);
        $helpful = $_POST['helpful'] === 'true';
        
        if (!$faq_id) {
            echo json_encode(['success' => false, 'error' => 'FAQ ID required']);
            break;
        }
        
        $support->markFAQHelpful($faq_id, $helpful);
        echo json_encode(['success' => true]);
        break;
        
    case 'get_stats':
        if (!$customer_id) {
            echo json_encode(['success' => false, 'error' => 'Login required']);
            break;
        }
        
        $stats = $support->getTicketStats($customer_id);
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
