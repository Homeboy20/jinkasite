<?php
/**
 * Customer Ticket Detail Page
 * View and reply to support tickets
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

if (!empty($_SESSION['support_notice'])) {
    $success_message = $_SESSION['support_notice'];
    unset($_SESSION['support_notice']);
}

// Get ticket ID
$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$ticket_id) {
    redirect('customer-support.php');
}

// Get ticket details
$ticket = $support->getTicketDetails($ticket_id, $customer_id);

if (!$ticket) {
    redirect('customer-support.php');
}

$reply_allowed_statuses = ['open', 'in_progress', 'waiting_customer'];
$can_reply = in_array($ticket['status'], $reply_allowed_statuses, true);
$is_finalized = in_array($ticket['status'], ['resolved', 'closed'], true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_rating') {
    $score = isset($_POST['score']) ? (int)$_POST['score'] : 0;
    $note = Security::sanitizeInput($_POST['note'] ?? '');
    $result = $support->saveTicketSatisfaction($ticket_id, $customer_id, $score, $note);
    if ($result['success']) {
        $success_message = 'Thanks for sharing your feedback!';
        $ticket = $support->getTicketDetails($ticket_id, $customer_id);
    } else {
        $error_message = $result['error'] ?? 'Unable to save your feedback';
    }
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_reply') {
    $message = Security::sanitizeInput($_POST['message']);
    
    if (empty($message)) {
        $error_message = 'Message cannot be empty';
    } else {
        $result = $support->addTicketMessage($ticket_id, [
            'sender_type' => 'customer',
            'sender_id' => $customer_id,
            'sender_name' => $customer['first_name'] . ' ' . $customer['last_name'],
            'message' => $message
        ]);
        
        if ($result['success']) {
            $success_message = 'Reply added successfully';
            // Reload ticket to show new message
            $ticket = $support->getTicketDetails($ticket_id, $customer_id);
        } else {
            $error_message = 'Failed to add reply';
        }
    }
}

// Handle close ticket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'close_ticket') {
    $support->updateTicketStatus($ticket_id, 'closed', $customer_id);
    $success_message = 'Ticket has been closed';
    $ticket = $support->getTicketDetails($ticket_id, $customer_id);
}

$page_title = 'Ticket #' . $ticket['ticket_number'] . ' | ' . $site_name;

// Status badge colors
$status_colors = [
    'open' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
    'in_progress' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'waiting_customer' => ['bg' => '#fce7f3', 'text' => '#9f1239'],
    'resolved' => ['bg' => '#dcfce7', 'text' => '#166534'],
    'closed' => ['bg' => '#f3f4f6', 'text' => '#4b5563']
];

$priority_colors = [
    'low' => ['bg' => '#e0f2fe', 'text' => '#075985'],
    'medium' => ['bg' => '#fef3c7', 'text' => '#92400e'],
    'high' => ['bg' => '#fed7aa', 'text' => '#9a3412'],
    'urgent' => ['bg' => '#fecaca', 'text' => '#991b1b']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <?php if ($site_favicon): ?>
    <link rel="icon" type="image/x-icon" href="<?php echo htmlspecialchars($site_favicon); ?>">
    <?php endif; ?>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/header-modern.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="includes/theme-variables.php">
    <link rel="stylesheet" href="css/responsive-global.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/support-chat.css?v=<?php echo time(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        .ticket-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .ticket-detail-header {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .ticket-title-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1.5rem;
            gap: 1rem;
        }
        
        .ticket-title h1 {
            font-size: 1.75rem;
            color: #1f2937;
            margin: 0 0 0.5rem 0;
        }
        
        .ticket-number {
            font-size: 1rem;
            color: #ff5900;
            font-weight: 600;
        }
        
        .ticket-badges {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .ticket-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .meta-label {
            font-size: 0.8125rem;
            color: #6b7280;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .meta-value {
            font-size: 1rem;
            color: #1f2937;
            font-weight: 600;
        }
        
        .conversation-container {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        
        .conversation-title {
            font-size: 1.25rem;
            color: #1f2937;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .message-thread {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .message-item {
            display: flex;
            gap: 1rem;
        }
        
        .message-item.agent {
            background: #f9fafb;
            padding: 1.25rem;
            border-radius: 12px;
            border-left: 4px solid #ff5900;
        }
        
        .message-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
            flex-shrink: 0;
        }
        
        .message-item.customer .message-avatar {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }
        
        .message-content {
            flex: 1;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 0.5rem;
        }
        
        .message-author {
            font-weight: 700;
            color: #1f2937;
            font-size: 0.9375rem;
        }
        
        .message-time {
            font-size: 0.8125rem;
            color: #6b7280;
        }
        
        .message-body {
            color: #4b5563;
            line-height: 1.7;
            white-space: pre-wrap;
        }
        
        .reply-form {
            background: #f9fafb;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .reply-form textarea {
            width: 100%;
            min-height: 120px;
            padding: 1rem;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-family: inherit;
            font-size: 0.9375rem;
            resize: vertical;
            margin-bottom: 1rem;
        }
        
        .reply-form textarea:focus {
            outline: none;
            border-color: #ff5900;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9375rem;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #ff5900 0%, #e64f00 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 89, 0, 0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #4b5563;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #f9fafb;
        }
        
        .alert {
            padding: 1rem 1.25rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.9375rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #ff5900;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 1.5rem;
            transition: gap 0.2s;
        }
        
        .back-link:hover {
            gap: 0.75rem;
        }
        
        .ticket-closed-notice {
            background: #f3f4f6;
            border: 2px solid #d1d5db;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            color: #4b5563;
            font-weight: 600;
        }

        .feedback-card {
            margin-top: 1.5rem;
            background: #fffaf6;
            border: 1px solid #fed7aa;
            border-radius: 12px;
            padding: 1.5rem;
        }

        .emoji-scale {
            display: flex;
            justify-content: space-between;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .emoji-option {
            flex: 1;
            text-align: center;
        }

        .emoji-option input {
            display: none;
        }

        .emoji-option label {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
            padding: 0.75rem 0.5rem;
            border-radius: 12px;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 600;
            color: #7c2d12;
        }

        .emoji-face {
            font-size: 2rem;
        }

        .emoji-option input:checked + label {
            border-color: #ff5900;
            background: #fff8ef;
            color: #b45309;
            box-shadow: 0 4px 12px rgba(255, 89, 0, 0.15);
        }

        .feedback-card textarea {
            width: 100%;
            margin-top: 1rem;
            min-height: 100px;
            border-radius: 10px;
            border: 1px solid #fbbf24;
            padding: 0.75rem;
        }
        
        @media (max-width: 768px) {
            .ticket-title-row {
                flex-direction: column;
            }
            
            .ticket-meta-grid {
                grid-template-columns: 1fr;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="ticket-detail-container">
        <a href="customer-support.php" class="back-link">
            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z"/>
            </svg>
            Back to Support Tickets
        </a>
        
        <?php if ($success_message): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
        <div class="ticket-detail-header">
            <div class="ticket-title-row">
                <div class="ticket-title">
                    <div class="ticket-number">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></div>
                    <h1><?php echo htmlspecialchars($ticket['subject']); ?></h1>
                </div>
                <div class="ticket-badges">
                    <span class="ticket-status <?php echo $ticket['status']; ?>" 
                          style="background: <?php echo $status_colors[$ticket['status']]['bg']; ?>; 
                                 color: <?php echo $status_colors[$ticket['status']]['text']; ?>;">
                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                    </span>
                    <span class="ticket-category" 
                          style="background: <?php echo $priority_colors[$ticket['priority']]['bg']; ?>; 
                                 color: <?php echo $priority_colors[$ticket['priority']]['text']; ?>;">
                        <?php echo ucfirst($ticket['priority']); ?> Priority
                    </span>
                </div>
            </div>
            
            <div class="ticket-meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Category</span>
                    <span class="meta-value"><?php echo ucfirst($ticket['category']); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Created</span>
                    <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($ticket['created_at'])); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Last Updated</span>
                    <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($ticket['updated_at'])); ?></span>
                </div>
                <?php if ($ticket['resolved_at']): ?>
                <div class="meta-item">
                    <span class="meta-label">Resolved</span>
                    <span class="meta-value"><?php echo date('M j, Y g:i A', strtotime($ticket['resolved_at'])); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="conversation-container">
            <h2 class="conversation-title">Conversation</h2>
            
            <div class="message-thread">
                <?php foreach ($ticket['messages'] as $message): ?>
                <div class="message-item <?php echo $message['sender_type']; ?>">
                    <div class="message-avatar">
                        <?php 
                        if ($message['sender_type'] === 'agent') {
                            echo 'AG';
                        } else {
                            echo strtoupper(substr($message['sender_name'], 0, 2));
                        }
                        ?>
                    </div>
                    <div class="message-content">
                        <div class="message-header">
                            <span class="message-author">
                                <?php echo htmlspecialchars($message['sender_name']); ?>
                                <?php if ($message['sender_type'] === 'agent'): ?>
                                <span style="color: #ff5900; font-size: 0.8125rem; font-weight: 500;"> • Support Agent</span>
                                <?php endif; ?>
                            </span>
                            <span class="message-time"><?php echo date('M j, Y g:i A', strtotime($message['created_at'])); ?></span>
                        </div>
                        <div class="message-body"><?php echo nl2br(htmlspecialchars($message['message'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php if ($can_reply): ?>
            <form method="POST" class="reply-form">
                <input type="hidden" name="action" value="add_reply">
                <textarea 
                    name="message" 
                    placeholder="Type your reply here..." 
                    required
                ></textarea>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                    <?php if ($ticket['status'] === 'resolved'): ?>
                    <button type="submit" name="action" value="close_ticket" class="btn btn-secondary" 
                            onclick="return confirm('Are you sure you want to close this ticket?')">
                        Close Ticket
                    </button>
                    <?php endif; ?>
                </div>
            </form>
            <?php else: ?>
            <div class="ticket-closed-notice">
                <svg width="24" height="24" fill="currentColor" viewBox="0 0 24 24" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                </svg>
                <?php if ($is_finalized): ?>
                    This ticket is <?php echo $ticket['status'] === 'resolved' ? 'resolved' : 'closed'; ?>. Please open a new ticket if you need more help.
                <?php else: ?>
                    This ticket cannot accept new replies right now.
                <?php endif; ?>
                <div style="margin-top:1rem;">
                    <a href="customer-support.php" class="btn btn-primary" style="text-decoration:none;display:inline-flex;align-items:center;gap:0.4rem;">
                        Start a new ticket
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4l1.41 1.41L8.83 10H20v2H8.83l4.58 4.59L12 18l-8-8 8-8z"/></svg>
                    </a>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($is_finalized): ?>
                <?php if (empty($ticket['satisfaction_score'])): ?>
                <div class="feedback-card">
                    <h3 style="margin:0;color:#7c2d12;">How satisfied were you with our help?</h3>
                    <p style="margin:0.35rem 0 1rem 0;color:#9a3412;">Tap a face to rate this ticket. You can also leave an optional note.</p>
                    <form method="POST">
                        <input type="hidden" name="action" value="submit_rating">
                        <div class="emoji-scale">
                            <?php
                            $faces = [
                                1 => ['emoji' => '😭', 'label' => 'Terrible'],
                                2 => ['emoji' => '😕', 'label' => 'Poor'],
                                3 => ['emoji' => '😐', 'label' => 'Okay'],
                                4 => ['emoji' => '🙂', 'label' => 'Good'],
                                5 => ['emoji' => '🤩', 'label' => 'Excellent']
                            ];
                            foreach ($faces as $value => $face): ?>
                                <div class="emoji-option">
                                    <input type="radio" id="face-<?php echo $value; ?>" name="score" value="<?php echo $value; ?>" required>
                                    <label for="face-<?php echo $value; ?>">
                                        <span class="emoji-face"><?php echo $face['emoji']; ?></span>
                                        <span><?php echo $face['label']; ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <textarea name="note" placeholder="Optional: tell us more"></textarea>
                        <div class="form-actions" style="justify-content:flex-start;margin-top:1rem;">
                            <button type="submit" class="btn btn-primary">Submit feedback</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="feedback-card" style="background:#ecfdf5;border-color:#a7f3d0;">
                    <h3 style="margin:0;color:#047857;display:flex;align-items:center;gap:0.5rem;">
                        <span style="font-size:2rem;line-height:1;">
                            <?php
                            $emojiMap = [1=>'😭',2=>'😕',3=>'😐',4=>'🙂',5=>'🤩'];
                            echo $emojiMap[$ticket['satisfaction_score']] ?? '🙂';
                            ?>
                        </span>
                        Thanks for rating this conversation!
                    </h3>
                    <p style="margin:0.35rem 0 0;color:#065f46;">Submitted on <?php echo date('M j, Y g:i A', strtotime($ticket['satisfaction_at'])); ?></p>
                    <?php if (!empty($ticket['satisfaction_note'])): ?>
                    <blockquote style="margin:1rem 0 0;border-left:4px solid #34d399;padding-left:1rem;color:#065f46;">
                        <?php echo nl2br(htmlspecialchars($ticket['satisfaction_note'])); ?>
                    </blockquote>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
