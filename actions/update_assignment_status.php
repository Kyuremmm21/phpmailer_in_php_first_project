<?php
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'], $_POST['status'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'in_progress', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        header('Location: my_assignments.php?error=invalid_status');
        exit();
    }
    
    try {
        // Check if assignment exists and belongs to user
        $stmt = $conn->prepare("SELECT id FROM assignments WHERE id = ? AND assigned_to = ? AND is_deleted = 0");
        $stmt->execute([$assignment_id, $_SESSION['user_id']]);
        $assignment = $stmt->fetch();
        
        if (!$assignment) {
            header('Location: my_assignments.php?error=assignment_not_found');
            exit();
        }
        
        // Update status
        $stmt = $conn->prepare("UPDATE assignments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $assignment_id]);
        
        // Log the activity
        $stmt = $conn->prepare("INSERT INTO logs (user_id, action, timestamp) VALUES (?, ?, NOW())");
        $stmt->execute([$_SESSION['user_id'], 'Updated assignment status to: ' . $status]);
        
        header('Location: my_assignments.php?success=status_updated');
    } catch (PDOException $e) {
        header('Location: my_assignments.php?error=update_failed');
    }
} else {
    header('Location: my_assignments.php');
}
exit(); 