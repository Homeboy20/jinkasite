<?php
/**
 * Email Handler Class
 * 
 * Handles all email notifications for the JINKA Plotter Website
 * including order confirmations, status updates, inquiries, and alerts.
 * 
 * @author ProCut Solutions
 * @version 1.0
 */

if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

class EmailHandler {
    private $from_email;
    private $from_name;
    private $admin_email;
    private $headers;
    
    public function __construct() {
        // Load configuration
        $this->from_email = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@jinkaplotter.com';
        $this->from_name = defined('SITE_NAME') ? SITE_NAME : 'JINKA Plotter';
        $this->admin_email = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : 'admin@jinkaplotter.com';
        
        // Set default headers
        $this->headers = "MIME-Version: 1.0\r\n";
        $this->headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $this->headers .= "From: {$this->from_name} <{$this->from_email}>\r\n";
        $this->headers .= "Reply-To: {$this->admin_email}\r\n";
        $this->headers .= "X-Mailer: PHP/" . phpversion();
    }
    
    /**
     * Send Order Confirmation to Customer
     */
    public function sendOrderConfirmation($order) {
        $to = $order['customer_email'];
        $subject = "Order Confirmation - Order #{$order['id']}";
        
        $message = $this->getEmailTemplate('order_confirmation', [
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'order_date' => date('F j, Y', strtotime($order['created_at'])),
            'total' => number_format($order['total_amount'], 2),
            'currency' => $order['currency'],
            'items' => $order['items'],
            'shipping_address' => $order['shipping_address'],
            'payment_method' => $order['payment_method'],
            'status' => ucfirst(str_replace('_', ' ', $order['status']))
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send New Order Notification to Admin
     */
    public function sendNewOrderNotification($order) {
        $to = $this->admin_email;
        $subject = "New Order Received - Order #{$order['id']}";
        
        $message = $this->getEmailTemplate('admin_new_order', [
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'customer_email' => $order['customer_email'],
            'customer_phone' => $order['customer_phone'],
            'order_date' => date('F j, Y g:i A', strtotime($order['created_at'])),
            'total' => number_format($order['total_amount'], 2),
            'currency' => $order['currency'],
            'items' => $order['items'],
            'shipping_address' => $order['shipping_address'],
            'payment_method' => $order['payment_method']
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Order Status Update to Customer
     */
    public function sendOrderStatusUpdate($order, $oldStatus, $newStatus) {
        $to = $order['customer_email'];
        $subject = "Order Status Update - Order #{$order['id']}";
        
        $statusMessages = [
            'confirmed' => 'Your order has been confirmed and is being prepared.',
            'processing' => 'Your order is currently being processed.',
            'shipped' => 'Great news! Your order has been shipped.',
            'delivered' => 'Your order has been delivered. Thank you for your purchase!',
            'cancelled' => 'Your order has been cancelled as requested.'
        ];
        
        $message = $this->getEmailTemplate('order_status_update', [
            'order_id' => $order['id'],
            'customer_name' => $order['customer_name'],
            'old_status' => ucfirst(str_replace('_', ' ', $oldStatus)),
            'new_status' => ucfirst(str_replace('_', ' ', $newStatus)),
            'status_message' => $statusMessages[$newStatus] ?? 'Your order status has been updated.',
            'total' => number_format($order['total_amount'], 2),
            'currency' => $order['currency']
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Inquiry Confirmation to Customer
     */
    public function sendInquiryConfirmation($inquiry) {
        $to = $inquiry['email'];
        $subject = "We Received Your Inquiry - Reference #{$inquiry['id']}";
        
        $message = $this->getEmailTemplate('inquiry_confirmation', [
            'inquiry_id' => $inquiry['id'],
            'customer_name' => $inquiry['name'],
            'subject' => $inquiry['subject'],
            'message' => nl2br(htmlspecialchars($inquiry['message'])),
            'date' => date('F j, Y g:i A', strtotime($inquiry['created_at']))
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send New Inquiry Notification to Admin
     */
    public function sendNewInquiryNotification($inquiry) {
        $to = $this->admin_email;
        $subject = "New Customer Inquiry - #{$inquiry['id']}";
        
        $message = $this->getEmailTemplate('admin_new_inquiry', [
            'inquiry_id' => $inquiry['id'],
            'customer_name' => $inquiry['name'],
            'customer_email' => $inquiry['email'],
            'customer_phone' => $inquiry['phone'] ?? 'Not provided',
            'subject' => $inquiry['subject'],
            'message' => nl2br(htmlspecialchars($inquiry['message'])),
            'date' => date('F j, Y g:i A', strtotime($inquiry['created_at'])),
            'priority' => $inquiry['priority'] ?? 'normal'
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Contact Form Submission
     */
    public function sendContactForm($data) {
        $to = $this->admin_email;
        $subject = "New Contact Form Submission - {$data['name']}";
        
        $message = $this->getEmailTemplate('contact_form', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? 'Not provided',
            'subject' => $data['subject'] ?? 'General Inquiry',
            'message' => nl2br(htmlspecialchars($data['message'])),
            'date' => date('F j, Y g:i A')
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Low Stock Alert to Admin
     */
    public function sendLowStockAlert($product) {
        $to = $this->admin_email;
        $subject = "Low Stock Alert - {$product['name']}";
        
        $message = $this->getEmailTemplate('low_stock_alert', [
            'product_name' => $product['name'],
            'product_sku' => $product['sku'],
            'current_stock' => $product['stock_quantity'],
            'threshold' => $product['low_stock_threshold'] ?? 10,
            'product_url' => SITE_URL . "/admin/products.php?edit={$product['id']}"
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Password Reset Email
     */
    public function sendPasswordReset($email, $resetToken, $userName) {
        $to = $email;
        $subject = "Password Reset Request";
        
        $resetUrl = SITE_URL . "/admin/reset-password.php?token={$resetToken}";
        
        $message = $this->getEmailTemplate('password_reset', [
            'user_name' => $userName,
            'reset_url' => $resetUrl,
            'expiry_time' => '1 hour'
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Send Inquiry Reply to Customer
     */
    public function sendInquiryReply($inquiry, $reply) {
        $to = $inquiry['email'];
        $subject = "Reply to Your Inquiry - Reference #{$inquiry['id']}";
        
        $message = $this->getEmailTemplate('inquiry_reply', [
            'inquiry_id' => $inquiry['id'],
            'customer_name' => $inquiry['name'],
            'original_message' => nl2br(htmlspecialchars($inquiry['message'])),
            'reply' => nl2br(htmlspecialchars($reply)),
            'date' => date('F j, Y g:i A')
        ]);
        
        return $this->send($to, $subject, $message);
    }
    
    /**
     * Core email sending function
     */
    private function send($to, $subject, $message) {
        try {
            $success = mail($to, $subject, $message, $this->headers);
            
            if ($success) {
                $this->logEmail($to, $subject, 'sent');
                return true;
            } else {
                $this->logEmail($to, $subject, 'failed');
                error_log("Email failed to send to: {$to}");
                return false;
            }
        } catch (Exception $e) {
            error_log("Email exception: " . $e->getMessage());
            $this->logEmail($to, $subject, 'error');
            return false;
        }
    }
    
    /**
     * Log email activity
     */
    private function logEmail($to, $subject, $status) {
        $logFile = __DIR__ . '/../logs/email.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logEntry = sprintf(
            "[%s] TO: %s | SUBJECT: %s | STATUS: %s\n",
            date('Y-m-d H:i:s'),
            $to,
            $subject,
            strtoupper($status)
        );
        
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Get email template
     */
    private function getEmailTemplate($template, $data) {
        $templateFile = __DIR__ . "/email_templates/{$template}.php";
        
        if (file_exists($templateFile)) {
            ob_start();
            extract($data);
            include $templateFile;
            return ob_get_clean();
        }
        
        // Fallback to basic template
        return $this->getBasicTemplate($template, $data);
    }
    
    /**
     * Get basic email template (fallback)
     */
    private function getBasicTemplate($type, $data) {
        $content = '';
        
        switch ($type) {
            case 'order_confirmation':
                $content = "
                    <h2>Thank you for your order!</h2>
                    <p>Dear {$data['customer_name']},</p>
                    <p>Your order has been received and is being processed.</p>
                    <p><strong>Order ID:</strong> #{$data['order_id']}<br>
                    <strong>Total:</strong> {$data['currency']} {$data['total']}</p>
                ";
                break;
                
            case 'inquiry_confirmation':
                $content = "
                    <h2>We received your inquiry</h2>
                    <p>Dear {$data['customer_name']},</p>
                    <p>Thank you for contacting us. We have received your inquiry and will respond shortly.</p>
                    <p><strong>Reference:</strong> #{$data['inquiry_id']}</p>
                ";
                break;
                
            default:
                $content = "<p>Thank you for contacting JINKA Plotter.</p>";
        }
        
        return $this->wrapTemplate($content);
    }
    
    /**
     * Wrap content in email HTML template
     */
    private function wrapTemplate($content) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #1e40af, #0369a1); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { background: #ffffff; padding: 30px; border: 1px solid #e5e7eb; }
                .footer { background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #6b7280; border-radius: 0 0 8px 8px; }
                .button { display: inline-block; padding: 12px 30px; background: #1e40af; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                .info-box { background: #f0f9ff; padding: 15px; border-left: 4px solid #1e40af; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { padding: 10px; text-align: left; border-bottom: 1px solid #e5e7eb; }
                th { background: #f9fafb; font-weight: 600; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$this->from_name}</h1>
                    <p style='margin: 5px 0 0 0; opacity: 0.9;'>Professional Cutting Solutions</p>
                </div>
                <div class='content'>
                    {$content}
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " {$this->from_name}. All rights reserved.</p>
                    <p>If you have any questions, please contact us at {$this->admin_email}</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
