<?php
session_start();
require_once 'db.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'user') {
        header("home.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error_message = "Please enter both email and password.";
    } else {
        try {
            $stmt = $conn->prepare("SELECT id, name, password, role, is_approved FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password'])) {
                    // Check if the user is an applicant (role = 'user')
                    if ($user['role'] === 'user') {
                        // Check if user is approved
                        $is_approved = (int)$user['is_approved'];

                        if ($is_approved === 0) {
                            $error_message = "Your account is pending admin approval. Please wait for approval before logging in.";
                        } elseif ($is_approved === 1) {
                            // Regenerate session ID to prevent session fixation attacks
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['name'];
                            $_SESSION['role'] = $user['role'];

                            header("Location: home.php");
                            exit;
                        } else {
                            $error_message = "Your account has been rejected. Please contact support for more information.";
                        }
                    } else {
                        $error_message = "This login is for applicants only. Please use the appropriate login portal.";
                    }
                } else {
                    $error_message = "Invalid email or password.";
                }
            } else {
                $error_message = "Invalid email or password.";
            }
        } catch(PDOException $e) {
            $error_message = "Database error occurred. Please try again.";
            error_log("Login database error: " . $e->getMessage());
        }
    }
} else {
    // Check for success messages from registration or password reset
    if (isset($_GET['status'])) {
        switch ($_GET['status']) {
            case 'registered':
                $success_message = "Registration successful! Please log in with your credentials.";
                break;
            case 'pending':
                $success_message = "Registration successful! Your account is pending admin approval. You will be notified once approved.";
                break;
            case 'reset_success':
                $success_message = "Password reset successful! Please log in with your new password.";
                break;
            case 'logout':
                $success_message = "You have been logged out successfully.";
                break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Login - OnlineBizPermit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth_style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                    <h1>OnlineBizPermit</h1>
                </div>
                <h2>Applicant Portal</h2>
                <p>Sign in to manage your business permit applications</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        value="<?= htmlspecialchars($email ?? '') ?>"
                        required 
                        autocomplete="email"
                        placeholder="Enter your email address"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="current-password"
                            placeholder="Enter your password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword()">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                </div>

                <div class="form-options">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" value="1">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In
                </button>
            </form>

            <div class="auth-footer">
                <p>Don't have an account? <a href="signup.php">Sign Up here</a></p>
                <div class="divider">
                
                </div>
                
            </div>
        </div>

        <div class="auth-info">
            <div class="info-content">
                <h3>Welcome to OnlineBizPermit</h3>
                <p>Your one-stop solution for business permit applications and management.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-file-alt"></i>
                        <div>
                            <h4>Easy Application</h4>
                            <p>Submit your business permit application online with our streamlined process.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-chart-line"></i>
                        <div>
                            <h4>Track Progress</h4>
                            <p>Monitor your application status in real-time and receive instant updates.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-headset"></i>
                        <div>
                            <h4>24/7 Support</h4>
                            <p>Get help anytime with our intelligent FAQ bot and support team.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);

        // Form validation
        document.querySelector('.auth-form').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all fields.');
                return;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return;
            }
        });
    </script>
</body>
</html>
