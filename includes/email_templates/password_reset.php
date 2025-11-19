<h2>🔐 Password Reset Request</h2>

<p>Dear <strong><?= htmlspecialchars($user_name) ?></strong>,</p>

<p>We received a request to reset your password. If you didn't make this request, please ignore this email.</p>

<div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
    <p style="margin: 0;">
        <strong>⚠️ Security Notice:</strong> This password reset link will expire in <?= $expiry_time ?>.
    </p>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="<?= $reset_url ?>" class="button" style="background: #dc2626;">
        Reset Your Password
    </a>
</div>

<p>Alternatively, you can copy and paste this link into your browser:</p>
<div style="background: #f9fafb; padding: 15px; border-radius: 6px; border: 1px solid #e5e7eb; word-break: break-all;">
    <code><?= $reset_url ?></code>
</div>

<h3>Security Tips:</h3>
<ul>
    <li>Never share your password with anyone</li>
    <li>Use a strong, unique password</li>
    <li>Enable two-factor authentication if available</li>
    <li>Don't use the same password across multiple sites</li>
</ul>

<p>If you didn't request this password reset, please contact us immediately.</p>

<p>Best regards,<br>
<strong>The JINKA Plotter Team</strong></p>
