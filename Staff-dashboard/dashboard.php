<?php
$current_page = 'dashboard';
require_once './staff_header.php';
// --- Fetch data for KPIs ---
$kpi_sql = "SELECT
                COUNT(*) as total_applications,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
            FROM applications";
$kpi_result = $conn->query($kpi_sql);
$kpis = $kpi_result ? $kpi_result->fetch_assoc() : [];

// --- Fetch recent applications ---
$recent_apps_sql = "SELECT a.id, a.business_name, u.name as applicant_name, a.status, a.submitted_at
                    FROM applications a
                    JOIN users u ON a.user_id = u.id
                    ORDER BY a.submitted_at DESC
                    LIMIT 5";
$recent_apps_result = $conn->query($recent_apps_sql);
$recent_applications = $recent_apps_result ? $recent_apps_result->fetch_all(MYSQLI_ASSOC) : [];

// --- Fetch data for monthly trend chart ---
$monthly_labels = [];
$monthly_counts = [];
$counts_by_month = [];
for ($i = 5; $i >= 0; $i--) {
    $month_key = date('Y-m', strtotime("-$i month"));
    $monthly_labels[] = date('M Y', strtotime($month_key));
    $counts_by_month[$month_key] = 0;
}

$monthly_sql = "SELECT DATE_FORMAT(submitted_at, '%Y-%m') AS month, COUNT(id) AS count
                FROM applications
                WHERE submitted_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
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

?>
  <style> /* Additional styles for this page */
    :root { /* Keep variables for consistency */
        --primary-color: #4a69bd;
        --secondary-color: #3c4b64;
        --bg-color: #f0f2f5;
        --card-bg-color: #ffffff;
        --text-color: #343a40;
        --text-secondary-color: #6c757d;
        --border-color: #dee2e6;
        --shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        --border-radius: 12px;
    }
    * { margin:0; padding:0; box-sizing:border-box; font-family:'Poppins',sans-serif; }

    /* Main Content */
    .main { flex: 1; padding: 30px; overflow-y: auto; }
    .main-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
    .main-header h1 { font-size: 28px; font-weight: 700; color: var(--secondary-color); }

    /* KPI Cards */
    .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
    .kpi-card { background: var(--card-bg-color); padding: 25px; border-radius: var(--border-radius); box-shadow: var(--shadow); display: flex; align-items: center; gap: 20px; transition: transform 0.2s; }
    .kpi-card:hover { transform: translateY(-5px); }
    .kpi-card .icon { font-size: 2.5rem; padding: 15px; border-radius: 50%; color: #fff; }
    .kpi-card .details h3 { font-size: 2rem; font-weight: 700; }
    .kpi-card .details p { font-size: 0.9rem; color: var(--text-secondary-color); font-weight: 600; text-transform: uppercase; }
    .kpi-card.total .icon { background: #6f42c1; }
    .kpi-card.approved .icon { background: #28a745; }
    .kpi-card.pending .icon { background: #ffc107; }
    .kpi-card.rejected .icon { background: #dc3545; }

    /* Dashboard Content Grid */
    .dashboard-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .main-chart-container, .recent-activity-container {
        background: var(--card-bg-color);
        padding: 20px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        height: 400px; /* Fixed height */
        display: flex;
        flex-direction: column;
    }
    .main-chart-container h2, .recent-activity-container h2 {
        font-size: 1.2rem;
        margin-bottom: 20px;
        font-weight: 600;
        flex-shrink: 0;
    }
    .chart-wrapper {
        position: relative;
        flex-grow: 1;
    }
    .activity-list {
        overflow-y: auto; /* Make the list scrollable if it exceeds the height */
        flex-grow: 1;
    }

    /* Recent Activity */
    .activity-list .activity-item { display: flex; align-items: center; gap: 15px; padding: 12px 0; border-bottom: 1px solid var(--border-color); }
    .activity-list .activity-item:last-child { border-bottom: none; }
    .activity-item .activity-icon { font-size: 1.5rem; color: var(--text-secondary-color); }
    .activity-item .activity-details p { margin: 0; font-weight: 500; }
    .activity-item .activity-details span { font-size: 0.85rem; color: var(--text-secondary-color); }
    .status-badge { padding: 5px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem; text-align: center; }
    .status-approved { background: rgba(40, 167, 69, 0.1); color: #28a745; }
    .status-pending { background: rgba(255, 193, 7, 0.1); color: #d9a400; }
    .status-rejected { background: rgba(220, 53, 69, 0.1); color: #dc3545; }

    @media (max-width: 1200px) { .dashboard-grid { grid-template-columns: 1fr; } }
  </style>

<?php
require_once './staff_sidebar.php';
?>
    <!-- Main Content -->
    <div class="main">
      <div class="main-header">
        <h1>Welcome, <?= htmlspecialchars($userName) ?>!</h1>
      </div>
      
      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card total">
          <div class="icon"><i class="fas fa-folder-open"></i></div>
          <div class="details">
            <h3><?= $kpis['total_applications'] ?? 0 ?></h3>
            <p>Total Applications</p>
          </div>
        </div>
        <div class="kpi-card approved">
          <div class="icon"><i class="fas fa-check-circle"></i></div>
          <div class="details">
            <h3><?= $kpis['approved_count'] ?? 0 ?></h3>
            <p>Approved</p>
          </div>
        </div>
        <div class="kpi-card pending">
          <div class="icon"><i class="fas fa-clock"></i></div>
          <div class="details">
            <h3><?= $kpis['pending_count'] ?? 0 ?></h3>
            <p>Pending</p>
          </div>
        </div>
        <div class="kpi-card rejected">
          <div class="icon"><i class="fas fa-times-circle"></i></div>
          <div class="details">
            <h3><?= $kpis['rejected_count'] ?? 0 ?></h3>
            <p>Rejected</p>
          </div>
        </div>
      </div>

      <!-- Dashboard Content Grid -->
      <div class="dashboard-grid">
        <div class="main-chart-container">
          <h2>Application Trends (Last 6 Months)</h2>
          <div class="chart-wrapper">
            <canvas id="monthlyTrendChart"></canvas>
          </div>
        </div>
        <div class="recent-activity-container">
          <h2>Recent Applications</h2>
          <div class="activity-list">
            <?php if (empty($recent_applications)): ?>
              <p>No recent applications.</p>
            <?php else: ?>
              <?php foreach ($recent_applications as $app): ?>
                <div class="activity-item">
                  <div class="activity-icon"><i class="fas fa-file-alt"></i></div>
                  <div class="activity-details">
                    <p><?= htmlspecialchars($app['business_name']) ?></p>
                    <span><span class="status-badge status-<?= strtolower(str_replace(' ', '-', $app['status'])) ?>"><?= htmlspecialchars(ucfirst($app['status'])) ?></span> &bull; <?= date('M d', strtotime($app['submitted_at'])) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  <script>
    // Monthly Trend Bar Chart
    new Chart(document.getElementById('monthlyTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($monthly_labels) ?>,
            datasets: [{
                label: 'Applications',
                data: <?= json_encode($monthly_counts) ?>,
                backgroundColor: 'rgba(74, 105, 189, 0.1)',
                borderColor: '#4a69bd',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
            plugins: { legend: { display: false } }
        }
    });
  </script>

<?php require_once './staff_footer.php'; ?>