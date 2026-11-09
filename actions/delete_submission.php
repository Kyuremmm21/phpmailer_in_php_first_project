<?php
session_start();

// Ensure only teachers and admins can access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$query = "SELECT * FROM teacher_submissions WHERE id = ? AND is_deleted = 0";
$params = [$submission_id];

// If user is a teacher, only allow them to delete their own submissions
if ($_SESSION['role'] === 'teacher') {
    $query .= " AND teacher_id = ?";
    $params[] = $_SESSION['user_id'];
}

$stmt = $conn->prepare($query);
$stmt->execute($params);
$submission = $stmt->fetch();

// If submission not found or teacher trying to delete another teacher's submission
if (!$submission) {
    header('Location: ' . ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'admin_dashboard.php'));
    exit();
}

// If submission is not pending and user is a teacher, don't allow deletion
if ($_SESSION['role'] === 'teacher' && $submission['status'] !== 'pending') {
    header('Location: view_submission.php?id=' . $submission_id);
    exit();
}

try {
    // Soft delete the submission
    $stmt = $conn->prepare("UPDATE teacher_submissions SET is_deleted = 1, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$submission_id]);

    // Log the activity
    $stmt = $conn->prepare("INSERT INTO logs (user_id, action, timestamp) VALUES (?, 'Deleted submission: ' . ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $submission['submission_title']]);

    header('Location: ' . ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'admin_dashboard.php') . '?success=submission_deleted');
} catch (PDOException $e) {
    header('Location: view_submission.php?id=' . $submission_id . '&error=delete_failed');
}
exit(); 