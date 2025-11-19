<h2>⚠️ Low Stock Alert</h2>

<p><strong>The following product is running low on stock:</strong></p>

<div class="info-box" style="background: #fee2e2; border-left-color: #ef4444;">
    <h3 style="margin-top: 0; color: #dc2626;">🚨 Stock Alert</h3>
    <p>
        <strong>Product:</strong> <?= htmlspecialchars($product_name) ?><br>
        <strong>SKU:</strong> <?= htmlspecialchars($product_sku) ?><br>
        <strong>Current Stock:</strong> <span style="color: #dc2626; font-weight: 600; font-size: 18px;"><?= $current_stock ?> units</span><br>
        <strong>Threshold:</strong> <?= $threshold ?> units
    </p>
</div>

<div style="background: #fef3c7; padding: 15px; border-left: 4px solid #f59e0b; margin: 20px 0;">
    <p style="margin: 0;">
        <strong>⚡ Action Required:</strong> Please reorder stock to avoid running out of this product.
    </p>
</div>

<div style="text-align: center; margin: 30px 0;">
    <a href="<?= $product_url ?>" class="button">
        View Product in Admin Panel
    </a>
</div>

<h3>Recommended Actions:</h3>
<ul>
    <li>Check supplier availability</li>
    <li>Place a reorder if needed</li>
    <li>Update product status if out of stock</li>
    <li>Consider setting up auto-reorder</li>
</ul>

<p><em>This is an automated alert. Stock levels are monitored in real-time.</em></p>
