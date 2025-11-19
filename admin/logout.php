<?php
define('JINKA_ACCESS', true);
require_once 'includes/auth.php';

// Initialize auth class
$auth = new AdminAuth();

// Logout user
$auth->logout();

// Redirect to login page
redirect('login.php', 'You have been logged out successfully', 'success');
?>