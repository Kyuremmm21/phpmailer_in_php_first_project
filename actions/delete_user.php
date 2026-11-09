<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins can perform this action
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    $query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$user_id]);

    header('Location: admin_dashboard.php');
    exit();
}
?>