<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$log_id = $_GET['id'];
$query = "SELECT logs.*, users.name FROM logs JOIN users ON logs.user_id = users.id WHERE logs.id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$log_id]);
$log = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$log) {
    echo "Log not found.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="styles.css">
    <title>Log Details</title>
</head>
<body>
    <h1>Log Details</h1>
    <p><strong>User:</strong> <?= htmlspecialchars($log['name']) ?></p>
    <p><strong>Action:</strong> <?= htmlspecialchars($log['action']) ?></p>
    <p><strong>Timestamp:</strong> <?= htmlspecialchars($log['timestamp']) ?></p>
    <a href="view_logs.php">Back to Logs</a>
</body>
</html>