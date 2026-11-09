<?php
session_start();
require_once __DIR__ . '/../config/database.php'; // Include your database connection

// Check for verification success message
if (isset($_GET['verification']) && $_GET['verification'] === 'success') {
    $success = "Email verification successful! You can now login.";
}

// Check for error message
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

// Only process login if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Fetch user from the database
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(1, $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$user['id']]);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin_dashboard.php');
            } elseif ($user['role'] === 'teacher') {
                header('Location: teacher_dashboard.php');
            } else {
                header('Location: user_dashboard.php');
            }
            exit();
        } else {
            $error = "Please verify your email first.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager - Login</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
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
        <div class="login-form">
            <h1><i class="fas fa-user-circle"></i> Login</h1>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="index.php" method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-icon">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" required 
                               placeholder="Enter your email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" required 
                               placeholder="Enter your password">
                    </div>
                </div>

                <button type="submit" class="btn">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <p class="register-link">
                Don't have an account? <a href="register.php">Register here</a>
            </p>
        </div>
    </div>

    <script>
        // Add loading state to button on form submit
        document.querySelector('form').addEventListener('submit', function() {
            this.querySelector('button').classList.add('loading');
        });
    </script>
</body>
</html>