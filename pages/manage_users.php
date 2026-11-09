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
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build the query
$query = "SELECT * FROM users WHERE role != 'admin'";
$params = [];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($role_filter)) {
    $query .= " AND role = ?";
    $params[] = $role_filter;
}

if ($status_filter !== '') {
    $query .= " AND status = ?";
    $params[] = $status_filter;
}

$query .= " ORDER BY created_at DESC";

// Execute the query
$stmt = $conn->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Manage Users</title>
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
        .status-active {
            background-color: #28a745;
            color: #fff;
        }
        .status-inactive {
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users-cog"></i> Manage Users</h1>
            <a href="admin_dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php
                switch ($_GET['success']) {
                    case 'user_created':
                        echo 'User has been created successfully.';
                        break;
                    case 'user_updated':
                        echo 'User has been updated successfully.';
                        break;
                    case 'user_deleted':
                        echo 'User has been deleted successfully.';
                        break;
                    default:
                        echo 'Operation completed successfully.';
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="filters">
            <form method="GET" action="">
                <input type="text" name="search" placeholder="Search by name or email" value="<?= htmlspecialchars($search) ?>">
                <select name="role">
                    <option value="">All Roles</option>
                    <option value="teacher" <?= $role_filter === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                    <option value="user" <?= $role_filter === 'user' ? 'selected' : '' ?>>User</option>
                </select>
                <select name="status">
                    <option value="">All Status</option>
                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit">Apply Filters</button>
                <?php if (!empty($search) || !empty($role_filter) || $status_filter !== ''): ?>
                    <a href="manage_users.php" class="btn btn-danger">Clear Filters</a>
                <?php endif; ?>
            </form>
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
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= ucfirst(htmlspecialchars($user['role'])) ?></td>
                            <td>
                                <span class="status-badge <?= $user['status'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $user['status'] ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><?= date('M d, Y H:i', strtotime($user['created_at'])) ?></td>
                            <td><?= $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never' ?></td>
                            <td class="actions">
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="toggle_user_status.php?id=<?= $user['id'] ?>" 
                                   class="btn <?= $user['status'] ? 'btn-danger' : 'btn-success' ?>" 
                                   title="<?= $user['status'] ? 'Deactivate' : 'Activate' ?>">
                                    <i class="fas <?= $user['status'] ? 'fa-user-slash' : 'fa-user-check' ?>"></i>
                                </a>
                                <a href="delete_user.php?id=<?= $user['id'] ?>" 
                                   class="btn btn-danger" 
                                   onclick="return confirm('Are you sure you want to delete this user?')" 
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