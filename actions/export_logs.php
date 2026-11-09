
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Fetch all logs
$query = "SELECT logs.*, users.name FROM logs JOIN users ON logs.user_id = users.id ORDER BY logs.timestamp DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=logs.csv');

// Open output stream
$output = fopen('php://output', 'w');

// Write CSV headers
fputcsv($output, ['User', 'Action', 'Timestamp']);

// Write log data
foreach ($logs as $log) {
    fputcsv($output, [$log['name'], $log['action'], $log['timestamp']]);
}

// Close output stream
fclose($output);
exit();
?>