<?php
// Page-specific variables
$page_title = 'User Management';
$current_page = 'user_management';

// Include Header
require_once __DIR__ . '/admin_header.php';

// Include mail functions. A better long-term solution would be a shared 'includes' directory.
if (file_exists(__DIR__ . '/../config_mail.php')) {
    require_once __DIR__ . '/../config_mail.php';
}
if (file_exists(__DIR__ . '/../Admin-dashboard/email_functions.php')) {
    require_once __DIR__ . '/../Admin-dashboard/email_functions.php';
}

// This page should be accessible only by admins
if ($current_user_role !== 'admin') {
    // You can either show an error or redirect
    echo "<div class='main'><div class='message error'>You do not have permission to access this page.</div></div>";
    require_once __DIR__ . '/admin_footer.php';
    exit;
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

            // Email the new user
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

// --- Handle Add User ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $message = '<div class="message error">All fields are required.</div>';
    } elseif (strlen($password) < 8) {
        $message = '<div class="message error">Password must be at least 8 characters long.</div>';
    } else {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $message = '<div class="message error">An account with this email already exists.</div>';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param("ssss", $name, $email, $hashedPassword, $role);
            $message = $insertStmt->execute() ? '<div class="message success">User added successfully.</div>' : '<div class="message error">Failed to add user.</div>';
        }
    }
}

// --- Handle Delete User ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $userIdToDelete = (int)$_GET['id'];
    if ($userIdToDelete === $current_user_id) {
        $message = '<div class="message error">You cannot delete your own account.</div>';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $userIdToDelete);
        $message = $stmt->execute() ? '<div class="message success">User deleted successfully.</div>' : '<div class="message error">Failed to delete user.</div>';
    }
}

// --- Handle Bulk Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action']) && isset($_POST['selected_users'])) {
    $bulk_action = $_POST['bulk_action'];
    $selected_users = $_POST['selected_users'];
    
    if (empty($selected_users)) {
        $message = '<div class="message error">Please select at least one user.</div>';
    } else {
        $user_ids = array_map('intval', explode(',', $selected_users));
        $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
        
        switch ($bulk_action) {
            case 'delete':
                // Remove current user from selection if present
                $user_ids = array_filter($user_ids, function($id) use ($current_user_id) {
                    return $id !== $current_user_id;
                });
                
                if (empty($user_ids)) {
                    $message = '<div class="message error">You cannot delete your own account.</div>';
                } else {
                    $placeholders = str_repeat('?,', count($user_ids) - 1) . '?';
                    $stmt = $conn->prepare("DELETE FROM users WHERE id IN ($placeholders)");
                    $stmt->bind_param(str_repeat('i', count($user_ids)), ...$user_ids);
                    $deleted_count = $stmt->execute() ? $stmt->affected_rows : 0;
                    $message = '<div class="message success">' . $deleted_count . ' user(s) deleted successfully.</div>';
                }
                break;
                
            case 'change_role':
                $new_role = $_POST['new_role'] ?? '';
                if (in_array($new_role, ['admin', 'staff', 'user'])) {
                    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id IN ($placeholders)");
                    $params = array_merge([$new_role], $user_ids);
                    $stmt->bind_param('s' . str_repeat('i', count($user_ids)), ...$params);
                    $updated_count = $stmt->execute() ? $stmt->affected_rows : 0;
                    $message = '<div class="message success">' . $updated_count . ' user(s) role updated to ' . ucfirst($new_role) . '.</div>';
                } else {
                    $message = '<div class="message error">Invalid role selected.</div>';
                }
                break;
        }
    }
}

// --- Fetch Users with Search, Filter, and Pagination ---
$search_term = trim($_GET['search'] ?? '');
$filter_role = trim($_GET['role'] ?? '');
$users = [];

// Pagination variables
$limit = 10; // Users per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Base SQL and params for counting and fetching
$sql_base = "FROM users";
$where_clauses = [];
$params = [];
$types = "";

