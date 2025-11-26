<?php
// Page-specific variables
$page_title = 'Pending Users';
$current_page = 'pending_users';

// Include Header
require_once __DIR__ . '/admin_header.php';

// Include mail functions. A better long-term solution would be a shared 'includes' directory.
if (file_exists(__DIR__ . '/../config_mail.php')) {
    require_once __DIR__ . '/../config_mail.php';
}
if (file_exists(__DIR__ . '/../Admin-dashboard/email_functions.php')) {
    require_once __DIR__ . '/../Admin-dashboard/email_functions.php';
}

$message = '';

// --- Handle Linking Application ---
if (isset($_GET['action']) && $_GET['action'] === 'link_app' && isset($_GET['user_id']) && isset($_GET['app_id'])) {
    $target_user_id = (int)$_GET['user_id'];
    $app_to_link_id = (int)$_GET['app_id'];

    // Get app details before changing anything
    $app_details_stmt = $conn->prepare("SELECT user_id, business_name FROM applications WHERE id = ?");
    $app_details_stmt->bind_param("i", $app_to_link_id);
    $app_details_stmt->execute();
    $app_details = $app_details_stmt->get_result()->fetch_assoc();
    $original_user_id = $app_details['user_id'] ?? null;
    $business_name = $app_details['business_name'] ?? 'Unknown Application';
    $app_details_stmt->close();

    // Get target user details
    $user_details_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
    $user_details_stmt->bind_param("i", $target_user_id);
    $user_details_stmt->execute();
    $target_user = $user_details_stmt->get_result()->fetch_assoc();
    $target_user_name = $target_user['name'] ?? 'the new user';
    $target_user_email = $target_user['email'] ?? null;
    $user_details_stmt->close();

    if ($app_details && $target_user) {
        $conn->begin_transaction();
        try {
            // 1. Update the application's user_id
            $update_stmt = $conn->prepare("UPDATE applications SET user_id = ? WHERE id = ?");
            $update_stmt->bind_param("ii", $target_user_id, $app_to_link_id);
            $update_stmt->execute();
            $update_stmt->close();

            // 2. Notify the new user
            $new_user_message = "An existing application for '{$business_name}' has been linked to your account by an administrator.";
            $link = "../Applicant-dashboard/view_my_application.php?id={$app_to_link_id}";
            $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
            $notify_stmt->bind_param("iss", $target_user_id, $new_user_message, $link);
            $notify_stmt->execute();
            $notify_stmt->close();

            // 4. Email the new user (if email function exists and user has an email)
            if (function_exists('sendApplicationEmail') && $target_user_email) {
                try {
                    $absolute_link = APP_BASE_URL . "/Applicant-dashboard/view_my_application.php?id={$app_to_link_id}";

                    $email_subject = "An application has been linked to your account";
                    $email_body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                            <h2 style='color: #4a69bd;'>Application Linked</h2>
                            <p>Dear " . htmlspecialchars($target_user_name) . ",</p>
                            <p>" . htmlspecialchars($new_user_message) . "</p>
                            <p>You can view the application by clicking the button below:</p>
                            <p style='text-align: center; margin: 30px 0;'>
                                <a href='" . htmlspecialchars($absolute_link) . "' style='background-color: #4a69bd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>View Application</a>
                            </p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 0.9em; color: #777;'>Thank you for using our service.<br><strong>The OnlineBizPermit Team</strong></p>
                        </div>
                    </div>";
                    sendApplicationEmail($target_user_email, $target_user_name, $email_subject, $email_body);
                } catch (Exception $e) {
                    error_log("Email sending failed for new user ID {$target_user_id} on app link: " . $e->getMessage());
                }
            }

            // 3. Notify the original user if there was one and it's a different user
            if ($original_user_id && $original_user_id !== $target_user_id) {
                $original_user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
                $original_user_stmt->bind_param("i", $original_user_id);
                $original_user_stmt->execute();
                $original_user = $original_user_stmt->get_result()->fetch_assoc();
                $original_user_stmt->close();

                $original_user_message = "Your application for '{$business_name}' has been reassigned to '{$target_user_name}' by an administrator.";
                $notify_stmt = $conn->prepare("INSERT INTO notifications (user_id, message) VALUES (?, ?)");
                $notify_stmt->bind_param("is", $original_user_id, $original_user_message);
                $notify_stmt->execute();
                $notify_stmt->close();

                // Email the original user
                if ($original_user && !empty($original_user['email']) && function_exists('sendApplicationEmail')) {
                    try {
                        $email_subject = "Application Reassigned: '" . htmlspecialchars($business_name) . "'";
                        $email_body = "
                        <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                            <div style='max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                                <h2 style='color: #4a69bd;'>Application Reassigned</h2>
                                <p>Dear " . htmlspecialchars($original_user['name']) . ",</p>
                                <p>" . htmlspecialchars($original_user_message) . "</p>
                                <p>If you believe this is an error, please contact our support team.</p>
                                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                                <p style='font-size: 0.9em; color: #777;'>Thank you for using our service.<br><strong>The OnlineBizPermit Team</strong></p>
                            </div>
                        </div>";
                        sendApplicationEmail($original_user['email'], $original_user['name'], $email_subject, $email_body);
                    } catch (Exception $e) {
                        error_log("Email sending failed for original user ID {$original_user_id} on app link: " . $e->getMessage());
                    }
                }
            }

            $conn->commit();
            $message = '<div class="message success">Application linked successfully to ' . htmlspecialchars($target_user_name) . '. They have been notified.</div>';
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="message error">Failed to link application: ' . $e->getMessage() . '</div>';
        }
    } else {
        $message = '<div class="message error">Could not find the specified user or application to link.</div>';
    }
}

