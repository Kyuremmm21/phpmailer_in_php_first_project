<?php
session_start();

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$teacher_id = $_SESSION['user_id'];
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$stmt = $conn->prepare("SELECT * FROM teacher_submissions WHERE id = ? AND teacher_id = ? AND is_deleted = 0");
$stmt->execute([$submission_id, $teacher_id]);
$submission = $stmt->fetch();

// If submission not found or doesn't belong to the teacher
if (!$submission) {
    header('Location: teacher_dashboard.php');
    exit();
}

// If submission is not pending, don't allow editing
if ($submission['status'] !== 'pending') {
    header('Location: view_submission.php?id=' . $submission_id);
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $errors = [];

    // Validate input
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }

    if (empty($errors)) {
        try {
            $stmt = $conn->prepare("UPDATE teacher_submissions SET submission_title = ?, description = ?, updated_at = NOW() WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$title, $description, $submission_id, $teacher_id]);

            // Log the activity
            $stmt = $conn->prepare("INSERT INTO logs (user_id, action, timestamp) VALUES (?, 'Updated submission: ' . ?, NOW())");
            $stmt->execute([$teacher_id, $title]);

            header('Location: view_submission.php?id=' . $submission_id . '&success=submission_updated');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to update submission. Please try again.";
        }
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
    <title>Edit Submission</title>
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
            margin: 20px auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .header h1 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        .form-group input[type="text"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-edit"></i> Edit Submission</h1>
            <a href="view_submission.php?id=<?= $submission_id ?>" class="btn btn-danger">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="form-container">
            <form method="POST" action="">
                <div class="form-group">
                    <label for="title">Submission Title</label>
                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($submission['submission_title']) ?>" required>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" required><?= htmlspecialchars($submission['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn">
                        <i class="fas fa-save"></i> Update Submission
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html> 