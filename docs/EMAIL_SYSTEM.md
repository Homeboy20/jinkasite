# Email Notification System Documentation

## Overview
The Email Notification System provides automated email notifications for various events in the JINKA Plotter website including orders, inquiries, contact forms, and administrative alerts.

## Features
- ✅ Order confirmation emails to customers
- ✅ New order notifications to admin
- ✅ Order status update notifications
- ✅ Inquiry confirmation emails
- ✅ New inquiry notifications to admin
- ✅ Inquiry reply notifications
- ✅ Contact form submissions
- ✅ Low stock alerts
- ✅ Password reset emails
- ✅ Professional HTML email templates
- ✅ Email activity logging

## Files Structure
```
includes/
├── EmailHandler.php              # Main email handler class
└── email_templates/              # Email templates directory
    ├── order_confirmation.php    # Customer order confirmation
    ├── admin_new_order.php       # Admin new order notification
    ├── order_status_update.php   # Order status change notification
    ├── inquiry_confirmation.php  # Customer inquiry confirmation
    ├── admin_new_inquiry.php     # Admin new inquiry notification
    ├── inquiry_reply.php          # Inquiry reply notification
    ├── contact_form.php          # Contact form submission
    ├── low_stock_alert.php       # Low stock warning
    └── password_reset.php        # Password reset email

logs/
└── email.log                     # Email activity log
```

## Configuration

### Email Settings (includes/config.php)
```php
define('SMTP_FROM_EMAIL', 'noreply@jinkaplotter.com');
define('FROM_EMAIL', 'support@jinkaplotter.com');
define('FROM_NAME', 'JINKA Plotter');
define('SITE_NAME', 'JINKA Plotter');
define('ADMIN_EMAIL', 'admin@jinkaplotter.com');
```

## Usage Examples

### 1. Send Order Confirmation

```php
require_once 'includes/EmailHandler.php';

$emailHandler = new EmailHandler();

$order = [
    'id' => 12345,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'customer_phone' => '+254700000000',
    'created_at' => date('Y-m-d H:i:s'),
    'total_amount' => 50000.00,
    'currency' => 'KES',
    'status' => 'pending',
    'payment_method' => 'mpesa',
    'shipping_address' => "John Doe\n123 Main Street\nNairobi, Kenya",
    'items' => [
        [
            'name' => 'Cutting Plotter Pro',
            'sku' => 'PLOTTER-001',
            'quantity' => 1,
            'price' => 50000.00
        ]
    ]
];

// Send confirmation to customer
$emailHandler->sendOrderConfirmation($order);

// Send notification to admin
$emailHandler->sendNewOrderNotification($order);
```

### 2. Send Order Status Update

```php
$order = [
    'id' => 12345,
    'customer_name' => 'John Doe',
    'customer_email' => 'john@example.com',
    'total_amount' => 50000.00,
    'currency' => 'KES'
];

$emailHandler->sendOrderStatusUpdate($order, 'pending', 'confirmed');
```

### 3. Send Inquiry Confirmation

```php
$inquiry = [
    'id' => 100,
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'phone' => '+255700000000',
    'subject' => 'Product Information Request',
    'message' => 'I would like to know more about your cutting plotters...',
    'created_at' => date('Y-m-d H:i:s'),
    'priority' => 'normal'
];

// Send confirmation to customer
$emailHandler->sendInquiryConfirmation($inquiry);

// Send notification to admin
$emailHandler->sendNewInquiryNotification($inquiry);
```

### 4. Send Contact Form Submission

```php
$contactData = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '+254700000000',
    'subject' => 'General Inquiry',
    'message' => 'I have a question about your services...'
];

$emailHandler->sendContactForm($contactData);
```

### 5. Send Low Stock Alert

```php
$product = [
    'id' => 1,
    'name' => 'Cutting Blade Set',
    'sku' => 'BLADE-001',
    'stock_quantity' => 5,
    'low_stock_threshold' => 10
];

$emailHandler->sendLowStockAlert($product);
```

### 6. Send Password Reset Email

```php
$resetToken = bin2hex(random_bytes(32));
$emailHandler->sendPasswordReset(
    'user@example.com',
    $resetToken,
    'John Doe'
);
```

### 7. Send Inquiry Reply

```php
$inquiry = [
    'id' => 100,
    'name' => 'Jane Smith',
    'email' => 'jane@example.com',
    'message' => 'Original inquiry message...'
];

$reply = 'Thank you for your inquiry. Here is the information you requested...';

$emailHandler->sendInquiryReply($inquiry, $reply);
```

