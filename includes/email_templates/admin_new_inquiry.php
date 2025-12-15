<h2>💬 New Customer Inquiry</h2>

<p><strong>A new inquiry has been submitted on your website.</strong></p>

<div class="info-box" style="background: <?= $priority === 'high' ? '#fee2e2' : '#fef3c7' ?>; border-left-color: <?= $priority === 'high' ? '#ef4444' : '#f59e0b' ?>;">
    <h3 style="margin-top: 0;">
        <?= $priority === 'high' ? '🔴 High Priority' : '📌' ?> Inquiry #<?= $inquiry_id ?>
    </h3>
    <p>
        <strong>Date:</strong> <?= $date ?><br>
        <strong>Subject:</strong> <?= htmlspecialchars($subject) ?>
    </p>
</div>

<h3>Customer Information:</h3>
<table>
    <tr>
        <th style="width: 150px;">Name:</th>
        <td><?= htmlspecialchars($customer_name) ?></td>
    </tr>
    <tr>
        <th>Email:</th>
        <td><a href="mailto:<?= $customer_email ?>"><?= $customer_email ?></a></td>
    </tr>
    <tr>
        <th>Phone:</th>
        <td><?= htmlspecialchars($customer_phone) ?></td>
    </tr>
</table>

<h3>Message:</h3>
<div style="background: #f9fafb; padding: 20px; border-radius: 6px; border: 1px solid #e5e7eb;">
    <p><?= $message ?></p>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="<?= rtrim(SITE_URL, '/') ?>/admin/inquiries.php?view=<?= $inquiry_id ?>" class="button">
        View & Reply in Admin Panel
    </a>
</div>

<p><em>Please respond to this inquiry within 24 hours to maintain excellent customer service.</em></p>
