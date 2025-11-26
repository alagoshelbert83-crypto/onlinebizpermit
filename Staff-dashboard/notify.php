<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>


<?php
session_start();
require './db.php';
require_once __DIR__ . '/../config_mail.php';
require_once './email_functions.php';

// ✅ Only staff can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header("Location: login.php");
    exit;
}

$application_id = null;
$app_data = null;
$flash_message = '';

// --- Get Application ID and Data ---
if (isset($_GET['application_id'])) {
    $application_id = (int)$_GET['application_id'];
} elseif (isset($_POST['application_id'])) {
    $application_id = (int)$_POST['application_id'];
}

if ($application_id) {
    $stmt = $conn->prepare("SELECT a.user_id, a.business_name, u.name as applicant_name, u.email as applicant_email FROM applications a JOIN users u ON a.user_id = u.id WHERE a.id = ?");
    $stmt->bind_param("i", $application_id);
    $stmt->execute();
    $app_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$app_data) {
    $_SESSION['flash_message'] = ['type' => 'error', 'text' => 'Application not found.'];
    header("Location: applicants.php");
    exit;
}

// --- Handle Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_notification'])) {
    $subject = trim($_POST['subject']);
    $message_body = trim($_POST['message']);

    if (empty($subject) || empty($message_body)) {
        $flash_message = '<div class="message error">Subject and message cannot be empty.</div>';
    } else {
        $conn->begin_transaction();
        try {
            // 1. Create an in-app notification
            $link = "../Applicant-dashboard/view_my_application.php?id={$application_id}";
            $notification_message = "You have a new message from staff regarding your application for '" . htmlspecialchars($app_data['business_name']) . "'.";
            
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $notify_stmt->bind_param("iss", $app_data['user_id'], $notification_message, $link);
            $notify_stmt->execute();
            $notify_stmt->close();

            // 2. Send an email notification
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https" : "http";
            $host = $_SERVER['HTTP_HOST'];
            $absolute_link = "{$protocol}://{$host}/onlinebizpermit/Applicant-dashboard/view_my_application.php?id={$application_id}";

            $email_body_html = "
            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <div style='max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                    <h2 style='color: #4a69bd;'>" . htmlspecialchars($subject) . "</h2>
                    <p>Dear " . htmlspecialchars($app_data['applicant_name']) . ",</p>
                    <p>You have received a message from our team regarding your application for <strong>" . htmlspecialchars($app_data['business_name']) . "</strong>.</p>
                    <div style='background-color: #f8f9fa; border-left: 4px solid #4a69bd; padding: 15px; margin: 20px 0;'>
                        " . nl2br(htmlspecialchars($message_body)) . "
                    </div>
                    <p>You can view your application and respond if necessary by clicking the button below:</p>
                    <p style='text-align: center; margin: 30px 0;'>
                        <a href='" . htmlspecialchars($absolute_link) . "' style='background-color: #4a69bd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>View My Application</a>
                    </p>
                    <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 0.9em; color: #777;'>Thank you for using our service.<br><strong>The OnlineBizPermit Team</strong></p>
                </div>
            </div>";

            // Capture any debug output from PHPMailer
            ob_start();
            sendApplicationEmail($app_data['applicant_email'], $app_data['applicant_name'], $subject, $email_body_html);
            $debug_output = ob_get_clean(); // Capture and discard debug output
            
            $conn->commit();

            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Notification sent successfully to ' . htmlspecialchars($app_data['applicant_name']) . '.'];
            header("Location: applicants.php");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            error_log("Notification sending failed for application ID {$application_id}: " . $e->getMessage());
            $flash_message = '<div class="message error">Failed to send notification. Please try again. Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- Fetch unread notification count for sidebar ---
$unread_notifications_count = 0;
if (isset($_SESSION['user_id'])) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0");
    $count_stmt->bind_param("i", $_SESSION['user_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result()->fetch_assoc();
    $unread_notifications_count = $count_result['unread_count'] ?? 0;
    $count_stmt->close();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Send Notification - OnlineBizPermit</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <style>
    /* Re-using styles from applicants.php for consistency */
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Inter',sans-serif; }
    body { background-color: #f4f7fa; color: #343a40; }
    .wrapper { display: flex; min-height: 100vh; }
    .sidebar { width: 80px; background: #232a3b; padding: 25px 10px; display: flex; flex-direction: column; justify-content: space-between; color: #d0d2d6; flex-shrink: 0; transition: width 0.3s ease; overflow-x: hidden; }
    .sidebar:hover { width: 240px; }
    .sidebar h2 { margin-bottom: 35px; position: relative; height: 24px; display: flex; align-items: center; }
    .sidebar h2 span { font-size: 18px; font-weight: 700; letter-spacing: 1px; color: #fff; white-space: nowrap; opacity: 0; transition: opacity 0.2s ease 0.1s; margin-left: 52px; }
    .sidebar h2::before { content: '\f1ad'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 24px; color: #fff; position: absolute; left: 50%; transform: translateX(-50%); transition: left 0.3s ease; }
    .sidebar:hover h2 span { opacity: 1; }
    .sidebar:hover h2::before { left: 28px; }
    .btn-nav { display: flex; align-items: center; padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; text-decoration: none; background: transparent; color: #d0d2d6; font-weight: 600; transition: all 0.2s ease; }
    .btn-nav span { white-space: nowrap; opacity: 0; transition: opacity 0.2s ease; transition-delay: 0s; }
    .sidebar:hover .btn-nav span { opacity: 1; transition-delay: 0.1s; }
    .btn-nav i { min-width: 20px; margin-right: 12px; text-align: center; font-size: 1.1em; }
    .btn-nav:hover { background: #3c4b64; color: #fff; }
    .btn-nav.active { background: #4a69bd; color: #fff; }
    .btn-nav.logout { margin-top: 20px; color: #e74c3c; }
    .btn-nav.logout:hover { background: #e74c3c; color: #fff; }
    .main { flex: 1; padding: 40px; overflow-y: auto; }
    .main h1 { margin-bottom: 20px; color: #232a3b; font-weight: 700; }
    .main h1 small { font-size: 1rem; color: #6c757d; font-weight: 400; }
    .form-container { background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); max-width: 800px; margin: 0 auto; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #343a40; }
    .form-group input[type="text"], .form-group textarea { width: 100%; padding: 12px; border: 1px solid #ced4da; border-radius: 8px; font-size: 14px; transition: border-color 0.2s; }
    .form-group input[type="text"]:focus, .form-group textarea:focus { outline: none; border-color: #4a69bd; box-shadow: 0 0 0 2px rgba(74, 105, 189, 0.2); }
    .form-group textarea { min-height: 150px; resize: vertical; }
    .form-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 30px; }
    .btn { padding: 12px 25px; border: none; border-radius: 8px; cursor: pointer; background: #4a69bd; color: #fff; transition: all 0.3s ease; text-decoration: none; display: inline-block; font-weight: 600; }
    .btn:hover { background: #3b5699; }
    .btn-secondary { background: #6c757d; }
    .btn-secondary:hover { background: #5a6268; }
    .message { padding: 15px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; }
    .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
    .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    .notification-count { background-color: #e74c3c; color: white; border-radius: 8px; padding: 2px 6px; font-size: 12px; margin-left: 8px; font-weight: 700; display: inline-block; vertical-align: middle; }
  </style>
</head>
<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar">
      <div>
        <h2><span>ONLINEBIZ PERMIT</span></h2>
        <a href="dashboard.php" class="btn-nav"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
        <a href="pending_applications.php" class="btn-nav"><i class="fas fa-hourglass-half"></i><span>Pending</span></a>
        <a href="applicants.php" class="btn-nav active"><i class="fas fa-users"></i><span>Applicants</span></a>
        <a href="notifications.php" class="btn-nav"><i class="fas fa-bell"></i><span>Notifications <?php if ($unread_notifications_count > 0): ?><span class="notification-count"><?= $unread_notifications_count ?></span><?php endif; ?></span></a>
        <a href="reports.php" class="btn-nav"><i class="fas fa-chart-bar"></i><span>Reports</span></a>
        <a href="feedback.php" class="btn-nav"><i class="fas fa-comment-dots"></i><span>Feedback</span></a>
        <a href="settings.php" class="btn-nav"><i class="fas fa-cog"></i><span>Settings</span></a>
      </div>
      <div>
        <a href="./logout.php" class="btn-nav logout"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main">
      <h1>
        Send Notification
        <small>to <?= htmlspecialchars($app_data['applicant_name']) ?> for '<?= htmlspecialchars($app_data['business_name']) ?>'</small>
      </h1>

      <?= $flash_message ?>

      <div class="form-container">
        <form action="notify.php" method="POST">
          <input type="hidden" name="application_id" value="<?= htmlspecialchars($application_id) ?>">
          
          <div class="form-group">
            <label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" required value="Regarding your application for '<?= htmlspecialchars($app_data['business_name']) ?>'">
          </div>
          
          <div class="form-group">
            <label for="message">Message</label>
            <textarea id="message" name="message" required placeholder="Enter your message to the applicant..."></textarea>
          </div>
          
          <div class="form-actions">
            <a href="applicants.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" name="send_notification" class="btn">
              <i class="fas fa-paper-plane"></i> Send Notification
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</body>
</html>