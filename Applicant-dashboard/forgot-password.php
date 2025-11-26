<?php
session_start();
require './db.php';

$message = '';
$error = '';
$email = '';
$showForm = true;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        $showForm = false; // Hide form after submission
        // To prevent user enumeration, we will always show a success message.
        // The database and email operations only run if the user actually exists.
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? AND (role = 'staff' OR role = 'admin') LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // User exists, proceed with token generation.
            $token = bin2hex(random_bytes(32));
            $expires = time() + 1800; // Token expires in 30 minutes

            $conn->begin_transaction();
            try {
                // Delete any previous tokens for this email
                $delStmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
                $delStmt->bind_param("s", $email);
                $delStmt->execute();

                // Insert the new token
                $insStmt = $conn->prepare("INSERT INTO password_resets (email, token, expires) VALUES (?, ?, ?)");
                $insStmt->bind_param("ssi", $email, $token, $expires);
                $insStmt->execute();

                $conn->commit();

                // --- Email Sending Logic (Simulated) ---
                // In a real app, you would use a library like PHPMailer to send an email.
                // You would NOT display the link to the user directly.
                $resetLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $token;
                $message = "If an account with that email exists, a password reset link has been sent.<br><br><strong>For Demonstration Only:</strong> <a href='$resetLink'>Reset Password Link</a>";

            } catch (mysqli_sql_exception $exception) {
                $conn->rollback();
                // In production, log this error but show the generic message.
                $message = "An error occurred. Please try again later.";
            }
        } else {
            // User does not exist, but we show the same generic message for security.
            $message = "If an account with that email exists, a password reset link has been sent.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Forgot Password</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
  <style>
    /* NOTE: For brevity, the full CSS from login.php is omitted. 
       You should copy all the styles from login.php into this file 
       to ensure a consistent appearance. Make sure the .success style is included. */
    :root {--primary-color: #0d6efd;--primary-hover-color: #0b5ed7;--body-bg: #f8f9fa;--form-bg: #ffffff;--input-border-color: #ced4da;--text-color: #212529;--text-muted: #6c757d;--error-color: #842029;--error-bg: #f8d7da;--error-border-color: #f5c2c7;--success-color: #0f5132;--success-bg: #d1e7dd;--success-border-color: #badbcc;--border-radius-lg: 1rem;--border-radius-md: 0.5rem;--font-family: 'Inter', sans-serif;--shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);}
    body {font-family: var(--font-family);display: flex;justify-content: center;align-items: center;min-height: 100vh;margin: 0;background-color: var(--body-bg);padding: 20px;box-sizing: border-box;color: var(--text-color);}
    .container {display: flex;flex-direction: row;background: var(--form-bg);border-radius: var(--border-radius-lg);box-shadow: var(--shadow);overflow: hidden;width: 900px;max-width: 100%;}
    .image {flex: 1;display: none;background: #343a40;}
    .image img {width: 100%;height: 100%;object-fit: cover;opacity: 0.7;}
    .form-box {flex: 1;padding: 40px 50px;display: flex;flex-direction: column;justify-content: center;}
    form {display: flex;flex-direction: column;gap: 20px;width: 100%;}
    h2 {margin: 0 0 10px;color: var(--text-color);font-weight: 600;font-size: 24px;text-align: center;}
    .form-subtitle {font-size: 16px;color: var(--text-muted);text-align: center;margin: 0 0 30px;}
    a {color: var(--primary-color);text-decoration: none;font-weight: 500;transition: color 0.2s ease;}
    a:hover {color: var(--primary-hover-color);text-decoration: underline;}
    .input-group {display: flex;flex-direction: column;gap: 5px;}
    .input-group label {font-weight: 500;font-size: 14px;}
    input {padding: 12px 15px;border: 1px solid var(--input-border-color);border-radius: var(--border-radius-md);width: 100%;font-size: 16px;transition: border-color 0.2s ease, box-shadow 0.2s ease;box-sizing: border-box;}
    input:focus {border-color: var(--primary-color);box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.25);outline: none;}
    button[type="submit"] {padding: 12px;background-color: var(--primary-color);border: none;color: var(--form-bg);border-radius: var(--border-radius-md);font-weight: 600;font-size: 16px;cursor: pointer;transition: background-color 0.2s ease;width: 100%;}
    button[type="submit"]:hover {background-color: var(--primary-hover-color);}
    .error {color: var(--error-color);background-color: var(--error-bg);border: 1px solid var(--error-border-color);padding: 1rem;margin-bottom: 1rem;border-radius: var(--border-radius-md);font-size: 14px;text-align: center;}
    .success {color: var(--success-color);background-color: var(--success-bg);border: 1px solid var(--success-border-color);padding: 1rem;margin-bottom: 1rem;border-radius: var(--border-radius-md);font-size: 14px;text-align: center;}
    .success a {color: var(--success-color);font-weight: bold;}
    .links-container {text-align: center;margin-top: 20px;font-size: 14px;}
    @media (min-width: 768px) { .image { display: block; } }
    @media (max-width: 767px) { .form-box { padding: 30px; } }
  </style>
</head>
<body>
  <div class="container">
    <div class="image"><img src="staff.png" alt="Forgot Password"></div>
    <div class="form-box">
      <h2>Forgot Your Password?</h2>
      
      <?php if (!empty($message)): ?>
        <p class="form-subtitle">Please check your inbox.</p>
        <p class='success'><?php echo $message; ?></p>
      <?php endif; ?>

      <?php if ($showForm): ?>
        <p class="form-subtitle">No problem. Enter your email below and we'll send you a reset link.</p>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" novalidate>
          <div class="input-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" placeholder="you@example.com" required value="<?php echo htmlspecialchars($email); ?>">
          </div>
          <button type="submit">Send Reset Link</button>
        </form>
      <?php endif; ?>

      <div class="links-container">
        <a href="login.php">Back to Login</a>
      </div>
    </div>
  </div>
</body>
</html>