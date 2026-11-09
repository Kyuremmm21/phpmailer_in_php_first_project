<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get all students
$stmt = $conn->prepare("SELECT id, name FROM users WHERE role = 'user' AND status = 1");
$stmt->execute();
$students = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $student_id = $_POST['student_id'] ?? 0;
    $due_date = $_POST['due_date'] ?? '';

    if (empty($title) || empty($description) || empty($student_id) || empty($due_date)) {
        $error = "All fields are required.";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO assignments (title, description, teacher_id, user_id, due_date) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$title, $description, $teacher_id, $student_id, $due_date])) {
                // Log the action
                $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                $stmt->execute([$teacher_id, "Created new assignment: $title"]);
                
                header("Location: teacher_dashboard.php?success=Assignment created successfully");
                exit;
            } else {
                $error = "Error creating assignment.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Assignment</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1><i class="fas fa-plus"></i> Create New Assignment</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="create_assignment.php" method="POST">
                <div class="form-group">
                    <label for="title">Assignment Title</label>
                    <div class="input-icon">
                        <i class="fas fa-heading"></i>
                        <input type="text" id="title" name="title" required 
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <div class="input-icon">
                        <i class="fas fa-align-left"></i>
                        <textarea id="description" name="description" rows="4" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-group">
                    <label for="student_id">Assign To</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                                <option value="<?= $student['id'] ?>" 
                                    <?= (isset($_POST['student_id']) && $_POST['student_id'] == $student['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($student['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="due_date">Due Date</label>
                    <div class="input-icon">
                        <i class="fas fa-calendar"></i>
                        <input type="datetime-local" id="due_date" name="due_date" required
                               value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-plus"></i> Create Assignment
                    </button>
                    <a href="teacher_dashboard.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: 500;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }
        .input-icon input,
        .input-icon textarea,
        .input-icon select {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .input-icon textarea {
            padding-top: 10px;
        }
        .input-icon select {
            appearance: none;
            background: #fff url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23666' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14L2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E") no-repeat right 10px center;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</body>
</html> 