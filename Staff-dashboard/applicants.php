<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<?php
$current_page = 'applicants';
require_once './staff_header.php'; // This already handles session, db, and auth
require_once __DIR__ . '/../config_mail.php'; // Keep for mail config
require_once './email_functions.php'; // Keep for mail functions
// Recommended for production environments like InfinityFree
// This will log errors to a file instead of displaying them to users.
ini_set('display_errors', 0);
ini_set('log_errors', 1);

$flash_message = '';
if (isset($_SESSION['flash_message'])) {
    $message_data = $_SESSION['flash_message'];
    $flash_message = '<div class="message ' . htmlspecialchars($message_data['type']) . '">' . htmlspecialchars($message_data['text']) . '</div>';
    unset($_SESSION['flash_message']);
}

if (isset($_POST['update_status'])) {
    error_log("Update status form submitted"); // Add log
    $id = $_POST['id'];
    $status = $_POST['status'];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE applications SET status=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
        error_log("Status updated for application ID: {$id} to status: {$status}"); // Add log
        
        $conn->commit();
        
        // Fetch application and user details for notification (if user is assigned)
        $stmt = $conn->prepare("SELECT a.business_name, u.name, u.email 
                                     FROM applications a
                                     LEFT JOIN users u ON a.user_id = u.id
                                     WHERE a.id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $application_data = $result->fetch_assoc();
        $stmt->close();

        // Only send notification if a user is linked to the application
        if ($application_data && !empty($application_data['email'])) {
            // Send notification email
            $subject = "Application Status Updated";
            $message_body = "Dear " . htmlspecialchars($application_data['name']) . ",\n\n";
            $message_body .= "Your application for " . htmlspecialchars($application_data['business_name']) . " has been updated to: " . htmlspecialchars(ucfirst($status)) . ".\n\n";
            $message_body .= "Please check your account for more details.\n\n";
            $message_body .= "Sincerely,\nOnlineBizPermit Team";

            try {
                sendApplicationEmail($application_data['email'], $application_data['name'], $subject, $message_body);
                $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Status updated and notification email sent!'];
            } catch (Exception $e) {
                $_SESSION['flash_message'] = ['type' => 'warning', 'text' => 'Status updated, but email sending failed: ' . $e->getMessage()];
                error_log("Email sending failed for app ID {$id}: " . $e->getMessage());
            }
        } else {
            // If no user is linked, just confirm the status update
            $_SESSION['flash_message'] = ['type' => 'success', 'text' => 'Status updated successfully.'];
            if ($application_data) {
                error_log("Skipping notification for app ID {$id} because no user/email is associated.");
            }
        }

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['flash_message'] = ['type' => 'danger', 'text' => 'Failed to update status. Please try again.'];
        error_log("Failed to update status for application ID {$id}: " . $e->getMessage());
    }

    header("Location: applicants.php");
    exit;
}

$filter = $_GET['filter'] ?? '';
$search_term   = trim($_GET['search'] ?? '');
$where_clauses =  [];
$params = [];
$types = "";

if ($filter === 'expired') {
    $where_clauses[] = "a.renewal_status = 'expired'";
} elseif ($filter === 'expiring') {
    $where_clauses[] = "a.renewal_status = 'expiring_soon'";
}

if (!empty($search_term)) {
    $where_clauses[] = "(u.name LIKE ? OR u.email LIKE ? OR a.business_name LIKE ?)";
    $like_term = "%{$search_term}%";
    $params = array_fill(0, 3, $like_term);
    $types = "sss";
}

