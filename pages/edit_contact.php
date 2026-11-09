<!-- filepath: edit_contact.php -->
<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$is_teacher = $_SESSION['role'] === 'teacher';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contact_id = $_POST['contact_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    // Validate inputs
    if (empty($name) || empty($email) || empty($phone)) {
        echo "All fields are required.";
        exit();
    }

    try {
        // Update the contact - check ownership based on role
        if ($is_teacher) {
            $query = "UPDATE contacts SET name = ?, email = ?, phone = ? WHERE id = ? AND teacher_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$name, $email, $phone, $contact_id, $user_id]);
        } else {
            $query = "UPDATE contacts SET name = ?, email = ?, phone = ? WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$name, $email, $phone, $contact_id, $user_id]);
        }

        // Log the action
        $log_query = "INSERT INTO logs (user_id, action) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->execute([$user_id, "Updated contact: $name"]);

        // Redirect based on role
        header('Location: ' . ($is_teacher ? 'teacher_dashboard.php' : 'user_dashboard.php') . '?success=1');
        exit();
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Fetch the contact to edit
$contact_id = $_GET['id'] ?? 0;

// Get contact based on role
if ($is_teacher) {
    $query = "SELECT * FROM contacts WHERE id = ? AND teacher_id = ? AND is_deleted = 0";
} else {
    $query = "SELECT * FROM contacts WHERE id = ? AND user_id = ? AND is_deleted = 0";
}

$stmt = $conn->prepare($query);
$stmt->execute([$contact_id, $user_id]);
$contact = $stmt->fetch();

if (!$contact) {
    header('Location: ' . ($is_teacher ? 'teacher_dashboard.php' : 'user_dashboard.php'));
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Edit Contact</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f9;
        }
        .container {
            width: 90%;
            max-width: 600px;
            margin: 40px auto;
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        .form-group input:focus {
            border-color: #007BFF;
            outline: none;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background-color: #007BFF;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            transition: background-color 0.3s;
            width: 100%;
            justify-content: center;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .btn i {
            font-size: 16px;
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
        .input-icon {
            position: relative;
        }
        .input-icon i {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        .input-icon input {
            padding-left: 35px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-edit"></i> Edit Contact</h1>
        <form action="edit_contact.php" method="POST">
            <input type="hidden" name="contact_id" value="<?= htmlspecialchars($contact['id']) ?>">
            
            <div class="form-group">
                <label for="name">Contact Name</label>
                <div class="input-icon">
                    <i class="fas fa-user"></i>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($contact['name']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($contact['phone']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-icon">
                    <i class="fas fa-envelope"></i>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($contact['email']) ?>" required>
                </div>
            </div>

            <button type="submit" class="btn">
                <i class="fas fa-save"></i> Update Contact
            </button>
        </form>
        <a href="<?= $is_teacher ? 'teacher_dashboard.php' : 'user_dashboard.php' ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</body>
</html>