<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Validate inputs
    if (empty($name) || empty($email)) {
        echo "Name and email are required.";
        exit();
    }

    try {
        // Start building the query
        $query = "UPDATE users SET name = ?, email = ?";
        $params = [$name, $email];

        // Only update password if a new one is provided
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query .= ", password = ?";
            $params[] = $hashed_password;
        }

        $query .= " WHERE id = ?";
        $params[] = $_SESSION['user_id'];

        // Update the user's profile
        $stmt = $conn->prepare($query);
        $stmt->execute($params);

        // Redirect based on role
        $redirect = $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 
                   ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'user_dashboard.php');
        
        header('Location: ' . $redirect . '?success=1');
        exit();
    } catch (PDOException $e) {
        echo "Error updating profile: " . $e->getMessage();
        exit();
    }
}
?>