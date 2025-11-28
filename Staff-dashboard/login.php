<?php
session_start();
require './db.php';

$error = '';
$success = '';
$email = ''; // Initialize to keep email in form on error

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Trim input and use null coalescing operator for safety
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // Only allow staff or admin to log in through this form
        $stmt = $conn->prepare("SELECT id, name, password, role FROM users WHERE email=? AND (role = 'staff' OR role = 'admin') LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation attacks
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['role'] = $user['role'];

                // Redirect based on role
                if ($user['role'] === 'admin') {
                    // Redirect admin to their own dashboard
                    header("Location: ../Admin-dashboard/dashboard.php");
                } else {
                    // Staff and other roles go to the dashboard in the current directory
                    header("Location: dashboard.php");
                }
                exit;
            }
        }
        // Generic error message to prevent user enumeration
        $error = "Invalid email or password. Please try again.";
    }
} else {
    if (isset($_GET['status']) && $_GET['status'] === 'reset_success') {
        $success = "Your password has been reset successfully. You can now log in.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Staff Login</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #0d6efd; /* A standard, professional blue */
      --primary-hover-color: #0b5ed7;
      --body-bg: #f8f9fa;
      --form-bg: #ffffff;
      --input-border-color: #ced4da;
      --text-color: #212529;
      --text-muted: #6c757d;
      --error-color: #842029;
      --error-bg: #f8d7da;
      --error-border-color: #f5c2c7;
      --success-color: #0f5132;
      --success-bg: #d1e7dd;
      --success-border-color: #badbcc;
      --border-radius-lg: 1rem; /* 16px */
      --border-radius-md: 0.5rem; /* 8px */
      --font-family: 'Inter', sans-serif;
      --shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    }

    /* --- General Styles --- */
    body {
      font-family: var(--font-family);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      margin: 0;
      background-color: var(--body-bg);
      padding: 20px;
      box-sizing: border-box;
      color: var(--text-color);
    }

    /* --- Container & Form --- */
    .container {
      display: flex;
      flex-direction: row;
      background: var(--form-bg);
      border-radius: var(--border-radius-lg);
      box-shadow: var(--shadow);
      overflow: hidden;
      width: 900px;
      max-width: 100%;
      /* Removed transition for a more static, professional feel */
    }
    .image {
      flex: 1;
      display: none;
      background: #343a40; /* Dark background for the image side */
    }
    .image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      opacity: 0.7; /* Soften the image */
    }
    .form-box {
      flex: 1;
      padding: 40px 50px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 20px; /* Increased gap for more breathing room */
      width: 100%;
    }

    /* --- Text & Links --- */
    h2 {
      margin: 0 0 10px;
      color: var(--text-color);
      font-weight: 600;
      font-size: 24px;
      text-align: center;
    }
    .form-subtitle {
      font-size: 16px;
      color: var(--text-muted);
      text-align: center;
      margin: 0 0 30px;
    }

    a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }
    a:hover {
      color: var(--primary-hover-color);
      text-decoration: underline;
    }

    /* --- Interactive Elements --- */
    .input-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .input-group label {
        font-weight: 500;
        font-size: 14px;
    }

    input {
      padding: 12px 15px;
      border: 1px solid var(--input-border-color);
      border-radius: var(--border-radius-md);
      width: 100%;
      font-size: 16px;
      transition: border-color 0.2s ease, box-shadow 0.2s ease;
      box-sizing: border-box; /* Ensure padding doesn't affect width */
    }
    input:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);
      outline: none;
    }

    /* The main submit button */
    button[type="submit"] {
      padding: 12px;
      background-color: var(--primary-color);
      border: none;
      color: var(--form-bg);
      border-radius: var(--border-radius-md);
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: background-color 0.2s ease;
      width: 100%;
    }
    button[type="submit"]:hover {
      background-color: var(--primary-hover-color);
    }

    /* --- Utility & Feedback --- */
    .error {
      color: var(--error-color);
      background-color: var(--error-bg);
      border: 1px solid var(--error-border-color);
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: var(--border-radius-md);
      font-size: 14px;
      text-align: center;
    }
    .success {
      color: var(--success-color);
      background-color: var(--success-bg);
      border: 1px solid var(--success-border-color);
      padding: 1rem;
      margin-bottom: 1rem;
      border-radius: var(--border-radius-md);
      font-size: 14px;
      text-align: center;
    }
    .password-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
    }
    .password-wrapper input {
      padding-right: 50px; /* More space for the button */
    }
    .toggle-password {
      position: absolute;
      right: 1px; /* Position inside the input border */
      top: 1px;
      bottom: 1px;
      width: 45px;
      cursor: pointer;
      color: var(--text-muted);
      background: transparent;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 0 var(--border-radius-md) var(--border-radius-md) 0;
    }
    .toggle-password:hover {
        background-color: #e9ecef;
    }
    .toggle-password:focus-visible {
      outline: 2px solid var(--primary-color);
      outline-offset: -2px;
      border-radius: var(--border-radius-md);
    }
    .toggle-password svg {
      width: 20px;
      height: 20px;
    }
    .links-container {
      text-align: center;
      margin-top: 20px;
      font-size: 14px;
    }
    .links-container p {
      margin-top: 10px;
      margin-bottom: 0;
    }

    /* --- Responsive Design --- */
    @media (min-width: 768px) { .image { display: block; } }
    @media (max-width: 767px) { .form-box { padding: 30px; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="image"><img src="logo.png" alt="Login"></div>
    <div class="form-box">
      <h2>Staff Portal</h2>
      <p class="form-subtitle">Welcome back! Please log in to your account.</p>
      <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
      <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
      <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
        <div class="input-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="input-group">
          <label for="password">Password</label>
          <div class="password-wrapper">
              <input type="password" name="password" id="password" placeholder="Enter your password" required>
              <button type="button" class="toggle-password" aria-label="Show password">
                  <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/>
                      <path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/>
                  </svg>
                  <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="display: none;">
                      <path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/>
                      <path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/>
                  </svg>
              </button>
          </div>
        </div>
        <button type="submit">Log In</button>
      </form>
      <div class="links-container">
        <a href="forgot-password.php">Forgot Password?</a>
      </div>
    </div>
  </div>
  <script>
    document.querySelectorAll('.toggle-password').forEach(toggle => {
      toggle.addEventListener('click', () => {
        const passwordInput = toggle.closest('.password-wrapper').querySelector('input');
        const isPassword = passwordInput.type === 'password';
        
        passwordInput.type = isPassword ? 'text' : 'password';

        const eyeIcon = toggle.querySelector('.icon-eye');
        const eyeSlashIcon = toggle.querySelector('.icon-eye-slash');

        if (eyeIcon && eyeSlashIcon) {
            eyeIcon.style.display = isPassword ? 'none' : 'block';
            eyeSlashIcon.style.display = isPassword ? 'block' : 'none';
        }
        
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
      });
    });
  </script>
</body>
</html>
