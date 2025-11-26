<?php
// Page-specific variables
$page_title = 'Reports';
$current_page = 'reports';

// Include Header
require_once __DIR__ . '/admin_header.php';

// This page should be accessible only by admins
if ($current_user_role !== 'admin') {
    echo "<div class='main'><div class='message error'>You do not have permission to access this page.</div></div>";
    require_once __DIR__ . '/admin_footer.php';
    exit;
}

// --- User KPI Counts ---
$user_kpi_sql = "SELECT
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'staff') as staff_count,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_approved = 0) as pending_users,
    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as new_users_this_month
    FROM DUAL";
$user_kpi_result = $conn->query($user_kpi_sql);
$user_kpis = $user_kpi_result ? $user_kpi_result->fetch_assoc() : [];

// --- Data for Monthly Trend Chart (Last 12 Months) ---
$monthly_labels = [];
$monthly_counts = [];
$counts_by_month = [];
for ($i = 11; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i month"));
    $monthly_labels[] = date('M Y', strtotime($month_key));
    $counts_by_month[$month_key] = 0;
}

$monthly_sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(id) AS count
                FROM users
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY month
                ORDER BY month ASC";
$monthly_result = $conn->query($monthly_sql);
if ($monthly_result) {
    while ($row = $monthly_result->fetch_assoc()) {
        if (isset($counts_by_month[$row['month']])) {
            $counts_by_month[$row['month']] = (int)$row['count'];
        }
    }
}
$monthly_counts = array_values($counts_by_month);

// --- Data for User Role Distribution Doughnut Chart ---
$role_distribution_sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$role_result = $conn->query($role_distribution_sql);
$role_labels = [];
$role_counts = [];
$role_colors = [
    'admin' => '#9333ea',
    'staff' => '#2563eb',
    'user' => '#475569',
    'default' => '#9ca3af'
];
$doughnut_bg_colors = [];
if ($role_result) {
    while ($row = $role_result->fetch_assoc()) {
        $role_labels[] = ($row['role'] === 'user') ? 'Applicants' : ucfirst($row['role']);
        $role_counts[] = $row['count'];
        $doughnut_bg_colors[] = $role_colors[strtolower($row['role'])] ?? $role_colors['default'];
    }
}

// --- Fetch Recent User Registrations for Table ---
$recent_users_sql = "SELECT id, name, email, role, created_at
                    FROM users
                    ORDER BY created_at DESC
                    LIMIT 7";
$recent_users_result = $conn->query($recent_users_sql);
$recent_users = $recent_users_result ? $recent_users_result->fetch_all(MYSQLI_ASSOC) : [];

// Include Sidebar
require_once __DIR__ . '/admin_sidebar.php';
?>

