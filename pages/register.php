<?php
session_start();
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Manager - Register</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="register-form">
            <h1><i class="fas fa-user-plus"></i> Register</h1>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form action="action.php" method="POST">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="name" name="name" required 
                               placeholder="Enter your full name">
                    </div>
                </div>

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
                               placeholder="Enter your password"
                               pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" 
                               title="Must contain at least one number and one uppercase and lowercase letter, and at least 8 or more characters">
                    </div>
                    <div class="password-strength">
                        <div class="password-strength-bar"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                               placeholder="Confirm your password">
                    </div>
                </div>

                <button type="submit" name="register" class="btn">
                    <i class="fas fa-user-plus"></i> Register
                </button>
            </form>

            <p class="login-link">
                Already have an account? <a href="index.php">Login here</a>
            </p>
        </div>
    </div>

    <script>
        // Password validation
        const password = document.getElementById('password');
        const confirm_password = document.getElementById('confirm_password');
        const strength_bar = document.querySelector('.password-strength-bar');

        function validatePassword() {
            if (password.value !== confirm_password.value) {
                confirm_password.setCustomValidity("Passwords don't match");
            } else {
                confirm_password.setCustomValidity('');
            }
        }

        function checkPasswordStrength() {
            const value = password.value;
            let strength = 0;
            
            if (value.length >= 8) strength++;
            if (value.match(/[a-z]/) && value.match(/[A-Z]/)) strength++;
            if (value.match(/\d/)) strength++;
            if (value.match(/[^a-zA-Z\d]/)) strength++;

            strength_bar.className = 'password-strength-bar';
            if (strength <= 2) {
                strength_bar.classList.add('strength-weak');
            } else if (strength === 3) {
                strength_bar.classList.add('strength-medium');
            } else {
                strength_bar.classList.add('strength-strong');
            }
        }

        password.addEventListener('input', checkPasswordStrength);
        password.addEventListener('change', validatePassword);
        confirm_password.addEventListener('keyup', validatePassword);

        // Add loading state to button on form submit
        document.querySelector('form').addEventListener('submit', function() {
            this.querySelector('button').classList.add('loading');
        });
    </script>
</body>
</html>