<?php
// Site Configuration
define('SITE_NAME', 'Fresh Home and Suite Hotel');
define('SITE_URL', 'https://freshhotelsuite.xo.je/');
define('UPLOAD_PATH', $_SERVER['DOCUMENT_ROOT'] . '/fresh-home-suite/uploads/');
define('UPLOAD_URL', SITE_URL . 'uploads/');

// Date & Time Settings
date_default_timezone_set('Africa/Lagos');

// Payment Settings
define('BANK_TRANSFER_INSTRUCTIONS', 'Please make payment to any of the bank accounts below and upload your receipt.');

// Include database
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
?>