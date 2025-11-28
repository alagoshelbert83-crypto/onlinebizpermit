<?php
session_start();
require_once 'db.php';

// If user is already logged in, redirect them
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'user') {
        header("Location: applicant_dashboard.php");
    } else {
        header("Location: login.php");
    }
    exit;
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $terms = isset($_POST['terms']);

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone)) {
        $error_message = "Please enter a valid phone number.";
    } elseif (!$terms) {
        $error_message = "Please accept the terms and conditions.";
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $error_message = "An account with this email already exists.";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user with pending approval (is_approved = 0)
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, phone, is_approved, created_at) VALUES (?, ?, ?, 'user', ?, 0, CURRENT_TIMESTAMP)");
            $result = $stmt->execute([$name, $email, $hashed_password, $phone]);

            if ($result) {
                // Registration successful - user is now pending approval
                header("Location: /login.php?status=pending");
                exit;
            } else {
                $error_message = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OnlineBizPermit</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="auth_style.css">
    <!-- Firebase SDK -->
    <script type="module">
        import { initializeApp } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-app.js';
        import { getAuth } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-auth.js';
        import { getFirestore } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-firestore.js';
        import { getStorage } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-storage.js';
        import { getAnalytics } from 'https://www.gstatic.com/firebasejs/9.22.0/firebase-analytics.js';

        // Firebase Configuration
        const firebaseConfig = {
            apiKey: "AIzaSyDPZY7B1BKzNrJRTulWFa0P0t28qlMDzig",
            authDomain: "onlinebizpermit.firebaseapp.com",
            projectId: "onlinebizpermit",
            storageBucket: "onlinebizpermit.firebasestorage.app",
            messagingSenderId: "37215767726",
            appId: "1:37215767726:web:44e68cd75b2628b438b13f",
            measurementId: "G-7RJHQKV7SC"
        };

        // Initialize Firebase
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);
        const db = getFirestore(app);
        const storage = getStorage(app);
        const analytics = getAnalytics(app);

        // Make services available globally
        window.firebaseAuth = auth;
        window.firebaseDb = db;
        window.firebaseStorage = storage;
    </script>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <i class="fas fa-building"></i>
                    <h1>OnlineBizPermit</h1>
                </div>
                <h2>Create Account</h2>
                <p>Join thousands of businesses using our platform</p>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <form action="/signup.php" method="POST" class="auth-form" id="registerForm">
                <div class="form-group">
                    <label for="name">
                        <i class="fas fa-user"></i>
                        Full Name *
                    </label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($name ?? '') ?>"
                        required 
                        autocomplete="name"
                        placeholder="Enter your full name"
                        minlength="2"
                        maxlength="100"
                    >
                </div>

                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i>
                        Email Address *
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
                    <label for="phone">
                        <i class="fas fa-phone"></i>
                        Phone Number
                    </label>
                    <input 
                        type="tel" 
                        id="phone" 
                        name="phone" 
                        value="<?= htmlspecialchars($phone ?? '') ?>"
                        autocomplete="tel"
                        placeholder="Enter your phone number (optional)"
                    >
                </div>

                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i>
                        Password *
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            required 
                            autocomplete="new-password"
                            placeholder="Create a strong password"
                            minlength="8"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="password-icon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <span class="strength-text" id="strengthText">Password strength</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i>
                        Confirm Password *
                    </label>
                    <div class="password-input">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            required 
                            autocomplete="new-password"
                            placeholder="Confirm your password"
                        >
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirm-password-icon"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>

                <div class="form-group">
                    <label class="terms-checkbox">
                        <input type="checkbox" name="terms" id="terms" required>
                        <span class="checkmark"></span>
                        I agree to the <a href="#" onclick="showTerms()">Terms and Conditions</a> and <a href="#" onclick="showPrivacy()">Privacy Policy</a> *
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-user-plus"></i>
                    Create Account
                </button>
            </form>

            <div class="auth-footer">
                <p>Already have an account? <a href="/login.php">Sign in here</a></p>
            </div>
        </div>

        <div class="auth-info">
            <div class="info-content">
                <h3>Why Choose OnlineBizPermit?</h3>
                <p>Join thousands of successful businesses that have streamlined their permit process with us.</p>
                
                <div class="features">
                    <div class="feature">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Quick Processing</h4>
                            <p>Get your business permits processed faster with our digital platform.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-shield-alt"></i>
                        <div>
                            <h4>Secure & Safe</h4>
                            <p>Your data is protected with enterprise-grade security measures.</p>
                        </div>
                    </div>
                    
                    <div class="feature">
                        <i class="fas fa-mobile-alt"></i>
                        <div>
                            <h4>Mobile Friendly</h4>
                            <p>Access your applications anywhere, anytime on any device.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms and Privacy Modals -->
    <div id="termsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Terms and Conditions</h3>
                <button class="modal-close" onclick="closeModal('termsModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>1. Acceptance of Terms</h4>
                <p>By using OnlineBizPermit, you agree to be bound by these terms and conditions.</p>
                
                <h4>2. Use of Service</h4>
                <p>You may use our service to apply for business permits and manage your applications.</p>
                
                <h4>3. User Responsibilities</h4>
                <p>You are responsible for providing accurate information and maintaining the security of your account.</p>
                
                <h4>4. Privacy</h4>
                <p>We respect your privacy and handle your data according to our privacy policy.</p>
                
                <h4>5. Limitation of Liability</h4>
                <p>Our service is provided "as is" and we are not liable for any damages arising from its use.</p>
            </div>
        </div>
    </div>

    <div id="privacyModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Privacy Policy</h3>
                <button class="modal-close" onclick="closeModal('privacyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <h4>Information We Collect</h4>
                <p>We collect information you provide directly to us, such as when you create an account or submit an application.</p>
                
                <h4>How We Use Information</h4>
                <p>We use your information to provide our services, process applications, and communicate with you.</p>
                
                <h4>Information Sharing</h4>
                <p>We do not sell, trade, or otherwise transfer your personal information to third parties without your consent.</p>
                
                <h4>Data Security</h4>
                <p>We implement appropriate security measures to protect your personal information.</p>
                
                <h4>Your Rights</h4>
                <p>You have the right to access, update, or delete your personal information at any time.</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const iconId = fieldId === 'password' ? 'password-icon' : 'confirm-password-icon';
            const passwordIcon = document.getElementById(iconId);
            
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

        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let strengthLabel = '';
            let strengthColor = '';
            
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;
            
            switch (strength) {
                case 0:
                case 1:
                    strengthLabel = 'Very Weak';
                    strengthColor = '#e74c3c';
                    break;
                case 2:
                    strengthLabel = 'Weak';
                    strengthColor = '#f39c12';
                    break;
                case 3:
                    strengthLabel = 'Fair';
                    strengthColor = '#f1c40f';
                    break;
                case 4:
                    strengthLabel = 'Good';
                    strengthColor = '#2ecc71';
                    break;
                case 5:
                    strengthLabel = 'Strong';
                    strengthColor = '#27ae60';
                    break;
            }
            
            strengthFill.style.width = (strength * 20) + '%';
            strengthFill.style.backgroundColor = strengthColor;
            strengthText.textContent = strengthLabel;
            strengthText.style.color = strengthColor;
        }

        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const matchDiv = document.getElementById('passwordMatch');
            
            if (confirmPassword === '') {
                matchDiv.innerHTML = '';
                return;
            }
            
            if (password === confirmPassword) {
                matchDiv.innerHTML = '<i class="fas fa-check-circle" style="color: #2ecc71;"></i> Passwords match';
            } else {
                matchDiv.innerHTML = '<i class="fas fa-times-circle" style="color: #e74c3c;"></i> Passwords do not match';
            }
        }

        function showTerms() {
            document.getElementById('termsModal').style.display = 'flex';
        }

        function showPrivacy() {
            document.getElementById('privacyModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', checkPasswordStrength);
        document.getElementById('confirm_password').addEventListener('input', checkPasswordMatch);

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match.');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                alert('Please accept the terms and conditions.');
                return;
            }
        });

        // Auto-hide error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.message');
            messages.forEach(message => {
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 300);
            });
        }, 5000);

        // Close modals when clicking outside
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    </script>

    <style>
        /* Modal Styles */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .modal-header h3 {
            margin: 0;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            padding: 20px;
        }

        .modal-body h4 {
            color: #333;
            margin: 20px 0 10px 0;
        }

        .modal-body p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 8px;
        }

        .strength-bar {
            width: 100%;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }

        .strength-fill {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .strength-text {
            font-size: 0.85rem;
            font-weight: 500;
        }

        .password-match {
            margin-top: 8px;
            font-size: 0.85rem;
            font-weight: 500;
        }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            cursor: pointer;
            font-size: 0.95rem;
            color: #666;
            line-height: 1.4;
        }

        .terms-checkbox input[type="checkbox"] {
            display: none;
        }

        .terms-checkbox .checkmark {
            width: 20px;
            height: 20px;
            border: 2px solid #ddd;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .terms-checkbox input[type="checkbox"]:checked + .checkmark {
            background: var(--primary);
            border-color: var(--primary);
        }

        .terms-checkbox input[type="checkbox"]:checked + .checkmark::after {
            content: '✓';
            color: white;
            font-size: 12px;
            font-weight: bold;
        }

        .terms-checkbox a {
            color: var(--primary);
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
        }
    </style>
</body>
</html>
