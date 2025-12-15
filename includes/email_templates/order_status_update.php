<h2>📦 Order Status Update</h2>

<p>Dear <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>

<p>Your order status has been updated!</p>

<div class="info-box">
    <h3 style="margin-top: 0;">Order #<?= $order_id ?></h3>
    <p>
        <strong>Previous Status:</strong> <span style="text-decoration: line-through;"><?= $old_status ?></span><br>
        <strong>Current Status:</strong> <span style="color: #059669; font-weight: 600;"><?= $new_status ?></span>
    </p>
</div>

<div style="background: #ecfdf5; padding: 20px; border-left: 4px solid #059669; margin: 20px 0;">
    <p style="margin: 0; font-size: 16px;">
        <?= $status_message ?>
    </p>
</div>

<table style="margin: 20px 0;">
    <tr>
        <th style="width: 150px;">Order Total:</th>
        <td style="font-size: 18px; font-weight: 600; color: #1e40af;"><?= $currency ?> <?= $total ?></td>
    </tr>
</table>

<div style="text-align: center; margin: 30px 0;">
    <a href="<?= rtrim(SITE_URL, '/') ?>/track-order.php?order=<?= $order_id ?>" class="button">
        Track Your Order
    </a>
</div>

<p>If you have any questions, please feel free to contact us.</p>

<p>Best regards,<br>
<strong>The JINKA Plotter Team</strong></p>
