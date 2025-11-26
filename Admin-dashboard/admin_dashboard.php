<?php
// Page-specific variables
$page_title = 'admin-Dashboard';
$current_page = 'admin_dashboard'; // This is used by the sidebar to set the active state

// Include Header
require_once __DIR__ . '/admin_header.php'; // Contains session start, DB connection, and auth checks
require_once __DIR__ . '/functions.php';

$message = '';

/**
 * Adds a new staff user to the database.
 *
 * @param mysqli $conn The database connection object.
 * @param string $name The full name of the staff member.
 * @param string $email The email address of the staff member.
 * @param string $password The plain-text password for the new account.
 * @return string An HTML message indicating success or failure.
 */
// --- Handle Add Staff ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_staff'])) {
    // This logic is now more robust and centralized in user_management.php,
    // but we can keep a simplified version here for the modal.
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $message = '<div class="message error">All fields are required to add a staff member.</div>';
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
            $insertStmt = $conn->prepare("INSERT INTO users (name, email, password, role, is_approved) VALUES (?, ?, ?, 'staff', 1)");
            $insertStmt->bind_param("sss", $name, $email, $hashedPassword);
            if ($insertStmt->execute()) {
                $message = '<div class="message success">Staff member added successfully.</div>';
            } else {
                $message = '<div class="message error">Failed to add staff member.</div>';
            }
        }
    }
}

// --- Fetch Recent Activity ---
$recent_users_sql = "SELECT id, name, email, role, created_at
                    FROM users
                    ORDER BY created_at DESC
                    LIMIT 7";
$recent_users_result = $conn->query($recent_users_sql);
$recent_users = $recent_users_result ? $recent_users_result->fetch_all(MYSQLI_ASSOC) : [];

// --- Fetch All Dashboard Stats in a Single, Optimized Query ---
$user_kpi_sql = "SELECT
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'staff') as staff_count,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_approved = 0) as pending_users,
    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as new_users_this_month
    FROM DUAL";
$user_kpi_result = $conn->query($user_kpi_sql);
$user_kpis = $user_kpi_result ? $user_kpi_result->fetch_assoc() : [];

// --- Monthly User Registrations for Bar Chart ---
// Initialize an array for the last 12 months with 0 counts to ensure a complete dataset for the chart.
$monthlyData = [];
for ($i = 11; $i >= 0; $i--) {
    $date = new DateTime("first day of -$i month");
    $monthKey = $date->format('M Y');
    $monthlyData[$monthKey] = 0;
}
// Fetch actual user registration counts from the database for the last 12 months
$twelveMonthsAgo = new DateTime('-11 months');
$startDate = $twelveMonthsAgo->format('Y-m-01 00:00:00');