if (!empty($search_term)) {
    $where_clauses[] = "(name LIKE ? OR email LIKE ?)";
    $like_term = "%" . $search_term . "%";
    $params[] = $like_term;
    $params[] = $like_term;
    $types .= "ss";
}
if (!empty($filter_role) && in_array($filter_role, ['admin', 'staff', 'user'])) {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= "s";
}
if (!empty($where_clauses)) { $sql_base .= " WHERE " . implode(" AND ", $where_clauses); }

// Get total number of users for pagination
$count_sql = "SELECT COUNT(*) " . $sql_base;
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) { $stmt_count->bind_param($types, ...$params); }
$stmt_count->execute();
$total_users = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_users / $limit);

// Fetch users for the current page
$fetch_sql = "SELECT id, name, email, role, is_approved, created_at " . $sql_base . " ORDER BY id DESC LIMIT ? OFFSET ?";
$fetch_params = array_merge($params, [$limit, $offset]);
$fetch_types = $types . "ii";

$stmt_fetch = $conn->prepare($fetch_sql);
$stmt_fetch->bind_param($fetch_types, ...$fetch_params);
$stmt_fetch->execute();
$users = $stmt_fetch->get_result()->fetch_all(MYSQLI_ASSOC);

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <div class="header-left">
            <button id="hamburger"><i class="fas fa-bars"></i></button>
            <h1>User Management</h1>
        </div>
        <div class="header-right">
            <form action="user_management.php" method="GET" class="filter-controls">
                <div class="search-form">
                    <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit" aria-label="Search"><i class="fas fa-search"></i></button>
                </div>
                <select name="role" class="role-filter" onchange="this.form.submit()" aria-label="Filter by role">
                    <option value="">All Roles</option>
                    <option value="admin" <?= ($filter_role === 'admin') ? 'selected' : '' ?>>Admin</option>
                    <option value="staff" <?= ($filter_role === 'staff') ? 'selected' : '' ?>>Staff</option>
                    <option value="user" <?= ($filter_role === 'user') ? 'selected' : '' ?>>Applicant</option>
                </select>
            </form>
            <button id="addUserBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Add User</button>
        </div>
    </header>

    <?php if ($message) echo $message; ?>

    <!-- Bulk Actions Bar -->
    <div class="bulk-actions" id="bulkActions" style="display: none;">
        <div class="bulk-info">
            <span id="selectedCount">0</span> user(s) selected
        </div>
        <div class="bulk-controls">
            <form method="POST" id="bulkForm" style="display: flex; gap: 10px; align-items: center;">
                <input type="hidden" name="selected_users" id="selectedUsers">
                <select name="bulk_action" id="bulkAction" required>
                    <option value="">Select Action</option>
                    <option value="change_role">Change Role</option>
                    <option value="delete">Delete Selected</option>
                </select>
                <select name="new_role" id="newRole" style="display: none;">
                    <option value="user">Applicant</option>
                    <option value="staff">Staff</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit" class="btn btn-primary" id="executeBulk">Apply</button>
                <button type="button" class="btn btn-secondary" id="clearSelection">Clear</button>
            </form>
        </div>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll"></th>
                    <th>User</th><th>Role</th><th>Status</th><th>Joined On</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" class="no-results-message">
                            <i class="fas fa-user-slash"></i>
                            <div>No users found.<?php if(!empty($search_term) || !empty($filter_role)) echo " Matching your criteria." ?></div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <?php if ($user['id'] !== $current_user_id): ?>
                                    <input type="checkbox" class="user-checkbox" value="<?= $user['id'] ?>">
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar" style="background-color: #<?= substr(md5($user['name']), 0, 6) ?>;">
                                        <span><?= strtoupper(substr($user['name'], 0, 1)) ?></span>
                                    </div>
                                    <div class="user-cell-info">
                                        <strong><?= htmlspecialchars($user['name']) ?></strong>
                                        <small>#<?= $user['id'] ?> &bull; <?= htmlspecialchars($user['email']) ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php
                                $role_icon = 'fa-user'; // Default for Applicant
                                if ($user['role'] === 'admin') {
                                    $role_icon = 'fa-user-shield';
                                } elseif ($user['role'] === 'staff') {
                                    $role_icon = 'fa-user-cog';
                                }
                                ?>
                                <span class="role-badge role-<?= strtolower($user['role']) ?>">
                                    <i class="fas <?= $role_icon ?>"></i><span><?= ($user['role'] === 'user') ? 'Applicant' : ucfirst($user['role']) ?></span>
                                </span>
                            </td>
                            <td>
                                <?php 
                                $is_approved = (int)$user['is_approved'];
                                $statusText = $is_approved == 1 ? 'Approved' : ($is_approved == 2 ? 'Rejected' : 'Pending');
                                $statusClass = $is_approved == 1 ? 'status-active' : ($is_approved == 2 ? 'status-rejected' : 'status-pending');
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= $statusText ?>
                                </span>
                            </td>
                            <td>
                                <div class="date-cell">
                                    <span><?= date('M d, Y', strtotime($user['created_at'])) ?></span>
                                    <small><?= date('h:i A', strtotime($user['created_at'])) ?></small>
                                </div>
                            </td>
                            <td>
                                <a href="view_user_details.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-eye"></i> View</a>
                                <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline"><i class="fas fa-pencil-alt"></i> Edit</a>
                                <?php if ($user['id'] !== $current_user_id): ?>
                                    <?php if ($user['role'] === 'user'): ?>
                                    <button class="btn btn-sm btn-outline btn-manual-link" data-userid="<?= $user['id'] ?>" data-username="<?= htmlspecialchars($user['name']) ?>">
                                        <i class="fas fa-link"></i> Link App
                                    </button>
                                    <?php endif; ?>
                                    <a href="user_management.php?action=delete&id=<?= $user['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');"><i class="fas fa-trash-alt"></i> Delete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php
        $query_params = $_GET;
        
        // Previous page link
        if ($page > 1) {
            $query_params['page'] = $page - 1;
            echo '<a href="?' . http_build_query($query_params) . '">&laquo; Prev</a>';
        }

        // Page number links
        for ($i = 1; $i <= $total_pages; $i++) {
            $query_params['page'] = $i;
            echo '<a href="?' . http_build_query($query_params) . '" class="' . (($i == $page) ? 'active' : '') . '">' . $i . '</a>';
        }

        // Next page link
        if ($page < $total_pages) {
            $query_params['page'] = $page + 1;
            echo '<a href="?' . http_build_query($query_params) . '">Next &raquo;</a>';
        }
        ?>
    </div>
    <?php endif; ?>

    <!-- Add User Modal -->
    <div id="addUserModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3>Add New User</h3>
            <form action="user_management.php" method="POST">
                <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" required></div>
                <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email" required></div>
                <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
                <div class="form-group">
                    <label for="role">Role</label>
                    <select id="role" name="role" required><option value="user">Applicant</option><option value="staff">Staff</option><option value="admin">Admin</option></select>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
            </form>
        </div>
    </div>

    <!-- Manual Link Modal -->
    <div id="manualLinkModal" class="modal">
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
</div>

