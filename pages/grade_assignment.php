<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

$teacher_id = $_SESSION['user_id'];
$assignment_id = $_GET['id'] ?? 0;
$error = '';
$success = '';

// Fetch assignment details with student information
$stmt = $conn->prepare("SELECT a.*, u.name as student_name 
                       FROM assignments a 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.id = ? AND a.teacher_id = ? AND a.is_deleted = 0");
$stmt->execute([$assignment_id, $teacher_id]);
$assignment = $stmt->fetch();

if (!$assignment || $assignment['status'] !== 'completed') {
    header("Location: teacher_dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $grade = $_POST['grade'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');

    if (empty($grade) || empty($feedback)) {
        $error = "Grade and feedback are required.";
    } elseif (!is_numeric($grade) || $grade < 0 || $grade > 100) {
        $error = "Grade must be a number between 0 and 100.";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE assignments SET grade = ?, feedback = ? WHERE id = ?");
            if ($stmt->execute([$grade, $feedback, $assignment_id])) {
                // Log the action
                $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                $stmt->execute([$teacher_id, "Graded assignment: {$assignment['title']}"]);
                
                header("Location: teacher_dashboard.php?success=Assignment graded successfully");
                exit;
            } else {
                $error = "Error updating assignment.";
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
    <title>Grade Assignment</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1><i class="fas fa-star"></i> Grade Assignment</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="assignment-details">
                <h2><?= htmlspecialchars($assignment['title']) ?></h2>
                <p class="description"><?= htmlspecialchars($assignment['description']) ?></p>
                
                <div class="info-grid">
                    <div class="info-item">
                        <i class="fas fa-user"></i>
                        <span>Student: <?= htmlspecialchars($assignment['student_name']) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-calendar"></i>
                        <span>Due Date: <?= date('F d, Y h:i A', strtotime($assignment['due_date'])) ?></span>
                    </div>
                    <div class="info-item">
                        <i class="fas fa-upload"></i>
                        <span>Submitted: <?= date('F d, Y h:i A', strtotime($assignment['submission_date'])) ?></span>
                    </div>
                </div>

                <?php if ($assignment['submission_file']): ?>
                <div class="submission-info">
                    <h3>Student's Submission</h3>
                    <p>
                        <i class="fas fa-file"></i>
                        File: <?= htmlspecialchars($assignment['submission_file']) ?>
                    </p>
                    <a href="download_assignment.php?id=<?= $assignment_id ?>" class="btn">
                        <i class="fas fa-download"></i> Download Submission
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <form action="grade_assignment.php?id=<?= $assignment_id ?>" method="POST">
                <div class="form-group">
                    <label for="grade">Grade (0-100)</label>
                    <div class="input-icon">
                        <i class="fas fa-star"></i>
                        <input type="number" id="grade" name="grade" min="0" max="100" step="0.01" required
                               value="<?= htmlspecialchars($_POST['grade'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label for="feedback">Feedback</label>
                    <div class="input-icon">
                        <i class="fas fa-comment"></i>
                        <textarea id="feedback" name="feedback" rows="4" required><?= htmlspecialchars($_POST['feedback'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Submit Grade
                    </button>
                    <a href="teacher_dashboard.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .assignment-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .assignment-details h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .description {
            color: #666;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #666;
        }
        .info-item i {
            color: #007BFF;
            width: 20px;
        }
        .submission-info {
            background: #fff;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .submission-info h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 16px;
        }
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
        .input-icon textarea {
            width: 100%;
            padding: 10px 10px 10px 35px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .input-icon textarea {
            padding-top: 10px;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
    </style>
</body>
</html> 