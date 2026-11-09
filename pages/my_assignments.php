<?php
/**
 * My Assignments Page
 * 
 * This page displays all assignments assigned to the logged-in user.
 * It allows users to view, submit, and track the status of their assignments.
 * 
 * @author Your Name
 * @version 1.0
 */

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Get user's assignments
$stmt = $conn->prepare("SELECT a.*, ts.submission_title, ts.description, ts.status as submission_status,
                              u.name as teacher_name, u2.name as assigned_by_name
                       FROM assignments a
                       JOIN teacher_submissions ts ON a.submission_id = ts.id
                       JOIN users u ON ts.teacher_id = u.id
                       JOIN users u2 ON a.assigned_by = u2.id
                       WHERE a.assigned_to = ? AND a.is_deleted = 0
                       ORDER BY a.due_date ASC");
$stmt->execute([$_SESSION['user_id']]);
$assignments = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assignment_id'], $_POST['status'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE assignments SET status = ?, updated_at = NOW() WHERE id = ? AND assigned_to = ?");
        $stmt->execute([$status, $assignment_id, $_SESSION['user_id']]);
        
        header('Location: my_assignments.php?success=status_updated');
        exit();
    } catch (PDOException $e) {
        $error = "Failed to update status. Please try again.";
    }
}

// Handle success/error messages
$success_message = '';
$error_message = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'status_updated':
            $success_message = "Assignment status updated successfully.";
            break;
        case 'submitted':
            $success_message = "Assignment submitted successfully.";
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'invalid_status':
            $error_message = "Invalid status provided.";
            break;
        case 'assignment_not_found':
            $error_message = "Assignment not found.";
            break;
        case 'update_failed':
            $error_message = "Failed to update assignment status.";
            break;
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
    <title>My Assignments</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 90%;
            max-width: 1200px;
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
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .assignment-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            padding: 20px;
            transition: transform 0.2s;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
        }
        .assignment-title {
            font-size: 18px;
            color: #333;
            margin: 0 0 15px 0;
        }
        .assignment-meta {
            margin-bottom: 15px;
            color: #666;
            font-size: 14px;
        }
        .assignment-meta p {
            margin: 5px 0;
        }
        .assignment-description {
            color: #444;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-in_progress {
            background-color: #17a2b8;
            color: #fff;
        }
        .status-completed {
            background-color: #28a745;
            color: #fff;
        }
        .status-cancelled {
            background-color: #dc3545;
            color: #fff;
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
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
        }
        .btn-warning {
            background-color: #ffc107;
            color: #000;
        }
        .btn-warning:hover {
            background-color: #e0a800;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .due-date {
            color: #dc3545;
            font-weight: 500;
        }
        .due-date.overdue {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> My Assignments</h1>
            <a href="dashboard.php" class="btn btn-danger">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <div class="assignments-grid">
            <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-card">
                    <h3 class="assignment-title"><?= htmlspecialchars($assignment['submission_title']) ?></h3>
                    
                    <div class="assignment-meta">
                        <p><i class="fas fa-user"></i> Teacher: <?= htmlspecialchars($assignment['teacher_name']) ?></p>
                        <p><i class="fas fa-user-tie"></i> Assigned by: <?= htmlspecialchars($assignment['assigned_by_name']) ?></p>
                        <p class="due-date <?= strtotime($assignment['due_date']) < time() ? 'overdue' : '' ?>">
                            <i class="fas fa-clock"></i> Due: <?= date('M d, Y H:i', strtotime($assignment['due_date'])) ?>
                        </p>
                    </div>

                    <div class="assignment-description">
                        <?= nl2br(htmlspecialchars($assignment['description'])) ?>
                    </div>

                    <?php if ($assignment['notes']): ?>
                        <div class="assignment-notes">
                            <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($assignment['notes'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <span class="status-badge status-<?= strtolower($assignment['status']) ?>">
                        <?= ucfirst(str_replace('_', ' ', $assignment['status'])) ?>
                    </span>

                    <div class="actions">
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="assignment_id" value="<?= $assignment['id'] ?>">
                            <?php if ($assignment['status'] === 'pending'): ?>
                                <button type="submit" name="status" value="in_progress" class="btn btn-warning">
                                    <i class="fas fa-play"></i> Start
                                </button>
                            <?php elseif ($assignment['status'] === 'in_progress'): ?>
                                <button type="submit" name="status" value="completed" class="btn btn-success">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            <?php endif; ?>
                            <?php if (in_array($assignment['status'], ['pending', 'in_progress'])): ?>
                                <button type="submit" name="status" value="cancelled" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($assignments)): ?>
                <div class="assignment-card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <h3 style="color: #666; margin: 0;">No assignments found</h3>
                    <p style="color: #888; margin: 10px 0 0 0;">You don't have any assignments at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 