$res = $conn->query("SELECT DATE_FORMAT(created_at, '%b %Y') AS month, COUNT(*) AS total
                     FROM users
                     WHERE created_at >= '{$startDate}'
                     GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                     ORDER BY created_at ASC");

if ($res) {
    while ($row = $res->fetch_assoc()) {
        if (isset($monthlyData[$row['month']])) {
            $monthlyData[$row['month']] = (int)$row['total'];
        }
    }
}
$months = json_encode(array_keys($monthlyData));
$values = json_encode(array_values($monthlyData));

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

    <!-- Main -->
    <div class="main">
      <header class="header">
        <div class="header-left">
            <button id="hamburger"><i class="fas fa-bars"></i></button>
            <h1>Dashboard</h1>
        </div>
        <div class="header-actions">
          <?php if ($current_user_role === 'admin'): ?>
            <button id="addStaffBtn" class="btn btn-primary"><i class="fas fa-plus"></i> Add Staff</button>
          <?php endif; ?>
          <div class="user-profile">
            <div class="user-avatar" style="background-color: #<?= substr(md5($current_user_name), 0, 6) ?>;">
                <span><?= strtoupper(substr($current_user_name, 0, 1)) ?></span>
            </div>
            <span><?= htmlspecialchars($current_user_name) ?></span>
            <div class="dropdown-menu">
              <a href="settings.php"><i class="fas fa-cog"></i> Profile</a>
              <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
          </div>
        </div>
      </header>

      <?php if ($message) echo $message; ?>

      <!-- Statistics Grid -->
      <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-icon icon-total-apps"><i class="fas fa-users"></i></div>
            <div class="stat-info">
                <p>Total Users</p>
                <span><?= $user_kpis['total_users'] ?? 0 ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-approved-apps"><i class="fas fa-user-plus"></i></div>
            <div class="stat-info">
                <p>New Users (This Month)</p>
                <span><?= $user_kpis['new_users_this_month'] ?? 0 ?></span>
            </div>
        </div>
        <a href="pending_users.php" class="stat-card interactive">
            <div class="stat-icon icon-pending-users"><i class="fas fa-user-clock"></i></div>
            <div class="stat-info">
                <p>Pending Users</p>
                <span><?= $user_kpis['pending_users'] ?? 0 ?></span>
            </div>
            <i class="fas fa-arrow-right card-arrow"></i>
        </a>
        <div class="stat-card">
            <div class="stat-icon" style="background-color: #64748b;"><i class="fas fa-user-shield"></i></div>
            <div class="stat-info">
                <p>Staff Accounts</p>
                <span><?= $user_kpis['staff_count'] ?? 0 ?></span>
            </div>
        </div>
      </div>

      <!-- Main Data Grid -->
      <div class="data-grid">
        <div class="chart-box">
          <h3>Monthly User Registrations (Last 12 Months)</h3>
          <div class="chart-container">
            <canvas id="barChart"></canvas>
          </div>
        </div>
        <div class="chart-box">
          <h3>Recent Activity</h3>
            <div class="activity-container">
                <ul id="activity-list" class="activity-list">
                    <?php if (empty($recent_users)): ?>
                        <li class="activity-item empty">
                            <i class="fas fa-moon activity-icon"></i>
                            <div class="activity-description">No recent user registrations.</div>
                        </li>
                    <?php else: ?>
                        <?php foreach ($recent_users as $user): ?>
                            <li class="activity-item" data-id="user-<?= $user['id'] ?>">
                                <i class="fas fa-user-plus activity-icon icon-user"></i>
                                <div class="activity-description">
                                    <strong><?= htmlspecialchars($user['name']) ?></strong>
                                    <small><?= htmlspecialchars($user['email']) ?></small>
                                </div>
                                <div class="activity-time"><?= time_ago($user['created_at']) ?></div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
      </div>
    </div>

    <!-- Add Staff Modal -->
    <div id="addStaffModal" class="modal">
        <div class="modal-content">
            <span class="close-btn">&times;</span>
            <h3>Add New Staff</h3>
            <form action="dashboard.php" method="POST">
                <div class="form-group"><label for="name">Full Name</label><input type="text" id="name" name="name" required></div>
                <div class="form-group"><label for="email">Email</label><input type="email" id="email" name="email" required></div>
                <div class="form-group"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
                <button type="submit" name="add_staff" class="btn">Add Staff</button>
            </form>
        </div>
    </div>
</div>

  <script>
    // --- Modal Logic ---
    const addStaffModal = document.getElementById('addStaffModal');
    const addStaffBtn = document.getElementById('addStaffBtn');
    if (addStaffModal && addStaffBtn) {
        const closeBtn = addStaffModal.querySelector('.close-btn');
        addStaffBtn.onclick = () => addStaffModal.style.display = 'block';
        if (closeBtn) closeBtn.onclick = () => modal.style.display = 'none';
        window.addEventListener('click', (event) => {
            if (event.target === addStaffModal) addStaffModal.style.display = 'none';
        });
    }

    // --- Charts ---
    Chart.defaults.color = 'rgba(255, 255, 255, 0.7)';
    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.2)';

    document.addEventListener('DOMContentLoaded', function() {
        // Bar Chart for Monthly Applications
        const barChartCanvas = document.getElementById('barChart');
        if (barChartCanvas && <?= array_sum(array_values($monthlyData)) ?> > 0) {
            new Chart(barChartCanvas, {
                type: 'bar',
                data: {
                    labels: <?= $months ?>,
                    datasets: [{
                        label: 'New Users',
                        data: <?= $values ?>,
                        backgroundColor: 'rgba(74, 105, 189, 0.7)',
                        borderColor: 'rgba(74, 105, 189, 1)',
                        borderWidth: 1,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                }
            });
        }
    });

    // --- Real-time Activity Log ---

    // Helper to prevent XSS when inserting dynamic text
    function escapeHTML(str) {
        const p = document.createElement('p');
        p.textContent = str;
        return p.innerHTML;
    }

    // JS equivalent of the PHP time_ago function
    function time_ago(datetime) {
        const now = new Date();
        const ago = new Date(datetime);
        const diff_s = Math.floor((now - ago) / 1000);

        if (diff_s < 60) return 'just now';
        const diff_m = Math.floor(diff_s / 60);
        if (diff_m < 60) return `${diff_m} minute${diff_m > 1 ? 's' : ''} ago`;
        const diff_h = Math.floor(diff_m / 60);
        if (diff_h < 24) return `${diff_h} hour${diff_h > 1 ? 's' : ''} ago`;
        const diff_d = Math.floor(diff_h / 24);
        if (diff_d < 7) return `${diff_d} day${diff_d > 1 ? 's' : ''} ago`;
        const diff_w = Math.floor(diff_d / 7);
        if (diff_w < 4) return `${diff_w} week${diff_w > 1 ? 's' : ''} ago`;
        const diff_months = Math.floor(diff_d / 30.44);
        if (diff_months < 12) return `${diff_months} month${diff_months > 1 ? 's' : ''} ago`;
        const diff_y = Math.floor(diff_d / 365.25);
        return `${diff_y} year${diff_y > 1 ? 's' : ''} ago`;
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
    --text-secondary: #475569;
    --primary: #4a69bd;
    --primary-hover: #3e5aa2;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #3b82f6;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
    --border-radius: 0.75rem; /* 12px */
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

/* Main content adjustments for fixed sidebar */
.main { margin-left: 80px; transition: margin-left 0.3s ease; padding: 1.5rem; }

/* Header Enhancements */
.header-actions { display: flex; align-items: center; gap: 1rem; }
.user-profile {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background-color: var(--card-bg);
    padding: 0.5rem;
    border-radius: 9999px;
    cursor: pointer;
    position: relative;
    border: 1px solid var(--border-color);
}
.user-avatar {
    width: 32px; height: 32px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white; font-weight: 600;
}
.user-profile span { font-weight: 600; color: var(--text-primary); }
.dropdown-menu {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    right: 0;
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--border-color);
    width: 180px;
    z-index: 100;
    overflow: hidden;
}
.dropdown-menu.show { display: block; }
.dropdown-menu a {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.75rem 1rem;
    text-decoration: none;
    color: var(--text-secondary);
    font-weight: 500;
    transition: background-color 0.2s ease;
}
.dropdown-menu a:hover { background-color: var(--admin-bg); }
.dropdown-menu a.logout-link { color: var(--danger); }

/* Alerts Container */
.alerts-container { margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem; }
.alert {
    padding: 1rem 1.25rem;
    border-radius: var(--border-radius);
    display: flex; align-items: center; gap: 0.75rem;
    font-weight: 500; border: 1px solid;
}
.alert-danger { background-color: #fee2e2; color: #991b1b; border-color: #fecaca; }
.alert-warning { background-color: #fef3c7; color: #92400e; border-color: #fde68a; }
.alert-link { color: inherit; text-decoration: underline; font-weight: 600; margin-left: auto; }
.alert-link:hover { text-decoration: none; }

/* Statistics Grid */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}
.stat-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    transition: all 0.2s ease-in-out;
    text-decoration: none;
    color: inherit;
}
.stat-card.interactive:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
}
.stat-icon {
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem; color: white;
    flex-shrink: 0;
}
.icon-total-apps { background-color: var(--info); }
.icon-approved-apps { background-color: var(--success); }
.icon-pending-apps { background-color: var(--warning); }
.icon-pending-users { background-color: var(--danger); }
.stat-info p { margin: 0; color: var(--text-secondary); font-weight: 600; }
.stat-info span { font-size: 2rem; font-weight: 700; color: var(--text-primary); }
.card-arrow { margin-left: auto; color: var(--border-color); transition: color 0.2s ease; }
.stat-card.interactive:hover .card-arrow { color: var(--primary); }

/* Main Data Grid */
.data-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 1.5rem;
}
.chart-box {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    border: 1px solid var(--border-color);
    box-shadow: var(--shadow-sm);
    display: flex;
    flex-direction: column;
    min-height: 400px;
}
.chart-box h3 {
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    color: var(--text-primary);
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
}
.chart-container { position: relative; flex-grow: 1; }