<!-- Main Content -->
<div class="main">
  <header class="header">
    <h1>Reports</h1>
    <div class="header-actions">
        <a href="generate_reports.php" class="btn btn-primary">
            <i class="fas fa-file-csv"></i> Export as CSV
        </a>
    </div>
  </header>
  
  <div class="kpi-grid">
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div class="stat-info">
        <p>Total Users</p>
        <span><?= $user_kpis['total_users'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
      <div class="stat-info">
        <p>New Users (This Month)</p>
        <span><?= $user_kpis['new_users_this_month'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-clock"></i></div>
      <div class="stat-info">
        <p>Pending Users</p>
        <span><?= $user_kpis['pending_users'] ?? 0 ?></span>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon"><i class="fas fa-user-shield"></i></div>
      <div class="stat-info">
        <p>Staff Accounts</p>
        <span><?= $user_kpis['staff_count'] ?? 0 ?></span>
      </div>
    </div>
  </div>

  <!-- Charts -->
  <div class="data-grid">
    <div class="chart-box">
      <h3>Monthly User Registrations</h3>
      <div class="chart-container">
        <canvas id="monthlyTrendChart"></canvas>
      </div>
    </div>
    <div class="chart-box">
      <h3>User Role Distribution</h3>
      <div class="chart-container" style="max-height: 300px; margin: auto;">
        <canvas id="roleDoughnutChart"></canvas>
      </div>
    </div>
  </div>

  <!-- Recent User Registrations Table -->
  <div class="table-container">
    <h3>Recent User Registrations</h3>
    <table class="recent-apps-table">
      <thead>
        <tr><th>User ID</th><th>Name</th><th>Email</th><th>Role</th><th>Date Registered</th></tr>
      </thead>
      <tbody>
        <?php if (empty($recent_users)): ?>
          <tr><td colspan="5" style="text-align:center; padding: 20px;">No recent user registrations found.</td></tr>
        <?php else: ?>
          <?php foreach ($recent_users as $user): ?>
            <tr>
              <td>#<?= htmlspecialchars($user['id']) ?></td>
              <td><?= htmlspecialchars($user['name']) ?></td>
              <td><?= htmlspecialchars($user['email']) ?></td>
              <td><span class="role-badge role-<?= strtolower($user['role']) ?>"><?= htmlspecialchars(ucfirst($user['role'])) ?></span></td>
              <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
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
    --text-secondary: #475569;
    --primary: #4a69bd;
    --danger: #ef4444;
    --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
    --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
    --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
.notification-badge { background-color: var(--danger); color: white; border-radius: 10px; padding: 2px 6px; font-size: 11px; font-weight: bold; position: absolute; top: 8px; right: 12px; transition: opacity 0.2s, transform 0.2s; transform: scale(1); }
.sidebar:not(:hover) .notification-badge { transform: scale(0.8) translate(8px, -8px); }

/* Main content adjustments for fixed sidebar */
.main { margin-left: 80px; transition: margin-left 0.3s ease; padding: 1.5rem; }

/* Styles copied from Staff Reports page */
.header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; background: var(--card-bg); padding: 1.5rem; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); }
.header h1 { color: var(--text-primary); font-size: 1.75rem; margin: 0; }
.kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1.5rem; margin-bottom: 1.5rem; }
.stat-card { background: var(--card-bg); border-radius: var(--border-radius); padding: 1.5rem; display: flex; align-items: center; gap: 1.5rem; border: 1px solid var(--border-color); box-shadow: var(--shadow-sm); color: #fff; }
.kpi-grid .stat-card:nth-child(1) { background: linear-gradient(45deg, #2980b9, #3498db); }
.kpi-grid .stat-card:nth-child(2) { background: linear-gradient(45deg, #27ae60, #2ecc71); }
.kpi-grid .stat-card:nth-child(3) { background: linear-gradient(45deg, #f39c12, #f1c40f); }
.kpi-grid .stat-card:nth-child(4) { background: linear-gradient(45deg, #c0392b, #e74c3c); }
.stat-card p { color: rgba(255, 255, 255, 0.8); }
.stat-icon { width: 60px; height: 60px; font-size: 1.75rem; color: #fff; display: flex; align-items: center; justify-content: center; background: rgba(255, 255, 255, 0.15); border-radius: 50%; }
.stat-info span { font-size: 2.5rem; color: #fff; }
.data-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 1.5rem; }
.chart-box { background: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); }
.chart-box h3 { margin: 0 0 1.5rem 0; font-size: 1.125rem; color: var(--text-primary); padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
.chart-container { position: relative; height: 350px; }
.table-container { background: var(--card-bg); padding: 20px; border-radius: var(--border-radius); box-shadow: var(--shadow-sm); margin-top: 1.5rem; }
.table-container h3 { margin: 0 0 1.5rem 0; font-size: 1.125rem; color: var(--text-primary); padding-bottom: 1rem; border-bottom: 1px solid var(--border-color); }
.recent-apps-table { width: 100%; border-collapse: collapse; }
.recent-apps-table th, .recent-apps-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid var(--border-color); }
.recent-apps-table th { font-weight: 600; font-size: 0.85rem; text-transform: uppercase; color: var(--text-secondary); }
.status-badge { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-align: center; display: inline-block; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
.status-approved, .status-complete { background-color: #27ae60; }
.status-pending, .status-review, .status-for-review { background-color: #f39c12; color: #1e293b; }
.status-rejected { background-color: #c0392b; }
.role-badge { padding: 5px 12px; border-radius: 20px; font-weight: 600; font-size: 0.75rem; text-align: center; display: inline-block; color: #fff; text-transform: uppercase; letter-spacing: 0.5px; }
.role-admin { background-color: #9333ea; }
.role-staff { background-color: #2563eb; }
.role-user { background-color: #475569; }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Trend Bar Chart
    const monthlyChartCanvas = document.getElementById('monthlyTrendChart');
    if (monthlyChartCanvas) {
        new Chart(monthlyChartCanvas, {
            type: 'bar',
            data: {
                labels: <?= json_encode($monthly_labels) ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?= json_encode($monthly_counts) ?>,
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
                scales: { 
                    y: { beginAtZero: true, ticks: { precision: 0, color: '#666' } },
                    x: { ticks: { color: '#666' } }
                }
            }
        });
    }

    // Status Distribution Doughnut Chart
    const roleChartCanvas = document.getElementById('roleDoughnutChart');
    if (roleChartCanvas) {
        new Chart(roleChartCanvas, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($role_labels) ?>,
                datasets: [{
                    data: <?= json_encode($role_counts) ?>,
                    backgroundColor: <?= json_encode($doughnut_bg_colors) ?>,
                    borderColor: '#fff',
                    borderWidth: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: { position: 'bottom', labels: { color: '#333' } },
                    title: { display: false }
                }
            }
        });
    }
});
</script>

<?php
// Include Footer
require_once __DIR__ . '/admin_footer.php';
?>