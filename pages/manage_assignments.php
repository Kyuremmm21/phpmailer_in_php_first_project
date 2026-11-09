<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Handle search and filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$teacher_filter = $_GET['teacher'] ?? '';
$date_filter = $_GET['date'] ?? '';

// Build the query
$query = "
    SELECT a.*, 
           t.name as teacher_name,
           s.name as student_name
    FROM assignments a 
    JOIN users t ON a.teacher_id = t.id 
    JOIN users s ON a.user_id = s.id 
    WHERE a.is_deleted = 0
";

$params = [];

if ($search) {
    $query .= " AND (a.title LIKE ? OR a.description LIKE ? OR t.name LIKE ? OR s.name LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if ($status_filter) {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
}

if ($teacher_filter) {
    $query .= " AND a.teacher_id = ?";
    $params[] = $teacher_filter;
}

if ($date_filter) {
    $query .= " AND DATE(a.due_date) = ?";
    $params[] = $date_filter;
}

$query .= " ORDER BY a.created_at DESC";

// Get assignments
$stmt = $conn->prepare($query);
$stmt->execute($params);
$assignments = $stmt->fetchAll();

// Get all teachers for filter
$teachers = $conn->query("SELECT id, name FROM users WHERE role = 'teacher'")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Assignments</title>
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
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .filter-group label {
            font-weight: 500;
            color: #555;
        }
        .filter-group input,
        .filter-group select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
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
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }
        table th {
            background-color: #007BFF;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .status-badge {
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
        .actions {
            display: flex;
            gap: 8px;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-tasks"></i> Manage Assignments</h1>
            <div class="actions">
                <a href="admin_dashboard.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>

        <!-- Filters -->
        <form method="GET" class="filters">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Search assignments...">
            </div>
            
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Status</option>
                    <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="completed" <?= $status_filter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="overdue" <?= $status_filter === 'overdue' ? 'selected' : '' ?>>Overdue</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="teacher">Teacher</label>
                <select id="teacher" name="teacher">
                    <option value="">All Teachers</option>
                    <?php foreach ($teachers as $teacher): ?>
                        <option value="<?= $teacher['id'] ?>" <?= $teacher_filter == $teacher['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($teacher['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date">Due Date</label>
                <input type="date" id="date" name="date" value="<?= htmlspecialchars($date_filter) ?>">
            </div>
            
            <div class="filter-group" style="align-self: end;">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Apply Filters
                </button>
            </div>
        </form>

        <!-- Assignments Table -->
        <div class="table-container">
            <?php if (empty($assignments)): ?>
                <div class="no-results">
                    <i class="fas fa-info-circle"></i> No assignments found matching your criteria.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $assignment): ?>
                            <tr>
                                <td><?= htmlspecialchars($assignment['teacher_name']) ?></td>
                                <td><?= htmlspecialchars($assignment['student_name']) ?></td>
                                <td><?= htmlspecialchars($assignment['title']) ?></td>
                                <td><?= htmlspecialchars(substr($assignment['description'] ?? '', 0, 100)) . 
                                    (strlen($assignment['description'] ?? '') > 100 ? '...' : '') ?></td>
                                <td><?= date('M d, Y', strtotime($assignment['due_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= strtolower($assignment['status']) ?>">
                                        <?= ucfirst(htmlspecialchars($assignment['status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($assignment['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="view_assignment.php?id=<?= $assignment['id'] ?>" class="btn" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_assignment.php?id=<?= $assignment['id'] ?>" class="btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="delete_assignment.php?id=<?= $assignment['id'] ?>" 
                                       class="btn btn-danger" 
                                       onclick="return confirm('Are you sure you want to delete this assignment?')" 
                                       title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Auto-submit form when filters change
        document.querySelectorAll('.filters select, .filters input[type="date"]').forEach(element => {
            element.addEventListener('change', () => {
                element.form.submit();
            });
        });
    </script>
</body>
</html> 