/* Activity Log */
.activity-container { flex-grow: 1; overflow-y: auto; position: relative; }
.activity-list { list-style: none; padding: 0; margin: 0; }
.activity-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 0.25rem;
    border-bottom: 1px solid var(--border-color);
}
.activity-item:last-child { border-bottom: none; }
.activity-icon {
    width: 36px; height: 36px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: white;
}
.icon-user { background-color: var(--info); }
.icon-application { background-color: var(--success); }
.activity-description { flex-grow: 1; color: var(--text-secondary); }
.activity-description strong { display: block; color: var(--text-primary); font-weight: 600; }
.activity-description small {
    font-size: 0.85rem;
    color: var(--text-secondary);
}
.activity-description strong { color: var(--text-primary); font-weight: 600; }
.activity-time { font-size: 0.875rem; color: var(--text-secondary); flex-shrink: 0; }
.activity-item.empty { justify-content: center; padding: 2rem; color: var(--text-secondary); }
.activity-item.empty .activity-icon { background-color: #e2e8f0; color: #94a3b8; }

/* Modal Styles */
.modal { display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(30, 41, 59, 0.5); animation: fadeIn 0.3s; }
@keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
.modal-content { background-color: #fefefe; margin: 10% auto; padding: 2rem; width: 90%; max-width: 500px; border-radius: var(--border-radius); box-shadow: var(--shadow-lg); position: relative; }
.close-btn { position: absolute; top: 1rem; right: 1rem; font-size: 1.5rem; color: #9ca3af; cursor: pointer; line-height: 1; }
.close-btn:hover { color: var(--text-primary); }
.modal-content h3 { margin-top: 0; margin-bottom: 1.5rem; font-size: 1.25rem; color: var(--text-primary); }
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--text-secondary); font-size: 0.875rem; }
.form-group input {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: var(--border-radius);
    font-size: 1rem;
}
.form-group input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(74, 105, 189, 0.2);
    outline: none;
}
.modal-content .btn { width: 100%; justify-content: center; padding: 0.75rem; }
</style>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>
