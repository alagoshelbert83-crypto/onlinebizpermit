<?php
session_start();
require './db.php';

$error = '';
$token = $_GET['token'] ?? '';
$showForm = false;
$resetData = null;

if (empty($token)) {
    $error = "No reset token provided. The link may be broken.";
} else {
    // Validate the token
    $stmt = $conn->prepare("SELECT email, expires FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($reset = $result->fetch_assoc()) {
        if ($reset['expires'] > time()) {
            $showForm = true;
            $resetData = $reset;
        } else {
            $error = "This password reset link has expired. Please request a new one.";
            // Clean up expired token
            $delStmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $delStmt->bind_param("s", $token);
            $delStmt->execute();
        }
    } else {
        $error = "This password reset link is invalid. Please request a new one.";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Re-validate token from POST to ensure it's still valid
    $stmt = $conn->prepare("SELECT email, expires FROM password_resets WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $resetData = $result->fetch_assoc();

    if (!$resetData || $resetData['expires'] <= time()) {
        $error = "Invalid or expired token. Please try the reset process again.";
        $showForm = false;
    } elseif (empty($password) || empty($password_confirm)) {
        $error = "Please fill in both password fields.";
        $showForm = true;
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
        $showForm = true;
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
        $showForm = true;
    } else {
        // All checks passed, update the password
        $email = $resetData['email'];
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $hashedPassword, $email);
        $updateStmt->execute();

        // Invalidate the token by deleting it
        $deleteStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();

        header("Location: login.php?status=reset_success");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* NOTE: For brevity, the full CSS from login.php is omitted. 
       You should copy all the styles from login.php into this file 
       to ensure a consistent appearance. */
    :root {--primary-color: #0d6efd;--primary-hover-color: #0b5ed7;--body-bg: #f8f9fa;--form-bg: #ffffff;--input-border-color: #ced4da;--text-color: #212529;--text-muted: #6c757d;--error-color: #842029;--error-bg: #f8d7da;--error-border-color: #f5c2c7;--border-radius-lg: 1rem;--border-radius-md: 0.5rem;--font-family: 'Inter', sans-serif;--shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);}
    body {font-family: var(--font-family);display: flex;justify-content: center;align-items: center;min-height: 100vh;margin: 0;background-color: var(--body-bg);padding: 20px;box-sizing: border-box;color: var(--text-color);}
    .container {display: flex;flex-direction: row;background: var(--form-bg);border-radius: var(--border-radius-lg);box-shadow: var(--shadow);overflow: hidden;width: 900px;max-width: 100%;max-height: calc(100vh - 40px);}
    .image {flex: 1;display: none;background: #343a40;}
    .image img {width: 100%;height: 100%;object-fit: cover;opacity: 0.7;}
    .form-box {flex: 1;padding: 40px 50px;display: flex;flex-direction: column;justify-content: center;overflow-y: auto;scrollbar-width: thin;scrollbar-color: #ccc var(--body-bg);}
    .form-box::-webkit-scrollbar {width: 8px;}
    .form-box::-webkit-scrollbar-track {background: transparent;}
    .form-box::-webkit-scrollbar-thumb {background-color: #ccc;border-radius: 10px;border: 2px solid var(--form-bg);}
    form {display: flex;flex-direction: column;gap: 20px;width: 100%;}
    h2 {margin: 0 0 10px;color: var(--text-color);font-weight: 600;font-size: 24px;text-align: center;}
    .form-subtitle {font-size: 16px;color: var(--text-muted);text-align: center;margin: 0 0 30px;}
    a {color: var(--primary-color);text-decoration: none;font-weight: 500;transition: color 0.2s ease;}
    a:hover {color: var(--primary-hover-color);text-decoration: underline;}
    .input-group {display: flex;flex-direction: column;gap: 5px;}
    .input-group label {font-weight: 500;font-size: 14px;}
    input {padding: 12px 15px;border: 1px solid var(--input-border-color);border-radius: var(--border-radius-md);width: 100%;font-size: 16px;transition: border-color 0.2s ease, box-shadow 0.2s ease;box-sizing: border-box;}
    input:focus {border-color: var(--primary-color);box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);outline: none;}
    button {padding: 12px;background-color: var(--primary-color);border: none;color: var(--form-bg);border-radius: var(--border-radius-md);font-weight: 600;font-size: 16px;cursor: pointer;transition: background-color 0.2s ease;width: 100%;}
    button:hover {background-color: var(--primary-hover-color);}
    .error {color: var(--error-color);background-color: var(--error-bg);border: 1px solid var(--error-border-color);padding: 1rem;margin-bottom: 1rem;border-radius: var(--border-radius-md);font-size: 14px;text-align: center;}
    .password-wrapper {position: relative;display: flex;align-items: center;width: 100%;}
    .password-wrapper input {padding-right: 50px;}
    .toggle-password {position: absolute;right: 1px;top: 1px;bottom: 1px;width: 45px;cursor: pointer;color: var(--text-muted);background: transparent;border: none;display: flex;align-items: center;justify-content: center;border-radius: 0 var(--border-radius-md) var(--border-radius-md) 0;}
    .toggle-password:hover {background-color: #e9ecef;}
    .toggle-password:focus-visible {outline: 2px solid var(--primary-color);outline-offset: -2px;border-radius: var(--border-radius-md);}
    .toggle-password svg {width: 20px;height: 20px;}
    .links-container {text-align: center;margin-top: 20px;font-size: 14px;}
    @media (min-width: 768px) { .image { display: block; } }
    @media (max-width: 767px) { .form-box { padding: 30px; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="image"><img src="staff.png" alt="Reset Password"></div>
    <div class="form-box">
      <h2>Set a New Password</h2>
      
      <?php if (!empty($error)): ?>
        <p class='error'><?php echo $error; ?></p>
        <div class="links-container">
          <a href="forgot-password.php">Request a new link</a>
        </div>
      <?php elseif ($showForm): ?>
        <p class="form-subtitle">Please enter and confirm your new password below.</p>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
          
          <div class="input-group">
            <label for="password">New Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" placeholder="Enter new password" required>
                <button type="button" class="toggle-password" aria-label="Show password">
                    <svg class="icon-eye" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M10.5 8a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0z"/><path d="M0 8s3-5.5 8-5.5S16 8 16 8s-3 5.5-8 5.5S0 8 0 8zm8 3.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"/></svg>
                    <svg class="icon-eye-slash" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" style="display: none;"><path d="m10.79 12.912-1.614-1.615a3.5 3.5 0 0 1-4.474-4.474l-2.06-2.06C.938 6.278 0 8 0 8s3 5.5 8 5.5a7.029 7.029 0 0 0 2.79-.588zM5.21 3.088A7.028 7.028 0 0 1 8 2.5c5 0 8 5.5 8 5.5s-.939 1.721-2.641 3.238l-2.062-2.062a3.5 3.5 0 0 0-4.474-4.474L5.21 3.089z"/><path d="M5.525 7.646a2.5 2.5 0 0 0 2.829 2.829l-2.83-2.829zm4.95.708-2.829-2.83a2.5 2.5 0 0 1 2.829 2.829zm3.171 6-12-12 .708-.708 12 12-.708.708z"/></svg>
                </button>
            </div>
          </div>

          <div class="input-group">
            <label for="password_confirm">Confirm New Password</label>
            <input type="password" name="password_confirm" id="password_confirm" placeholder="Confirm new password" required>
          </div>

          <button type="submit">Reset Password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
  <script>
    // Use the same robust script from login.php
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