<script>
    // Add User Modal
    const addUserModal = document.getElementById('addUserModal');
    if (addUserModal) {
        const addUserBtn = document.getElementById('addUserBtn');
        const closeBtn = addUserModal.querySelector('.close-btn');
        if(addUserBtn) addUserBtn.onclick = () => addUserModal.style.display = 'block';
        if(closeBtn) closeBtn.onclick = () => addUserModal.style.display = 'none';
        window.addEventListener('click', (event) => {
            if (event.target == addUserModal) addUserModal.style.display = 'none';
        });
    }

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
                                        <a href="user_management.php?action=link_app&user_id=${userId}&app_id=${app.id}" class="btn btn-sm btn-link" onclick="${confirmOnClick}">
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

    // Bulk Actions
    const selectAllCheckbox = document.getElementById('selectAll');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    const selectedUsers = document.getElementById('selectedUsers');
    const bulkAction = document.getElementById('bulkAction');
    const newRole = document.getElementById('newRole');
    const bulkForm = document.getElementById('bulkForm');
    const clearSelection = document.getElementById('clearSelection');

    // Select All functionality
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
            updateBulkActions();
        });
    }

    // Individual checkbox change
    userCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateBulkActions();
            updateSelectAllState();
        });
    });

    // Update bulk actions visibility and selected count
    function updateBulkActions() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count > 0) {
            bulkActions.style.display = 'flex';
            selectedCount.textContent = count;
            
            // Update selected users input
            const selectedIds = Array.from(checkedBoxes).map(cb => cb.value);
            selectedUsers.value = selectedIds.join(',');
        } else {
            bulkActions.style.display = 'none';
        }
    }

    // Update select all checkbox state
    function updateSelectAllState() {
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const totalBoxes = userCheckboxes.length;
        
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedBoxes.length === totalBoxes) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Show/hide new role select based on bulk action
    if (bulkAction) {
        bulkAction.addEventListener('change', function() {
            if (this.value === 'change_role') {
                newRole.style.display = 'block';
                newRole.required = true;
            } else {
                newRole.style.display = 'none';
                newRole.required = false;
            }
        });
    }

    // Clear selection
    if (clearSelection) {
        clearSelection.addEventListener('click', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
            updateBulkActions();
        });
    }

    // Bulk form submission confirmation
    if (bulkForm) {
        bulkForm.addEventListener('submit', function(e) {
            const action = bulkAction.value;
            const count = document.querySelectorAll('.user-checkbox:checked').length;
            
            if (action === 'delete') {
                if (!confirm(`Are you sure you want to delete ${count} user(s)? This action cannot be undone.`)) {
                    e.preventDefault();
                }
            } else if (action === 'change_role') {
                const role = newRole.value;
                if (!confirm(`Are you sure you want to change the role of ${count} user(s) to ${role}?`)) {
                    e.preventDefault();
                }
            }
        });
    }
