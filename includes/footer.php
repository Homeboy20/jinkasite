    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-col footer-col-brand">
                    <?php if (!empty($footer_logo)): ?>
                        <div class="footer-logo">
                            <img src="<?php echo htmlspecialchars($footer_logo); ?>" alt="<?php echo htmlspecialchars($business_name); ?>" class="footer-logo-img">
                        </div>
                    <?php else: ?>
                        <h3><?php echo htmlspecialchars($business_name); ?></h3>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($footer_about); ?></p>
                    <div class="footer-social">
                        <?php if (!empty($whatsapp_number)): ?>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $whatsapp_number); ?>" target="_blank" title="WhatsApp">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($facebook_url)): ?>
                        <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank" title="Facebook">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($twitter_url)): ?>
                        <a href="<?php echo htmlspecialchars($twitter_url); ?>" target="_blank" title="Twitter">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($instagram_url)): ?>
                        <a href="<?php echo htmlspecialchars($instagram_url); ?>" target="_blank" title="Instagram">
                            <svg width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678c-3.405 0-6.162 2.76-6.162 6.162 0 3.405 2.76 6.162 6.162 6.162 3.405 0 6.162-2.76 6.162-6.162 0-3.405-2.76-6.162-6.162-6.162zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405c0 .795-.646 1.44-1.44 1.44-.795 0-1.44-.646-1.44-1.44 0-.794.646-1.439 1.44-1.439.793-.001 1.44.645 1.44 1.439z"/>
                            </svg>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="/">Home</a></li>
                        <li><a href="products">Products</a></li>
                        <li><a href="#features">Features</a></li>
                        <li><a href="#applications">Applications</a></li>
                        <li><a href="contact">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h4>Get in Touch</h4>
                    <?php if (!empty($phone_number) || !empty($phone_number_ke)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20.01 15.38c-1.23 0-2.42-.2-3.53-.56a.977.977 0 00-1.01.24l-1.57 1.97c-2.83-1.35-5.48-3.9-6.89-6.83l1.95-1.66c.27-.28.35-.67.24-1.02-.37-1.11-.56-2.3-.56-3.53 0-.54-.45-.99-.99-.99H4.19C3.65 3 3 3.24 3 3.99 3 13.28 10.73 21 20.01 21c.71 0 .99-.63.99-1.18v-3.45c0-.54-.45-.99-.99-.99z"/>
                            </svg>
                        </div>
                        <div>
                            <?php if (!empty($phone_number)): ?>
                            <div><?php echo htmlspecialchars($footer_phone_label_tz); ?>: <a href="tel:<?php echo $phone_number; ?>"><?php echo htmlspecialchars($phone_number); ?></a></div>
                            <?php endif; ?>
                            <?php if (!empty($phone_number_ke)): ?>
                            <div style="margin-top: 0.25rem;"><?php echo htmlspecialchars($footer_phone_label_ke); ?>: <a href="tel:<?php echo $phone_number_ke; ?>"><?php echo htmlspecialchars($phone_number_ke); ?></a></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($email)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                            </svg>
                        </div>
                        <a href="mailto:<?php echo $email; ?>"><?php echo htmlspecialchars($email); ?></a>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_address)): ?>
                    <div class="footer-contact-item">
                        <div class="footer-contact-icon">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/>
                            </svg>
                        </div>
                        <span><?php echo htmlspecialchars($footer_address); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="footer-col">
                    <h4>Business Hours</h4>
                    <?php if (!empty($footer_hours_weekday)): ?>
                    <div class="footer-hours-item">
                        <span>Mon - Fri</span>
                        <strong><?php echo htmlspecialchars($footer_hours_weekday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_hours_saturday)): ?>
                    <div class="footer-hours-item">
                        <span>Saturday</span>
                        <strong><?php echo htmlspecialchars($footer_hours_saturday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_hours_sunday)): ?>
                    <div class="footer-hours-item">
                        <span>Sunday</span>
                        <strong><?php echo htmlspecialchars($footer_hours_sunday); ?></strong>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($footer_whatsapp_label)): ?>
                    <div class="footer-hours-item">
                        <span>WhatsApp</span>
                        <strong style="color: #10b981;"><?php echo htmlspecialchars($footer_whatsapp_label); ?></strong>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($business_name); ?>. <?php echo !empty($footer_copyright) ? htmlspecialchars($footer_copyright) : 'All rights reserved.'; ?></p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Shipping Policy</a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- Live Chat Widget -->
    <link rel="stylesheet" href="css/support-chat.css?v=<?php echo time(); ?>">
    <script src="js/live-chat.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            new LiveChatWidget({
                apiEndpoint: 'api/support-chat.php',
                customerName: '<?php echo isset($customer) ? addslashes($customer['first_name'] . ' ' . $customer['last_name']) : (isset($customer_data) ? addslashes($customer_data['first_name'] . ' ' . $customer_data['last_name']) : 'Guest'); ?>',
                customerEmail: '<?php echo isset($customer) ? addslashes($customer['email']) : (isset($customer_data['email']) ? addslashes($customer_data['email']) : ''); ?>',
                customerId: <?php echo isset($customer_id) ? (int)$customer_id : (isset($customer_data['id']) ? (int)$customer_data['id'] : 'null'); ?>
            });
        });
    </script>
