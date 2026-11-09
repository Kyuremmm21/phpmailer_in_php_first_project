<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = $_GET['id'] ?? 0;

// Fetch assignment details
$stmt = $conn->prepare("SELECT * FROM assignments WHERE id = ? AND user_id = ? AND is_deleted = 0");
$stmt->execute([$assignment_id, $user_id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    header("Location: user_dashboard.php");
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['submission_file']) && $_FILES['submission_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['submission_file'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_size = $file['size'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Allowed file extensions
        $allowed = array('pdf', 'doc', 'docx', 'txt');
        
        if (in_array($file_ext, $allowed)) {
            if ($file_size <= 5242880) { // 5MB max
                $new_file_name = uniqid() . '.' . $file_ext;
                $upload_directory = __DIR__ . '/../uploads/assignments';
                $upload_path = $upload_directory . DIRECTORY_SEPARATOR . $new_file_name;
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_directory)) {
                    mkdir($upload_directory, 0777, true);
                }
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Update assignment in database
                    $stmt = $conn->prepare("UPDATE assignments SET 
                        submission_file = ?, 
                        submission_date = CURRENT_TIMESTAMP,
                        status = 'completed'
                        WHERE id = ?");
                    
                    if ($stmt->execute([$new_file_name, $assignment_id])) {
                        // Log the submission
                        $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
                        $stmt->execute([$user_id, "Submitted assignment: {$assignment['title']}"]);
                        
                        header("Location: user_dashboard.php?success=Assignment submitted successfully");
                        exit;
                    } else {
                        $error = "Error updating assignment in database.";
                    }
                } else {
                    $error = "Error uploading file.";
                }
            } else {
                $error = "File size must be less than 5MB.";
            }
        } else {
            $error = "Only PDF, DOC, DOCX, and TXT files are allowed.";
        }
    } else {
        $error = "Please select a file to upload.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Assignment</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1><i class="fas fa-upload"></i> Submit Assignment</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <div class="assignment-details">
                <h2><?= htmlspecialchars($assignment['title']) ?></h2>
                <p class="description"><?= htmlspecialchars($assignment['description']) ?></p>
                <p class="due-date">
                    <i class="fas fa-calendar"></i> Due Date: 
                    <?= date('F d, Y h:i A', strtotime($assignment['due_date'])) ?>
                </p>
            </div>

            <form action="submit_assignment.php?id=<?= $assignment_id ?>" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="submission_file">Upload File</label>
                    <div class="input-icon">
                        <i class="fas fa-file"></i>
                        <input type="file" id="submission_file" name="submission_file" required 
                               accept=".pdf,.doc,.docx,.txt">
                    </div>
                    <small>Allowed file types: PDF, DOC, DOCX, TXT (Max size: 5MB)</small>
                </div>

                <div class="form-buttons">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-upload"></i> Submit Assignment
                    </button>
                    <a href="user_dashboard.php" class="btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>

    <style>
        .assignment-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .assignment-details h2 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .description {
            color: #666;
            margin-bottom: 15px;
        }
        .due-date {
            color: #dc3545;
            font-weight: 500;
        }
        .due-date i {
            margin-right: 5px;
        }
        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .input-icon input[type="file"] {
            padding: 8px;
        }
        small {
            display: block;
            color: #666;
            margin-top: 5px;
        }
    </style>
</body>
</html> 