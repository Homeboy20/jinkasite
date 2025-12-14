<?php
define('JINKA_ACCESS', true);
require_once 'includes/config.php';
require_once 'includes/CustomerAuth.php';

$auth = new CustomerAuth($conn);
$auth->logout();

header('Location: index.php');
exit;
?>

