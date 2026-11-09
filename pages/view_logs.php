<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

// Pagination setup
$logs_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $logs_per_page;

// Search functionality
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Action filter functionality
$action_filter = isset($_GET['action_filter']) ? $_GET['action_filter'] : '';
$action_condition = $action_filter ? "AND l.action = :action_filter" : '';

// Date range condition
$date_condition = '';
if ($start_date && $end_date) {
    $date_condition = "AND l.timestamp BETWEEN :start_date AND :end_date";
}

// Sorting functionality
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';

// Fetch logs with pagination, search, action filter, and sorting
$query = "SELECT l.*, u.name, u.email 
          FROM logs l 
          JOIN users u ON l.user_id = u.id 
          WHERE (u.name LIKE :search OR l.action LIKE :search OR u.email LIKE :search) 
          $action_condition $date_condition 
          ORDER BY $sort_column $sort_order 
          LIMIT :limit OFFSET :offset";

$stmt = $conn->prepare($query);
$stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
if ($action_filter) {
    $stmt->bindValue(':action_filter', $action_filter, PDO::PARAM_STR);
}
if ($start_date && $end_date) {
    $stmt->bindValue(':start_date', $start_date . ' 00:00:00', PDO::PARAM_STR);
    $stmt->bindValue(':end_date', $end_date . ' 23:59:59', PDO::PARAM_STR);
}
$stmt->bindValue(':limit', $logs_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get total log count for pagination
$count_query = "SELECT COUNT(*) FROM logs l 
                JOIN users u ON l.user_id = u.id 
                WHERE (u.name LIKE :search OR l.action LIKE :search OR u.email LIKE :search) 
                $action_condition $date_condition";
$count_stmt = $conn->prepare($count_query);
$count_stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
if ($action_filter) {
    $count_stmt->bindValue(':action_filter', $action_filter, PDO::PARAM_STR);
}
if ($start_date && $end_date) {
    $count_stmt->bindValue(':start_date', $start_date . ' 00:00:00', PDO::PARAM_STR);
    $count_stmt->bindValue(':end_date', $end_date . ' 23:59:59', PDO::PARAM_STR);
}
$count_stmt->execute();
$total_logs = $count_stmt->fetchColumn();
$total_pages = ceil($total_logs / $logs_per_page);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>System Logs</title>
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
            padding: 20px;
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
        .btn-success {
            background-color: #28a745;
        }
        .btn-success:hover {
            background-color: #218838;
        }
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: #fff;
        }
        table th, table td {
            border: 1px solid #e0e0e0;
            padding: 12px;
            text-align: left;
        }
        table th {
            background-color: #007BFF;
            color: #fff;
            font-weight: 600;
            white-space: nowrap;
        }
        table th a {
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        table th a:hover {
            text-decoration: underline;
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table tr:hover {
            background-color: #f1f1f1;
        }
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
        }
        .pagination a {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #007BFF;
            transition: all 0.3s;
        }
        .pagination a:hover {
            background-color: #007BFF;
            color: #fff;
            border-color: #007BFF;
        }
        .pagination a.active {
            background-color: #007BFF;
            color: #fff;
            border-color: #007BFF;
        }
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #6c757d;
            text-decoration: none;
            margin-top: 20px;
        }
        .back-link:hover {
            color: #007BFF;
        }
        .no-logs {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        @media (max-width: 768px) {
            .filters {
                grid-template-columns: 1fr;
            }
            .table-container {
                margin: 10px -20px;
            }
            table th, table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-history"></i> System Logs</h1>
            <a href="admin_dashboard.php" class="btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <form method="GET" action="view_logs.php" class="filters">
            <div class="filter-group">
                <label for="search">Search</label>
                <input type="text" id="search" name="search" placeholder="Search logs..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="filter-group">
                <label for="action_filter">Action Type</label>
                <select name="action_filter" id="action_filter">
                    <option value="">All Actions</option>
                    <option value="Login" <?= $action_filter === 'Login' ? 'selected' : '' ?>>Login</option>
                    <option value="Registration" <?= $action_filter === 'Registration' ? 'selected' : '' ?>>Registration</option>
                    <option value="Contact" <?= $action_filter === 'Contact' ? 'selected' : '' ?>>Contact</option>
                    <option value="Update" <?= $action_filter === 'Update' ? 'selected' : '' ?>>Update</option>
                    <option value="Delete" <?= $action_filter === 'Delete' ? 'selected' : '' ?>>Delete</option>
                </select>
            </div>
            <div class="filter-group">
                <label for="start_date">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="filter-group">
                <label for="end_date">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="filter-group" style="align-self: end;">
                <button type="submit" class="btn">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>

        <div class="table-container">
            <?php if (empty($logs)): ?>
                <div class="no-logs">
                    <i class="fas fa-info-circle"></i> No logs found matching your criteria.
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>
                                <a href="?sort=name&order=<?= $sort_order === 'ASC' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>">
                                    <i class="fas fa-user"></i> User
                                </a>
                            </th>
                            <th>
                                <a href="?sort=email&order=<?= $sort_order === 'ASC' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>">
                                    <i class="fas fa-envelope"></i> Email
                                </a>
                            </th>
                            <th>
                                <a href="?sort=action&order=<?= $sort_order === 'ASC' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>">
                                    <i class="fas fa-tasks"></i> Action
                                </a>
                            </th>
                            <th>
                                <a href="?sort=timestamp&order=<?= $sort_order === 'ASC' ? 'desc' : 'asc' ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>">
                                    <i class="fas fa-clock"></i> Timestamp
                                </a>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['name']) ?></td>
                                <td><?= htmlspecialchars($log['email']) ?></td>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&sort=<?= htmlspecialchars($sort_column) ?>&order=<?= htmlspecialchars($sort_order) ?>">
                        <i class="fas fa-chevron-left"></i> Previous
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&sort=<?= htmlspecialchars($sort_column) ?>&order=<?= htmlspecialchars($sort_order) ?>" 
                       class="<?= $i === $page ? 'active' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&search=<?= htmlspecialchars($search) ?>&action_filter=<?= htmlspecialchars($action_filter) ?>&start_date=<?= htmlspecialchars($start_date) ?>&end_date=<?= htmlspecialchars($end_date) ?>&sort=<?= htmlspecialchars($sort_column) ?>&order=<?= htmlspecialchars($sort_order) ?>">
                        Next <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>