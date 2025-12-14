<?php
/**
 * Customer Support Tickets Page
 * Create and manage support tickets
 */

define('JINKA_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/CustomerAuth.php';
require_once 'includes/SupportSystem.php';

// Header/Footer Configuration
$site_name = site_setting('site_name', 'ProCut Solutions');
$site_logo = site_setting('site_logo', '');
$site_favicon_setting = trim(site_setting('site_favicon', ''));
$default_favicon_path = 'images/favicon.ico';
$site_favicon = '';
if ($site_favicon_setting !== '') {
    if (preg_match('#^https?://#i', $site_favicon_setting)) {
        $site_favicon = $site_favicon_setting;
    } else {
        $site_favicon = site_url($site_favicon_setting);
    }
} elseif (file_exists(__DIR__ . '/' . $default_favicon_path)) {
    $site_favicon = site_url($default_favicon_path);
}
$site_tagline = site_setting('site_tagline', 'Professional Printing Equipment');
$business_name = $site_name;
$whatsapp_number = site_setting('whatsapp_number', '+255753098911');
$phone = site_setting('contact_phone', '+255753098911');
$phone_number = $phone;
$phone_number_ke = site_setting('contact_phone_ke', '+254716522828');
$email = site_setting('contact_email', 'support@procutsolutions.com');
$whatsapp_number_link = preg_replace('/[^0-9]/', '', $whatsapp_number);

// Footer Configuration
$footer_logo = site_setting('footer_logo', $site_logo);
$footer_about = site_setting('footer_about', 'Professional printing equipment supplier serving Kenya and Tanzania.');
$footer_address = site_setting('footer_address', 'Kenya & Tanzania');
$footer_phone_label_tz = site_setting('footer_phone_label_tz', 'Tanzania');
$footer_phone_label_ke = site_setting('footer_phone_label_ke', 'Kenya');
$footer_hours_weekday = site_setting('footer_hours_weekday', '8:00 AM - 6:00 PM');
$footer_hours_saturday = site_setting('footer_hours_saturday', '9:00 AM - 4:00 PM');
$footer_hours_sunday = site_setting('footer_hours_sunday', 'Closed');
$footer_whatsapp_label = site_setting('footer_whatsapp_label', '24/7 Available');
$footer_copyright = site_setting('footer_copyright', '');
$facebook_url = trim(site_setting('facebook_url', ''));
$instagram_url = trim(site_setting('instagram_url', ''));
$twitter_url = trim(site_setting('twitter_url', ''));
$linkedin_url = trim(site_setting('linkedin_url', ''));

// Check if customer is logged in
$auth = new CustomerAuth($conn);
if (!$auth->isLoggedIn()) {
    redirect('customer-login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
}

$customer = $auth->getCustomerData();
$customer_id = $customer['id'];

$support = new SupportSystem($conn);
$success_message = '';
$error_message = '';

// Handle create ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_ticket') {
    $subject = Security::sanitizeInput($_POST['subject']);
    $category = Security::sanitizeInput($_POST['category']);
    $priority = Security::sanitizeInput($_POST['priority']);
    $message = Security::sanitizeInput($_POST['message']);
    
    if (empty($subject) || empty($message)) {
        $error_message = 'Subject and message are required';
    } else {
        $result = $support->createTicket([
            'customer_id' => $customer_id,
            'guest_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'guest_email' => $customer['email'],
            'guest_phone' => $customer['phone'] ?? '',
            'subject' => $subject,
            'category' => $category,
            'priority' => $priority,
            'message' => $message,
            'sender_name' => $customer['first_name'] . ' ' . $customer['last_name']
        ]);
        
        if ($result['success']) {
            $success_message = "Support ticket created successfully! Ticket #: " . $result['ticket_number'];
        } else {
            if (($result['error'] ?? '') === 'existing_ticket' && !empty($result['ticket_id'])) {
                $_SESSION['support_notice'] = 'You already have an active ticket (#' . ($result['ticket_number'] ?? '') . '). Please continue the conversation there.';
                redirect('customer-ticket-detail.php?id=' . (int)$result['ticket_id']);
            } else {
                $error_message = 'Failed to create ticket: ' . ($result['error'] ?? 'Unknown error');
            }
        }
    }
}

// Get tickets
$status_filter = isset($_GET['status']) ? Security::sanitizeInput($_GET['status']) : 'all';
$stats = $support->getTicketStats($customer_id);

$where_clause = "customer_id = ?";
$params = [$customer_id];
$types = 'i';

if (!empty($status_filter) && $status_filter !== 'all') {
    $where_clause .= " AND status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

$tickets_sql = "SELECT st.*, 
                (SELECT COUNT(*) FROM support_messages sm WHERE sm.ticket_id = st.id AND sm.is_internal = 0) AS reply_count
                FROM support_tickets st
                WHERE {$where_clause}
                ORDER BY created_at DESC";
$tickets_stmt = $conn->prepare($tickets_sql);
$tickets_stmt->bind_param($types, ...$params);
$tickets_stmt->execute();
$tickets_result = $tickets_stmt->get_result();
$tickets = $tickets_result->fetch_all(MYSQLI_ASSOC);
$tickets_stmt->close();

// Status colors and icons
$status_info = [
    'open' => ['color' => '#17a2b8', 'icon' => 'folder-open', 'label' => 'Open'],
    'in_progress' => ['color' => '#ffc107', 'icon' => 'cog', 'label' => 'In Progress'],
    'waiting_customer' => ['color' => '#e83e8c', 'icon' => 'hourglass-half', 'label' => 'Waiting for You'],
    'resolved' => ['color' => '#28a745', 'icon' => 'check-circle', 'label' => 'Resolved'],
    'closed' => ['color' => '#6c757d', 'icon' => 'times-circle', 'label' => 'Closed']
];

$priority_colors = [
    'low' => '#6c757d',
    'medium' => '#ffc107',
    'high' => '#fd7e14',
    'urgent' => '#dc3545'
];

$page_title = 'Support Tickets';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title . ' - ' . $site_name); ?></title>
    <?php if (!empty($site_favicon)): ?>
    <link rel="icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/header-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/theme-variables.php?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/responsive-global.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<style>
body {
    background-color: #f8f9fa;
}

.page-wrapper {
    min-height: calc(100vh - 200px);
    padding-bottom: 60px;
}

.support-container {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.support-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 40px;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.support-header-left h1 {
    margin: 0 0 10px 0;
    font-size: 2rem;
}

.support-header-left p {
    margin: 0;
    opacity: 0.9;
}

.btn-new-ticket {
    background: white;
    color: #ff5900;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: transform 0.2s;
    border: none;
    cursor: pointer;
}

.btn-new-ticket:hover {
    transform: translateY(-2px);
    color: #ff5900;
}

.support-filters {
    background: white;
    padding: 20px;
    border-left: 1px solid #e0e0e0;
    border-right: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 500;
    border: 1px solid #ddd;
    color: #666;
    transition: all 0.2s;
}

.filter-btn:hover {
    border-color: #ff5900;
    color: #ff5900;
}

.filter-btn.active {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    border-color: #ff5900;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-success {
    background: #d1e7dd;
    color: #0f5132;
    border: 1px solid #badbcc;
}

.alert-error {
    background: #f8d7da;
    color: #842029;
    border: 1px solid #f5c2c7;
}

.support-content {
    background: white;
    border: 1px solid #e0e0e0;
    border-top: none;
    border-radius: 0 0 20px 20px;
    padding: 30px;
}

.tickets-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.ticket-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 12px;
    padding: 25px;
    transition: all 0.3s;
    cursor: pointer;
}

.ticket-card:hover {
    border-color: #ff5900;
    box-shadow: 0 5px 20px rgba(102, 126, 234, 0.1);
}

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
    flex-wrap: wrap;
    gap: 15px;
}