</script>

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
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}
.header-left, .header-right { display: flex; align-items: center; gap: 1rem; }
.header h1 { font-size: 1.75rem; font-weight: 700; color: black; margin: 0; }
#hamburger { background: none; border: none; font-size: 1.25rem; cursor: pointer; color: var(--text-secondary); }

/* Buttons */
.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.6rem 1.2rem; border-radius: var(--border-radius); font-weight: 600; font-size: 0.875rem; border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease; text-decoration: none; }
.btn-primary { background-color: var(--primary); color: white; }
.btn-primary:hover { background-color: var(--primary-hover); }
.btn-secondary { background-color: var(--border-color); color: var(--text-primary); }
.btn-secondary:hover { background-color: #d1d5db; }
.btn-danger { background-color: var(--danger); color: white; }
.btn-danger:hover { background-color: #dc2626; }
.btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; }
.btn-outline { background-color: transparent; border-color: var(--border-color); color: var(--text-secondary); }
.btn-outline:hover { background-color: #f8f9fa; border-color: #d1d5db; color: var(--text-primary); }

/* Forms & Filters */
.filter-controls { display: flex; gap: 0.75rem; }
.search-form { position: relative; }
.search-form input { padding: 0.6rem 1rem 0.6rem 2.5rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 0.875rem; }
.search-form input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2); outline: none; }
.search-form button { position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-secondary); cursor: pointer; }
.role-filter { padding: 0.6rem 1rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 0.875rem; background-color:#fffafa; }

/* Table Container */
.table-container {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-md);
    border: 1px solid var(--border-color);
    overflow: hidden; /* Important for rounded corners on table */
}

