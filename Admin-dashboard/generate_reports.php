<?php
session_start();
require './db.php';
 
// Security check: Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("HTTP/1.1 403 Forbidden");
    exit;
}

// --- Fetch all users for the report ---
// --- Application KPI Counts ---
$kpi_sql = "SELECT
                COUNT(*) as total_applications,
                SUM(CASE WHEN status IN ('approved', 'complete') THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN status IN ('pending', 'for review', 'review') THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
            FROM applications";
$kpi_result = $conn->query($kpi_sql);
$kpis = $kpi_result ? $kpi_result->fetch_assoc() : [];
 
// --- User KPI Counts ---
$user_kpi_sql = "SELECT
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM users WHERE role = 'staff') as staff_count,
    (SELECT COUNT(*) FROM users WHERE role = 'user' AND is_approved = 0) as pending_users,
    (SELECT COUNT(*) FROM users WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')) as new_users_this_month
    FROM DUAL";
$user_kpi_result = $conn->query($user_kpi_sql);
$user_kpis = $user_kpi_result ? $user_kpi_result->fetch_assoc() : [];

// --- Generate CSV ---
$filename = "onlinebizpermit_summary_report_" . date('Y-m-d') . ".csv";

// Set headers to force download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
 
$output = fopen('php://output', 'w');
 
// Add headers to the CSV
fputcsv($output, ['OnlineBizPermit Summary Report']);
fputcsv($output, ['Generated on:', date('Y-m-d H:i:s')]);
fputcsv($output, []); // Blank line
 
// Add Application Statistics
fputcsv($output, ['Application Statistics']);
fputcsv($output, ['Metric', 'Value']);
fputcsv($output, ['Total Applications', $kpis['total_applications'] ?? 0]);
fputcsv($output, ['Approved / Completed', $kpis['approved_count'] ?? 0]);
fputcsv($output, ['Pending / In Review', $kpis['pending_count'] ?? 0]);
fputcsv($output, ['Rejected', $kpis['rejected_count'] ?? 0]);
fputcsv($output, []); // Blank line
 
// Add User Statistics
fputcsv($output, ['User Statistics']);
fputcsv($output, ['Metric', 'Value']);
fputcsv($output, ['Total Users', $user_kpis['total_users'] ?? 0]);
fputcsv($output, ['New Users (This Month)', $user_kpis['new_users_this_month'] ?? 0]);
fputcsv($output, ['Pending Users', $user_kpis['pending_users'] ?? 0]);
fputcsv($output, ['Staff Accounts', $user_kpis['staff_count'] ?? 0]);
 
fclose($output);
exit;