<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

$security = new Security($conn);

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $security->logActivity($_SESSION['user_id'], 'User logged out');
}

// Destroy the session
session_unset();
session_destroy();

// Redirect to login page
header('Location: index.php?page=login');
exit(); 