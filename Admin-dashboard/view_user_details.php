<?php
// Page-specific variables
$page_title = 'User Details';
$current_page = 'pending_users';

// Include Header
require_once __DIR__ . '/admin_header.php';

$user = null;
$applications = [];

if (isset($_GET['id'])) {
    $userId = (int)$_GET['id'];
    
    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'user'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
    }
    $stmt->close();
    
    // Fetch user's application history
    if ($user) {
        $stmt = $conn->prepare("SELECT * FROM applications WHERE user_id = ? ORDER BY submitted_at DESC");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

if (!$user) {
    header("Location: pending_users.php");
    exit;
}

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
    <header class="header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <a href="pending_users.php" class="btn" style="padding: 8px 12px;"><i class="fas fa-arrow-left"></i> Back</a>
            <h1>User Details & History</h1>
        </div>
        <div class="header-actions">
            <?php if ($user['is_approved'] == 0): ?>
                <a href="pending_users.php?action=approve&id=<?= $user['id'] ?>" 
                   class="btn btn-success"
                   onclick="return confirm('Approve this user? They will be able to access the applicant dashboard.')">
                    <i class="fas fa-check"></i> Approve User
                </a>
                <a href="pending_users.php?action=reject&id=<?= $user['id'] ?>" 
                   class="btn btn-danger"
                   onclick="return confirm('Reject this user? They will not be able to access the system.')">
                    <i class="fas fa-times"></i> Reject User
                </a>
            <?php else: ?>
                <span class="status-badge status-<?= $user['is_approved'] == 1 ? 'active' : 'rejected' ?>">
                    <?= $user['is_approved'] == 1 ? 'Approved' : 'Rejected' ?>
                </span>
            <?php endif; ?>
        </div>
    </header>

    <div class="user-details-container">
        <!-- User Information Card -->
        <div class="info-card">
            <div class="card-header">
                <h3><i class="fas fa-user"></i> User Information</h3>
            </div>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <span><?= htmlspecialchars($user['name']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email Address</label>
                        <span><?= htmlspecialchars($user['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone Number</label>
                        <span><?= htmlspecialchars($user['phone'] ?: 'Not provided') ?></span>
                    </div>
                    <div class="info-item">
                        <label>User ID</label>
                        <span>#<?= $user['id'] ?></span>
                    </div>
                    <div class="info-item">
                        <label>Registration Date</label>
                        <span><?= date('F d, Y \a\t H:i', strtotime($user['created_at'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Account Status</label>
                        <?php 
                        $status = $user['is_approved'] == 1 ? 'approved' : ($user['is_approved'] == 2 ? 'rejected' : 'pending');
                        $statusText = $user['is_approved'] == 1 ? 'Approved' : ($user['is_approved'] == 2 ? 'Rejected' : 'Pending');
                        ?>
                        <span class="status-badge status-<?= $status ?>">
                            <?= $statusText ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Application History Card -->
        <div class="info-card">
            <div class="card-header">
                <h3><i class="fas fa-file-alt"></i> Application History</h3>
                <span class="count-badge"><?= count($applications) ?> application<?= count($applications) !== 1 ? 's' : '' ?></span>
            </div>
            <div class="card-body">
                <?php if (empty($applications)): ?>
                    <div class="empty-state">
                        <i class="fas fa-file-alt"></i>
                        <h4>No Applications Found</h4>
                        <p>This user has not submitted any business permit applications yet.</p>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $app): ?>
                            <div class="application-item">
                                <div class="app-header">
                                    <div class="app-title">
                                        <h4><?= htmlspecialchars($app['business_name']) ?></h4>
                                        <span class="app-id">#<?= $app['id'] ?></span>
                                    </div>
                                    <div class="app-meta">
                                        <span class="status-badge status-<?= $app['status'] ?>">
                                            <?= ucfirst($app['status']) ?>
                                        </span>
                                        <span class="app-date">
                                            <?= date('M d, Y', strtotime($app['submitted_at'])) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="app-details">
                                    <div class="detail-row">
                                        <label>Business Address:</label>
                                        <span><?= htmlspecialchars($app['business_address']) ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <label>Type of Business:</label>
                                        <span><?= htmlspecialchars($app['type_of_business']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Professional Admin Design System */
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
    --text-secondary: #475569;
    --text-muted: #6c757d;
    --primary: #4a69bd;
    --primary-hover: #3e5aa2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --border-radius: 0.75rem;
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

.main { margin-left: 80px; transition: margin-left 0.3s ease; }

.user-details-container {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

.info-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
}

.card-header {
    background: var(--primary);
    color: white;
    padding: 16px 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.count-badge {
    background: rgba(255,255,255,0.2);
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.card-body {
    padding: 24px;
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.info-item {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.info-item label {
    font-weight: 600;
    color: var(--text-muted);
    font-size: 0.9rem;
}

.info-item span {
    color: var(--text-primary);
    font-size: 1rem;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-pending {
    background: #fff3cd;
    color: #856404;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-rejected {
    background: #f8d7da;
    color: #721c24;
}

.status-approved {
    background: #d1ecf1;
    color: #0c5460;
}

.status-complete {
    background: #d4edda;
    color: #155724;
}

.applications-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.application-item {
    border: 1px solid var(--border-color);
    border-radius: 8px;
    padding: 20px;
    background: #f8f9fa;
}

.app-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.app-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.app-title h4 {
    margin: 0;
    color: var(--text-primary);
}

.app-id {
    background: var(--primary);
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 600;
}

.app-meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 8px;
}

.app-date {
    font-size: 0.9rem;
    color: var(--text-muted);
}

.app-details {
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    gap: 12px;
    margin-bottom: 8px;
}

.detail-row label {
    font-weight: 600;
    color: var(--text-muted);
    min-width: 140px;
}

.detail-row span {
    color: var(--text-primary);
}

.app-actions {
    display: flex;
    gap: 8px;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 0.85rem;
    border-radius: 4px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    border: none;
    cursor: pointer;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-sm:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 16px;
    color: var(--text-muted);
}

.empty-state h4 {
    margin: 0 0 8px 0;
    color: var(--text-primary);
}

.empty-state p {
    margin: 0;
}

.header-actions {
    display: flex;
    gap: 12px;
    align-items: center;
}

.btn {
    padding: 10px 16px;
    border-radius: 6px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-weight: 600;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
    }
    
    .app-header {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
    }
    
    .app-meta {
        align-items: flex-start;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 8px;
    }
}
</style>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>
