<?php
/**
 * Contact Form Handler with Email Notifications
 * 
 * This file demonstrates how to integrate the EmailHandler
 * into your contact form processing.
 */

// Initialize
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}

require_once 'includes/config.php';
require_once 'includes/EmailHandler.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
    $subject = htmlspecialchars(trim($_POST['subject'] ?? 'General Inquiry'));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    // Validate
    if (empty($name) || !$email || empty($message)) {
        $response = [
            'success' => false,
            'message' => 'Please fill in all required fields.'
        ];
    } else {
        // Prepare data
        $contactData = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'subject' => $subject,
            'message' => $message
        ];
        
        // Send email
        $emailHandler = new EmailHandler();
        $emailSent = $emailHandler->sendContactForm($contactData);
        
        if ($emailSent) {
            $response = [
                'success' => true,
                'message' => 'Thank you for contacting us! We will get back to you soon.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'There was an error sending your message. Please try again later.'
            ];
        }
    }
    
    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - JINKA Plotter</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="contact-form-container">
        <h2>Contact Us</h2>
        
        <form id="contactForm" method="POST">
            <div class="form-group">
                <label for="name">Name *</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone</label>
                <input type="tel" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="subject">Subject</label>
                <input type="text" id="subject" name="subject" value="General Inquiry">
            </div>
            
            <div class="form-group">
                <label for="message">Message *</label>
                <textarea id="message" name="message" rows="5" required></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
        
        <div id="response-message"></div>
    </div>
    
    <script>
        document.getElementById('contactForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const responseDiv = document.getElementById('response-message');
            
            try {
                const response = await fetch('contact_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                responseDiv.className = result.success ? 'alert alert-success' : 'alert alert-error';
                responseDiv.textContent = result.message;
                
                if (result.success) {
                    this.reset();
                }
            } catch (error) {
                responseDiv.className = 'alert alert-error';
                responseDiv.textContent = 'An error occurred. Please try again.';
            }
        });
    </script>
</body>
</html>
