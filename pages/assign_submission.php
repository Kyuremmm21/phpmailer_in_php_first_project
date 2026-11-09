<?php
/**
 * Assign Submission Page
 * 
 * This page allows teachers to assign submissions to students.
 * It handles the assignment of teacher submissions to specific students.
 * 
 * @author Your Name
 * @version 1.0
 */

session_start();

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

$teacher_id = $_SESSION['user_id'];
$submission_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Fetch submission details
$stmt = $conn->prepare("
    SELECT ts.*, u.name as teacher_name 
    FROM teacher_submissions ts 
    JOIN users u ON ts.teacher_id = u.id 
    WHERE ts.id = ? AND ts.teacher_id = ? AND ts.is_deleted = 0
");
$stmt->execute([$submission_id, $teacher_id]);
$submission = $stmt->fetch();

if (!$submission) {
    header("Location: teacher_dashboard.php");
    exit;
}

// Fetch all active students
$stmt = $conn->prepare("
    SELECT id, name, email 
    FROM users 
    WHERE role = 'user' AND status = 1 
    ORDER BY name ASC
");
$stmt->execute();
$students = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_POST['student_id'] ?? 0;
    $due_date = $_POST['due_date'] ?? '';

    if (empty($student_id) || empty($due_date)) {
        $error = "All fields are required.";
    } else {
        try {
            // Create new assignment from submission
            $stmt = $conn->prepare("
                INSERT INTO assignments (
                    title, 
                    description, 
                    teacher_id, 
                    user_id, 
                    due_date, 
                    status
                ) VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            
            if ($stmt->execute([
                $submission['submission_title'],
                $submission['description'],
                $teacher_id,
                $student_id,
                $due_date
            ])) {
                // Log the action
                $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                $stmt->execute([$teacher_id, "Assigned submission to student ID: $student_id"]);
                
                header("Location: teacher_dashboard.php?success=assignment_created");
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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Assign Submission</title>
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
        .form-group select,
        .form-group input[type="datetime-local"],
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
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
        .submission-info {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .submission-info h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .submission-info p {
            margin: 5px 0;
            color: #666;
        }
        .submission-details {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .submission-title {
            font-size: 1.2em;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .submission-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #4a5568;
            font-weight: 500;
        }
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #718096;
        }
        select, input[type="datetime-local"] {
            width: 100%;
            padding: 12px 12px 12px 40px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        select:focus, input[type="datetime-local"]:focus {
            outline: none;
            border-color: #4299e1;
        }
        .form-buttons {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Assign Submission</h1>
            <div class="nav-buttons">
                <a href="teacher_dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="submission-details">
            <div class="submission-title">
                <?= htmlspecialchars($submission['submission_title']) ?>
            </div>
            <div class="submission-description">
                <?= nl2br(htmlspecialchars($submission['description'])) ?>
            </div>
        </div>

        <form method="POST" action="assign_submission.php?id=<?= $submission_id ?>">
            <div class="form-group">
                <label for="student_id">Assign To</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <select id="student_id" name="student_id" required>
                        <option value="">Select Student</option>
                        <?php foreach ($students as $student): ?>
                            <option value="<?= $student['id'] ?>">
                                <?= htmlspecialchars($student['name']) ?> (<?= htmlspecialchars($student['email']) ?>)
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
                           min="<?= date('Y-m-d\TH:i') ?>">
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-check"></i> Assign to Student
                </button>
                <a href="teacher_dashboard.php" class="btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>

    <script>
        // Add any JavaScript functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add confirmation for assignment
            const form = document.querySelector('form');
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to assign this submission to the selected student?')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html> 