.ticket-number {
    font-weight: 600;
    color: #ff5900;
    font-size: 0.9rem;
}

.ticket-subject {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin: 5px 0 10px 0;
}

.ticket-badges {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.ticket-meta {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    color: #666;
    font-size: 0.875rem;
    margin-top: 15px;
}

.ticket-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 4rem;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    color: #666;
    margin-bottom: 10px;
}

.empty-state p {
    color: #999;
    margin-bottom: 30px;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 50px auto;
    padding: 0;
    border-radius: 15px;
    width: 90%;
    max-width: 700px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 25px 30px;
    border-radius: 15px 15px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    background: none;
    border: none;
}

.modal-body {
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    color: #333;
    margin-bottom: 8px;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 1rem;
}

.form-group textarea {
    min-height: 150px;
    resize: vertical;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.btn-submit {
    background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
    color: white;
    padding: 14px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

@media (max-width: 768px) {
    .support-container {
        margin: 20px auto;
    }
    
    .support-header {
        padding: 30px 20px;
        border-radius: 15px 15px 0 0;
        flex-direction: column;
        align-items: flex-start;
    }
    
    .support-header-left h1 {
        font-size: 1.5rem;
    }
    
    .btn-new-ticket {
        width: 100%;
        justify-content: center;
    }
    
    .ticket-header {
        flex-direction: column;
    }
    
    .modal-content {
        margin: 20px;
        width: calc(100% - 40px);
    }
}
</style>

<div class="page-wrapper">
<div class="account-grid">
    <?php include 'includes/customer-sidebar.php'; ?>
    
    <main class="account-main">
    <div class="support-container" style="max-width: 100%; margin: 0; padding: 0;">
    <div class="support-header">
        <div class="support-header-left">
            <h1><i class="fas fa-life-ring"></i> Support Tickets</h1>
            <p>Get help from our support team</p>
        </div>
        <button class="btn-new-ticket" onclick="openModal()">
            <i class="fas fa-plus"></i> Create New Ticket
        </button>
    </div>
    
    <div class="support-filters">
        <a href="?status=all" class="filter-btn <?= $status_filter === 'all' ? 'active' : '' ?>">All Tickets</a>
        <a href="?status=open" class="filter-btn <?= $status_filter === 'open' ? 'active' : '' ?>">Open</a>
        <a href="?status=in_progress" class="filter-btn <?= $status_filter === 'in_progress' ? 'active' : '' ?>">In Progress</a>
        <a href="?status=waiting_customer" class="filter-btn <?= $status_filter === 'waiting_customer' ? 'active' : '' ?>">Waiting for Me</a>
        <a href="?status=resolved" class="filter-btn <?= $status_filter === 'resolved' ? 'active' : '' ?>">Resolved</a>
    </div>
    
    <div class="support-content">
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= esc_html($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?= esc_html($error_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (count($tickets) > 0): ?>
            <div class="tickets-list">
                <?php foreach ($tickets as $ticket): ?>
                    <?php
                    $status = $ticket['status'];
                    $status_details = $status_info[$status] ?? $status_info['open'];
                    $priority = $ticket['priority'];
                    ?>
                    <div class="ticket-card" onclick="window.location.href='customer-ticket-detail.php?id=<?= $ticket['id'] ?>'"
                        <div class="ticket-header">
                            <div>
                                <div class="ticket-number">#<?= esc_html($ticket['ticket_number']) ?></div>
                                <div class="ticket-subject"><?= esc_html($ticket['subject']) ?></div>
                            </div>
                            <div class="ticket-badges">
                                <span class="badge" style="background: <?= $status_details['color'] ?>20; color: <?= $status_details['color'] ?>;">
                                    <i class="fas fa-<?= $status_details['icon'] ?>"></i>
                                    <?= $status_details['label'] ?>
                                </span>
                                <span class="badge" style="background: <?= $priority_colors[$priority] ?>20; color: <?= $priority_colors[$priority] ?>;">
                                    <i class="fas fa-flag"></i>
                                    <?= ucfirst($priority) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="ticket-meta">
                            <span><i class="fas fa-folder"></i> <?= ucfirst($ticket['category']) ?></span>
                            <span><i class="fas fa-comment"></i> <?= $ticket['reply_count'] ?> replies</span>
                            <span><i class="far fa-clock"></i> <?= date('M d, Y', strtotime($ticket['created_at'])) ?></span>
                            <?php if ($ticket['updated_at'] !== $ticket['created_at']): ?>
                                <span><i class="fas fa-sync"></i> Updated <?= date('M d, Y', strtotime($ticket['updated_at'])) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-ticket-alt"></i>
                <h3>No Support Tickets</h3>
                <p>You haven't created any support tickets yet.</p>
                <button class="btn-new-ticket" onclick="openModal()">
                    <i class="fas fa-plus"></i> Create Your First Ticket
                </button>
            </div>
        <?php endif; ?>
    </div>
    </main>
</div>
</div>

<!-- Create Ticket Modal -->
<div id="ticketModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Create Support Ticket</h2>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="create_ticket">
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" name="subject" id="subject" required placeholder="Brief description of your issue">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select name="category" id="category" required>
                            <option value="order">Order Issue</option>
                            <option value="payment">Payment Problem</option>
                            <option value="technical">Technical Support</option>
                            <option value="product">Product Inquiry</option>
                            <option value="general">General Question</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority *</label>
                        <select name="priority" id="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea name="message" id="message" required placeholder="Please describe your issue in detail..."></textarea>
                </div>
                
                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Ticket
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('ticketModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('ticketModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('ticketModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'includes/footer.php'; ?>
</body>
</html>

