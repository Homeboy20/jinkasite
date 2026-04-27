<?php
if (!defined('JINKA_ACCESS')) {
    define('JINKA_ACCESS', true);
}
require_once __DIR__ . '/includes/config.php';

$site_name        = site_setting('site_name', 'NDOSA STORE');
$business_email   = site_setting('business_email', defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : 'info@ndosa.store');
$admin_email      = defined('ADMIN_EMAIL') ? ADMIN_EMAIL : $business_email;
$phone_tz         = site_setting('business_phone_tz', defined('BUSINESS_PHONE_TZ') ? BUSINESS_PHONE_TZ : '');
$phone_ke         = site_setting('business_phone_ke', defined('BUSINESS_PHONE_KE') ? BUSINESS_PHONE_KE : '');
$whatsapp_number  = site_setting('whatsapp_number', defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '');
$whatsapp_link    = preg_replace('/[^0-9]/', '', $whatsapp_number);

// Handle POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $name     = Security::sanitizeInput($_POST['name']     ?? '');
    $phone    = Security::sanitizeInput($_POST['phone']    ?? '');
    $email    = trim((string)($_POST['email']    ?? ''));
    $business = Security::sanitizeInput($_POST['business'] ?? '');
    $subject  = Security::sanitizeInput($_POST['subject']  ?? 'General Inquiry');
    $message  = Security::sanitizeInput($_POST['message']  ?? '');

    if ($name === '' || $phone === '' || $email === '' || $message === '') {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email address.']);
        exit;
    }

    // Persist to inquiries table when available, fall back to a log file.
    $stored = false;
    try {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare(
            "INSERT INTO inquiries (name, email, phone, subject, message, status, created_at)
             VALUES (?, ?, ?, ?, ?, 'new', NOW())"
        );
        if ($stmt) {
            $stmt->bind_param('sssss', $name, $email, $phone, $subject, $message);
            $stored = $stmt->execute();
        }
    } catch (Throwable $e) {
        Logger::error('Contact insert failed: ' . $e->getMessage());
    }

    if (!$stored) {
        $log = LOG_PATH . '/inquiries.txt';
        @file_put_contents(
            $log,
            "[" . date('c') . "] $name <$email> $phone | $subject | $message\n",
            FILE_APPEND | LOCK_EX
        );
    }

    // Best-effort email notification.
    $body  = "New inquiry from $site_name\n\n";
    $body .= "Name:    $name\nEmail:   $email\nPhone:   $phone\n";
    if ($business !== '') $body .= "Business: $business\n";
    $body .= "Subject: $subject\n\nMessage:\n$message\n";
    $headers = "From: $site_name <" . ($admin_email ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) . ">\r\n";
    $headers .= "Reply-To: $email\r\n";
    @mail($admin_email, "[$site_name] $subject", $body, $headers);

    echo json_encode(['success' => true, 'message' => 'Thank you! We will get back to you shortly.']);
    exit;
}

