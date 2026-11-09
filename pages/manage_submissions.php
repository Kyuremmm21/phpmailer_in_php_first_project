<?php
session_start();

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Handle search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$teacher_filter = isset($_GET['teacher_id']) ? (int)$_GET['teacher_id'] : '';

// Get all teachers for filter dropdown
$stmt = $conn->query("SELECT id, name FROM users WHERE role = 'teacher' ORDER BY name");
$teachers = $stmt->fetchAll();

// Build the query
$query = "SELECT ts.*, u.name as teacher_name 
          FROM teacher_submissions ts 
          JOIN users u ON ts.teacher_id = u.id 
          WHERE ts.is_deleted = 0";
$params = [];

if (!empty($search)) {
    $query .= " AND (ts.submission_title LIKE ? OR ts.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($status_filter)) {
    $query .= " AND ts.status = ?";
    $params[] = $status_filter;
}

if (!empty($teacher_filter)) {
    $query .= " AND ts.teacher_id = ?";
    $params[] = $teacher_filter;
}

$query .= " ORDER BY ts.created_at DESC";

// Execute the query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$submissions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Submissions</title>
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
        .filters {
            background: #fff;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .filters form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filters input, .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .filters button {
            padding: 8px 16px;
            background: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .table-container {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: 600;
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
        .btn-success {
            background-color: #28a745;
        }
        .btn-danger {
            background-color: #dc3545;
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
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
        .actions {
            display: flex;
            gap: 8px;
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
        .description-cell {
            max-width: 300px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Manage Submissions</h1>
            <div class="actions">
                <a href="export_submissions.php" class="btn btn-success">
                    <i class="fas fa-download"></i> Export Data
                </a>
                <a href="admin_dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'submission_approved':
                        echo 'Submission has been approved successfully.';
                        break;
                    case 'submission_rejected':
                        echo 'Submission has been rejected successfully.';
                        break;
                    case 'submission_deleted':
                        echo 'Submission has been deleted successfully.';
                        break;
                    default:
                        echo 'Operation completed successfully.';
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by title or description" value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status_filter === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="rejected" <?= $status_filter === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
                <select name="teacher_id">
                    <option value="">All Teachers</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>" <?= $teacher_filter === $teacher['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Apply Filters</button>
                <?php if (!empty($search) || !empty($status_filter) || !empty($teacher_filter)): ?>
                    <a href="manage_submissions.php" class="btn btn-danger">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?= htmlspecialchars($submission['teacher_name']) ?></td>
                            <td><?= htmlspecialchars($submission['submission_title']) ?></td>
                            <td class="description-cell" title="<?= htmlspecialchars($submission['description']) ?>">
                                <?= htmlspecialchars($submission['description']) ?>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower($submission['status']) ?>">
                                    <?= ucfirst(htmlspecialchars($submission['status'])) ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($submission['created_at'])) ?></td>
                            <td><?= date('M d, Y H:i', strtotime($submission['updated_at'])) ?></td>
                            <td class="actions">
                                <a href="view_submission.php?id=<?= $submission['id'] ?>" class="btn" title="View Details">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($submission['status'] === 'pending'): ?>
                                    <a href="approve_submission.php?id=<?= $submission['id'] ?>" class="btn btn-success" title="Approve">
                                        <i class="fas fa-check"></i>
                                    </a>
                                    <a href="reject_submission.php?id=<?= $submission['id'] ?>" class="btn btn-danger" title="Reject">
                                        <i class="fas fa-times"></i>
                                    </a>
                                <?php endif; ?>
                                <a href="edit_submission.php?id=<?= $submission['id'] ?>" class="btn" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="delete_submission.php?id=<?= $submission['id'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this submission?')" 
                                   title="Delete">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html> 