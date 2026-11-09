<?php

require_once __DIR__ . '/../config/database.php';
require __DIR__ . '/../PHPMailer/src/Exception.php';
require __DIR__ . '/../PHPMailer/src/PHPMailer.php';
require __DIR__ . '/../PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

session_start();

// Handle user registration
if (isset($_POST['register'])) {
    $name = $_POST['name']; 
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $verification_code = rand(100000, 999999);

    // Check if the email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $existing_user = $stmt->fetch();

    if ($existing_user) {
        header("Location: register.php?error=Email already registered");
        exit;
    }

    // Insert the new user into the database
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, verification_code, is_verified) VALUES (?, ?, ?, ?, 0)");
    $stmt->execute([$name, $email, $password, $verification_code]);

    // Send verification email
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'gumalogk7@gmail.com'; // Your Gmail address
        $mail->Password = 'zgsnkvpkewghxbpd'; // Your Gmail App Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('gumalogk7@gmail.com', 'Online Contact Manager');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Email Verification Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2 style='color: #333;'>Welcome to Online Contact Manager!</h2>
                <p style='color: #666;'>Thank you for registering. Please use the following verification code to verify your email:</p>
                <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                    <h1 style='color: #4CAF50; font-size: 24px; margin: 0;'>{$verification_code}</h1>
                </div>
                <p style='color: #666;'>If you did not request this verification, please ignore this email.</p>
                <hr style='border: 1px solid #eee; margin: 20px 0;'>
                <p style='color: #999; font-size: 12px;'>This is an automated message, please do not reply.</p>
            </div>
        ";

        $mail->send();
        header("Location: verify.php?email=" . urlencode($email));
    } catch (Exception $e) {
        error_log("Email Error: " . $mail->ErrorInfo);
        header("Location: register.php?error=Verification email could not be sent");
    }
    exit;
}

// Handle email verification
if (isset($_POST['verify'])) {
    $verification_code = $_POST['verification_code'] ?? '';

    if (empty($verification_code)) {
        header("Location: verify.php?error=Please enter the verification code");
        exit;
    }

    // Check if verification code exists and is valid
    $stmt = $conn->prepare("SELECT * FROM users WHERE verification_code = ? AND is_verified = 0");
    $stmt->execute([$verification_code]);
    $user = $stmt->fetch();

    if ($user) {
        // Update user as verified
        $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_code = NULL WHERE id = ?");
        if ($update_stmt->execute([$user['id']])) {
            header("Location: index.php?verification=success");
        } else {
            header("Location: verify.php?error=Error updating verification status");
        }
    } else {
        header("Location: verify.php?error=Invalid verification code");
    }
    exit;
}

// Handle user login
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        if ($user['is_verified']) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            
            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?");
            $update_stmt->execute([$user['id']]);

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($user['role'] === 'teacher') {
                header("Location: teacher_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }
        } else {
            header("Location: index.php?error=Please verify your email first");
        }
    } else {
        header("Location: index.php?error=Invalid email or password");
    }
    exit;
}

// Handle adding a contact
if (isset($_POST['add_contact'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("INSERT INTO contacts (user_id, name, phone, email) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $name, $phone, $email]);
    header("Location: index.php");
    exit;
}

// Handle editing a contact
if (isset($_POST['edit_contact'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];

    $stmt = $conn->prepare("UPDATE contacts SET name = ?, phone = ?, email = ? WHERE id = ?");
    $stmt->execute([$name, $phone, $email, $id]);
    header("Location: index.php");
    exit;
}

// Handle deleting a contact
if (isset($_GET['delete_contact'])) {
    $id = $_GET['delete_contact'];

    $stmt = $conn->prepare("DELETE FROM contacts WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: index.php");
    exit;
}

// Handle updating the user profile
if (isset($_POST['update_profile'])) {
    $user_id = $_SESSION['user_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    if (!empty($password)) {
        $password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $email, $password, $user_id]);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
        $stmt->execute([$name, $email, $user_id]);
    }
    header("Location: profile.php");
    exit;
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle password reset request
if (isset($_POST['reset_password'])) {
    $email = $_POST['email'];
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $reset_code = rand(100000, 999999);
        $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Update user with reset code
        $update_stmt = $conn->prepare("UPDATE users SET reset_code = ?, reset_expiry = ? WHERE id = ?");
        $update_stmt->execute([$reset_code, $reset_expiry, $user['id']]);

        // Send reset email
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'gumalogk7@gmail.com';
            $mail->Password = 'zgsnkvpkewghxbpd';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('gumalogk7@gmail.com', 'Online Contact Manager');
            $mail->addAddress($email);

            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Code';
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color: #333;'>Password Reset Request</h2>
                    <p style='color: #666;'>You have requested to reset your password. Use the following code:</p>
                    <div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                        <h1 style='color: #4CAF50; font-size: 24px; margin: 0;'>{$reset_code}</h1>
                    </div>
                    <p style='color: #666;'>This code will expire in 1 hour.</p>
                    <p style='color: #666;'>If you did not request this reset, please ignore this email.</p>
                    <hr style='border: 1px solid #eee; margin: 20px 0;'>
                    <p style='color: #999; font-size: 12px;'>This is an automated message, please do not reply.</p>
                </div>
            ";

            $mail->send();
            header("Location: reset_password.php?email=" . urlencode($email) . "&sent=1");
        } catch (Exception $e) {
            error_log("Email Error: " . $mail->ErrorInfo);
            header("Location: reset_password.php?error=Reset email could not be sent");
        }
    } else {
        header("Location: reset_password.php?error=Email not found");
    }
    exit;
}
?>