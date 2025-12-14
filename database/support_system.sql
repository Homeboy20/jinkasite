-- Support Chat and Ticket System Database Schema

-- Support Tickets Table
CREATE TABLE IF NOT EXISTS `support_tickets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_number` varchar(50) NOT NULL UNIQUE,
  `customer_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `guest_phone` varchar(20) DEFAULT NULL,
  `subject` varchar(255) NOT NULL,
  `category` enum('technical','billing','product','shipping','other') DEFAULT 'other',
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('open','in_progress','waiting_customer','resolved','closed') DEFAULT 'open',
  `assigned_to` int(11) DEFAULT NULL,
  `satisfaction_score` tinyint(1) unsigned DEFAULT NULL,
  `satisfaction_note` text DEFAULT NULL,
  `satisfaction_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `last_response_at` timestamp NULL DEFAULT NULL,
  `last_response_by` enum('customer','agent') DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_customer_id` (`customer_id`),
  KEY `idx_assigned_to` (`assigned_to`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Messages Table
CREATE TABLE IF NOT EXISTS `support_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `sender_type` enum('customer','agent','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `is_internal` tinyint(1) DEFAULT 0 COMMENT 'Internal notes visible only to agents',
  `attachments` text DEFAULT NULL COMMENT 'JSON array of attachment file paths',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_by_customer` tinyint(1) DEFAULT 0,
  `read_by_agent` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_ticket_id` (`ticket_id`),
  KEY `idx_sender_type` (`sender_type`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Live Chat Sessions Table
CREATE TABLE IF NOT EXISTS `live_chat_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` varchar(100) NOT NULL UNIQUE,
  `customer_id` int(11) DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `guest_email` varchar(100) DEFAULT NULL,
  `visitor_ip` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `status` enum('active','waiting','ended','transferred') DEFAULT 'waiting',
  `assigned_to` int(11) DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ended_at` timestamp NULL DEFAULT NULL,
  `rating` tinyint(1) DEFAULT NULL COMMENT '1-5 star rating',
  `feedback` text DEFAULT NULL,
  `page_url` varchar(500) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_chat_customer_id` (`customer_id`),
  KEY `idx_chat_assigned_to` (`assigned_to`),
  KEY `idx_chat_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Live Chat Messages Table
CREATE TABLE IF NOT EXISTS `live_chat_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `session_id` int(11) NOT NULL,
  `sender_type` enum('customer','agent','system') NOT NULL,
  `sender_id` int(11) DEFAULT NULL,
  `sender_name` varchar(100) DEFAULT NULL,
  `message` text NOT NULL,
  `message_type` enum('text','file','image','system') DEFAULT 'text',
  `attachment_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_msg_session_id` (`session_id`),
  KEY `idx_msg_sender_type` (`sender_type`),
  KEY `idx_msg_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support FAQ Table
CREATE TABLE IF NOT EXISTS `support_faq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `display_order` int(11) DEFAULT 0,
  `views` int(11) DEFAULT 0,
  `helpful_count` int(11) DEFAULT 0,
  `not_helpful_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Canned Responses Table (for quick replies)
CREATE TABLE IF NOT EXISTS `support_canned_responses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(100) NOT NULL,
  `shortcut` varchar(20) DEFAULT NULL COMMENT 'Quick access shortcut like /greeting',
  `message` text NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `usage_count` int(11) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `shortcut` (`shortcut`),
  KEY `is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Agent Status Table
CREATE TABLE IF NOT EXISTS `support_agent_status` (
  `admin_id` int(11) NOT NULL,
  `is_online` tinyint(1) DEFAULT 0,
  `auto_assign` tinyint(1) DEFAULT 1,
  `max_active_tickets` int(11) DEFAULT 3,
  `last_heartbeat` timestamp NULL DEFAULT NULL,
  `sound_enabled` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`admin_id`),
  KEY `idx_agent_online` (`is_online`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Support Ticket Queue Table
CREATE TABLE IF NOT EXISTS `support_ticket_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ticket_id` int(11) NOT NULL,
  `queued_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default canned responses
INSERT INTO `support_canned_responses` (`title`, `shortcut`, `message`, `category`) VALUES
('Greeting', '/hi', 'Hello! Thank you for contacting ProCut Solutions. How can I help you today?', 'general'),
('Thank You', '/thanks', 'Thank you for reaching out! We appreciate your business.', 'general'),
('Product Info Request', '/product', 'I''d be happy to help you with product information. Which plotter model are you interested in?', 'product'),
('Shipping Info', '/shipping', 'We offer free delivery and installation across Kenya, Tanzania, and Uganda. Delivery typically takes 2-3 business days.', 'shipping'),
('Warranty Info', '/warranty', 'All our JINKA plotters come with a comprehensive 12-month warranty covering parts and labor. Extended warranty options are also available.', 'technical'),
('Technical Support', '/tech', 'I understand you''re experiencing a technical issue. Let me help you resolve this. Can you describe what''s happening?', 'technical'),
('Closing', '/close', 'Is there anything else I can help you with today?', 'general');

-- Insert sample FAQs
INSERT INTO `support_faq` (`category`, `question`, `answer`, `display_order`) VALUES
('Products', 'What cutting plotters do you offer?', 'We specialize in JINKA cutting plotters ranging from 720mm to 1350mm cutting width. All models feature ARM9 processors, USB connectivity, and precision cutting up to 600g force.', 1),
('Products', 'What materials can the plotter cut?', 'Our plotters can cut vinyl, heat transfer vinyl, reflective sheeting, sandblast stencils, cardstock, and other materials up to 600g cutting force.', 2),
('Shipping', 'Do you offer delivery and installation?', 'Yes! We provide FREE delivery and professional installation across Kenya, Tanzania, and Uganda. Our technicians will set up your plotter and train your team.', 3),
('Shipping', 'How long does delivery take?', 'Standard delivery takes 2-3 business days within Kenya and Tanzania. Remote locations may take 3-5 business days.', 4),
('Pricing', 'What payment methods do you accept?', 'We accept M-Pesa, bank transfers, credit/debit cards, and cash on delivery. Flexible payment plans are available for qualified businesses.', 5),
('Technical', 'What software is compatible?', 'Our plotters work with CorelDRAW, Adobe Illustrator, SignMaster, FlexiSign, and other industry-standard design software via USB or serial connection.', 6),
('Warranty', 'What does the warranty cover?', 'Our 12-month warranty covers all mechanical parts, electronics, and labor. This includes free repairs and replacement parts during the warranty period.', 7),
('Support', 'How can I get technical support?', 'We offer 24/7 technical support via phone, WhatsApp, and email. You can also submit a support ticket through your customer account.', 8);
