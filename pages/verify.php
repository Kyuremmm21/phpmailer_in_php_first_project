<?php
session_start();
require_once __DIR__ . '/../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify'])) {
    $verification_code = $_POST['verification_code'] ?? '';
    
    if (empty($verification_code)) {
        $error = "Please enter the verification code.";
    } else {
        // Check if verification code exists and is valid
        $query = "SELECT * FROM users WHERE verification_code = ? AND is_verified = 0";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(1, $verification_code, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Update user as verified
            $update_query = "UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindValue(1, $user['id'], PDO::PARAM_INT);
            
            if ($update_stmt->execute()) {
                header('Location: index.php?verification=success');
                exit();
            } else {
                $error = "Error updating verification status.";
            }
        } else {
            $error = "Invalid verification code.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="login-form">
            <h1><i class="fas fa-envelope"></i> Verify Email</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="verify.php" method="POST">
                <div class="form-group">
                    <label for="verification_code">Verification Code</label>
                    <div class="input-icon">
                        <i class="fas fa-key"></i>
                        <input type="text" id="verification_code" name="verification_code" 
                               placeholder="Enter verification code" required>
                    </div>
                </div>

                <button type="submit" name="verify" class="btn">
                    <i class="fas fa-check-circle"></i> Verify Email
                </button>
            </form>

            <p class="login-link">
                <a href="index.php">Back to Login</a>
            </p>
        </div>
    </div>
</body>
</html>