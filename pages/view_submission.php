<?php
session_start();

// Ensure only teachers and admins can access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['teacher', 'admin'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Get submission ID
$submission_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get submission details
$query = "SELECT ts.*, u.name as teacher_name 
          FROM teacher_submissions ts 
          JOIN users u ON ts.teacher_id = u.id 
          WHERE ts.id = ? AND ts.is_deleted = 0";

// If user is a teacher, only show their own submissions
if ($_SESSION['role'] === 'teacher') {
    $query .= " AND ts.teacher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$submission_id, $_SESSION['user_id']]);
} else {
    $stmt = $conn->prepare($query);
    $stmt->execute([$submission_id]);
}

$submission = $stmt->fetch();

// If submission not found or teacher trying to access another teacher's submission
if (!$submission) {
    header('Location: ' . ($_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'admin_dashboard.php'));
    exit();
}

// Get assignment details if exists
$stmt = $conn->prepare("SELECT a.*, u.name as assigned_to_name, u2.name as assigned_by_name 
                       FROM assignments a 
                       JOIN users u ON a.assigned_to = u.id 
                       JOIN users u2 ON a.assigned_by = u2.id 
                       WHERE a.submission_id = ? AND a.is_deleted = 0");
$stmt->execute([$submission_id]);
$assignment = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>View Submission</title>
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
        .submission-container {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .submission-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .submission-title {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        .submission-meta {
            display: flex;
            gap: 20px;
            color: #666;
            font-size: 14px;
        }
        .submission-description {
            margin-bottom: 30px;
            line-height: 1.6;
            color: #444;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .status-approved {
            background-color: #28a745;
            color: #fff;
        }
        .status-rejected {
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
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .assignment-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .assignment-info h3 {
            margin: 0 0 15px 0;
            color: #333;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .assignment-info p {
            margin: 8px 0;
            color: #666;
        }
        .assignment-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 500;
            margin-top: 10px;
        }
        .assignment-status-pending {
            background-color: #ffc107;
            color: #000;
        }
        .assignment-status-in_progress {
            background-color: #17a2b8;
            color: #fff;
        }
        .assignment-status-completed {
            background-color: #28a745;
            color: #fff;
        }
        .assignment-status-cancelled {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-file-alt"></i> Submission Details</h1>
            <a href="<?= $_SESSION['role'] === 'teacher' ? 'teacher_dashboard.php' : 'admin_dashboard.php' ?>" class="btn btn-danger">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="submission-container">
            <div class="submission-header">
                <h2 class="submission-title"><?= htmlspecialchars($submission['submission_title']) ?></h2>
                <div class="submission-meta">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($submission['teacher_name']) ?></span>
                    <span><i class="fas fa-clock"></i> Submitted: <?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></span>
                    <span><i class="fas fa-sync"></i> Last Updated: <?= date('M d, Y H:i', strtotime($submission['updated_at'])) ?></span>
                    <span class="status-badge status-<?= strtolower($submission['status']) ?>">
                        <?= ucfirst(htmlspecialchars($submission['status'])) ?>
                    </span>
                </div>
            </div>

            <div class="submission-description">
                <?= nl2br(htmlspecialchars($submission['description'])) ?>
            </div>

            <?php if ($assignment): ?>
            <div class="assignment-info">
                <h3><i class="fas fa-tasks"></i> Assignment Information</h3>
                <p><strong>Assigned To:</strong> <?= htmlspecialchars($assignment['assigned_to_name']) ?></p>
                <p><strong>Assigned By:</strong> <?= htmlspecialchars($assignment['assigned_by_name']) ?></p>
                <p><strong>Due Date:</strong> <?= date('M d, Y H:i', strtotime($assignment['due_date'])) ?></p>
                <?php if ($assignment['notes']): ?>
                    <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($assignment['notes'])) ?></p>
                <?php endif; ?>
                <span class="assignment-status assignment-status-<?= strtolower($assignment['status']) ?>">
                    <?= ucfirst(str_replace('_', ' ', $assignment['status'])) ?>
                </span>
            </div>
            <?php endif; ?>

            <div class="actions">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <?php if ($submission['status'] === 'pending'): ?>
                        <a href="approve_submission.php?id=<?= $submission['id'] ?>" class="btn btn-success" title="Approve">
                            <i class="fas fa-check"></i> Approve
                        </a>
                        <a href="reject_submission.php?id=<?= $submission['id'] ?>" class="btn btn-danger" title="Reject">
                            <i class="fas fa-times"></i> Reject
                        </a>
                    <?php endif; ?>
                    <a href="assign_submission.php?id=<?= $submission['id'] ?>" class="btn" title="Assign">
                        <i class="fas fa-user-plus"></i> <?= $assignment ? 'Update Assignment' : 'Assign' ?>
                    </a>
                <?php endif; ?>

                <?php if ($_SESSION['role'] === 'teacher' && $submission['status'] === 'pending'): ?>
                    <a href="edit_submission.php?id=<?= $submission['id'] ?>" class="btn" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="delete_submission.php?id=<?= $submission['id'] ?>" 
                       class="btn btn-danger" 
                       onclick="return confirm('Are you sure you want to delete this submission?')" 
                       title="Delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 