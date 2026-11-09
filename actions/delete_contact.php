<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if (isset($_GET['id'])) {
    $contact_id = $_GET['id'];
    $user_id = $_SESSION['user_id'];

    // Soft delete the contact by setting is_deleted flag
    $query = "UPDATE contacts SET is_deleted = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$contact_id, $user_id]);

    header('Location: user_dashboard.php?success=1');
    exit();
}
?>