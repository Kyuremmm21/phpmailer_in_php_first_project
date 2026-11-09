<?php
session_start();

// Include security class
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../config/database.php';

// Initialize security
$security = new Security($conn);
$security->init_security();

// Ensure only teachers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header('Location: index.php');
    exit();
}

// Update last activity timestamp
$_SESSION['last_activity'] = time();

$teacher_id = $_SESSION['user_id'];

// Get teacher's contacts with DISTINCT to avoid duplicates
$stmt = $conn->prepare("
    SELECT DISTINCT c.* 
    FROM contacts c 
    WHERE c.teacher_id = ? 
    AND c.is_deleted = 0 
    ORDER BY c.name ASC
");
$stmt->execute([$teacher_id]);
$contacts = $stmt->fetchAll();

// Get teacher's submissions
$stmt = $conn->prepare("SELECT * FROM teacher_submissions WHERE teacher_id = ? AND is_deleted = 0 ORDER BY created_at DESC");
$stmt->execute([$teacher_id]);
$submissions = $stmt->fetchAll();

// Get teacher's assignments
$stmt = $conn->prepare("SELECT a.*, u.name as student_name 
                       FROM assignments a 
                       JOIN users u ON a.user_id = u.id 
                       WHERE a.teacher_id = ? AND a.is_deleted = 0 
                       ORDER BY a.due_date DESC");
$stmt->execute([$teacher_id]);
$assignments = $stmt->fetchAll();

// Get teacher's recent activity logs
$stmt = $conn->prepare("SELECT l.*, u.name as user_name 
                       FROM logs l 
                       JOIN users u ON l.user_id = u.id 
                       WHERE l.user_id = ? 
                       ORDER BY l.timestamp DESC LIMIT 5");
$stmt->execute([$teacher_id]);
$recent_logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Teacher Dashboard</title>
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
        .table-container {
            overflow-x: auto;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
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
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        table tr:hover {
            background-color: #f1f1f1;
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
        .actions {
            display: flex;
            gap: 10px;
        }
        .search-box {
            margin-bottom: 20px;
        }
        .search-box input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .search-box input:focus {
            border-color: #007BFF;
            outline: none;
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
        .assignment-description {
            font-size: 0.8em;
            color: #666;
        }
        .due-date {
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-chalkboard-teacher"></i> Teacher Dashboard</h1>
            <div class="actions">
                <a href="profile.php" class="btn">
                    <i class="fas fa-user"></i> Profile
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Contacts Section -->
        <div class="section">
            <div class="header-actions">
                <h2><i class="fas fa-address-book"></i> My Contacts</h2>
                <a href="add_contact.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Add New Contact
                </a>
            </div>
            
            <div class="search-box">
                <input type="text" id="contactSearch" placeholder="Search contacts...">
            </div>

            <div class="table-container">
                <?php if (empty($contacts)): ?>
                    <div class="no-contacts">
                        <i class="fas fa-info-circle"></i> No contacts found. Add your first contact!
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Category</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contacts as $contact): ?>
                                <tr>
                                    <td><?= htmlspecialchars($contact['name']) ?></td>
                                    <td><?= htmlspecialchars($contact['phone']) ?></td>
                                    <td><?= htmlspecialchars($contact['email']) ?></td>
                                    <td><?= htmlspecialchars($contact['category']) ?></td>
                                    <td class="actions">
                                        <a href="edit_contact.php?id=<?= $contact['id'] ?>" class="btn" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_contact.php?id=<?= $contact['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this contact?')" 
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

        <!-- Assignments Section -->
        <div class="section">
            <div class="header-actions">
                <h2><i class="fas fa-tasks"></i> My Assignments</h2>
                <a href="create_assignment.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Create New Assignment
                </a>
            </div>

            <div class="search-box">
                <input type="text" id="assignmentSearch" placeholder="Search assignments...">
            </div>

            <div class="table-container">
                <?php if (empty($assignments)): ?>
                    <div class="no-assignments">
                        <i class="fas fa-info-circle"></i> No assignments found. Create your first assignment!
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Student</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignments as $assignment): 
                                $status_class = '';
                                $status_text = '';
                                
                                if ($assignment['status'] === 'completed') {
                                    $status_class = 'status-completed';
                                    $status_text = 'Completed';
                                } elseif (strtotime($assignment['due_date']) < time()) {
                                    $status_class = 'status-overdue';
                                    $status_text = 'Overdue';
                                } else {
                                    $status_class = 'status-pending';
                                    $status_text = 'Pending';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div><?= htmlspecialchars($assignment['title']) ?></div>
                                        <div class="assignment-description">
                                            <?= htmlspecialchars($assignment['description']) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($assignment['student_name']) ?></td>
                                    <td>
                                        <div class="due-date">
                                            <i class="fas fa-calendar"></i>
                                            <?= date('M d, Y', strtotime($assignment['due_date'])) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $status_class ?>">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                    <td class="actions">
                                        <a href="view_assignment.php?id=<?= $assignment['id'] ?>" class="btn" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($assignment['status'] === 'completed'): ?>
                                            <a href="grade_assignment.php?id=<?= $assignment['id'] ?>" class="btn btn-success" title="Grade Assignment">
                                                <i class="fas fa-star"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="edit_assignment.php?id=<?= $assignment['id'] ?>" class="btn" title="Edit Assignment">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete_assignment.php?id=<?= $assignment['id'] ?>" 
                                           class="btn btn-danger" 
                                           onclick="return confirm('Are you sure you want to delete this assignment?')" 
                                           title="Delete Assignment">
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

        <!-- Recent Activity Section -->
        <div class="section">
            <h2><i class="fas fa-history"></i> Recent Activity</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['action']) ?></td>
                                <td><?= htmlspecialchars($log['timestamp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Add search functionality for contacts
        document.getElementById('contactSearch').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.querySelector('.table-container table');
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        });

        // Add search functionality for assignments
        document.getElementById('assignmentSearch').addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const table = document.querySelectorAll('.table-container table')[1];
            const rows = table.getElementsByTagName('tr');

            for (let i = 1; i < rows.length; i++) {
                const row = rows[i];
                const cells = row.getElementsByTagName('td');
                let found = false;

                for (let j = 0; j < cells.length; j++) {
                    const cell = cells[j];
                    if (cell.textContent.toLowerCase().indexOf(searchText) > -1) {
                        found = true;
                        break;
                    }
                }

                row.style.display = found ? '' : 'none';
            }
        });
    </script>
</body>
</html>