## Integration with Existing Code

### In Order Creation (admin/orders.php)
```php
// After creating order
if ($stmt->execute()) {
    $orderId = $db->insert_id;
    
    // Fetch complete order details
    $order = getOrderById($orderId);
    
    // Send emails
    $emailHandler = new EmailHandler();
    $emailHandler->sendOrderConfirmation($order);
    $emailHandler->sendNewOrderNotification($order);
}
```

### In Order Status Update (admin/orders.php)
```php
// After updating order status
if ($stmt->execute()) {
    $emailHandler = new EmailHandler();
    $emailHandler->sendOrderStatusUpdate($order, $oldStatus, $newStatus);
}
```

### In Inquiry Creation (admin/inquiries.php)
```php
// After creating inquiry
if ($stmt->execute()) {
    $inquiryId = $db->insert_id;
    $inquiry = getInquiryById($inquiryId);
    
    $emailHandler = new EmailHandler();
    $emailHandler->sendInquiryConfirmation($inquiry);
    $emailHandler->sendNewInquiryNotification($inquiry);
}
```

### In Stock Management (admin/products.php)
```php
// After stock update
if ($newStock <= $product['low_stock_threshold']) {
    $emailHandler = new EmailHandler();
    $emailHandler->sendLowStockAlert($product);
}
```

## Email Templates

All email templates are located in `includes/email_templates/` and use PHP for dynamic content. They share a common HTML wrapper with professional styling.

### Template Variables

Each template receives specific variables:

**order_confirmation.php**
- `$order_id`, `$customer_name`, `$order_date`, `$total`, `$currency`, `$items`, `$shipping_address`, `$payment_method`, `$status`

**admin_new_order.php**
- `$order_id`, `$customer_name`, `$customer_email`, `$customer_phone`, `$order_date`, `$total`, `$currency`, `$items`, `$shipping_address`, `$payment_method`

**order_status_update.php**
- `$order_id`, `$customer_name`, `$old_status`, `$new_status`, `$status_message`, `$total`, `$currency`

**inquiry_confirmation.php**
- `$inquiry_id`, `$customer_name`, `$subject`, `$message`, `$date`

**admin_new_inquiry.php**
- `$inquiry_id`, `$customer_name`, `$customer_email`, `$customer_phone`, `$subject`, `$message`, `$date`, `$priority`

## Email Logging

All email activity is automatically logged to `logs/email.log`:

```
[2025-11-07 14:30:45] TO: customer@example.com | SUBJECT: Order Confirmation - Order #12345 | STATUS: SENT
[2025-11-07 14:30:46] TO: admin@jinkaplotter.com | SUBJECT: New Order Received - Order #12345 | STATUS: SENT
```

## Testing

### Test Email Configuration
```php
require_once 'includes/EmailHandler.php';

$emailHandler = new EmailHandler();

// Test with a simple contact form
$testData = [
    'name' => 'Test User',
    'email' => 'test@example.com',
    'phone' => '+254700000000',
    'subject' => 'Test Email',
    'message' => 'This is a test message.'
];

if ($emailHandler->sendContactForm($testData)) {
    echo "Test email sent successfully!";
} else {
    echo "Failed to send test email. Check logs/email.log";
}
```

## Troubleshooting

1. **Emails not sending**: Check PHP mail configuration in `php.ini`
2. **Check logs**: Review `logs/email.log` for send status
3. **Test with simple script**: Use the test example above
4. **Verify email addresses**: Ensure email addresses in config are correct
5. **Server configuration**: Ensure your server supports PHP mail() function

## Production Considerations

1. **SMTP Configuration**: For production, consider using SMTP instead of PHP mail():
   - Use PHPMailer or SwiftMailer library
   - Configure proper SMTP credentials
   - Use TLS/SSL encryption

2. **Email Queue**: For high-volume sites:
   - Implement email queue system
   - Send emails asynchronously
   - Use services like SendGrid or Amazon SES

3. **Rate Limiting**: Implement rate limiting to prevent abuse

4. **Monitoring**: Set up monitoring for:
   - Email delivery rates
   - Bounce rates
   - Failed sends

## Future Enhancements

- [ ] SMTP support with PHPMailer
- [ ] Email queue system
- [ ] Unsubscribe functionality
- [ ] Email preferences management
- [ ] Email analytics dashboard
- [ ] Template editor in admin panel
- [ ] Multi-language email templates

## Support

For issues or questions, contact the development team or check the logs directory for debugging information.
