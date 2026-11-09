<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Get assignment ID from URL
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    header('Location: admin_dashboard.php');
    exit();
}

try {
    // Get assignment details for logging
    $stmt = $conn->prepare("SELECT title FROM assignments WHERE id = ? AND is_deleted = 0");
    $stmt->execute([$assignment_id]);
    $assignment = $stmt->fetch();

    if (!$assignment) {
        header('Location: admin_dashboard.php?error=assignment_not_found');
        exit();
    }

    // Soft delete the assignment
    $stmt = $conn->prepare("
        UPDATE assignments 
        SET is_deleted = 1, 
            updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$assignment_id]);

    // Log the action
    $stmt = $conn->prepare("
        INSERT INTO logs (user_id, action, details) 
        VALUES (?, 'delete_assignment', ?)
    ");
    $stmt->execute([$_SESSION['user_id'], "Deleted assignment: " . $assignment['title']]);

    header('Location: admin_dashboard.php?success=assignment_deleted');
    exit();
} catch (PDOException $e) {
    header('Location: admin_dashboard.php?error=delete_failed');
    exit();
} 