$where_sql = "";
if(!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// --- Pagination ---
$limit = 15; // Applications per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total number of applications for pagination
$count_sql = "SELECT COUNT(a.id) FROM applications a LEFT JOIN users u ON a.user_id = u.id" . $where_sql;
$stmt_count = $conn->prepare($count_sql);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_applications = $stmt_count->get_result()->fetch_row()[0];
$total_pages = ceil($total_applications / $limit);
$stmt_count->close();

$sql = "SELECT a.id, a.business_name, a.status, a.renewal_date, a.renewal_status, a.permit_released_at, u.name, u.email, a.submitted_at
        FROM applications a 
        LEFT JOIN users u ON a.user_id = u.id"
       . $where_sql . "
        ORDER BY a.submitted_at DESC
        LIMIT ? OFFSET ?";
$params[] = $limit; $params[] = $offset; $types .= "ii";
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
require_once './staff_sidebar.php';
?>
<style>
    /* --- Enhanced Applicant Management Styles --- */
    /* Renewal Info Styles */
    .renewal-info { display: flex; flex-direction: column; gap: 4px; }
    .renewal-date { font-weight: 600; font-size: 14px; }
    .renewal-date.expired { color: #dc3545; }
    .renewal-date.expiring-soon { color: #ffc107; }
    .renewal-date.active { color: #28a745; }

    .renewal-status-text { font-size: 11px; font-weight: 500; padding: 2px 6px; border-radius: 12px; text-align: center; display: inline-block; }
    .renewal-status-text.expired { background: #f8d7da; color: #721c24; }
    .renewal-status-text.expiring-soon { background: #fff3cd; color: #856404; }
    .renewal-status-text.active { background: #d4edda; color: #155724; }

    .no-renewal { color: var(--text-secondary-color); font-style: italic; }
    
    .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
    .header h1 { font-size: 1.75rem; font-weight: 700; color: var(--text-primary); margin: 0; }
    
    .page-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; gap: 1rem; flex-wrap: wrap; }
    .filter-buttons { display: flex; gap: 0.5rem; }
    .btn-filter { padding: 0.5rem 1rem; border-radius: var(--border-radius); text-decoration: none; font-weight: 600; background-color: var(--card-bg); border: 1px solid var(--border-color); color: var(--text-secondary); transition: all 0.2s ease; }
    .btn-filter.active, .btn-filter:hover { background-color: var(--primary-color); color: white; border-color: var(--primary-color); }
    
    .search-form { display: flex; }
    .search-form input { padding: 0.5rem 1rem; border: 1px solid var(--border-color); border-radius: var(--border-radius) 0 0 var(--border-radius); border-right: none; }
    .search-form button { padding: 0.5rem 1rem; border: 1px solid var(--primary-color); background-color: var(--primary-color); color: white; border-radius: 0 var(--border-radius) var(--border-radius) 0; cursor: pointer; }

    .table-container { background: var(--card-bg); border-radius: var(--border-radius); box-shadow: var(--shadow-sm); border: 1px solid var(--border-color); overflow-x: auto; }
    .applicants-table { width: 100%; border-collapse: collapse; }
    .applicants-table th, .applicants-table td { padding: 0.85rem 1.25rem; text-align: left; border-bottom: 1px solid var(--border-color); vertical-align: middle; }
    .applicants-table thead th { font-weight: 600; color: var(--text-secondary); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; background-color: #f8f9fa; }
    .applicants-table tbody tr:last-child td { border-bottom: none; }
    .applicants-table tbody tr:hover { background-color: #f8fafc; }

    .user-cell { display: flex; flex-direction: column; }
    .user-cell strong { font-weight: 600; color: var(--text-primary); }
    .user-cell small { color: var(--text-secondary); font-size: 0.85rem; }

    .status-badge { padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .status-pending, .status-for-review { background-color: #fef3c7; color: #92400e; }
    .status-approved, .status-complete { background-color: #d1fae5; color: #065f46; }
    .status-rejected { background-color: #fee2e2; color: #991b1b; }

    .action-buttons { display: flex; gap: 0.5rem; justify-content: flex-end; }
    .btn-action { background: none; border: 1px solid var(--border-color); color: var(--text-secondary); width: 36px; height: 36px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s ease; }
    .btn-action:hover { background: var(--primary-color); color: #fff; border-color: var(--primary-color); }

    .pagination { display: flex; justify-content: center; margin-top: 1.5rem; gap: 0.25rem; }
    .pagination a { display: inline-block; padding: 0.5rem 1rem; text-decoration: none; color: var(--text-secondary); background-color: white; border: 1px solid var(--border-color); border-radius: 0.375rem; transition: all 0.2s ease; }
    .pagination a:hover { background-color: #f1f5f9; color: var(--text-primary); }
    .pagination a.active { background-color: var(--primary-color); color: white; border-color: var(--primary-color); font-weight: 600; }

    /* Responsive data labels */
    @media (max-width: 768px) {
        td[data-label="Renewal Info"] .renewal-info {
            align-items: flex-end;
        }
        .applicants-table thead { display: none; }
        .applicants-table, .applicants-table tbody, .applicants-table tr, .applicants-table td { display: block; width: 100%; }
        .applicants-table tr { margin-bottom: 1rem; border: 1px solid var(--border-color); border-radius: var(--border-radius); }
        .applicants-table td { text-align: right; padding-left: 50%; position: relative; border-bottom: 1px solid var(--border-color); }
        .applicants-table td:last-child { border-bottom: none; }
        .applicants-table td::before { content: attr(data-label); position: absolute; left: 1.25rem; font-weight: 600; color: var(--text-secondary); text-align: left; }
    }
</style>
<!-- Main Content -->
    <div class="main">
      <header class="header">
        <h1>Application Management</h1>
      </header>
      <?php echo $flash_message; ?>
      <div class="page-controls">
        <div class="filter-buttons">
          <a href="applicants.php" class="btn-filter <?= $filter === '' ? 'active' : '' ?>">All</a>
          <a href="applicants.php?filter=expiring" class="btn-filter <?= $filter === 'expiring' ? 'active' : '' ?>">Expiring Soon</a>
          <a href="applicants.php?filter=expired" class="btn-filter <?= $filter === 'expired' ? 'active' : '' ?>">Expired</a>
        </div>
        <form action="applicants.php" method="GET" class="search-form">
          <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search_term) ?>">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
          <button type="submit"><i class="fas fa-search"></i></button>
        </form>
      </div>
      <div class="table-container">
        <table class="applicants-table">
          <thead>
            <tr><th>Applicant</th><th>Business Name</th><th>Renewal Info</th><th>Status</th><th>Update Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows === 0): ?>
              <tr><td colspan="6" style="text-align:center; padding: 40px;">No applications found.</td></tr>
            <?php else: ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td data-label="Applicant">
                    <div class="user-cell">
                        <strong><?= htmlspecialchars($row['name'] ?? 'N/A') ?></strong>
                        <small><?= htmlspecialchars($row['email'] ?? 'N/A') ?></small>
                    </div>
                  </td>
                  <td data-label="Business Name"><?= htmlspecialchars($row['business_name'] ?? 'N/A') ?></td>
                  <td data-label="Renewal Info">
                    <?php if ($row['renewal_date'] && in_array($row['status'], ['approved', 'complete'])): ?>
                        <?php
                        $renewal_date = new DateTime($row['renewal_date']);
                        $today = new DateTime();
                        $interval = $today->diff($renewal_date);
                        $days_until_renewal = $interval->days;
                        $is_expired = $renewal_date < $today;
                        $is_expiring_soon = !$is_expired && $days_until_renewal <= 30;
                        ?>
                        <div class="renewal-info">
                            <span class="renewal-date <?= $is_expired ? 'expired' : ($is_expiring_soon ? 'expiring-soon' : 'active') ?>">
                                <?= $renewal_date->format('M d, Y') ?>
                            </span>
                            <?php if ($is_expired): ?>
                                <small class="renewal-status-text expired">Expired <?= $interval->format('%a days ago') ?></small>
                            <?php elseif ($is_expiring_soon): ?>
                                <small class="renewal-status-text expiring-soon">Expires in <?= $days_until_renewal ?> days</small>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <span class="no-renewal">N/A</span>
                    <?php endif; ?>
                  </td>
                  <td data-label="Status"><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $row['status'])) ?>"><?= htmlspecialchars(ucfirst($row['status'])) ?></span></td>
                  <td data-label="Update Status" style="min-width: 220px;">
                    <form action="applicants.php" method="POST" class="status-update-form">
                        <input type="hidden" name="id" value="<?= $row['id'] ?>">
                        <select name="status">
                            <option value="pending" <?= $row['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="for review" <?= $row['status'] === 'for review' ? 'selected' : '' ?>>For Review</option>
                            <option value="approved" <?= $row['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="rejected" <?= $row['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                            <option value="complete" <?= $row['status'] === 'complete' ? 'selected' : '' ?>>Complete</option>
                        </select>
                        <button type="submit" name="update_status" class="btn-update-status">
                            <i class="fas fa-sync-alt"></i> Update
                        </button>
                    </form>
                  </td>
                  <td data-label="Actions">
                    <div class="action-buttons">
                      <a href="view_application.php?id=<?= $row['id'] ?>" class="btn-action" title="View"><i class="fas fa-eye"></i></a>
                      <a href="notify.php?application_id=<?= $row['id'] ?>" class="btn-action" title="Send Notification"><i class="fas fa-envelope"></i></a>

                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php
            $query_params = $_GET;
            if ($page > 1) {
                $query_params['page'] = $page - 1;
                echo '<a href="?' . http_build_query($query_params) . '">&laquo; Prev</a>';
            }
            for ($i = 1; $i <= $total_pages; $i++) {
                $query_params['page'] = $i;
                echo '<a href="?' . http_build_query($query_params) . '" class="' . (($i == $page) ? 'active' : '') . '">' . $i . '</a>';
            }
            if ($page < $total_pages) {
                $query_params['page'] = $page + 1;
                echo '<a href="?' . http_build_query($query_params) . '">Next &raquo;</a>';
            }
            ?>
        </div>
        <?php endif; ?>
    </div>

<style>
/* Styles for the new status update form in the table */
.status-update-form {
    display: flex;
    align-items: center;
    gap: 8px;
}
.status-update-form select {
    padding: 8px;
    border: 1px solid var(--border-color);
    border-radius: 6px;
    font-size: 0.85rem;
}
.btn-update-status {
    background: var(--secondary-color);
    color: white;
    border: none;
    padding: 8px 12px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    transition: background-color 0.2s ease;
}
.btn-update-status:hover { background: var(--primary-color); }
</style>

<?php require_once './staff_footer.php'; ?>
<!--
[PROMPT_SUGGESTION]Can you add a confirmation dialog before updating the status?[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Implement an edit functionality for each applicant's details.[/PROMPT_SUGGESTION]