// ---- Render page (GET) ---------------------------------------------------
$page_title       = "Contact $site_name | Vinyl Cutting Plotters in Kenya & Tanzania";
$page_description = "Talk to our team about JINKA cutting plotters, pricing, training, and after-sales support across Kenya and Tanzania.";
$canonical_url    = site_url('contact');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title); ?></title>
    <meta name="description" content="<?php echo esc_html($page_description); ?>">
    <link rel="canonical" href="<?php echo esc_html($canonical_url); ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo esc_html($page_title); ?>">
    <meta property="og:description" content="<?php echo esc_html($page_description); ?>">
    <meta property="og:url" content="<?php echo esc_html($canonical_url); ?>">
    <meta name="twitter:card" content="summary">
    <link rel="stylesheet" href="<?php echo site_url('css/style.css'); ?>">
    <style>
        .contact-hero { background: linear-gradient(135deg,#fef3ed 0%,#fff7ed 50%,#fff 100%); padding: 4rem 0 2rem; }
        .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 3rem; padding: 3rem 0; }
        @media (max-width: 768px) { .contact-grid { grid-template-columns: 1fr; gap: 2rem; } }
        .contact-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:2rem; box-shadow:0 4px 12px rgba(0,0,0,0.04); }
        .contact-card h3 { margin: 0 0 1rem; font-size: 1.25rem; color:#0f172a; }
        .contact-method { display:flex; align-items:center; gap:.75rem; padding:.75rem 0; border-bottom:1px solid #f1f5f9; color:#334155; text-decoration:none; }
        .contact-method:last-child { border-bottom:none; }
        .contact-method:hover { color:#ff5900; }
        .form-row { margin-bottom: 1rem; }
        .form-row label { display:block; font-weight:600; color:#334155; margin-bottom:.35rem; font-size:.875rem; }
        .form-row input, .form-row textarea { width:100%; padding:.75rem 1rem; border:1px solid #cbd5e1; border-radius:8px; font:inherit; box-sizing:border-box; }
        .form-row textarea { min-height: 140px; resize: vertical; }
        .submit-btn { background: linear-gradient(135deg,#ff5900 0%,#e64f00 100%); color:#fff; border:none; padding:.9rem 2rem; border-radius:10px; font-weight:700; font-size:1rem; cursor:pointer; box-shadow:0 8px 18px rgba(255,89,0,.25); }
        .submit-btn:disabled { opacity:.6; cursor:not-allowed; }
        .form-status { padding:.75rem 1rem; border-radius:8px; margin-bottom:1rem; display:none; }
        .form-status.success { background:#dcfce7; color:#166534; border:1px solid #86efac; }
        .form-status.error   { background:#fee2e2; color:#991b1b; border:1px solid #fca5a5; }
    </style>
</head>
<body>
<?php
$header_path = __DIR__ . '/includes/header.php';
if (is_file($header_path)) include $header_path;
?>

<section class="contact-hero">
    <div class="container">
        <h1 style="font-size:2.5rem;font-weight:800;color:#0f172a;margin:0 0 .5rem;">Get in touch</h1>
        <p style="font-size:1.125rem;color:#475569;max-width:640px;margin:0;">
            Questions about pricing, specifications, financing, or installation? Send us a message and a specialist will reply within one business day.
        </p>
    </div>
</section>

<section class="container">
    <div class="contact-grid">
        <div class="contact-card">
            <h3>Send a message</h3>
            <div id="contact-status" class="form-status" role="alert"></div>
            <form id="contact-form" method="POST" action="<?php echo esc_html(site_url('contact')); ?>" novalidate>
                <div class="form-row">
                    <label for="cf-name">Full name *</label>
                    <input id="cf-name" name="name" type="text" required autocomplete="name">
                </div>
                <div class="form-row">
                    <label for="cf-email">Email *</label>
                    <input id="cf-email" name="email" type="email" required autocomplete="email">
                </div>
                <div class="form-row">
                    <label for="cf-phone">Phone *</label>
                    <input id="cf-phone" name="phone" type="tel" required autocomplete="tel" placeholder="+254 7…">
                </div>
                <div class="form-row">
                    <label for="cf-business">Business / Company</label>
                    <input id="cf-business" name="business" type="text" autocomplete="organization">
                </div>
                <div class="form-row">
                    <label for="cf-subject">Subject</label>
                    <input id="cf-subject" name="subject" type="text" value="General Inquiry">
                </div>
                <div class="form-row">
                    <label for="cf-message">Message *</label>
                    <textarea id="cf-message" name="message" required></textarea>
                </div>
                <button class="submit-btn" type="submit" id="cf-submit">Send message</button>
            </form>
        </div>

        <div class="contact-card">
            <h3>Other ways to reach us</h3>
            <?php if ($whatsapp_link !== ''): ?>
                <a class="contact-method" href="https://wa.me/<?php echo esc_html($whatsapp_link); ?>" target="_blank" rel="noopener">
                    💬 <strong>WhatsApp:</strong> <?php echo esc_html($whatsapp_number); ?>
                </a>
            <?php endif; ?>
            <?php if ($phone_ke !== ''): ?>
                <a class="contact-method" href="tel:<?php echo esc_html(preg_replace('/\s+/', '', $phone_ke)); ?>">
                    🇰🇪 <strong>Kenya:</strong> <?php echo esc_html($phone_ke); ?>
                </a>
            <?php endif; ?>
            <?php if ($phone_tz !== ''): ?>
                <a class="contact-method" href="tel:<?php echo esc_html(preg_replace('/\s+/', '', $phone_tz)); ?>">
                    🇹🇿 <strong>Tanzania:</strong> <?php echo esc_html($phone_tz); ?>
                </a>
            <?php endif; ?>
            <?php if ($business_email !== ''): ?>
                <a class="contact-method" href="mailto:<?php echo esc_html($business_email); ?>">
                    ✉️ <strong>Email:</strong> <?php echo esc_html($business_email); ?>
                </a>
            <?php endif; ?>

            <h3 style="margin-top:1.5rem;">Hours</h3>
            <p style="color:#475569;margin:0;">Mon – Fri 09:00 – 18:00 EAT<br>Sat 09:00 – 13:00 EAT</p>
        </div>
    </div>
</section>

<?php
$footer_path = __DIR__ . '/includes/footer.php';
if (is_file($footer_path)) include $footer_path;
?>

<script>
(function () {
    const form   = document.getElementById('contact-form');
    const status = document.getElementById('contact-status');
    const btn    = document.getElementById('cf-submit');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        status.style.display = 'none';
        btn.disabled = true; btn.textContent = 'Sending…';

        try {
            const res = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json().catch(() => ({ success:false, message:'Server returned an unexpected response.' }));
            status.textContent = data.message || (data.success ? 'Sent.' : 'Failed.');
            status.className = 'form-status ' + (data.success ? 'success' : 'error');
            status.style.display = 'block';
            if (data.success) form.reset();
        } catch (err) {
            status.textContent = 'Network error. Please try again or message us on WhatsApp.';
            status.className = 'form-status error';
            status.style.display = 'block';
        } finally {
            btn.disabled = false; btn.textContent = 'Send message';
        }
    });
})();
</script>
</body>
</html>
