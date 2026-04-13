<?php
session_start();
require_once '../config/config.php';

// Redirect to dashboard if logged in, otherwise to login
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
} else {
    header("Location: login.php");
}
exit();