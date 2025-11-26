<?php
/**
 * Database Connection for Staff Dashboard
 */

// --- Database Configuration for your Hosting Provider (e.g., InfinityFree) ---
// IMPORTANT: Replace the placeholder values below with the actual credentials
// you get from your hosting provider's control panel after creating the database.

define('DB_HOST', getenv('DB_HOST') ?: 'sql302.infinityfree.com'); // PlanetScale host or fallback
define('DB_USER', getenv('DB_USER') ?: 'if0_40313162');      // PlanetScale user or fallback
define('DB_PASS', getenv('DB_PASS') ?: '83870oEAzLrDVmd'); // PlanetScale password or fallback
define('DB_NAME', getenv('DB_NAME') ?: 'if0_40313162_onlinebizpermit'); // PlanetScale database or fallback

// --- Establish the Connection ---
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// --- Check for Connection Errors ---
if ($conn->connect_error) {
    // Use a more generic error in production
    $error_message = "Database connection failed.";
    die($error_message);
}

$conn->set_charset("utf8mb4");

// Include custom session handler for serverless compatibility
require_once __DIR__ . '/session_handler.php';

// Include file upload helper for cloud storage
require_once __DIR__ . '/file_upload_helper.php';
?>
