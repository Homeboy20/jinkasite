<h2>💬 Reply to Your Inquiry</h2>

<p>Dear <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>

<p>We have replied to your inquiry. Here are the details:</p>

<div class="info-box">
    <h3 style="margin-top: 0;">Inquiry #<?= $inquiry_id ?></h3>
    <p><strong>Date:</strong> <?= $date ?></p>
</div>

<h3>Your Original Message:</h3>
<div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb; margin: 15px 0;">
    <p><?= $original_message ?></p>
</div>

<h3>Our Response:</h3>
<div style="background: #ecfdf5; padding: 20px; border-radius: 6px; border-left: 4px solid #059669; margin: 15px 0;">
    <p><?= $reply ?></p>
</div>

<p>If you have any additional questions, please feel free to contact us again.</p>

<div style="text-align: center; margin: 30px 0;">
    <a href="mailto:<?= $GLOBALS['admin_email'] ?? 'admin@jinkaplotter.com' ?>" class="button">
        Reply to This Message
    </a>
</div>

<p>Best regards,<br>
<strong>The JINKA Plotter Team</strong></p>