// --- Handle User Status Update ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    $action = $_GET['action'];
    $new_status = '';

    if ($action === 'approve') {
        $is_approved = 1;
    } elseif ($action === 'reject') {
        $is_approved = 2; // Use 2 for rejected
    }

    if (isset($is_approved)) {
        $conn->begin_transaction();
        try {
            // Update user approval status
            $stmt = $conn->prepare("UPDATE users SET is_approved = ? WHERE id = ? AND role = 'user'");
            $stmt->bind_param("ii", $is_approved, $userId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update user status");
            }
            $stmt->close();
            
            // Get user details for notification
            $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $userDetails = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            // Send notification
            if ($userDetails) {
                $status_text = $is_approved === 1 ? 'approved' : 'rejected';
                
                // 1. Create in-app notification
                if ($is_approved === 1) {
                    $notificationMessage = "Welcome! Your account has been approved. You can now submit and manage your business permit applications.";
                    $link = "../Applicant-dashboard/applicant_dashboard.php";
                } else {
                    $notificationMessage = "Your account registration has been rejected. Please contact support for more information.";
                    $link = null; // No link for rejection
                }
                $notifyStmt = $conn->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
                $notifyStmt->bind_param("iss", $userId, $notificationMessage, $link);
                $notifyStmt->execute();
                $notifyStmt->close();

                // 2. Send email notification
                if (function_exists('sendApplicationEmail') && !empty($userDetails['email'])) {
                    $email_subject = "Your OnlineBizPermit Account has been " . ucfirst($status_text);
                    $email_body = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <div style='max-width: 600px; margin: 20px auto; border: 1px solid #ddd; border-radius: 8px; padding: 20px;'>
                            <h2 style='color: #4a69bd;'>Account Status Update</h2>
                            <p>Dear " . htmlspecialchars($userDetails['name']) . ",</p>
                            <p>" . htmlspecialchars($notificationMessage) . "</p>";
                    if ($is_approved === 1) {
                        $login_link = APP_BASE_URL . "/Applicant-dashboard/login.php";
                        $email_body .= "<p style='text-align: center; margin: 30px 0;'><a href='" . htmlspecialchars($login_link) . "' style='background-color: #4a69bd; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: bold;'>Login to Your Account</a></p>";
                    }
                    $email_body .= "<hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'><p style='font-size: 0.9em; color: #777;'>Thank you,<br><strong>The OnlineBizPermit Team</strong></p>
                        </div>
                    </div>";
                    sendApplicationEmail($userDetails['email'], $userDetails['name'], $email_subject, $email_body);
                }
            }
            
            $conn->commit();
            $status_text = $is_approved === 1 ? 'approved' : 'rejected';
            $message = '<div class="message success">User has been ' . $status_text . '. They have been notified.</div>';
        } catch (Exception $e) {
            $conn->rollback();
            $message = '<div class="message error">Failed to update user status: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- Fetch Pending Users ---
$pending_users = [];
$sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
               COUNT(a.id) as application_count,
               MAX(a.submitted_at) as last_application_date
        FROM users u
        LEFT JOIN applications a ON u.id = a.user_id
        WHERE u.role = 'user' AND u.is_approved = 0
        GROUP BY u.id
        ORDER BY u.created_at ASC";
$result = $conn->query($sql);
if ($result) {
    $pending_users = $result->fetch_all(MYSQLI_ASSOC);
}

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <div class="header-left">
            <button id="hamburger"><i class="fas fa-bars"></i></button>
            <h1>Pending User Approvals</h1>
        </div>
    </header>

    <?php if (!empty($message)) echo $message; ?>

    <div class="card-container">
        <div class="table-header">
            <div class="table-info">
                <h3>Users Awaiting Approval</h3>
                <p>Review new user registrations and check their application history</p>
            </div>
            <div class="table-actions">
                <span class="badge"><?= count($pending_users) ?> pending</span>
            </div>
        </div>

        <?php if (empty($pending_users)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <h3>No Pending Users</h3>
                <p>All user registrations have been reviewed.</p>
            </div>
        <?php else: ?>
            <div class="user-cards-grid">
                <!-- Table structure replaced with a grid of cards -->
                <!-- <thead>
                    <tr>
                        <th>User Details</th>
                        <th>Application History</th>
                        <th>Potential Matches</th>
                        <th>Registration Date</th>
                        <th>Actions</th>
                    </tr>
                </thead> -->
                <!-- <tbody> -->
                    <?php foreach ($pending_users as $user): ?>
                        <?php
                            $potential_apps = [];
                            // This query requires MySQL 5.7+ or MariaDB 10.2+ for JSON functions
                            $sql_potential = "SELECT a.id, a.business_name, a.status, u.name as current_owner_name
                                FROM applications a
                                LEFT JOIN users u ON a.user_id = u.id
                                WHERE (
                                    JSON_UNQUOTE(JSON_EXTRACT(a.form_details, '$.owner_name')) = ?
                                    OR
                                    TRIM(CONCAT_WS(' ',
                                        JSON_UNQUOTE(JSON_EXTRACT(a.form_details, '$.first_name')),
                                        JSON_UNQUOTE(JSON_EXTRACT(a.form_details, '$.middle_name')),
                                        JSON_UNQUOTE(JSON_EXTRACT(a.form_details, '$.last_name'))
                                    )) = ?
                                )
                                AND (a.user_id IS NULL OR a.user_id != ?)";

                            $potential_apps_stmt = $conn->prepare($sql_potential);

                            if ($potential_apps_stmt) {
                                $potential_apps_stmt->bind_param("ssi", $user['name'], $user['name'], $user['id']);
                                $potential_apps_stmt->execute();
                                $potential_apps_result = $potential_apps_stmt->get_result();
                                $potential_apps = $potential_apps_result->fetch_all(MYSQLI_ASSOC);
                                $potential_apps_stmt->close();
                            }
                            // Silently fail if JSON functions are not supported, or log the error
                        ?>
                        <div class="user-card">
                            <div class="card-section">
                                <div class="user-info"> <!-- This is now a flex-column container -->
                                    <div class="user-main-info"> <!-- This is the new flex-row for avatar and name -->
                                        <div class="user-avatar" style="background-color: #<?= substr(md5($user['name']), 0, 6) ?>;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="user-details">
                                            <strong><?= htmlspecialchars($user['name']) ?></strong>
                                            <span class="user-id">#<?= $user['id'] ?></span>
                                        </div>
                                    </div>
                                    <div class="contact-info"> <!-- Contact info is now a sibling, not a child of the row -->
                                        <div><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></div>
                                        <?php if (!empty($user['phone'])): ?>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($user['phone']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="card-section">
                                <h4>Application History</h4>
                                    <?php if ($user['application_count'] > 0): ?>
                                        <div class="history-summary">
                                            <span class="app-count"><?= $user['application_count'] ?> application<?= $user['application_count'] > 1 ? 's' : '' ?></span>
                                            <?php if ($user['last_application_date']): ?>
                                                <span class="last-app">Last: <?= date('M d, Y', strtotime($user['last_application_date'])) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <a href="view_user_details.php?id=<?= $user['id'] ?>" class="btn btn-sm">View History</a>
                                    <?php else: ?>
                                        <span class="no-history">No previous applications</span>
                                <?php endif; ?>
                            </div>
                            <div class="card-section">
                                <h4>Potential Matches</h4>
                                <?php if (!empty($potential_apps)): ?>
                                    <div class="potential-matches">
                                        <ul>
                                            <?php foreach ($potential_apps as $app): ?>
                                                <li>
                                                    <div class="match-info">
                                                        <i class="fas fa-file-alt"></i>
                                                        <div>
                                                            <strong>'<?= htmlspecialchars($app['business_name']) ?>'</strong> (ID: #<?= $app['id'] ?>)
                                                            <small>Owner: <?= htmlspecialchars($app['current_owner_name'] ?? 'Unassigned') ?></small>
                                                        </div>
                                                    </div>
                                                    <a href="pending_users.php?action=link_app&user_id=<?= $user['id'] ?>&app_id=<?= $app['id'] ?>" class="btn btn-sm btn-link" onclick="return confirm('Link this application to ' + <?= json_encode($user['name']) ?> + '? The original owner (if any) will be notified.')">
                                                        <i class="fas fa-link"></i> Link
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <span class="no-history">No matches found</span>
                                <?php endif; ?>
                                <?php if ($current_user_role === 'admin'): ?>
                                <button class="btn btn-sm btn-manual-link" data-userid="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['name']) ?>">
                                    <i class="fas fa-search"></i> Manual Link
                                </button>
                                <?php endif; ?> 
                            </div>
                            <div class="card-footer">
                                <span class="date-info">
                                    Registered on:
                                    <?= date('M d, Y', strtotime($user['created_at'])) ?>
                                    <small><?= date('H:i', strtotime($user['created_at'])) ?></small>
                                </span>
                                <div class="action-buttons">
                                    <a href="view_user_details.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline">
                                        <i class="fas fa-eye"></i> User Details
                                    </a>
                                    <a href="pending_users.php?action=approve&id=<?= $user['id'] ?>" 
                                       class="btn btn-sm btn-primary"
                                       onclick="return confirm('Approve this user? They will be able to access the applicant dashboard.')">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="pending_users.php?action=reject&id=<?= $user['id'] ?>" 
                                       class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Reject this user? They will not be able to access the system.')">
                                        <i class="fas fa-times"></i> Reject
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <!-- </tbody> -->
            <!-- </table> -->
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Manual Link Modal -->
<div id="manualLinkModal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h3>Link Application to <span id="modalUserName"></span></h3>
        <p>Search for an application by Business Name or ID to link it to this user.</p>
        <div class="modal-search-form">
            <input type="text" id="modalAppSearch" placeholder="Search by Business Name or ID...">
            <input type="hidden" id="modalUserId">
        </div>
        <div id="modalSearchResults" class="modal-search-results">
            <div class="modal-placeholder">Start typing to search for applications.</div>
        </div>
    </div>
</div>

<style>
/* Professional Admin Design System */
/* Sidebar styles copied from Staff dashboard for consistency */
:root {
    --sidebar-bg: #232a3b;
    --sidebar-text: #d0d2d6;
    --sidebar-hover-bg: #3c4b64;
    --sidebar-active-bg: #4a69bd; /* Primary color */
    --sidebar-active-text: #fff;

    --admin-bg: #f1f5f9;
    --card-bg: #ffffff;
    --border-color: #e2e8f0;
    --text-primary: #1e293b;
    --text-secondary: #475569; /* Darkened for better contrast */
    --primary: #4a69bd;
    --primary-hover: #3e5aa2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --border-radius: 0.5rem;
}

/* Sidebar Styles */
.sidebar { width: 80px; background: var(--sidebar-bg); padding: 25px 10px; display: flex; flex-direction: column; justify-content: space-between; color: var(--sidebar-text); flex-shrink: 0; transition: width 0.3s ease; overflow-x: hidden; position: fixed; height: 100%; z-index: 100; }
.sidebar:hover { width: 240px; }
.sidebar h2 { margin-bottom: 35px; position: relative; height: 24px; display: flex; align-items: center; }
.sidebar h2 span { font-size: 18px; font-weight: 700; letter-spacing: 1px; color: #fff; white-space: nowrap; opacity: 0; transition: opacity 0.2s ease 0.1s; margin-left: 52px; }
.sidebar h2::before { content: '\f1ad'; font-family: 'Font Awesome 6 Free'; font-weight: 900; font-size: 24px; color: #fff; position: absolute; left: 50%; transform: translateX(-50%); transition: left 0.3s ease; }
.sidebar:hover h2 span { opacity: 1; }
.sidebar:hover h2::before { left: 28px; }
.btn-nav { display: flex; align-items: center; justify-content: center; padding: 12px 15px; margin-bottom: 8px; border-radius: 8px; text-decoration: none; background: transparent; color: var(--sidebar-text); font-weight: 600; transition: all 0.2s ease; position: relative; }
.btn-nav i { min-width: 20px; text-align: center; font-size: 1.1em; flex-shrink: 0; }
.btn-nav span { white-space: nowrap; opacity: 0; max-width: 0; overflow: hidden; transition: opacity 0.1s ease, max-width 0.2s ease 0.1s, margin-left 0.2s ease 0.1s; }
.sidebar:hover .btn-nav { justify-content: flex-start; }
.sidebar:hover .btn-nav span { opacity: 1; max-width: 150px; margin-left: 12px; }
.btn-nav:hover { background: var(--sidebar-hover-bg); color: #fff; }
.btn-nav.active { background: var(--sidebar-active-bg); color: var(--sidebar-active-text); }
.btn-nav.logout { margin-top: 20px; color: #e74c3c; }
.btn-nav.logout:hover { background: #e74c3c; color: #fff; }
.notification-badge { background-color: var(--danger); color: white; border-radius: 10px; padding: 2px 6px; font-size: 11px; font-weight: bold; position: absolute; top: 8px; right: 12px; transition: opacity 0.2s, transform 0.2s; transform: scale(1); }
.sidebar:not(:hover) .notification-badge { transform: scale(0.8) translate(8px, -8px); }

/* General Layout */
.main { margin-left: 80px; transition: margin-left 0.3s ease; padding: 1.5rem; }
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
.header-left { display: flex; align-items: center; gap: 1rem; }
.header h1 { font-size: 1.75rem; font-weight: 700; color: black; margin: 0; }
#hamburger { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-secondary); }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: var(--border-radius); font-weight: 600; font-size: 0.875rem; border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease; text-decoration: none; }
.btn-primary { background-color: var(--primary); color: white; }
.btn-primary:hover { background-color: var(--primary-hover); }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
.btn-outline { background-color: transparent; border-color: var(--border-color); color: var(--text-secondary); }
.btn-outline:hover { background-color: #f8f9fa; border-color: #d1d5db; color: var(--text-primary); }
.btn-outline-danger { background-color: transparent; border-color: var(--danger); color: var(--danger); }
.btn-outline-danger:hover { background-color: #fee2e2; }

/* Card Container */
.card-container {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    padding: 1.5rem;
}
.table-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid var(--border-color);
}
.table-info h3 { margin: 0 0 5px 0; color: var(--text-primary); }
.table-info p { margin: 0; color: var(--text-secondary); font-size: 0.9rem; }
.badge {
    background: var(--primary-hover);
    color: white;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}
.empty-state { text-align: center; padding: 4rem 1rem; color: var(--text-secondary); }
.empty-state i { font-size: 3rem; margin-bottom: 1rem; color: var(--success); }
.empty-state h3 { font-size: 1.25rem; color: var(--text-primary); }

/* User Cards Grid */
.user-cards-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1.5rem;
}
.user-card {
    background-color: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-sm);
    display: grid;
    grid-template-columns: repeat(1, 1fr);
}
@media (min-width: 1024px) {
    .user-card {
        grid-template-columns: 1.2fr 1fr 1fr;
        align-items: start;
    }
}
@media (min-width: 768px) and (max-width: 1200px) {
    .user-card {
        grid-template-columns: 1fr 1fr; /* 2 columns for tablet */
    }
}

.card-section {
    padding: 1.25rem;
    border-bottom: 1px solid var(--border-color);
}
.user-card > .card-section:last-child { border-bottom: none; }
@media (min-width: 1024px) {
    .card-section {
        border-bottom: none;
        border-right: 1px solid var(--border-color);
        height: 100%;
    }
    .user-card > .card-section:last-child { border-right: none; }
}
.card-section h4 {
    margin: 0 0 1.25rem 0; /* Increased bottom margin */
    font-size: 0.875rem; /* Slightly larger */
    font-weight: 700; /* Bolder */
    color: var(--text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.user-info { display: flex; flex-direction: column; align-items: flex-start; gap: 1rem; }
.user-main-info { display: flex; align-items: center; gap: 1rem; }
.user-avatar {
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white; font-size: 1.5rem;
    flex-shrink: 0;
}
.user-details strong { font-size: 1.1rem; font-weight: 600; color: var(--text-primary); }
.user-id { font-size: 0.875rem; color: var(--text-secondary); } /* Increased font size */
.contact-info { margin-top: 0; font-size: 0.9rem; display: flex; flex-direction: column; gap: 0.5rem; } /* Increased font size & gap */
.contact-info div { display: flex; align-items: center; gap: 0.5rem; color: var(--text-secondary); }
.contact-info i { width: 16px; text-align: center; }

.history-summary { display: flex; flex-direction: column; gap: 0.25rem; margin-bottom: 0.75rem; }
.app-count { font-weight: 600; color: var(--text-primary); }
.last-app, .no-history { font-size: 0.9rem; color: var(--text-secondary); line-height: 1.5; } /* Increased font size and line height */
.no-history { font-style: italic; }

.card-footer {
    padding: 1.25rem;
    background-color: #f8fafc;
    border-top: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    grid-column: 1 / -1; /* Span all columns */
}
.date-info { font-size: 0.875rem; color: var(--text-secondary); } /* Increased font size */
.date-info small { display: block; }
.action-buttons { display: flex; gap: 0.5rem; flex-wrap: wrap; }

/* Potential Matches List */
.potential-matches ul {
    list-style: none;
    padding: 0;
    margin: 0;
    max-height: 120px;
    overflow-y: auto;
    border-radius: var(--border-radius);
}
.potential-matches li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.5rem;
    font-size: 0.9rem;
}
.match-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-grow: 1;
}
.match-info i { color: var(--text-secondary); }
.match-info small { display: block; color: var(--text-secondary); font-size: 0.875rem; line-height: 1.5; } /* Increased font size and line height */
.btn-link {
    background: #eef2ff;
    color: var(--primary);
    font-weight: 600;
    flex-shrink: 0;
    padding: 0.375rem 0.75rem;
    font-size: 0.8rem;
    border-radius: var(--border-radius);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    border: 1px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-link:hover { background: var(--primary); color: white; }
.btn-manual-link {
    background-color: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-secondary);
    margin-top: 0.75rem;
    width: 100%;
}
.btn-manual-link:hover { background-color: #f8f9fa; color: var(--text-primary); }

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1001;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(30, 41, 59, 0.5);
    animation: fadeIn 0.3s;
}
.modal-content {
    background-color: #fefefe;
    margin: 8% auto;
    padding: 2rem;
    width: 80%;
    max-width: 600px;
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    position: relative;
}
.close-btn { position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; color: #9ca3af; cursor: pointer; line-height: 1; }
.close-btn:hover { color: var(--text-primary); }
.modal-content h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    color: var(--text-primary);
}
.modal-search-form input {
    width: 100%;
    padding: 0.75rem;
    font-size: 1rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    box-sizing: border-box;
}
.modal-search-results {
    margin-top: 15px;
    max-height: 300px;
    overflow-y: auto;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
}
.modal-search-results ul {
    list-style: none;
    padding: 0;
    margin: 0;
}
.modal-search-results li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem;
    border-bottom: 1px solid var(--border-color);
}
.modal-search-results li:last-child { border-bottom: none; }
.modal-placeholder {
    padding: 20px;
    text-align: center;
    color: var(--text-secondary);
}
</style>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Manual Link Modal Logic ---
    const manualLinkModal = document.getElementById('manualLinkModal');
    if (manualLinkModal) {
        const modalCloseBtn = manualLinkModal.querySelector('.close-btn');
        const modalAppSearch = document.getElementById('modalAppSearch');
        const modalSearchResults = document.getElementById('modalSearchResults');
        const modalUserName = document.getElementById('modalUserName');
        const modalUserId = document.getElementById('modalUserId');

        document.querySelectorAll('.btn-manual-link').forEach(button => {
            button.addEventListener('click', function() {
                const userId = this.dataset.userid;
                const userName = this.dataset.username;
                
                modalUserName.textContent = userName;
                modalUserId.value = userId;
                modalAppSearch.value = '';
                modalSearchResults.innerHTML = '<div class="modal-placeholder">Start typing to search for applications.</div>';
                
                manualLinkModal.style.display = 'block';
                modalAppSearch.focus();
            });
        });

        modalCloseBtn.onclick = () => {
            manualLinkModal.style.display = 'none';
        };

        window.addEventListener('click', (event) => {
            if (event.target === manualLinkModal) {
                manualLinkModal.style.display = 'none';
            }
        });

        let searchTimeout;
        modalAppSearch.addEventListener('keyup', function() {
            clearTimeout(searchTimeout);
            const searchTerm = this.value.trim();
            const userId = modalUserId.value;

            if (searchTerm.length < 2) {
                modalSearchResults.innerHTML = '<div class="modal-placeholder">Enter at least 2 characters to search.</div>';
                return;
            }

            modalSearchResults.innerHTML = '<div class="modal-placeholder">Searching...</div>';

            searchTimeout = setTimeout(() => {
                fetch(`search_applications.php?term=${encodeURIComponent(searchTerm)}`)
                    .then(response => response.json())
                    .then(data => {
                        let resultsHtml = '';
                        if (data.length > 0) {
                            resultsHtml += '<ul>';
                            data.forEach(app => {
                                const owner = app.current_owner_name ? ` (Owner: ${app.current_owner_name})` : ' (Unassigned)';
                                // Use JSON.stringify to safely embed the user's name into the confirm dialog.
                                const userNameForConfirm = JSON.stringify(modalUserName.textContent);
                                const confirmOnClick = `return confirm('Link this application to ' + ${userNameForConfirm} + '?')`;
                                resultsHtml += `
                                    <li>
                                        <div class="match-info">
                                            <i class="fas fa-file-alt"></i>
                                            <div>
                                                <strong>'${app.business_name}'</strong> (ID: #${app.id})
                                                <small>Status: ${app.status}${owner}</small>
                                            </div>
                                        </div>
                                        <a href="pending_users.php?action=link_app&user_id=${userId}&app_id=${app.id}" class="btn btn-sm btn-link" onclick="${confirmOnClick}">
                                            <i class="fas fa-link"></i> Link
                                        </a>
                                    </li>
                                `;
                            });
                            resultsHtml += '</ul>';
                        } else {
                            resultsHtml = '<div class="modal-placeholder">No applications found matching your search.</div>';
                        }
                        modalSearchResults.innerHTML = resultsHtml;
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        modalSearchResults.innerHTML = '<div class="modal-placeholder error">Error searching for applications.</div>';
                    });
            }, 300); // Debounce for 300ms
        });
    }
});
</script>
