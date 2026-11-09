<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = $_GET['id'] ?? 0;

// Fetch assignment details
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$assignment_id, $user_id]);
$assignment = $stmt->fetch();

if (!$assignment || !$assignment['submission_file']) {
    header("Location: view_assignment.php?id=" . $assignment_id);
    exit;
}

$file_path = __DIR__ . '/../uploads/assignments/' . $assignment['submission_file'];

if (!file_exists($file_path)) {
    header("Location: view_assignment.php?id=" . $assignment_id . "&error=File not found");
    exit;
}

// Get file extension
$file_extension = strtolower(pathinfo($assignment['submission_file'], PATHINFO_EXTENSION));

// Set appropriate headers for download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $assignment['title'] . '.' . $file_extension . '"');
header('Content-Length: ' . filesize($file_path));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($file_path);
exit; 