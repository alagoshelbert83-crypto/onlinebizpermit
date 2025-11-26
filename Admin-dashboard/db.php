<?php
/**
 * Database Connection for Staff Dashboard
 */

// --- Database Configuration for your Hosting Provider (e.g., InfinityFree) ---
// IMPORTANT: Replace the placeholder values below with the actual credentials
// you get from your hosting provider's control panel after creating the database.

define('DB_HOST', 'sql302.infinityfree.com'); // <-- Your MySQL Host from InfinityFree
define('DB_USER', 'if0_40313162');      // <-- Your MySQL User from InfinityFree
define('DB_PASS', '83870oEAzLrDVmd'); // <-- Your InfinityFree account password
define('DB_NAME', 'if0_40313162_onlinebizpermit'); // <-- Your MySQL Database name from InfinityFree

// --- Establish the Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check for Connection Errors ---
if ($conn->connect_error) {
    // Use a more generic error in production
    $error_message = "Database connection failed.";
    die($error_message);
}

$conn->set_charset("utf8mb4");
?>