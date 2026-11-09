<!-- filepath: add_contact.php -->
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
    $phone = trim($_POST['phone']);
    $user_id = $_SESSION['user_id'];
    $teacher_id = ($_SESSION['role'] === 'teacher') ? $user_id : null;

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone)) {
        echo "All fields are required.";
        exit();
    }

    try {
        // Insert the new contact into the database
        $query = "INSERT INTO contacts (name, email, phone, teacher_id, user_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->execute([$name, $email, $phone, $teacher_id, $user_id]);

        // Log the action
        $log_query = "INSERT INTO logs (user_id, action) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->execute([$user_id, "Added new contact: $name"]);

        // Redirect based on user role
        if ($_SESSION['role'] === 'teacher') {
            header('Location: teacher_dashboard.php?success=1');
        } else {
            header('Location: user_dashboard.php?success=1');
        }
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Add Contact</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #007BFF;
            outline: none;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
            width: 100%;
            justify-content: center;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn i {
            font-size: 16px;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            text-decoration: none;
            margin-top: 20px;
        }
        .back-link:hover {
            color: #007BFF;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .input-icon input {
            padding-left: 35px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-user-plus"></i> Add New Contact</h1>
        <form action="add_contact.php" method="POST">
            <div class="form-group">
                <label for="name">Contact Name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" placeholder="Enter contact name" required>
                </div>
            </div>
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" placeholder="Enter phone number" required>
                </div>
            </div>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" placeholder="Enter email address" required>
                </div>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Save Contact
            </button>
        </form>
        <a href="<?= $_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'user_dashboard.php' ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>