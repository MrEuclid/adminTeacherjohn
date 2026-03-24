<?php
ob_start(); // 1. THIS MUST BE LINE 1. NO SPACES ABOVE IT.

// 2. Error Reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * authConf.php - The Central "Brain" of the PIO System
 */

// 3. Define Absolute Paths
$baseDir = __DIR__; 
$sessionPath = $baseDir . '/sessions';

// 4. Secure the private session folder
if (!is_dir($sessionPath)) {
    if (!mkdir($sessionPath, 0700, true) && !is_dir($sessionPath)) {
        die("Critical Error: Could not create session directory at $sessionPath");
    }
}

// 5. Configure Session Persistence (10-Hour Workday)
ini_set('session.save_path', $sessionPath);
ini_set('session.gc_maxlifetime', 36000); 

// 6. Set Browser Cookie for 10 hours
session_set_cookie_params(36000, '/', '', false, true);

// 7. Start the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 8. AUTO-CONNECT DATABASE
if (!isset($dbServer)) {
    $dbFile = $baseDir . "/includes/connect_db_euclid_pio.php";
    if (file_exists($dbFile)) {
        require_once $dbFile;
    } else {
        die("Critical Error: Database connection file not found at $dbFile");
    }
}

// 9. Security Headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("X-Frame-Options: DENY");

// No closing  tag. This is important!