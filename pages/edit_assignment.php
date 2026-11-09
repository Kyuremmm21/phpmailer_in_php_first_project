<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins or teachers can access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'teacher'])) {
    header('Location: index.php');
    exit();
}

// Get assignment ID from URL
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'));
    exit();
}

// Get assignment details
$stmt = $conn->prepare("
    SELECT a.*, 
           t.name as teacher_name,
           s.name as student_name
    FROM assignments a 
    JOIN users t ON a.teacher_id = t.id 
    JOIN users s ON a.user_id = s.id 
    WHERE a.id = ? AND a.is_deleted = 0
    " . ($_SESSION['role'] === 'teacher' ? "AND a.teacher_id = ?" : "")
);
$params = [$assignment_id];
if ($_SESSION['role'] === 'teacher') {
    $params[] = $_SESSION['user_id'];
}
$stmt->execute($params);
$assignment = $stmt->fetch();

if (!$assignment) {
    header('Location: ' . ($_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php'));
    exit();
}

// Get all teachers and students for dropdowns (only for admin)
$teachers = [];
$students = [];
if ($_SESSION['role'] === 'admin') {
    $teachers = $conn->query("SELECT id, name FROM users WHERE role = 'teacher'")->fetchAll();
    $students = $conn->query("SELECT id, name FROM users WHERE role = 'user'")->fetchAll();
} else {
    // For teachers, only show their own students
    $students = $conn->query("SELECT id, name FROM users WHERE role = 'user'")->fetchAll();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $teacher_id = $_SESSION['role'] === 'admin' ? $_POST['teacher_id'] : $_SESSION['user_id'];
    $user_id = $_POST['user_id'];

    // Validate input
    $errors = [];
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($due_date)) {
        $errors[] = "Due date is required";
    }
    if (empty($user_id)) {
        $errors[] = "Student is required";
    }

    if (empty($errors)) {
        try {
            // Update assignment
            $stmt = $conn->prepare("
                UPDATE assignments 
                SET title = ?, 
                    description = ?, 
                    due_date = ?, 
                    status = ?, 
                    teacher_id = ?, 
                    user_id = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $due_date, $status, $teacher_id, $user_id, $assignment_id]);

            // Log the action - Remove the details column since it doesn't exist
            $stmt = $conn->prepare("
                INSERT INTO logs (user_id, action) 
                VALUES (?, 'edit_assignment')
            ");
            $stmt->execute([$_SESSION['user_id']]);

            header('Location: view_assignment.php?id=' . $assignment_id . '&success=updated');
            exit();
        } catch (PDOException $e) {
            $errors[] = "Failed to update assignment: " . $e->getMessage();
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
    <title>Edit Assignment</title>
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
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .header h1 {
            margin: 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn i {
            font-size: 16px;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .alert {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            font-size: 14px;
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
            <h1><i class="fas fa-edit"></i> Edit Assignment</h1>
            <div class="actions">
                <a href="<?= $_SESSION['role'] === 'admin' ? 'admin_dashboard.php' : 'teacher_dashboard.php' ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
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

        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Title</label>
                <input type="text" id="title" name="title" value="<?= htmlspecialchars($assignment['title']) ?>" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"><?= htmlspecialchars($assignment['description']) ?></textarea>
            </div>

            <div class="form-group">
                <label for="teacher_id">Teacher</label>
                <select id="teacher_id" name="teacher_id" required>
                    <option value="">Select Teacher</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>" <?= $teacher['id'] == $assignment['teacher_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="user_id">Student</label>
                <select id="user_id" name="user_id" required>
                    <option value="">Select Student</option>
                    <?php foreach ($students as $student): ?>
                        <option value="<?= $student['id'] ?>" <?= $student['id'] == $assignment['user_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($student['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="due_date">Due Date</label>
                <input type="date" id="due_date" name="due_date" 
                       value="<?= date('Y-m-d', strtotime($assignment['due_date'])) ?>" required>
            </div>

            <div class="form-group">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="pending" <?= $assignment['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $assignment['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="overdue" <?= $assignment['status'] === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>

            <div class="actions">
                <button type="submit" class="btn">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            
            </div>
        </form>
    </div>
</body>
</html> 