<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins can access this functionality
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    
    try {
        // Get current status
        $stmt = $conn->prepare("SELECT status FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_status = $stmt->fetchColumn();
        
        if ($current_status !== false) {
            // Toggle the status
            $new_status = $current_status ? 0 : 1;
            
            // Update the status
            $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $user_id]);
            
            // Log the activity
            $action = $new_status ? 'User activated' : 'User deactivated';
            $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
            $stmt->execute([$_SESSION['user_id'], $action . ' (ID: ' . $user_id . ')']);
            
            // Redirect with success message
            header('Location: admin_dashboard.php?success=' . ($new_status ? 'user_activated' : 'user_deactivated'));
            exit();
        } else {
            header('Location: admin_dashboard.php?error=user_not_found');
            exit();
        }
    } catch (PDOException $e) {
        header('Location: admin_dashboard.php?error=update_failed');
        exit();
    }
}

// If no ID provided, redirect back to dashboard
header('Location: admin_dashboard.php');
exit(); 