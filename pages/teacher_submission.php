<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $teacher_id = $_SESSION['user_id'];

    // Validate inputs
    if (empty($title) || empty($content)) {
        echo "Both title and content are required.";
        exit();
    }

    try {
        // Insert the submission
        $query = "INSERT INTO teacher_submissions (teacher_id, submission_title, submission_content) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$teacher_id, $title, $content]);

        // Log the action
        $log_query = "INSERT INTO logs (user_id, teacher_id, action) VALUES (?, ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->execute([$teacher_id, $teacher_id, "Submitted new content: $title"]);

        header('Location: teacher_dashboard.php?success=1');
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
    <title>Submit Content</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 90%;
            max-width: 800px;
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
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group textarea {
            min-height: 200px;
            resize: vertical;
        }
        .form-group input:focus,
        .form-group textarea:focus {
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
        <h1><i class="fas fa-file-alt"></i> Submit New Content</h1>
        <form action="teacher_submission.php" method="POST">
            <div class="form-group">
                <label for="title">Submission Title</label>
                <div class="input-icon">
                    <i class="fas fa-heading"></i>
                    <input type="text" id="title" name="title" placeholder="Enter submission title" required>
                </div>
            </div>
            <div class="form-group">
                <label for="content">Content</label>
                <textarea id="content" name="content" placeholder="Enter your content here..." required></textarea>
            </div>
            <button type="submit" class="btn">
                <i class="fas fa-paper-plane"></i> Submit Content
            </button>
        </form>
        <a href="teacher_dashboard.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html> 