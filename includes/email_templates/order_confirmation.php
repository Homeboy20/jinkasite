<h2>🎉 Thank You for Your Order!</h2>

<p>Dear <strong><?= htmlspecialchars($customer_name) ?></strong>,</p>

<p>We have received your order and it is now being processed. You will receive a confirmation email when your order ships.</p>

<div class="info-box">
    <h3 style="margin-top: 0;">Order Details</h3>
    <p>
        <strong>Order ID:</strong> #<?= $order_id ?><br>
        <strong>Order Date:</strong> <?= $order_date ?><br>
        <strong>Status:</strong> <?= $status ?>
    </p>
</div>

<h3>Items Ordered:</h3>
<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $currency ?> <?= number_format($item['price'], 2) ?></td>
            <td><?= $currency ?> <?= number_format($item['quantity'] * $item['price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight: 600; background: #f9fafb;">
            <td colspan="3" style="text-align: right;">Total:</td>
            <td><?= $currency ?> <?= $total ?></td>
        </tr>
    </tbody>
</table>

<h3>Shipping Address:</h3>
<div class="info-box">
    <?= nl2br(htmlspecialchars($shipping_address)) ?>
</div>

<h3>Payment Method:</h3>
<p><?= ucfirst(str_replace('_', ' ', $payment_method)) ?></p>

<div style="text-align: center; margin: 30px 0;">
    <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/jinkaplotterwebsite/track-order.php?order=<?= $order_id ?>" class="button">
        Track Your Order
    </a>
</div>

<p>If you have any questions about your order, please don't hesitate to contact us.</p>

<p>Best regards,<br>
<strong>The JINKA Plotter Team</strong></p>
