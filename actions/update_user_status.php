<?php

session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins can access this functionality
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['status'])) {
    $user_id = (int)$_POST['user_id'];
    $status = (int)$_POST['status'];

    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);
}

header('Location: admin_dashboard.php');
exit();
?>