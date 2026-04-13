<?php
// Start session
session_start();

// Include configuration
require_once 'config/config.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page with logout message
redirect('index.php?msg=loggedout');
?>