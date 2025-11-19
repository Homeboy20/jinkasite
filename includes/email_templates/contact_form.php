<h2>📧 Contact Form Submission</h2>

<p><strong>Someone has sent you a message through the contact form.</strong></p>

<h3>Contact Details:</h3>
<table>
    <tr>
        <th style="width: 150px;">Name:</th>
        <td><?= htmlspecialchars($name) ?></td>
    </tr>
    <tr>
        <th>Email:</th>
        <td><a href="mailto:<?= $email ?>"><?= $email ?></a></td>
    </tr>
    <tr>
        <th>Phone:</th>
        <td><?= htmlspecialchars($phone) ?></td>
    </tr>
    <tr>
        <th>Subject:</th>
        <td><?= htmlspecialchars($subject) ?></td>
    </tr>
    <tr>
        <th>Date:</th>
        <td><?= $date ?></td>
    </tr>
</table>

<h3>Message:</h3>
<div style="background: #f9fafb; padding: 20px; border-radius: 6px; border: 1px solid #e5e7eb;">
    <p><?= $message ?></p>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="mailto:<?= $email ?>?subject=Re: <?= urlencode($subject) ?>" class="button">
        Reply to <?= htmlspecialchars($name) ?>
    </a>
</div>
