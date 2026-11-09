<?php
session_start();

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$stmt = $conn->prepare("SELECT * FROM teacher_submissions WHERE id = ? AND is_deleted = 0");
$stmt->execute([$submission_id]);
$submission = $stmt->fetch();

// If submission not found
if (!$submission) {
    header('Location: admin_dashboard.php');
    exit();
}

// If submission is not pending, don't allow rejection
if ($submission['status'] !== 'pending') {
    header('Location: view_submission.php?id=' . $submission_id);
    exit();
}

try {
    // Update submission status
    $stmt = $conn->prepare("UPDATE teacher_submissions SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$submission_id]);

    // Log the activity
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, timestamp) VALUES (?, 'Rejected submission: ' . ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $submission['submission_title']]);

    header('Location: view_submission.php?id=' . $submission_id . '&success=submission_rejected');
} catch (PDOException $e) {
    header('Location: view_submission.php?id=' . $submission_id . '&error=rejection_failed');
}
exit(); 