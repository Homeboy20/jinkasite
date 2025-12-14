<?php
require_once 'includes/config.php';

// Theme color updates
$theme_updates = [
    'theme_primary_color' => '#ff5900',
    'theme_secondary_color' => '#e64f00',
    'theme_accent_color' => '#10b981',
    'theme_text_color' => '#1f2937',
    'theme_background_color' => '#ffffff',
    'theme_header_bg' => '#ffffff',
    'theme_header_text' => '#1f2937',
    'theme_button_bg' => '#ff5900',
    'theme_button_text' => '#ffffff',
    'theme_link_color' => '#ff5900',
    'theme_footer_bg' => '#0f172a',
    'theme_footer_text' => '#e2e8f0',
    'theme_border_color' => '#e5e7eb',
    'theme_card_bg' => '#ffffff',
    'theme_card_shadow' => '0 1px 3px rgba(0,0,0,0.1)',
    'theme_font_family' => 'Inter, system-ui, sans-serif'
];

echo "Updating theme colors to orange (#ff5900)...\n\n";

foreach ($theme_updates as $key => $value) {
    $stmt = $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, setting_type) 
        VALUES (?, ?, 'theme')
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
    ");
    $stmt->execute([$key, $value]);
    echo "✓ Updated $key = $value\n";
}

// Clear theme cache
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "\n✓ Cache cleared\n";
}

echo "\n✅ Theme colors updated successfully!\n";
echo "\nCurrent theme settings:\n";
echo str_repeat('-', 50) . "\n";

$stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%' ORDER BY setting_key");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-30s : %s\n", $row['setting_key'], $row['setting_value']);
}
