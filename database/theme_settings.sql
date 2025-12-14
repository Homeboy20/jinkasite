-- Theme Customization Default Settings
-- Insert these into your database to initialize theme settings

INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`, `group_name`, `is_public`) 
VALUES 
    ('theme_primary_color', '#3b82f6', 'string', 'Primary brand color', 'theme', 1),
    ('theme_secondary_color', '#8b5cf6', 'string', 'Secondary brand color', 'theme', 1),
    ('theme_accent_color', '#06b6d4', 'string', 'Accent color', 'theme', 1),
    ('theme_success_color', '#10b981', 'string', 'Success message color', 'theme', 1),
    ('theme_warning_color', '#f59e0b', 'string', 'Warning message color', 'theme', 1),
    ('theme_error_color', '#ef4444', 'string', 'Error message color', 'theme', 1),
    ('theme_text_primary', '#1e293b', 'string', 'Primary text color', 'theme', 1),
    ('theme_text_secondary', '#64748b', 'string', 'Secondary text color', 'theme', 1),
    ('theme_background', '#ffffff', 'string', 'Page background color', 'theme', 1),
    ('theme_card_background', '#f8fafc', 'string', 'Card background color', 'theme', 1),
    ('theme_border_color', '#e2e8f0', 'string', 'Border color', 'theme', 1),
    ('theme_link_color', '#3b82f6', 'string', 'Hyperlink color', 'theme', 1),
    ('theme_button_radius', '8', 'number', 'Button border radius in pixels', 'theme', 1),
    ('theme_card_radius', '12', 'number', 'Card border radius in pixels', 'theme', 1),
    ('theme_font_family', 'system-ui, -apple-system, sans-serif', 'string', 'Body font family', 'theme', 1),
    ('theme_heading_font', 'system-ui, -apple-system, sans-serif', 'string', 'Heading font family', 'theme', 1)
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `setting_type` = VALUES(`setting_type`),
    `description` = VALUES(`description`),
    `group_name` = VALUES(`group_name`),
    `is_public` = VALUES(`is_public`);

-- Verify insertion
SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'theme_%';
