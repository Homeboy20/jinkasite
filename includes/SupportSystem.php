<?php
/**
 * Support System Class
 * Handles tickets, live chat, and customer support functionality
 */

if (!defined('JINKA_ACCESS')) {
    die('Direct access not permitted');
}

class SupportSystem {
    private $conn;
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
    }
    
    /**
     * Create a new support ticket
     */
    public function createTicket($data) {
        $ticket_number = 'TKT-' . strtoupper(substr(uniqid(), -8));

        // Enforce single active ticket per customer/guest
        $existingTicket = $this->findActiveTicketForCustomer($data['customer_id'] ?? null, $data['guest_email'] ?? null);
        if ($existingTicket) {
            return [
                'success' => false,
                'error' => 'existing_ticket',
                'message' => 'You already have an active support ticket. Please continue the conversation there.',
                'ticket_id' => $existingTicket['id'],
                'ticket_number' => $existingTicket['ticket_number'],
                'status' => $existingTicket['status']
            ];
        }
        
        $stmt = $this->conn->prepare("
            INSERT INTO support_tickets 
            (ticket_number, customer_id, guest_name, guest_email, guest_phone, subject, category, priority, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
        ");
        
        $stmt->bind_param('sissssss',
            $ticket_number,
            $data['customer_id'],
            $data['guest_name'],
            $data['guest_email'],
            $data['guest_phone'],
            $data['subject'],
            $data['category'],
            $data['priority']
        );
        
        if ($stmt->execute()) {
            $ticket_id = $stmt->insert_id;
            
            // Add initial message
            if (!empty($data['message'])) {
                $this->addTicketMessage($ticket_id, [
                    'sender_type' => 'customer',
                    'sender_id' => $data['customer_id'],
                    'sender_name' => $data['sender_name'] ?? $data['guest_name'],
                    'message' => $data['message']
                ]);
            }

            $this->handleAutoAssignment($ticket_id);
            
            return ['success' => true, 'ticket_id' => $ticket_id, 'ticket_number' => $ticket_number];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Add message to ticket
     */
    public function addTicketMessage($ticket_id, $data) {
        $ticketContext = $this->getTicketParticipantContext($ticket_id);
        if (!$ticketContext) {
            return ['success' => false, 'error' => 'Ticket not found'];
        }

        $isCustomerReply = ($data['sender_type'] ?? '') === 'customer' && empty($data['is_internal']);
        if ($isCustomerReply && in_array($ticketContext['status'], ['resolved', 'closed'], true)) {
            return [
                'success' => false,
                'error' => 'This ticket is already resolved. Please open a new ticket for further assistance.',
                'code' => 'ticket_closed'
            ];
        }

        $stmt = $this->conn->prepare("
            INSERT INTO support_messages 
            (ticket_id, sender_type, sender_id, sender_name, message, is_internal, attachments)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $is_internal = isset($data['is_internal']) ? (int)$data['is_internal'] : 0;
        $attachments = isset($data['attachments']) ? json_encode($data['attachments']) : null;
        
        $stmt->bind_param('isissis',
            $ticket_id,
            $data['sender_type'],
            $data['sender_id'],
            $data['sender_name'],
            $data['message'],
            $is_internal,
            $attachments
        );
        
        if ($stmt->execute()) {
            // Update ticket's last response info
            $this->conn->query("
                UPDATE support_tickets 
                SET last_response_at = NOW(), 
                    last_response_by = '{$data['sender_type']}'
                WHERE id = $ticket_id
            ");
            
            return ['success' => true, 'message_id' => $stmt->insert_id];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Get customer tickets
     */
    public function getCustomerTickets($customer_id, $status = null) {
        $sql = "SELECT * FROM support_tickets WHERE customer_id = ?";
        $params = [$customer_id];
        $types = 'i';
        
        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= 's';
        }
        
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get ticket details with messages
     */
    public function getTicketDetails($ticket_id, $customer_id = null) {
        // Get ticket
        if ($customer_id) {
            $stmt = $this->conn->prepare("SELECT * FROM support_tickets WHERE id = ? AND customer_id = ?");
            $stmt->bind_param('ii', $ticket_id, $customer_id);
        } else {
            $stmt = $this->conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
            $stmt->bind_param('i', $ticket_id);
        }
        
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();
        
        if (!$ticket) {
            return null;
        }
        
        // Get messages
        $stmt = $this->conn->prepare("
            SELECT * FROM support_messages 
            WHERE ticket_id = ? AND is_internal = 0
            ORDER BY created_at ASC
        ");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $ticket['messages'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        return $ticket;
    }
    
    /**
     * Update ticket status
     */
    public function updateTicketStatus($ticket_id, $status, $customer_id = null) {
        $ticketContext = $this->getTicketParticipantContext($ticket_id);
        if (!$ticketContext) {
            return false;
        }

        if ($customer_id) {
            $stmt = $this->conn->prepare("UPDATE support_tickets SET status = ? WHERE id = ? AND customer_id = ?");
            $stmt->bind_param('sii', $status, $ticket_id, $customer_id);
        } else {
            $stmt = $this->conn->prepare("UPDATE support_tickets SET status = ? WHERE id = ?");
            $stmt->bind_param('si', $status, $ticket_id);
        }
        
        $updated = $stmt->execute();

        if ($updated && in_array($status, ['resolved', 'closed'], true)) {
            $this->conn->query("UPDATE support_tickets SET resolved_at = NOW() WHERE id = $ticket_id");
            $this->endActiveChatsForParticipant($ticketContext['customer_id'] ?? null, $ticketContext['guest_email'] ?? null);
            $this->assignQueuedTicketsToAvailableAgents();
        } elseif ($updated && $status === 'open') {
            $ticket = $this->getTicketById($ticket_id);
            if ($ticket && empty($ticket['assigned_to'])) {
                $this->handleAutoAssignment($ticket_id);
            }
        }
        
        return $updated;
    }
    
    /**
     * Create live chat session
     */
    public function createChatSession($data) {
        $session_id = 'CHAT-' . uniqid();
        
        $stmt = $this->conn->prepare("
            INSERT INTO live_chat_sessions 
            (session_id, customer_id, guest_name, guest_email, visitor_ip, user_agent, status, page_url)
            VALUES (?, ?, ?, ?, ?, ?, 'waiting', ?)
        ");
        
        $stmt->bind_param('sisssss',
            $session_id,
            $data['customer_id'],
            $data['guest_name'],
            $data['guest_email'],
            $data['visitor_ip'],
            $data['user_agent'],
            $data['page_url']
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'session_id' => $session_id, 'id' => $stmt->insert_id];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Add chat message
     */
    public function addChatMessage($session_id, $data) {
        // Get numeric session ID
        $stmt = $this->conn->prepare("SELECT id, status FROM live_chat_sessions WHERE session_id = ?");
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if (!$result) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if (!in_array($result['status'], ['active', 'waiting', 'transferred'], true)) {
            return ['success' => false, 'error' => 'Chat session is closed'];
        }
        
        $numeric_session_id = $result['id'];
        
        $stmt = $this->conn->prepare("
            INSERT INTO live_chat_messages 
            (session_id, sender_type, sender_id, sender_name, message, message_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param('isisss',
            $numeric_session_id,
            $data['sender_type'],
            $data['sender_id'],
            $data['sender_name'],
            $data['message'],
            $data['message_type'] ?? 'text'
        );
        
        if ($stmt->execute()) {
            return ['success' => true, 'message_id' => $stmt->insert_id];
        }
        
        return ['success' => false, 'error' => $stmt->error];
    }
    
    /**
     * Get chat messages
     */
    public function getChatMessages($session_id, $since_id = 0) {
        $stmt = $this->conn->prepare("
            SELECT cm.* 
            FROM live_chat_messages cm
            JOIN live_chat_sessions cs ON cs.id = cm.session_id
            WHERE cs.session_id = ? AND cm.id > ?
            ORDER BY cm.created_at ASC
        ");
        $stmt->bind_param('si', $session_id, $since_id);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get active chat session
     */
    public function getActiveChatSession($customer_id = null, $session_id = null) {
        if ($session_id) {
            $stmt = $this->conn->prepare("
                SELECT * FROM live_chat_sessions 
                WHERE session_id = ? AND status IN ('active', 'waiting')
            ");
            $stmt->bind_param('s', $session_id);
        } elseif ($customer_id) {
            $stmt = $this->conn->prepare("
                SELECT * FROM live_chat_sessions 
                WHERE customer_id = ? AND status IN ('active', 'waiting')
                ORDER BY started_at DESC LIMIT 1
            ");
            $stmt->bind_param('i', $customer_id);
        } else {
            return null;
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    public function getChatSessionByPublicId($session_id) {
        $stmt = $this->conn->prepare("SELECT * FROM live_chat_sessions WHERE session_id = ?");
        $stmt->bind_param('s', $session_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * End chat session
     */
    public function endChatSession($session_id, $rating = null, $feedback = null) {
        $stmt = $this->conn->prepare("
            UPDATE live_chat_sessions 
            SET status = 'ended', ended_at = NOW(), rating = ?, feedback = ?
            WHERE session_id = ?
        ");
        $stmt->bind_param('iss', $rating, $feedback, $session_id);
        return $stmt->execute();
    }
    
    /**
     * Get FAQs by category
     */
    public function getFAQs($category = null) {
        if ($category) {
            $stmt = $this->conn->prepare("
                SELECT * FROM support_faq 
                WHERE is_active = 1 AND category = ?
                ORDER BY display_order ASC, views DESC
            ");
            $stmt->bind_param('s', $category);
        } else {
            $stmt = $this->conn->prepare("
                SELECT * FROM support_faq 
                WHERE is_active = 1
                ORDER BY category, display_order ASC
            ");
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Search FAQs
     */
    public function searchFAQs($query) {
        $search_term = "%$query%";
        $stmt = $this->conn->prepare("
            SELECT * FROM support_faq 
            WHERE is_active = 1 
            AND (question LIKE ? OR answer LIKE ?)
            ORDER BY views DESC
            LIMIT 10
        ");
        $stmt->bind_param('ss', $search_term, $search_term);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Mark FAQ as helpful
     */
    public function markFAQHelpful($faq_id, $helpful = true) {
        $field = $helpful ? 'helpful_count' : 'not_helpful_count';
        $this->conn->query("UPDATE support_faq SET $field = $field + 1 WHERE id = $faq_id");
        return true;
    }
    
    /**
     * Get ticket statistics
     */
    public function getTicketStats($customer_id = null) {
        $where = $customer_id ? "WHERE customer_id = $customer_id" : "";
        
        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed
            FROM support_tickets
            $where
        ");
        
        return $result->fetch_assoc();
    }

    public function saveTicketSatisfaction($ticket_id, $customer_id, $score, $note = null) {
        $score = (int)$score;
        if ($score < 1 || $score > 5) {
            return ['success' => false, 'error' => 'Invalid rating'];
        }

        $stmt = $this->conn->prepare("SELECT status, satisfaction_score FROM support_tickets WHERE id = ? AND customer_id = ?");
        $stmt->bind_param('ii', $ticket_id, $customer_id);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();

        if (!$ticket) {
            return ['success' => false, 'error' => 'Ticket not found'];
        }

        if (!in_array($ticket['status'], ['resolved', 'closed'], true)) {
            return ['success' => false, 'error' => 'Ticket is not resolved'];
        }

        if (!empty($ticket['satisfaction_score'])) {
            return ['success' => false, 'error' => 'Feedback already submitted'];
        }

        $stmt = $this->conn->prepare("UPDATE support_tickets SET satisfaction_score = ?, satisfaction_note = ?, satisfaction_at = NOW() WHERE id = ? AND customer_id = ?");
        $stmt->bind_param('isii', $score, $note, $ticket_id, $customer_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => $stmt->error];
    }

    public function getTicketSatisfactionStats($filters = []) {
        $query = "FROM support_tickets st LEFT JOIN admin_users au ON st.assigned_to = au.id WHERE st.satisfaction_score IS NOT NULL";
        $params = [];
        $types = '';

        [$query, $types, $params] = $this->applySatisfactionFilters($query, $types, $params, $filters);

        $statsSql = "SELECT 
                COUNT(*) as total_rated,
                AVG(st.satisfaction_score) as average_score,
                SUM(CASE WHEN st.satisfaction_score >= 4 THEN 1 ELSE 0 END) as promoters
            $query";
        $stmt = $this->conn->prepare($statsSql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $core = $stmt->get_result()->fetch_assoc() ?: ['total_rated' => 0, 'average_score' => null, 'promoters' => 0];

        $distributionSql = "SELECT st.satisfaction_score as score, COUNT(*) as total $query GROUP BY st.satisfaction_score";
        $stmt = $this->conn->prepare($distributionSql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $distribution = [];
        $distResult = $stmt->get_result();
        while ($row = $distResult->fetch_assoc()) {
            $distribution[(int)$row['score']] = (int)$row['total'];
        }

        $recentSql = "SELECT st.ticket_number, st.subject, st.satisfaction_score, st.satisfaction_note, st.satisfaction_at,
                au.full_name AS agent_name
            $query
            ORDER BY st.satisfaction_at DESC LIMIT 5";
        $stmt = $this->conn->prepare($recentSql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'overall' => [
                'total_rated' => (int)($core['total_rated'] ?? 0),
                'average_score' => $core['average_score'] ? round($core['average_score'], 2) : null,
                'positive_share' => $core['total_rated'] ? round(($core['promoters'] / $core['total_rated']) * 100, 1) : 0
            ],
            'distribution' => $distribution,
            'recent' => $recent
        ];
    }

    public function getTicketSatisfactionList($filters = [], $page = 1, $limit = 25) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit));
        $offset = ($page - 1) * $limit;

        $base = "FROM support_tickets st
            LEFT JOIN customers c ON st.customer_id = c.id
            LEFT JOIN admin_users au ON st.assigned_to = au.id
            WHERE st.satisfaction_score IS NOT NULL";

        $params = [];
        $types = '';
        [$base, $types, $params] = $this->applySatisfactionFilters($base, $types, $params, $filters);

        $countSql = "SELECT COUNT(*) as total $base";
        $stmt = $this->conn->prepare($countSql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;

        $dataSql = "SELECT st.id, st.ticket_number, st.subject, st.category, st.priority, st.status,
                st.satisfaction_score, st.satisfaction_note, st.satisfaction_at,
                c.first_name, c.last_name, c.business_name,
                au.full_name AS agent_name
            $base
            ORDER BY st.satisfaction_at DESC
            LIMIT ? OFFSET ?";
        $dataStmt = $this->conn->prepare($dataSql);
        $dataParams = $params;
        $dataTypes = $types . 'ii';
        $dataParams[] = $limit;
        $dataParams[] = $offset;
        if ($dataParams) {
            $dataStmt->bind_param($dataTypes, ...$dataParams);
        }
        $dataStmt->execute();
        $reviews = $dataStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'reviews' => $reviews,
            'total' => (int)$total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit)
        ];
    }

    private function applySatisfactionFilters($baseQuery, $types, $params, $filters) {
        if (!empty($filters['agent_id'])) {
            $baseQuery .= " AND st.assigned_to = ?";
            $params[] = (int)$filters['agent_id'];
            $types .= 'i';
        }

        if (!empty($filters['score'])) {
            $baseQuery .= " AND st.satisfaction_score = ?";
            $params[] = (int)$filters['score'];
            $types .= 'i';
        }

        if (!empty($filters['date_from'])) {
            $baseQuery .= " AND DATE(st.satisfaction_at) >= ?";
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $baseQuery .= " AND DATE(st.satisfaction_at) <= ?";
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        if (!empty($filters['category'])) {
            $baseQuery .= " AND st.category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }

        return [$baseQuery, $types, $params];
    }

    private function handleAutoAssignment($ticket_id) {
        $ticket = $this->getTicketById($ticket_id);
        if (!$ticket || $ticket['status'] !== 'open') {
            return;
        }

        $agent = $this->findAvailableAgent();
        if ($agent) {
            $this->assignTicket($ticket_id, $agent['admin_id']);
        } else {
            $this->addTicketToQueue($ticket_id);
        }
    }

    private function getTicketById($ticket_id) {
        $stmt = $this->conn->prepare("SELECT * FROM support_tickets WHERE id = ?");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function findAvailableAgent() {
        // First, try to find agents with auto_assign enabled and capacity
        $sql = "SELECT sas.admin_id, sas.max_active_tickets, sas.is_online, sas.auto_assign,
                    COALESCE(active.active_count, 0) AS active_count
                FROM support_agent_status sas
                JOIN admin_users au ON au.id = sas.admin_id AND au.is_active = 1
                LEFT JOIN (
                    SELECT assigned_to, COUNT(*) AS active_count
                    FROM support_tickets
                    WHERE status IN ('open','in_progress','waiting_customer') AND assigned_to IS NOT NULL
                    GROUP BY assigned_to
                ) active ON active.assigned_to = sas.admin_id
                WHERE sas.auto_assign = 1
                  AND (sas.is_online = 1 OR COALESCE(active.active_count, 0) < sas.max_active_tickets)
                ORDER BY sas.is_online DESC, active_count ASC, COALESCE(sas.last_heartbeat, NOW()) DESC
                LIMIT 1";
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        // If no auto-assign agents, find any active support agent or admin with capacity
        $sql = "SELECT au.id AS admin_id, 
                    COALESCE(sas.max_active_tickets, 3) AS max_active_tickets,
                    COALESCE(active.active_count, 0) AS active_count
                FROM admin_users au
                LEFT JOIN support_agent_status sas ON sas.admin_id = au.id
                LEFT JOIN (
                    SELECT assigned_to, COUNT(*) AS active_count
                    FROM support_tickets
                    WHERE status IN ('open','in_progress','waiting_customer') AND assigned_to IS NOT NULL
                    GROUP BY assigned_to
                ) active ON active.assigned_to = au.id
                WHERE au.is_active = 1 
                  AND au.role IN ('support_agent', 'admin', 'manager', 'super_admin')
                  AND COALESCE(active.active_count, 0) < COALESCE(sas.max_active_tickets, 3)
                ORDER BY active_count ASC
                LIMIT 1";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    private function addTicketToQueue($ticket_id) {
        $stmt = $this->conn->prepare("INSERT IGNORE INTO support_ticket_queue (ticket_id) VALUES (?)");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
    }

    private function removeTicketFromQueue($ticket_id) {
        $stmt = $this->conn->prepare("DELETE FROM support_ticket_queue WHERE ticket_id = ?");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
    }

    private function getNextQueuedTicket() {
        $sql = "SELECT q.id, q.ticket_id
            FROM support_ticket_queue q
            JOIN support_tickets st ON st.id = q.ticket_id
            WHERE st.status IN ('open','waiting_customer')
            ORDER BY q.queued_at ASC
            LIMIT 1";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }

    private function assignQueuedTicketsToAvailableAgents() {
        while ($queued = $this->getNextQueuedTicket()) {
            $agent = $this->findAvailableAgent();
            if (!$agent) {
                break;
            }
            $this->assignTicket($queued['ticket_id'], $agent['admin_id']);
        }
    }

    public function getAgentBoard() {
        $sql = "SELECT au.id, au.full_name,
                    COALESCE(sas.is_online, 0) AS is_online,
                    COALESCE(sas.auto_assign, 1) AS auto_assign,
                    COALESCE(sas.max_active_tickets, 3) AS max_active_tickets,
                    COALESCE(sas.sound_enabled, 1) AS sound_enabled,
                    COALESCE(active.active_count, 0) AS active_count
                FROM admin_users au
                LEFT JOIN support_agent_status sas ON sas.admin_id = au.id
                LEFT JOIN (
                    SELECT assigned_to, COUNT(*) AS active_count
                    FROM support_tickets
                    WHERE status IN ('open','in_progress','waiting_customer') AND assigned_to IS NOT NULL
                    GROUP BY assigned_to
                ) active ON active.assigned_to = au.id
                WHERE au.is_active = 1
                ORDER BY au.full_name";

        $agents = [];
        $result = $this->conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $recent = $this->getMostRecentTicketForAgent($row['id']);
                $row['last_ticket_id'] = $recent['id'] ?? null;
                $row['last_ticket_number'] = $recent['ticket_number'] ?? null;
                $agents[] = $row;
            }
        }

        return [
            'agents' => $agents,
            'queue' => $this->getTicketQueue()
        ];
    }

    private function getMostRecentTicketForAgent($agent_id) {
        $stmt = $this->conn->prepare("SELECT id, ticket_number FROM support_tickets WHERE assigned_to = ? AND status IN ('open','in_progress','waiting_customer') ORDER BY updated_at DESC LIMIT 1");
        $stmt->bind_param('i', $agent_id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row ?: [];
    }

    public function getTicketQueue() {
        $sql = "SELECT q.ticket_id, q.queued_at, st.ticket_number, st.subject, st.priority, st.category,
                    COALESCE(c.first_name, c.business_name) AS customer_name
                FROM support_ticket_queue q
                JOIN support_tickets st ON st.id = q.ticket_id
                LEFT JOIN customers c ON st.customer_id = c.id
                WHERE st.status IN ('open','waiting_customer')
                ORDER BY q.queued_at ASC";
        $result = $this->conn->query($sql);
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function updateAgentStatus($admin_id, $data) {
        if (!empty($data['heartbeat'])) {
            $data['last_heartbeat'] = date('Y-m-d H:i:s');
            unset($data['heartbeat']);
        }

        $allowed = ['is_online', 'auto_assign', 'max_active_tickets', 'sound_enabled', 'last_heartbeat'];
        $updated = false;

        foreach ($data as $field => $value) {
            if (!in_array($field, $allowed, true) || $value === null) {
                continue;
            }

            $type = $field === 'last_heartbeat' ? 's' : 'i';
            $stmt = $this->conn->prepare("INSERT INTO support_agent_status (admin_id, $field) VALUES (?, ?) ON DUPLICATE KEY UPDATE $field = VALUES($field)");
            $stmt->bind_param('i' . $type, $admin_id, $value);
            $stmt->execute();
            if ($stmt->affected_rows >= 0) {
                $updated = true;
            }
        }

        if ($updated) {
            $this->assignQueuedTicketsToAvailableAgents();
        }

        return ['success' => $updated];
    }

    public function assignNextQueuedTicket($agent_id = null, $ticket_id = null) {
        if ($ticket_id) {
            $this->removeTicketFromQueue($ticket_id);
            if ($agent_id) {
                return [
                    'success' => (bool)$this->assignTicket($ticket_id, $agent_id),
                    'ticket_id' => $ticket_id,
                    'agent_id' => $agent_id
                ];
            }
            $agent = $this->findAvailableAgent();
            if ($agent) {
                return [
                    'success' => (bool)$this->assignTicket($ticket_id, $agent['admin_id']),
                    'ticket_id' => $ticket_id,
                    'agent_id' => $agent['admin_id']
                ];
            }
            $this->addTicketToQueue($ticket_id);
            return ['success' => false, 'error' => 'No available agent'];
        }

        $queued = $this->getNextQueuedTicket();
        if (!$queued) {
            return ['success' => false, 'error' => 'Queue is empty'];
        }

        $agent = $agent_id ? ['admin_id' => $agent_id] : $this->findAvailableAgent();
        if (!$agent) {
            return ['success' => false, 'error' => 'No available agent'];
        }

        $this->assignTicket($queued['ticket_id'], $agent['admin_id']);
        return ['success' => true, 'ticket_id' => $queued['ticket_id'], 'agent_id' => $agent['admin_id']];
    }

    /**
     * Get tickets for admin with filters
     */
    public function getTicketsAdmin($filters = [], $page = 1, $limit = 25) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['status'])) {
            $statuses = (array)$filters['status'];
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = "st.status IN ($placeholders)";
            foreach ($statuses as $status) {
                $params[] = $status;
                $types .= 's';
            }
        }

        if (!empty($filters['priority'])) {
            $priorities = (array)$filters['priority'];
            $placeholders = implode(',', array_fill(0, count($priorities), '?'));
            $where[] = "st.priority IN ($placeholders)";
            foreach ($priorities as $priority) {
                $params[] = $priority;
                $types .= 's';
            }
        }

        if (!empty($filters['category'])) {
            $categories = (array)$filters['category'];
            $placeholders = implode(',', array_fill(0, count($categories), '?'));
            $where[] = "st.category IN ($placeholders)";
            foreach ($categories as $category) {
                $params[] = $category;
                $types .= 's';
            }
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'st.assigned_to = ?';
            $params[] = (int)$filters['assigned_to'];
            $types .= 'i';
        }

        if (!empty($filters['customer_id'])) {
            $where[] = 'st.customer_id = ?';
            $params[] = (int)$filters['customer_id'];
            $types .= 'i';
        }

        if (!empty($filters['ticket_number'])) {
            $where[] = 'st.ticket_number = ?';
            $params[] = $filters['ticket_number'];
            $types .= 's';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(st.created_at) >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(st.created_at) <= ?';
            $params[] = $filters['date_to'];
            $types .= 's';
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = "(
                st.subject LIKE ? OR
                st.ticket_number LIKE ? OR
                st.guest_name LIKE ? OR
                st.guest_email LIKE ? OR
                c.first_name LIKE ? OR
                c.last_name LIKE ? OR
                c.business_name LIKE ?
            )";
            for ($i = 0; $i < 7; $i++) {
                $params[] = $search;
                $types .= 's';
            }
        }

        $where_clause = $where ? implode(' AND ', $where) : '1=1';

        $base_sql = "FROM support_tickets st
            LEFT JOIN customers c ON st.customer_id = c.id
            LEFT JOIN admin_users au ON st.assigned_to = au.id
            WHERE $where_clause";

        // Count
        $count_sql = "SELECT COUNT(*) as total $base_sql";
        $count_stmt = $this->conn->prepare($count_sql);
        if ($params) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;

        // Data
        $data_sql = "SELECT st.*, 
                au.full_name AS assigned_name,
                c.first_name, c.last_name, c.business_name, c.email AS customer_email, c.phone AS customer_phone
            $base_sql
            ORDER BY st.created_at DESC
            LIMIT ? OFFSET ?";

        $data_stmt = $this->conn->prepare($data_sql);
        $data_params = $params;
        $data_types = $types . 'ii';
        $data_params[] = $limit;
        $data_params[] = $offset;

        if ($data_params) {
            $data_stmt->bind_param($data_types, ...$data_params);
        }
        $data_stmt->execute();
        $tickets = $data_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'tickets' => $tickets,
            'total' => (int)$total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit)
        ];
    }

    /**
     * Get ticket details for admin (includes internal messages)
     */
    public function getTicketDetailsAdmin($ticket_id) {
        $stmt = $this->conn->prepare("
            SELECT st.*, 
                   au.full_name AS assigned_name,
                   c.first_name, c.last_name, c.business_name, c.email AS customer_email, c.phone AS customer_phone
            FROM support_tickets st
            LEFT JOIN customers c ON st.customer_id = c.id
            LEFT JOIN admin_users au ON st.assigned_to = au.id
            WHERE st.id = ?
        ");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $ticket = $stmt->get_result()->fetch_assoc();

        if (!$ticket) {
            return null;
        }

        $stmt = $this->conn->prepare("
            SELECT * FROM support_messages
            WHERE ticket_id = ?
            ORDER BY created_at ASC
        ");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        $ticket['messages'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        if (!empty($ticket['customer_id'])) {
            $ticket['customer_profile'] = $this->buildCustomerProfileContext($ticket['customer_id'], $ticket_id);
        } else {
            $ticket['customer_profile'] = null;
        }

        return $ticket;
    }

    private function findActiveTicketForCustomer($customer_id = null, $guest_email = null) {
        $statuses = "('open','in_progress','waiting_customer')";
        if ($customer_id) {
            $stmt = $this->conn->prepare("SELECT id, ticket_number, status FROM support_tickets WHERE customer_id = ? AND status IN $statuses ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param('i', $customer_id);
        } elseif (!empty($guest_email)) {
            $stmt = $this->conn->prepare("SELECT id, ticket_number, status FROM support_tickets WHERE guest_email = ? AND status IN $statuses ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param('s', $guest_email);
        } else {
            return null;
        }

        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function buildCustomerProfileContext($customer_id, $exclude_ticket_id = null) {
        $profile = [
            'stats' => [
                'total_orders' => 0,
                'last_order_date' => null,
                'open_tickets' => 0,
                'total_tickets' => 0
            ],
            'recent_orders' => [],
            'recent_tickets' => []
        ];

        if (!$customer_id) {
            return $profile;
        }

        // Orders snapshot
        if ($this->tableExists('orders')) {
            $stmt = $this->conn->prepare("SELECT id, order_number, status, total_amount, currency, created_at FROM orders WHERE customer_id = ? ORDER BY created_at DESC LIMIT 3");
            $stmt->bind_param('i', $customer_id);
            $stmt->execute();
            $profile['recent_orders'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $countResult = $this->conn->query("SELECT COUNT(*) as total, MAX(created_at) as last_order FROM orders WHERE customer_id = " . (int)$customer_id);
            if ($countResult) {
                $row = $countResult->fetch_assoc();
                $profile['stats']['total_orders'] = (int)($row['total'] ?? 0);
                $profile['stats']['last_order_date'] = $row['last_order'] ?? null;
            }
        }

        // Ticket summary
        $countTickets = $this->conn->query("SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('open','in_progress','waiting_customer') THEN 1 ELSE 0 END) as open_count
            FROM support_tickets WHERE customer_id = " . (int)$customer_id);
        if ($countTickets) {
            $row = $countTickets->fetch_assoc();
            $profile['stats']['total_tickets'] = (int)($row['total'] ?? 0);
            $profile['stats']['open_tickets'] = (int)($row['open_count'] ?? 0);
        }

        $ticketsStmt = $this->conn->prepare("SELECT id, ticket_number, subject, status, created_at
            FROM support_tickets
            WHERE customer_id = ? AND id != ?
            ORDER BY created_at DESC
            LIMIT 4");
        $excludeId = $exclude_ticket_id ?? 0;
        $ticketsStmt->bind_param('ii', $customer_id, $excludeId);
        $ticketsStmt->execute();
        $profile['recent_tickets'] = $ticketsStmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return $profile;
    }

    private function getTicketParticipantContext($ticket_id) {
        $stmt = $this->conn->prepare("SELECT id, customer_id, guest_email, status FROM support_tickets WHERE id = ?");
        $stmt->bind_param('i', $ticket_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function endActiveChatsForParticipant($customer_id = null, $guest_email = null) {
        $conditions = [];
        $params = [];
        $types = '';

        if (!empty($customer_id)) {
            $conditions[] = 'customer_id = ?';
            $params[] = (int)$customer_id;
            $types .= 'i';
        }

        if (!empty($guest_email)) {
            $conditions[] = 'guest_email = ?';
            $params[] = $guest_email;
            $types .= 's';
        }

        if (!$conditions) {
            return;
        }

        $where = implode(' OR ', $conditions);
        $sql = "UPDATE live_chat_sessions SET status = 'ended', ended_at = COALESCE(ended_at, NOW()) WHERE status IN ('active','waiting') AND ($where)";
        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
    }

    private function tableExists($table) {
        $table = $this->conn->real_escape_string($table);
        $result = $this->conn->query("SHOW TABLES LIKE '$table'");
        return $result && $result->num_rows > 0;
    }

    /**
     * Assign ticket to admin user
     */
    public function assignTicket($ticket_id, $admin_id) {
        $this->removeTicketFromQueue($ticket_id);
        $stmt = $this->conn->prepare("UPDATE support_tickets SET assigned_to = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('ii', $admin_id, $ticket_id);
        return $stmt->execute();
    }

    /**
     * Update ticket priority
     */
    public function updateTicketPriority($ticket_id, $priority) {
        $stmt = $this->conn->prepare("UPDATE support_tickets SET priority = ?, updated_at = NOW() WHERE id = ?");
        $stmt->bind_param('si', $priority, $ticket_id);
        return $stmt->execute();
    }

    /**
     * Support overview stats for admin
     */
    public function getSupportOverviewStats() {
        $stats = [
            'tickets' => ['total' => 0, 'open' => 0, 'waiting_customer' => 0, 'in_progress' => 0, 'resolved' => 0],
            'today' => ['created' => 0, 'closed' => 0],
            'chats' => ['active' => 0, 'waiting' => 0]
        ];

        $result = $this->conn->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open,
                SUM(CASE WHEN status = 'waiting_customer' THEN 1 ELSE 0 END) as waiting_customer,
                SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status IN ('resolved','closed') THEN 1 ELSE 0 END) as resolved
            FROM support_tickets
        ");
        if ($result) {
            $stats['tickets'] = array_merge($stats['tickets'], $result->fetch_assoc());
        }

        $result = $this->conn->query("
            SELECT 
                SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as created,
                SUM(CASE WHEN DATE(resolved_at) = CURDATE() THEN 1 ELSE 0 END) as closed
            FROM support_tickets
        ");
        if ($result) {
            $stats['today'] = array_merge($stats['today'], $result->fetch_assoc());
        }

        $result = $this->conn->query("
            SELECT 
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN status = 'waiting' THEN 1 ELSE 0 END) as waiting
            FROM live_chat_sessions
        ");
        if ($result) {
            $stats['chats'] = array_merge($stats['chats'], $result->fetch_assoc());
        }

        return $stats;
    }

    /**
     * Retrieve live chat sessions for admin
     */
    public function getLiveChatSessions($filters = [], $page = 1, $limit = 25) {
        $page = max(1, (int)$page);
        $limit = max(1, min(100, (int)$limit));
        $offset = ($page - 1) * $limit;

        $where = [];
        $params = [];
        $types = '';

        if (!empty($filters['status'])) {
            $statuses = (array)$filters['status'];
            $placeholders = implode(',', array_fill(0, count($statuses), '?'));
            $where[] = "cs.status IN ($placeholders)";
            foreach ($statuses as $status) {
                $params[] = $status;
                $types .= 's';
            }
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'cs.assigned_to = ?';
            $params[] = (int)$filters['assigned_to'];
            $types .= 'i';
        }

        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = "(
                cs.session_id LIKE ? OR
                cs.guest_name LIKE ? OR
                cs.guest_email LIKE ?
            )";
            for ($i = 0; $i < 3; $i++) {
                $params[] = $search;
                $types .= 's';
            }
        }

        $where_clause = $where ? implode(' AND ', $where) : '1=1';

        $base_sql = "FROM live_chat_sessions cs
            LEFT JOIN customers c ON cs.customer_id = c.id
            LEFT JOIN admin_users au ON cs.assigned_to = au.id
            WHERE $where_clause";

        $count_stmt = $this->conn->prepare("SELECT COUNT(*) as total $base_sql");
        if ($params) {
            $count_stmt->bind_param($types, ...$params);
        }
        $count_stmt->execute();
        $total = $count_stmt->get_result()->fetch_assoc()['total'] ?? 0;

        $data_sql = "SELECT cs.*, 
                au.full_name AS assigned_name,
                c.first_name, c.last_name, c.business_name
            $base_sql
            ORDER BY cs.started_at DESC
            LIMIT ? OFFSET ?";

        $data_stmt = $this->conn->prepare($data_sql);
        $data_params = $params;
        $data_types = $types . 'ii';
        $data_params[] = $limit;
        $data_params[] = $offset;

        if ($data_params) {
            $data_stmt->bind_param($data_types, ...$data_params);
        }
        $data_stmt->execute();
        $sessions = $data_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return [
            'sessions' => $sessions,
            'total' => (int)$total,
            'page' => $page,
            'pages' => (int)ceil($total / $limit)
        ];
    }

    /**
     * Assign or transfer chat session
     */
    public function assignChatSession($session_id, $admin_id) {
        $stmt = $this->conn->prepare("UPDATE live_chat_sessions SET assigned_to = ?, status = 'active' WHERE session_id = ?");
        $stmt->bind_param('is', $admin_id, $session_id);
        return $stmt->execute();
    }

    /**
     * Update chat session status
     */
    public function updateChatStatus($session_id, $status) {
        $stmt = $this->conn->prepare("UPDATE live_chat_sessions SET status = ?, ended_at = CASE WHEN ? IN ('ended','transferred') THEN NOW() ELSE ended_at END WHERE session_id = ?");
        $stmt->bind_param('sss', $status, $status, $session_id);
        return $stmt->execute();
    }

    /**
     * Get canned responses
     */
    public function getCannedResponses($filters = []) {
        $sql = "SELECT * FROM support_canned_responses WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
            $types .= 'i';
        }

        $sql .= " ORDER BY category, title";

        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function saveCannedResponse($data) {
        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare("UPDATE support_canned_responses SET title = ?, shortcut = ?, message = ?, category = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $stmt->bind_param('ssssii', $data['title'], $data['shortcut'], $data['message'], $data['category'], $is_active, $data['id']);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO support_canned_responses (title, shortcut, message, category, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?)");
            $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
            $created_by = $data['created_by'] ?? null;
            $stmt->bind_param('ssssii', $data['title'], $data['shortcut'], $data['message'], $data['category'], $is_active, $created_by);
        }

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $data['id'] ?? $stmt->insert_id];
        }

        return ['success' => false, 'error' => $stmt->error];
    }

    public function deleteCannedResponse($id) {
        $stmt = $this->conn->prepare("DELETE FROM support_canned_responses WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    /**
     * FAQ management helpers
     */
    public function getFAQsAdmin($filters = []) {
        $sql = "SELECT * FROM support_faq WHERE 1=1";
        $params = [];
        $types = '';

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
            $types .= 's';
        }

        if (isset($filters['is_active'])) {
            $sql .= " AND is_active = ?";
            $params[] = (int)$filters['is_active'];
            $types .= 'i';
        }

        $sql .= " ORDER BY category, display_order";

        $stmt = $this->conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getFAQById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM support_faq WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function saveFAQ($data) {
        $display_order = isset($data['display_order']) ? (int)$data['display_order'] : 0;
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;

        if (!empty($data['id'])) {
            $stmt = $this->conn->prepare("UPDATE support_faq SET category = ?, question = ?, answer = ?, display_order = ?, is_active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param('sssiii', $data['category'], $data['question'], $data['answer'], $display_order, $is_active, $data['id']);
        } else {
            $stmt = $this->conn->prepare("INSERT INTO support_faq (category, question, answer, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssii', $data['category'], $data['question'], $data['answer'], $display_order, $is_active);
        }

        if ($stmt->execute()) {
            return ['success' => true, 'id' => $data['id'] ?? $stmt->insert_id];
        }

        return ['success' => false, 'error' => $stmt->error];
    }

    public function deleteFAQ($id) {
        $stmt = $this->conn->prepare("DELETE FROM support_faq WHERE id = ?");
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }
}
