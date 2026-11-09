<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = (int)$_POST['contact_id'];
    $user_id = (int)$_POST['user_id'];
    $teacher_id = $_SESSION['user_id'];

    try {
        // Check if the contact belongs to the teacher
        $query = "SELECT * FROM contacts WHERE id = ? AND teacher_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$contact_id, $teacher_id]);
        $contact = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$contact) {
            throw new Exception("You do not have permission to assign this contact.");
        }

        // Check if the user exists and is a regular user
        $query = "SELECT * FROM users WHERE id = ? AND role = 'user'";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            throw new Exception("Invalid user selected.");
        }

        // Assign the contact to the user
        $query = "UPDATE contacts SET user_id = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$user_id, $contact_id]);

        // Log the action
        $log_query = "INSERT INTO logs (user_id, teacher_id, action) VALUES (?, ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->execute([$teacher_id, $teacher_id, "Assigned contact {$contact['name']} to user {$user['name']}"]);

        header('Location: teacher_dashboard.php?success=1');
        exit();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>