/* Table */
table { width: 100%; border-collapse: collapse; }
th, td { padding: 0.85rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; font-size: 0.9rem; line-height: 1.6; color: var(--text-primary); }
thead th {
    font-weight: 600;
    color: var(--text-secondary);
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    background-color: #f8f9fa;
}
tbody tr:last-child th, tbody tr:last-child td { border-bottom: none; }
tbody tr:hover { background-color: #f8fafc; }
td:last-child { text-align: right; }
.no-results-message { text-align: center; padding: 3rem; color: var(--text-secondary); }
.no-results-message i { font-size: 2rem; margin-bottom: 0.5rem; display: block; }

/* Checkboxes */
input[type="checkbox"] {
    width: 1rem; height: 1rem; border-radius: 0.25rem; border: 1px solid #9ca3af; cursor: pointer;
    accent-color: var(--primary);
}

/* Status Badge Styles */
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: inline-block;
}
.status-pending { background-color: #fef3c7; color: #92400e; }
.status-active { background-color: #d1fae5; color: #065f46; }
.status-rejected { background-color: #fee2e2; color: #991b1b; }

/* Role Badge Styles */
.role-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 9999px; /* Pill shape */
    font-size: 0.8rem;
    font-weight: 600;
    color: white;
    text-transform: capitalize;
    border: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-width: 100px;
    justify-content: center;
}
.role-admin { background-color: #9333ea; } /* Purple */
.role-staff { background-color: #2563eb; } /* Blue */
.role-user { background-color: #475569; } /* Slate */

/* Pagination */
.pagination { display: flex; justify-content: center; margin-top: 1.5rem; gap: 0.25rem; }
.pagination a {
    display: inline-block;
    padding: 0.5rem 1rem;
    text-decoration: none;
    color: var(--text-secondary);
    background-color: white;
    border: 1px solid var(--border-color);
    border-radius: 0.375rem;
    transition: all 0.2s ease;
}
.pagination a:hover { background-color: #f1f5f9; color: var(--text-primary); }
.pagination a.active {
    background-color: var(--primary);
    color: white;
    border-color: var(--primary);
    font-weight: 600;
}

/* User Cell in Table */
.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}
.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 1rem;
    flex-shrink: 0;
    text-transform: uppercase;
}
.user-cell-info { display: flex; flex-direction: column; }
.user-cell-info strong { font-weight: 600; color: var(--text-primary); font-size: 0.95rem; }
.user-cell-info small { color: var(--text-secondary); font-size: 0.85rem; }

/* Date Cell in Table */
.date-cell {
    display: flex;
    flex-direction: column;
    line-height: 1.3;
}
.date-cell span {
    font-weight: 500;
}
.date-cell small {
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Bulk Actions */
.bulk-actions {
    background: #f8fafc;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    padding: 1rem 1.5rem;
    margin: 1.5rem 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: var(--shadow-sm);
}
.bulk-info { font-weight: 600; color: var(--text-secondary); }
.bulk-controls { display: flex; gap: 0.75rem; align-items: center; }
.bulk-controls select { padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); font-size: 0.875rem; }

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
    background-color: rgba(30, 41, 59, 0.5); /* sidebar-bg with opacity */
    animation: fadeIn 0.3s;
}
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
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
.close-btn {
    position: absolute;
    top: 1rem;
    right: 1rem;
    font-size: 1.5rem;
    color: #9ca3af;
    cursor: pointer;
    line-height: 1;
}
.close-btn:hover { color: var(--text-primary); }
.modal-content h3 {
    margin-top: 0;
    margin-bottom: 1.5rem;
    font-size: 1.25rem;
    color: var(--text-primary);
}
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); font-size: 0.875rem; }
.form-group input, .form-group select {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
}
.form-group input:focus, .form-group select:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
    outline: none;
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
.match-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex-grow: 1;
}
.match-info small {
    display: block;
    color: var(--text-secondary);
    font-size: 0.875rem;
    line-height: 1.5;
}
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
.btn-link:hover {
    background: var(--primary);
    color: white;
}

/* Responsive */
@media (max-width: 768px) {
    .main { padding: 1rem; }
    .header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    .header-right { flex-direction: column; align-items: stretch; width: 100%; }
    .filter-controls { flex-direction: column; }
    .bulk-actions { flex-direction: column; gap: 1rem; align-items: stretch; }
    .bulk-controls form { flex-direction: column; align-items: stretch; }
    .modal-content { margin: 5% auto; }
}
</style>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>