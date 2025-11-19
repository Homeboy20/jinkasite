<?php
// Contact form handler
header('Content-Type: application/json');

// Configuration
$to_email = "support@procutsolutions.com"; // Support email
$subject = "New Inquiry from JINKA Plotter Website";

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate input
    $name = filter_var(trim($_POST["name"]), FILTER_SANITIZE_STRING);
    $phone = filter_var(trim($_POST["phone"]), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    $business = filter_var(trim($_POST["business"]), FILTER_SANITIZE_STRING);
    $message = filter_var(trim($_POST["message"]), FILTER_SANITIZE_STRING);
    
    // Validate required fields
    if (empty($name) || empty($phone) || empty($email) || empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }
    
    // Build email content
    $email_content = "New inquiry from JINKA Cutting Plotter website\n\n";
    $email_content .= "Name: $name\n";
    $email_content .= "Phone: $phone\n";
    $email_content .= "Email: $email\n";
    if (!empty($business)) {
        $email_content .= "Business: $business\n";
    }
    $email_content .= "\nMessage:\n$message\n";
    $email_content .= "\n---\n";
    $email_content .= "Submitted: " . date('Y-m-d H:i:s') . "\n";
    $email_content .= "IP Address: " . $_SERVER['REMOTE_ADDR'] . "\n";
    
    // Email headers
    $headers = "From: $name <$email>\r\n";
    $headers .= "Reply-To: $email\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    // Send email
    if (mail($to_email, $subject, $email_content, $headers)) {
        // Also save to a file as backup
        $log_file = 'inquiries.txt';
        $log_content = "\n\n=== NEW INQUIRY ===\n";
        $log_content .= $email_content;
        file_put_contents($log_file, $log_content, FILE_APPEND);
        
        echo json_encode(['success' => true, 'message' => 'Thank you! We will contact you soon.']);
    } else {
        // If email fails, still save to file
        $log_file = 'inquiries.txt';
        $log_content = "\n\n=== NEW INQUIRY (EMAIL FAILED) ===\n";
        $log_content .= $email_content;
        file_put_contents($log_file, $log_content, FILE_APPEND);
        
        echo json_encode(['success' => false, 'message' => 'Error sending email. Please contact us via WhatsApp.']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
