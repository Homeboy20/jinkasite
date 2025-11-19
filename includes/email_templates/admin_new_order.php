<h2>🔔 New Order Received</h2>

<p><strong>A new order has been placed on your website!</strong></p>

<div class="info-box" style="background: #fef3c7; border-left-color: #f59e0b;">
    <h3 style="margin-top: 0;">⚡ Order Summary</h3>
    <p>
        <strong>Order ID:</strong> #<?= $order_id ?><br>
        <strong>Order Date:</strong> <?= $order_date ?><br>
        <strong>Total Amount:</strong> <?= $currency ?> <?= $total ?>
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

<h3>Order Items:</h3>
<table>
    <thead>
        <tr>
            <th>Product</th>
            <th>SKU</th>
            <th>Quantity</th>
            <th>Price</th>
            <th>Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $item): ?>
        <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td><?= htmlspecialchars($item['sku']) ?></td>
            <td><?= $item['quantity'] ?></td>
            <td><?= $currency ?> <?= number_format($item['price'], 2) ?></td>
            <td><?= $currency ?> <?= number_format($item['quantity'] * $item['price'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr style="font-weight: 600; background: #f9fafb;">
            <td colspan="4" style="text-align: right;">Total:</td>
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
    <a href="http://<?= $_SERVER['HTTP_HOST'] ?>/jinkaplotterwebsite/admin/orders.php?view=<?= $order_id ?>" class="button">
        View Order in Admin Panel
    </a>
</div>

<p><em>This is an automated notification. Please process this order as soon as possible.</em></p>
