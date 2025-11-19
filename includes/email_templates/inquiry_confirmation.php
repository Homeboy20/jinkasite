<h2>✅ We Received Your Inquiry</h2>

<p>Dear <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>

<p>Thank you for contacting us! We have received your inquiry and our team will review it shortly.</p>

<div class="info-box">
    <h3 style="margin-top: 0;">Inquiry Details</h3>
    <p>
        <strong>Reference Number:</strong> #<?= $inquiry_id ?><br>
        <strong>Subject:</strong> <?= htmlspecialchars($subject) ?><br>
        <strong>Date:</strong> <?= $date ?>
    </p>
</div>

<h3>Your Message:</h3>
<div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb;">
    <p><?= $message ?></p>
</div>

<p>We typically respond to inquiries within 24 hours during business days. You will receive our response at the email address you provided.</p>

<div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
    <p style="margin: 0;">
        <strong>📌 Important:</strong> Please keep your reference number (#<?= $inquiry_id ?>) for future correspondence regarding this inquiry.
    </p>
</div>

<p>If you need immediate assistance, please feel free to call us or visit our office.</p>

<p>Best regards,<br>
<strong>The JINKA Plotter Team</strong></p>
