<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get assignment ID from URL
$assignment_id = $_GET['id'] ?? null;

if (!$assignment_id) {
    // Redirect based on role
    $redirect = match($_SESSION['role'] ?? 'user') {
        'admin' => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        default => 'user_dashboard.php'
    };
    header('Location: ' . $redirect);
    exit();
}

// Build query based on role
$query = "
    SELECT a.*, 
           t.name as teacher_name,
           s.name as student_name
    FROM assignments a 
    JOIN users t ON a.teacher_id = t.id 
    JOIN users s ON a.user_id = s.id 
    WHERE a.id = ? AND a.is_deleted = 0
";

$params = [$assignment_id];

// Add role-specific conditions
if ($_SESSION['role'] === 'teacher') {
    $query .= " AND a.teacher_id = ?";
    $params[] = $_SESSION['user_id'];
} elseif ($_SESSION['role'] === 'user') {
    $query .= " AND a.user_id = ?";
    $params[] = $_SESSION['user_id'];
}
// Admin can see all assignments, no additional condition needed

$stmt = $conn->prepare($query);
$stmt->execute($params);
$assignment = $stmt->fetch();

if (!$assignment) {
    // Redirect based on role
    $redirect = match($_SESSION['role'] ?? 'user') {
        'admin' => 'admin_dashboard.php',
        'teacher' => 'teacher_dashboard.php',
        default => 'user_dashboard.php'
    };
    header('Location: ' . $redirect);
    exit();
}

// Determine which dashboard to return to
$dashboard = match($_SESSION['role'] ?? 'user') {
    'admin' => 'admin_dashboard.php',
    'teacher' => 'teacher_dashboard.php',
    default => 'user_dashboard.php'
};
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>View Assignment</title>
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
        .assignment-details {
            margin-bottom: 30px;
        }
        .detail-group {
            margin-bottom: 20px;
        }
        .detail-group label {
            display: block;
            font-weight: 500;
            color: #555;
            margin-bottom: 5px;
        }
        .detail-group .value {
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #ffeeba;
            color: #856404;
        }
        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }
        .status-overdue {
            background-color: #f8d7da;
            color: #721c24;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Assignment Details</h1>
            <div class="actions">
                <a href="<?= $dashboard ?>" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <div class="assignment-details">
            <div class="detail-group">
                <label>Title</label>
                <div class="value"><?= htmlspecialchars($assignment['title']) ?></div>
            </div>

            <div class="detail-group">
                <label>Description</label>
                <div class="value"><?= nl2br(htmlspecialchars($assignment['description'])) ?></div>
            </div>

            <div class="detail-group">
                <label>Teacher</label>
                <div class="value"><?= htmlspecialchars($assignment['teacher_name']) ?></div>
            </div>

            <div class="detail-group">
                <label>Due Date</label>
                <div class="value"><?= date('F d, Y', strtotime($assignment['due_date'])) ?></div>
            </div>

            <div class="detail-group">
                <label>Status</label>
                <div class="value">
                    <span class="status-badge status-<?= strtolower($assignment['status']) ?>">
                        <?= ucfirst(htmlspecialchars($assignment['status'])) ?>
                    </span>
                </div>
            </div>

            <?php if ($assignment['status'] !== 'completed'): ?>
            <div class="actions">
                <a href="submit_assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-success">
                    <i class="fas fa-upload"></i> Submit Assignment
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 