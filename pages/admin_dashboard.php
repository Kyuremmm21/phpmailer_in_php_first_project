<?php
session_start();

// Ensure only admins can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../config/database.php';

// Get user statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'total_teachers' => $conn->query("SELECT COUNT(*) FROM users WHERE role = 'teacher'")->fetchColumn(),
    'total_contacts' => $conn->query("SELECT COUNT(*) FROM contacts WHERE is_deleted = 0")->fetchColumn(),
    'total_assignments' => $conn->query("SELECT COUNT(*) FROM assignments WHERE is_deleted = 0")->fetchColumn()
];

// Get recent users
$stmt = $conn->query("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 5");
$recent_users = $stmt->fetchAll();

// Get recent assignments with teacher and student names
$stmt = $conn->query("
    SELECT a.*, 
           t.name as teacher_name,
           s.name as student_name
    FROM assignments a 
    JOIN users t ON a.teacher_id = t.id 
    JOIN users s ON a.user_id = s.id 
    WHERE a.is_deleted = 0 
    ORDER BY a.created_at DESC LIMIT 5
");
$recent_assignments = $stmt->fetchAll();

// Get recent activity logs
$stmt = $conn->query("
    SELECT l.*, u.name, u.email 
    FROM logs l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.timestamp DESC LIMIT 5
");
$recent_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <title>Admin Dashboard</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
            color: #333;
        }
        h1, h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 20px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .logout {
            text-align: right;
            margin-bottom: 20px;
        }
        a {
            text-decoration: none;
            color: #007BFF;
        }
        a:hover {
            text-decoration: underline;
        }

        /* Table Styles */
        .table-container {
            overflow-x: auto;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #007BFF;
            color: #fff;
        }
        table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }

        /* Icon Button Styles */
        .icon-button {
            background: none;
            border: none;
            color: #007BFF;
            font-size: 16px;
            cursor: pointer;
            padding: 5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
        }
        .icon-button:hover {
            color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .table-container {
                overflow-x: auto;
            }
            table, thead, tbody, th, td, tr {
                display: block;
            }
            table tr {
                margin-bottom: 15px;
            }
            table th {
                display: none;
            }
            table td {
                display: flex;
                justify-content: space-between;
                padding: 10px;
                border: 1px solid #ddd;
            }
            table td::before {
                content: attr(data-label);
                font-weight: bold;
                margin-right: 10px;
            }
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
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-card i {
            font-size: 2em;
            color: #007BFF;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            margin: 0;
            color: #333;
            font-size: 1.5em;
        }
        .stat-card p {
            margin: 5px 0 0;
            color: #666;
        }
        .section {
            background: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
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
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .status-active {
            background-color: #28a745;
            color: #fff;
        }
        .status-inactive {
            background-color: #dc3545;
            color: #fff;
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
            gap: 10px;
        }
        .chart-container {
            margin-top: 20px;
            height: 300px;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-shield"></i> Admin Dashboard</h1>
            <div class="actions">
                <a href="create_teacher.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Create Teacher
                </a>
                <a href="profile.php" class="btn">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'user_activated':
                        echo 'User has been activated successfully.';
                        break;
                    case 'user_deactivated':
                        echo 'User has been deactivated successfully.';
                        break;
                    default:
                        echo 'Operation completed successfully.';
                }
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php
                switch ($_GET['error']) {
                    case 'user_not_found':
                        echo 'User not found.';
                        break;
                    case 'update_failed':
                        echo 'Failed to update user status.';
                        break;
                    default:
                        echo 'An error occurred.';
                }
                ?>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users"></i>
                <h3><?= $stats['total_users'] ?></h3>
                <p>Total Users</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?= $stats['total_teachers'] ?></h3>
                <p>Total Teachers</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-address-book"></i>
                <h3><?= $stats['total_contacts'] ?></h3>
                <p>Total Contacts</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <h3><?= $stats['total_assignments'] ?></h3>
                <p>Total Assignments</p>
            </div>
        </div>

        <!-- Recent Assignments -->
        <div class="section">
            <div class="header-actions">
                <h2><i class="fas fa-tasks"></i> Recent Assignments</h2>
                <div class="actions">
                    <a href="manage_assignments.php" class="btn">
                        <i class="fas fa-cog"></i> Manage All Assignments
                    </a>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Student</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_assignments)): ?>
                            <tr>
                                <td colspan="7" style="text-align: center;">No assignments found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_assignments as $assignment): ?>
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
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="section">
            <div class="header-actions">
                <h2><i class="fas fa-users"></i> Recent Users</h2>
                <a href="manage_users.php" class="btn">
                    <i class="fas fa-cog"></i> Manage Users
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $user['status'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td class="actions">
                                    <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="toggle_user_status.php?id=<?= $user['id'] ?>" 
                                       class="btn <?= $user['status'] ? 'btn-danger' : 'btn-success' ?>" 
                                       title="<?= $user['status'] ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas <?= $user['status'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Activity Logs -->
        <div class="section">
            <div class="header-actions">
                <h2><i class="fas fa-history"></i> Recent Activity</h2>
                <a href="view_logs.php" class="btn">
                    <i class="fas fa-list"></i> View All Logs
                </a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['name']) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add any JavaScript for charts or interactive features here
    </script>
</body>
</html>